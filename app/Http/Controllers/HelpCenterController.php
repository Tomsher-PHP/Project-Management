<?php

namespace App\Http\Controllers;

use App\Models\FeatureUpdate;
use App\Models\FeatureUpdateUserStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class HelpCenterController extends Controller
{
    /**
     * Display the Help Center home.
     */
    public function index(): View
    {
        $articles = $this->articles();

        return view('help-center.index', [
            'categories' => $this->categories($articles),
            'articles' => $articles,
            'searchIndex' => $this->searchIndex($articles),
            'currentArticle' => null,
        ]);
    }

    /**
     * Display a single Help Center announcement.
     *
     * Opening an announcement automatically marks it as read.
     */
    public function show(string $article): View
    {
        // $user = auth()->user();

        // Find the currently published announcement.
        $featureUpdate = FeatureUpdate::query()
            ->where('slug', $article)
            ->where('status', 'published')
            ->where(function ($query) {
                $query->whereNull('publish_at')
                    ->orWhere('publish_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->firstOrFail();


            $user = auth()->user();

            if ($user) {
                FeatureUpdateUserStatus::updateOrCreate(
                    [
                        'feature_update_id' => $featureUpdate->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'acknowledged_at' => now(),
                        'skipped_at' => null,
                    ]
                );
            }


        // Mark this announcement as read for the current user.
        FeatureUpdateUserStatus::updateOrCreate(
            [
                'feature_update_id' => $featureUpdate->id,
                'user_id' => $user->id,
            ],
            [
                'acknowledged_at' => now(),
                'skipped_at' => null,
            ]
        );

        // Fetch articles AFTER updating the read status.
        $articles = $this->articles();

        $currentArticle = $articles->firstWhere('slug', $article);

        abort_if(blank($currentArticle), 404);

        $currentIndex = $articles->search(
            fn (array $item): bool => $item['slug'] === $article
        );

        return view('help-center.show', [
            'categories' => $this->categories($articles),
            'articles' => $articles,
            'searchIndex' => $this->searchIndex($articles),
            'currentArticle' => $currentArticle,
            'previousArticle' => $articles->get($currentIndex - 1),
            'nextArticle' => $articles->get($currentIndex + 1),
        ]);
    }

    /**
     * Get all currently published feature announcements.
     *
     * Help Center displays all published announcements,
     * regardless of their popup targeting.
     */
    private function publishedFeatures(): Collection
    {
        $userId = auth()->id();

        return FeatureUpdate::query()
            ->where('status', 'published')
            ->where(function ($query) {
                $query->whereNull('publish_at')
                    ->orWhere('publish_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->with([
                'userStatuses' => function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                },
            ])
            ->orderByDesc('publish_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Convert feature records into the Help Center article structure.
     */
    private function articles(): Collection
    {
        return $this->publishedFeatures()
            ->map(function (FeatureUpdate $feature): array {
                $userStatus = $feature->userStatuses->first();
                $category = $feature->module_name ?: 'General';

                $description = $feature->short_description
                    ?: strip_tags($feature->description ?? '');

                $article = [
                    'id' => $feature->id,
                    'slug' => $feature->slug,
                    'title' => $feature->title,
                    'description' => $description,
                    'content' => $feature->description,
                    'instructions' => $feature->instructions,
                    'feature_type' => $feature->feature_type,
                    'module_name' => $feature->module_name,
                    'icon' => $feature->icon,
                    'cover_image' => $feature->cover_image,
                    'screenshots' => $feature->screenshots ?? [],
                    'video_url' => $feature->video_url,
                    'category' => $category,
                    'is_read' => !is_null($userStatus?->acknowledged_at),
                    'is_unread' => is_null($userStatus?->acknowledged_at),
                    'is_skipped' => !is_null($userStatus?->skipped_at),
                    'published_at' => $feature->publish_at,
                    'url' => route('help-center.show', $feature->slug),
                ];

                $article['searchable'] = trim(implode(' ', [
                    $article['title'],
                    $article['description'],
                    $article['content'],
                    $article['instructions'],
                    $article['feature_type'],
                    $article['module_name'],
                    $article['category'],
                ]));

                return $article;
            })
            ->values();
    }

    /**
     * Group announcements by module for Help Center navigation.
     */
    private function categories(Collection $articles): array
    {
        return $articles
            ->groupBy('category')
            ->map(function (Collection $items, string $category) {
                return [
                    'title' => $category,
                    'articles' => $items->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Build the Help Center search index.
     */
    private function searchIndex(Collection $articles): Collection
    {
        return $articles
            ->map(fn (array $article): array => [
                'title' => $article['title'],
                'description' => $article['description'],
                'url' => $article['url'],
                'searchable' => $article['searchable'],
                'is_read' => $article['is_read'],
            ])
            ->values();
    }
}
