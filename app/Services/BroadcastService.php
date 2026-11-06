<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Broadcast;
use App\Models\BroadcastCategory;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BroadcastService
{
    public function __construct(
        protected AttachmentService $attachmentService,
    ) {
    }

    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $allowedSorts = [
            'title',
            'broadcast_category_id',
            'priority',
            'audience_type',
            'start_date',
            'end_date',
            'status',
            'created_at',
        ];
        $sortBy = in_array($filters['sort_by'] ?? null, $allowedSorts, true)
            ? $filters['sort_by']
            : 'created_at';
        $sortDirection = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return Broadcast::query()
            ->with(['category:id,name,color', 'creator:id,name'])
            ->withCount('allocations')
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters) {
                $query->where('title', 'like', '%'.trim($filters['search']).'%');
            })
            ->when(filled($filters['broadcast_category_id'] ?? null), fn (Builder $query) => $query->whereIn('broadcast_category_id', (array) $filters['broadcast_category_id']))
            ->when(filled($filters['priority'] ?? null), fn (Builder $query) => $query->whereIn('priority', (array) $filters['priority']))
            ->when(filled($filters['audience_type'] ?? null), fn (Builder $query) => $query->whereIn('audience_type', (array) $filters['audience_type']))
            ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->whereIn('status', (array) $filters['status']))
            ->orderBy($sortBy, $sortDirection)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data, User $actor, array $files = []): Broadcast
    {
        $uploaded = [];

        try {
            return DB::transaction(function () use ($data, $actor, $files, &$uploaded) {
                $data = $this->prepareData($data, null);
                $this->validateForPublication($data);

                $broadcast = Broadcast::create([
                    ...$this->broadcastAttributes($data),
                    'published_at' => (int) $data['status'] === Broadcast::STATUS_PUBLISHED ? now() : null,
                    'added_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]);

                $this->syncAudience($broadcast, $data);
                $this->uploadAttachments($broadcast, $files, $uploaded);

                return $broadcast->fresh($this->detailRelations());
            });
        } catch (\Throwable $exception) {
            $this->removeUploadedFiles($uploaded);
            throw $exception;
        }
    }

    public function update(Broadcast $broadcast, array $data, User $actor, array $files = []): Broadcast
    {
        $uploaded = [];

        try {
            return DB::transaction(function () use ($broadcast, $data, $actor, $files, &$uploaded) {
                $data['status'] = $broadcast->status;
                $data = $this->prepareData($data, $broadcast);
                $this->validateForPublication($data);

                $audienceChanged = $this->audienceChanged($broadcast, $data);
                $broadcast->update([
                    ...$this->broadcastAttributes($data),
                    'status' => $broadcast->status,
                    'published_at' => $broadcast->published_at,
                    'updated_by' => $actor->id,
                ]);

                if ($broadcast->status !== Broadcast::STATUS_PUBLISHED || $audienceChanged) {
                    $this->syncAudience($broadcast, $data);
                }

                $this->uploadAttachments($broadcast, $files, $uploaded);

                return $broadcast->fresh($this->detailRelations());
            });
        } catch (\Throwable $exception) {
            $this->removeUploadedFiles($uploaded);
            throw $exception;
        }
    }

    public function changeStatus(Broadcast $broadcast, int $status, User $actor): Broadcast
    {
        if ($broadcast->status === $status) {
            return $broadcast;
        }

        return DB::transaction(function () use ($broadcast, $status, $actor) {
            if ($status === Broadcast::STATUS_PUBLISHED) {
                $data = [
                    ...$broadcast->only([
                        'title',
                        'content',
                        'broadcast_category_id',
                        'priority',
                        'audience_type',
                        'start_date',
                        'end_date',
                    ]),
                    'status' => Broadcast::STATUS_PUBLISHED,
                    'team_ids' => $broadcast->target_team_ids ?? [],
                    'user_ids' => $broadcast->recipients()->pluck('users.id')->all(),
                ];

                $this->validateForPublication($data);
                $this->syncAudience($broadcast, $data);
            }

            $broadcast->update([
                'status' => $status,
                'published_at' => $status === Broadcast::STATUS_PUBLISHED
                    ? ($broadcast->published_at ?? now())
                    : $broadcast->published_at,
                'updated_by' => $actor->id,
            ]);

            return $broadcast->fresh($this->detailRelations());
        });
    }

    public function delete(Broadcast $broadcast): bool
    {
        $broadcast->loadMissing('attachments');

        return DB::transaction(function () use ($broadcast) {
            if ($broadcast->attachments->isNotEmpty()) {
                $this->attachmentService->delete($broadcast->attachments);
            }

            return (bool) $broadcast->delete();
        });
    }

    public function deleteAttachment(Broadcast $broadcast, Attachment $attachment): bool
    {
        if (
            (int) $attachment->link_id !== (int) $broadcast->id
            || $attachment->link_type !== Broadcast::class
            || $attachment->category !== Broadcast::ATTACHMENT_CATEGORY
        ) {
            return false;
        }

        return (bool) $this->attachmentService->delete([$attachment]);
    }

    protected function prepareData(array $data, ?Broadcast $broadcast): array
    {
        $data['content'] = $this->sanitizeContent($data['content'] ?? '');
        $data['team_ids'] = $this->normalizeIds($data['team_ids'] ?? []);
        $data['user_ids'] = $this->normalizeIds($data['user_ids'] ?? []);

        if (blank($data['content'] ?? null)) {
            throw ValidationException::withMessages([
                'content' => 'The content field is required.',
            ]);
        }

        if (empty($data['broadcast_category_id'])) {
            throw ValidationException::withMessages([
                'broadcast_category_id' => 'Please select a category.',
            ]);
        }

        if (! empty($data['start_date']) && empty($data['end_date'])) {
            $data['end_date'] = $data['start_date'];
        }

        if ($data['audience_type'] === Broadcast::AUDIENCE_ALL) {
            $data['team_ids'] = [];
            $data['user_ids'] = [];
        } elseif ($data['audience_type'] === Broadcast::AUDIENCE_TEAMS) {
            $data['user_ids'] = [];
        } else {
            $data['team_ids'] = [];
        }

        return $data;
    }

    protected function broadcastAttributes(array $data): array
    {
        return [
            'title' => trim($data['title']),
            'content' => $data['content'],
            'broadcast_category_id' => (int) $data['broadcast_category_id'],
            'priority' => (int) $data['priority'],
            'audience_type' => $data['audience_type'],
            'target_team_ids' => $data['audience_type'] === Broadcast::AUDIENCE_TEAMS ? $data['team_ids'] : null,
            'status' => (int) $data['status'],
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
        ];
    }

    protected function validateForPublication(array $data): void
    {
        if ((int) ($data['status'] ?? Broadcast::STATUS_DRAFT) !== Broadcast::STATUS_PUBLISHED) {
            return;
        }

        $errors = [];

        if (blank($data['title'] ?? null)) {
            $errors['title'] = 'Title is required before publishing.';
        }
        if (blank($data['content'] ?? null)) {
            $errors['content'] = 'Content is required before publishing.';
        }
        if (
            blank($data['broadcast_category_id'] ?? null)
            || ! BroadcastCategory::query()->active()->whereKey($data['broadcast_category_id'])->exists()
        ) {
            $errors['broadcast_category_id'] = 'Category is required before publishing.';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    protected function syncAudience(Broadcast $broadcast, array $data): void
    {
        if ($data['audience_type'] === Broadcast::AUDIENCE_ALL) {
            $broadcast->recipients()->detach();
            return;
        }

        $recipientIds = $data['audience_type'] === Broadcast::AUDIENCE_TEAMS
            ? $this->resolveTeamMemberIds($data['team_ids'])
            : $this->eligibleUserIds($data['user_ids']);

        if ((int) $data['status'] === Broadcast::STATUS_PUBLISHED && $recipientIds === []) {
            throw ValidationException::withMessages([
                $data['audience_type'] === Broadcast::AUDIENCE_TEAMS ? 'team_ids' : 'user_ids'
                    => 'A published broadcast must have at least one eligible recipient.',
            ]);
        }

        $broadcast->recipients()->sync($recipientIds);
    }

    protected function resolveTeamMemberIds(array $teamIds): array
    {
        if ($teamIds === []) {
            return [];
        }

        return DB::table('team_user')
            ->join('teams', 'teams.id', '=', 'team_user.team_id')
            ->join('users', 'users.id', '=', 'team_user.user_id')
            ->whereIn('team_user.team_id', $teamIds)
            ->where('teams.is_active', true)
            ->whereNull('teams.deleted_at')
            ->where('team_user.is_active', true)
            ->whereNull('team_user.deleted_at')
            ->where('users.is_active', true)
            ->where('users.delete_status', false)
            ->whereNull('users.deleted_at')
            ->distinct()
            ->pluck('users.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected function eligibleUserIds(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        return User::query()
            ->active()
            ->where('delete_status', false)
            ->whereIn('id', $userIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected function audienceChanged(Broadcast $broadcast, array $data): bool
    {
        if ($broadcast->audience_type !== $data['audience_type']) {
            return true;
        }

        if ($data['audience_type'] === Broadcast::AUDIENCE_TEAMS) {
            $current = $this->normalizeIds($broadcast->target_team_ids ?? []);
            return $current !== $data['team_ids'];
        }

        if ($data['audience_type'] === Broadcast::AUDIENCE_USERS) {
            $current = $this->normalizeIds($broadcast->recipients()->pluck('users.id')->all());
            return $current !== $data['user_ids'];
        }

        return false;
    }

    protected function normalizeIds(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', array_filter($ids, 'filled'))));
        sort($ids);

        return $ids;
    }

    /**
     * @param array<int, UploadedFile> $files
     * @param array<int, Attachment> $uploaded
     */
    protected function uploadAttachments(Broadcast $broadcast, array $files, array &$uploaded): void
    {
        $configuredDisk = config('filesystems.default', 'local');
        $disk = $configuredDisk === 's3' ? 's3' : 'local';

        foreach ($files as $file) {
            $attachment = $this->attachmentService->upload(
                $file,
                "broadcasts/{$broadcast->id}",
                $broadcast,
                $disk,
                'private',
                false,
                Broadcast::ATTACHMENT_CATEGORY,
            );

            if (! $attachment) {
                throw ValidationException::withMessages([
                    'attachments' => 'An attachment could not be uploaded. Please try again.',
                ]);
            }

            $uploaded[] = $attachment;
        }
    }

    /** @param array<int, Attachment> $attachments */
    protected function removeUploadedFiles(array $attachments): void
    {
        foreach ($attachments as $attachment) {
            if ($attachment->file_path) {
                Storage::disk($attachment->disk ?? config('filesystems.default'))->delete($attachment->file_path);
            }
        }
    }

    protected function sanitizeContent(string $content): string
    {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,strong,b,em,i,u,s,ol,ul,li,a[href|title|target],h1,h2,h3,blockquote,code,pre');
        $config->set('Attr.AllowedFrameTargets', ['_blank']);

        return (new \HTMLPurifier($config))->purify($content);
    }

    protected function detailRelations(): array
    {
        return [
            'category:id,name,color',
            'creator:id,name',
            'updater:id,name',
            'recipients:id,name,email',
            'attachments',
        ];
    }
}

