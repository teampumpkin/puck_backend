<?php

namespace App\Services;

use App\Contracts\ErrorTrackerInterface;
use Throwable;

class SentryErrorTracker implements ErrorTrackerInterface
{
    public function __construct()
    {
        // Ensure Sentry is initialized
        $this->ensureSentryInitialized();
    }

    /**
     * Ensure Sentry SDK is properly initialized
     */
    private function ensureSentryInitialized(): void
    {
        if (!function_exists('sentry_capture_exception')) {
            $dsn = config('sentry.dsn');
            if ($dsn) {
                \Sentry\init([
                    'dsn' => $dsn,
                    'environment' => config('sentry.environment'),
                    'sample_rate' => config('sentry.sample_rate', 1.0),
                    'traces_sample_rate' => config('sentry.traces_sample_rate'),
                    'send_default_pii' => config('sentry.send_default_pii', false),
                ]);
            }
        }
    }
    /**
     * Capture and log an exception to Sentry
     *
     * @param Throwable $exception
     * @param array $context
     * @return void
     */
    public function captureException(Throwable $exception, array $context = []): void
    {
        try {
            \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($context) {
                // Set environment from ENVIRONMENT variable
                $environment = env('ENVIRONMENT', env('APP_ENV', 'production'));
                $scope->setTag('environment', $environment);

                // Add custom context
                if (!empty($context)) {
                    foreach ($context as $key => $value) {
                        $scope->setContext($key, is_array($value) ? $value : ['value' => $value]);
                    }
                }
            });

            // Use Sentry SDK directly
            \Sentry\captureException($exception);

            // Log for debugging
            \Log::info('Sentry: Exception captured', [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'context' => $context,
            ]);

        } catch (\Throwable $e) {
            // Fail silently but log the error
            \Log::error('Sentry capture failed: ' . $e->getMessage());
        }
    }

    /**
     * Capture a message to Sentry
     *
     * @param string $message
     * @param string $level
     * @param array $context
     * @return void
     */
    public function captureMessage(string $message, string $level = 'info', array $context = []): void
    {
        try {
            \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($context) {
                // Set environment from ENVIRONMENT variable
                $environment = env('ENVIRONMENT', env('APP_ENV', 'production'));
                $scope->setTag('environment', $environment);

                // Add custom context
                if (!empty($context)) {
                    foreach ($context as $key => $value) {
                        $scope->setContext($key, is_array($value) ? $value : ['value' => $value]);
                    }
                }
            });

            // Use Sentry SDK directly
            \Sentry\captureMessage($message, $this->mapLevel($level));

            // Log for debugging
            \Log::info('Sentry: Message captured', [
                'message' => $message,
                'level' => $level,
                'context' => $context,
            ]);

        } catch (\Throwable $e) {
            // Fail silently but log the error
            \Log::error('Sentry capture message failed: ' . $e->getMessage());
        }
    }

    /**
     * Map level string to Sentry severity
     *
     * @param string $level
     * @return \Sentry\Severity
     */
    private function mapLevel(string $level): \Sentry\Severity
    {
        return match ($level) {
            'debug' => \Sentry\Severity::debug(),
            'info' => \Sentry\Severity::info(),
            'warning' => \Sentry\Severity::warning(),
            'error' => \Sentry\Severity::error(),
            'fatal' => \Sentry\Severity::fatal(),
            default => \Sentry\Severity::info(),
        };
    }
}
