<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(title="PRC API", version="0.1"),
 * @OA\SecurityScheme(
 *   type="apiKey",
 *   description="Authentication Token",
 *   name="Authorization",
 *   in="header",
 *   securityScheme="apiAuth"
 * ),
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
