<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectChecklistRequest;
use App\Models\ChecklistTemplate;
use App\Models\Project;
use App\Models\ProjectChecklist;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ProjectChecklistController extends Controller
{
    public function renderChecklistsTab(Project $project): string
    {
        $users = User::whereHas('projectChecklists', function ($q) use ($project) {
                $q->where('project_id', $project->id);
            })
            ->with(['projectChecklists' => function ($q) use ($project) {
                $q->where('project_id', $project->id)->with('items');
            }])
            ->get();

        return view('projects.partials.tabs.checklists', compact('project', 'users'))->render();
    }
    public function show(Project $project, int $userId): JsonResponse
    {
        $member = $this->resolveProjectMember($project, $userId);

        $checklists = $project->projectChecklists()
            ->where('assigned_to', $member->id)
            ->with([
                'template:id,name',
                'items',
            ])
            ->get()
            ->map(fn(ProjectChecklist $checklist) => $this->serializeProjectChecklist($checklist))
            ->values();

        $library = ChecklistTemplate::query()
            ->active()
            ->with([
                'items' => fn($query) => $query->active()->orderBy('sort_order'),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn(ChecklistTemplate $template) => [
                'id' => (int) $template->id,
                'name' => $template->name,
                'questions' => $template->items
                    ->map(fn($item) => [
                        'id' => (int) $item->id,
                        'question' => $item->question,
                    ])
                    ->values(),
            ])
            ->values();

        return response()->json([
            'status' => true,
            'member' => [
                'id' => (int) $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'designation' => $member->designation_name,
                'avatar' => $member->profile_image_url,
            ],
            'checklists' => $checklists,
            'library' => $library,
            'save_url' => route('projects.checklists.update', [$project, $member->id]),
        ], Response::HTTP_OK);
    }

    public function update(ProjectChecklistRequest $request, Project $project, int $userId): JsonResponse
    {
        $member = $this->resolveProjectMember($project, $userId);
        $validated = $request->validated();

        $savedChecklists = DB::transaction(function () use ($project, $member, $validated) {
            $this->syncAssignedChecklists($project, $member, $validated['checklists'] ?? []);

            return $project->projectChecklists()
                ->where('assigned_to', $member->id)
                ->with([
                    'template:id,name',
                    'items',
                ])
                ->get();
        });

        $memberCard = view('projects.partials.member-card', [
            'project' => $project,
            'member' => $member,
            'checklistCount' => $savedChecklists->count(),
        ])->render();

        return response()->json([
            'status' => true,
            'message' => 'Project checklists saved successfully.',
            'checklist_count' => $savedChecklists->count(),
            'checklists' => $savedChecklists
                ->map(fn(ProjectChecklist $checklist) => $this->serializeProjectChecklist($checklist))
                ->values(),
            'member_card' => $memberCard,
        ], Response::HTTP_OK);
    }

    public function renderWorkspaceChecklist(Request $request, Project $project): JsonResponse
    {
        $checklist = $request->input('checklist', []);
        $checklistIndex = $request->input('checklistIndex', 0);
        $titleError = $request->input('titleError');
        $questionsError = $request->input('questionsError');
        $questionErrors = $request->input('questionErrors', []);

        $html = view('projects.partials.checklists.workspace-checklist', [
            'checklist' => $checklist,
            'checklistIndex' => $checklistIndex,
            'titleError' => $titleError,
            'questionsError' => $questionsError,
            'questionErrors' => $questionErrors,
        ])->render();

        return response()->json(['html' => $html]);
    }

    public function renderLibraryChecklist(Request $request, Project $project): JsonResponse
    {
        $templates = $request->input('templates', []);
        $selectedTemplateIds = $request->input('selectedTemplateIds', []);

        $html = view('projects.partials.checklists.library', [
            'templates' => $templates,
            'selectedTemplateIds' => $selectedTemplateIds,
        ])->render();

        return response()->json(['html' => $html]);
    }

    private function syncAssignedChecklists(Project $project, User $member, array $payload): void
    {
        $payloadCollection = collect($payload)->values();

        $existingChecklists = $project->projectChecklists()
            ->where('assigned_to', $member->id)
            ->with('items')
            ->get()
            ->keyBy('id');

        $incomingChecklistIds = $payloadCollection
            ->pluck('id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->all();

        $checklistIdsToDelete = $existingChecklists->keys()
            ->diff($incomingChecklistIds)
            ->values()
            ->all();

        if (!empty($checklistIdsToDelete)) {
            ProjectChecklist::query()
                ->whereIn('id', $checklistIdsToDelete)
                ->delete();
        }

        $payloadCollection->each(function (array $checklistData) use ($project, $member, $existingChecklists) {
            $projectChecklist = null;

            if (!empty($checklistData['id'])) {
                $projectChecklist = $existingChecklists->get((int) $checklistData['id']);
            }

            if (!$projectChecklist) {
                $projectChecklist = new ProjectChecklist([
                    'project_id' => $project->id,
                    'assigned_to' => $member->id,
                ]);
            }

            $projectChecklist->fill([
                'checklist_template_id' => $checklistData['checklist_template_id'] ?: null,
                'title' => $checklistData['title'],
            ]);
            $projectChecklist->save();

            $this->syncChecklistItems($projectChecklist, $checklistData['questions'] ?? []);
        });
    }

    private function syncChecklistItems(ProjectChecklist $projectChecklist, array $items): void
    {
        $payloadCollection = collect($items)->values();
        $existingItems = $projectChecklist->items()->get()->keyBy('id');

        $incomingItemIds = $payloadCollection
            ->pluck('id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->all();

        $itemIdsToDelete = $existingItems->keys()
            ->diff($incomingItemIds)
            ->values()
            ->all();

        if (!empty($itemIdsToDelete)) {
            $projectChecklist->items()->whereIn('id', $itemIdsToDelete)->delete();
        }

        $payloadCollection->each(function (array $itemData, int $index) use ($projectChecklist, $existingItems) {
            $checklistItem = null;

            if (!empty($itemData['id'])) {
                $checklistItem = $existingItems->get((int) $itemData['id']);
            }

            if (!$checklistItem) {
                $checklistItem = $projectChecklist->items()->make();
            }

            $checklistItem->fill([
                'question' => $itemData['question'],
                'sort_order' => $index + 1,
            ]);
            $checklistItem->save();
        });
    }

    private function resolveProjectMember(Project $project, int $userId): User
    {
        return $project->membersAll()
            ->with([
                'details.designation',
                'primaryAttachment',
            ])
            ->where('users.id', $userId)
            ->whereNull('project_members.removed_at')
            ->firstOrFail();
    }

    private function serializeProjectChecklist(ProjectChecklist $checklist): array
    {
        return [
            'id' => (int) $checklist->id,
            'title' => $checklist->title,
            'checklist_template_id' => $checklist->checklist_template_id ? (int) $checklist->checklist_template_id : null,
            'template_name' => $checklist->template?->name,
            'questions' => $checklist->items
                ->map(fn($item) => [
                    'id' => (int) $item->id,
                    'question' => $item->question,
                    'status' => (int) $item->status,
                ])
                ->values(),
        ];
    }
}
