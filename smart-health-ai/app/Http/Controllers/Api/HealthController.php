<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiTriageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    private AiTriageService $aiService;

    public function __construct(AiTriageService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Check application health status.
     */
    public function index(): JsonResponse
    {
        $dbStatus = true;
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $dbStatus = false;
        }

        $aiStatus = $this->aiService->isHealthy();

        $overall = $dbStatus && $aiStatus;

        return response()->json([
            'status' => $overall ? 'success' : 'partial_failure',
            'health' => [
                'database' => $dbStatus ? 'connected' : 'disconnected',
                'ai_triage' => $aiStatus ? 'active' : 'unavailable',
            ],
            'timestamp' => now()->toIso8601String(),
        ], $overall ? 200 : 503);
    }
}
