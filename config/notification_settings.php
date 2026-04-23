<?php

return [

    'shift_assigned' => [
        'label' => 'Shift Scheduled',
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

    // NEW 1: Team Assigned
    'team_assigned' => [
        'label' => 'Team Assigned',
        'action' => 'team_assigned',
        'icon_bg' => '#3B82F6',
        'icon' => '
            <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="30" cy="30" r="30" fill="#3B82F6"/>
                <path d="M22 26a4 4 0 1 1 8 0a4 4 0 1 1-8 0zm10 2c0-2.2 1.8-4 4-4s4 1.8 4 4-1.8 4-4 4-4-1.8-4-4zm-14 12c0-4 4-6 8-6s8 2 8 6v2H18v-2zm16 0c.2-1.5.8-2.8 2-3.8c1.2-1 2.8-1.2 4.5-1.2V40h-6.5z" fill="white"/>
            </svg>
        ',
        'in_app' => true,
        'email' => true,
        'sort_order' => 4
    ],

    // NEW 2: Task Status Change
    'task_status_changed' => [
        'label' => 'Task Status Updated',
        'action' => 'task_status_changed',
        'icon_bg' => '#8B5CF6',
        'icon' => '
            <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="30" cy="30" r="30" fill="#8B5CF6"/>

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

];