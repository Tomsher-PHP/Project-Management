@extends('layouts.master')

@section('page-content')
    <!-- Page starts -->
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[156px] xl:px-[48px] xl:pb-[48px]">

        <a href="javascript:void(0)" data-target="#multi-step-modal" class="modal-open inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-success-300 text-sm font-semibold text-white hover:bg-success-400 transition duration-200 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>

            <span>New Designations</span>
        </a>

        <!-- write your code here-->
        <div class="2xl:flex 2xl:space-x-[48px]">
            <section class="mb-6 2xl:mb-0 2xl:flex-1">
                <!--list table-->
                <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                    <div class="flex flex-col space-y-5">

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
                                @php
                                    $startNumber = ($designations->currentPage() - 1) * $designations->perPage();
                                @endphp
                                @forelse ($designations as $key => $designation)
                                    <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                        <td class="px-6 py-5 xl:px-0">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">{{ $startNumber + $loop->iteration }}</span>
                                        </td>
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex w-full items-center space-x-2.5">
                                                @if ($designation->default == 1)
                                                    <span>
                                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M12.0001 17.75L5.82808 20.995L7.00708 14.122L2.00708 9.25495L8.90708 8.25495L11.9931 2.00195L15.0791 8.25495L21.9791 9.25495L16.9791 14.122L18.1581 20.995L12.0001 17.75Z" fill="#F6A723" stroke="#F6A723" stroke-linecap="round" stroke-linejoin="round"></path>
                                                        </svg>
                                                    </span>
                                                @endif
                                                <p class="text-base font-semibold text-bgray-900 dark:text-white">
                                                    {{ $designation->name }}
                                                </p>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex w-full items-center">
                                                <span class="block rounded-md bg-success-50 px-4 py-1.5 text-sm font-semibold leading-[22px] text-success-400 dark:bg-darkblack-500 dark:text-bgray-50">{{ $designation->order }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex w-full items-center">
                                                <x-status-toggle :model="$designation" route="settings.designation.toggleStatus" entity="designation" />
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex w-full items-center space-x-2">
                                                <a href="javascript:void(0)" class="edit-designation modal-open inline-flex items-center justify-center w-8 h-8rounded-lg bg-gray-100 dark:bg-darkblack-500hover:bg-gray-200 dark:hover:bg-darkblack-400transition duration-200 group" data-id="{{ $designation->id }}" data-name="{{ $designation->name }}" data-order="{{ $designation->order }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-600 group-hover:text-indigo-600 transition" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M17.414 2.586a2 2 0 010 2.828l-9.193 9.193a1 1 0 01-.464.263l-4 1a1 1 0 01-1.213-1.213l1-4a1 1 0 01.263-.464l9.193-9.193a2 2 0 012.828 0z" />
                                                    </svg>
                                                </a>
                                                @if (!$designation->default)
                                                    <x-delete-form :action="route('settings.designations.destroy', $designation->id)" />
                                                @endif

                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-sm text-gray-500 dark:text-gray-200">
                                            No designations found.
                                        </td>
                                    </tr>
                                @endforelse
                            </table>
                        </div>
                        <x-pagination :paginator="$designations" :per-page="$perPage" />
                    </div>
                </div>
            </section>
        </div>
        <!-- write your code here-->
    </main>
    <!-- Page ends -->

    {{-- Modal content start --}}
    @include('settings.designations.create-update-modal')
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {

            // Setup CSRF for Ajax
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            const modal = document.getElementById('multi-step-modal');
            const form = document.getElementById('designationForm');
            const title = document.getElementById('modalTitle');
            const methodInput = document.getElementById('formMethod');
            const submitBtn = document.getElementById('submitBtn');

            const nameInput = form.querySelector('input[name="name"]');
            const orderInput = form.querySelector('input[name="order"]');

            // OPEN EDIT
            document.querySelectorAll('.edit-designation').forEach(button => {
                button.addEventListener('click', function() {

                    const id = this.dataset.id;
                    const name = this.dataset.name;
                    const order = this.dataset.order;

                    // Change title and button text
                    title.innerText = 'Edit designation';
                    submitBtn.innerText = 'Update designation';

                    // Fill inputs
                    nameInput.value = name;
                    orderInput.value = order;

                    // Change form action
                    form.action = `/settings/designations/${id}`;

                    // Change method to PUT
                    methodInput.value = 'PUT';

                    // Show modal
                    modal.classList.remove('hidden');

                    clearFormErrors();
                });
            });

            // RESET WHEN CLOSING
            document.getElementById('step-1-cancel').addEventListener('click', function() {
                resetModal()
            });

            // Store and update
            $('#designationForm').on('submit', function(e) {
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

                            Alert.success(response.message); // You can replace with toast

                            $('#multi-step-modal').addClass('hidden');
                            form[0].reset();

                            location.reload(); // reload table (simple way)
                        }
                    },
                    error: function(xhr) {
                        // Remove old errors ONLY inside this form
                        clearFormErrors();

                        if (xhr.status === 422) {

                            let errors = xhr.responseJSON.errors;

                            $.each(errors, function(key, value) {

                                let input = $('[name="' + key + '"]');
                                input.addClass('border-red-500');

                                input.after(
                                    '<span class="mt-2 text-sm text-error-300 error-text">' + value[0] + '</span>'
                                );
                            });
                        }
                    }
                });
            });

            function resetModal() {
                form.reset();
                title.innerText = 'Add designation';
                submitBtn.innerText = 'Create designation';
                form.action = "{{ route('settings.designations.store') }}";
                methodInput.value = 'POST';
                modal.classList.add('hidden');
            }

            function clearFormErrors() {
                let form = $('#designationForm');
                form.find('.error-text').remove();
                form.find('input').removeClass('border-red-500');
            }

        });
    </script>
@endpush
