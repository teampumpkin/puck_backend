<?php

namespace App\Providers;

use App\Models\V4PlayerPortfolio;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Bind ErrorTracker interface to Sentry implementation
        $this->app->bind(
            \App\Contracts\ErrorTrackerInterface::class,
            \App\Services\SentryErrorTracker::class
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        require_once app_path() . '/Helpers/helpers.php';

        // morphMap (NOT enforceMorphMap — existing polymorphic rows store FQCNs and must keep resolving)
        Relation::morphMap([
            'portfolio' => V4PlayerPortfolio::class,
        ]);

        if (env('APP_DOMAIN') === '' || empty(env('APP_DOMAIN'))) {
            URL::forceScheme('https');
        }

        if ($this->isProduction()) {
            // DB::prohibitDestructiveCommands();
        }
    }

    /**
     * Custom method to check if production environment.
     *
     * @return bool
     */
    protected function isProduction()
    {
        return config('app.env') === 'production';
    }
}
