<?php

namespace App\Http\Middleware;

use App\Models\APILog;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;

/**
 *
 */
class APILogMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    /**
     * @param $request
     * @param $response
     */
    public function terminate($request, $response)
    {
        $url   = $request->fullUrl();
        $route = $request->path();

        if (!in_array($route, ['api-docs.json', 'api/docs', 'api/api-logs', '_ignition/execute-solution', 'api/delete-api-logs'])) {
            APILog::where('created_at', '<', Carbon::now()->subDays(2))
                ->delete();

            $ip = $request->ip();

            $api_log = new APILog();

            $api_log->ip       = $ip;
            $api_log->route    = $route;
            $api_log->url      = $url;
            $api_log->header   = $request->header('Authorization');
            $api_log->request  = json_encode($request->all());
            $api_log->response = (!empty($response)) ? json_encode($response) : '';

            $api_log->save();
        }
    }
}
