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

        .org-root-list,
        .org-children {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .org-root-list {
            display: grid;
            gap: 1.5rem;
        }

        .org-node {
            position: relative;
        }

        .org-card {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-height: 4.5rem;
            border: 1px solid rgba(226, 232, 240, 0.95);
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 16px 34px rgba(15, 23, 42, 0.08);
            padding: 0.75rem 0.9rem;
        }

        .dark .org-card {
            border-color: rgba(58, 67, 81, 0.95);
            background: rgba(23, 28, 37, 0.96);
            box-shadow: 0 16px 36px rgba(0, 0, 0, 0.24);
        }

        .org-card--root {
            border-color: rgba(34, 197, 94, 0.28);
            box-shadow: 0 18px 40px rgba(34, 197, 94, 0.12);
        }

        .dark .org-card--root {
            border-color: rgba(34, 197, 94, 0.36);
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.28);
        }

        .org-avatar {
            display: flex;
            height: 2.75rem;
            width: 2.75rem;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: 9999px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #fff;
            font-size: 0.82rem;
            font-weight: 800;
            letter-spacing: 0.04em;
        }

        .org-avatar img {
            height: 100%;
            width: 100%;
            object-fit: cover;
        }

        .org-meta {
            min-width: 0;
            flex: 1;
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

        .org-badge {
            flex-shrink: 0;
            border-radius: 9999px;
            padding: 0.28rem 0.55rem;
            font-size: 0.7rem;
            font-weight: 700;
            line-height: 1;
        }

        .org-badge--count {
            background: rgba(15, 23, 42, 0.06);
            color: rgb(71, 85, 105);
        }

        .dark .org-badge--count {
            background: rgba(255, 255, 255, 0.07);
            color: rgb(203, 213, 225);
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

        .org-children {
            position: relative;
            margin-top: 0.85rem;
            margin-left: 1.35rem;
            padding-left: 1.4rem;
        }

        .org-children::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 1.35rem;
            width: 2px;
            border-radius: 9999px;
            background: linear-gradient(180deg, rgba(34, 197, 94, 0.4), rgba(148, 163, 184, 0.22));
        }

        .dark .org-children::before {
            background: linear-gradient(180deg, rgba(74, 222, 128, 0.55), rgba(71, 85, 105, 0.32));
        }

        .org-children > .org-node {
            margin-top: 0.85rem;
        }

        .org-children > .org-node:first-child {
            margin-top: 0;
        }

        .org-children > .org-node::before {
            content: "";
            position: absolute;
            left: -1.4rem;
            top: 2.15rem;
            width: 1.4rem;
            height: 2px;
            border-radius: 9999px;
            background: rgba(34, 197, 94, 0.35);
        }

        .dark .org-children > .org-node::before {
            background: rgba(74, 222, 128, 0.4);
        }

        @media (max-width: 640px) {
            .org-card {
                padding: 0.7rem 0.78rem;
            }

            .org-children {
                margin-left: 0.85rem;
                padding-left: 1rem;
            }

            .org-children > .org-node::before {
                left: -1rem;
                width: 1rem;
            }
        }
    </style>
@endpush

@section('page-content')
    <main class="w-full px-6 pb-6 pt-[100px] sm:pt-[120px] xl:px-[48px] xl:pb-[48px]">
        <div class="org-surface overflow-hidden rounded-[28px] border border-bgray-200 p-6 shadow-sm dark:border-darkblack-400">
            <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-bgray-900 dark:text-white">Reporting Tree</h2>
                </div>

                <div class="ml-auto flex flex-col items-stretch gap-3">
                    <div class="grid gap-2 sm:grid-cols-3">
                        <div class="rounded-2xl border border-bgray-200 bg-white/90 px-3 py-2.5 dark:border-darkblack-400 dark:bg-darkblack-600/90">
                            <p class="text-[11px] font-medium uppercase tracking-[0.08em] text-bgray-500 dark:text-bgray-300">Team : {{ $totalUsers }}</p>
                        </div>

                        <div class="rounded-2xl border border-bgray-200 bg-white/90 px-3 py-2.5 dark:border-darkblack-400 dark:bg-darkblack-600/90">
                            <p class="text-[11px] font-medium uppercase tracking-[0.08em] text-bgray-500 dark:text-bgray-300">Roots : {{ $superAdminCount }}</p>
                        </div>

                        <div class="rounded-2xl border border-bgray-200 bg-white/90 px-3 py-2.5 dark:border-darkblack-400 dark:bg-darkblack-600/90">
                            <p class="text-[11px] font-medium uppercase tracking-[0.08em] text-bgray-500 dark:text-bgray-300">No Reporter : {{ $usersWithoutReporterCount }}</p>
                        </div>
                    </div>

                    @if ($hasDetachedNodes)
                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-700 dark:border-amber-900/40 dark:bg-darkblack-500 dark:text-amber-300">
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

            <ul class="org-root-list">
                @forelse ($roots as $root)
                    @php
                        $rootUser = $root['user'] ?? null;
                        $rootChildren = $root['children'] ?? [];
                        $rootIsVirtual = (bool) ($root['is_virtual'] ?? false);
                        $rootInitials = $rootUser
                            ? \Illuminate\Support\Str::of($rootUser->name)
                                ->trim()
                                ->explode(' ')
                                ->filter()
                                ->take(2)
                                ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
                                ->implode('')
                            : 'SA';
                        $rootHasAvatar = $rootUser && filled($rootUser->primaryAttachment?->file_path ?? null);
                        $rootRole = $rootUser?->is_super_admin
                            ? 'Super Admin'
                            : ($rootUser?->roles?->first()?->name ?? 'No Role');
                    @endphp

                    <li class="org-node">
                        <div class="org-card org-card--root">
                            <div class="org-avatar">
                                @if ($rootHasAvatar)
                                    <img src="{{ $rootUser->profile_image_url }}" alt="{{ $rootUser->name }}">
                                @else
                                    <span>{{ $rootInitials }}</span>
                                @endif
                            </div>

                            <div class="org-meta">
                                <h3 class="org-name text-sm font-bold text-bgray-900 dark:text-white">
                                    {{ $rootUser?->name ?? ($root['label'] ?? 'Super Admin') }}
                                </h3>
                                <p class="org-role mt-0.5 text-xs font-medium text-bgray-600 dark:text-bgray-300">
                                    {{ $rootIsVirtual ? 'Virtual Root' : $rootRole }}
                                </p>
                            </div>

                            <span class="org-badge org-badge--count">{{ count($rootChildren) }}</span>
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
                    <li class="rounded-2xl border border-dashed border-bgray-300 px-5 py-8 text-center text-sm font-medium text-bgray-500 dark:border-darkblack-400 dark:text-bgray-300">
                        No users found for the hierarchy view.
                    </li>
                @endforelse
            </ul>
        </div>
    </main>
@endsection
