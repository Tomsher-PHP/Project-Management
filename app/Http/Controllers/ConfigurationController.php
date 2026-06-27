<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfigurationRequest;
use App\Models\Configuration;
use App\Models\CountryTimezones;
use App\Services\AttachmentService;
use Illuminate\Support\Facades\Cache;

class ConfigurationController extends Controller
{

    protected string $pageTitle;
    protected string $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Configuration Management';
        $this->subTitle = 'Manage configuration settings for the application';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function edit()
    {
        $config = $this->getConfiguration();
        $dateFormats = config('constants.date_formats');
        $timeFormats = config('constants.time_formats');
        $timezones = CountryTimezones::select('zone_name')
            ->distinct()
            ->orderBy('zone_name')
            ->get();

        if (! $timezones->contains(function ($timezone) {
            return strtoupper((string) $timezone->zone_name) === 'UTC';
        })) {
            $timezones->prepend((object) [
                'zone_name' => 'UTC',
            ]);
        }

        return view('settings.configurations.page', compact('config', 'dateFormats', 'timeFormats', 'timezones'));
    }

    public function update(ConfigurationRequest $request, AttachmentService $attachmentService)
    {
        $config = $this->getConfiguration();
        $oldTimezone = $config->timezone;

        $config->update($request->only([
            'company_name',
            'company_email',
            'website',
            'email_suffix',
            'company_phone',
            'company_address',
            'timezone',
            'date_format',
            'time_format',
        ]));

        // clear cache only if timezone changed
        if ($oldTimezone !== $config->timezone) {
            Cache::forget('company_timezone');
        }

        if ($request->hasFile('logo')) {
            $this->replaceLogo($config, $request->file('logo'), $attachmentService);
        } elseif ($request->boolean('remove_logo')) {
            $this->deleteLogo($config, $attachmentService);
        }

        return redirect()->back()->with('success', 'Configuration updated successfully!');
    }

    private function replaceLogo(Configuration $config, $file, AttachmentService $attachmentService): void
    {
        $this->deleteLogo($config, $attachmentService);

        $attachmentService->upload($file, 'configurations/logo', $config);
    }

    private function deleteLogo(Configuration $config, AttachmentService $attachmentService): void
    {
        $attachments = $config->attachments()
            ->where('file_path', 'like', 'configurations/logo/%')
            ->get();

        foreach ($attachments as $attachment) {
            $attachmentService->delete(collect([$attachment]));
        }
    }

    private function getConfiguration(): Configuration
    {
        return Configuration::firstOrCreate([]);
    }
}
