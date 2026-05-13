<?php

namespace App\View\Composers;

use App\Services\Layout\RequestMenuBadgeService;
use Illuminate\View\View;

class SidebarComposer
{
    public function __construct(
        private readonly RequestMenuBadgeService $badgeService
    ) {
    }

    public function compose(View $view): void
    {
        $view->with(
            'requestMenuBadges',
            $this->badgeService->getPendingCountsForUser(auth()->user())
        );
    }
}
