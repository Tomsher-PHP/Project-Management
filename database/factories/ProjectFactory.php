<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectStage;
use App\Models\ProjectStatus;
use App\Models\ProjectStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    protected static ?int $projectCodeCounter = null;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-3 months', '+1 month');
        $endDate = (clone $startDate)->modify('+' . fake()->numberBetween(14, 90) . ' days');
        $statusId = ProjectStatus::query()->inRandomOrder()->value('id')
            ?? ProjectStatus::query()->create([
                'name' => 'Planned',
                'code' => 'planned',
                'color' => '#6c757d',
                'type' => 'open',
                'sort_order' => 1,
                'is_default' => true,
                'is_completed' => false,
                'is_system' => true,
                'is_active' => true,
            ])->id;

        return [
            'project_code' => $this->nextProjectCode(),
            'name' => fake()->unique()->catchPhrase(),
            'customer_id' => Customer::query()->inRandomOrder()->value('id'),
            'project_flow' => fake()->randomElement(['agile', 'linear']),
            'priority' => fake()->randomElement(['urgent', 'high', 'medium', 'low']),
            'status_id' => $statusId,
            'project_stage_id' => ProjectStage::query()->inRandomOrder()->value('id'),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'customer_end_date' => fake()->boolean(60)
                ? (clone $endDate)->modify('+' . fake()->numberBetween(0, 14) . ' days')
                : null,
            'estimated_time_seconds' => fake()->numberBetween(8, 160) * 3600,
            'domain' => fake()->optional()->domainName(),
            'project_category_id' => ProjectCategory::query()->inRandomOrder()->value('id'),
            'default_billable' => fake()->boolean(75),
            'is_active' => true,
            'sales_person_id' => User::query()->inRandomOrder()->value('id'),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Project $project) {
            if (! $project->status_id) {
                return;
            }

            ProjectStatusHistory::query()->firstOrCreate([
                'project_id' => $project->id,
                'status_id' => $project->status_id,
            ], [
                'remarks' => 'Initial dummy project status.',
            ]);
        });
    }

    private function nextProjectCode(): string
    {
        if (static::$projectCodeCounter === null) {
            $lastProjectCode = Project::withTrashed()->orderByDesc('id')->value('project_code') ?? 'PRJ00000';
            static::$projectCodeCounter = (int) substr($lastProjectCode, 3);
        }

        static::$projectCodeCounter++;

        return 'PRJ' . str_pad((string) static::$projectCodeCounter, 5, '0', STR_PAD_LEFT);
    }
}
