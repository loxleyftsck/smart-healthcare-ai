<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConsultationController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\HealthController;

/*
|--------------------------------------------------------------------------
| API Routes — Smart Healthcare AI
|--------------------------------------------------------------------------
|
| Flow: Public routes → JWT-protected routes
| Standard: RESTful JSON API, Conventional Commits
|
*/

// ────────────────────────────────────────────────────────────────────────
// Public Routes
// ────────────────────────────────────────────────────────────────────────

/**
 * @OA\Get(
 *     path="/api/health",
 *     tags={"Health"},
 *     summary="System health check",
 *     @OA\Response(response=200, description="System is healthy")
 * )
 */
Route::get('/health', [HealthController::class, 'index']);

Route::prefix('auth')->group(function () {
    /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     tags={"Auth"},
     *     summary="Register a new user",
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", minLength=12, example="SecurePassword123!")
     *         )
     *     ),
     *     @OA\Response(response=201, description="User registered"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    Route::post('/register', [AuthController::class, 'register']);

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     tags={"Auth"},
     *     summary="Login and receive JWT token",
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="JWT Token returned"),
     *     @OA\Response(response=401, description="Invalid credentials")
     * )
     */
    Route::post('/login', [AuthController::class, 'login']);
});

// ────────────────────────────────────────────────────────────────────────
// Protected Routes (JWT Required)
// ────────────────────────────────────────────────────────────────────────
Route::middleware('auth:api')->group(function () {

    // Auth management
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // Patient CRUD
    Route::apiResource('patients', PatientController::class);

    // AI Consultation
    Route::apiResource('consultations', ConsultationController::class)->only(['index', 'store', 'show']);
});
