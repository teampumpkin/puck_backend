<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HandleSwaggerDocAccess
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
        if (empty(env('ENABLE_SWAGGER_DOC')) || !env('ENABLE_SWAGGER_DOC')) {
            return false;
        }
        return $next($request);
    }
}
