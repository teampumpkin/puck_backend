<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;

/**
 * Class CheckUser
 * @package App\Http\Middleware
 */
class CheckUser
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
        try {
            $route = $request->path();
            $route = str_replace('api/', '', $route);

            if (empty($request->header('Authorization'))) {
                return response()
                    ->json([
                        'success' => false,
                        'code'    => 406,
                        'message' => __('messages.user_not_found_or_token_expire')
                    ]);
            }
            $user = checkUserAccess($request->header('Authorization'), $route);
            if (!$user) {
                return response()
                    ->json([
                        'success' => false,
                        'code'    => 406,
                        'message' => __('messages.user_not_found_or_token_expire')
                    ]);
            }
        } catch (Exception $e) {
            if ($e->getCode() == 200) {
                return response()
                    ->json([
                        'success' => false,
                        'code'    => 407,
                        'message' => $e->getMessage()
                    ]);
            }
        }

        return $next($request);
    }
}
