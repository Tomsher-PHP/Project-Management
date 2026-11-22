<?php

namespace App\Http\Requests;

use App\Models\DailyWorkReportSetting;
use App\Models\ProjectStatus;
use App\Services\Reports\ProjectReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectReportSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('report_settings.manage') ?? false;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $settings = $this->input('settings', []);

        if ($this->has('project_status_ids') && !isset($settings['filters']['project_status_ids'])) {
            $settings['filters']['project_status_ids'] = $this->input('project_status_ids');
        }

        if ($this->has('date_filter') && !isset($settings['filters']['date_filter'])) {
            $settings['filters']['date_filter'] = $this->input('date_filter');
        }

        if ($this->has('columns') && !isset($settings['columns'])) {
            $settings['columns'] = $this->input('columns');
        }

        if ($this->has('monthly_day') && !isset($settings['schedule']['monthly_day'])) {
            $settings['schedule']['monthly_day'] = $this->input('monthly_day');
        }

        if (!empty($settings)) {
            $this->merge(['settings' => $settings]);
        }

        if ($this->has('frequency')) {
            $this->merge(['frequency' => strtolower((string) $this->input('frequency'))]);
        }

        // Backwards compatibility with legacy /settings update form
        if ($this->is('*/settings') && !$this->has('report_name')) {
            $currentSetting = DailyWorkReportSetting::where('report_key', 'project_report')->find($this->input('id', 2));
            $this->merge([
                'report_name' => $currentSetting?->report_name ?: 'Project Report',
                'reporting_period' => $this->input('reporting_period', $currentSetting?->reporting_period ?: 'all_time'),
            ]);

            if (empty($settings['filters']['project_status_ids'])) {
                $statusIds = $currentSetting?->settings['filters']['project_status_ids'] ?? [];
                if (empty($statusIds)) {
                    $firstStatusId = ProjectStatus::value('id');
                    $statusIds = $firstStatusId ? [$firstStatusId] : [1];
                }
                $settings['filters']['project_status_ids'] = $statusIds;
                $settings['filters']['date_filter'] = $currentSetting?->settings['filters']['date_filter'] ?? 'created_at';
                $settings['columns'] = $currentSetting?->settings['columns'] ?? ['project_name', 'status'];
                $this->merge(['settings' => $settings]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $frequency = strtolower((string) $this->input('frequency'));
        $projectReportService = app(ProjectReportService::class);
        $availableColumns = array_keys($projectReportService->getColumnLabels());

        return [
            'report_name' => ['required', 'string', 'max:255'],
            'enabled' => ['sometimes', 'boolean'],
            'frequency' => ['required', 'string', Rule::in(['daily', 'weekly', 'monthly'])],
            'delivery_time' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    try {
                        Carbon::parse($value);
                    } catch (\Throwable $e) {
                        $fail('The delivery time is invalid.');
                    }
                },
            ],
            'timezone' => ['required', 'string', 'timezone'],
            'reporting_period' => [
                'required',
                'string',
                Rule::in([
                    'all_time',
                    'previous_day',
                    'last_7_days',
                    'last_30_days',
                    'last_90_days',
                    'last_6_months',
                    'last_1_year',
                ]),
            ],
            'weekly_day' => [
                'required_if:frequency,weekly',
                'nullable',
                'integer',
                'between:1,7',
            ],
            'settings' => ['required', 'array'],
            'settings.schedule' => ['sometimes', 'array'],
            'settings.schedule.monthly_day' => [
                'required_if:frequency,monthly',
                'nullable',
                function ($attribute, $value, $fail) use ($frequency) {
                    if ($frequency !== 'monthly') {
                        return;
                    }
                    if ($value === 'last' || (is_numeric($value) && (int) $value >= 1 && (int) $value <= 28)) {
                        return;
                    }
                    $fail('The monthly day must be between 1 and 28 or "last".');
                },
            ],
            'settings.filters' => ['required', 'array'],
            'settings.filters.project_status_ids' => ['required', 'array', 'min:1'],
            'settings.filters.project_status_ids.*' => [
                'required',
                'integer',
                Rule::exists('project_statuses', 'id')->whereNull('deleted_at'),
            ],
            'settings.filters.date_filter' => [
                'required',
                'string',
                Rule::in(['created_at', 'start_date', 'end_date']),
            ],
            'settings.columns' => ['required', 'array', 'min:1'],
            'settings.columns.*' => [
                'required',
                'string',
                Rule::in($availableColumns),
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'settings.filters.project_status_ids.required' => 'At least one project status must be selected.',
            'settings.filters.project_status_ids.min' => 'At least one project status must be selected.',
            'settings.filters.project_status_ids.*.exists' => 'One or more selected project statuses are invalid.',
            'settings.filters.date_filter.required' => 'The date filter is required.',
            'settings.filters.date_filter.in' => 'The selected date filter is invalid.',
            'settings.columns.required' => 'At least one column must be selected.',
            'settings.columns.min' => 'At least one column must be selected.',
            'settings.columns.*.in' => 'One or more selected columns are invalid or not permitted.',
        ];
    }
}

