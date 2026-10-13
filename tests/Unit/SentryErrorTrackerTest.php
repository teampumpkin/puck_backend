<?php

namespace Tests\Unit;

use App\Contracts\ErrorTrackerInterface;
use Sentry\Integration\ErrorListenerIntegration;
use Sentry\SentrySdk;
use Tests\TestCase;

// Sentry PHP-LARAVEL-9/E/1N/1X/15/D/13/24: building the tracker re-ran \Sentry\init(),
// replacing sentry-laravel's hub and re-adding the PHP error listener, so every
// PHP 8.2 deprecation in framework code was reported.
class SentryErrorTrackerTest extends TestCase
{
    public function test_resolving_tracker_keeps_laravels_sentry_client(): void
    {
        config(['sentry.dsn' => 'https://public@sentry.example.com/1']);
        $this->app->forgetInstance('sentry');
        $laravelHub = $this->app->make('sentry');
        SentrySdk::setCurrentHub($laravelHub);
        $client = $laravelHub->getClient();

        $this->app->make(ErrorTrackerInterface::class);

        $this->assertSame($client, SentrySdk::getCurrentHub()->getClient());
        $this->assertNull(SentrySdk::getCurrentHub()->getClient()->getIntegration(ErrorListenerIntegration::class));
    }
}
