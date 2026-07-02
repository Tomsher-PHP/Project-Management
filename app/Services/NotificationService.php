<?php

namespace App\Services;

use App\Models\BreakWorkRequest;
use App\Models\HandoffRequest;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectSprint;
use App\Models\Shift;
use App\Models\Task;
use App\Models\TaskTimeLogChangeRequest;
use App\Models\Team;
use App\Models\User;
use App\Models\UserNotificationSetting;
use App\Notifications\TaskAssignedNotification;
use App\Models\TaskExtendTimeRequest;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class NotificationService
{
    private static array $handledTaskAssignmentNotifications = [];

    private const MILESTONE_TIMELINE_FIELDS = [
        'owner_id' => 'Owner',
        'estimated_time_seconds' => 'Estimated Time',
        'start_date' => 'Start Date',
        'end_date' => 'End Date',
    ];

    private const SPRINT_TIMELINE_FIELDS = [
        'estimated_time_seconds' => 'Estimated Time',
        'start_date' => 'Start Date',
        'end_date' => 'End Date',
    ];

    private function getNotificationChannels(int $userId, ?string $notificationType): array
    {
        if (blank($notificationType)) {
            return [];
        }

        $setting = UserNotificationSetting::query()
            ->where('user_id', $userId)
            ->where('action', $notificationType)
            ->first();

        if (! $setting) {
            return [];
        }

        $channels = [];

        if ($setting->mail) {
            $channels[] = 'mail';
        }

        if ($setting->in_app) {
            $channels[] = 'database';
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    // Single user
    public function send(int $userId, string $title, string $message, ?string $url = null, ?string $notificationType = null, ?int $actorUserId = null, ?int $projectId = null, array $emailDetails = [], array $emailSubjectContext = []): void
    {
        $user = User::find($userId);

        if (!$user) {
            return;
        }

        $channels = $this->getNotificationChannels($user->id, $notificationType);

        if ($channels === []) {
            return;
        }

        info('send', $emailSubjectContext);
        $user->notify(new TaskAssignedNotification(
            $title,
            $message,
            $url,
            $channels,
            $actorUserId,
            $projectId,
            $this->buildEmailDetails($emailDetails, $actorUserId, $projectId),
            $emailSubjectContext
        ));
    }

    // Multiple users
    public function sendToMany(array $userIds, string $title, string $message, ?string $url = null, ?string $notificationType = null, ?int $actorUserId = null, ?int $projectId = null, array $emailDetails = [], array $emailSubjectContext = []): void
    {
        info('sendToMany', $emailSubjectContext);
        User::whereIn('id', $userIds)
            ->chunk(50, function ($users) use ($title, $message, $url, $notificationType, $actorUserId, $projectId, $emailDetails, $emailSubjectContext) {
                foreach ($users as $user) {
                    $channels = $this->getNotificationChannels($user->id, $notificationType);

                    if ($channels === []) {
                        continue;
                    }

                    $user->notify(new TaskAssignedNotification(
                        $title,
                        $message,
                        $url,
                        $channels,
                        $actorUserId,
                        $projectId,
                        $this->buildEmailDetails($emailDetails, $actorUserId, $projectId),
                        $emailSubjectContext
                    ));
                }
            });
    }

    // Team Member Added: Notify users when they are added to a team
    public function notifyTeamMemberAdded(int|array $userIds, Team $team, ?string $roleName = null): void
    {
        $userIds = $this->normalizeUserIds($userIds);

        if ($userIds === []) {
            return;
        }

        $message = $roleName
            ? "You have been added to team '{$team->name}' as '{$roleName}'."
            : "You have been added to team '{$team->name}'.";

        $this->dispatchTeamNotification(
            $userIds,
            'Team Assigned',
            $message
        );
    }

    // Team Member Removed: Notify users when they are removed from a team
    public function notifyTeamMemberRemoved(int|array $userIds, Team $team, ?string $roleName = null): void
    {
        $userIds = $this->normalizeUserIds($userIds);

        if ($userIds === []) {
            return;
        }

        $message = $roleName
            ? "You have been removed from team '{$team->name}' where your role was '{$roleName}'."
            : "You have been removed from team '{$team->name}'.";

        $this->dispatchTeamNotification(
            $userIds,
            'Team Removed',
            $message
        );
    }

    // Project Member Added: Notify users when they are added to a project
    public function notifyProjectMemberAdded(int $userId, Project $project, ?string $roleName = null): void
    {
        $this->dispatchProjectAssignmentNotification(
            'Project Assigned',
            $userId,
            $project,
            fn(User $recipient, User $targetUser, ?User $actor) => (int) $recipient->id === (int) $targetUser->id
                ? "{$this->actorName($actor)} assigned you to project '{$project->name}'."
                : "{$this->actorName($actor)} added {$targetUser->name} to project '{$project->name}'."
        );
    }

    // Project Member Removed: Notify users when they are removed from a project
    public function notifyProjectMemberRemoved(int $userId, Project $project, ?string $roleName = null): void
    {
        $this->dispatchProjectAssignmentNotification(
            'Project Unassigned',
            $userId,
            $project,
            fn(User $recipient, User $targetUser, ?User $actor) => (int) $recipient->id === (int) $targetUser->id
                ? "{$this->actorName($actor)} removed you from project '{$project->name}'."
                : "{$this->actorName($actor)} removed {$targetUser->name} from project '{$project->name}'."
        );
    }

    // Project Member Status Changed: Notify users when their status in a project changes
    public function notifyProjectMemberStatusChanged(int $userId, Project $project, bool $isEnabled, ?string $roleName = null): void
    {
        $oldStatus = $isEnabled ? 'Inactive' : 'Active';
        $newStatus = $isEnabled ? 'Active' : 'Inactive';

        $this->dispatchProjectAssignmentNotification(
            'Project Member Status Updated',
            $userId,
            $project,
            fn(User $recipient, User $targetUser, ?User $actor) => (int) $recipient->id === (int) $targetUser->id
                ? "{$this->actorName($actor)} changed your status in project '{$project->name}' from {$oldStatus} to {$newStatus}."
                : "{$this->actorName($actor)} changed {$targetUser->name}'s status in project '{$project->name}' from {$oldStatus} to {$newStatus}."
        );
    }

    // Project Member Role Updated: Notify users when their role in a project changes
    public function notifyProjectMemberRoleUpdated(int $userId, Project $project, ?string $oldRoleName = null, ?string $newRoleName = null): void
    {
        $oldRole = filled($oldRoleName) ? $oldRoleName : 'Unassigned';
        $newRole = filled($newRoleName) ? $newRoleName : 'Unassigned';

        $this->dispatchProjectAssignmentNotification(
            'Project Member Role Updated',
            $userId,
            $project,
            fn(User $recipient, User $targetUser, ?User $actor) => (int) $recipient->id === (int) $targetUser->id
                ? "{$this->actorName($actor)} changed your role in project '{$project->name}' from {$oldRole} to {$newRole}."
                : "{$this->actorName($actor)} changed {$targetUser->name}'s role in project '{$project->name}' from {$oldRole} to {$newRole}."
        );
    }

    // Project Status Changed: Notify users when a project's status changes
    public function notifyProjectStatusChanged(Project $project, User $actor, string $oldStatus, string $newStatus): void
    {
        $recipientIds = $this->getProjectChangeRecipientIds($project, $actor);

        if ($recipientIds === []) {
            return;
        }

        $projectName = $project->name ?? 'Project';
        $actorName = $actor->name ?? 'A team member';

        $this->sendToMany(
            $recipientIds,
            'Project Status Updated',
            "{$actorName} changed project '{$projectName}' status from {$oldStatus} to {$newStatus}.",
            $this->getProjectNotificationUrl($project),
            UserNotificationSetting::PROJECT_STATUS_CHANGED,
            (int) $actor->id,
            (int) $project->id,
            [
                'Status' => "{$oldStatus} to {$newStatus}",
            ]
        );
    }

    // Project Stage Changed: Notify users when a project's stage changes
    public function notifyProjectStageChanged(Project $project, User $actor, string $oldStage, string $newStage): void
    {
        $recipientIds = $this->getProjectChangeRecipientIds($project, $actor);

        if ($recipientIds === []) {
            return;
        }

        $projectName = $project->name ?? 'Project';
        $actorName = $actor->name ?? 'A team member';

        $this->sendToMany(
            $recipientIds,
            'Project Stage Updated',
            "{$actorName} moved project '{$projectName}' stage from {$oldStage} to {$newStage}.",
            $this->getProjectNotificationUrl($project),
            UserNotificationSetting::PROJECT_STAGE_CHANGED,
            (int) $actor->id,
            (int) $project->id,
            [
                'Stage' => "{$oldStage} to {$newStage}",
            ]
        );
    }

    // Project Timeline Changed: Notify users when a project's timeline changes
    public function notifyProjectTimelineChanged(Project $project, User $actor, array $changes): void
    {
        if ($changes === []) {
            return;
        }

        $recipientIds = $this->getProjectChangeRecipientIds($project, $actor);

        if ($recipientIds === []) {
            return;
        }

        $projectName = $project->name ?? 'Project';
        $actorName = $actor->name ?? 'A team member';
        $changeSummary = collect($changes)
            ->map(fn($change) => "{$change['field']} from {$change['old']} to {$change['new']}")
            ->implode('; ');

        $message = count($changes) === 1
            ? "{$actorName} changed project '{$projectName}' {$changeSummary}."
            : "{$actorName} changed project '{$projectName}' timeline: {$changeSummary}.";

        $this->sendToMany(
            $recipientIds,
            'Project Timeline Updated',
            $message,
            $this->getProjectNotificationUrl($project),
            UserNotificationSetting::PROJECT_TIMELINE_CHANGED,
            (int) $actor->id,
            (int) $project->id,
            [
                'Timeline Changes' => $this->formatTimelineChangeSummary($changes),
            ]
        );
    }

    // Milestone Timeline Changed: Notify assignee when task is created or updated
    public function notifyMilestoneTimelineChanged(ProjectMilestone $projectMilestone, User $actor, array $originalValues): void
    {
        $projectMilestone->loadMissing(['project.teamLeader']);
        $project = $projectMilestone->project;

        if (! $project) {
            return;
        }

        $changes = $this->buildTimelineChanges($projectMilestone, self::MILESTONE_TIMELINE_FIELDS, $originalValues);

        if ($changes === []) {
            return;
        }

        $recipientIds = $this->getProjectChangeRecipientIds($project, $actor);

        if ($recipientIds === []) {
            return;
        }

        $projectName = $project->name ?? 'Project';
        $milestoneName = $projectMilestone->name ?? 'Milestone';
        $actorName = $actor->name ?? 'A team member';

        $message = "{$actorName} updated milestone '{$milestoneName}' in project '{$projectName}'.\n\n"
            . $this->formatTimelineChangeSummary($changes);

        $this->sendToMany(
            $recipientIds,
            'Milestone Timeline Updated',
            $message,
            $this->getProjectNotificationUrl($project),
            UserNotificationSetting::PROJECT_TIMELINE_CHANGED,
            (int) $actor->id,
            (int) $project->id,
            [
                'Milestone' => $milestoneName,
                'Timeline Changes' => $this->formatTimelineChangeSummary($changes),
            ]
        );
    }

    // Sprint Timeline Changed: Notify assignee when task is created or updated
    public function notifySprintTimelineChanged(ProjectSprint $projectSprint, User $actor, array $originalValues): void
    {
        $projectSprint->loadMissing(['project.teamLeader']);
        $project = $projectSprint->project;

        if (! $project) {
            return;
        }

        $changes = $this->buildTimelineChanges($projectSprint, self::SPRINT_TIMELINE_FIELDS, $originalValues);

        if ($changes === []) {
            return;
        }

        $recipientIds = $this->getProjectChangeRecipientIds($project, $actor);

        if ($recipientIds === []) {
            return;
        }

        $projectName = $project->name ?? 'Project';
        $sprintName = $projectSprint->name ?? 'Sprint';
        $actorName = $actor->name ?? 'A team member';

        $message = "{$actorName} updated sprint '{$sprintName}' in project '{$projectName}'.\n\n"
            . $this->formatTimelineChangeSummary($changes);

        $this->sendToMany(
            $recipientIds,
            'Sprint Timeline Updated',
            $message,
            $this->getProjectNotificationUrl($project),
            UserNotificationSetting::PROJECT_TIMELINE_CHANGED,
            (int) $actor->id,
            (int) $project->id,
            [
                'Sprint' => $sprintName,
                'Timeline Changes' => $this->formatTimelineChangeSummary($changes),
            ]
        );
    }

    // ShiftSchedule: Notify users about shift assignment with shift details
    public function sendShiftAssigned(array|int $userIds, int $shiftId, Carbon|string $dateFrom, Carbon|string|null $dateTo = null, ?string $url = null): void
    {
        $userIds = is_array($userIds) ? $userIds : [$userIds];

        if (empty($userIds)) {
            return;
        }

        $dateFrom = $dateFrom instanceof Carbon ? $dateFrom : Carbon::parse($dateFrom);
        $dateTo = $dateTo ? ($dateTo instanceof Carbon ? $dateTo : Carbon::parse($dateTo)) : null;

        $shift = Shift::withTrashed()->find($shiftId);

        if (! $shift) {
            return;
        }

        $title = 'Shift Assigned';

        $message = "You have been assigned to shift '{$shift->name}' from {$dateFrom->format('Y-m-d')} to " . ($dateTo ? $dateTo->format('Y-m-d') : '--');

        $this->sendToMany(
            $userIds,
            $title,
            $message,
            $url,
            UserNotificationSetting::SHIFT_SCHEDULED,
            auth()->id(),
            null,
            [
                'Shift' => $shift->name,
                'From' => $dateFrom->format('Y-m-d'),
                'To' => $dateTo ? $dateTo->format('Y-m-d') : '--',
            ]
        );
    }

    // Task assignment: Notify assignee when task is created or updated
    public function sendTaskAssignmentIfNeeded(Task $task, ?int $currentAssigneeId, ?int $previousAssigneeId = null): void
    {
        $currentAssigneeId = filled($currentAssigneeId) ? (int) $currentAssigneeId : null;
        $previousAssigneeId = filled($previousAssigneeId) ? (int) $previousAssigneeId : null;

        if (!$currentAssigneeId || $currentAssigneeId === $previousAssigneeId) {
            return;
        }

        if ($this->wasTaskAssignmentNotificationHandled($task, $previousAssigneeId, $currentAssigneeId)) {
            return;
        }

        $task->loadMissing('project:id,name');

        $authUser = auth()->user();
        $isSelfAssigned = $authUser && (int) $authUser->id === $currentAssigneeId;

        $projectName = $task->project?->name ?: 'Untitled Project';
        $title = $previousAssigneeId ? 'Task Reassigned' : 'Task Assigned';

        if ($isSelfAssigned) {
            $message = $previousAssigneeId
                ? "You reassigned task '{$task->name}' to yourself in project '{$projectName}'."
                : "You assigned yourself to task '{$task->name}' in project '{$projectName}'.";
        } else {
            $actorName = $authUser?->name ?? 'A team member';

            $message = $previousAssigneeId
                ? "{$actorName} reassigned task '{$task->name}' to you in project '{$projectName}'."
                : "{$actorName} assigned you to task '{$task->name}' in project '{$projectName}'.";
        }

        $this->send(
            $currentAssigneeId,
            $title,
            $message,
            route('tasks.edit', $task),
            UserNotificationSetting::TASK_ASSIGNED,
            $authUser?->id,
            $task->project_id ? (int) $task->project_id : null,
            $this->taskEmailDetails($task, [
                'Assignee' => $task->currentAssignee?->name,
            ]),
            $this->taskAssignmentEmailSubjectContext($task, $authUser, $currentAssigneeId)
        );

        $this->markTaskAssignmentNotificationHandled($task, $previousAssigneeId, $currentAssigneeId);
    }

    // Task Timeline Changes: Notify assignee when task timeline is updated
    public function notifyTaskTimelineChanged(Task $task, User $actor, array $changes): void
    {
        if ($changes === []) {
            return;
        }

        $task->loadMissing([
            'project:id,name',
            'currentAssignee:id,name',
        ]);

        $recipientIds = $this->getTaskTimelineRecipientIds($task, $actor);

        if ($recipientIds === []) {
            return;
        }

        $actorName = $actor->name ?? 'A team member';
        $taskName = $task->name ?? 'Task';
        $projectName = $task->project?->name ?? 'Project';

        $message = "{$actorName} updated task '{$taskName}' in project '{$projectName}'.\n\n"
            . $this->formatTimelineChangeSummary($changes);

        $this->sendToMany(
            $recipientIds,
            'Task Timeline Updated',
            $message,
            route('tasks.edit', $task),
            UserNotificationSetting::TASK_TIMELINE_CHANGED,
            (int) $actor->id,
            $task->project_id ? (int) $task->project_id : null,
            $this->taskEmailDetails($task, [
                'Timeline Changes' => $this->formatTimelineChangeSummary($changes),
            ])
        );
    }

    // Task Assignment: Notify assignee when task is assigned or reassigned
    public function notifyTaskAssigneeChanged(Task $task, User $actor, ?int $previousAssigneeId, ?int $newAssigneeId): void
    {
        $previousAssigneeId = filled($previousAssigneeId) ? (int) $previousAssigneeId : null;
        $newAssigneeId = filled($newAssigneeId) ? (int) $newAssigneeId : null;

        if ($previousAssigneeId === $newAssigneeId) {
            return;
        }

        $this->markTaskAssignmentNotificationHandled($task, $previousAssigneeId, $newAssigneeId);

        $task->loadMissing([
            'project:id,name',
            'currentAssignee:id,name',
        ]);

        $recipientIds = $this->getTaskTimelineRecipientIds($task, $actor);

        if ($recipientIds === []) {
            return;
        }

        $assigneeNames = User::query()
            ->whereIn('id', array_filter([$previousAssigneeId, $newAssigneeId]))
            ->pluck('name', 'id');

        $actorName = $actor->name ?? 'A team member';
        $taskName = $task->name ?? 'Task';
        $projectName = $task->project?->name ?? 'Project';
        $previousAssigneeName = $previousAssigneeId
            ? ($assigneeNames->get($previousAssigneeId) ?? 'Unknown User')
            : 'Unassigned';
        $newAssigneeName = $newAssigneeId
            ? ($assigneeNames->get($newAssigneeId) ?? $task->currentAssignee?->name ?? 'Unknown User')
            : 'Unassigned';
        $url = route('tasks.edit', $task);
        $projectId = $task->project_id ? (int) $task->project_id : null;

        foreach ($recipientIds as $recipientId) {
            $message = match ((int) $recipientId) {
                $newAssigneeId => "{$actorName} assigned you to task '{$taskName}' in project '{$projectName}'.",
                $previousAssigneeId => "{$actorName} reassigned task '{$taskName}' in project '{$projectName}' from you to {$newAssigneeName}.",
                default => "{$actorName} reassigned task '{$taskName}' in project '{$projectName}' from {$previousAssigneeName} to {$newAssigneeName}.",
            };

            $this->send(
                (int) $recipientId,
                'Task Assigned',
                $message,
                $url,
                UserNotificationSetting::TASK_ASSIGNED,
                (int) $actor->id,
                $projectId,
                $this->taskEmailDetails($task, [
                    'Previous Assignee' => $previousAssigneeName,
                    'Assignee' => $newAssigneeName,
                ]),
                $this->taskAssignmentEmailSubjectContext($task, $actor, $newAssigneeId, $newAssigneeName)
            );
        }
    }

    // Task status change: Notify assignee, reporter, manager and super admins
    public function notifyTaskStatusChanged(Task $task, User $actor, string $oldStatus, string $newStatus): void
    {
        $userIds = $task->getRelatedUsers()
            ->pluck('id')
            ->filter()
            ->reject(fn($userId) => (int) $userId === (int) $task->current_assignee_id)
            ->unique()
            ->values()
            ->all();

        $taskName = $task->name ?? 'Task';
        $projectName = $task->project?->name ?? 'Project';
        $projectId = $task->project_id ? (int) $task->project_id : null;

        $title = "Task Status Updated";
        $url = url('tasks/' . $task->id . '/edit');

        User::whereIn('id', $userIds)->chunk(50, function ($users) use ($actor, $task, $taskName, $projectName, $projectId, $oldStatus, $newStatus, $title, $url) {
            foreach ($users as $user) {
                $actorLabel = $user->id === $actor->id ? 'You' : $actor->name;
                $message = "{$actorLabel} moved '{$taskName}' in '{$projectName}' from {$oldStatus} to {$newStatus}";
                $this->send(
                    $user->id,
                    $title,
                    $message,
                    $url,
                    UserNotificationSetting::TASK_STATUS_CHANGED,
                    (int) $actor->id,
                    $projectId,
                    $this->taskEmailDetails($task, [
                        'Status' => "{$oldStatus} to {$newStatus}",
                    ])
                );
            }
        });
    }

    // Task request: Notify reporters when a task request is created
    public function notifyTaskRequestCreated(Task $task): void
    {
        $task->loadMissing([
            'project:id,name',
            'currentAssignee:id,name',
        ]);

        $userIds = $task->getRelatedUsers()
            ->pluck('id')
            ->filter()
            ->reject(fn($userId) => (int) $userId === (int) $task->current_assignee_id)
            ->unique()
            ->values()
            ->all();

        if ($userIds === []) {
            return;
        }

        $taskName = $task->name ?? 'Task';
        $requesterName = $task->currentAssignee?->name ?? 'A team member';
        $projectName = $task->project?->name ?? 'Project';
        $emailSubjectContext = [
            'type' => 'task_request_submitted',
            'actor_id' => $task->current_assignee_id ? (int) $task->current_assignee_id : null,
            'actor_name' => $requesterName,
        ];

        $this->sendToMany(
            $userIds,
            'Task Request Created',
            "{$requesterName} requested task '{$taskName}' in '{$projectName}'.",
            route('tasks.edit', $task),
            UserNotificationSetting::TASK_REQUEST,
            $task->current_assignee_id ? (int) $task->current_assignee_id : null,
            $task->project_id ? (int) $task->project_id : null,
            $this->taskEmailDetails($task, [
                'Request Type' => 'Task Request',
            ]),
            $emailSubjectContext
        );
    }

    // Task request: Notify only the requested user when their task request is approved or rejected
    public function notifyTaskRequestReviewed(Task $task, User $reviewer, string $action, ?string $description = null): void
    {
        if (! $task->current_assignee_id) {
            return;
        }

        $task->loadMissing([
            'project:id,name',
            'currentAssignee:id,name',
        ]);

        $isRejected = $action === 'reject';
        $taskName = $task->name ?? 'Task';
        $projectName = $task->project?->name ?? 'Project';
        $reviewerName = $reviewer->name ?? 'A team member';
        $reviewLabel = $isRejected ? 'rejected' : 'approved';
        $emailSubjectContext = [
            'type' => $isRejected ? 'task_request_rejected' : 'task_request_approved',
            'actor_id' => (int) $reviewer->id,
            'actor_name' => $reviewerName,
            'assignee_id' => (int) $task->current_assignee_id,
            'assignee_name' => $task->currentAssignee?->name ?? 'Unknown User',
        ];

        $message = "{$reviewerName} {$reviewLabel} your task request '{$taskName}' in '{$projectName}'.";

        if ($isRejected && filled($description)) {
            $message .= ' Description: ' . trim((string) $description);
        }

        $this->send(
            (int) $task->current_assignee_id,
            $isRejected ? 'Task Request Rejected' : 'Task Request Approved',
            $message,
            route('tasks.edit', $task),
            UserNotificationSetting::TASK_REQUEST,
            (int) $reviewer->id,
            $task->project_id ? (int) $task->project_id : null,
            $this->taskEmailDetails($task, [
                'Request Type' => 'Task Request',
                'Status' => ucfirst($reviewLabel),
            ]),
            $emailSubjectContext
        );
    }

    // Break work request: Notify related users when a break work request is created
    public function notifyBreakRequestCreated(BreakWorkRequest $breakWorkRequest): void
    {
        $breakWorkRequest->loadMissing([
            'user:id,name',
            'user.manager',
        ]);

        $recipientIds = collect(User::getReporterChainUserIds((int) $breakWorkRequest->user_id))
            ->push($breakWorkRequest->user?->manager?->id)
            ->filter()
            ->reject(fn($userId) => (int) $userId === (int) $breakWorkRequest->user_id)
            ->unique()
            ->values()
            ->all();

        if ($recipientIds === []) {
            return;
        }

        [$workDate, $startTime, $endTime] = $this->formatBreakRequestDateTimeParts($breakWorkRequest);
        $userName = $breakWorkRequest->user?->name ?? 'A team member';
        $actor = auth()->user();
        $emailSubjectContext = [
            'type' => 'break_request_submitted',
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name ?? 'A team member',
        ];

        $this->sendToMany(
            $recipientIds,
            'Break Work Request Created',
            "{$userName} submitted a break work request for {$workDate} from {$startTime} to {$endTime}.",
            $this->getBreakRequestNotificationUrl($breakWorkRequest),
            UserNotificationSetting::BREAK_REQUEST,
            $breakWorkRequest->user_id ? (int) $breakWorkRequest->user_id : null,
            null,
            [
                'Request Type' => 'Break Request',
                'Work Date' => $workDate,
                'Start Time' => $startTime,
                'End Time' => $endTime,
            ],
            $emailSubjectContext
        );
    }

    // Break work request: Notify only the requested user when their break work request is approved
    public function notifyBreakRequestApproved(BreakWorkRequest $breakWorkRequest): void
    {
        if (! $breakWorkRequest->user_id) {
            return;
        }

        [$workDate, $startTime, $endTime] = $this->formatBreakRequestDateTimeParts($breakWorkRequest);
        $breakWorkRequest->loadMissing('user:id,name');
        $actor = auth()->user();
        $emailSubjectContext = [
            'type' => 'break_request_approved',
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name ?? 'A team member',
            'assignee_id' => (int) $breakWorkRequest->user_id,
            'assignee_name' => $breakWorkRequest->user?->name ?? 'Unknown User',
        ];

        $this->send(
            (int) $breakWorkRequest->user_id,
            'Break Work Request Approved',
            "Your break work request for {$workDate} from {$startTime} to {$endTime} has been approved.",
            $this->getBreakRequestNotificationUrl($breakWorkRequest, true),
            UserNotificationSetting::BREAK_REQUEST,
            auth()->id(),
            null,
            [
                'Request Type' => 'Break Request',
                'Work Date' => $workDate,
                'Start Time' => $startTime,
                'End Time' => $endTime,
                'Status' => 'Approved',
            ],
            $emailSubjectContext
        );
    }

    // Break work request: Notify only the requested user when their break work request is rejected
    public function notifyBreakRequestRejected(BreakWorkRequest $breakWorkRequest): void
    {
        if (! $breakWorkRequest->user_id) {
            return;
        }

        [$workDate, $startTime, $endTime] = $this->formatBreakRequestDateTimeParts($breakWorkRequest);
        $breakWorkRequest->loadMissing('user:id,name');
        $actor = auth()->user();
        $emailSubjectContext = [
            'type' => 'break_request_rejected',
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name ?? 'A team member',
            'assignee_id' => (int) $breakWorkRequest->user_id,
            'assignee_name' => $breakWorkRequest->user?->name ?? 'Unknown User',
        ];
        $message = "Your break work request for {$workDate} from {$startTime} to {$endTime} has been rejected.";

        if (filled($breakWorkRequest->rejection_reason)) {
            $message .= ' Reason: ' . trim((string) $breakWorkRequest->rejection_reason);
        }

        $this->send(
            (int) $breakWorkRequest->user_id,
            'Break Work Request Rejected',
            $message,
            $this->getBreakRequestNotificationUrl($breakWorkRequest),
            UserNotificationSetting::BREAK_REQUEST,
            auth()->id(),
            null,
            [
                'Request Type' => 'Break Request',
                'Work Date' => $workDate,
                'Start Time' => $startTime,
                'End Time' => $endTime,
                'Status' => 'Rejected',
            ],
            $emailSubjectContext
        );
    }

    // Task time log change request: Notify related users when a time log change request is created
    public function notifyTaskTimeLogChangeRequestCreated(TaskTimeLogChangeRequest $changeRequest): void
    {
        $changeRequest->loadMissing([
            'timeLog.task.project:id,name',
            'user:id,name',
            'user.manager',
        ]);

        $task = $changeRequest->timeLog?->task;

        if (! $task) {
            return;
        }

        $reporterChainUserIds = $changeRequest->user_id ? User::getReporterChainUserIds($changeRequest->user_id) : [];

        $recipientIds = collect($reporterChainUserIds)
            ->push($changeRequest->user?->manager?->id)
            ->filter()
            ->reject(fn($userId) => (int) $userId === (int) $changeRequest->user_id)
            ->unique()
            ->values()
            ->all();

        if ($recipientIds === []) {
            return;
        }

        $taskName = $task->name ?? 'Task';
        $projectName = $task->project?->name ?? 'Project';
        $requesterName = $changeRequest->user?->name ?? 'A team member';
        $emailSubjectContext = [
            'type' => 'task_time_log_change_request_submitted',
            'actor_id' => $changeRequest->user_id ? (int) $changeRequest->user_id : null,
            'actor_name' => $requesterName,
        ];

        $this->sendToMany(
            $recipientIds,
            'Task Time Log Change Request Created',
            "{$requesterName} requested a time log change for task '{$taskName}' in '{$projectName}'.",
            route('tasks.edit', $task),
            UserNotificationSetting::TASK_LOG_REQUEST,
            $changeRequest->user_id ? (int) $changeRequest->user_id : null,
            $task->project_id ? (int) $task->project_id : null,
            $this->taskEmailDetails($task, [
                'Request Type' => 'Task Log Change Request',
            ]),
            $emailSubjectContext
        );
    }

    // Task time log change request: Notify only the requested user when their time log change request is approved or rejected
    public function notifyTaskTimeLogChangeRequestReviewed(TaskTimeLogChangeRequest $changeRequest, User $reviewer, string $action, ?string $description = null): void
    {
        if (! $changeRequest->user_id) {
            return;
        }

        $changeRequest->loadMissing([
            'timeLog.task.project:id,name',
            'user:id,name',
        ]);

        $task = $changeRequest->timeLog?->task;

        if (! $task) {
            return;
        }

        $isRejected = $action === 'reject';
        $taskName = $task->name ?? 'Task';
        $projectName = $task->project?->name ?? 'Project';
        $reviewerName = $reviewer->name ?? 'A team member';
        $reviewLabel = $isRejected ? 'rejected' : 'approved';
        $emailSubjectContext = [
            'type' => $isRejected
                ? 'task_time_log_change_request_rejected'
                : 'task_time_log_change_request_approved',
            'actor_id' => (int) $reviewer->id,
            'actor_name' => $reviewerName,
            'assignee_id' => (int) $changeRequest->user_id,
            'assignee_name' => $changeRequest->user?->name ?? 'Unknown User',
        ];

        $message = "{$reviewerName} {$reviewLabel} your time log change request for task '{$taskName}' in '{$projectName}'.";

        if ($isRejected && filled($description)) {
            $message .= ' Description: ' . trim((string) $description);
        }

        $this->send(
            (int) $changeRequest->user_id,
            $isRejected ? 'Task Time Log Change Request Rejected' : 'Task Time Log Change Request Approved',
            $message,
            route('tasks.edit', $task),
            UserNotificationSetting::TASK_LOG_REQUEST,
            (int) $reviewer->id,
            $task->project_id ? (int) $task->project_id : null,
            $this->taskEmailDetails($task, [
                'Request Type' => 'Task Log Change Request',
                'Status' => ucfirst($reviewLabel),
            ]),
            $emailSubjectContext
        );
    }

    // Task timer stopped: Notify assignee when their running timer is stopped by other user or system due to status change
    public function notifyTaskTimerStoppedByOtherUser(Task $task, User $actor): void
    {
        $assigneeId = (int) ($task->current_assignee_id ?? 0);

        if (! $assigneeId || $assigneeId === (int) $actor->id) {
            return;
        }

        $task->loadMissing([
            'project:id,name',
            'currentAssignee:id,name',
        ]);

        $taskName = $task->name ?? 'Task';
        $projectName = $task->project?->name ?? 'Project';
        $actorName = $actor->name ?? 'A team member';

        $this->send(
            $assigneeId,
            'Task Timer Stopped',
            "{$actorName} stopped your running timer for task '{$taskName}' in project '{$projectName}'.",
            route('tasks.edit', $task),
            UserNotificationSetting::TASK_STATUS_CHANGED,
            (int) $actor->id,
            $task->project_id ? (int) $task->project_id : null,
            $this->taskEmailDetails($task)
        );
    }

    // Task timer stopped: Notify assignee when their running timer is stopped due to status change by other user or system
    public function notifyTaskTimerStoppedBecauseStatusChanged(Task $task, User $actor, string $statusName): void
    {
        $assigneeId = (int) ($task->current_assignee_id ?? 0);

        if (! $assigneeId || $assigneeId === (int) $actor->id) {
            return;
        }

        $task->loadMissing([
            'project:id,name',
            'currentAssignee:id,name',
        ]);

        $taskName = $task->name ?? 'Task';
        $projectName = $task->project?->name ?? 'Project';
        $actorName = $actor->name ?? 'A team member';

        $this->send(
            $assigneeId,
            'Task Timer Stopped',
            "{$actorName} changed task '{$taskName}' in project '{$projectName}' to '{$statusName}', so your running timer was stopped.",
            route('tasks.edit', $task),
            UserNotificationSetting::TASK_STATUS_CHANGED,
            (int) $actor->id,
            $task->project_id ? (int) $task->project_id : null,
            $this->taskEmailDetails($task, [
                'Status' => $statusName,
            ])
        );
    }

    // Task start: Notify assignee when their task is due soon based on due date and estimated time, only notify once when the task starts
    public function notifyTaskStart(Task $task): bool
    {
        $assigneeId = (int) ($task->current_assignee_id ?? 0);

        if (! $assigneeId) {
            return false;
        }

        if (($task->status?->type ?? null) !== 'pending') {
            return false;
        }

        if (! $task->due_date_time || (int) ($task->estimated_time_seconds ?? 0) <= 0) {
            return false;
        }

        if ($task->timeLogs()->exists()) {
            return false;
        }

        //update start_notify_at avoid duplicate
        $task->updateQuietly(['start_notify_at' => now()]);

        $startAt = $task->due_date_time->copy()->subSeconds((int) $task->estimated_time_seconds);
        $title = 'Task Start Reminder';
        $taskName = $task->name ?? 'Task';
        $projectName = $task->project?->name ?? 'Project';
        $startAtLabel = $startAt->timezone(config('constants.timezone', config('app.timezone')))->format('d-M-Y h:i A');
        $dueAtLabel = $task->due_date_time->copy()->timezone(config('constants.timezone', config('app.timezone')))->format('d-M-Y h:i A');
        $message = "Please start task '{$taskName}' in project '{$projectName}' by {$startAtLabel} to stay on track for the due time {$dueAtLabel}.";
        $url = route('tasks.edit', $task);

        $this->send(
            $assigneeId,
            $title,
            $message,
            $url,
            UserNotificationSetting::TASK_STATUS_CHANGED,
            null,
            $task->project_id ? (int) $task->project_id : null,
            $this->taskEmailDetails($task, [
                'Due Date' => $dueAtLabel,
                'Start By' => $startAtLabel,
            ])
        );

        return true;
    }

    // Handoff request: Notify related users when a handoff request is created
    public function notifyHandoffRequestCreated(HandoffRequest $handoffRequest, User $requester): void
    {
        $handoffRequest->loadMissing('project.teamLeader');

        $authorizedUserIds = User::permission(['handoff_request.view', 'handoff_request.view_all'])
            ->pluck('id')
            ->toArray();

        $teamLeaderId = $handoffRequest->project?->teamLeader?->id;

        $recipientIds = collect($authorizedUserIds)
            ->push($teamLeaderId)
            ->filter()
            ->reject(fn($id) => (int) $id === (int) $requester->id)
            ->unique()
            ->values()
            ->all();

        if (empty($recipientIds)) {
            return;
        }

        $projectName = $handoffRequest->project?->name ?? 'Project';
        $title = 'New Handoff Request';
        $message = "{$requester->name} created a new handoff request (#{$handoffRequest->id}) for project '{$projectName}'.";
        $url = route('handoff_requests.index', ['request_status' => 'pending']);
        $emailSubjectContext = [
            'type' => 'handoff_request_submitted',
            'actor_id' => (int) $requester->id,
            'actor_name' => $requester->name ?? 'A team member',
        ];

        $this->sendToMany(
            $recipientIds,
            $title,
            $message,
            $url,
            UserNotificationSetting::HANDOFF_REQUEST,
            (int) $requester->id,
            $handoffRequest->project_id ? (int) $handoffRequest->project_id : null,
            [
                'Request Type' => 'Handoff Request',
            ],
            $emailSubjectContext
        );
    }

    // Handoff request: Notify related users when a handoff request is assigned and a task is created
    public function notifyHandoffRequestAssigned(HandoffRequest $handoffRequest, Task $createdTask, User $actor): void
    {
        $requesterId = (int) $handoffRequest->user_id;

        if (!$requesterId || $requesterId === (int) $actor->id) {
            return;
        }

        $handoffRequest->loadMissing([
            'project:id,name',
            'user:id,name',
        ]);

        $taskName = $createdTask->name ?? 'Task';
        $projectName = $handoffRequest->project?->name ?? 'Project';
        $actorName = $actor->name ?? 'A team member';
        $emailSubjectContext = [
            'type' => 'handoff_request_assigned',
            'actor_id' => (int) $actor->id,
            'actor_name' => $actorName,
            'assignee_id' => $requesterId,
            'assignee_name' => $handoffRequest->user?->name ?? 'Unknown User',
        ];

        $title = 'Handoff Request Assigned';
        $message = "{$actorName} assigned your handoff request (#{$handoffRequest->id}) and a new task '{$taskName}' (#{$createdTask->id}) was created in project '{$projectName}'.";

        $this->send(
            $requesterId,
            $title,
            $message,
            route('tasks.edit', $createdTask),
            UserNotificationSetting::HANDOFF_REQUEST,
            (int) $actor->id,
            $handoffRequest->project_id ? (int) $handoffRequest->project_id : null,
            $this->taskEmailDetails($createdTask, [
                'Request Type' => 'Handoff Request',
            ]),
            $emailSubjectContext
        );
    }

    // Handoff request: Notify related users when a handoff request is noted
    public function notifyHandoffRequestNoted(HandoffRequest $handoffRequest, User $actor): void
    {
        $requesterId = (int) $handoffRequest->user_id;

        if (!$requesterId || $requesterId === (int) $actor->id) {
            return;
        }

        $handoffRequest->loadMissing([
            'project:id,name',
            'user:id,name',
        ]);

        $projectName = $handoffRequest->project?->name ?? 'Project';
        $title = 'Handoff Request Noted';
        $message = "{$actor->name} marked your handoff request (#{$handoffRequest->id}) in project '{$projectName}' as noted.";
        $url = route('handoff_requests.index', ['request_status' => 'noted']);
        $emailSubjectContext = [
            'type' => 'handoff_request_noted',
            'actor_id' => (int) $actor->id,
            'actor_name' => $actor->name ?? 'A team member',
            'assignee_id' => $requesterId,
            'assignee_name' => $handoffRequest->user?->name ?? 'Unknown User',
        ];

        $this->send(
            $requesterId,
            $title,
            $message,
            $url,
            UserNotificationSetting::HANDOFF_REQUEST,
            (int) $actor->id,
            $handoffRequest->project_id ? (int) $handoffRequest->project_id : null,
            [
                'Request Type' => 'Handoff Request',
            ],
            $emailSubjectContext
        );
    }

    // Task time extension request: Notify reporter chain users when a task time extension request is created
    public function notifyTaskTimeExtendRequest(User $requestingUser, Task $task, TaskExtendTimeRequest $extendRequest): void
    {
        $reporterChainUserIds = User::getReporterChainUserIds($requestingUser->id);

        $recipientIds = collect($reporterChainUserIds)
            ->reject(fn($userId) => (int) $userId === (int) $requestingUser->id)
            ->unique()
            ->values()
            ->all();

        if (empty($recipientIds)) {
            return;
        }

        $task->loadMissing('project:id,name');
        $taskName = $task->name ?? 'Task';
        $projectName = $task->project?->name ?? 'Project';
        $requesterName = $requestingUser->name ?? 'A team member';
        $title = 'Task Time Extend Request';
        $message = "{$requesterName} requested to extend time for task '{$taskName}' in '{$projectName}'.";
        $url = route('tasks.edit', $task);

        $this->sendToMany(
            $recipientIds,
            $title,
            $message,
            $url,
            UserNotificationSetting::TASK_TIME_EXTEND_REQUEST,
            (int) $requestingUser->id,
            $task->project_id ? (int) $task->project_id : null,
            $this->taskEmailDetails($task, [
                'Request Type' => 'Task Time Extension Request',
            ])
        );
    }

    // Task time extension request: Notify requesting user when their task time extension request is rejected
    public function notifyTaskTimeExtendRequestRejected(TaskExtendTimeRequest $extendRequest, Task $task, User $requestingUser): void
    {
        $task->loadMissing('project:id,name');
        $taskName = $task->name ?? 'Task';
        $projectName = $task->project?->name ?? 'Project';

        $extendRequest->loadMissing('rejector:id,name');
        $rejectorName = $extendRequest->rejector?->name ?? 'A manager';

        $title = 'Task Time Extend Request Rejected';
        $message = "{$rejectorName} rejected your time extend request for task '{$taskName}' in '{$projectName}'.";

        if (filled($extendRequest->rejection_reason)) {
            $message .= ' Reason: ' . trim((string) $extendRequest->rejection_reason);
        }

        $this->send(
            (int) $requestingUser->id,
            $title,
            $message,
            route('tasks.edit', $task),
            UserNotificationSetting::TASK_TIME_EXTEND_REQUEST,
            $extendRequest->rejected_by ? (int) $extendRequest->rejected_by : auth()->id(),
            $task->project_id ? (int) $task->project_id : null,
            $this->taskEmailDetails($task, [
                'Request Type' => 'Task Time Extension Request',
                'Status' => 'Rejected',
            ])
        );
    }

    // Task time extension request: Notify requesting user when their task time extension request is approved
    public function notifyTaskTimeExtendRequestApprovedToRequester(TaskExtendTimeRequest $extendRequest, Task $task, User $requestingUser): void
    {
        $task->loadMissing('project:id,name');
        $taskName = $task->name ?? 'Task';
        $projectName = $task->project?->name ?? 'Project';

        $extendRequest->loadMissing('approver:id,name');
        $approverName = $extendRequest->approver?->name ?? 'A manager';

        $previousEstimatedTime = $this->formatEstimatedTime((int) $extendRequest->estimated_time_seconds);
        $newApprovedEstimatedTime = $this->formatEstimatedTime((int) $extendRequest->new_estimated_time_seconds);

        $title = 'Task Time Extend Request Approved';

        $message = "'{$taskName}' in '{$projectName}' approved by {$approverName}.\n\n"
            . "Time changed from {$previousEstimatedTime} to {$newApprovedEstimatedTime}";

        $this->send(
            (int) $requestingUser->id,
            $title,
            $message,
            route('tasks.edit', $task),
            UserNotificationSetting::TASK_TIME_EXTEND_REQUEST,
            $extendRequest->approved_by ? (int) $extendRequest->approved_by : auth()->id(),
            $task->project_id ? (int) $task->project_id : null,
            $this->taskEmailDetails($task, [
                'Request Type' => 'Task Time Extension Request',
                'Status' => 'Approved',
                'Previous Estimate' => $previousEstimatedTime,
                'New Estimate' => $newApprovedEstimatedTime,
            ])
        );
    }

    // Task time extension request: Notify reporter chain users when a task time extension request is approved
    public function notifyTaskTimeExtendRequestApprovedToReporterChain(TaskExtendTimeRequest $extendRequest, Task $task, User $approvedByUser): void
    {
        $task->loadMissing('project:id,name');
        $taskName = $task->name ?? 'Task';
        $projectName = $task->project?->name ?? 'Project';

        $extendRequest->loadMissing('user');
        $requesterName = $extendRequest->user?->name ?? 'A team member';
        $previousEstimatedTime = $this->formatEstimatedTime((int) $extendRequest->estimated_time_seconds);
        $newApprovedEstimatedTime = $this->formatEstimatedTime((int) $extendRequest->new_estimated_time_seconds);

        $reporterChainUserIds = User::getReporterChainUserIds($approvedByUser->id);

        $recipientIds = collect($reporterChainUserIds)
            ->reject(fn($userId) => (int) $userId === (int) $approvedByUser->id)
            ->unique()
            ->values()
            ->all();

        if (empty($recipientIds)) {
            return;
        }

        $title = 'Task Time Extend Request Approved';
        $message = "'{$taskName}' in '{$projectName}' requested by {$requesterName} has been approved by {$approvedByUser->name}.\n\n"
            . "Time changed from {$previousEstimatedTime} to {$newApprovedEstimatedTime}";

        $this->sendToMany(
            $recipientIds,
            $title,
            $message,
            route('tasks.edit', $task),
            UserNotificationSetting::TASK_TIME_EXTEND_REQUEST,
            (int) $approvedByUser->id,
            $task->project_id ? (int) $task->project_id : null,
            $this->taskEmailDetails($task, [
                'Request Type' => 'Task Time Extension Request',
                'Status' => 'Approved',
                'Previous Estimate' => $previousEstimatedTime,
                'New Estimate' => $newApprovedEstimatedTime,
            ])
        );
    }

    /** ===========================================================================================================
     *  Private Helper Methods
     *  ===========================================================================================================
     */

    private function formatBreakRequestDateTimeParts(BreakWorkRequest $breakWorkRequest): array
    {
        $timezone = (string) config('constants.timezone', config('app.timezone'));
        $workDate = $breakWorkRequest->work_date?->format('Y-m-d') ?: (string) $breakWorkRequest->getRawOriginal('work_date');
        $startTime = $breakWorkRequest->started_at?->copy()->timezone($timezone)->format('H:i') ?? '--';
        $endTime = $breakWorkRequest->ended_at?->copy()->timezone($timezone)->format('H:i') ?? '--';

        return [$workDate, $startTime, $endTime];
    }

    private function getBreakRequestNotificationUrl(BreakWorkRequest $breakWorkRequest, bool $preferTaskUrl = false): ?string
    {
        if ($preferTaskUrl) {
            $breakWorkRequest->loadMissing('task:id');

            if ($breakWorkRequest->task_id && $breakWorkRequest->task) {
                return route('tasks.edit', $breakWorkRequest->task);
            }
        }

        return route('break-requests.index');
    }

    private function formatEstimatedTime(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        $parts = [];
        if ($hours > 0) {
            $parts[] = $hours . ' ' . Str::plural('Hour', $hours);
        }
        if ($minutes > 0 || empty($parts)) {
            $parts[] = $minutes . ' ' . Str::plural('Minute', $minutes);
        }

        return implode(' ', $parts);
    }

    private function dispatchTeamNotification(array $userIds, string $title, string $message): void
    {
        // There is no dedicated read-only team detail route, so avoid linking to the edit page here.
        $url = null;

        if (count($userIds) === 1) {
            $this->send(
                $userIds[0],
                $title,
                $message,
                $url,
                UserNotificationSetting::TEAM_ASSIGNED,
                auth()->id()
            );

            return;
        }

        $this->sendToMany(
            $userIds,
            $title,
            $message,
            $url,
            UserNotificationSetting::TEAM_ASSIGNED,
            auth()->id()
        );
    }

    private function normalizeUserIds(int|array $userIds): array
    {
        return collect(is_array($userIds) ? $userIds : [$userIds])
            ->filter(fn($userId) => filled($userId))
            ->map(fn($userId) => (int) $userId)
            ->unique()
            ->values()
            ->all();
    }

    private function getProjectNotificationUrl(Project $project): ?string
    {
        return route('projects.edit', $project);
    }

    private function dispatchProjectAssignmentNotification(string $title, int $targetUserId, Project $project, callable $messageBuilder): void
    {
        $actor = auth()->user();
        $targetUser = User::find($targetUserId);

        if (! $targetUser) {
            return;
        }

        $recipients = $this->getProjectAssignmentRecipientUsers($project, $targetUserId, $actor);

        if ($recipients->isEmpty()) {
            return;
        }

        foreach ($recipients as $recipient) {
            $this->send(
                (int) $recipient->id,
                $title,
                $messageBuilder($recipient, $targetUser, $actor),
                $this->getProjectNotificationUrl($project),
                UserNotificationSetting::PROJECT_ASSIGNED,
                $actor?->id ? (int) $actor->id : null,
                (int) $project->id
            );
        }
    }

    private function getProjectAssignmentRecipientUsers(Project $project, int $targetUserId, ?User $actor)
    {
        $project->loadMissing('teamLeader');

        $recipientIds = collect([$targetUserId])
            ->merge($actor ? User::getReporterChainUserIds((int) $actor->id) : [])
            ->push($project->teamLeader?->id)
            ->filter()
            ->map(fn($userId) => (int) $userId)
            ->unique()
            ->values()
            ->all();

        if ($recipientIds === []) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $recipientIds)
            ->where('is_active', true)
            ->where('delete_status', false)
            ->get(['id', 'name']);
    }

    private function actorName(?User $actor): string
    {
        return $actor?->name ?? 'A team member';
    }

    private function getProjectChangeRecipientIds(Project $project, User $actor): array
    {
        $project->loadMissing('teamLeader');

        $recipientIds = collect(User::getReporterChainUserIds((int) $actor->id))
            ->push($project->teamLeader?->id)
            ->filter()
            ->map(fn($userId) => (int) $userId)
            ->reject(fn($userId) => $userId === (int) $actor->id)
            ->unique()
            ->values()
            ->all();

        if ($recipientIds === []) {
            return [];
        }

        return User::query()
            ->whereIn('id', $recipientIds)
            ->where('is_active', true)
            ->where('delete_status', false)
            ->pluck('id')
            ->map(fn($userId) => (int) $userId)
            ->all();
    }

    private function getTaskTimelineRecipientIds(Task $task, User $actor): array
    {
        $task->loadMissing([
            'project.teamLeader',
            'projectMilestone.owner',
        ]);

        $recipientIds = collect([$task->current_assignee_id])
            ->merge(User::getReporterChainUserIds((int) $actor->id))
            ->push($task->project?->teamLeader?->id)
            ->push($task->projectMilestone?->owner?->id)
            ->filter()
            ->map(fn($userId) => (int) $userId)
            ->reject(fn($userId) => $userId === (int) $actor->id)
            ->unique()
            ->values()
            ->all();

        if ($recipientIds === []) {
            return [];
        }

        return User::query()
            ->whereIn('id', $recipientIds)
            ->where('is_active', true)
            ->where('delete_status', false)
            ->pluck('id')
            ->map(fn($userId) => (int) $userId)
            ->all();
    }

    private function buildTimelineChanges(ProjectMilestone|ProjectSprint $model, array $fields, array $originalValues): array
    {
        return collect($fields)
            ->filter(fn($label, $field) => $this->timelineValueChanged(
                $field,
                $originalValues[$field] ?? null,
                $model->getAttribute($field)
            ))
            ->map(function ($label, $field) use ($model, $originalValues) {
                return [
                    'field' => $label,
                    'old' => $this->formatTimelineValue($field, $originalValues[$field] ?? null),
                    'new' => $this->formatTimelineValue($field, $model->getAttribute($field)),
                ];
            })
            ->values()
            ->all();
    }

    private function timelineValueChanged(string $field, mixed $oldValue, mixed $newValue): bool
    {
        if ($field === 'estimated_time_seconds' || $field === 'owner_id') {
            return $this->normalizeNullableInteger($oldValue) !== $this->normalizeNullableInteger($newValue);
        }

        return $this->normalizeDateValue($oldValue) !== $this->normalizeDateValue($newValue);
    }

    private function formatTimelineValue(string $field, mixed $value): string
    {
        if ($field === 'estimated_time_seconds') {
            $seconds = $this->normalizeNullableInteger($value);

            return $seconds === null
                ? '--'
                : formatMinutesToHoursMinutes((int) round($seconds / 60));
        }

        if ($field === 'owner_id') {
            $userId = $this->normalizeNullableInteger($value);

            return $userId ? (User::find($userId)?->name ?? 'Unknown User') : 'Unassigned';
        }

        return AppServiceProvider::formatAppDate($value);
    }

    private function formatTimelineChangeSummary(array $changes): string
    {
        return collect($changes)
            ->map(fn($change) => "{$change['field']}:\n{$change['old']} → {$change['new']}")
            ->implode("\n\n");
    }

    private function normalizeNullableInteger(mixed $value): ?int
    {
        return filled($value) ? (int) $value : null;
    }

    private function normalizeDateValue(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return $value instanceof Carbon
                ? $value->toDateString()
                : Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return trim((string) $value) ?: null;
        }
    }

    private function taskAssignmentEmailSubjectContext(Task $task, ?User $actor, ?int $assigneeId, ?string $assigneeName = null): array
    {
        return [
            'type' => 'task_assignment',
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name ?? 'A team member',
            'assignee_id' => $assigneeId,
            'assignee_name' => $assigneeName ?? $task->currentAssignee?->name ?? 'Unassigned',
            'task_name' => $task->name ?? 'Task',
        ];
    }

    private function markTaskAssignmentNotificationHandled(Task $task, ?int $previousAssigneeId, ?int $newAssigneeId): void
    {
        self::$handledTaskAssignmentNotifications[$this->taskAssignmentNotificationKey($task, $previousAssigneeId, $newAssigneeId)] = true;
    }

    private function wasTaskAssignmentNotificationHandled(Task $task, ?int $previousAssigneeId, ?int $newAssigneeId): bool
    {
        return self::$handledTaskAssignmentNotifications[$this->taskAssignmentNotificationKey($task, $previousAssigneeId, $newAssigneeId)] ?? false;
    }

    private function taskAssignmentNotificationKey(Task $task, ?int $previousAssigneeId, ?int $newAssigneeId): string
    {
        return implode(':', [
            (int) $task->id,
            $previousAssigneeId ?? 'none',
            $newAssigneeId ?? 'none',
        ]);
    }

    private function buildEmailDetails(array $details, ?int $actorUserId = null, ?int $projectId = null): array
    {
        $baseDetails = [];

        if ($projectId) {
            $baseDetails['Project'] = Project::withTrashed()->whereKey($projectId)->value('name');
        }

        if ($actorUserId) {
            $baseDetails['Actor'] = User::whereKey($actorUserId)->value('name');
        }

        return $this->normalizeEmailDetails(array_merge($baseDetails, $details));
    }

    private function normalizeEmailDetails(array $details): array
    {
        return collect($details)
            ->map(function ($value, $label) {
                if (is_array($value)) {
                    $label = $value['label'] ?? $label;
                    $value = $value['value'] ?? null;
                }

                if (is_array($value)) {
                    $value = collect($value)
                        ->filter(fn($item) => filled($item))
                        ->implode("\n");
                }

                return [
                    'label' => (string) $label,
                    'value' => is_scalar($value) ? (string) $value : null,
                ];
            })
            ->filter(fn($detail) => filled($detail['label']) && filled($detail['value']))
            ->values()
            ->all();
    }

    private function taskEmailDetails(Task $task, array $details = []): array
    {
        $task->loadMissing([
            'projectMilestone:id,name',
            'projectSprint:id,name',
            'currentAssignee:id,name',
        ]);

        $baseDetails = [
            'Task' => $task->name,
            'Milestone' => $task->projectMilestone?->name,
            'Sprint' => $task->projectSprint?->name,
            'Assignee' => $task->currentAssignee?->name,
        ];

        return array_merge($baseDetails, $details);
    }
}
