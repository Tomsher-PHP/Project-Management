<?php

return [

    //colors for notification types
    //#22C55E - green
    //#FFC837 - yellow
    //#2DD4BF - teal

    'shift_assigned' => [
        'label' => 'Shift Scheduled',
        'group' => 'Team & Shift',
        'action' => 'shift_scheduled',
        'icon_bg' => '#22C55E',
        'icon' => '
            <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="30" cy="30" r="30" fill="#22C55E"/>
                <path d="M18 20h24v4H18v-4zm0 8h24v4H18v-4zm0 8h18v4H18v-4z" fill="white"/>
            </svg>
        ',
        'in_app' => true,
        'email' => true,
        'sort_order' => 1
    ],

    'project_assigned' => [
        'label' => 'Project Assigned',
        'group' => 'Project Management',
        'action' => 'project_assigned',
        'icon_bg' => '#FFC837',
        'icon' => '
            <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="30" cy="30" r="30" fill="#FFC837"/>
                <path d="M18 22h24v16H18V22zm2 2v12h20V24H20zm4 3h12v2H24v-2zm0 4h10v2H24v-2z" fill="white"/>
            </svg>
        ',
        'in_app' => true,
        'email' => true,
        'sort_order' => 2
    ],

    'task_assigned' => [
        'label' => 'Task Assigned',
        'group' => 'Task Management',
        'action' => 'task_assigned',
        'icon_bg' => '#2DD4BF',
        'icon' => '
            <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="30" cy="30" r="30" fill="#2DD4BF"/>
                
                <!-- clipboard -->
                <rect x="22" y="20" width="16" height="20" rx="2" fill="white"/>

                <!-- lines -->
                <path d="M25 26h10M25 30h10M25 34h7" stroke="#2DD4BF" stroke-width="2" stroke-linecap="round"/>
                
                <!-- small plus badge (new task) -->
                <circle cx="40" cy="22" r="6" fill="#0F766E"/>
                <path d="M40 19v6M37 22h6" stroke="white" stroke-width="2" stroke-linecap="round"/>
            </svg>
            ',
        'in_app' => true,
        'email' => true,
        'sort_order' => 3
    ],

    'team_assigned' => [
        'label' => 'Team Assigned',
        'group' => 'Team & Shift',
        'action' => 'team_assigned',
        'icon_bg' => '#22C55E',
        'icon' => '
            <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="30" cy="30" r="30" fill="#22C55E"/>
                <path d="M22 26a4 4 0 1 1 8 0a4 4 0 1 1-8 0zm10 2c0-2.2 1.8-4 4-4s4 1.8 4 4-1.8 4-4 4-4-1.8-4-4zm-14 12c0-4 4-6 8-6s8 2 8 6v2H18v-2zm16 0c.2-1.5.8-2.8 2-3.8c1.2-1 2.8-1.2 4.5-1.2V40h-6.5z" fill="white"/>
            </svg>
        ',
        'in_app' => true,
        'email' => true,
        'sort_order' => 4
    ],

    'task_status_changed' => [
        'label' => 'Task Status Updated',
        'group' => 'Task Management',
        'action' => 'task_status_changed',
        'icon_bg' => '#FFC837',
        'icon' => '
            <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="30" cy="30" r="30" fill="#FFC837"/>

                <!-- circular arrows -->
                <path d="M38 24a10 10 0 1 0 2 8" stroke="white" stroke-width="2.5" fill="none" stroke-linecap="round"/>
                <path d="M40 24v6h-6" stroke="white" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>

                <!-- status dot -->
                <circle cx="30" cy="30" r="4" fill="white"/>
            </svg>
            ',
        'in_app' => true,
        'email' => true,
        'sort_order' => 5
    ],

    'task_request' => [
        'label' => 'Task Request',
        'group' => 'Requests & Approvals',
        'action' => 'task_request',
        'icon_bg' => '#2DD4BF',
        'icon' => '
            <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="30" cy="30" r="30" fill="#2DD4BF"/>
                <rect x="20" y="17" width="18" height="24" rx="3" fill="white"/>
                <rect x="25" y="14" width="8" height="5" rx="2" fill="white"/>
                <path d="M24 25h10M24 30h8M24 35h6" stroke="#099f38" stroke-width="2" stroke-linecap="round"/>
                <circle cx="40" cy="36" r="6" fill="#06752A"/>
                <path d="M37.5 36h5M40 33.5v5" stroke="white" stroke-width="2" stroke-linecap="round"/>
            </svg>
            ',
        'in_app' => true,
        'email' => true,
        'sort_order' => 6
    ],

    'task_log_request' => [
        'label' => 'Task Log Request',
        'group' => 'Requests & Approvals',
        'action' => 'task_log_request',
        'icon_bg' => '#22C55E',
        'icon' => '
            <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="30" cy="30" r="30" fill="#22C55E"/>
                <rect x="18" y="18" width="16" height="22" rx="3" fill="white"/>
                <path d="M22 24h8M22 29h8M22 34h5" stroke="#22C55E" stroke-width="2" stroke-linecap="round"/>
                <circle cx="39" cy="31" r="8" stroke="white" stroke-width="2.5"/>
                <path d="M39 27v4l3 2" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            ',
        'in_app' => true,
        'email' => true,
        'sort_order' => 7
    ],

    'handoff_request' => [
        'label' => 'Handoff Request',
        'group' => 'Requests & Approvals',
        'action' => 'handoff_request',
        'icon_bg' => '#FFC837',
        'icon' => '
            <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="30" cy="30" r="30" fill="#FFC837"/>
                <circle cx="22" cy="24" r="4" fill="white"/>
                <circle cx="38" cy="36" r="4" fill="white"/>
                <path d="M16 42c0-4 3.5-7 8-7h2" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                <path d="M34 18h2c4.5 0 8 3 8 7" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                <path d="M24 30h10" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                <path d="M31 27l3 3-3 3" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M36 30H26" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                <path d="M29 33l-3-3 3-3" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            ',
        'in_app' => true,
        'email' => true,
        'sort_order' => 7
    ],

    'break_request' => [
        'label' => 'Break Request',
        'group' => 'Requests & Approvals',
        'action' => 'break_request',
        'icon_bg' => '#2DD4BF',
        'icon' => '
            <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="30" cy="30" r="30" fill="#2DD4BF"/>
                <circle cx="22" cy="24" r="4" fill="white"/>
                <circle cx="38" cy="36" r="4" fill="white"/>
                <path d="M16 42c0-4 3.5-7 8-7h2" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                <path d="M34 18h2c4.5 0 8 3 8 7" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                <path d="M24 30h10" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                <path d="M31 27l3 3-3 3" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M36 30H26" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                <path d="M29 33l-3-3 3-3" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            ',
        'in_app' => true,
        'email' => true,
        'sort_order' => 8
    ],

    'task_time_extension_request' => [
        'label' => 'Task Time Extension Request',
        'group' => 'Requests & Approvals',
        'action' => 'task_time_extension_request',
        'icon_bg' => '#22C55E',
        'icon' => '
            <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="30" cy="30" r="30" fill="#22C55E"/>
                <rect x="18" y="18" width="16" height="22" rx="3" fill="white"/>
                <path d="M22 24h8M22 29h8M22 34h5" stroke="#22C55E" stroke-width="2" stroke-linecap="round"/>
                <circle cx="39" cy="31" r="8" stroke="white" stroke-width="2.5"/>
                <path d="M39 27v4l3 2" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            ',
        'in_app' => true,
        'email' => true,
        'sort_order' => 9
    ],

];
