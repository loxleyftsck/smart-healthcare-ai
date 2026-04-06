<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RequestLoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);

        // Log Incoming Request
        Log::info('Incoming API Request', [
            'method' => $request->method(),
            'url'    => $request->fullUrl(),
            'ip'     => $request->ip(),
            'user_agent' => $request->userAgent(),
            'body'   => $request->except(['password', 'password_confirmation']),
        ]);

        $response = $next($request);

        $duration = microtime(true) - $startTime;

        // Log Response Output
        Log::info('Outgoing API Response', [
            'status'   => $response->getStatusCode(),
            'duration' => round($duration * 1000, 2) . 'ms',
        ]);

        return $response;
    }
}
