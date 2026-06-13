<div class="profile-wrapper">
    <div onclick="profileAction()" class="profile-outside fixed -left-[43px] top-0 hidden h-full w-full">
    </div>
    <div style="filter: drop-shadow(12px 12px 40px rgba(0, 0, 0, 0.08));" class="profile-box absolute right-0 top-[68px] hidden w-[300px] overflow-hidden rounded-lg bg-white dark:bg-darkblack-600">
        <div class="relative w-full px-3 py-2">
            <div>
                <ul>
                    <li class="w-full">
                        <div class="rounded-lg px-[14px] py-4">
                            <div class="flex items-center space-x-3">
                                <x-user-avatar :user="$authUser" size="xlg" class="border border-bgray-300 dark:border-darkblack-400" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-bgray-900 dark:text-white">{{ $authUser->name }}</p>
                                    <div class="mt-0.5 flex items-center gap-2">
                                        <p class="truncate text-xs text-bgray-800 dark:text-bgray-300">{{ $authUser->email }}</p>
                                        <button type="button" class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-bgray-700 transition hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-300 dark:hover:bg-darkblack-500 dark:hover:text-white" onclick="copyProfileEmail(event, @js($authUser->email))" aria-label="Copy email" title="Copy email">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M8 7V6C8 4.89543 8.89543 4 10 4H18C19.1046 4 20 4.89543 20 6V14C20 15.1046 19.1046 16 18 16H17" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                                <path d="M6 8H14C15.1046 8 16 8.89543 16 10V18C16 19.1046 15.1046 20 14 20H6C4.89543 20 4 19.1046 4 18V10C4 8.89543 4.89543 8 6 8Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="mt-1 truncate text-xs font-medium uppercase tracking-wide text-success-400">{{ $userRoleName }} @if($authUser->is_super_admin) (Super Admin) @endif</p>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li class="w-full">
                        <a href="{{ route('users.show', auth()->id()) }}">
                            <div class="flex items-center space-x-[18px] rounded-lg p-[14px] text-bgray-600 hover:bg-bgray-100 hover:text-bgray-900 hover:dark:bg-darkblack-500">
                                <div class="w-[20px]">
                                    <span>
                                        <svg class="stroke-bgray-900 dark:stroke-bgray-50" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12.1197 12.7805C12.0497 12.7705 11.9597 12.7705 11.8797 12.7805C10.1197 12.7205 8.71973 11.2805 8.71973 9.51047C8.71973 7.70047 10.1797 6.23047 11.9997 6.23047C13.8097 6.23047 15.2797 7.70047 15.2797 9.51047C15.2697 11.2805 13.8797 12.7205 12.1197 12.7805Z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M18.7398 19.3796C16.9598 21.0096 14.5998 21.9996 11.9998 21.9996C9.39977 21.9996 7.03977 21.0096 5.25977 19.3796C5.35977 18.4396 5.95977 17.5196 7.02977 16.7996C9.76977 14.9796 14.2498 14.9796 16.9698 16.7996C18.0398 17.5196 18.6398 18.4396 18.7398 19.3796Z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                </div>
                                <div class="flex-1">
                                    <span class="text-sm font-semibold text-bgray-900 dark:text-white">My Profile</span>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li class="w-full">
                        <a href="{{ route('user.login.activity') }}">
                            <div class="flex items-center space-x-[18px] rounded-lg p-[14px] text-bgray-600 hover:bg-bgray-100 hover:text-bgray-900 hover:dark:bg-darkblack-500">
                                <div class="w-[20px]">
                                    <span>
                                        <svg class="stroke-bgray-900 dark:stroke-bgray-50" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 8V12L14.5 14.5" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M4.93 4.93C8.84 1.02 15.16 1.02 19.07 4.93C22.98 8.84 22.98 15.16 19.07 19.07C15.16 22.98 8.84 22.98 4.93 19.07C2.84 16.98 1.87 14.19 2.02 11.45" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M2 6V11.5H7.5" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                </div>
                                <div class="flex-1">
                                    <span class="text-sm font-semibold text-bgray-900 dark:text-white">Login Activity</span>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li class="w-full">

                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full text-left">
                                <div class="flex items-center space-x-[18px] rounded-lg p-[14px] text-success-300 hover:bg-gray-100 transition">

                                    <div class="w-[20px]">
                                        <span>
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M15 10L13.7071 11.2929C13.3166 11.6834 13.3166 12.3166 13.7071 12.7071L15 14M14 12L22 12M6 20C3.79086 20 2 18.2091 2 16V8C2 5.79086 3.79086 4 6 4M6 20C8.20914 20 10 18.2091 10 16V8C10 5.79086 8.20914 4 6 4M6 20H14C16.2091 20 18 18.2091 18 16M6 4H14C16.2091 4 18 5.79086 18 8" stroke="#22C55E" stroke-width="1.5" stroke-linecap="round" />
                                            </svg>
                                        </span>
                                    </div>

                                    <div class="flex-1">
                                        <span class="text-sm font-semibold">Log Out</span>
                                    </div>

                                </div>
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
            <div class="my-[14px] h-[1px] w-full bg-bgray-300"></div>
            <div>
                <ul>
                    <li class="w-full">
                        <a href="{{ route('settings.index') }}">
                            <div class="rounded-lg p-[14px] text-bgray-600 hover:bg-bgray-100 hover:text-bgray-900 dark:text-bgray-50 dark:hover:bg-darkblack-500">
                                <span class="text-sm font-semibold">Settings</span>
                            </div>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
