<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_milestones')) {
            $addMilestoneBacklog = !Schema::hasColumn('project_milestones', 'is_backlog');
            $addMilestoneSystem = !Schema::hasColumn('project_milestones', 'is_system');

            if ($addMilestoneBacklog || $addMilestoneSystem) {
                Schema::table('project_milestones', function (Blueprint $table) use ($addMilestoneBacklog, $addMilestoneSystem) {
                    if ($addMilestoneBacklog) {
                        $table->boolean('is_backlog')->default(false)->after('actual_time_seconds');
                    }

                    if ($addMilestoneSystem) {
                        $table->boolean('is_system')->default(false)->after('is_backlog');
                    }
                });
            }
        }

        if (Schema::hasTable('project_sprints')) {
            $addSprintBacklog = !Schema::hasColumn('project_sprints', 'is_backlog');
            $addSprintSystem = !Schema::hasColumn('project_sprints', 'is_system');

            if ($addSprintBacklog || $addSprintSystem) {
                Schema::table('project_sprints', function (Blueprint $table) use ($addSprintBacklog, $addSprintSystem) {
                    if ($addSprintBacklog) {
                        $table->boolean('is_backlog')->default(false)->after('actual_time_seconds');
                    }

                    if ($addSprintSystem) {
                        $table->boolean('is_system')->default(false)->after('is_backlog');
                    }
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('project_milestones')) {
            $dropMilestoneBacklog = Schema::hasColumn('project_milestones', 'is_backlog');
            $dropMilestoneSystem = Schema::hasColumn('project_milestones', 'is_system');

            if ($dropMilestoneBacklog || $dropMilestoneSystem) {
                Schema::table('project_milestones', function (Blueprint $table) use ($dropMilestoneBacklog, $dropMilestoneSystem) {
                    if ($dropMilestoneBacklog) {
                        $table->dropColumn('is_backlog');
                    }

                    if ($dropMilestoneSystem) {
                        $table->dropColumn('is_system');
                    }
                });
            }
        }

        if (Schema::hasTable('project_sprints')) {
            $dropSprintBacklog = Schema::hasColumn('project_sprints', 'is_backlog');
            $dropSprintSystem = Schema::hasColumn('project_sprints', 'is_system');

            if ($dropSprintBacklog || $dropSprintSystem) {
                Schema::table('project_sprints', function (Blueprint $table) use ($dropSprintBacklog, $dropSprintSystem) {
                    if ($dropSprintBacklog) {
                        $table->dropColumn('is_backlog');
                    }

                    if ($dropSprintSystem) {
                        $table->dropColumn('is_system');
                    }
                });
            }
        }
    }
};
