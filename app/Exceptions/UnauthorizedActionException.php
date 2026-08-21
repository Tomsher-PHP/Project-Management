<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedActionException extends Exception
{
    protected string $pageTitle;

    public function __construct()
    {
        $this->pageTitle = 'Unauthorized';
        view()->share(['pageTitle' => $this->pageTitle]);
    }

    public function render($request)
    {
        return response()->view('errors.error-page', [], 403);
    }
}
