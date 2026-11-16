<?php

namespace App\Http\Requests;

use App\Models\ProjectNote;
use Illuminate\Foundation\Http\FormRequest;

class ProjectNoteRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $description = $this->input('description');

        if (is_string($description)) {
            $plainText = trim(str_replace("\xc2\xa0", ' ', strip_tags($description)));

            $this->merge([
                'description' => $plainText === '' ? null : $description,
            ]);
        }
    }

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
        $note = $this->route('note');
        if (is_numeric($note)) {
            $note = ProjectNote::find($note);
        }

        $hasExistingAttachments = false;
        if ($note instanceof ProjectNote) {
            $removedIds = (array) $this->input('removed_attachment_ids', []);
            $hasExistingAttachments = $note->attachments()
                ->whereNotIn('id', $removedIds)
                ->exists();
        }

        $requiresContent = ! $hasExistingAttachments;

        return [
            'description' => ['nullable', 'string', $requiresContent ? 'required_without:attachments' : 'nullable'],
            'attachments' => ['nullable', 'array', $requiresContent ? 'required_without:description' : 'nullable'],
            'attachments.*' => ['file', 'mimes:pdf,xls,xlsx,doc,docx,ppt,pptx,jpg,jpeg,png', 'max:1048576'],
            'removed_attachment_ids' => ['nullable', 'array'],
            'removed_attachment_ids.*' => ['integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.required_without' => 'Please add a note or attach at least one file.',
            'attachments.required_without' => 'Please add a note or attach at least one file.',
            'attachments.*.max' => 'Maximum file size is 1GB per file.',
        ];
    }
}
