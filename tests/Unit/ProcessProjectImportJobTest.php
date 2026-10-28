<?php

namespace Tests\Unit;

use App\Jobs\ProcessProjectImportJob;
use App\Models\ProjectImport;
use App\Services\Contracts\ProjectImportHandlerInterface;
use App\Services\ProjectHostingImportService;
use App\Services\ProjectImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class ProcessProjectImportJobTest extends TestCase
{
    public function test_job_implements_should_queue(): void
    {
        $this->assertTrue(is_subclass_of(ProcessProjectImportJob::class, ShouldQueue::class));
    }

    public function test_job_queue_properties_and_middleware(): void
    {
        $job = new ProcessProjectImportJob(999);

        $this->assertEquals(999, $job->importId);
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(600, $job->timeout);
        $this->assertEquals([30, 60, 120], $job->backoff());

        $middleware = $job->middleware();
        $this->assertCount(1, $middleware);
        $this->assertInstanceOf(WithoutOverlapping::class, $middleware[0]);
    }

    public function test_module_resolver_resolves_project_hosting_handler(): void
    {
        /** @var ProjectImportService $service */
        $service = app(ProjectImportService::class);

        $handler = $service->resolveImportHandler('project_hosting');

        $this->assertNotNull($handler);
        $this->assertInstanceOf(ProjectImportHandlerInterface::class, $handler);
        $this->assertInstanceOf(ProjectHostingImportService::class, $handler);
    }

    public function test_module_resolver_rejects_unknown_or_disabled_modules(): void
    {
        /** @var ProjectImportService $service */
        $service = app(ProjectImportService::class);

        $this->expectException(\InvalidArgumentException::class);
        $service->resolveImportHandler('unknown_module_xyz');
    }

    public function test_job_handles_failed_method_cleanly(): void
    {
        $importServiceMock = Mockery::mock(ProjectImportService::class);
        $importServiceMock->shouldReceive('markImportFailed')
            ->once()
            ->with(999, 'Fatal processing failure');
        $importServiceMock->shouldReceive('deleteImportFile')
            ->once()
            ->with(999);

        $this->app->instance(ProjectImportService::class, $importServiceMock);

        $job = new ProcessProjectImportJob(999);
        $exception = new \RuntimeException('Fatal processing failure');

        $job->failed($exception);
        $this->assertTrue(true);
    }
}
