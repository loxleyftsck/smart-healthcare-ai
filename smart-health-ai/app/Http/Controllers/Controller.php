<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Smart Healthcare AI API",
 *     description="REST API for AI-powered Smart Healthcare Assistant System",
 *     @OA\Contact(
 *         email="admin@smarthealthcare.ai"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 *
 * @OA\Tag(name="Auth", description="Authentication endpoints (JWT)")
 * @OA\Tag(name="Patients", description="Patient management endpoints")
 * @OA\Tag(name="Consultations", description="AI-powered consultation endpoints")
 * @OA\Tag(name="Health", description="System health check")
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
