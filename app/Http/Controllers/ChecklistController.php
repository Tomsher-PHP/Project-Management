<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChecklistRequest;
use App\Models\ChecklistTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ChecklistController extends Controller
{
    protected string $pageTitle;
    protected string $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Checklist Templates';
        $this->subTitle = 'Manage your checklist templates here';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function index(Request $request): View
    {
        return view('settings.checklists.index', $this->getIndexViewData($request));
    }

    public function store(ChecklistRequest $request): JsonResponse
    {
        $checklist = DB::transaction(function () use ($request) {
            $validated = $request->validated();

            $checklist = ChecklistTemplate::create([
                'name' => $validated['name'],
            ]);

            $this->syncChecklistItems($checklist, $validated['questions']);

            return $checklist->load('items');
        });

        return response()->json([
            'status' => true,
            'message' => 'Checklist created successfully.',
            'data' => $checklist,
            'redirect_url' => route('settings.checklists.index'),
            'html' => $this->renderIndexContent($this->indexRequestFromQuery($request)),
            'render_target' => '#checklist-template-index-content',
            'render_mode' => 'replace_inner',
        ]);
    }

    public function update(ChecklistRequest $request, ChecklistTemplate $checklist): JsonResponse
    {
        DB::transaction(function () use ($request, $checklist) {
            $validated = $request->validated();

            $checklist->update([
                'name' => $validated['name'],
            ]);

            $this->syncChecklistItems($checklist, $validated['questions']);
        });

        return response()->json([
            'status' => true,
            'message' => 'Checklist updated successfully.',
            'data' => $checklist->fresh('items'),
            'redirect_url' => route('settings.checklists.index'),
            'html' => $this->renderIndexContent($this->indexRequestFromQuery($request)),
            'render_target' => '#checklist-template-index-content',
            'render_mode' => 'replace_inner',
        ]);
    }

    public function destroy(ChecklistTemplate $checklist)
    {
        if ($checklist->is_system) {
            return redirect()
                ->route('settings.checklists.index')
                ->with('error', 'System Checklist cannot be deleted.');
        }

        $checklist->delete();

        return redirect()
            ->route('settings.checklists.index')
            ->with('success', 'Checklist deleted successfully.');
    }

    public function toggleStatusChecklist(Request $request): JsonResponse
    {
        $checklistTemplate = ChecklistTemplate::findOrFail($request->id);
        $checklistTemplate->is_active = !$checklistTemplate->is_active;
        $checklistTemplate->save();

        return response()->json([
            'success' => true,
            'is_active' => $checklistTemplate->is_active,
            'message' => 'Status updated successfully'
        ], Response::HTTP_OK);
    }

    private function getIndexViewData(Request $request): array
    {
        $perPage = $request->input('per_page', config('constants.per_page_count'));
        $baseQuery = ChecklistTemplate::query();

        return [
            'checklists' => ChecklistTemplate::with('items')
                ->withCount('items')
                ->filter($request->all())
                ->sort($request->all())
                ->paginate($perPage)
                ->withQueryString(),
            'perPage' => $perPage,
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'active' => (clone $baseQuery)->where('is_active', true)->count(),
                'system' => (clone $baseQuery)->where('is_system', true)->count(),
            ],
        ];
    }

    private function renderIndexContent(Request $request): string
    {
        return view('settings.checklists.partials.index-content', $this->getIndexViewData($request))->render();
    }

    private function indexRequestFromQuery(Request $request): Request
    {
        return Request::create(
            $request->path(),
            'GET',
            $request->query()
        );
    }

    private function syncChecklistItems(ChecklistTemplate $checklist, array $questions): void
    {
        $checklist->items()->delete();

        $checklist->items()->createMany(
            collect($questions)
                ->values()
                ->map(fn (string $question, int $index) => [
                    'question' => $question,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ])
                ->all()
        );
    }
}
