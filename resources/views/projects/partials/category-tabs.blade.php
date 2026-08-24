@php
    $rawSelected = $selectedCategories ?? request()->input('project_category_ids', request()->input('project_category_id', []));

    if (!is_array($rawSelected)) {
        $rawSelected = strlen((string) $rawSelected) > 0 ? [$rawSelected] : [];
    }

    // Normalize to an array of unique string IDs
    $selectedCategoryIds = array_values(
        array_unique(
            array_filter(array_map('strval', $rawSelected), function ($val) {
                return $val !== '';
            }),
        ),
    );

    // Format categories collection/array into structured array for Alpine component
    $categoriesList = collect($projectCategories ?? [])
        ->map(function ($cat) {
            $id = is_array($cat) ? $cat['id'] ?? '' : $cat->id ?? '';
            $label = is_array($cat) ? $cat['name'] ?? ($cat['label'] ?? '') : $cat->name ?? ($cat->label ?? '');
            $count = is_array($cat) ? $cat['projects_count'] ?? ($cat['count'] ?? null) : $cat->projects_count ?? ($cat->count ?? null);
            return [
                'id' => (string) $id,
                'label' => (string) $label,
                'count' => $count !== null ? (int) $count : null,
            ];
        })
        ->values()
        ->toArray();
@endphp

