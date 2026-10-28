<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessProjectImportJob;
use App\Models\ProjectImport;
use App\Services\ProjectHostingImportValidator;
use App\Services\ProjectImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        protected ProjectHostingImportValidator $hostingValidator
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
            $candidate = ProjectImport::where('id', $request->query('import_id'))
                ->where('module_key', $selectedModule['key'] ?? null)
                ->first();

            if ($candidate && ($candidate->uploaded_by === null || $candidate->uploaded_by === $user?->id || !empty($user?->is_super_admin))) {
                $activeImport = $candidate;
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

        // Enforce the module-specific permission
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
     * Validate an uploaded Excel workbook for the specified module (read-only).
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

        // Enforce the module-specific permission
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
                'max:10240',
            ],
        ], [
            'file.required' => 'Please select an Excel (.xlsx) file to validate.',
            'file.mimes' => 'The uploaded file must be a valid .xlsx Excel workbook.',
            'file.max' => 'The file size cannot exceed 10 MB.',
        ]);

        $selectedModule = $this->importService->resolveModule($moduleKey, $user);
        $availableModules = $this->importService->getAccessibleModules($user);

        // Run read-only row-by-row validation
        $validationResult = $this->hostingValidator->validate($request->file('file'), $user);

        return view('projects.import.index', [
            'pageTitle' => 'Import Records',
            'availableModules' => $availableModules,
            'selectedModule' => $selectedModule,
            'selectedModuleKey' => $moduleKey,
            'validationResult' => $validationResult,
        ]);
    }

    /**
     * Store uploaded file securely and dispatch the common asynchronous queued import job.
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
                'max:10240',
            ],
        ], [
            'file.required' => 'Please select an Excel (.xlsx) file to import.',
            'file.mimes' => 'The uploaded file must be a valid .xlsx Excel workbook.',
            'file.max' => 'The file size cannot exceed 10 MB.',
        ]);

        $uploadedFile = $request->file('file');
        $filePath = $uploadedFile->store('project-imports', 'local');

        if ($filePath === false || !Storage::disk('local')->exists($filePath)) {
            throw new RuntimeException('The uploaded import file could not be safely stored.');
        }

        $import = $this->importService->createImportRecord(
            moduleKey: $moduleKey,
            fileName: $uploadedFile->getClientOriginalName(),
            filePath: $filePath,
            userId: $user?->id
        );

        ProcessProjectImportJob::dispatch($import->id)->afterCommit();

        $message = "The {$moduleConfig['label']} import has been queued and will be processed in the background.";

        if ($request->expectsJson()) {
            return response()->json([
                'status' => true,
                'message' => $message,
                'data' => [
                    'id' => $import->id,
                    'module_key' => $import->module_key,
                    'status' => $import->status,
                    'file_name' => $import->file_name,
                    'status_url' => route('projects.import.status', ['module' => $moduleKey, 'import' => $import->id]),
                ],
            ], Response::HTTP_ACCEPTED);
        }

        return redirect()->route('projects.import.index', [
            'module' => $moduleKey,
            'import_id' => $import->id,
        ])->with('success', $message);
    }

    /**
     * Check the current status and row-level results of a queued import.
     */
    public function importStatus(Request $request, string $module, int|string $import): JsonResponse
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

        $importRecord = ProjectImport::where('id', $import)
            ->where('module_key', $module)
            ->first();

        if (!$importRecord) {
            return response()->json([
                'status' => false,
                'message' => 'Import record not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        if ($importRecord->uploaded_by !== null && $importRecord->uploaded_by !== $user?->id && empty($user?->is_super_admin)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access to this import record.',
            ], Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $importRecord->id,
                'module_key' => $importRecord->module_key,
                'status' => $importRecord->status,
                'file_name' => $importRecord->file_name,
                'total_rows' => $importRecord->total_rows,
                'processed_rows' => $importRecord->processed_rows,
                'successful_rows' => $importRecord->successful_rows,
                'failed_rows' => $importRecord->failed_rows,
                'error_message' => $importRecord->error_message,
                'row_results' => $importRecord->row_results,
                'is_finished' => in_array($importRecord->status, [
                    ProjectImport::STATUS_COMPLETED,
                    ProjectImport::STATUS_COMPLETED_WITH_ERRORS,
                    ProjectImport::STATUS_FAILED,
                ], true),
                'created_at' => $importRecord->created_at?->toIso8601String(),
                'updated_at' => $importRecord->updated_at?->toIso8601String(),
            ],
        ], Response::HTTP_OK);
    }
}
