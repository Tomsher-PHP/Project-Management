<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KanbanNotesModalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Permission::findOrCreate('task.view', 'web');
    }

    public function test_notes_modal_endpoint_returns_latest_five_combined_items()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('task.view');

        $project = Project::factory()->create();
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'current_assignee_id' => $user->id,
        ]);

        // Create 4 notes
        for ($i = 1; $i <= 4; $i++) {
            TaskNote::create([
                'task_id' => $task->id,
                'description' => "Note content {$i}",
                'added_by' => $user->id,
                'created_at' => now()->addMinutes($i),
            ]);
        }

        $note = $task->taskNotes()->first();

        // Create 4 attachments
        for ($j = 1; $j <= 4; $j++) {
            Attachment::create([
                'link_id' => $note->id,
                'link_type' => TaskNote::class,
                'file_name' => "file_{$j}.pdf",
                'original_name' => "document_{$j}.pdf",
                'file_size' => 1024,
                'added_by' => $user->id,
                'created_at' => now()->addMinutes(4 + $j),
            ]);
        }

        $response = $this->actingAs($user)->getJson(route('tasks.notes.modal', $task));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $json = $response->json();
        $this->assertStringContainsString('Notes & Files', $json['html']);
        $this->assertStringContainsString('tab=notes', $json['viewAllUrl']);
    }

    public function test_task_notes_and_attachments_count_calculation()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $task = Task::factory()->create([
            'project_id' => $project->id,
        ]);

        $note1 = TaskNote::create([
            'task_id' => $task->id,
            'description' => 'Note 1',
            'added_by' => $user->id,
        ]);

        $note2 = TaskNote::create([
            'task_id' => $task->id,
            'description' => 'Note 2',
            'added_by' => $user->id,
        ]);

        Attachment::create([
            'link_id' => $note1->id,
            'link_type' => TaskNote::class,
            'file_name' => 'file1.pdf',
            'original_name' => 'file1.pdf',
            'file_size' => 2048,
            'added_by' => $user->id,
        ]);

        Attachment::create([
            'link_id' => $note1->id,
            'link_type' => TaskNote::class,
            'file_name' => 'file2.png',
            'original_name' => 'file2.png',
            'file_size' => 4096,
            'added_by' => $user->id,
        ]);

        $taskWithCounts = Task::where('id', $task->id)
            ->withCount([
                'taskNotes as task_notes_count' => function ($query) {
                    $query->whereNotNull('description')->where('description', '!=', '');
                },
                'taskNoteAttachments',
            ])
            ->first();

        $this->assertEquals(2, $taskWithCounts->task_notes_count);
        $this->assertEquals(2, $taskWithCounts->task_note_attachments_count);
        $this->assertEquals(4, $taskWithCounts->task_notes_count + $taskWithCounts->task_note_attachments_count);
    }
}
