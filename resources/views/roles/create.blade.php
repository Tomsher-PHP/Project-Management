@extends('layouts.master')

@section('page-content')
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[156px] xl:px-[48px] xl:pb-[48px]">

        <!-- write your code here-->
        <div class="2xl:flex 2xl:space-x-[48px]">
            <section class="mb-6 2xl:mb-0 2xl:flex-1">
                <div class="w-full rounded-lg bg-white px-[24px] py-[20px] dark:bg-darkblack-600">
                    <div class="flex grid-cols-12 flex-col-reverse gap-12 xl:grid 2xl:flex-row">
                        <div class="xl:col-span-7 2xl:col-span-8">
                            <h3 class="border-b border-bgray-200 pb-5 text-2xl font-bold text-bgray-900 dark:border-darkblack-400 dark:text-white">
                                Create New Role
                            </h3>
                            <div class="mt-8">
                                @include('roles._form', ['userTypes' => $userTypes])
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <!-- write your code here-->
    </main>
@endsection
@push('scripts')
    <script>
        $(document).ready(function() {

            function loadPermissions(userType) {

                if (!userType) {
                    $('#permission-container').html(
                        '<span class="text-muted">Select user type to load permissions.</span>');
                    return;
                }

                $.ajax({
                    url: "{{ route('roles.permissions.byUserType') }}",
                    type: "POST",
                    data: {
                        user_type: userType,
                        role_id: $('input[name="role_id"]').val(), // Pass role ID for edit case
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(data) {
                        $('#permission-container').html(data);
                    }
                });
            }

            // On change
            $('#user_type').change(function() {
                loadPermissions($(this).val());
            });

            // Load default on page load
            @if (isset($role))
                loadPermissions($('#user_type').val());
            @else
                loadPermissions($('#user_type').val('normal_user').trigger('change'));
            @endif

        });
    </script>
@endpush
