@php $url = route($route); @endphp
{{-- <button type="button" class="status-toggle switch-btn relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none
    {{ $model->status ? 'active' : '' }}" data-id="{{ $model->id }}" data-url="{{ $url }}" data-entity="{{ $entity }}" role="switch" aria-checked="{{ $model->status ? 'true' : 'false' }}">

    <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out
        {{ $model->status ? 'translate-x-5' : 'translate-x-0' }}">
    </span>

</button> --}}

<button type="button" class="status-toggle switch-btn {{ $model->status ? 'active' : '' }} relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent text-center transition-colors duration-200 ease-in-out focus:outline-none" data-id="{{ $model->id }}" data-url="{{ $url }}" data-entity="{{ $entity }}" role="switch" aria-checked="{{ $model->status ? 'true' : 'false' }}" aria-labelledby="availability-label" aria-describedby="availability-description">
    <!-- Enabled: "translate-x-5", Not Enabled: "translate-x-0" -->
    <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
</button>
