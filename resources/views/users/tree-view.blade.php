@extends('layouts.master')

@push('styles')
    <style>
        .org-surface {
            background:
                radial-gradient(circle at top right, rgba(34, 197, 94, 0.08), transparent 28%),
                linear-gradient(180deg, rgba(248, 250, 252, 0.98), rgba(255, 255, 255, 0.98));
        }

        .dark .org-surface {
            background:
                radial-gradient(circle at top right, rgba(34, 197, 94, 0.12), transparent 30%),
                linear-gradient(180deg, rgba(34, 39, 49, 0.96), rgba(26, 31, 41, 0.98));
        }

        .org-panel {
            border-radius: 28px;
        }

        .org-stat-card {
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.9);
            padding: 0.625rem 0.75rem;
        }

        .dark .org-stat-card {
            border-color: rgba(58, 67, 81, 0.95);
            background: rgba(23, 28, 37, 0.9);
        }

        .org-stat-label {
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .org-alert-warning {
            border: 1px solid rgba(253, 230, 138, 0.95);
            border-radius: 12px;
            background: rgba(254, 252, 232, 0.96);
            color: rgb(180, 83, 9);
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .dark .org-alert-warning {
            border-color: rgba(120, 53, 15, 0.45);
            background: rgba(23, 28, 37, 0.96);
            color: rgb(253, 224, 71);
        }

        .org-chart-scroll {
            position: relative;
            overflow-x: auto;
            overflow-y: visible;
            padding-bottom: 0.5rem;
        }

        .org-chart-scroll::-webkit-scrollbar {
            height: 8px;
        }

        .org-chart-scroll::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.45);
            border-radius: 9999px;
        }

        .org-root-list,
        .org-children {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .org-root-list {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.4rem;
            min-width: max-content;
            width: max-content;
            margin-left: auto;
            margin-right: auto;
        }

        .org-node {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0 0.3rem;
            width: max-content;
        }

        .org-node--branch {
            padding-bottom: 0.1rem;
        }

        .org-card {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.38rem;
            width: 150px;
            min-height: 92px;
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.97);
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.07);
            padding: 0.65rem 0.55rem 0.6rem;
            text-align: center;
            transition: transform 160ms ease, box-shadow 160ms ease;
        }

        .org-card[data-user-card] {
            cursor: pointer;
        }

        .org-card[data-user-card]:hover,
        .org-card[data-user-card]:focus-visible {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.12);
            outline: none;
        }

        .dark .org-card {
            border-color: rgba(58, 67, 81, 0.95);
            background: rgba(23, 28, 37, 0.96);
            box-shadow: 0 10px 22px rgba(0, 0, 0, 0.22);
        }

        .org-card--root {
            width: 164px;
            border-color: rgba(34, 197, 94, 0.28);
            box-shadow: 0 12px 24px rgba(34, 197, 94, 0.1);
        }

        .dark .org-card--root {
            border-color: rgba(34, 197, 94, 0.36);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.24);
        }

        .org-card--auth {
            border-color: #22c55e;
            background: #f0fdf4;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.18), 0 14px 28px rgba(34, 197, 94, 0.2);
        }

        .dark .org-card--auth {
            border-color: #22c55e;
            background: rgba(34, 197, 94, 0.12);
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.22), 0 14px 28px rgba(0, 0, 0, 0.24);
        }

        .org-card--auth .org-avatar {
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.18), 0 8px 16px rgba(34, 197, 94, 0.22);
        }

        .dark .org-card--auth .org-avatar {
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.22), 0 8px 16px rgba(0, 0, 0, 0.26);
        }

        .org-card--auth .org-name {
            color: #15803d;
        }

        .dark .org-card--auth .org-name {
            color: #86efac;
        }

        .org-avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            overflow: hidden;
            border-radius: 9999px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #fff;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            box-shadow: 0 6px 12px rgba(34, 197, 94, 0.18);
        }

        .org-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .org-meta {
            width: 100%;
            min-width: 0;
        }

        .org-name {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .org-role {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .org-badges {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.2rem;
            min-height: 1rem;
        }

        .org-badge {
            flex-shrink: 0;
            border-radius: 9999px;
            padding: 0.22rem 0.42rem;
            font-size: 0.62rem;
            font-weight: 700;
            line-height: 1;
        }

        .org-badge--warn {
            background: rgba(251, 191, 36, 0.16);
            color: rgb(180, 83, 9);
        }

        .dark .org-badge--warn {
            background: rgba(251, 191, 36, 0.18);
            color: rgb(253, 224, 71);
        }

        .org-badge--inactive {
            background: rgba(239, 68, 68, 0.14);
            color: rgb(185, 28, 28);
        }

        .dark .org-badge--inactive {
            background: rgba(239, 68, 68, 0.18);
            color: rgb(252, 165, 165);
        }

        .org-badge--self {
            background: #22c55e;
            color: #fff;
            box-shadow: 0 4px 10px rgba(34, 197, 94, 0.22);
        }

        .dark .org-badge--self {
            background: #4ade80;
            color: #1f2937;
        }

        .org-user-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 10000;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.42);
            padding: 1rem;
        }

        .org-user-modal-backdrop.is-open {
            display: flex;
        }

        .org-user-modal {
            width: min(100%, 320px);
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.99);
            box-shadow: 0 24px 48px rgba(15, 23, 42, 0.22);
            padding: 1rem;
        }

        .dark .org-user-modal {
            border-color: rgba(58, 67, 81, 0.95);
            background: rgba(23, 28, 37, 0.99);
            box-shadow: 0 24px 48px rgba(0, 0, 0, 0.38);
        }

        .org-user-modal-row + .org-user-modal-row {
            margin-top: 0.65rem;
            padding-top: 0.65rem;
            border-top: 1px solid rgba(226, 232, 240, 0.82);
        }

        .dark .org-user-modal-row + .org-user-modal-row {
            border-top-color: rgba(58, 67, 81, 0.82);
        }

        .org-user-modal-label {
            display: block;
            margin-bottom: 0.15rem;
            font-size: 0.66rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: rgb(100, 116, 139);
        }

        .dark .org-user-modal-label {
            color: rgb(148, 163, 184);
        }

        .org-user-modal-value {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            line-height: 1.4;
            color: rgb(15, 23, 42);
            word-break: break-word;
        }

        .dark .org-user-modal-value {
            color: rgb(226, 232, 240);
        }

        .org-user-modal-email {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .org-user-modal-email .org-user-modal-value {
            min-width: 0;
            flex: 1;
        }

        .org-user-copy-btn {
            display: inline-flex;
            height: 2rem;
            width: 2rem;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            border: 1px solid rgba(226, 232, 240, 0.95);
            color: rgb(100, 116, 139);
            transition: border-color 160ms ease, color 160ms ease, background-color 160ms ease;
        }

        .org-user-copy-btn:hover {
            border-color: rgba(34, 197, 94, 0.65);
            color: rgb(34, 197, 94);
            background: rgba(34, 197, 94, 0.06);
        }

        .dark .org-user-copy-btn {
            border-color: rgba(58, 67, 81, 0.95);
            color: rgb(148, 163, 184);
        }

        .dark .org-user-copy-btn:hover {
            border-color: rgba(74, 222, 128, 0.65);
            color: rgb(134, 239, 172);
            background: rgba(34, 197, 94, 0.12);
        }

        .org-children {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            gap: 0;
            margin-top: 0.7rem;
            padding-top: 0.8rem;
        }

        .org-children::before {
            content: "";
            position: absolute;
            top: 0;
            left: 50%;
            width: 2px;
            height: 0.8rem;
            transform: translateX(-50%);
            background: linear-gradient(180deg, rgba(34, 197, 94, 0.55), rgba(148, 163, 184, 0.22));
        }

        .dark .org-children::before {
            background: linear-gradient(180deg, rgba(74, 222, 128, 0.65), rgba(71, 85, 105, 0.32));
        }

        .org-children>.org-node {
            padding-top: 0.8rem;
        }

        .org-children>.org-node::before,
        .org-children>.org-node::after {
            content: "";
            position: absolute;
            top: 0;
            width: 50%;
            height: 0.8rem;
            border-top: 1px solid rgba(34, 197, 94, 0.35);
        }

        .org-children>.org-node::before {
            right: 50%;
        }

        .org-children>.org-node::after {
            left: 50%;
            border-left: 1px solid rgba(34, 197, 94, 0.35);
        }

        .dark .org-children>.org-node::before,
        .dark .org-children>.org-node::after {
            border-top-color: rgba(74, 222, 128, 0.4);
        }

        .dark .org-children>.org-node::after {
            border-left-color: rgba(74, 222, 128, 0.4);
        }

        .org-children>.org-node:first-child::before,
        .org-children>.org-node:last-child::after {
            border-top: 0;
        }

        .org-children>.org-node:only-child {
            padding-top: 0;
        }

        .org-children>.org-node:only-child::before,
        .org-children>.org-node:only-child::after {
            display: none;
        }

        .org-children>.org-node:last-child::before {
            border-right: 1px solid rgba(34, 197, 94, 0.35);
            border-top-right-radius: 12px;
        }

        .dark .org-children>.org-node:last-child::before {
            border-right-color: rgba(74, 222, 128, 0.4);
        }

        .org-children>.org-node:first-child::after {
            border-top-left-radius: 12px;
        }

        .org-empty-state {
            min-width: 220px;
        }

        @media (max-width: 767px) {
            .org-chart-scroll {
                overflow-x: auto;
            }

            .org-root-list {
                min-width: max-content;
                align-items: stretch;
            }

            .org-node {
                width: 100%;
                padding-left: 0;
                padding-right: 0;
            }

            .org-card,
            .org-card--root {
                width: 100%;
                max-width: 220px;
            }

            .org-children {
                flex-direction: column;
                align-items: center;
                margin-top: 0.7rem;
                padding-top: 0.7rem;
            }

            .org-children::before {
                height: calc(100% - 0.7rem);
                bottom: 0;
            }

            .org-children>.org-node {
                padding-top: 0;
                margin-top: 0.7rem;
            }

            .org-children>.org-node:first-child {
                margin-top: 0;
            }

            .org-children>.org-node::before,
            .org-children>.org-node::after {
                display: none;
            }

            .org-children>.org-node>.org-card::before {
                content: "";
                position: absolute;
                top: -0.7rem;
                left: 50%;
                width: 2px;
                height: 0.7rem;
                transform: translateX(-50%);
                background: rgba(34, 197, 94, 0.35);
            }

            .dark .org-children>.org-node>.org-card::before {
                background: rgba(74, 222, 128, 0.4);
            }

            .org-children>.org-node:only-child>.org-card::before {
                display: none;
            }
        }
    </style>
@endpush

@section('page-content')
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px] xl:pb-[48px]">
        <div class="org-panel org-surface overflow-visible border border-bgray-200 p-6 shadow-sm dark:border-darkblack-400">
            <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-bgray-900 dark:text-white">Reporting Tree</h2>
                    <p class="mt-1 text-sm text-bgray-600 dark:text-bgray-300">Understand reporting lines across your team.</p>
                </div>

                <div class="ml-auto flex flex-col items-stretch gap-3">
                    <div class="grid gap-2 sm:grid-cols-3">
                        <div class="org-stat-card">
                            <p class="org-stat-label text-bgray-700 dark:text-bgray-300">Users : {{ $totalUsers }}</p>
                        </div>

                        <div class="org-stat-card">
                            <p class="org-stat-label text-bgray-700 dark:text-bgray-300">No Reporter : {{ $usersWithoutReporterCount }}</p>
                        </div>
                    </div>

                    @if ($hasDetachedNodes)
                        <div class="org-alert-warning">
                            Some users were attached to the root because their reporter chain was incomplete.
                        </div>
                    @endif
                </div>
            </div>

            @if ($hasDetachedNodes === false)
                <div class="sr-only">
                    All users are connected to a valid reporter chain.
                </div>
            @endif

            <div class="org-chart-scroll">
                <ul class="org-root-list">
                    @forelse ($roots as $root)
                        @php
                            $rootUser = $root['user'] ?? null;
                            $rootChildren = $root['children'] ?? [];
                            $rootIsVirtual = (bool) ($root['is_virtual'] ?? false);
                            $rootRole = $rootUser?->is_super_admin ? 'Super Admin' : $rootUser?->roles?->first()?->name ?? 'No Role';
                            $isAuthRootUser = $rootUser && (int) $rootUser->id === (int) auth()->id();
                            $rootEmail = $rootUser?->email ?? '-';
                            $rootEmployeeId = $rootUser?->details?->employee_id ?? '-';
                            $rootDesignation = $rootIsVirtual ? 'Virtual Root' : $rootUser?->details?->designation?->name ?? $rootRole;
                        @endphp

                        <li class="org-node {{ !empty($rootChildren) ? 'org-node--branch' : '' }}">
                            <div class="org-card org-card--root {{ $isAuthRootUser ? 'org-card--auth' : '' }}"
                                data-user-card
                                data-user-name="{{ $rootUser?->name ?? ($root['label'] ?? 'Super Admin') }}"
                                data-user-email="{{ $rootEmail }}"
                                data-user-employee-id="{{ $rootEmployeeId }}"
                                data-user-role="{{ $rootRole }}"
                                data-user-designation="{{ $rootDesignation }}"
                                role="button"
                                tabindex="0"
                                aria-haspopup="dialog"
                                aria-controls="org-user-modal">
                                <x-user-avatar :user="$rootUser" :name="$rootUser?->name ?? ($root['label'] ?? 'Super Admin')" class="org-avatar" />

                                <div class="org-meta">
                                    <h3 class="org-name text-sm font-bold text-bgray-900 dark:text-white">
                                        {{ $rootUser?->name ?? ($root['label'] ?? 'Super Admin') }}
                                    </h3>
                                    <p class="org-role mt-0.5 text-xs font-medium text-bgray-600 dark:text-bgray-300">
                                        {{ $rootIsVirtual ? 'Virtual Root' : $rootRole }}
                                    </p>
                                </div>
                            </div>

                            @if (!empty($rootChildren))
                                <ul class="org-children">
                                    @foreach ($rootChildren as $node)
                                        @include('users.partials.hierarchy-node', ['node' => $node])
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @empty
                        <li class="org-empty-state rounded-2xl border border-dashed border-bgray-300 px-5 py-8 text-center text-sm font-medium text-bgray-500 dark:border-darkblack-400 dark:text-bgray-300">
                            No users found for the hierarchy view.
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </main>

    <div id="org-user-modal" class="org-user-modal-backdrop" aria-hidden="true">
        <div class="org-user-modal" role="dialog" aria-modal="true" aria-labelledby="org-user-modal-title">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-success-400">Reporting User</p>
                    <h3 id="org-user-modal-title" class="mt-1 text-lg font-bold text-bgray-900 dark:text-white">User Details</h3>
                </div>
                <button type="button" id="org-user-modal-close" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-bgray-200 text-bgray-600 transition hover:border-success-300 hover:text-success-400 dark:border-darkblack-400 dark:text-bgray-300 dark:hover:border-success-300 dark:hover:text-success-300" aria-label="Close user details">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 6L18 18M18 6L6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                    </svg>
                </button>
            </div>

            <div class="mt-5">
                <div class="org-user-modal-row">
                    <span class="org-user-modal-label">Name</span>
                    <span class="org-user-modal-value" data-org-user-name>-</span>
                </div>
                <div class="org-user-modal-row">
                    <span class="org-user-modal-label">Email</span>
                    <div class="org-user-modal-email">
                        <span class="org-user-modal-value" data-org-user-email>-</span>
                        <button type="button" class="org-user-copy-btn" data-org-user-copy-email aria-label="Copy email" title="Copy email">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M8 7V6C8 4.89543 8.89543 4 10 4H18C19.1046 4 20 4.89543 20 6V14C20 15.1046 19.1046 16 18 16H17" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M6 8H14C15.1046 8 16 8.89543 16 10V18C16 19.1046 15.1046 20 14 20H6C4.89543 20 4 19.1046 4 18V10C4 8.89543 4.89543 8 6 8Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="org-user-modal-row">
                    <span class="org-user-modal-label">Employee ID</span>
                    <span class="org-user-modal-value" data-org-user-employee-id>-</span>
                </div>
                <div class="org-user-modal-row">
                    <span class="org-user-modal-label">Role</span>
                    <span class="org-user-modal-value" data-org-user-role>-</span>
                </div>
                <div class="org-user-modal-row">
                    <span class="org-user-modal-label">Designation</span>
                    <span class="org-user-modal-value" data-org-user-designation>-</span>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('org-user-modal');
            const closeButton = document.getElementById('org-user-modal-close');
            const copyEmailButton = modal?.querySelector('[data-org-user-copy-email]');

            if (!modal || !closeButton || !copyEmailButton) {
                return;
            }

            const fields = {
                name: modal.querySelector('[data-org-user-name]'),
                email: modal.querySelector('[data-org-user-email]'),
                employeeId: modal.querySelector('[data-org-user-employee-id]'),
                role: modal.querySelector('[data-org-user-role]'),
                designation: modal.querySelector('[data-org-user-designation]'),
            };

            const copyText = (value) => {
                const fallbackCopy = (text) => {
                    const input = document.createElement('textarea');
                    input.value = text;
                    input.setAttribute('readonly', '');
                    input.style.position = 'fixed';
                    input.style.opacity = '0';
                    input.style.pointerEvents = 'none';
                    document.body.appendChild(input);
                    input.focus();
                    input.select();

                    try {
                        document.execCommand('copy');
                    } finally {
                        document.body.removeChild(input);
                    }
                };

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(value).catch(() => fallbackCopy(value));
                    return;
                }

                fallbackCopy(value);
            };

            const openModal = (card) => {
                fields.name.textContent = card.dataset.userName || '-';
                fields.email.textContent = card.dataset.userEmail || '-';
                fields.employeeId.textContent = card.dataset.userEmployeeId || '-';
                fields.role.textContent = card.dataset.userRole || '-';
                fields.designation.textContent = card.dataset.userDesignation || '-';

                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                closeButton.focus();
            };

            const closeModal = () => {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
            };

            document.addEventListener('click', (event) => {
                const card = event.target.closest('[data-user-card]');

                if (card) {
                    openModal(card);
                    return;
                }

                if (event.target.closest('[data-org-user-copy-email]')) {
                    const email = fields.email.textContent?.trim();

                    if (email && email !== '-') {
                        copyText(email);
                    }

                    return;
                }

                if (event.target === modal || event.target.closest('#org-user-modal-close')) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                const card = event.target.closest('[data-user-card]');

                if (card && (event.key === 'Enter' || event.key === ' ')) {
                    event.preventDefault();
                    openModal(card);
                    return;
                }

                if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                    closeModal();
                }
            });
        });
    </script>
@endpush
