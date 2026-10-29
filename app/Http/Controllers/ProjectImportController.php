<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessProjectImportJob;
use App\Models\ProjectImport;
use App\Services\ProjectHostingImportValidator;
use App\Services\ProjectImportService;
use App\Services\ProjectImportTrackingManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class ProjectImportController extends Controller
{
    /**
     * Create a new controller instance with dependencies.
     */
    public function __construct(
        protected ProjectImportService $importService,
        protected ProjectHostingImportValidator $hostingValidator,
        protected ProjectImportTrackingManager $trackingManager
    ) {}

    /**
     * Display the centralized import page with the selected module interface.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        if (!$this->importService->hasAnyAccessibleModule($user)) {
            abort(403, 'Unauthorized access to the import section.');
        }

        $availableModules = $this->importService->getAccessibleModules($user);
        $requestedKey = $request->query('module');

        if ($requestedKey !== null && $requestedKey !== '') {
            $selectedModule = $this->importService->resolveModule($requestedKey, $user);
            if (!$selectedModule) {
                abort(404, 'The requested import module was not found or is unavailable.');
            }
        } else {
            $defaultKey = $this->importService->getDefaultModuleKey($user);
            $selectedModule = $this->importService->resolveModule($defaultKey, $user);
        }

        $activeImport = null;
        if ($request->filled('import_id')) {
            $importId = (string) $request->query('import_id');

            // 1. Check non-database tracking manager
            $tracked = $this->trackingManager->getTracking($importId);

            if ($tracked && ($tracked['module_key'] ?? null) === ($selectedModule['key'] ?? null)) {
                if ($tracked['user_id'] === $user?->id || !empty($user?->is_super_admin)) {
                    $activeImport = (object) $tracked;
                }
            }

            // 2. Fallback to legacy database record if numeric
            if (!$activeImport && is_numeric($importId)) {
                $candidate = ProjectImport::where('id', $importId)
                    ->where('module_key', $selectedModule['key'] ?? null)
                    ->first();

                if ($candidate && ($candidate->uploaded_by === null || $candidate->uploaded_by === $user?->id || !empty($user?->is_super_admin))) {
                    $activeImport = $candidate;
                }
            }

            if (!$activeImport) {
                session()->flash('warning', 'The requested import results have expired or were not found.');
            }
        }

        return view('projects.import.index', [
            'pageTitle' => 'Import Records',
            'availableModules' => $availableModules,
            'selectedModule' => $selectedModule,
            'selectedModuleKey' => $selectedModule['key'] ?? null,
            'activeImport' => $activeImport,
        ]);
    }

    /**
     * Download the preconfigured Excel template for the selected import module.
     */
    public function downloadTemplate(Request $request, ?string $module = null): BinaryFileResponse
    {
        $user = $request->user();
        $moduleKey = $module ?? $request->query('module');

        if (empty($moduleKey)) {
            abort(404, 'No import module specified.');
        }

        $allModules = $this->importService->getAllModules();
        if (!isset($allModules[$moduleKey]) || empty($allModules[$moduleKey]['enabled'])) {
            abort(404, 'Import module not found or unavailable.');
        }

        $moduleConfig = $allModules[$moduleKey];

        // Enforce module-specific permission
        if (!empty($moduleConfig['permission'])) {
            if (!$user || !$user->can($moduleConfig['permission'])) {
                abort(403, 'Unauthorized to download template for this module.');
            }
        }

        // Resolve template file path securely
        $filePath = $this->importService->getTemplateAbsolutePath($moduleKey, $user);
        if (!$filePath || !file_exists($filePath)) {
            abort(404, 'Import template file was not found on the server.');
        }

        $downloadFilename = $this->importService->getTemplateDownloadFilename($moduleKey, $user)
            ?: basename($filePath);

        return response()->download($filePath, $downloadFilename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Store uploaded file in private storage and dispatch the asynchronous queued import job.
     * Uses non-database temporary tracking (no ProjectImport database records created).
     */
    public function startImport(Request $request, ?string $module = null): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $moduleKey = $module ?? $request->query('module');

        if (empty($moduleKey)) {
            abort(404, 'No import module specified.');
        }

        $allModules = $this->importService->getAllModules();
        if (!isset($allModules[$moduleKey]) || empty($allModules[$moduleKey]['enabled'])) {
            abort(404, 'Import module not found or unavailable.');
        }

        $moduleConfig = $allModules[$moduleKey];

        // Enforce module-specific permission
        if (!empty($moduleConfig['permission'])) {
            if (!$user || !$user->can($moduleConfig['permission'])) {
                abort(403, 'Unauthorized to import records for this module.');
            }
        }

        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx',
                'max:20480',
            ],
        ], [
            'file.required' => 'Please select an Excel (.xlsx) file to import.',
            'file.mimes' => 'The uploaded file must be a valid .xlsx Excel workbook.',
            'file.max' => 'The file size cannot exceed 20 MB.',
        ]);

        $uploadedFile = $request->file('file');
        $importId = (string) Str::uuid();

        // Store source workbook in private storage
        $storedDir = $this->trackingManager->getImportDirectory($importId);
        $storedPath = $uploadedFile->storeAs($storedDir, 'source.xlsx', ProjectImportTrackingManager::DISK);

        if ($storedPath === false || !Storage::disk(ProjectImportTrackingManager::DISK)->exists($storedPath)) {
            throw new RuntimeException('The uploaded import file could not be safely stored.');
        }

        // Initialize temporary non-database tracking state
        $this->trackingManager->initTracking(
            importId: $importId,
            moduleKey: $moduleKey,
            fileName: $uploadedFile->getClientOriginalName(),
            filePath: $storedPath,
            userId: (int) $user->id
        );

        // Dispatch background queue job without creating database rows
        ProcessProjectImportJob::dispatch($importId)->afterCommit();

        $message = "The {$moduleConfig['label']} import has been queued and is processing in the background.";

        if ($request->expectsJson()) {
            return response()->json([
                'status' => true,
                'message' => $message,
                'data' => [
                    'id' => $importId,
                    'module_key' => $moduleKey,
                    'status' => ProjectImportTrackingManager::STATUS_PENDING,
                    'file_name' => $uploadedFile->getClientOriginalName(),
                    'status_url' => route('projects.import.status', ['module' => $moduleKey, 'import' => $importId]),
                ],
            ], Response::HTTP_ACCEPTED);
        }

        return redirect()->route('projects.import.index', [
            'module' => $moduleKey,
            'import_id' => $importId,
        ])->with('success', $message);
    }

    /**
     * Check the current status and row-level results of a queued import.
     */
    public function importStatus(Request $request, string $module, string $import): JsonResponse
    {
        $user = $request->user();

        $allModules = $this->importService->getAllModules();
        if (!isset($allModules[$module]) || empty($allModules[$module]['enabled'])) {
            return response()->json([
                'status' => false,
                'message' => 'Import module not found or unavailable.',
            ], Response::HTTP_NOT_FOUND);
        }

        $moduleConfig = $allModules[$module];
        if (!empty($moduleConfig['permission'])) {
            if (!$user || !$user->can($moduleConfig['permission'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized to view import status for this module.',
                ], Response::HTTP_FORBIDDEN);
            }
        }

        // 1. Look up non-database tracking
        $tracking = $this->trackingManager->getTracking($import);

        if ($tracking) {
            if ($tracking['module_key'] !== $module) {
                return response()->json([
                    'status' => false,
                    'message' => 'Import record not found for this module.',
                ], Response::HTTP_NOT_FOUND);
            }

            if ($tracking['user_id'] !== $user?->id && empty($user?->is_super_admin)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access to this import record.',
                ], Response::HTTP_FORBIDDEN);
            }

            $isFinished = in_array($tracking['status'], [
                ProjectImportTrackingManager::STATUS_COMPLETED,
                ProjectImportTrackingManager::STATUS_COMPLETED_WITH_ERRORS,
                ProjectImportTrackingManager::STATUS_FAILED,
                ProjectImportTrackingManager::STATUS_INTERRUPTED,
            ], true);

            return response()->json([
                'status' => true,
                'data' => [
                    'id' => $tracking['id'],
                    'module_key' => $tracking['module_key'],
                    'status' => $tracking['status'],
                    'file_name' => $tracking['file_name'],
                    'total_rows' => $tracking['total_rows'],
                    'processed_rows' => $tracking['processed_rows'],
                    'successful_rows' => $tracking['successful_rows'],
                    'failed_rows' => $tracking['failed_rows'],
                    'error_message' => $tracking['error_message'],
                    'row_results' => $tracking['row_results'],
                    'is_finished' => $isFinished,
                    'download_failed_url' => route('projects.import.download.failed', ['module' => $module, 'import' => $tracking['id']]),
                    'download_results_url' => route('projects.import.download.results', ['module' => $module, 'import' => $tracking['id']]),
                    'created_at' => $tracking['created_at'],
                    'completed_at' => $tracking['completed_at'],
                ],
            ], Response::HTTP_OK);
        }

        // 2. Legacy database fallback if numeric
        if (is_numeric($import)) {
            $legacyRecord = ProjectImport::where('id', $import)
                ->where('module_key', $module)
                ->first();

            if ($legacyRecord) {
                if ($legacyRecord->uploaded_by !== null && $legacyRecord->uploaded_by !== $user?->id && empty($user?->is_super_admin)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Unauthorized access to this import record.',
                    ], Response::HTTP_FORBIDDEN);
                }

                return response()->json([
                    'status' => true,
                    'data' => [
                        'id' => (string) $legacyRecord->id,
                        'module_key' => $legacyRecord->module_key,
                        'status' => $legacyRecord->status,
                        'file_name' => $legacyRecord->file_name,
                        'total_rows' => $legacyRecord->total_rows,
                        'processed_rows' => $legacyRecord->processed_rows,
                        'successful_rows' => $legacyRecord->successful_rows,
                        'failed_rows' => $legacyRecord->failed_rows,
                        'error_message' => $legacyRecord->error_message,
                        'row_results' => $legacyRecord->row_results,
                        'is_finished' => in_array($legacyRecord->status, [
                            ProjectImport::STATUS_COMPLETED,
                            ProjectImport::STATUS_COMPLETED_WITH_ERRORS,
                            ProjectImport::STATUS_FAILED,
                        ], true),
                        'download_failed_url' => null,
                        'download_results_url' => null,
                        'created_at' => $legacyRecord->created_at?->toIso8601String(),
                        'completed_at' => $legacyRecord->completed_at?->toIso8601String(),
                    ],
                ], Response::HTTP_OK);
            }
        }

        return response()->json([
            'status' => false,
            'expired' => true,
            'message' => 'Import results have expired or were not found.',
        ], Response::HTTP_NOT_FOUND);
    }

    /**
     * Download the "Failed Rows" Excel workbook containing only failed rows.
     */
    public function downloadFailedRows(Request $request, string $module, string $import): BinaryFileResponse
    {
        $user = $request->user();

        $allModules = $this->importService->getAllModules();
        if (!isset($allModules[$module]) || empty($allModules[$module]['enabled'])) {
            abort(404, 'Import module not found or unavailable.');
        }

        $moduleConfig = $allModules[$module];
        if (!empty($moduleConfig['permission'])) {
            if (!$user || !$user->can($moduleConfig['permission'])) {
                abort(403, 'Unauthorized to download import results for this module.');
            }
        }

        $tracking = $this->trackingManager->getTracking($import);
        if (!$tracking || ($tracking['module_key'] ?? '') !== $module) {
            abort(404, 'Import results have expired or were not found.');
        }

        if ($tracking['user_id'] !== $user?->id && empty($user?->is_super_admin)) {
            abort(403, 'Unauthorized access to this import report.');
        }

        if (($tracking['failed_rows'] ?? 0) === 0) {
            abort(400, 'There are no failed rows to download for this import.');
        }

        $filePath = $this->trackingManager->generateFailedRowsFile($import);
        if (!$filePath || !file_exists($filePath)) {
            abort(500, 'Unable to generate failed rows spreadsheet.');
        }

        $downloadFilename = "failed-{$module}-records-{$import}.xlsx";

        return response()->download($filePath, $downloadFilename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Download the "All Results" Excel workbook containing all processed rows.
     */
    public function downloadAllResults(Request $request, string $module, string $import): BinaryFileResponse
    {
        $user = $request->user();

        $allModules = $this->importService->getAllModules();
        if (!isset($allModules[$module]) || empty($allModules[$module]['enabled'])) {
            abort(404, 'Import module not found or unavailable.');
        }

        $moduleConfig = $allModules[$module];
        if (!empty($moduleConfig['permission'])) {
            if (!$user || !$user->can($moduleConfig['permission'])) {
                abort(403, 'Unauthorized to download import results for this module.');
            }
        }

        $tracking = $this->trackingManager->getTracking($import);
        if (!$tracking || ($tracking['module_key'] ?? '') !== $module) {
            abort(404, 'Import results have expired or were not found.');
        }

        if ($tracking['user_id'] !== $user?->id && empty($user?->is_super_admin)) {
            abort(403, 'Unauthorized access to this import report.');
        }

        $filePath = $this->trackingManager->generateAllResultsFile($import);
        if (!$filePath || !file_exists($filePath)) {
            abort(500, 'Unable to generate all results spreadsheet.');
        }

        $downloadFilename = "all-{$module}-results-{$import}.xlsx";

        return response()->download($filePath, $downloadFilename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Validate an uploaded Excel workbook for the specified module (read-only).
     * Kept for backwards compatibility.
     */
    public function validateUpload(Request $request, ?string $module = null): View
    {
        $user = $request->user();
        $moduleKey = $module ?? $request->query('module');

        if (empty($moduleKey)) {
            abort(404, 'No import module specified.');
        }

        $allModules = $this->importService->getAllModules();
        if (!isset($allModules[$moduleKey]) || empty($allModules[$moduleKey]['enabled'])) {
            abort(404, 'Import module not found or unavailable.');
        }

        $moduleConfig = $allModules[$moduleKey];

        if (!empty($moduleConfig['permission'])) {
            if (!$user || !$user->can($moduleConfig['permission'])) {
                abort(403, 'Unauthorized to upload and validate for this module.');
            }
        }

        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx',
                'max:20480',
            ],
        ], [
            'file.required' => 'Please select an Excel (.xlsx) file to validate.',
            'file.mimes' => 'The uploaded file must be a valid .xlsx Excel workbook.',
            'file.max' => 'The file size cannot exceed 20 MB.',
        ]);

        $selectedModule = $this->importService->resolveModule($moduleKey, $user);
        $availableModules = $this->importService->getAccessibleModules($user);

        $validationResult = $this->hostingValidator->validate($request->file('file'), $user);

        return view('projects.import.index', [
            'pageTitle' => 'Import Records',
            'availableModules' => $availableModules,
            'selectedModule' => $selectedModule,
            'selectedModuleKey' => $moduleKey,
            'validationResult' => $validationResult,
        ]);
    }
}
