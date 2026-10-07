<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectTimelineStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // handled by controller middleware
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:renewal,extension,new',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'customer_end_date' => 'nullable|date|after_or_equal:end_date',
            'estimated_time_minutes' => 'nullable|integer|min:0',
            'customer_estimate_minutes' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['status' => \App\Models\ProjectTimeline::STATUS_PLANNED]);
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $projectId = $this->route('project');
            $projectId = is_object($projectId) ? $projectId->id : $projectId;

            $startDate = $this->input('start_date');
            $endDate = $this->input('end_date');

            if ($startDate && $endDate) {
                $overlapping = \App\Models\ProjectTimeline::where('project_id', $projectId)
                    ->where('status', '!=', \App\Models\ProjectTimeline::STATUS_CANCELLED)
                    ->where(function ($query) use ($startDate, $endDate) {
                        $query->where('start_date', '<=', $endDate)
                            ->where('end_date', '>=', $startDate);
                    })
                    ->exists();

                if ($overlapping) {
                    $validator->errors()->add('start_date', 'The date range overlaps with an existing timeline.');
                }
            }
        });
    }
}
