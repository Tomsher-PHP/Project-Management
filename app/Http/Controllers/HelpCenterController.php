<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class HelpCenterController extends Controller
{
    /**
     * Display the Help Center.
     */
    public function index(): View
    {
        $categories = [
            [
                'title' => 'Project Management',
                'articles' => [
                    [
                        'id' => 'start-assigned-task',
                        'title' => 'How do I start working on an assigned task?',
                        'view' => 'help-center.articles.start-assigned-task',
                    ],
                    [
                        'id' => 'no-task-assigned',
                        'title' => 'What should I do if no task is assigned to me?',
                        'view' => 'help-center.articles.no-task-assigned',
                    ],
                    [
                        'id' => 'request-more-estimated-time',
                        'title' => 'How do I request more estimated time for a task?',
                        'view' => 'help-center.articles.request-more-estimated-time',
                    ],
                    [
                        'id' => 'task-management-best-practices',
                        'title' => 'What are the best practices for task management?',
                        'view' => 'help-center.articles.task-management-best-practices',
                    ],
                ],
            ],
        ];

        return view('help-center.index', compact('categories'));
    }
}
