<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskNoteRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $note = $this->input('note') ?? $this->input('description');

        if (is_string($note)) {
            $plainText = trim(str_replace("\xc2\xa0", ' ', strip_tags($note)));
            $cleanedNote = $plainText === '' ? null : $note;

            $this->merge([
                'note' => $cleanedNote,
                'description' => $cleanedNote,
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array', 'required_without_all:note,description'],
            'attachments.*' => ['file', 'mimes:pdf,xls,xlsx,doc,docx,ppt,pptx,jpg,jpeg,png', 'max:1048576'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required_without' => 'Please add a note or attach at least one file.',
            'description.required_without' => 'Please add a note or attach at least one file.',
            'attachments.required_without_all' => 'Please add a note or attach at least one file.',
            'attachments.*.max' => 'Maximum file size is 1GB per file.',
        ];
    }
}
