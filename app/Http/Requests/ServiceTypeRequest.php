<?php

namespace App\Http\Requests;

use App\Models\ServiceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class ServiceTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->isMethod('post') && $this->has('name')) {
            $code = Str::slug($this->name) . '-' . random_int(1000, 9999);
            while (ServiceType::withTrashed()->where('code', $code)->exists()) {
                $code = Str::slug($this->name) . '-' . random_int(1000, 9999);
            }
            $this->merge(['code' => $code]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('service_type') ? $this->route('service_type')->id : null;

        $rules = [
            'name' => ['required', 'string', 'max:255', Rule::unique('service_types', 'name')->ignore($id)->whereNull('deleted_at')],
            'service_group_id' => ['required', 'exists:service_groups,id'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['required', 'numeric'],
        ];

        if ($this->isMethod('post')) {
            $rules['code'] = ['required', 'string', 'max:255', 'unique:service_types,code'];
        }

        return $rules;
    }
}
