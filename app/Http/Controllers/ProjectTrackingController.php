<?php

namespace App\Http\Controllers;

use App\Exports\ProjectTrackingExport;
use App\Models\Attachment;
use App\Models\Project;
use App\Models\ProjectTracking;
use App\Services\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectTrackingController extends Controller
{
    /**
     * Store a new project tracking entry.
     */
    public function store(
        Request $request,
        Project $project
    ): RedirectResponse|JsonResponse {
        $validated = $request->validate([
            'date' => [
                'required',
                'date',
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'attachments' => [
                'nullable',
                'array',
                'max:5',
            ],

            'attachments.*' => [
                'file',
                'max:15360',
            ],
        ]);

        DB::beginTransaction();

        try {
            /*
             * Create tracking record through the project
             * relationship so project_id is automatically assigned.
             */
            $projectTracking = $project->trackings()->create([
                'date' => $validated['date'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
            ]);

            /*
             * Store attachments through the existing
             * AttachmentService.
             */
            if ($request->hasFile('attachments')) {
                $attachmentService = app(
                    AttachmentService::class
                );

                foreach (
                    $request->file('attachments')
                    as $file
                ) {
                    if (!$file->isValid()) {
                        continue;
                    }

                    $attachmentService->upload(
                        $file,
                        'project_trackings',
                        $projectTracking,
                        'public',
                        'public',
                        false,
                        'project_tracking'
                    );
                }
            }

            DB::commit();

            /*
             * AJAX / JSON response.
             */
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' =>
                    'Project tracking added successfully.',
                    'html' =>
                    $this->renderTrackingList($project),
                ]);
            }

            /*
             * Normal non-AJAX response.
             */
            return redirect()
                ->to(
                    route(
                        'projects.tabs.show',
                        [
                            'project' => $project,
                            'tab' => 'history',
                        ]
                    )
                )
                ->with(
                    'success',
                    'Project tracking added successfully.'
                );
        } catch (\Throwable $e) {
            DB::rollBack();

            report($e);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' =>
                    'Unable to save the project tracking. Please try again.',
                ], 500);
            }

            return back()
                ->withInput()
                ->withErrors([
                    'tracking' =>
                    'Unable to save the project tracking. Please try again.',
                ]);
        }
    }


    /**
     * Display a project tracking entry.
     *
     * Used by AJAX for:
     *
     * View:
     * /trackings/{tracking}
     *
     * Edit:
     * /trackings/{tracking}?mode=edit
     */
    public function show(
        Request $request,
        Project $project,
        ProjectTracking $projectTracking
    ): JsonResponse {
        $this->ensureProjectTrackingBelongsToProject(
            $project,
            $projectTracking
        );

        $projectTracking->load([
            'attachments',
            'attachments.addedBy:id,name',
        ]);

        /*
         * Edit mode.
         *
         * IMPORTANT:
         *
         * The URL contains:
         *
         * ?mode=edit
         *
         * Therefore use query('mode') === 'edit'.
         */
        if (
            $request->query('mode') === 'edit'
        ) {
            return response()->json([
                'success' => true,

                'tracking' => [
                    'id' =>
                    $projectTracking->id,

                    'date' =>
                    $projectTracking
                        ->date
                        ?->format('Y-m-d'),

                    'title' =>
                    $projectTracking->title,

                    'description' =>
                    $projectTracking->description,

                    'update_url' =>
                    route(
                        'projects.trackings.update',
                        [
                            $project,
                            $projectTracking,
                        ]
                    ),

                    'attachments' =>
                    $projectTracking
                        ->attachments
                        ->map(
                            function ($attachment) {
                                return [
                                    'id' =>
                                    $attachment->id,

                                    'original_name' =>
                                    $attachment->original_name,

                                    'file_name' =>
                                    $attachment->file_name,

                                    'url' =>
                                    $attachment->url,
                                ];
                            }
                        )
                        ->values()
                        ->all(),
                ],
            ]);
        }

        /*
         * Normal View request.
         */
        return response()->json([
            'success' => true,

            'html' => view(
                'projects.partials.project-trackings.view',
                compact(
                    'project',
                    'projectTracking'
                )
            )->render(),
        ]);
    }


    /**
     * Update a project tracking entry.
     */
    public function update(
        Request $request,
        Project $project,
        ProjectTracking $projectTracking
    ): RedirectResponse|JsonResponse {
        $this->ensureProjectTrackingBelongsToProject(
            $project,
            $projectTracking
        );

        $validated = $request->validate([
            'date' => [
                'required',
                'date',
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'attachments' => [
                'nullable',
                'array',
                'max:5',
            ],

            'attachments.*' => [
                'file',
                'max:15360',
            ],

            /*
             * Existing attachment IDs selected for removal.
             */
            'remove_attachments' => [
                'nullable',
                'array',
            ],

            'remove_attachments.*' => [
                'integer',
                'exists:attachments,id',
            ],
        ]);

        DB::beginTransaction();

        try {
            /*
             * IDs that the user selected by clicking Remove.
             */
            $removeAttachmentIds = collect(
                $validated['remove_attachments'] ?? []
            )
                ->map(
                    fn($id) => (int) $id
                )
                ->unique()
                ->values();

            /*
             * IMPORTANT:
             *
             * Only retrieve attachments belonging to THIS
             * ProjectTracking record.
             *
             * This prevents someone from submitting an
             * unrelated attachment ID and deleting it.
             */
            $attachmentsToRemove =
                $projectTracking
                ->attachments()
                ->whereIn(
                    'id',
                    $removeAttachmentIds
                )
                ->get();

            /*
             * Count all existing attachments.
             */
            $existingCount =
                $projectTracking
                ->attachments()
                ->count();

            /*
             * Count attachments that will remain after
             * the selected removals.
             */
            $remainingCount =
                $existingCount -
                $attachmentsToRemove->count();

            /*
             * New files uploaded during this update.
             */
            $newFiles =
                $request->file('attachments', []);

            /*
             * Final attachment count cannot exceed 5.
             */
            if (
                $remainingCount +
                count($newFiles) >
                5
            ) {
                DB::rollBack();

                $message =
                    'A project tracking entry can have a maximum of 5 attachments.';

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'errors' => [
                            'attachments' => [
                                $message,
                            ],
                        ],
                    ], 422);
                }

                return back()
                    ->withInput()
                    ->withErrors([
                        'attachments' => $message,
                    ]);
            }

            /*
             * Update tracking details.
             */
            $projectTracking->update([
                'date' =>
                $validated['date'],

                'title' =>
                $validated['title'],

                'description' =>
                $validated['description'] ?? null,
            ]);

            /*
             * Delete selected existing attachments.
             *
             * This happens ONLY when Update is submitted.
             *
             * Delete:
             *
             * 1. Physical file
             * 2. Attachment database record
             */
            foreach (
                $attachmentsToRemove
                as $attachment
            ) {
                if (
                    $attachment->file_path
                ) {
                    $disk =
                        $attachment->disk
                        ?: config(
                            'filesystems.default'
                        );

                    Storage::disk($disk)->delete(
                        $attachment->file_path
                    );
                }

                $attachment->delete();
            }

            /*
             * Add new attachments.
             */
            if (!empty($newFiles)) {
                $attachmentService =
                    app(
                        AttachmentService::class
                    );

                foreach (
                    $newFiles
                    as $file
                ) {
                    if (!$file->isValid()) {
                        continue;
                    }

                    $attachmentService->upload(
                        $file,
                        'project_trackings',
                        $projectTracking,
                        'public',
                        'public',
                        false,
                        'project_tracking'
                    );
                }
            }

            DB::commit();

            /*
             * AJAX / JSON response.
             */
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' =>
                    'Project tracking updated successfully.',
                    'html' =>
                    $this->renderTrackingList($project),
                ]);
            }

            /*
             * Normal non-AJAX response.
             */
            return redirect()
                ->route(
                    'projects.trackings.show',
                    [
                        $project,
                        $projectTracking,
                    ]
                )
                ->with(
                    'success',
                    'Project tracking updated successfully.'
                );
        } catch (\Throwable $e) {
            DB::rollBack();

            report($e);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' =>
                    'Unable to update the project tracking. Please try again.',
                ], 500);
            }

            return back()
                ->withInput()
                ->withErrors([
                    'tracking' =>
                    'Unable to update the project tracking. Please try again.',
                ]);
        }
    }


    /**
     * Delete a project tracking entry.
     */
    public function destroy(
        Request $request,
        Project $project,
        ProjectTracking $projectTracking
    ): RedirectResponse|JsonResponse {
        $this->ensureProjectTrackingBelongsToProject(
            $project,
            $projectTracking
        );

        DB::beginTransaction();

        try {
            /*
             * Load attachments before deleting the tracking.
             */
            $projectTracking->load(
                'attachments'
            );

            /*
             * Delete physical files and attachment
             * database records.
             */
            foreach (
                $projectTracking->attachments
                as $attachment
            ) {
                if (
                    $attachment->file_path
                ) {
                    $disk =
                        $attachment->disk
                        ?: config(
                            'filesystems.default'
                        );

                    Storage::disk($disk)->delete(
                        $attachment->file_path
                    );
                }

                $attachment->delete();
            }

            /*
             * Delete the tracking record.
             */
            $projectTracking->delete();

            DB::commit();

            /*
             * AJAX / JSON response.
             */
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' =>
                    'Project tracking deleted successfully.',
                    'html' =>
                    $this->renderTrackingList($project),
                ]);
            }

            /*
             * Normal non-AJAX response.
             */
            return redirect()
                ->to(
                    route(
                        'projects.tabs.show',
                        [
                            'project' => $project,
                            'tab' => 'history',
                        ]
                    )
                )
                ->with(
                    'success',
                    'Project tracking deleted successfully.'
                );
        } catch (\Throwable $e) {
            DB::rollBack();

            report($e);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' =>
                    'Unable to delete the project tracking. Please try again.',
                ], 500);
            }

            return back()
                ->withErrors([
                    'tracking' =>
                    'Unable to delete the project tracking. Please try again.',
                ]);
        }
    }


    /**
     * Render the project tracking list.
     */
    protected function renderTrackingList(
        Project $project
    ): string {
        $projectTrackings =
            $project
            ->trackings()
            ->with([
                'attachments',
                'attachments.addedBy:id,name',
            ])
            ->latest('date')
            ->latest('id')
            ->get();

        return view(
            'projects.partials.project-trackings.show',
            compact(
                'project',
                'projectTrackings'
            )
        )->render();
    }


    /**
     * Make sure the tracking belongs to the current project.
     */
    protected function ensureProjectTrackingBelongsToProject(
        Project $project,
        ProjectTracking $projectTracking
    ): void {
        abort_unless(
            (int) $projectTracking->project_id ===
                (int) $project->id,
            404
        );
    }

    /**
     * Export multiple project trackings.
     * @param  Request $request
     * @param  Project $project
     */
    public function bulkExport(Request $request, Project $project)
    {
        $validated = $request->validate([
            'tracking_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'tracking_ids.*' => [
                'integer',
                'exists:project_trackings,id',
            ],
        ]);

        $trackingIds = $validated['tracking_ids'];

        $trackings = ProjectTracking::query()
            ->where('project_id', $project->id)
            ->whereIn('id', $trackingIds)
            ->with('attachments')
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        if ($trackings->isEmpty()) {
            return response()->json([
                'message' => 'No project tracking records found.',
            ], 422);
        }

        $fileName = 'project-tracking-' .
            \Illuminate\Support\Str::slug(
                $project->project_name
                    ?? $project->name
                    ?? 'project'
            ) .
            '-' .
            now()->format('Y-m-d-His') .
            '.xlsx';

        return Excel::download(
            new ProjectTrackingExport(
                $trackings,
                $project
            ),
            $fileName
        );
    }

    public function downloadAttachment(Project $project, Attachment $attachment): StreamedResponse {
        /*
        * Make sure this attachment actually belongs
        * to a project tracking record inside this project.
        */
        $belongsToProjectTracking = ProjectTracking::query()
            ->where('project_id', $project->id)
            ->whereHas('attachments', function ($query) use ($attachment) {
                $query->whereKey($attachment->id);
            })
            ->exists();

        abort_unless(
            $belongsToProjectTracking,
            404
        );

        /*
        * Get the stored file path.
        *
        * Adjust these fallbacks if your Attachment model
        * uses a different column.
        */
        $filePath =
            $attachment->file
            ?? $attachment->file_path
            ?? $attachment->path
            ?? null;

        if (!$filePath) {
            abort(404, 'Attachment file was not found.');
        }

        /*
        * If the database contains:
        *
        * storage/foo.pdf
        *
        * convert it to:
        *
        * foo.pdf
        *
        * because the public disk normally starts
        * from storage/app/public.
        */
        $filePath = ltrim(
            str_replace(
                [
                    'storage/',
                    '/storage/',
                ],
                '',
                $filePath
            ),
            '/'
        );

        if (!Storage::disk('public')->exists($filePath)) {
            abort(404, 'Attachment file was not found.');
        }

        $downloadName =
            $attachment->filename
            ?? $attachment->file_name
            ?? $attachment->original_name
            ?? basename($filePath);

        return Storage::disk('public')->download(
            $filePath,
            $downloadName
        );
    }
}