<div x-data="projectCategoryTabs({ categories: {{ json_encode($categoriesList) }}, selected: {{ json_encode($selectedCategoryIds) }} })" class="w-[70%] rounded-lg p-3 shadow-sm dark:bg-darkblack-600 mb-6">
    <div class="flex w-full min-w-0 items-center gap-2">
        <!-- 1. Pinned All Projects Chip (Fixed Width / Left) -->
        <button type="button" @click="selectAll()" :data-active="isAllSelected()" :class="isAllSelected() ?
            'bg-success-300 text-white font-semibold shadow-sm' :
            'bg-bgray-200 text-bgray-600 hover:bg-bgray-300 dark:bg-darkblack-600 dark:text-bgray-50 dark:hover:bg-darkblack-500 font-medium'" class="shrink-0 rounded-lg px-4 py-2 text-sm transition-all duration-200 focus:outline-none flex items-center gap-1.5">
            <span>All Projects</span>
        </button>

        <div class="h-5 w-[1px] bg-bgray-300 dark:bg-darkblack-500 shrink-0 mx-1"></div>

        <!-- 2. Flexible Category Chips Area (Scrollable Middle) -->
        <div class="relative min-w-0 flex-1 flex items-center max-w-[100%]">
            <!-- Left Scroll Button -->
            <button type="button" x-show="canScrollLeft" x-cloak @click="scrollLeft()" class="absolute left-0 z-10 flex h-7 w-7 items-center justify-center rounded-full border border-bgray-300 bg-white/90 text-bgray-700 shadow-md backdrop-blur-sm transition-all hover:bg-white dark:border-darkblack-400 dark:bg-darkblack-700/90 dark:text-bgray-50 dark:hover:bg-darkblack-600 focus:outline-none" title="Scroll Left" aria-label="Scroll Left" style="display: none;">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <!-- Scroll Container -->
            <div x-ref="scrollContainer" @scroll.debounce.50ms="checkScroll()" @resize.window.debounce.100ms="checkScroll()" class="no-scrollbar flex w-full min-w-0 items-center gap-2 overflow-x-auto scroll-smooth py-1 px-1" style="scrollbar-width: none; -ms-overflow-style: none;">
                <template x-for="cat in categories" :key="cat.id">
                    <button type="button" @click="toggleCategory(cat.id)" :data-active="isSelected(cat.id)" :title="cat.label" :class="isSelected(cat.id) ?
                        'bg-success-300 text-white font-semibold shadow-sm' :
                        'bg-bgray-200 text-bgray-600 hover:bg-bgray-300 dark:bg-darkblack-600 dark:text-bgray-50 dark:hover:bg-darkblack-500 font-medium'" class="shrink-0 rounded-lg px-4 py-2 text-sm transition-all duration-200 focus:outline-none inline-flex items-center gap-1.5 max-w-[180px]">
                        <span class="truncate" x-text="cat.label"></span>
                        <span x-show="cat.count !== null && cat.count !== undefined" class="text-xs opacity-80" x-text="'(' + cat.count + ')'"></span>
                    </button>
                </template>

                <!-- Others Chip -->
                <button type="button" @click="toggleOthers()" :data-active="isSelected('others')" :class="isSelected('others') ?
                    'bg-success-300 text-white font-semibold shadow-sm' :
                    'bg-bgray-200 text-bgray-600 hover:bg-bgray-300 dark:bg-darkblack-600 dark:text-bgray-50 dark:hover:bg-darkblack-500 font-medium'" class="shrink-0 rounded-lg px-4 py-2 text-sm transition-all duration-200 focus:outline-none inline-flex items-center gap-1.5" title="Others">
                    <span>Others</span>
                </button>
            </div>

            <!-- Right Scroll Button -->
            <button type="button" x-show="canScrollRight" x-cloak @click="scrollRight()" class="absolute right-0 z-10 flex h-7 w-7 items-center justify-center rounded-full border border-bgray-300 bg-white/90 text-bgray-700 shadow-md backdrop-blur-sm transition-all hover:bg-white dark:border-darkblack-400 dark:bg-darkblack-700/90 dark:text-bgray-50 dark:hover:bg-darkblack-600 focus:outline-none" title="Scroll Right" aria-label="Scroll Right" style="display: none;">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>

        <div class="h-5 w-[1px] bg-bgray-300 dark:bg-darkblack-500 shrink-0 mx-1"></div>

        <!-- 3. All Categories Searchable Overflow Dropdown (Fixed Width / Right) -->
        <div class="relative shrink-0" @click.away="dropdownOpen = false">
            <button type="button" @click="dropdownOpen = !dropdownOpen" class="flex items-center gap-2 rounded-lg border border-bgray-300 bg-white px-3 py-2 text-sm font-medium text-bgray-700 transition hover:bg-bgray-100 dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-bgray-50 dark:hover:bg-darkblack-500 focus:outline-none" :aria-expanded="dropdownOpen.toString()">
                <svg class="h-4 w-4 text-bgray-500 dark:text-bgray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <span class="hidden sm:inline">All Categories</span>
                <span x-show="selected.length > 0" class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-success-300 text-xs font-bold text-white" x-text="selected.length"></span>
                <svg class="h-4 w-4 transition-transform duration-200" :class="dropdownOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <!-- Dropdown Content -->
            <div x-show="dropdownOpen" x-cloak x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute right-0 z-30 mt-2 w-72 rounded-xl border border-bgray-200 bg-white p-3 shadow-xl dark:border-darkblack-400 dark:bg-darkblack-700" style="display: none;">
                <!-- Search input -->
                <div class="relative mb-2">
                    <input type="text" x-model="searchQuery" placeholder="Search categories..." class="w-full rounded-lg border border-bgray-300 bg-bgray-50 py-1.5 pl-8 pr-3 text-xs text-bgray-900 focus:border-success-300 focus:outline-none dark:border-darkblack-400 dark:bg-darkblack-600 dark:text-white dark:placeholder-bgray-400" />
                    <svg class="absolute left-2.5 top-2 h-3.5 w-3.5 text-bgray-400 dark:text-bgray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <!-- Category List -->
                <div class="max-h-60 overflow-y-auto pr-1">
                    <!-- Clear selection / All Projects option -->
                    <button type="button" @click="selectAll()" class="flex w-full items-center justify-between rounded-md px-2.5 py-1.5 text-left text-xs font-medium transition hover:bg-bgray-100 dark:hover:bg-darkblack-600" :class="isAllSelected() ? 'text-success-400 font-semibold' : 'text-bgray-700 dark:text-bgray-200'">
                        <span>All Projects</span>
                        <svg x-show="isAllSelected()" class="h-4 w-4 text-success-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>

                    <div class="my-1 border-t border-bgray-200 dark:border-darkblack-500"></div>

                    <template x-for="cat in filteredCategories()" :key="cat.id">
                        <button type="button" @click="toggleCategory(cat.id)" class="flex w-full items-center justify-between rounded-md px-2.5 py-1.5 text-left text-xs transition hover:bg-bgray-100 dark:hover:bg-darkblack-600" :class="isSelected(cat.id) ? 'bg-success-50 text-success-400 font-semibold dark:bg-darkblack-600 dark:text-success-300' : 'text-bgray-700 dark:text-bgray-200'">
                            <span class="truncate pr-2" x-text="cat.label" :title="cat.label"></span>
                            <span class="flex items-center gap-1 shrink-0">
                                <span x-show="cat.count !== null && cat.count !== undefined" class="text-[11px] opacity-75" x-text="'(' + cat.count + ')'"></span>
                                <svg x-show="isSelected(cat.id)" class="h-4 w-4 text-success-400 dark:text-success-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </span>
                        </button>
                    </template>

                    <!-- Others Category Option -->
                    <button type="button" @click="toggleOthers()" class="flex w-full items-center justify-between rounded-md px-2.5 py-1.5 text-left text-xs transition hover:bg-bgray-100 dark:hover:bg-darkblack-600" :class="isSelected('others') ? 'bg-success-50 text-success-400 font-semibold dark:bg-darkblack-600 dark:text-success-300' : 'text-bgray-700 dark:text-bgray-200'">
                        <span>Others</span>
                        <svg x-show="isSelected('others')" class="h-4 w-4 text-success-400 dark:text-success-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>

                    <div x-show="filteredCategories().length === 0" class="px-2.5 py-3 text-center text-xs text-bgray-500 dark:text-bgray-400">
                        No categories found
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    if (typeof window.projectCategoryTabs !== 'function') {
        window.projectCategoryTabs = function(config) {
            return {
                categories: config.categories || [],
                selected: config.selected || [],
                dropdownOpen: false,
                searchQuery: '',
                canScrollLeft: false,
                canScrollRight: false,

                init() {
                    this.$nextTick(() => {
                        this.checkScroll();
                        this.scrollToActive();
                    });
                },

                isSelected(id) {
                    return this.selected.includes(String(id));
                },

                isAllSelected() {
                    return this.selected.length === 0;
                },

                toggleCategory(id) {
                    const strId = String(id);
                    if (this.isSelected(strId)) {
                        this.selected = this.selected.filter(item => item !== strId);
                    } else {
                        this.selected.push(strId);
                    }
                    this.applyFilter();
                },

                selectAll() {
                    this.selected = [];
                    this.applyFilter();
                },

                toggleOthers() {
                    this.toggleCategory('others');
                },

                applyFilter() {
                    const currentUrl = new URL(window.location.href);
                    const params = currentUrl.searchParams;

                    const keysToDelete = [];
                    for (const key of params.keys()) {
                        if (key === 'page' || key === 'project_category_id' || key === 'project_category' || key === 'project_category_ids' || key.startsWith('project_category_ids[')) {
                            keysToDelete.push(key);
                        }
                    }
                    keysToDelete.forEach(key => params.delete(key));

                    this.selected.forEach(id => {
                        params.append('project_category_ids[]', id);
                    });

                    window.location.href = currentUrl.pathname + (params.toString() ? '?' + params.toString() : '');
                },

                filteredCategories() {
                    if (!this.searchQuery.trim()) {
                        return this.categories;
                    }
                    const q = this.searchQuery.toLowerCase();
                    return this.categories.filter(cat => cat.label.toLowerCase().includes(q));
                },

                scrollLeft() {
                    const container = this.$refs.scrollContainer;
                    if (container) {
                        container.scrollBy({
                            left: -240,
                            behavior: 'smooth'
                        });
                    }
                },

                scrollRight() {
                    const container = this.$refs.scrollContainer;
                    if (container) {
                        container.scrollBy({
                            left: 240,
                            behavior: 'smooth'
                        });
                    }
                },

                checkScroll() {
                    const container = this.$refs.scrollContainer;
                    if (container) {
                        this.canScrollLeft = container.scrollLeft > 2;
                        this.canScrollRight = container.scrollWidth - container.clientWidth - container.scrollLeft > 2;
                    }
                },

                scrollToActive() {
                    const container = this.$refs.scrollContainer;
                    if (!container) return;
                    const activeChip = container.querySelector('[data-active="true"]');
                    if (activeChip) {
                        activeChip.scrollIntoView({
                            behavior: 'smooth',
                            block: 'nearest',
                            inline: 'nearest'
                        });
                    }
                }
            };
        };
    }
</script>
