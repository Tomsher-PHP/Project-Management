<div id="api-explorer-root" class="flex flex-col space-y-5">
    <!-- Subtitle Description -->
    <p class="text-xs text-bgray-600 dark:text-bgray-400 -mt-2">
        Browse available Data Sync modules, endpoints, and request examples.
    </p>

    <!-- Explorer Two-Column Layout -->
    <div class="flex flex-col lg:flex-row gap-6 items-start w-full">
        <!-- =============================================================== -->
        <!-- LEFT PANEL: Module Navigation                                   -->
        <!-- =============================================================== -->
        @include('settings.data-sync.partials.explorer.navigation')

        <!-- =============================================================== -->
        <!-- RIGHT PANEL: Endpoint & Operation Details                      -->
        <!-- =============================================================== -->
        <main class="flex-1 min-w-0 w-full flex flex-col space-y-5">
            <!-- Empty State -->
            @include('settings.data-sync.partials.explorer.empty-state')

            <div id="explorer-content" class="flex min-w-0 flex-col space-y-5">
                <!-- Module Header Card -->
                @include('settings.data-sync.partials.explorer.module-header')

                <!-- Operation Details Card -->
                @include('settings.data-sync.partials.explorer.operation-details')
            </div>
        </main>
    </div>
    <p id="explorer-copy-status" class="sr-only" role="status" aria-live="polite"></p>
</div>

<!-- ======================================================================= -->
<!-- EMBEDDED REGISTRY DATA SCRIPT                                          -->
<!-- ======================================================================= -->
@include('settings.data-sync.partials.explorer.data-script')
