<?php

namespace App\View\Components;

use App\Services\Task\RunningTaskNavbarService;
use Illuminate\View\Component;
use Illuminate\View\View;

class RunningTaskBar extends Component
{
    protected array $state;

    public function __construct(RunningTaskNavbarService $runningTaskNavbarService)
    {
        $this->state = $runningTaskNavbarService->getForUser(auth()->id());
    }

    public function render(): View
    {
        return view('components.running-task-bar', $this->state);
    }
}
