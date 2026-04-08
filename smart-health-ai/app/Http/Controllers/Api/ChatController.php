<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatRequest;
use App\Http\Resources\ConsultationResource;
use App\Services\LocalLlmService;
use App\Services\IntentDetectorService;
use App\Services\AiTriageService;
use App\Services\ConsultationService;
use App\Services\PromptCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Chat Controller
 * 
 * Handles chat messages with local Mistral 7B LLM.
 * Integrates intent detection, conversation history, and AI triage.
 * 
 * @OA\Tag(name="Chat", description="Chat with Mistral 7B healthcare assistant")
 */
class ChatController extends Controller
{
    public function __construct(
        private LocalLlmService $llmService,
        private IntentDetectorService $intentDetector,
        private AiTriageService $aiTriageService,
        private ConsultationService $consultationService,
    ) {
        // Pre-warm cache on first request
        PromptCacheService::warmCache();
    }

    /**
     * Send message to healthcare chatbot
     * 
     * @OA\Post(
     *     path="/api/chat",
     *     tags={"Chat"},
     *     summary="Send message to Mistral 7B healthcare assistant",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"message"},
     *             @OA\Property(property="message", type="string", example="Saya demam 39 derajat"),
     *             @OA\Property(property="session_id", type="string", example="uuid-session-id"),
     *             @OA\Property(property="patient_id", type="integer", example=1),
     *             @OA\Property(property="include_history", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Chat response with intent detection and optional triage",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="response", type="string"),
     *                 @OA\Property(property="intent", type="string"),
     *                 @OA\Property(property="session_id", type="string"),
     *                 @OA\Property(property="consultation_id", type="integer"),
     *                 @OA\Property(property="triage", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error (LLM unavailable)")
     * )
     */
    public function chat(ChatRequest $request): JsonResponse
    {
        $patient = auth()->user();
        $message = $request->validated()['message'];
        $sessionId = $request->validated()['session_id'] ?? null;
        $includeHistory = $request->validated()['include_history'] ?? true;

        try {
            // 1. Detect intent
            $intent = $this->intentDetector->detect($message);
            Log::info('Chat intent detected', [
                'intent' => $intent->value,
                'user_id' => $patient->id,
                'message' => substr($message, 0, 100)
            ]);

            // 2. Get conversation history (last 5 messages)
            $history = [];
            if ($includeHistory && $sessionId) {
                $history = $this->consultationService->getSessionHistory($sessionId, limit: 5);
            }

            // 3. Get patient context
            $patientContext = [
                'patient_name' => $patient->name,
                'age' => $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->age : null,
                'gender' => $patient->gender,
                'medical_history' => $patient->medical_history ?? 'Tidak ada',
                'current_medications' => $patient->current_medications ?? 'Tidak ada',
                'allergies' => $patient->allergies ?? 'Tidak ada',
            ];

            // 4. Generate response using Mistral 7B (with intent-aware optimization)
            $response = $this->llmService->generate(
                message: $message,
                context: $patientContext,
                history: $history,
                intent: $intent  // 🎯 OPTIMIZATION: Pass intent for parameter tuning
            );

            // 5. If symptom query or emergency, run triage
            $triageResult = null;
            if ($intent->requiresTriage()) {
                $triageResult = $this->aiTriageService->analyze($message);
                
                // Append triage result to response if severity is HIGH
                if ($triageResult['severity'] === 'HIGH') {
                    $response .= "\n\n⚠️ **Assessment: URGENT**\n"
                               . $triageResult['recommendation'];
                }
            }

            // 6. Save to database
            $consultation = $this->consultationService->create(
                patient_id: $patient->id,
                message: $message,
                response: $response,
                intent: $intent->value,
                session_id: $sessionId,
                triage_result: $triageResult,
            );

            Log::info('Chat response generated', [
                'consultation_id' => $consultation->id,
                'intent' => $intent->value,
                'severity' => $triageResult['severity'] ?? 'N/A',
                'user_id' => $patient->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Response generated successfully',
                'data' => [
                    'response' => $response,
                    'intent' => $intent->value,
                    'triage' => $triageResult,
                    'session_id' => $consultation->session_id,
                    'consultation_id' => $consultation->id,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Chat error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $patient->id ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses pesan. Silakan coba lagi.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Check LLM service status
     * 
     * @OA\Get(
     *     path="/api/chat/status",
     *     tags={"Chat"},
     *     summary="Check Mistral 7B LLM service status",
     *     @OA\Response(response=200, description="Service status")
     * )
     */
    public function status(): JsonResponse
    {
        $isAvailable = $this->llmService->isAvailable();
        $modelInfo = $isAvailable ? $this->llmService->getModelInfo() : [];

        return response()->json([
            'success' => true,
            'data' => [
                'llm_available' => $isAvailable,
                'service_url' => config('services.ollama.url', 'http://localhost:11434'),
                'model' => config('services.ollama.model', 'mistral'),
                'models' => $modelInfo,
                'message' => $isAvailable ? 'Mistral 7B service is running' : 'Mistral 7B service is unavailable',
            ],
        ]);
    }
}
