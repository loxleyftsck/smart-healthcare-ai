<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(append: [
            \App\Http\Middleware\RequestLoggingMiddleware::class,
            \App\Http\Middleware\ResponseCompressionMiddleware::class, // PATH B Day 3: Response compression
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                $statusCode = 500;
                
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    $statusCode = 422;
                    $errors = $e->errors();
                } elseif ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException || $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    $statusCode = 404;
                    $errors = ['error' => 'Resource not found.'];
                } elseif ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    $statusCode = 401;
                    $errors = ['error' => 'Unauthenticated.'];
                } else {
                    $errors = ['error' => $e->getMessage()];
                }

                return response()->json([
                    'success' => false,
                    'message' => $statusCode === 500 ? 'Internal Server Error' : $e->getMessage(),
                    'errors' => $errors,
                    'meta' => ['timestamp' => now()->toIso8601String()]
                ], $statusCode);
            }
        });
    })->create();
