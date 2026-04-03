@extends('layouts.master')

@section('page-content')
    <!-- Page starts -->
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px] xl:pb-[48px]">
        <div class="mb-6 flex flex-wrap items-center gap-3">

        @can('team.create')
            <a href="{{ route('teams.create') }}" class="inline-flex items-center px-4 py-1.5
               rounded-md bg-success-300
               text-sm font-semibold text-white
               hover:bg-success-400
               transition duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>

                <span>New Team</span>
            </a>
        @endcan

        <x-filters.button />
        </div>

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
                                            <x-sorting.sortable-column column="name" label="Name" />
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Members</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Is Active</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                        <div class="flex w-full items-center space-x-2.5">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">Actions</span>
                                        </div>
                                    </td>
                                </tr>
                                @php
                                    $startNumber = ($teams->currentPage() - 1) * $teams->perPage();
                                @endphp
                                @forelse ($teams as $key => $team)
                                    <tr class="border-b border-bgray-300 dark:border-darkblack-400">
                                        <td class="px-6 py-5 xl:px-0">
                                            <span class="text-base font-medium text-bgray-600 dark:text-bgray-50">{{ $startNumber + $loop->iteration }}</span>
                                        </td>
                                        <td class="px-6 py-5 xl:px-0">
                                            <div class="flex items-center gap-5">
                                                <div class="h-[64px] w-[64px]">
                                                    <img class="h-full w-full rounded-lg object-cover" src="{{ $team->team_avatar_url }}" alt="" />
                                                </div>
                                                <div class="flex-1">
                                                    <h4 class="text-lg font-bold text-bgray-900 dark:text-white">
                                                        {{ $team->name }}
                                                    </h4>
                                                    <div class="flex flex-col">
                                                        <span class="text-base font-medium text-bgray-700 dark:text-bgray-50">Owner: {{ $team->leader->first()->name ?? '' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex w-full items-center">
                                                <div class="mt-4 flex -space-x-2 overflow-hidden">
                                                    @if ($team->users->isNotEmpty())
                                                        @php
                                                            $members = $team->users;
                                                            $visibleMembers = $members->take(5);
                                                            $remainingCount = $members->count() - 5;
                                                        @endphp

                                                        @foreach ($visibleMembers as $member)
                                                            <img class="inline-block h-8 w-8 rounded-full ring ring-white" src="{{ $member->profile_image_url }}" alt="{{ $member->name }}" title="{{ $member->name }}" />
                                                        @endforeach

                                                        @if ($remainingCount > 0)
                                                            <div class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-white text-xs font-semibold text-gray-500 ring ring-white">
                                                                +{{ $remainingCount }}
                                                            </div>
                                                        @endif
                                                    @else
                                                        <span class="block rounded-md bg-bgray-50 px-4 py-1.5 text-sm font-semibold leading-[22px] text-bgray-700 dark:bg-darkblack-500 dark:text-bgray-50">--</span>
                                                    @endif

                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex w-full items-center">
                                                <x-status-toggle :model="$team" route="teams.toggleStatus" entity="team" permission="team.edit" />
                                            </div>
                                        </td>
                                        <td class="px-6 py-5 xl:w-[165px] xl:px-0">
                                            <div class="flex w-full items-center space-x-2">
                                                @can('team.edit')
                                                    <x-edit-button :action="route('teams.edit', $team->id)" />
                                                @endcan
                                                @can('team.delete')
                                                    <x-delete-form :action="route('teams.destroy', $team->id)" />
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-table-no-data col-span="5" message="No teams found." />
                                @endforelse
                            </table>
                        </div>
                        <x-pagination :paginator="$teams" :per-page="$perPage" />
                    </div>
                </div>
            </section>
        </div>
        <!-- write your code here-->
    </main>
    <!-- Page ends -->

    <!-- Filter drawer -->
    <x-filters.drawer>
        <x-filters.input-search name="search" label="Team Name" />
        <x-filters.multi-select name="user_id" label="Users" :options="$users" />
        <x-filters.select name="is_active" label="Is Active" :options="[
            1 => 'Active',
            0 => 'Inactive',
        ]" />
    </x-filters.drawer>
    <!-- Filter drawer end -->
@endsection
