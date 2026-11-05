<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulletinRequest;
use App\Models\Attachment;
use App\Models\Bulletin;
use App\Models\BulletinCategory;
use App\Models\Team;
use App\Models\User;
use App\Services\BulletinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulletinController extends Controller
{
    public function __construct(protected BulletinService $bulletinService)
    {
        view()->share('pageTitle', 'Bulletin Management');
    }

    public function index(Request $request): View
    {
        $perPage = min(500, max(5, (int) $request->input('per_page', config('constants.per_page_count', 20))));

        return view('bulletins.index', [
            'bulletins' => $this->bulletinService->paginate($request->all(), $perPage),
            'perPage' => $perPage,
            'categories' => BulletinCategory::query()->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'color']),
            'priorityOptions' => $this->optionObjects(Bulletin::priorityOptions()),
            'audienceOptions' => $this->optionObjects(Bulletin::audienceOptions()),
            'statusOptions' => $this->optionObjects(Bulletin::statusOptions()),
        ]);
    }

    public function create(): JsonResponse
    {
        $defaultCategoryId = BulletinCategory::query()
            ->active()
            ->where('is_default', true)
            ->orderBy('sort_order')
            ->value('id');

        return response()->json([
            'status' => true,
            'html' => view('bulletins.partials.bulletin-form-modal', [
                'bulletin' => new Bulletin([
                    'priority' => Bulletin::PRIORITY_NORMAL,
                    'audience_type' => Bulletin::AUDIENCE_ALL,
                    'status' => Bulletin::STATUS_DRAFT,
                    'bulletin_category_id' => $defaultCategoryId,
                ]),
                ...$this->formOptions(),
            ])->render(),
        ]);
    }

    public function store(BulletinRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        if ((int) $data['status'] === Bulletin::STATUS_PUBLISHED && ! $request->user()->can('bulletin.status_change')) {
            abort(403, 'You are not authorized to publish bulletins.');
        }

        $bulletin = $this->bulletinService->create($data, $request->user(), $request->file('attachments', []));
        $message = $bulletin->status === Bulletin::STATUS_PUBLISHED
            ? 'Bulletin published successfully.'
            : 'Bulletin created successfully.';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['status' => true, 'message' => $message]);
        }

        return redirect()->route('bulletins.index')->with('success', $message);
    }

    public function show(Bulletin $bulletin): JsonResponse
    {
        $bulletin->load([
            'category:id,name,color',
            'creator:id,name',
            'updater:id,name',
            'recipients:id,name,email',
            'attachments',
        ]);

        $teamNames = collect();
        if ($bulletin->audience_type === Bulletin::AUDIENCE_TEAMS) {
            $teamNames = Team::query()->whereIn('id', $bulletin->target_team_ids ?? [])->orderBy('name')->pluck('name');
        }

        return response()->json([
            'status' => true,
            'html' => view('bulletins.partials.bulletin-details-modal', compact('bulletin', 'teamNames'))->render(),
        ]);
    }

    public function edit(Bulletin $bulletin): JsonResponse
    {
        $bulletin->load(['recipients:id,name,email', 'attachments']);

        return response()->json([
            'status' => true,
            'html' => view('bulletins.partials.bulletin-form-modal', [
                'bulletin' => $bulletin,
                ...$this->formOptions(),
            ])->render(),
        ]);
    }

    public function update(BulletinRequest $request, Bulletin $bulletin): JsonResponse|RedirectResponse
    {
        $this->bulletinService->update($bulletin, $request->validated(), $request->user(), $request->file('attachments', []));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['status' => true, 'message' => 'Bulletin updated successfully.']);
        }

        return redirect()->route('bulletins.index')->with('success', 'Bulletin updated successfully.');
    }

    public function updateStatus(Request $request, Bulletin $bulletin): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'integer', Rule::in([
                Bulletin::STATUS_PUBLISHED,
                Bulletin::STATUS_ARCHIVED,
            ])],
        ]);

        $updated = $this->bulletinService->changeStatus($bulletin, (int) $validated['status'], $request->user());

        return response()->json([
            'status' => true,
            'message' => $updated->status === Bulletin::STATUS_PUBLISHED
                ? 'Bulletin published successfully.'
                : 'Bulletin archived successfully.',
        ]);
    }

    public function destroy(Request $request, Bulletin $bulletin): JsonResponse|RedirectResponse
    {
        $this->bulletinService->delete($bulletin);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['status' => true, 'message' => 'Bulletin deleted successfully.']);
        }

        return redirect()->route('bulletins.index')->with('success', 'Bulletin deleted successfully.');
    }

    public function deleteAttachment(Bulletin $bulletin, Attachment $attachment): JsonResponse
    {
        abort_unless($this->bulletinService->deleteAttachment($bulletin, $attachment), 404);

        return response()->json(['status' => true, 'message' => 'Attachment removed successfully.']);
    }

    public function downloadAttachment(Bulletin $bulletin, Attachment $attachment): StreamedResponse
    {
        $this->ensureBulletinAttachment($bulletin, $attachment);
        $disk = $attachment->disk ?? config('filesystems.default');
        abort_unless(Storage::disk($disk)->exists($attachment->file_path), 404);

        return Storage::disk($disk)->download(
            $attachment->file_path,
            $attachment->original_name ?: $attachment->file_name,
        );
    }

    protected function formOptions(): array
    {
        return [
            'categories' => BulletinCategory::query()->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'color']),
            'teams' => Team::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->active()->where('delete_status', false)->orderBy('name')->get(['id', 'name', 'email']),
            'priorityOptions' => Bulletin::priorityOptions(),
            'audienceOptions' => Bulletin::audienceOptions(),
        ];
    }

    protected function optionObjects(array $options): \Illuminate\Support\Collection
    {
        return collect($options)->map(fn ($name, $id) => (object) ['id' => $id, 'name' => $name])->values();
    }

    protected function ensureBulletinAttachment(Bulletin $bulletin, Attachment $attachment): void
    {
        abort_unless(
            (int) $attachment->link_id === (int) $bulletin->id
            && $attachment->link_type === Bulletin::class
            && $attachment->category === Bulletin::ATTACHMENT_CATEGORY,
            404,
        );
    }
}
