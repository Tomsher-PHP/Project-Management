<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class HelpCenterController extends Controller
{
    /**
     * Display the Help Center home.
     */
    public function index(): View
    {
        return view('help-center.index', [
            'categories' => $this->categories(),
            'articles' => $this->articles(),
            'searchIndex' => $this->searchIndex(),
            'currentArticle' => null,
        ]);
    }

    /**
     * Display a single Help Center article.
     */
    public function show(string $article): View
    {
        $articles = $this->articles();
        $currentArticle = $articles->firstWhere('slug', $article);

        abort_if(blank($currentArticle), 404);

        $currentIndex = $articles->search(fn (array $item): bool => $item['slug'] === $article);

        return view('help-center.show', [
            'categories' => $this->categories(),
            'articles' => $articles,
            'searchIndex' => $this->searchIndex(),
            'currentArticle' => $currentArticle,
            'previousArticle' => $articles->get($currentIndex - 1),
            'nextArticle' => $articles->get($currentIndex + 1),
        ]);
    }

    /**
     * Central Help Center registry.
     *
     * Add future modules here so the home, sidebar, article pages, and search
     * stay in sync without duplicating navigation logic.
     */
    private function categories(): array
    {
        return [
            [
                'title' => 'Project Management',
                'articles' => [
                    [
                        'slug' => 'start-assigned-task',
                        'title' => 'How do I start working on an assigned task?',
                        'description' => 'Learn how to move an assigned task into progress, start the timer, and complete or pause work safely.',
                        'keywords' => 'assigned task workspace kanban board open todo progressing progress timer play completed on hold agile linear',
                        'view' => 'help-center.articles.start-assigned-task',
                    ],
                    [
                        'slug' => 'no-task-assigned',
                        'title' => 'What should I do if no task is assigned to me?',
                        'description' => 'Create a task request with the right project, priority, estimate, and details when no work is assigned.',
                        'keywords' => 'no task assigned task request reporter approve reject estimated time priority project description working hours',
                        'view' => 'help-center.articles.no-task-assigned',
                    ],
                    [
                        'slug' => 'request-more-estimated-time',
                        'title' => 'How do I request more estimated time for a task?',
                        'description' => 'Request an estimate change when a task needs more approved working time.',
                        'keywords' => 'request estimate change estimated hours minutes approval modal new estimate time extension expires',
                        'view' => 'help-center.articles.request-more-estimated-time',
                    ],
                    [
                        'slug' => 'task-management-best-practices',
                        'title' => 'What are the best practices for task management?',
                        'description' => 'Follow the core timer, status, estimate, and task-request habits that keep project reports accurate.',
                        'keywords' => 'best practices task management reporting timer stop start status updated estimate extension task request',
                        'view' => 'help-center.articles.task-management-best-practices',
                    ],
                ],
            ],
        ];
    }

    private function articles(): Collection
    {
        return collect($this->categories())
            ->flatMap(fn (array $category) => collect($category['articles'])->map(function (array $article) use ($category): array {
                $article['category'] = $category['title'];
                $article['url'] = route('help-center.show', $article['slug']);
                $article['searchable'] = trim(implode(' ', [
                    $article['title'],
                    $article['description'],
                    $article['keywords'],
                    $article['category'],
                ]));

                return $article;
            }))
            ->values();
    }

    private function searchIndex(): Collection
    {
        return $this->articles()
            ->map(fn (array $article): array => [
                'title' => $article['title'],
                'description' => $article['description'],
                'url' => $article['url'],
                'searchable' => $article['searchable'],
            ])
            ->values();
    }
}
