<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeetingResource extends JsonResource
{
    /**
     * Transform the meeting resource into an array for the external API contract.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'start_at' => $this->start_at?->toIso8601String(),
            'end_at' => $this->end_at?->toIso8601String(),
            'url' => $this->url,
            'location_details' => $this->location_details,
            'rescheduled_from_id' => $this->rescheduled_from_id,

            'status' => $this->relationLoaded('meetingStatus') && $this->meetingStatus ? [
                'id' => $this->meetingStatus->id,
                'code' => $this->meetingStatus->code,
                'name' => $this->meetingStatus->name,
                'color' => $this->meetingStatus->color,
            ] : ($this->meetingStatus ? [
                'id' => $this->meetingStatus->id,
                'code' => $this->meetingStatus->code,
                'name' => $this->meetingStatus->name,
                'color' => $this->meetingStatus->color,
            ] : null),

            'meeting_type' => $this->relationLoaded('meetingType') && $this->meetingType ? [
                'id' => $this->meetingType->id,
                'name' => $this->meetingType->name,
                'color' => $this->meetingType->color,
            ] : ($this->meetingType ? [
                'id' => $this->meetingType->id,
                'name' => $this->meetingType->name,
                'color' => $this->meetingType->color,
            ] : null),

            'location' => $this->relationLoaded('meetingLocation') && $this->meetingLocation ? [
                'id' => $this->meetingLocation->id,
                'name' => $this->meetingLocation->name,
            ] : ($this->meetingLocation ? [
                'id' => $this->meetingLocation->id,
                'name' => $this->meetingLocation->name,
            ] : null),

            'project' => $this->relationLoaded('project') && $this->project ? [
                'id' => $this->project->id,
                'name' => $this->project->name,
            ] : ($this->project ? [
                'id' => $this->project->id,
                'name' => $this->project->name,
            ] : null),

            'organizer' => $this->relationLoaded('organizer') && $this->organizer ? [
                'id' => $this->organizer->id,
                'name' => $this->organizer->name,
                'email' => $this->organizer->email,
            ] : ($this->organizer ? [
                'id' => $this->organizer->id,
                'name' => $this->organizer->name,
                'email' => $this->organizer->email,
            ] : null),

            'participants' => $this->relationLoaded('participants') ? $this->participants->map(function ($p) {
                return [
                    'id' => $p->id,
                    'user_id' => $p->user_id,
                    'name' => $p->user?->name ?? $p->name,
                    'email' => $p->user?->email ?? $p->email,
                    'is_external' => (bool) $p->is_external,
                ];
            })->values()->all() : [],

            'tags' => $this->relationLoaded('tags') ? $this->tags->map(function ($t) {
                return [
                    'id' => $t->id,
                    'name' => $t->name,
                    'color' => $t->color,
                ];
            })->values()->all() : [],
        ];
    }
}
