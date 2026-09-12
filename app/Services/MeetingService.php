<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\MeetingStatus;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use App\Mail\MeetingExternalParticipantAssignedMail;
use App\Mail\MeetingExternalParticipantRemovedMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class MeetingService
{
    protected AttachmentService $attachmentService;
    protected NotificationService $notificationService;

    public function __construct(AttachmentService $attachmentService, NotificationService $notificationService)
    {
        $this->attachmentService = $attachmentService;
        $this->notificationService = $notificationService;
    }
    /**
     * Retrieve paginated or query list of meetings.
     */
    public function list(array $filters = [], ?User $user = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = Meeting::query()
            ->with([
                'project:id,name,project_code',
                'meetingType:id,name,color',
                'meetingLocation:id,name',
                'meetingStatus:id,name,code,color,type',
                'organizer:id,name,email',
                'tags:id,name,color',
            ])
            ->when($user, fn(Builder $q) => $q->accessibleBy($user))
            ->when(! empty($filters['project_id']), fn(Builder $q) => $q->where('project_id', $filters['project_id']))
            ->when(! empty($filters['meeting_type_id']), fn(Builder $q) => $q->where('meeting_type_id', $filters['meeting_type_id']))
            ->when(! empty($filters['meeting_location_id']), fn(Builder $q) => $q->where('meeting_location_id', $filters['meeting_location_id']))
            ->when(! empty($filters['meeting_status_id']), fn(Builder $q) => $q->where('meeting_status_id', $filters['meeting_status_id']))
            ->when(! empty($filters['organizer_id']), fn(Builder $q) => $q->where('organizer_id', $filters['organizer_id']))
            ->when(! empty($filters['start_date']), fn(Builder $q) => $q->whereDate('start_at', '>=', $filters['start_date']))
            ->when(! empty($filters['end_date']), fn(Builder $q) => $q->whereDate('end_at', '<=', $filters['end_date']))
            ->when(! empty($filters['search']), function (Builder $q) use ($filters) {
                $search = $filters['search'];
                $q->where(function (Builder $sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('location_details', 'like', "%{$search}%");
                });
            });

        $sortField = $filters['sort_by'] ?? 'start_at';
        $sortDirection = $filters['sort_dir'] ?? 'desc';

        return $query->orderBy($sortField, $sortDirection)->paginate($perPage);
    }

    /**
     * Retrieve calendar events within a date range for FullCalendar.
     */
    public function getCalendarEvents(array $filters = [], ?User $user = null, ?string $start = null, ?string $end = null): array
    {
        $query = Meeting::query()
            ->with([
                'project:id,name,project_code',
                'meetingType:id,name,color',
                'meetingLocation:id,name',
                'meetingStatus:id,name,code,color,type',
                'organizer:id,name,email',
                'tags:id,name,color',
            ])
            ->when($user, fn(Builder $q) => $q->accessibleBy($user))
            ->when(! empty($filters['project_id']), fn(Builder $q) => $q->where('project_id', $filters['project_id']))
            ->when(! empty($filters['meeting_type_id']), fn(Builder $q) => $q->where('meeting_type_id', $filters['meeting_type_id']))
            ->when(! empty($filters['meeting_location_id']), fn(Builder $q) => $q->where('meeting_location_id', $filters['meeting_location_id']))
            ->when(! empty($filters['meeting_status_id']), fn(Builder $q) => $q->where('meeting_status_id', $filters['meeting_status_id']))
            ->when(! empty($filters['organizer_id']), fn(Builder $q) => $q->where('organizer_id', $filters['organizer_id']))
            ->when(! empty($filters['search']), function (Builder $q) use ($filters) {
                $search = $filters['search'];
                $q->where(function (Builder $sub) use ($search) {
                    $sub->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('location_details', 'like', "%{$search}%");
                });
            });

        if ($start) {
            $query->whereDate('end_at', '>=', substr($start, 0, 10));
        }

        if ($end) {
            $query->whereDate('start_at', '<=', substr($end, 0, 10));
        }

        $meetings = $query->get();

        return $meetings->map(function (Meeting $meeting) {
            $color = $meeting->meetingType?->color
                ?: ($meeting->meetingStatus?->color ?: '#3B82F6');

            return [
                'id' => $meeting->id,
                'title' => $meeting->title,
                'start' => $meeting->start_at->toIso8601String(),
                'end' => $meeting->end_at->toIso8601String(),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => '#FFFFFF',
                'extendedProps' => [
                    'type' => 'meeting',
                    'meeting_id' => $meeting->id,
                    'edit_url' => route('meetings.edit', $meeting->id),
                    'update_url' => route('meetings.update', $meeting->id),
                    'status_name' => $meeting->meetingStatus?->name,
                    'type_name' => $meeting->meetingType?->name,
                    'location_name' => $meeting->meetingLocation?->name,
                    'project_name' => $meeting->project?->name,
                    'organizer_name' => $meeting->organizer?->name,
                ],
            ];
        })->toArray();
    }

    /**
     * Find a single meeting by ID.
     */
    public function getMeeting(int $id, array $relations = []): ?Meeting
    {
        $defaultRelations = [
            'project',
            'meetingType',
            'meetingLocation',
            'meetingStatus',
            'organizer',
            'participants.user',
            'tags',
            'attachments',
        ];

        return Meeting::with(array_merge($defaultRelations, $relations))->find($id);
    }

    /**
     * Create a new Meeting along with participants and tags.
     */
    public function create(array $data, ?User $user = null, array $files = []): Meeting
    {
        $createdMeeting = DB::transaction(function () use ($data, $user, $files) {
            if (empty($data['organizer_id']) && $user) {
                $data['organizer_id'] = $user->id;
            }

            if (empty($data['meeting_status_id'])) {
                $data['meeting_status_id'] = MeetingStatus::where('code', MeetingStatus::STATUS_SCHEDULED)->value('id')
                    ?? MeetingStatus::query()->first()?->id;
            }

            $meeting = Meeting::create([
                'project_id' => $data['project_id'] ?? null,
                'meeting_type_id' => $data['meeting_type_id'],
                'meeting_location_id' => $data['meeting_location_id'] ?? null,
                'meeting_status_id' => $data['meeting_status_id'],
                'organizer_id' => $data['organizer_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'start_at' => $data['start_at'],
                'end_at' => $data['end_at'],
                'url' => $data['url'] ?? null,
                'location_details' => $data['location_details'] ?? null,
                'added_by' => $data['added_by'] ?? ($user?->id ?? Auth::id()),
            ]);

            $this->syncParticipants($meeting, $data['participants'] ?? []);

            if (isset($data['tag_ids']) && is_array($data['tag_ids'])) {
                $meeting->tags()->sync($data['tag_ids']);
            }

            if (! empty($files['attachments'])) {
                $disk = env('FILESYSTEM_DISK', config('filesystems.default'));
                foreach ($files['attachments'] as $file) {
                    $this->attachmentService->upload(
                        $file,
                        'meetings',
                        $meeting,
                        $disk,
                        'public',
                        false,
                        Meeting::MEETING_FILE_CATEGORY
                    );
                }
            }

            return $meeting->fresh(['project', 'meetingType', 'meetingLocation', 'meetingStatus', 'organizer', 'participants.user', 'tags', 'attachments']);
        });

        $actor = $user ?? auth()->user();
        $this->notificationService->notifyMeetingAssigned($createdMeeting, $actor);

        // Send email notifications to external participants
        $this->sendExternalParticipantEmailsOnCreate($createdMeeting);

        return $createdMeeting;
    }

    /**
     * Update an existing Meeting along with participants and tags.
     */
    public function update(Meeting $meeting, array $data, ?User $user = null, array $files = []): Meeting
    {
        $oldStatusId = $meeting->meeting_status_id;

        // Capture original external participants BEFORE update
        $originalExternalMap = [];
        $originalExternal = $meeting->participants()
            ->where(function ($query) {
                $query->where('is_external', true)->orWhereNull('user_id');
            })
            ->whereNotNull('email')
            ->get();

        foreach ($originalExternal as $p) {
            $email = strtolower(trim((string) $p->email));
            if ($email !== '') {
                $originalExternalMap[$email] = [
                    'name' => $p->name,
                    'send_email' => (bool) $p->send_email,
                ];
            }
        }

        $participantChanges = [
            'added_user_ids' => [],
            'removed_user_ids' => [],
            'kept_user_ids' => [],
        ];

        $updatedMeeting = DB::transaction(function () use ($meeting, $data, $files, &$participantChanges) {
            $meeting->update(array_intersect_key($data, array_flip([
                'project_id',
                'meeting_type_id',
                'meeting_location_id',
                'meeting_status_id',
                'organizer_id',
                'title',
                'description',
                'start_at',
                'end_at',
                'url',
                'location_details',
            ])));

            if (array_key_exists('participants', $data)) {
                $participantChanges = $this->syncParticipants($meeting, is_array($data['participants']) ? $data['participants'] : []);
            }

            if (array_key_exists('tag_ids', $data) && is_array($data['tag_ids'])) {
                $meeting->tags()->sync($data['tag_ids']);
            }

            if (! empty($files['attachments'])) {
                $disk = env('FILESYSTEM_DISK', config('filesystems.default'));
                foreach ($files['attachments'] as $file) {
                    $this->attachmentService->upload(
                        $file,
                        'meetings',
                        $meeting,
                        $disk,
                        'public',
                        false,
                        Meeting::MEETING_FILE_CATEGORY
                    );
                }
            }

            return $meeting->fresh(['project', 'meetingType', 'meetingLocation', 'meetingStatus', 'organizer', 'participants.user', 'tags', 'attachments']);
        });

        $actor = $user ?? auth()->user();

        // 1. Notify newly added internal participants
        if (! empty($participantChanges['added_user_ids'])) {
            $this->notificationService->notifyMeetingAssigned($updatedMeeting, $actor, $participantChanges['added_user_ids']);
        }

        // 2. Notify removed internal participants
        if (! empty($participantChanges['removed_user_ids'])) {
            $this->notificationService->notifyMeetingParticipantRemoved($updatedMeeting, $participantChanges['removed_user_ids'], $actor);
        }

        // 3. Notify status change if status updated
        if ((int) $oldStatusId !== (int) $updatedMeeting->meeting_status_id) {
            $oldStatus = MeetingStatus::find($oldStatusId)?->name ?? 'Unknown';
            $newStatus = $updatedMeeting->meetingStatus?->name ?? 'Unknown';
            $this->notificationService->notifyMeetingStatusChanged($updatedMeeting, $actor, $oldStatus, $newStatus);
        }

        // 4. Send email notifications to external participants on update
        $this->sendExternalParticipantEmailsOnUpdate($updatedMeeting, $originalExternalMap);

        return $updatedMeeting;
    }

    /**
     * Delete a Meeting and its participants and attachments.
     */
    public function delete(Meeting $meeting, ?User $user = null): bool
    {
        $actor = $user ?? auth()->user();

        $meeting->loadMissing([
            'project',
            'meetingType',
            'meetingLocation',
            'meetingStatus',
            'organizer',
            'participants.user',
            'attachments',
        ]);

        // 1. Notify internal participants, organizer, and creator
        $this->notificationService->notifyMeetingDeleted($meeting, $actor);

        // 2. Send removal email to external participants if any
        $this->sendExternalParticipantEmailsOnDelete($meeting);

        return DB::transaction(function () use ($meeting) {
            $this->attachmentService->delete($meeting->attachments);
            $meeting->participants()->delete();
            return (bool) $meeting->delete();
        });
    }

    /**
     * Delete a single attachment from a meeting.
     */
    public function deleteAttachment(Meeting $meeting, Attachment $attachment): bool
    {
        if ($attachment->link_id === $meeting->id && $attachment->link_type === Meeting::class) {
            return (bool) $this->attachmentService->delete([$attachment]);
        }
        return false;
    }

    /**
     * Synchronize meeting participants selectively without deleting unchanged participants.
     *
     * @return array{added_user_ids: array, removed_user_ids: array, kept_user_ids: array}
     */
    protected function syncParticipants(Meeting $meeting, array $participantsData): array
    {
        // 1. Get existing internal participants
        $existingInternal = $meeting->participants()
            ->where('is_external', false)
            ->whereNotNull('user_id')
            ->get();
        $existingInternalUserIds = $existingInternal->pluck('user_id')->map(fn($id) => (int) $id)->unique()->toArray();

        // 2. Parse incoming participants
        $incomingInternalUserIds = [];
        $incomingExternal = [];

        foreach ($participantsData as $participant) {
            $isExternal = filter_var($participant['is_external'] ?? false, FILTER_VALIDATE_BOOLEAN) || empty($participant['user_id']);
            if (! $isExternal && ! empty($participant['user_id'])) {
                $incomingInternalUserIds[] = (int) $participant['user_id'];
            } else {
                $incomingExternal[] = $participant;
            }
        }
        $incomingInternalUserIds = array_values(array_unique($incomingInternalUserIds));

        // Calculate diffs for internal participants
        $removedInternalUserIds = array_values(array_diff($existingInternalUserIds, $incomingInternalUserIds));
        $addedInternalUserIds = array_values(array_diff($incomingInternalUserIds, $existingInternalUserIds));
        $keptInternalUserIds = array_values(array_intersect($existingInternalUserIds, $incomingInternalUserIds));

        // A. Delete only removed internal participants
        if (! empty($removedInternalUserIds)) {
            $meeting->participants()
                ->where('is_external', false)
                ->whereIn('user_id', $removedInternalUserIds)
                ->delete();
        }

        // B. Add new internal participants & update kept participants settings
        foreach ($participantsData as $participant) {
            $isExternal = filter_var($participant['is_external'] ?? false, FILTER_VALIDATE_BOOLEAN) || empty($participant['user_id']);
            if (! $isExternal && ! empty($participant['user_id'])) {
                $uId = (int) $participant['user_id'];
                if (in_array($uId, $addedInternalUserIds, true)) {
                    $name = $participant['name'] ?? null;
                    $email = $participant['email'] ?? null;
                    $phone = $participant['phone'] ?? null;

                    if (! $name || ! $email) {
                        $pUser = User::find($uId);
                        if ($pUser) {
                            $name = $name ?: $pUser->name;
                            $email = $email ?: $pUser->email;
                        }
                    }

                    MeetingParticipant::create([
                        'meeting_id' => $meeting->id,
                        'user_id' => $uId,
                        'is_external' => false,
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'send_email' => filter_var($participant['send_email'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    ]);
                } else if (in_array($uId, $keptInternalUserIds, true)) {
                    $meeting->participants()
                        ->where('is_external', false)
                        ->where('user_id', $uId)
                        ->update([
                            'send_email' => filter_var($participant['send_email'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        ]);
                }
            }
        }

        // C. Sync External participants
        $existingExternalEmails = $meeting->participants()
            ->where('is_external', true)
            ->pluck('email')
            ->filter()
            ->map(fn($e) => strtolower(trim($e)))
            ->toArray();

        $incomingExternalEmails = array_values(array_unique(array_filter(array_map(fn($p) => strtolower(trim($p['email'] ?? '')), $incomingExternal))));
        $removedExternalEmails = array_values(array_diff($existingExternalEmails, $incomingExternalEmails));

        if (! empty($removedExternalEmails)) {
            $meeting->participants()
                ->where('is_external', true)
                ->whereIn('email', $removedExternalEmails)
                ->delete();
        }

        foreach ($incomingExternal as $extP) {
            $extEmail = strtolower(trim($extP['email'] ?? ''));
            if (! $extEmail) {
                continue;
            }

            MeetingParticipant::updateOrCreate(
                [
                    'meeting_id' => $meeting->id,
                    'is_external' => true,
                    'email' => $extEmail,
                ],
                [
                    'name' => $extP['name'] ?? null,
                    'phone' => $extP['phone'] ?? null,
                    'send_email' => filter_var($extP['send_email'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ]
            );
        }

        return [
            'added_user_ids' => $addedInternalUserIds,
            'removed_user_ids' => $removedInternalUserIds,
            'kept_user_ids' => $keptInternalUserIds,
        ];
    }

    /**
     * Send email notifications to eligible external participants upon meeting creation.
     */
    protected function sendExternalParticipantEmailsOnCreate(Meeting $meeting): void
    {
        $meeting->loadMissing(['organizer', 'meetingLocation', 'meetingType', 'participants']);

        $sentEmails = [];

        foreach ($meeting->participants as $participant) {
            $isExternal = (bool) $participant->is_external || empty($participant->user_id);
            if (! $isExternal) {
                continue;
            }

            if (! (bool) $participant->send_email) {
                continue;
            }

            $email = strtolower(trim((string) $participant->email));
            if (empty($email) || in_array($email, $sentEmails, true)) {
                continue;
            }

            $sentEmails[] = $email;

            try {
                Mail::to($email)->send(new MeetingExternalParticipantAssignedMail($meeting, $participant->name));
            } catch (\Throwable $e) {
                logger()->error("Failed to send external participant assigned email to {$email}: " . $e->getMessage());
            }
        }
    }

    /**
     * Send email notifications to external participants upon meeting update.
     */
    protected function sendExternalParticipantEmailsOnUpdate(Meeting $meeting, array $originalExternalMap): void
    {
        $meeting->loadMissing(['organizer', 'meetingLocation', 'meetingType', 'participants']);

        $newExternalMap = [];
        foreach ($meeting->participants as $p) {
            $isExternal = (bool) $p->is_external || empty($p->user_id);
            if (! $isExternal) {
                continue;
            }

            $email = strtolower(trim((string) $p->email));
            if ($email !== '') {
                $newExternalMap[$email] = [
                    'name' => $p->name,
                    'send_email' => (bool) $p->send_email,
                ];
            }
        }

        // 1. Removed external participants: in $originalExternalMap but not in $newExternalMap
        $removedEmails = array_diff_key($originalExternalMap, $newExternalMap);
        foreach ($removedEmails as $email => $info) {
            if ($info['send_email'] === true) {
                try {
                    Mail::to($email)->send(new MeetingExternalParticipantRemovedMail($meeting, $info['name']));
                } catch (\Throwable $e) {
                    logger()->error("Failed to send external participant removed email to {$email}: " . $e->getMessage());
                }
            }
        }

        // 2. Newly added external participants: in $newExternalMap but not in $originalExternalMap
        $addedEmails = array_diff_key($newExternalMap, $originalExternalMap);
        foreach ($addedEmails as $email => $info) {
            if ($info['send_email'] === true) {
                try {
                    Mail::to($email)->send(new MeetingExternalParticipantAssignedMail($meeting, $info['name']));
                } catch (\Throwable $e) {
                    logger()->error("Failed to send external participant assigned email to {$email}: " . $e->getMessage());
                }
            }
        }

        // Unchanged external participants (in both maps): no email sent!
    }

    /**
     * Send removal email to external participants when a meeting is deleted.
     */
    protected function sendExternalParticipantEmailsOnDelete(Meeting $meeting): void
    {
        $meeting->loadMissing(['organizer', 'meetingLocation', 'meetingType', 'participants']);

        $sentEmails = [];

        foreach ($meeting->participants as $participant) {
            $isExternal = (bool) $participant->is_external || empty($participant->user_id);
            if (! $isExternal) {
                continue;
            }

            // Only deliver email to external participant if meeting_participants.send_email = 1
            $shouldSendEmail = (int) $participant->send_email === 1 || (bool) $participant->send_email === true;
            if (! $shouldSendEmail) {
                continue;
            }

            $email = strtolower(trim((string) $participant->email));
            if (empty($email) || in_array($email, $sentEmails, true)) {
                continue;
            }

            $sentEmails[] = $email;

            try {
                Mail::to($email)->send(new MeetingExternalParticipantRemovedMail($meeting, $participant->name));
            } catch (\Throwable $e) {
                logger()->error("Failed to send external participant removal email on delete to {$email}: " . $e->getMessage());
            }
        }
    }
}
