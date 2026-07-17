<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="Everyday Store API",
 *     version="1.0.0",
 *     description="API documentation for the Everyday Store Backend System",
 *     @OA\Contact(
 *         email="support@everydaypak.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url="/api",
 *     description="Primary API Gateway"
 * )
 *
 * @OA\SecurityScheme(
 *     type="http",
 *     securityScheme="sanctum",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your Sanctum token to authenticate requests"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
