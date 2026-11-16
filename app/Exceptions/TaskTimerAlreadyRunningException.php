<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TaskTimerAlreadyRunningException extends RuntimeException
{
    protected array $restriction;

    public function __construct(array $restriction, ?Throwable $previous = null)
    {
        parent::__construct($restriction['message'] ?? 'Timer already running', 0, $previous);
        $this->restriction = $restriction;
    }

    public function getRestriction(): array
    {
        return $this->restriction;
    }

    public function render($request): JsonResponse
    {
        $response = ['message' => $this->restriction['message'] ?? 'Timer already running'];

        if (($this->restriction['reason'] ?? null) === 'running_timer_exists') {
            $response['requires_confirmation'] = true;
            $response['running_task_id'] = $this->restriction['running_task_id'] ?? null;
            $response['running_task_name'] = $this->restriction['running_task_name'] ?? null;
            $response['running_task_assignee_name'] = $this->restriction['running_task_assignee_name'] ?? null;
        }

        return response()->json(
            $response,
            $this->restriction['status'] ?? Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }
}
