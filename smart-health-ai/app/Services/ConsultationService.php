<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\TriageLog;
use App\Services\AiTriageService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * ConsultationService - Business logic for conversation management
 * PATH B: Integrated with QueryCacheService for performance
 */
class ConsultationService
{
    protected AiTriageService $aiTriageService;
    protected QueryCacheService $cacheService;

    public function __construct(AiTriageService $aiTriageService, QueryCacheService $cacheService)
    {
        $this->aiTriageService = $aiTriageService;
        $this->cacheService = $cacheService;
    }

    /**
     * Get all consultations paginated (with caching)
     *
     * @param int $perPage
     * @param int $page
     * @return LengthAwarePaginator
     */
    public function getAll(int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        // Note: Paginated data changes frequently, use shorter TTL
        return $this->cacheService->rememberConsultations(
            0,  // Use 0 as placeholder for "all consultations"
            $perPage,
            fn() => Consultation::with('patient')->latest()->paginate($perPage, ['*'], 'page', $page),
            QueryCacheService::TTL_SHORT
        );
    }

    /**
     * Find consultation by ID (with caching)
     *
     * @param int $id
     * @return Consultation
     */
    public function findOrFail(int $id): Consultation
    {
        return $this->cacheService->rememberConsultation(
            $id,
            fn() => Consultation::with(['patient', 'triageLogs'])->findOrFail($id),
            QueryCacheService::TTL_REALTIME
        );
    }

    /**
     * Create a consultation and process AI triage (invalidates caches)
     *
     * @param array $data
     * @return Consultation
     */
    public function createWithAiTriage(array $data): Consultation
    {
        $sessionId = $data['session_id'] ?? (string) Str::uuid();

        // 1. Get AI triage analysis
        $triageResult = $this->aiTriageService->analyze($data['message']);

        // 2. Create consultation record
        $consultation = Consultation::create([
            'patient_id' => $data['patient_id'] ?? null,
            'session_id' => $sessionId,
            'message'    => $data['message'],
            'intent'     => $triageResult['intent'],
            'response'   => $triageResult['response'],
        ]);

        // 3. Log triage result
        TriageLog::create([
            'patient_id'     => $data['patient_id'] ?? null,
            'consultation_id' => $consultation->id,
            'symptoms'       => $triageResult['symptoms'],
            'severity'       => $triageResult['severity'],
            'confidence'     => $triageResult['confidence'],
            'recommendation' => $triageResult['recommendation'],
        ]);

        // 4. Invalidate caches
        if ($data['patient_id'] ?? null) {
            $this->cacheService->invalidatePatientConsultations($data['patient_id']);
        }
        $this->cacheService->invalidateTriage($sessionId);

        return $consultation->load('triageLogs');
    }

    /**
     * Create consultation with explicit parameters (invalidates caches)
     * 
     * @param int|null $patient_id
     * @param string $message
     * @param string $response
     * @param string|null $intent
     * @param string|null $session_id
     * @param array|null $triage_result
     * @return Consultation
     */
    public function create(
        ?int $patient_id = null,
        string $message = '',
        string $response = '',
        ?string $intent = null,
        ?string $session_id = null,
        ?array $triage_result = null
    ): Consultation {
        $sessionId = $session_id ?? (string) Str::uuid();

        // Create consultation record
        $consultation = Consultation::create([
            'patient_id' => $patient_id,
            'session_id' => $sessionId,
            'message'    => $message,
            'intent'     => $intent ?? 'general',
            'response'   => $response,
        ]);

        // Log triage result if provided
        if ($triage_result) {
            TriageLog::create([
                'patient_id'      => $patient_id,
                'consultation_id' => $consultation->id,
                'symptoms'        => json_encode($triage_result['symptoms'] ?? []),
                'severity'        => $triage_result['severity'] ?? 'UNKNOWN',
                'confidence'      => $triage_result['confidence'] ?? 0.0,
                'recommendation'  => $triage_result['recommendation'] ?? '',
            ]);
        }

        // Invalidate caches
        if ($patient_id) {
            $this->cacheService->invalidatePatientConsultations($patient_id);
        }
        $this->cacheService->invalidateTriage($sessionId);

        return $consultation->load('triageLogs');
    }

    /**
     * Get conversation history for a session (cached)
     * 
     * @param string|null $sessionId
     * @param int $limit
     * @return array
     */
    public function getSessionHistory(?string $sessionId, int $limit = 5): array
    {
        if (!$sessionId) {
            return [];
        }

        // Cache with very short TTL since conversations are active
        return $this->cacheService->rememberTriage(
            $sessionId,
            function () use ($sessionId, $limit) {
                $consultations = Consultation::where('session_id', $sessionId)
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();

                $history = [];
                foreach ($consultations->reverse() as $consultation) {
                    $history[] = [
                        'role' => 'user',
                        'content' => $consultation->message,
                        'timestamp' => $consultation->created_at->toIso8601String(),
                    ];

                    if ($consultation->response) {
                        $history[] = [
                            'role' => 'assistant',
                            'content' => $consultation->response,
                            'timestamp' => $consultation->created_at->toIso8601String(),
                        ];
                    }
                }

                return $history;
            },
            QueryCacheService::TTL_REALTIME
        );
    }
}
