<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuickNote extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'quick_notes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'project_id',
        'task_id',
        'title',
        'content',
        'sort_order',
        'is_pinned',
        'is_archived',
        'color',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'project_id' => 'integer',
            'task_id' => 'integer',
            'sort_order' => 'integer',
            'is_pinned' => 'boolean',
            'is_archived' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the quick note.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the project that the quick note belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the task that the quick note belongs to.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
