<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApiClient extends Model
{
    use HasFactory, SoftDeletes;

    public const SCOPE_MEETINGS_READ = 'meetings.read';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'key_id',
        'secret_hash',
        'scopes',
        'is_active',
        'last_used_at',
        'expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'secret_hash',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Scope a query to only include active API clients.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Determine whether the API client has the given scope.
     */
    public function hasScope(string $scope): bool
    {
        if (! is_array($this->scopes)) {
            return false;
        }

        return in_array($scope, $this->scopes, true);
    }
}
