<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Project;
use App\Models\ProjectComment;
use App\Models\ProjectMember;
use App\Models\ProjectModule;
use App\Models\ProjectNote;
use App\Models\ProjectSprint;
use App\Models\ProjectTechnology;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskNote;
use App\Models\TaskTimeLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;

trait BuildsProjectActivityQueries
{
    protected function getProjectActivitiesQuery(Project $project): Builder
    {
        return $this->applyProjectActivityScope(Activity::query(), $project);
    }

    protected function applyProjectActivityScope(Builder $query, Project $project): Builder
    {
        return $query->where(function (Builder $activityQuery) use ($project) {
            $activityQuery->where(function (Builder $subjectQuery) use ($project) {
                $subjectQuery->where('subject_type', Project::class)
                    ->where('subject_id', $project->id);
            });

            if ($this->activityLogSupportsParentColumns()) {
                $activityQuery->orWhere(function (Builder $parentQuery) use ($project) {
                    $parentQuery->where('parent_type', Project::class)
                        ->where('parent_id', $project->id);
                });
            }

            foreach ($this->getProjectActivitySubjectQueries($project) as $subjectType => $subjectIdsQuery) {
                $activityQuery->orWhere(function (Builder $subjectQuery) use ($subjectType, $subjectIdsQuery) {
                    $subjectQuery->where('subject_type', $subjectType)
                        ->whereIn('subject_id', $subjectIdsQuery);
                });
            }
        });
    }

    protected function getProjectActivitySubjectQueries(Project $project): array
    {
        return [
            ProjectModule::class => ProjectModule::query()
                ->where('project_id', $project->id)
                ->select('id'),
            ProjectSprint::class => ProjectSprint::query()
                ->where('project_id', $project->id)
                ->select('id'),
            ProjectNote::class => ProjectNote::query()
                ->where('project_id', $project->id)
                ->select('id'),
            ProjectMember::class => ProjectMember::query()
                ->where('project_id', $project->id)
                ->select('id'),
        ];
    }

    protected function activityLogSupportsParentColumns(): bool
    {
        static $supportsParentColumns;

        if ($supportsParentColumns !== null) {
            return $supportsParentColumns;
        }

        $schema = Schema::connection(config('activitylog.database_connection'));
        $table = config('activitylog.table_name');

        $supportsParentColumns = $schema->hasColumn($table, 'parent_type')
            && $schema->hasColumn($table, 'parent_id');

        return $supportsParentColumns;
    }
}
