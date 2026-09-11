<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\MeetingStatus;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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
        return DB::transaction(function () use ($data, $user, $files) {
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
        $this->notificationService->notifyMeetingAssigned($meeting, $actor);

        return $meeting;
    }

    /**
     * Update an existing Meeting along with participants and tags.
     */
    public function update(Meeting $meeting, array $data, ?User $user = null, array $files = []): Meeting
    {
        $oldStatusId = $meeting->meeting_status_id;

        $updatedMeeting = DB::transaction(function () use ($meeting, $data, $files) {
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
                $this->syncParticipants($meeting, $data['participants']);
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
        if ((int) $oldStatusId !== (int) $updatedMeeting->meeting_status_id) {
            $oldStatus = MeetingStatus::find($oldStatusId)?->name ?? 'Unknown';
            $newStatus = $updatedMeeting->meetingStatus?->name ?? 'Unknown';
            $this->notificationService->notifyMeetingStatusChanged($updatedMeeting, $actor, $oldStatus, $newStatus);
        }

        return $updatedMeeting;
    }

    /**
     * Delete a Meeting and its participants and attachments.
     */
    public function delete(Meeting $meeting): bool
    {
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
     * Synchronize meeting participants.
     */
    protected function syncParticipants(Meeting $meeting, array $participantsData): void
    {
        $meeting->participants()->delete();

        foreach ($participantsData as $participant) {
            $isExternal = filter_var($participant['is_external'] ?? false, FILTER_VALIDATE_BOOLEAN) || empty($participant['user_id']);
            $userId = ! $isExternal && ! empty($participant['user_id']) ? (int) $participant['user_id'] : null;

            $name = $participant['name'] ?? null;
            $email = $participant['email'] ?? null;
            $phone = $participant['phone'] ?? null;

            if ($userId && (! $name || ! $email)) {
                $pUser = User::find($userId);
                if ($pUser) {
                    $name = $name ?: $pUser->name;
                    $email = $email ?: $pUser->email;
                }
            }

            MeetingParticipant::create([
                'meeting_id' => $meeting->id,
                'user_id' => $userId,
                'is_external' => $isExternal,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'send_email' => filter_var($participant['send_email'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
