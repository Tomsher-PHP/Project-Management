<div class="table-content w-full overflow-x-auto">
    <table class="w-full">
        <tr class="border-b border-bgray-300 dark:border-darkblack-400">
            <td class="">
                <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">#</span>
            </td>
            <td class="inline-block w-[250px] px-6 py-5 lg:w-auto xl:px-0">
                <div class="flex w-full items-center space-x-2.5">
                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">
                        Name
                    </span>
                </div>
            </td>
            <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                <div class="flex w-full items-center space-x-2.5">
                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Order</span>
                </div>
            </td>
            <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                <div class="flex w-full items-center space-x-2.5">
                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Status</span>
                </div>
            </td>
            <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                <div class="flex w-full items-center space-x-2.5">
                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span>
                </div>
            </td>
        </tr>
        @forelse ($departments as $key => $department)
            <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                <td class="px-6 py-5 xl:px-0">
                    <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">{{ $key + 1 }}</span>
                </td>
                <td class="px-6 py-5 xl:px-0">
                    <div class="flex w-full items-center space-x-2.5">
                        @if ($department->default == 1)
                            <span>
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12.0001 17.75L5.82808 20.995L7.00708 14.122L2.00708 9.25495L8.90708 8.25495L11.9931 2.00195L15.0791 8.25495L21.9791 9.25495L16.9791 14.122L18.1581 20.995L12.0001 17.75Z" fill="#F6A723" stroke="#F6A723" stroke-linecap="round" stroke-linejoin="round"></path>
                                </svg>
                            </span>
                        @endif
                        <p class="text-base font-semibold text-bgray-900 dark:text-white">
                            {{ $department->name }}
                        </p>
                    </div>
                </td>
                <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                    <div class="flex w-full items-center">
                        <span class="block rounded-md bg-success-50 px-4 py-1.5 text-sm font-semibold leading-[22px] text-success-400 dark:bg-darkblack-500 dark:text-bgray-50">{{ $department->order }}</span>
                    </div>
                </td>
                <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                    <div class="flex w-full items-center">

                        <button type="button" data-id="{{ $department->id }}" class="switch-btn relative inline-flex h-5 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none
                            {{ $department->status ? 'bg-green-600 active' : 'bg-gray-200' }}" role="switch" aria-checked="{{ $department->status ? 'true' : 'false' }}" @unlesscanType('role.edit') disabled @endcanType>

                            <span aria-hidden="true" class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out
                                {{ $department->status ? 'translate-x-5' : 'translate-x-0' }}">
                            </span>

                        </button>

                    </div>
                </td>
                <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                    <div class="flex w-full items-center space-x-2">
                        <a href="javascript:void(0)" class="edit-department modal-open inline-flex items-center justify-center w-8 h-8rounded-lg bg-gray-100 dark:bg-darkblack-500hover:bg-gray-200 dark:hover:bg-darkblack-400transition duration-200 group" data-id="{{ $department->id }}" data-name="{{ $department->name }}" data-order="{{ $department->order }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-600 group-hover:text-indigo-600 transition" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M17.414 2.586a2 2 0 010 2.828l-9.193 9.193a1 1 0 01-.464.263l-4 1a1 1 0 01-1.213-1.213l1-4a1 1 0 01.263-.464l9.193-9.193a2 2 0 012.828 0z" />
                            </svg>
                        </a>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center py-4 text-sm text-gray-500 dark:text-gray-200">
                    No departments found.
                </td>
            </tr>
        @endforelse
    </table>
</div>
<x-pagination :paginator="$departments" :per-page="$perPage" />

@push('scripts')
    <script>
        $(document).ready(function() {

            // Setup CSRF for Ajax
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $(document).on('click', '.switch-btn', function() {
                let btn = $(this);

                // Prevent multiple clicks while processing
                if (btn.data('processing')) return;
                btn.data('processing', true);

                let id = btn.data('id');
                let isActive = btn.attr('aria-checked') === 'true';
                let actionText = isActive ? 'deactivate' : 'activate';

                if (!confirm(`Are you sure you want to ${actionText} this department?`)) {
                    btn.data('processing', false);
                    return;
                }

                $.ajax({
                    url: '/settings/department/toggle-status',
                    type: 'PATCH',
                    data: {
                        // _token: $('meta[name="csrf-token"]').attr('content'),
                        id: id
                    },
                    success: function(response) {

                        if (response.success) {

                            let newStatus = response.status == 1;

                            // Update switch UI
                            btn.attr('aria-checked', newStatus);

                            btn.toggleClass('bg-green-600', newStatus);
                            btn.toggleClass('bg-gray-200', !newStatus);

                            btn.find('span').toggleClass('translate-x-5', newStatus);
                            btn.find('span').toggleClass('translate-x-0', !newStatus);

                            // Update badge
                            let badge = btn.closest('tr').find('.status-badge');

                            if (newStatus) {
                                badge.removeClass('bg-secondary')
                                    .addClass('bg-success')
                                    .text('Active');
                            } else {
                                badge.removeClass('bg-success')
                                    .addClass('bg-secondary')
                                    .text('Inactive');
                            }

                        } else {
                            alert('Status update failed.');
                        }

                    },
                    error: function() {
                        alert('Something went wrong.');
                    },
                    complete: function() {
                        btn.data('processing', false);
                    }
                });

            });

            const modal = document.getElementById('multi-step-modal');
            const form = document.getElementById('departmentForm');
            const title = document.getElementById('modalTitle');
            const methodInput = document.getElementById('formMethod');

            const nameInput = form.querySelector('input[name="name"]');
            const orderInput = form.querySelector('input[name="order"]');

            // OPEN EDIT
            document.querySelectorAll('.edit-department').forEach(button => {
                button.addEventListener('click', function() {

                    const id = this.dataset.id;
                    const name = this.dataset.name;
                    const order = this.dataset.order;

                    // Change title
                    title.innerText = 'Edit Department';

                    // Fill inputs
                    nameInput.value = name;
                    orderInput.value = order;

                    // Change form action
                    form.action = `/settings/department/${id}`;

                    // Change method to PUT
                    methodInput.value = 'PUT';

                    // Show modal
                    modal.classList.remove('hidden');
                });
            });

            // RESET WHEN CLOSING
            document.getElementById('step-1-cancel').addEventListener('click', function() {

                form.reset();
                title.innerText = 'Add Department';
                form.action = "{{ route('settings.department.store') }}";
                methodInput.value = 'POST';
                modal.classList.add('hidden');
            });

            // Store and update
            $('#departmentForm').on('submit', function(e) {
                e.preventDefault();

                let form = $(this);
                let url = form.attr('action');
                let method = $('#formMethod').val();
                let formData = form.serialize();

                $('.error-text').remove();
                $('input').removeClass('border-red-500');

                $.ajax({
                    url: url,
                    type: method === 'PUT' ? 'POST' : 'POST',
                    data: formData,
                    success: function(response) {

                        if (response.status) {

                            alert(response.message); // You can replace with toast

                            $('#multi-step-modal').addClass('hidden');
                            form[0].reset();

                            location.reload(); // reload table (simple way)
                        }
                    },
                    error: function(xhr) {

                        if (xhr.status === 422) {

                            let errors = xhr.responseJSON.errors;

                            $.each(errors, function(key, value) {

                                let input = $('[name="' + key + '"]');
                                input.addClass('border-red-500');

                                input.after(
                                    '<span class="error-text text-red-500 text-sm">' + value[0] + '</span>'
                                );
                            });
                        }
                    }
                });
            });

        });
    </script>
@endpush
