<?php

namespace App\Http\Requests;

use App\Models\QuickNote;
use Illuminate\Foundation\Http\FormRequest;

class QuickNoteReorderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'notes' => ['required', 'array', 'min:1'],
            'notes.*.id' => ['required', 'integer', 'exists:quick_notes,id'],
            'notes.*.sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Perform additional validation hooks.
     */
    public function after(): array
    {
        return [function ($validator) {
            $user = $this->user();
            if (! $user) {
                return;
            }

            $noteIds = collect($this->input('notes', []))
                ->pluck('id')
                ->filter()
                ->unique()
                ->values();

            if ($noteIds->isNotEmpty()) {
                $ownedCount = QuickNote::query()
                    ->where('user_id', $user->id)
                    ->whereIn('id', $noteIds)
                    ->count();

                if ($ownedCount !== $noteIds->count()) {
                    $validator->errors()->add('notes', 'One or more quick notes do not belong to you.');
                }
            }
        }];
    }
}
