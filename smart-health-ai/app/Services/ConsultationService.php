<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\TriageLog;
use App\Services\AiTriageService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class ConsultationService
{
    protected AiTriageService $aiTriageService;

    public function __construct(AiTriageService $aiTriageService)
    {
        $this->aiTriageService = $aiTriageService;
    }

    public function getAll(int $perPage = 15): LengthAwarePaginator
    {
        return Consultation::with('patient')->latest()->paginate($perPage);
    }

    public function findOrFail(int $id): Consultation
    {
        return Consultation::with(['patient', 'triageLogs'])->findOrFail($id);
    }

    /**
     * Create a consultation and process AI triage.
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

        return $consultation->load('triageLogs');
    }
}
