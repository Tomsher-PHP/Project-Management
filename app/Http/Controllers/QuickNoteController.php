<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuickNoteReorderRequest;
use App\Http\Requests\QuickNoteRequest;
use App\Models\Project;
use App\Models\QuickNote;
use App\Models\Task;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuickNoteController extends Controller
{
    protected string $pageTitle;

    public function __construct()
    {
        $this->pageTitle = 'Quick Notes';
        view()->share(['pageTitle' => $this->pageTitle]);
    }

    /**
     * Render the Quick Notes drawer content via AJAX.
     */
    public function drawer(Request $request): JsonResponse
    {
        $userId = auth()->id();

        $notes = QuickNote::query()
            ->where('user_id', $userId)
            ->orderBy('is_pinned', 'desc')
            ->orderBy('sort_order', 'asc')
            ->orderBy('updated_at', 'desc')
            ->get();

        $html = view('quick-notes.partials.drawer-content', compact('notes'))->render();

        return response()->json([
            'status' => true,
            'html' => $html,
            'count' => $notes->where('is_archived', false)->count(),
        ]);
    }

    /**
     * Display a listing of the user's quick notes.
     */
    public function index(Request $request): View|JsonResponse
    {
        $userId = auth()->id();
        $user = auth()->user();

        $query = QuickNote::query()
            ->where('user_id', $userId)
            ->with([
                'project:id,name,project_code',
                'task:id,name,code,project_id',
            ]);

        // Optional filter: archived vs active (default: active if not specified)
        if ($request->has('is_archived')) {
            $query->where('is_archived', filter_var($request->input('is_archived'), FILTER_VALIDATE_BOOLEAN));
        }

        // Optional filter: pinned status
        if ($request->has('is_pinned')) {
            $query->where('is_pinned', filter_var($request->input('is_pinned'), FILTER_VALIDATE_BOOLEAN));
        }

        // Optional filter by project
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->input('project_id'));
        }

        // Optional filter by task
        if ($request->filled('task_id')) {
            $query->where('task_id', $request->input('task_id'));
        }

        // Optional search in title or content
        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                    ->orWhere('content', 'like', $search);
            });
        }

        // Ordering: Pinned first, then by sort_order asc, then by updated_at / id desc
        $notes = $query
            ->orderBy('is_pinned', 'desc')
            ->orderBy('sort_order', 'asc')
            ->orderBy('updated_at', 'desc')
            ->get();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'data' => $notes,
            ]);
        }

        $projects = Project::query()
            ->accessibleBy($user)
            ->orderBy('name')
            ->get(['id', 'name', 'project_code']);

        $tasks = Task::query()
            ->accessibleBy($user)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'project_id']);

        return view('quick-notes.index', compact('notes', 'projects', 'tasks'));
    }

    /**
     * Store a newly created quick note.
     */
    public function store(QuickNoteRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = auth()->user();

        // Enforce maximum 4 pinned notes limit
        if (! empty($validated['is_pinned'])) {
            $pinnedCount = QuickNote::where('user_id', $user->id)
                ->where('is_pinned', true)
                ->where('is_archived', false)
                ->count();

            if ($pinnedCount >= 4) {
                return response()->json([
                    'status' => false,
                    'message' => 'You can pin a maximum of 4 notes.',
                ], 422);
            }
        }

        // Auto-assign project_id from task if task_id is provided and project_id is empty
        if (! empty($validated['task_id']) && empty($validated['project_id'])) {
            $task = Task::find($validated['task_id']);
            if ($task && $task->project_id) {
                $validated['project_id'] = $task->project_id;
            }
        }

        // Determine sort order if not specified
        if (! isset($validated['sort_order'])) {
            $maxSort = QuickNote::where('user_id', $user->id)->max('sort_order');
            $validated['sort_order'] = is_null($maxSort) ? 0 : $maxSort + 1;
        }

        $validated['user_id'] = $user->id;

        $note = QuickNote::create($validated);
        $note->load(['project:id,name,project_code', 'task:id,name,code,project_id']);

        return response()->json([
            'status' => true,
            'message' => 'Quick note created successfully.',
            'data' => $note,
            'html' => view('quick-notes.partials.note-card', ['note' => $note])->render(),
        ], 201);
    }

    /**
     * Display the specified quick note.
     */
    public function show(int|string $id): JsonResponse
    {
        $note = QuickNote::query()
            ->where('user_id', auth()->id())
            ->with(['project:id,name,project_code', 'task:id,name,code,project_id'])
            ->whereKey($id)
            ->firstOrFail();

        return response()->json([
            'status' => true,
            'data' => $note,
        ]);
    }

    /**
     * Update the specified quick note.
     */
    public function update(QuickNoteRequest $request, int|string $id): JsonResponse
    {
        $note = QuickNote::query()
            ->where('user_id', auth()->id())
            ->whereKey($id)
            ->firstOrFail();

        $validated = $request->validated();

        // Enforce maximum 4 pinned notes limit
        if (! empty($validated['is_pinned'])) {
            $pinnedCount = QuickNote::where('user_id', auth()->id())
                ->where('is_pinned', true)
                ->where('is_archived', false)
                ->where('id', '!=', $id)
                ->count();

            if ($pinnedCount >= 4) {
                return response()->json([
                    'status' => false,
                    'message' => 'You can pin a maximum of 4 notes.',
                ], 422);
            }
        }

        // Auto-assign project_id from task if task_id is provided and project_id is empty
        if (! empty($validated['task_id']) && empty($validated['project_id'])) {
            $task = Task::find($validated['task_id']);
            if ($task && $task->project_id) {
                $validated['project_id'] = $task->project_id;
            }
        }

        $note->update($validated);
        $note->load(['project:id,name,project_code', 'task:id,name,code,project_id']);

        return response()->json([
            'status' => true,
            'message' => 'Quick note updated successfully.',
            'data' => $note,
            'html' => view('quick-notes.partials.note-card', ['note' => $note])->render(),
        ]);
    }

    /**
     * Remove the specified quick note.
     */
    public function destroy(int|string $id): JsonResponse
    {
        $note = QuickNote::query()
            ->where('user_id', auth()->id())
            ->whereKey($id)
            ->firstOrFail();

        $note->delete();

        return response()->json([
            'status' => true,
            'message' => 'Quick note deleted successfully.',
        ]);
    }

    /**
     * Toggle or update pin status of a quick note.
     */
    public function togglePin(Request $request, int|string $id): JsonResponse
    {
        $note = QuickNote::query()
            ->where('user_id', auth()->id())
            ->whereKey($id)
            ->firstOrFail();

        $targetPinnedState = $request->has('is_pinned')
            ? filter_var($request->input('is_pinned'), FILTER_VALIDATE_BOOLEAN)
            : ! $note->is_pinned;

        if ($targetPinnedState) {
            $pinnedCount = QuickNote::where('user_id', auth()->id())
                ->where('is_pinned', true)
                ->where('is_archived', false)
                ->where('id', '!=', $id)
                ->count();

            if ($pinnedCount >= 4) {
                return response()->json([
                    'status' => false,
                    'message' => 'You can pin a maximum of 4 notes.',
                ], 422);
            }
        }

        $note->is_pinned = $targetPinnedState;
        $note->save();
        $note->load(['project:id,name,project_code', 'task:id,name,code,project_id']);

        return response()->json([
            'status' => true,
            'message' => $note->is_pinned ? 'Note pinned successfully.' : 'Note unpinned successfully.',
            'data' => $note,
            'html' => view('quick-notes.partials.note-card', ['note' => $note])->render(),
        ]);
    }

    /**
     * Toggle or update archive status of a quick note.
     */
    public function toggleArchive(Request $request, int|string $id): JsonResponse
    {
        $note = QuickNote::query()
            ->where('user_id', auth()->id())
            ->whereKey($id)
            ->firstOrFail();

        if ($request->has('is_archived')) {
            $note->is_archived = filter_var($request->input('is_archived'), FILTER_VALIDATE_BOOLEAN);
        } else {
            $note->is_archived = ! $note->is_archived;
        }

        $note->save();
        $note->load(['project:id,name,project_code', 'task:id,name,code,project_id']);

        return response()->json([
            'status' => true,
            'message' => $note->is_archived ? 'Note archived successfully.' : 'Note unarchived successfully.',
            'data' => $note,
            'html' => view('quick-notes.partials.note-card', ['note' => $note])->render(),
        ]);
    }

    /**
     * Update sort order for multiple notes belonging to the authenticated user.
     */
    public function reorder(QuickNoteReorderRequest $request): JsonResponse
    {
        $userId = auth()->id();
        $notesData = $request->input('notes', []);

        DB::transaction(function () use ($userId, $notesData) {
            foreach ($notesData as $item) {
                QuickNote::query()
                    ->where('user_id', $userId)
                    ->where('id', $item['id'])
                    ->update(['sort_order' => (int) $item['sort_order']]);
            }
        });

        return response()->json([
            'status' => true,
            'message' => 'Notes reordered successfully.',
        ]);
    }
}
