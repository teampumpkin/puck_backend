<?php

namespace App\Console\Commands;

use App\Models\PrcModule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class SyncRouteModules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'route:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync routes in database';

    private $prc_module;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->prc_module = new PrcModule();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $exclude_route = [
            'api/docs',
            'api/oauth2-callback',
            'api/test',
            'api/register',
            'api/login',
            'api/forgot-password',
            'api/reset-password',
            'api/verify-token',
            'api/verify-otp',
            'api/save-subscription'
        ];

        $count = 0;
        foreach (Route::getRoutes()->getIterator() as $route) {
            if (strpos($route->uri, 'api') !== false) {
                if (!in_array($route->uri, $exclude_route)) {
                    $api_route = str_replace('api/', '', $route->uri);
                    $module    = $this->prc_module->where('api_route', $api_route)->first();

                    if (empty($module)) {
                        $count++;
                        $module_name = str_replace('api/', '', $api_route);
                        $module_name = ucwords(str_replace('-', ' ', $module_name));

                        $this->prc_module->create([
                            'module_name' => $module_name,
                            'api_route'   => $api_route
                        ]);
                    }
                }
            }
        }
        if ($count == 0) {
            $this->line('<fg=red>Nothing to sync</>');
            return 0;
        }
        $this->line('<fg=green>' . $count . ' routes have been sync</>');
        return 0;
    }
}
