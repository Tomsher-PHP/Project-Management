<div class="modal hidden fixed inset-0 z-50 overflow-y-auto flex items-center justify-center" id="multi-step-modal">
    <div class="modal-overlay absolute inset-0 bg-gray-500 opacity-75 dark:bg-bgray-900 dark:opacity-50"></div>
    <div class="modal-content w-full max-w-lg mx-auto px-4">
        <div class="step-content step-1">
            <!-- My Content -->
            <div class="relative max-w-[492px] transform overflow-hidden rounded-lg bg-white dark:bg-darkblack-600 p-8 text-left transition-all">
                <div class="absolute top-0 right-0 pt-5 pr-5">
                    <button type="button" id="step-1-cancel" class="rounded-md bg-white dark:bg-darkblack-500 focus:outline-none">
                        <span class="sr-only">Close</span>
                        <!-- Cross Icon -->
                        <svg class="stroke-darkblack-300" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6 6L18 18M6 18L18 6L6 18Z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </div>
                <div>
                    <a href="signin.html" class="block mb-7">
                        <img src="{{ asset(config('assets.icons.logo')) }}" class="block dark:hidden" alt="" />
                        <img src="{{ asset(config('assets.icons.logo')) }}" class="hidden dark:block" alt="" />
                    </a>
                    <h3 class="text-2xl font-bold text-bgray-900 dark:text-white mb-3">
                        Reset your password
                    </h3>
                    <p class="text-base font-medium text-bgray-600 dark:text-darkblack-300 mb-7">
                        Enter the email address associated with your account and we'll
                        send you an otp.
                    </p>
                    <form action="{{ route('forgot.password') }}" method="POST" id="forgot-form">
                        <div class="mb-8">
                            <input type="text" name="email" class="rounded-lg bg-[#F5F5F5] dark:bg-darkblack-500 dark:text-white p-4 border-0 focus:border focus:ring-0 focus:border-success-300 w-full placeholder:font-medium text-base h-14" placeholder="Email" />
                        </div>
                        <a href="{{ route('login') }}" class="block text-sm font-bold text-success-300 mb-8 underline">Return to login</a>
                        <button type="button" id="step-1-next" class="flex w-full py-4 text-white bg-success-300 hover:bg-success-400 transition-all justify-center text-base font-medium rounded-lg">
                            Continue
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <!-- Step 2 -->
        <div class="step-content step-2 hidden">
            <div class="relative max-w-lg transform overflow-hidden rounded-lg bg-white dark:bg-darkblack-600 p-8 text-left transition-all">
                <div class="absolute top-0 right-0 pt-5 pr-5">
                    <button type="button" class="rounded-md bg-white dark:bg-darkblack-500 focus:outline-none" id="step-2-cancel">
                        <span class="sr-only">Close</span>
                        <!-- Cross Icon -->
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6 6L18 18M6 18L18 6L6 18Z" stroke="#747681" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </div>
                <div>
                    <a href="signin.html" class="block mb-7">
                        <img src="{{ asset(config('assets.icons.logo')) }}" class="block dark:hidden" alt="" />
                        <img src="{{ asset(config('assets.icons.logo')) }}" class="hidden dark:block" alt="" />
                    </a>
                    <h3 class="text-2xl font-bold text-bgray-900 dark:text-white mb-3">
                        Enter verification code
                    </h3>
                    <p class="text-base font-medium text-bgray-600 dark:text-darkblack-300 mb-7">
                        We have just sent a verification code to <span id="masked-email">mail id</span>
                    </p>
                    <form action="{{ route('verify.otp') }}" method="POST" id="otp-form">
                        @csrf

                        <input type="hidden" id="stored-email" name="email" value="">
                        <input type="hidden" name="otp" id="final-otp">

                        <div class="flex justify-center space-x-4 mb-8">
                            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-input w-14 h-14 text-center text-2xl font-bold rounded-xl bg-gray-100 focus:border-success-300 focus:ring-0">
                            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-input w-14 h-14 text-center text-2xl font-bold rounded-xl bg-gray-100 focus:border-success-300 focus:ring-0">
                            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-input w-14 h-14 text-center text-2xl font-bold rounded-xl bg-gray-100 focus:border-success-300 focus:ring-0">
                            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-input w-14 h-14 text-center text-2xl font-bold rounded-xl bg-gray-100 focus:border-success-300 focus:ring-0">
                            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-input w-14 h-14 text-center text-2xl font-bold rounded-xl bg-gray-100 focus:border-success-300 focus:ring-0">
                        </div>

                        <button type="button" id="step-2-next" class="flex w-full py-4 text-white bg-success-300 transition-all justify-center text-base font-medium rounded-lg">
                            Verify
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <!-- Step 3 -->
        <div class="step-content step-3 hidden">
            <!-- Step 3 Content Here -->
            <div class="relative  transform overflow-hidden rounded-lg bg-white dark:bg-darkblack-600 p-8 text-left transition-all">
                <div class="absolute top-0 right-0 pt-5 pr-5">
                    <button type="button" id="step-3-cancel" class="rounded-md bg-white dark:bg-darkblack-500 focus:outline-none">
                        <span class="sr-only">Close</span>
                        <!-- Cross Icon -->
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6 6L18 18M6 18L18 6L6 18Z" stroke="#747681" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </div>
                <div>
                    <a href="signin.html" class="block mb-7">
                        <img src="{{ asset(config('assets.icons.logo')) }}" class="block dark:hidden" alt="" />
                        <img src="{{ asset(config('assets.icons.logo')) }}" class="hidden dark:block" alt="" />
                    </a>
                    <h3 class="text-2xl font-bold text-bgray-900 dark:text-white mb-3">
                        Create new password
                    </h3>
                    <p class="text-base font-medium text-bgray-600 dark:text-darkblack-300 mb-7">
                        Please enter a new password. Your new password must be different
                        from previous password.
                    </p>
                    <form action="{{ route('reset.password') }}" method="POST" id="reset-password-form">
                        <div class="mb-6 relative">
                            <input type="text" id="reset-new-password" class="text-bgray-800 text-base border border-bgray-300 h-14 w-full focus:border focus:border-success-300 focus:ring-0 rounded-lg px-4 py-3.5 placeholder:text-bgray-500 placeholder:text-base dark:text-white dark:bg-darkblack-500 dark:border-0" placeholder="Password" />
                            <button class="absolute top-4 right-4 bottom-4">
                                <svg width="22" height="20" viewBox="0 0 22 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M2 1L20 19" stroke="#718096" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M9.58445 8.58704C9.20917 8.96205 8.99823 9.47079 8.99805 10.0013C8.99786 10.5319 9.20844 11.0408 9.58345 11.416C9.95847 11.7913 10.4672 12.0023 10.9977 12.0024C11.5283 12.0026 12.0372 11.7921 12.4125 11.417" stroke="#718096" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M8.363 3.36506C9.22042 3.11978 10.1082 2.9969 11 3.00006C15 3.00006 18.333 5.33306 21 10.0001C20.222 11.3611 19.388 12.5241 18.497 13.4881M16.357 15.3491C14.726 16.4491 12.942 17.0001 11 17.0001C7 17.0001 3.667 14.6671 1 10.0001C2.369 7.60506 3.913 5.82506 5.632 4.65906" stroke="#718096" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                        </div>
                        <div class="mb-8 relative">
                            <input type="text" id="reset-confirm-password" class="text-bgray-800 text-base border border-bgray-300 h-14 w-full focus:border-success-300 focus:ring-0 rounded-lg px-4 py-3.5 placeholder:text-bgray-500 placeholder:text-base dark:bg-darkblack-500 dark:border-0" placeholder="Confirm new Password" />
                            <button class="absolute top-4 right-4 bottom-4">
                                <svg width="22" height="20" viewBox="0 0 22 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M2 1L20 19" stroke="#718096" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M9.58445 8.58704C9.20917 8.96205 8.99823 9.47079 8.99805 10.0013C8.99786 10.5319 9.20844 11.0408 9.58345 11.416C9.95847 11.7913 10.4672 12.0023 10.9977 12.0024C11.5283 12.0026 12.0372 11.7921 12.4125 11.417" stroke="#718096" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M8.363 3.36506C9.22042 3.11978 10.1082 2.9969 11 3.00006C15 3.00006 18.333 5.33306 21 10.0001C20.222 11.3611 19.388 12.5241 18.497 13.4881M16.357 15.3491C14.726 16.4491 12.942 17.0001 11 17.0001C7 17.0001 3.667 14.6671 1 10.0001C2.369 7.60506 3.913 5.82506 5.632 4.65906" stroke="#718096" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                        </div>
                        <button type="button" id="step-3-next" class="flex w-full py-4 text-white bg-success-300 hover:bg-success-400 transition-all justify-center text-base font-medium rounded-lg">
                            Confirm Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <!-- Step 4 -->
        {{-- <div class="step-content step-4 hidden">
            <div class="relative  transform overflow-hidden rounded-lg bg-white dark:bg-darkblack-600 p-8 text-left transition-all">
                <div class="absolute top-0 right-0 pt-5 pr-5">
                    <button type="button" id="step-4-cancel" class="rounded-md bg-white dark:bg-darkblack-500 focus:outline-none">
                        <span class="sr-only">Close</span>
                        <!-- Cross Icon -->
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6 6L18 18M6 18L18 6L6 18Z" stroke="#747681" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </div>
                <div class="text-center mt-4">
                    <h3 class="text-2xl font-bold text-bgray-900 dark:text-white mb-3">
                        Your successfully changed your password
                    </h3>
                    <p class="text-base font-medium text-bgray-600 dark:text-darkblack-300 mb-7">
                        Commodo gravida eget ultricies sed in lacus. Commodo, tellus
                        duis eros pellentesque.
                    </p>
                    <a href="{{ route('login') }}" id="step-4-cancel" class="flex w-full py-4 text-white bg-success-300 hover:bg-success-400 transition-all justify-center text-base font-semibold rounded-lg">
                        Back to Login
                    </a>
                </div>
            </div>
        </div> --}}
    </div>
</div>
