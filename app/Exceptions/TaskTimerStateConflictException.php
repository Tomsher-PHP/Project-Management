<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TaskTimerStateConflictException extends RuntimeException
{
    protected ?int $currentRunningTaskId;

    public function __construct(
        string $message = 'The running task timer state has changed. Please review and try again.',
        ?int $currentRunningTaskId = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->currentRunningTaskId = $currentRunningTaskId;
    }

    public function getCurrentRunningTaskId(): ?int
    {
        return $this->currentRunningTaskId;
    }

    public function render($request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'conflict' => true,
            'current_running_task_id' => $this->currentRunningTaskId,
        ], Response::HTTP_CONFLICT);
    }
}
