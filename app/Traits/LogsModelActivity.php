<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

trait LogsModelActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        $options = LogOptions::defaults()
            ->useLogName($this->getActivityLogName())
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly($this->getActivityIgnoredOnlyChanges())
            ->setDescriptionForEvent(fn (string $eventName) => $this->getActivityDescription($eventName));

        if (! empty($this->getFillable())) {
            $options->logFillable();
        } else {
            $options->logUnguarded();
        }

        return $options->logExcept($this->getActivityLogExceptAttributes());
    }

    protected function getActivityLogName(): string
    {
        if (property_exists($this, 'activityLogName') && filled($this->activityLogName)) {
            return $this->activityLogName;
        }

        return (string) Str::of(class_basename(static::class))
            ->snake()
            ->plural();
    }

    protected function getActivityDescription(string $eventName): string
    {
        return $this->getActivityLogName() . '.' . $eventName;
    }

    protected function getActivityLogExceptAttributes(): array
    {
        $except = ['created_at', 'updated_at'];

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            $except[] = 'deleted_at';
        }

        if (property_exists($this, 'hidden')) {
            $except = array_merge($except, $this->hidden);
        }

        if (property_exists($this, 'activityLogExceptAttributes')) {
            $except = array_merge($except, $this->activityLogExceptAttributes);
        }

        return array_values(array_unique($except));
    }

    protected function getActivityIgnoredOnlyChanges(): array
    {
        $ignored = ['updated_at'];

        if (property_exists($this, 'activityLogIgnoredOnlyChanges')) {
            $ignored = array_merge($ignored, $this->activityLogIgnoredOnlyChanges);
        }

        return array_values(array_unique($ignored));
    }
}
