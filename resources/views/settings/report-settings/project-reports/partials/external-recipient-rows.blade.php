@forelse ($externalRecipients as $idx => $ext)
    @include('settings.report-settings.project-reports.partials.external-recipient-row', ['idx' => $idx, 'ext' => $ext])
@empty
@endforelse

