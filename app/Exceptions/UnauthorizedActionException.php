<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedActionException extends Exception
{
    protected string $pageTitle;
    protected string $subTitle;

    public function __construct()
    {
        $this->pageTitle = 'Unauthorized';
        $this->subTitle = 'You do not have permission to access this page.';
        view()->share(['pageTitle' => $this->pageTitle, 'subTitle' => $this->subTitle]);
    }

    public function render($request)
    {
        return response()->view('errors.403', [], 403);
    }
}
