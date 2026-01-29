<?php

namespace App\Services;

use App\Contracts\ErrorTrackerInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Simple Log-based error tracker
 * Use this as an alternative to Sentry or when Sentry is not needed
 */
class LogErrorTracker implements ErrorTrackerInterface
{
    /**
     * Capture and log an exception to Laravel log
     *
     * @param Throwable $exception
     * @param array $context
     * @return void
     */
    public function captureException(Throwable $exception, array $context = []): void
    {
        Log::error($exception->getMessage(), array_merge([
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ], $context));
    }

    /**
     * Capture a message to Laravel log
     *
     * @param string $message
     * @param string $level
     * @param array $context
     * @return void
     */
    public function captureMessage(string $message, string $level = 'info', array $context = []): void
    {
        Log::log($level, $message, $context);
    }
}
