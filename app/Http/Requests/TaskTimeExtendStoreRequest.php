<?php

namespace App\Http\Requests;

use App\Models\Task;
use App\Models\TaskExtendTimeRequest;
use Illuminate\Foundation\Http\FormRequest;

class TaskTimeExtendStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'new_estimated_time_minutes' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'new_estimated_time_minutes.required' => 'Please enter new estimated time.',
            'new_estimated_time_minutes.integer' => 'Please enter a valid new estimated time.',
            'new_estimated_time_minutes.min' => 'Please enter a valid new estimated time.',
            'reason.max' => 'Please enter a valid reason.',
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $task = $this->route('task');

                if (
                    ! $task instanceof Task
                    || (int) $this->user()?->id !== (int) $task->current_assignee_id
                ) {
                    return;
                }

                $existingRequest = TaskExtendTimeRequest::query()
                    ->where('task_id', $task->id)
                    ->latest('id')
                    ->first();

                if ($existingRequest && ($existingRequest->status === 'approved' || $existingRequest->status === 'rejected')) {
                    $validator->errors()->add(
                        'extend_request',
                        'Only one extend time request is allowed per task.'
                    );
                }
            },
        ];
    }
}
