<?php

namespace App\Http\Controllers;

use App\Http\Requests\BroadcastRequest;
use App\Models\Attachment;
use App\Models\Broadcast;
use App\Models\BroadcastCategory;
use App\Models\Team;
use App\Models\User;
use App\Services\BroadcastService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BroadcastController extends Controller
{
    public function __construct(protected BroadcastService $broadcastService)
    {
        view()->share('pageTitle', 'Broadcast Management');
    }

    public function index(Request $request): View
    {
        $perPage = (int) $request->input('per_page', config('constants.per_page_count', 20));

        return view('broadcasts.index', [
            'broadcasts' => $this->broadcastService->paginate($request->all(), $perPage),
            'perPage' => $perPage,
            'categories' => BroadcastCategory::query()->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'color']),
            'priorityOptions' => $this->optionObjects(Broadcast::priorityOptions()),
            'audienceOptions' => $this->optionObjects(Broadcast::audienceOptions()),
            'statusOptions' => $this->optionObjects(Broadcast::statusOptions()),
        ]);
    }

    public function create(): JsonResponse
    {
        $defaultCategoryId = BroadcastCategory::query()
            ->active()
            ->where('is_default', true)
            ->orderBy('sort_order')
            ->value('id');

        return response()->json([
            'status' => true,
            'html' => view('broadcasts.partials.broadcast-form-modal', [
                'broadcast' => new Broadcast([
                    'priority' => Broadcast::PRIORITY_NORMAL,
                    'audience_type' => Broadcast::AUDIENCE_ALL,
                    'status' => Broadcast::STATUS_DRAFT,
                    'broadcast_category_id' => $defaultCategoryId,
                ]),
                ...$this->formOptions(),
            ])->render(),
        ]);
    }

    public function store(BroadcastRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        if ((int) $data['status'] === Broadcast::STATUS_PUBLISHED && ! $request->user()->can('broadcast.status_change')) {
            abort(403, 'You are not authorized to publish broadcasts.');
        }

        $broadcast = $this->broadcastService->create($data, $request->user(), $request->file('attachments', []));
        $message = $broadcast->status === Broadcast::STATUS_PUBLISHED
            ? 'Broadcast published successfully.'
            : 'Broadcast created successfully.';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['status' => true, 'message' => $message]);
        }

        return redirect()->route('broadcasts.index')->with('success', $message);
    }

    public function show(Broadcast $broadcast): JsonResponse
    {
        $broadcast->load([
            'category:id,name,color',
            'creator:id,name',
            'updater:id,name',
            'recipients:id,name,email',
            'attachments',
        ]);

        $teams = collect();
        $teamNames = collect();
        if ($broadcast->audience_type === Broadcast::AUDIENCE_TEAMS) {
            $teams = Team::query()
                ->whereIn('id', $broadcast->target_team_ids ?? [])
                ->withCount('users')
                ->orderBy('name')
                ->get();
            $teamNames = $teams->pluck('name');
        }

        return response()->json([
            'status' => true,
            'html' => view('broadcasts.partials.broadcast-details-modal', compact('broadcast', 'teams', 'teamNames'))->render(),
        ]);
    }

    public function edit(Broadcast $broadcast): JsonResponse
    {
        $broadcast->load(['recipients:id,name,email', 'attachments']);

        return response()->json([
            'status' => true,
            'html' => view('broadcasts.partials.broadcast-form-modal', [
                'broadcast' => $broadcast,
                ...$this->formOptions(),
            ])->render(),
        ]);
    }

    public function update(BroadcastRequest $request, Broadcast $broadcast): JsonResponse|RedirectResponse
    {
        $this->broadcastService->update($broadcast, $request->validated(), $request->user(), $request->file('attachments', []));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['status' => true, 'message' => 'Broadcast updated successfully.']);
        }

        return redirect()->route('broadcasts.index')->with('success', 'Broadcast updated successfully.');
    }

    public function updateStatus(Request $request, Broadcast $broadcast): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'integer', Rule::in([
                Broadcast::STATUS_DRAFT,
                Broadcast::STATUS_PUBLISHED,
                Broadcast::STATUS_ARCHIVED,
            ])],
        ]);

        $updated = $this->broadcastService->changeStatus($broadcast, (int) $validated['status'], $request->user());

        $statusLabel = Broadcast::statusOptions()[$updated->status] ?? 'Status';

        return response()->json([
            'status' => true,
            'message' => "Broadcast status updated to {$statusLabel}.",
        ]);
    }

    public function destroy(Request $request, Broadcast $broadcast): JsonResponse|RedirectResponse
    {
        $this->broadcastService->delete($broadcast);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['status' => true, 'message' => 'Broadcast deleted successfully.']);
        }

        return redirect()->route('broadcasts.index')->with('success', 'Broadcast deleted successfully.');
    }

    public function deleteAttachment(Broadcast $broadcast, Attachment $attachment): JsonResponse
    {
        abort_unless($this->broadcastService->deleteAttachment($broadcast, $attachment), 404);

        return response()->json(['status' => true, 'message' => 'Attachment removed successfully.']);
    }

    public function downloadAttachment(Broadcast $broadcast, Attachment $attachment): StreamedResponse
    {
        $this->ensureBroadcastAttachment($broadcast, $attachment);
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
            'categories' => BroadcastCategory::query()->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'color']),
            'teams' => Team::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->active()->where('delete_status', false)->orderBy('name')->get(['id', 'name', 'email']),
            'priorityOptions' => Broadcast::priorityOptions(),
            'audienceOptions' => Broadcast::audienceOptions(),
        ];
    }

    protected function optionObjects(array $options): \Illuminate\Support\Collection
    {
        return collect($options)->map(fn($name, $id) => (object) ['id' => $id, 'name' => $name])->values();
    }

    protected function ensureBroadcastAttachment(Broadcast $broadcast, Attachment $attachment): void
    {
        abort_unless(
            (int) $attachment->link_id === (int) $broadcast->id
                && $attachment->link_type === Broadcast::class
                && $attachment->category === Broadcast::ATTACHMENT_CATEGORY,
            404,
        );
    }
}
