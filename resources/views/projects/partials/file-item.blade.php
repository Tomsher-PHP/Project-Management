<div class="file-item flex h-24 w-24 flex-col items-center lg:h-44 lg:w-44">

    <!-- File Icon -->
    <div class="flex w-full justify-center">
        @php
            $ext = strtolower(pathinfo($file->original_name, PATHINFO_EXTENSION));
        @endphp

        @if (in_array($ext, ['jpg', 'jpeg', 'png']))
            <img src="{{ asset('storage/' . $file->file_path) }}" class="h-16 w-16 object-cover rounded" />
        @else
            <svg width="47" height="66" viewBox="0 0 67 86" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M5.4032 0H46.9892L67 19.8123V80.625C67 83.5946 64.5796 86 61.5968 86H5.4032C2.42052 86 0 83.5946 0 80.625V5.37496C0 2.40536 2.4208 0 5.4032 0Z" fill="white" />
                <path d="M5.4032 0.5H46.7835L66.5 20.0208V80.625C66.5 83.3158 64.306 85.5 61.5968 85.5H5.4032C2.69405 85.5 0.5 83.3158 0.5 80.625V5.37496C0.5 2.68413 2.6943 0.5 5.4032 0.5Z" stroke="#E8E9EB" />
                <path d="M65.9198 20.4252H51.2864C48.6265 20.4252 46.468 18.2802 46.468 15.6368V1.0752" stroke="#E8E9EB" />
                <path d="M34.7022 51.4466V48.8359H37.3266V46.2252H34.7022V43.6145H37.3266V41.0038H34.7022V38.3931H37.3266V35.7823H34.7022V33.1716H37.3266V30.5609H34.7022V27.9502H32.0777V30.5609H29.4533V33.1716H32.0777V35.7823H29.4533V38.3931H32.0777V41.0038H29.4533V43.6145H32.0777V46.2252H29.4533V48.8359H32.0777V51.4466H26.8289V57.9734C26.8289 61.5723 29.7722 64.5002 33.3899 64.5002C37.0077 64.5002 39.951 61.5723 39.951 57.9734V51.4466H34.7022ZM37.3266 57.9734C37.3266 60.1325 35.5603 61.8895 33.3899 61.8895C31.2195 61.8895 29.4533 60.1325 29.4533 57.9734V54.0573H37.3266V57.9734Z"
                    fill="#8A9099" />
                <path d="M32.0778 59.2787H34.7023C35.4266 59.2787 36.0145 58.6952 36.0145 57.9733C36.0145 57.2515 35.4266 56.668 34.7023 56.668H32.0778C31.3535 56.668 30.7656 57.2515 30.7656 57.9733C30.7656 58.6952 31.3535 59.2787 32.0778 59.2787Z" fill="#8A9099" />
            </svg>
        @endif
    </div>

    <!-- File Name -->
    <h4 class="mt-2 text-bgray-600 text-sm font-semibold dark:text-white md:text-base truncate w-full text-center">
        {{ $file->original_name }}
    </h4>

    <!-- File Size -->
    <span class="text-xs text-bgray-500">
        {{ number_format($file->file_size / 1024, 1) }} KB
    </span>

    <!-- Actions -->
    <div class="flex gap-2 mt-1">
        <!-- Download -->
        <a href="{{ asset('storage/' . $file->file_path) }}" target="_blank" class="text-xs text-blue-500 hover:underline">
            View
        </a>

        <!-- Delete -->
        @can('project.remove_files')
            <button type="button" class="text-xs text-red-500 delete-file" data-id="{{ $file->id }}">
                Delete
            </button>
        @endcan
    </div>

</div>
