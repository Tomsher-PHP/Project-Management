<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskNoteRequest extends FormRequest
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

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['nullable', 'string', 'required_without:attachments'],
            'attachments' => ['nullable', 'array', 'required_without:description'],
            'attachments.*' => ['file', 'mimes:pdf,xls,xlsx,doc,docx,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.required_without' => 'Please add a note or attach at least one file.',
            'attachments.required_without' => 'Please add a note or attach at least one file.',
        ];
    }
}
