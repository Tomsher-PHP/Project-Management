<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ProjectImportService;
use App\Services\ProjectImportTrackingManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProjectsImportPartOneTest extends TestCase
{
    use DatabaseTransactions;

    protected function createUserWithPermissions(array $permissions): User
    {
        $user = User::factory()->create(['is_super_admin' => false]);

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    public function test_projects_module_is_registered_first_in_config(): void
    {
        $modules = config('project_constants.project_import_modules');
        $keys = array_keys($modules);

        $this->assertNotEmpty($keys);
        $this->assertSame('projects', $keys[0]);
        $this->assertSame('Projects', $modules['projects']['label']);
        $this->assertSame(['project.create', 'project.edit'], $modules['projects']['permission']);
        $this->assertSame('projects.import.modules.projects', $modules['projects']['view_partial']);
        $this->assertSame('assets/templates/projects_import/bulk-projects-import-template-final.xlsx', $modules['projects']['template_path']);
        $this->assertSame('bulk-projects-import-template-final.xlsx', $modules['projects']['template_filename']);
        $this->assertSame(\App\Services\ProjectBulkImportService::class, $modules['projects']['handler_class']);
        $this->assertTrue($modules['projects']['enabled']);
    }

    public function test_projects_is_default_module_for_user_with_project_create_or_edit_and_explicit_hosting_selection_works(): void
    {
        $createUser = $this->createUserWithPermissions(['project.create', 'project_hosting.create']);
        $editUser = $this->createUserWithPermissions(['project.edit']);

        // Default module for project.create + project_hosting.create is Projects, listed first
        $response = $this->actingAs($createUser)->get(route('projects.import.index'));
        $response->assertOk();
        $response->assertViewHas('selectedModuleKey', 'projects');
        $response->assertSee('Fill Project Details');
        $response->assertSee('Column Instructions &amp; Format Examples', false);
        $response->assertSee('19 Columns');
        $response->assertSee('Project Stage Code');
        $response->assertSee('Sales Person Email');
        $response->assertSee('Default Billable');

        $viewModules = array_keys($response->viewData('availableModules'));
        $this->assertSame(['projects', 'project_hosting'], $viewModules);

        // Explicit module query switches to project_hosting
        $hostingResponse = $this->actingAs($createUser)->get(route('projects.import.index', ['module' => 'project_hosting']));
        $hostingResponse->assertOk();
        $hostingResponse->assertViewHas('selectedModuleKey', 'project_hosting');
        $hostingResponse->assertSee('Project Hosting Records');

        // User with only project.edit can also access Projects Import by default
        $editResponse = $this->actingAs($editUser)->get(route('projects.import.index'));
        $editResponse->assertOk();
        $editResponse->assertViewHas('selectedModuleKey', 'projects');
    }

    public function test_user_with_only_hosting_permission_defaults_to_hosting_and_cannot_access_projects_module(): void
    {
        $hostingOnlyUser = $this->createUserWithPermissions(['project_hosting.create']);

        $response = $this->actingAs($hostingOnlyUser)->get(route('projects.import.index'));
        $response->assertOk();
        $response->assertViewHas('selectedModuleKey', 'project_hosting');
        $this->assertSame(['project_hosting'], array_keys($response->viewData('availableModules')));

        $this->actingAs($hostingOnlyUser)
            ->get(route('projects.import.index', ['module' => 'projects']))
            ->assertNotFound();

        $this->actingAs($hostingOnlyUser)
            ->get(route('projects.import.template', 'projects'))
            ->assertForbidden();
    }

    public function test_user_without_import_permissions_is_forbidden_and_does_not_see_imports_button(): void
    {
        $viewOnlyUser = $this->createUserWithPermissions(['project.view']);

        $this->actingAs($viewOnlyUser)
            ->get(route('projects.import.index'))
            ->assertForbidden();

        $projectsIndexResponse = $this->actingAs($viewOnlyUser)->get(route('projects.index'));
        $projectsIndexResponse->assertOk();
        $projectsIndexResponse->assertDontSee(route('projects.import.index'), false);
    }

    public function test_imports_button_visibility_on_projects_index_respects_module_permissions_without_changing_export(): void
    {
        $createUser = $this->createUserWithPermissions(['project.view', 'project.create']);
        $response = $this->actingAs($createUser)->get(route('projects.index'));
        $response->assertOk();
        $response->assertSee(route('projects.import.index'), false);
        $response->assertDontSee(route('projects.export'), false);

        $exportUser = $this->createUserWithPermissions(['project.view', 'project.export']);
        $exportResponse = $this->actingAs($exportUser)->get(route('projects.index'));
        $exportResponse->assertOk();
        $exportResponse->assertDontSee(route('projects.import.index'), false);
        $exportResponse->assertSee(route('projects.export'), false);
    }

    public function test_projects_template_downloads_exact_existing_file(): void
    {
        $user = $this->createUserWithPermissions(['project.edit']);

        $response = $this->actingAs($user)->get(route('projects.import.template', 'projects'));

        $response->assertOk();
        $response->assertDownload('bulk-projects-import-template-final.xlsx');

        $expectedPath = public_path('assets/templates/projects_import/bulk-projects-import-template-final.xlsx');
        $this->assertFileExists($expectedPath);
        $this->assertSame(
            filesize($expectedPath),
            filesize($response->getFile()->getPathname())
        );
    }

    public function test_projects_status_card_renders_summary_filters_and_results_table(): void
    {
        $user = $this->createUserWithPermissions(['project.create']);
        $trackingManager = app(ProjectImportTrackingManager::class);
        $importId = (string) Str::uuid();

        $trackingManager->initTracking($importId, 'projects', 'projects.xlsx', 'project-imports-data/' . $importId . '/source.xlsx', $user->id);
        $trackingManager->updateTracking($importId, [
            'status' => ProjectImportTrackingManager::STATUS_COMPLETED_WITH_ERRORS,
            'total_rows' => 4,
            'processed_rows' => 4,
            'successful_rows' => 2,
            'created_rows' => 1,
            'updated_rows' => 1,
            'unchanged_rows' => 1,
            'failed_rows' => 1,
            'row_results' => [
                ['excel_row' => 2, 'project_code' => 'PRJ00101', 'project_name' => 'Alpha Site', 'status' => 'created', 'message' => 'Project created.'],
                ['excel_row' => 3, 'project_code' => 'PRJ00010', 'project_name' => 'Beta App', 'status' => 'updated', 'message' => 'Project updated.'],
                ['excel_row' => 4, 'project_code' => 'PRJ00011', 'project_name' => 'Gamma Portal', 'status' => 'unchanged', 'message' => 'No changes detected.'],
                ['excel_row' => 5, 'project_code' => 'PRJ99999', 'project_name' => 'Invalid Row', 'status' => 'failed', 'message' => 'Project Code not found.'],
            ],
        ]);

        try {
            $response = $this->actingAs($user)->get(route('projects.import.index', [
                'module' => 'projects',
                'import_id' => $importId,
            ]));

            $response->assertOk();
            $response->assertSee('Total:');
            $response->assertSee('Created:');
            $response->assertSee('Updated:');
            $response->assertSee('Unchanged:');
            $response->assertSee('Failed:');
            $response->assertSee('Excel Row');
            $response->assertSee('Project Name');
            $response->assertSee('Alpha Site');
            $response->assertSee('Beta App');
            $response->assertSee('Gamma Portal');
            $response->assertSee('data-filter="created"', false);
            $response->assertSee('data-filter="updated"', false);
            $response->assertSee('data-filter="unchanged"', false);
            $response->assertSee('data-filter="failed"', false);
        } finally {
            $trackingManager->deleteTrackingDirectory($importId);
        }
    }
}

