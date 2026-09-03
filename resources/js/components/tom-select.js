export function initTomSelect(root = document) {
    const normalizeText = (value = '') => String(value).trim().toLowerCase();

    const enableCompactMultipleDisplay = (instance) => {
        const badgeClass = 'ts-selection-count';
        const hiddenItemClass = 'ts-selection-item-hidden';
        let selectedItemsPopover = null;

        instance.wrapper.classList.add('ts-wrapper-multiple-compact');

        const closeSelectedItemsPopover = () => {
            instance.control
                .querySelector(`.${badgeClass}`)
                ?.setAttribute('aria-expanded', 'false');

            if (!selectedItemsPopover) return;

            selectedItemsPopover.remove();
            selectedItemsPopover = null;
            document.removeEventListener('mousedown', handleOutsidePopoverClick);
            document.removeEventListener('keydown', handlePopoverKeydown);
            window.removeEventListener('resize', closeSelectedItemsPopover);
        };

        const handleOutsidePopoverClick = (event) => {
            if (
                selectedItemsPopover?.contains(event.target)
                || (
                    event.target instanceof Element
                    && event.target.closest(`.${badgeClass}`)
                )
            ) {
                return;
            }

            closeSelectedItemsPopover();
        };

        const handlePopoverKeydown = (event) => {
            if (event.key === 'Escape') {
                closeSelectedItemsPopover();
                instance.focus();
            }
        };

        const handleControlMouseDown = (event) => {
            if (
                selectedItemsPopover
                && (
                    !(event.target instanceof Element)
                    || !event.target.closest(`.${badgeClass}`)
                )
            ) {
                closeSelectedItemsPopover();
            }
        };

        const renderSelectedItemsPopover = () => {
            if (!selectedItemsPopover) return;

            const list = selectedItemsPopover.querySelector('.ts-selected-items-list');
            const scrollTop = list.scrollTop;
            const existingItems = new Map(
                Array.from(list.children).map((item) => [item.dataset.value, item])
            );

            instance.items.forEach((value) => {
                const itemValue = String(value);
                let item = existingItems.get(itemValue);

                if (!item) {
                    item = document.createElement('div');
                    item.className = 'ts-selected-items-option';
                    item.dataset.value = itemValue;

                    const label = document.createElement('span');
                    label.className = 'ts-selected-items-label';

                    const removeButton = document.createElement('button');
                    removeButton.type = 'button';
                    removeButton.className = 'ts-selected-items-remove';
                    removeButton.textContent = '×';
                    removeButton.addEventListener('click', (event) => {
                        const selectedItem = instance.getItem(itemValue);

                        if (
                            instance.isLocked
                            || !selectedItem
                            || !instance.shouldDelete([selectedItem], event)
                        ) {
                            return;
                        }

                        instance.removeItem(selectedItem);
                        instance.refreshOptions(false);
                        instance.inputState();
                    });

                    item.append(label, removeButton);
                }

                const label = item.querySelector('.ts-selected-items-label');
                const removeButton = item.querySelector('.ts-selected-items-remove');
                label.textContent = String(instance.options[itemValue]?.text ?? itemValue);
                removeButton.setAttribute('aria-label', `Remove ${label.textContent}`);

                list.append(item);
                existingItems.delete(itemValue);
            });

            existingItems.forEach((item) => item.remove());
            list.scrollTop = scrollTop;
        };

        const openSelectedItemsPopover = (badge) => {
            const wasIgnoringFocus = instance.ignoreFocus;

            try {
                instance.ignoreFocus = true;
                instance.close();
            } finally {
                instance.ignoreFocus = wasIgnoringFocus;
            }

            closeSelectedItemsPopover();

            selectedItemsPopover = document.createElement('div');
            selectedItemsPopover.className = 'ts-selected-items-popover';
            selectedItemsPopover.setAttribute('role', 'dialog');
            selectedItemsPopover.setAttribute('aria-label', 'Selected items');
            selectedItemsPopover.innerHTML = `
                <div class="ts-selected-items-heading">Selected items</div>
                <div class="ts-selected-items-list"></div>
            `;
            document.body.append(selectedItemsPopover);
            selectedItemsPopover.addEventListener('mousedown', (event) => {
                event.stopPropagation();
            });
            selectedItemsPopover.addEventListener('click', (event) => {
                event.stopPropagation();
            });

            const wrapperRect = instance.wrapper.getBoundingClientRect();
            const viewportPadding = 8;
            const width = Math.min(wrapperRect.width, window.innerWidth - (viewportPadding * 2));
            const left = Math.min(
                Math.max(viewportPadding, wrapperRect.left),
                window.innerWidth - width - viewportPadding
            );

            selectedItemsPopover.style.width = `${width}px`;
            selectedItemsPopover.style.left = `${left + window.scrollX}px`;
            selectedItemsPopover.style.top = `${wrapperRect.bottom + window.scrollY + 4}px`;

            renderSelectedItemsPopover();
            document.addEventListener('mousedown', handleOutsidePopoverClick);
            document.addEventListener('keydown', handlePopoverKeydown);
            window.addEventListener('resize', closeSelectedItemsPopover);

            selectedItemsPopover.querySelector('.ts-selected-items-remove')?.focus();
            badge.setAttribute('aria-expanded', 'true');
        };

        const syncSelectedItems = () => {
            const selectedItems = Array.from(instance.control.querySelectorAll('.item'));
            const hiddenCount = Math.max(0, selectedItems.length - 1);

            selectedItems.forEach((item, index) => {
                item.classList.toggle(hiddenItemClass, index > 0);
            });

            let badge = instance.control.querySelector(`.${badgeClass}`);

            if (hiddenCount === 0) {
                badge?.remove();
                renderSelectedItemsPopover();
                return;
            }

            if (!badge) {
                badge = document.createElement('button');
                badge.type = 'button';
                badge.className = badgeClass;
                badge.setAttribute('aria-haspopup', 'dialog');
                badge.setAttribute('aria-expanded', 'false');
                badge.addEventListener('mousedown', (event) => event.preventDefault());
                badge.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();

                    if (!instance.isDisabled) {
                        openSelectedItemsPopover(badge);
                    }
                });

                const input = instance.control.querySelector('input');
                instance.control.insertBefore(badge, input);
            }

            badge.textContent = `+${hiddenCount}`;
            badge.setAttribute(
                'aria-label',
                `Show all ${selectedItems.length} selected items`
            );
            badge.disabled = instance.isDisabled;
            renderSelectedItemsPopover();
        };

        instance.on('item_add', syncSelectedItems);
        instance.on('item_remove', syncSelectedItems);
        instance.on('change', syncSelectedItems);
        instance.control.addEventListener('mousedown', handleControlMouseDown);
        instance.on('destroy', () => {
            instance.control.removeEventListener('mousedown', handleControlMouseDown);
            closeSelectedItemsPopover();
        });
        syncSelectedItems();
    };

    const applyDisabledStyles = (instance, el) => {
        if (!instance?.wrapper || !instance?.control || !el.disabled) return;

        instance.wrapper.classList.add('opacity-100');
        instance.control.classList.add(
            'border-bgray-200',
            'bg-bgray-50',
            'text-bgray-600',
            'dark:border-darkblack-400',
            'dark:bg-darkblack-500',
            'dark:text-bgray-300'
        );
        instance.control.classList.remove('bg-white');

        instance.control.querySelectorAll('.item, input, .ts-control > div').forEach(node => {
            node.classList.add('text-bgray-600', 'dark:text-bgray-300');
        });
    };

    // Standard Select
    const syncOptionSubtypes = (instance, el) => {
        if (!el || el.tagName !== 'SELECT') return;

        let hasSubtype = false;

        Array.from(el.options).forEach((option) => {
            const optionValue = String(option.value ?? '');

            if (!Object.prototype.hasOwnProperty.call(instance.options, optionValue)) {
                return;
            }

            let subtype = option.dataset.subtype || '';
            let email = option.dataset.email || '';

            if (option.dataset.data) {
                try {
                    const parsedData = JSON.parse(option.dataset.data);
                    if (!subtype && parsedData?.subtype) subtype = parsedData.subtype;
                    if (!subtype && parsedData?.email) subtype = parsedData.email;
                    if (!email && parsedData?.email) email = parsedData.email;
                } catch (error) {
                    // ignore JSON error
                }
            }

            if (subtype || email) {
                hasSubtype = true;
                instance.options[optionValue] = {
                    ...instance.options[optionValue],
                    subtype: subtype || email,
                    email: email || subtype,
                };
            }
        });

        if (hasSubtype) {
            instance.clearCache();
            instance.refreshOptions(false);

            const currentValue = instance.getValue();

            if (currentValue !== undefined && currentValue !== null && currentValue !== '' && (Array.isArray(currentValue) ? currentValue.length > 0 : true)) {
                instance.setValue(currentValue, true);
            }
        }
    };

    // Standard Select
    root.querySelectorAll('select.tom-select-no-search, input.tom-select-no-search').forEach(el => {

        if (el.tomselect) return; // Prevent double init

        const config = {
            create: false,
            persist: false,
            hideDropdownArrow: false,
            plugins: ['remove_button'],
            dropdownParent: 'body',
        };

        if (el.dataset.renderSubtype === 'true') {
            config.render = {
                option: function (data, escape) {
                    const sub = data.subtype || data.email || '';
                    return `
                        <div>
                            <div class="font-medium">${escape(data.text)}</div>
                            ${sub ? `<div class="text-sm text-gray-600 dark:text-gray-400">${escape(sub)}</div>` : ''}
                        </div>
                    `;
                },
                item: function (data, escape) {
                    const sub = data.subtype || data.email || '';
                    return `
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium">${escape(data.text)}</span>
                            ${sub ? `<span class="text-sm text-gray-600 dark:text-gray-400 ml-2">${escape(sub)}</span>` : ''}
                        </div>
                    `;
                }
            };
        }

        const instance = new TomSelect(el, config);

        if (el.dataset.renderSubtype === 'true' && el.tagName === 'SELECT') {
            syncOptionSubtypes(instance, el);
        }

        applyDisabledStyles(instance, el);
    });

    // Standard Select
    root.querySelectorAll('select.tom-select, input.tom-select').forEach(el => {

        if (el.tomselect) return; // Prevent double init

        const sort = el.dataset.sort != "0";

        const config = {
            create: false,
            persist: false,
            hideDropdownArrow: false,
            plugins: ['dropdown_input', 'remove_button'],
            searchField: ['text', 'subtype', 'email'],
            dropdownParent: 'body',
            render: {
                option: function (data, escape) {
                    const sub = data.subtype || data.email || '';
                    return `
                        <div>
                            <div class="font-medium">${escape(data.text)}</div>
                            ${sub ? `<div class="text-sm text-gray-600 dark:text-gray-400">${escape(sub)}</div>` : ''}
                        </div>
                    `;
                },
                item: function (data, escape) {
                    const sub = data.subtype || data.email || '';
                    return `
                        <div>
                            <span class="font-medium">${escape(data.text)}</span>
                            ${sub ? `<span class="text-sm text-gray-600 dark:text-gray-400 ml-2">${escape(sub)}</span>` : ''}
                        </div>
                    `;
                }
            }
        };

        if (sort) {
            config.sortField = { field: "text", direction: "asc" };
        }

        const instance = new TomSelect(el, config);
        syncOptionSubtypes(instance, el);
        if (el.multiple) {
            enableCompactMultipleDisplay(instance);
        }
        applyDisabledStyles(instance, el);
    });

    root.querySelectorAll('select.tom-select-tags, input.tom-select-tags, select.tom-select-add').forEach(el => {
        if (el.tomselect) return;

        const placeholder = el.dataset.placeholder || 'Search or add tags';
        const maxItems = el.dataset.maxItems || null;

        const instance = new TomSelect(el, {
            plugins: ['remove_button'],
            maxItems: maxItems,
            persist: false,
            dropdownParent: 'body',
            createOnBlur: true,
            hideSelected: true,
            closeAfterSelect: false,
            placeholder: placeholder,
            create: el.disabled ? false : (input) => {
                const text = String(input || '').trim();

                return {
                    value: text,
                    text,
                };
            },
            createFilter(input) {
                const normalizedInput = normalizeText(input);

                if (!normalizedInput) {
                    return false;
                }

                return !Object.values(this.options).some((option) => {
                    const optionText = normalizeText(option?.text ?? option?.value ?? '');
                    return optionText === normalizedInput;
                });
            },
            score(search) {
                const normalizedSearch = normalizeText(search);

                return function (item) {
                    const text = normalizeText(item.text);

                    if (!normalizedSearch) {
                        return 1;
                    }

                    if (text === normalizedSearch) {
                        return 2;
                    }

                    return text.includes(normalizedSearch) ? 1 : 0;
                };
            },
            render: {
                option(data, escape) {
                    return `
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium">${escape(data.text)}</span>
                            ${data.$option ? '' : '<span class="text-xs font-semibold text-success-400">Create</span>'}
                        </div>
                    `;
                },
                item(data, escape) {
                    return `<div class="font-medium">${escape(data.text)}</div>`;
                },
            },
        });

        applyDisabledStyles(instance, el);
    });

    // Multiple select
    root.querySelectorAll('select.tom-select-multiple, input.tom-select-multiple').forEach(el => {

        if (el.tomselect) return; // Prevent double init

        const instance = new TomSelect(el, {
            plugins: ['remove_button', 'dropdown_input'],
            maxItems: null,
            searchField: ['text', 'subtype', 'email'],
            dropdownParent: 'body',
            render: {
                option: function (data, escape) {
                    const sub = data.subtype || data.email || '';
                    return `
                        <div>
                            <div class="font-medium">${escape(data.text)}</div>
                            ${sub ? `<div class="text-sm text-gray-600 dark:text-gray-400">${escape(sub)}</div>` : ''}
                        </div>
                    `;
                },
                item: function (data, escape) {
                    const sub = data.subtype || data.email || '';
                    return `
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium">${escape(data.text)}</span>
                            ${sub ? `<span class="text-sm text-gray-600 dark:text-gray-400 ml-2">${escape(sub)}</span>` : ''}
                        </div>
                    `;
                }
            }
        });

        syncOptionSubtypes(instance, el);
        enableCompactMultipleDisplay(instance);
        applyDisabledStyles(instance, el);
    });

    // Lazy load tom select
    root.querySelectorAll('select.tom-select-lazy, input.tom-select-lazy').forEach(el => {
        if (el.tomselect) return; // Prevent double init

        const sort = el.dataset.sort != "0";
        const route = el.dataset.route;

        const config = {
            create: false,
            persist: false,
            hideDropdownArrow: false,
            plugins: ['dropdown_input', 'remove_button'],
            searchField: ['text', 'subtype', 'email'],
            sortField: sort ? { field: "text", direction: "asc" } : null,
            dropdownParent: 'body',

            render: {
                option: function (data, escape) {
                    const sub = data.subtype || data.email || data.project_code || '';
                    return `
                        <div>
                            <div class="font-medium">${escape(data.text || data.name)}</div>
                            ${sub ? `<div class="text-sm text-gray-600 dark:text-gray-400">${escape(sub)}</div>` : ''}
                        </div>
                    `;
                },
                item: function (data, escape) {
                    const sub = data.subtype || data.email || data.project_code || '';
                    return `
                        <div>
                            <span class="font-medium">${escape(data.text || data.name)}</span>
                            ${sub ? `<span class="text-sm text-gray-600 dark:text-gray-400 ml-2">${escape(sub)}</span>` : ''}
                        </div>
                    `;
                }
            },

            // Lazy load items via AJAX
            load: function (query, callback) {
                if (!route) return callback();

                const sep = route.includes('?') ? '&' : '?';
                let fetchUrl = `${route}${sep}q=${encodeURIComponent(query || '')}`;
                if (el.dataset.excludeId) {
                    fetchUrl += `&exclude_id=${encodeURIComponent(el.dataset.excludeId)}`;
                }

                fetch(fetchUrl)
                    .then(res => res.json())
                    .then(json => {
                        // Expect JSON array [{id: 1, name: '...', subtype: '...'}, ...]
                        callback(json.map(c => ({
                            value: String(c.value ?? c.id),
                            text: c.text ?? c.name,
                            subtype: c.subtype ?? c.project_code ?? '',
                        })));
                    })
                    .catch(() => callback());
            }
        };

        const instance = new TomSelect(el, config);
        syncOptionSubtypes(instance, el);
        applyDisabledStyles(instance, el);
    });

    document.dispatchEvent(new Event('tomselect:ready'));
}

//make auto select for dropdown input
export const autoTomSelect = (el, value) => {
    const select = document.getElementById(el);

    if (!select || !select.tomselect) return;

    if (value) {
        select.tomselect.setValue(value);
    } else {
        select.tomselect.clear();
    }
};
