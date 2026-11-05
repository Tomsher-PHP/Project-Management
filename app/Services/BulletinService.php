<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Bulletin;
use App\Models\BulletinCategory;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BulletinService
{
    public function __construct(
        protected AttachmentService $attachmentService,
    ) {
    }

    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $allowedSorts = [
            'title',
            'bulletin_category_id',
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

        return Bulletin::query()
            ->with(['category:id,name,color', 'creator:id,name'])
            ->withCount('allocations')
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters) {
                $query->where('title', 'like', '%'.trim($filters['search']).'%');
            })
            ->when(filled($filters['bulletin_category_id'] ?? null), fn (Builder $query) => $query->whereIn('bulletin_category_id', (array) $filters['bulletin_category_id']))
            ->when(filled($filters['priority'] ?? null), fn (Builder $query) => $query->whereIn('priority', (array) $filters['priority']))
            ->when(filled($filters['audience_type'] ?? null), fn (Builder $query) => $query->whereIn('audience_type', (array) $filters['audience_type']))
            ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->whereIn('status', (array) $filters['status']))
            ->orderBy($sortBy, $sortDirection)
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data, User $actor, array $files = []): Bulletin
    {
        $uploaded = [];

        try {
            return DB::transaction(function () use ($data, $actor, $files, &$uploaded) {
                $data = $this->prepareData($data, null);
                $this->validateForPublication($data);

                $bulletin = Bulletin::create([
                    ...$this->bulletinAttributes($data),
                    'published_at' => (int) $data['status'] === Bulletin::STATUS_PUBLISHED ? now() : null,
                    'added_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]);

                $this->syncAudience($bulletin, $data);
                $this->uploadAttachments($bulletin, $files, $uploaded);

                return $bulletin->fresh($this->detailRelations());
            });
        } catch (\Throwable $exception) {
            $this->removeUploadedFiles($uploaded);
            throw $exception;
        }
    }

    public function update(Bulletin $bulletin, array $data, User $actor, array $files = []): Bulletin
    {
        $uploaded = [];

        try {
            return DB::transaction(function () use ($bulletin, $data, $actor, $files, &$uploaded) {
                $data['status'] = $bulletin->status;
                $data = $this->prepareData($data, $bulletin);
                $this->validateForPublication($data);

                $audienceChanged = $this->audienceChanged($bulletin, $data);
                $bulletin->update([
                    ...$this->bulletinAttributes($data),
                    'status' => $bulletin->status,
                    'published_at' => $bulletin->published_at,
                    'updated_by' => $actor->id,
                ]);

                if ($bulletin->status !== Bulletin::STATUS_PUBLISHED || $audienceChanged) {
                    $this->syncAudience($bulletin, $data);
                }

                $this->uploadAttachments($bulletin, $files, $uploaded);

                return $bulletin->fresh($this->detailRelations());
            });
        } catch (\Throwable $exception) {
            $this->removeUploadedFiles($uploaded);
            throw $exception;
        }
    }

    public function changeStatus(Bulletin $bulletin, int $status, User $actor): Bulletin
    {
        if ($status === Bulletin::STATUS_PUBLISHED && $bulletin->status !== Bulletin::STATUS_DRAFT) {
            throw ValidationException::withMessages(['status' => 'Only draft bulletins can be published.']);
        }

        if ($status === Bulletin::STATUS_ARCHIVED && $bulletin->status !== Bulletin::STATUS_PUBLISHED) {
            throw ValidationException::withMessages(['status' => 'Only published bulletins can be archived.']);
        }

        return DB::transaction(function () use ($bulletin, $status, $actor) {
            if ($status === Bulletin::STATUS_PUBLISHED) {
                $data = [
                    ...$bulletin->only([
                        'title',
                        'content',
                        'bulletin_category_id',
                        'priority',
                        'audience_type',
                        'start_date',
                        'end_date',
                    ]),
                    'status' => Bulletin::STATUS_PUBLISHED,
                    'team_ids' => $bulletin->target_team_ids ?? [],
                    'user_ids' => $bulletin->recipients()->pluck('users.id')->all(),
                ];

                $this->validateForPublication($data);
                $this->syncAudience($bulletin, $data);
            }

            $bulletin->update([
                'status' => $status,
                'published_at' => $status === Bulletin::STATUS_PUBLISHED
                    ? ($bulletin->published_at ?? now())
                    : $bulletin->published_at,
                'updated_by' => $actor->id,
            ]);

            return $bulletin->fresh($this->detailRelations());
        });
    }

    public function delete(Bulletin $bulletin): bool
    {
        $bulletin->loadMissing('attachments');

        return DB::transaction(function () use ($bulletin) {
            if ($bulletin->attachments->isNotEmpty()) {
                $this->attachmentService->delete($bulletin->attachments);
            }

            return (bool) $bulletin->delete();
        });
    }

    public function deleteAttachment(Bulletin $bulletin, Attachment $attachment): bool
    {
        if (
            (int) $attachment->link_id !== (int) $bulletin->id
            || $attachment->link_type !== Bulletin::class
            || $attachment->category !== Bulletin::ATTACHMENT_CATEGORY
        ) {
            return false;
        }

        return (bool) $this->attachmentService->delete([$attachment]);
    }

    protected function prepareData(array $data, ?Bulletin $bulletin): array
    {
        $data['content'] = $this->sanitizeContent($data['content'] ?? '');
        $data['team_ids'] = $this->normalizeIds($data['team_ids'] ?? []);
        $data['user_ids'] = $this->normalizeIds($data['user_ids'] ?? []);

        if (empty($data['bulletin_category_id'])) {
            throw ValidationException::withMessages([
                'bulletin_category_id' => 'Please select a category.',
            ]);
        }

        if ($data['audience_type'] === Bulletin::AUDIENCE_ALL) {
            $data['team_ids'] = [];
            $data['user_ids'] = [];
        } elseif ($data['audience_type'] === Bulletin::AUDIENCE_TEAMS) {
            $data['user_ids'] = [];
        } else {
            $data['team_ids'] = [];
        }

        return $data;
    }

    protected function bulletinAttributes(array $data): array
    {
        return [
            'title' => trim($data['title']),
            'content' => $data['content'],
            'bulletin_category_id' => (int) $data['bulletin_category_id'],
            'priority' => (int) $data['priority'],
            'audience_type' => $data['audience_type'],
            'target_team_ids' => $data['audience_type'] === Bulletin::AUDIENCE_TEAMS ? $data['team_ids'] : null,
            'status' => (int) $data['status'],
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
        ];
    }

    protected function validateForPublication(array $data): void
    {
        if ((int) ($data['status'] ?? Bulletin::STATUS_DRAFT) !== Bulletin::STATUS_PUBLISHED) {
            return;
        }

        $errors = [];

        if (blank($data['title'] ?? null)) {
            $errors['title'] = 'Title is required before publishing.';
        }
        if (
            blank($data['bulletin_category_id'] ?? null)
            || ! BulletinCategory::query()->active()->whereKey($data['bulletin_category_id'])->exists()
        ) {
            $errors['bulletin_category_id'] = 'Category is required before publishing.';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    protected function syncAudience(Bulletin $bulletin, array $data): void
    {
        if ($data['audience_type'] === Bulletin::AUDIENCE_ALL) {
            $bulletin->recipients()->detach();
            return;
        }

        $recipientIds = $data['audience_type'] === Bulletin::AUDIENCE_TEAMS
            ? $this->resolveTeamMemberIds($data['team_ids'])
            : $this->eligibleUserIds($data['user_ids']);

        if ((int) $data['status'] === Bulletin::STATUS_PUBLISHED && $recipientIds === []) {
            throw ValidationException::withMessages([
                $data['audience_type'] === Bulletin::AUDIENCE_TEAMS ? 'team_ids' : 'user_ids'
                    => 'A published bulletin must have at least one eligible recipient.',
            ]);
        }

        $bulletin->recipients()->sync($recipientIds);
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

    protected function audienceChanged(Bulletin $bulletin, array $data): bool
    {
        if ($bulletin->audience_type !== $data['audience_type']) {
            return true;
        }

        if ($data['audience_type'] === Bulletin::AUDIENCE_TEAMS) {
            $current = $this->normalizeIds($bulletin->target_team_ids ?? []);
            return $current !== $data['team_ids'];
        }

        if ($data['audience_type'] === Bulletin::AUDIENCE_USERS) {
            $current = $this->normalizeIds($bulletin->recipients()->pluck('users.id')->all());
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
    protected function uploadAttachments(Bulletin $bulletin, array $files, array &$uploaded): void
    {
        $configuredDisk = config('filesystems.default', 'local');
        $disk = $configuredDisk === 's3' ? 's3' : 'local';

        foreach ($files as $file) {
            $attachment = $this->attachmentService->upload(
                $file,
                "bulletins/{$bulletin->id}",
                $bulletin,
                $disk,
                'private',
                false,
                Bulletin::ATTACHMENT_CATEGORY,
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
