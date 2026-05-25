@php
    $isDeletedProjectView = $project->trashed();
    $showUpload = ($showUpload ?? true) && ! $isDeletedProjectView;
    $showDelete = ($showDelete ?? true) && ! $isDeletedProjectView;
    $heading = $heading ?? 'Scope Files';
@endphp

<h3 class="text-lg font-bold text-bgray-900 dark:text-white">{{ $heading }}</h3>

<!-- Upload Box -->
@if ($showUpload && auth()->user()->can('project.add_scope'))
    <div id="file-upload-box" class="border-2 border-dashed border-bgray-300 rounded-xl p-6 mt-4 text-center cursor-pointer hover:border-success-300 transition">

        <p class="text-bgray-700">Attach your project scope files <span class="text-success-300">click to upload</span></p>
        <input type="file" id="file-input" multiple class="hidden" accept=".pdf,.xls,.xlsx,.doc,.docx,.jpg,.jpeg,.png">
    </div>
    <p class="text-error-300 text-sm mt-2">Files includes pdf, xls, xlsx, doc, docx, jpg, jpeg, png and max file size is 5MB</p>
@endif

<!-- File Preview List -->
<div id="file-list" class="flex flex-wrap gap-4 mt-6">
    @forelse ($project->scopeFiles as $file)
        @include('projects.partials.file-item', ['file' => $file, 'showDelete' => $showDelete])
    @empty
        <p id="file-empty-state" class="text-gray-400 text-sm">No scope files uploaded yet.</p>
    @endforelse
</div>
