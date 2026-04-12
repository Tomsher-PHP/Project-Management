<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_modules')) {
            $addModuleBacklog = !Schema::hasColumn('project_modules', 'is_backlog');
            $addModuleSystem = !Schema::hasColumn('project_modules', 'is_system');

            if ($addModuleBacklog || $addModuleSystem) {
                Schema::table('project_modules', function (Blueprint $table) use ($addModuleBacklog, $addModuleSystem) {
                    if ($addModuleBacklog) {
                        $table->boolean('is_backlog')->default(false)->after('actual_time_seconds');
                    }

                    if ($addModuleSystem) {
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
        if (Schema::hasTable('project_modules')) {
            $dropModuleBacklog = Schema::hasColumn('project_modules', 'is_backlog');
            $dropModuleSystem = Schema::hasColumn('project_modules', 'is_system');

            if ($dropModuleBacklog || $dropModuleSystem) {
                Schema::table('project_modules', function (Blueprint $table) use ($dropModuleBacklog, $dropModuleSystem) {
                    if ($dropModuleBacklog) {
                        $table->dropColumn('is_backlog');
                    }

                    if ($dropModuleSystem) {
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
