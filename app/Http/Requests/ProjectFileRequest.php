<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectFileRequest extends FormRequest
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
            'project_files' => ['required', 'array'],
            'project_files.*' => ['file', 'mimes:pdf,xls,xlsx,doc,docx,jpg,jpeg,png', 'max:5120'], // 5MB
        ];
    }

    public function messages(): array
    {
        return [
            'project_file.required' => 'Please select a file to upload.',
            'project_file.file' => 'The selected file is not a valid file.',
            'project_file.mimes' => 'The selected file must be a PDF, Excel, Word, or image file.',
            'project_file.max' => 'The selected file must not be larger than 5MB.',
        ];
    }
}
