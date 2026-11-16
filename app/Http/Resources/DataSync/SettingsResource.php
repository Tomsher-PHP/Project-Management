<?php

namespace App\Http\Resources\DataSync;

use App\Models\Industry;
use App\Models\ProjectStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingsResource extends JsonResource
{
    /**
     * Transform the settings master resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
        ];

        if ($this->resource instanceof Industry) {
            $data['parent_code'] = $this->parent?->code;
        }

        if ($this->resource instanceof ProjectStatus) {
            $data['color'] = $this->color;
            $data['type'] = $this->type;
        }

        $data['sort_order'] = (int) $this->sort_order;

        if ($this->resource instanceof ProjectStatus) {
            $data['is_default'] = (bool) $this->is_default;
            $data['is_completed'] = (bool) $this->is_completed;
        }

        $data['is_system'] = (bool) $this->is_system;
        $data['is_active'] = (bool) $this->is_active;

        return $data;
    }
}

