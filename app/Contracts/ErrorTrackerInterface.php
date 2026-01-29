<?php

namespace App\Contracts;

use Throwable;

interface ErrorTrackerInterface
{
    /**
     * Capture and log an exception
     *
     * @param Throwable $exception
     * @param array $context Additional context data
     * @return void
     */
    public function captureException(Throwable $exception, array $context = []): void;

    /**
     * Capture a message
     *
     * @param string $message
     * @param string $level (info, warning, error, etc.)
     * @param array $context
     * @return void
     */
    public function captureMessage(string $message, string $level = 'info', array $context = []): void;
}
