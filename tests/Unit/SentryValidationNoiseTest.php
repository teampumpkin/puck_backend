<?php

namespace Tests\Unit;

use App\Contracts\ErrorTrackerInterface;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

// Sentry PHP-LARAVEL-19/1W/25: controllers' catch (ValidationException) blocks
// sent ordinary 422 user-input errors to Sentry as issues.
class SentryValidationNoiseTest extends TestCase
{
    private array $sent = [];

    private function tracker(): ErrorTrackerInterface
    {
        $tracker = $this->app->make(ErrorTrackerInterface::class);
        \Sentry\init([
            'dsn' => 'https://public@sentry.example.com/1',
            'before_send' => function ($event) {
                $this->sent[] = $event;
                return null;
            },
        ]);

        return $tracker;
    }

    public function test_validation_exceptions_are_not_sent(): void
    {
        $this->tracker()->captureException(ValidationException::withMessages(['phone' => 'taken']));

        $this->assertCount(0, $this->sent);
    }

    public function test_other_exceptions_are_still_sent(): void
    {
        $this->tracker()->captureException(new RuntimeException('boom'));

        $this->assertCount(1, $this->sent);
    }
}
