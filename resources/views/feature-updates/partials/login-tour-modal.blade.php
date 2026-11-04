@if(isset($pendingFeatureUpdates) && $pendingFeatureUpdates->isNotEmpty())

<div
    class="modal fixed inset-0 z-[70] items-center justify-center overflow-y-auto flex"
    data-feature-tour-modal
>
    {{-- Backdrop --}}
    <div
        class="fixed inset-0 bg-gray-500/70 dark:bg-bgray-900/70"
        data-feature-tour-close
    ></div>

    <div class="relative flex min-h-full items-center justify-center p-4 sm:p-6">

        <div class="relative z-10 flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-[8px] bg-white shadow-2xl dark:bg-darkblack-600">

            {{-- Header --}}
            <div class="flex flex-shrink-0 items-center justify-between border-b border-bgray-200 px-5 py-4 dark:border-darkblack-400 sm:px-6">

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-success-300">
                        What's New in PMS
                    </p>

                    <h3 class="mt-1 text-lg font-semibold text-bgray-900 dark:text-white">
                        Discover the latest updates
                    </h3>
                </div>

                <button
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-transparent bg-bgray-100 text-bgray-700 transition duration-200 hover:border-red-200 hover:bg-red-50 hover:text-red-500 dark:bg-darkblack-500 dark:text-bgray-300 dark:hover:border-red-900/40 dark:hover:bg-darkblack-400 dark:hover:text-red-300"
                    data-feature-tour-close
                >
                    ✕
                </button>

            </div>

            {{-- Scrollable Content --}}
            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">

                @foreach($pendingFeatureUpdates as $index => $feature)

                    <div
                        class="feature-tour-slide {{ $index === 0 ? '' : 'hidden' }}"
                        data-index="{{ $index }}"
                        data-feature-id="{{ $feature->id }}"
                    >

                        {{-- Feature Type --}}
                        <div class="mb-4 flex flex-wrap items-center gap-2">

                            <span class="rounded-md bg-success-50 px-3 py-1 text-xs font-semibold text-success-300 dark:bg-darkblack-500">
                                {{ ucwords(str_replace('_', ' ', $feature->feature_type)) }}
                            </span>

                            @if($feature->module_name)
                                <span class="text-sm text-bgray-500 dark:text-bgray-300">
                                    {{ $feature->module_name }}
                                </span>
                            @endif

                        </div>

                        {{-- Title --}}
                        <h2 class="mb-3 text-xl font-semibold text-bgray-900 dark:text-white sm:text-2xl">
                            {{ $feature->title }}
                        </h2>

                        {{-- Cover Image --}}
                        @if($feature->cover_image)
                            <div class="mb-5 overflow-hidden rounded-lg border border-bgray-200 dark:border-darkblack-400">
                                <img
                                    src="{{ asset('storage/' . $feature->cover_image) }}"
                                    alt="{{ $feature->title }}"
                                    class="max-h-[280px] w-full object-contain"
                                >
                            </div>
                        @endif

                        {{-- Short Description --}}
                        @if($feature->short_description)
                            <p class="mb-4 text-sm leading-6 text-bgray-700 dark:text-bgray-300">
                                {{ $feature->short_description }}
                            </p>
                        @endif

                        {{-- Description --}}
                        @if($feature->description)
                            <div class="mb-5 whitespace-pre-line text-sm leading-6 text-bgray-700 dark:text-bgray-300">
                                {{ $feature->description }}
                            </div>
                        @endif

                        {{-- Instructions --}}
                        @if($feature->instructions)
                            <div class="mb-5 rounded-lg border border-bgray-200 bg-bgray-50 p-4 dark:border-darkblack-400 dark:bg-darkblack-500">

                                <h4 class="mb-2 text-sm font-semibold text-bgray-900 dark:text-white">
                                    How to use
                                </h4>

                                <div class="whitespace-pre-line text-sm leading-6 text-bgray-700 dark:text-bgray-300">
                                    {{ $feature->instructions }}
                                </div>

                            </div>
                        @endif

                        {{-- Screenshots --}}
                        @if($feature->screenshots)
                            <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                @foreach($feature->screenshots as $screenshot)
                                    <div class="overflow-hidden rounded-lg border border-bgray-200 dark:border-darkblack-400">
                                        <img
                                            src="{{ asset('storage/' . $screenshot) }}"
                                            alt="Feature screenshot"
                                            class="w-full object-contain"
                                        >
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Video --}}
                        @if($feature->video_url)
                            <a
                                href="{{ $feature->video_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-2 text-sm font-semibold text-success-300 transition hover:text-success-400"
                            >
                                Watch feature video
                            </a>
                        @endif

                    </div>

                @endforeach

            </div>

            {{-- Footer --}}
            <div class="flex flex-shrink-0 flex-wrap items-center justify-between gap-3 border-t border-bgray-200 px-5 py-4 dark:border-darkblack-400 sm:px-6">

                <div>
                    <p
                        class="text-xs text-bgray-500 dark:text-bgray-400"
                        data-feature-tour-counter
                    >
                        1 of {{ $pendingFeatureUpdates->count() }}
                    </p>

                    <button
                        type="button"
                        class="mt-1 text-sm font-medium text-bgray-600 transition hover:text-bgray-900 dark:text-bgray-300 dark:hover:text-white"
                        data-feature-tour-skip
                    >
                        Skip for now
                    </button>
                </div>

                <div class="flex items-center gap-2">

                    <button
                        type="button"
                        class="hidden rounded-lg border border-bgray-200 bg-white px-4 py-2 text-sm font-medium text-bgray-700 transition hover:border-bgray-300 hover:text-bgray-900 dark:border-darkblack-400 dark:bg-darkblack-500 dark:text-bgray-300"
                        data-feature-tour-previous
                    >
                        Previous
                    </button>

                    <button
                        type="button"
                        class="rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400"
                        data-feature-tour-next
                    >
                        Next
                    </button>

                    <button
                        type="button"
                        class="hidden rounded-lg bg-success-300 px-4 py-2 text-sm font-semibold text-white transition hover:bg-success-400 disabled:cursor-not-allowed disabled:opacity-50"
                        data-feature-tour-got-it
                    >
                        Got it
                    </button>

                </div>

            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const modal = document.querySelector('[data-feature-tour-modal]');

    if (!modal) return;

    const slides = Array.from(
        modal.querySelectorAll('.feature-tour-slide')
    );

    if (!slides.length) return;

    let currentIndex = 0;

    const previousButton = modal.querySelector('[data-feature-tour-previous]');
    const nextButton = modal.querySelector('[data-feature-tour-next]');
    const gotItButton = modal.querySelector('[data-feature-tour-got-it]');
    const skipButton = modal.querySelector('[data-feature-tour-skip]');
    const closeButtons = modal.querySelectorAll('[data-feature-tour-close]');
    const counter = modal.querySelector('[data-feature-tour-counter]');

    function showSlide(index) {

        currentIndex = index;

        slides.forEach((slide, i) => {
            slide.classList.toggle('hidden', i !== index);
        });

        counter.textContent = `${index + 1} of ${slides.length}`;

        previousButton.classList.toggle('hidden', index === 0);
        nextButton.classList.toggle('hidden', index === slides.length - 1);
        gotItButton.classList.toggle('hidden', index !== slides.length - 1);
    }

    function closeTour() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    nextButton.addEventListener('click', function () {
        if (currentIndex < slides.length - 1) {
            showSlide(currentIndex + 1);
        }
    });

    previousButton.addEventListener('click', function () {
        if (currentIndex > 0) {
            showSlide(currentIndex - 1);
        }
    });

    async function acknowledgeCurrentFeature() {

        const featureId = slides[currentIndex].dataset.featureId;

        gotItButton.disabled = true;

        try {

            const response = await fetch(
                `/feature-updates/${featureId}/acknowledge`,
                {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({})
                }
            );

            if (!response.ok) {
                throw new Error('Unable to acknowledge feature.');
            }

            closeTour();

        } catch (error) {
            alert('Unable to save your acknowledgement. Please try again.');
        } finally {
            gotItButton.disabled = false;
        }
    }

    gotItButton.addEventListener('click', acknowledgeCurrentFeature);

    skipButton.addEventListener('click', closeTour);

    closeButtons.forEach(button => {
        button.addEventListener('click', closeTour);
    });

    showSlide(0);

    modal.classList.remove('hidden');
    modal.classList.add('flex');

});
</script>

@endif