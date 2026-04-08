<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI Triage Service
 *
 * Communicates with the AI Microservice (Python/GenAI) for symptom analysis.
 * Falls back to rule-based triage when AI is unavailable.
 */
class AiTriageService
{
    private string $aiServiceUrl;
    private int $timeout;

    public function __construct()
    {
        $this->aiServiceUrl = config('services.ai_triage.url', 'http://localhost:8001');
        $this->timeout = config('services.ai_triage.timeout', 10);
    }

    /**
     * Analyze patient message and return triage recommendation.
     *
     * @param string $message
     * @return array{intent: string, response: string, symptoms: array, severity: string, confidence: float, recommendation: string}
     */
    public function analyze(string $message): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->aiServiceUrl}/triage", [
                    'message' => $message,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('AI triage service returned error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('AI triage service unavailable, using fallback', [
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback: Rule-based triage
        return $this->fallbackTriage($message);
    }

    /**
     * Rule-based fallback triage when AI is unavailable.
     *
     * @param string $message
     * @return array
     */
    private function fallbackTriage(string $message): array
    {
        $lowerMessage = strtolower($message);

        // High-severity keywords
        $highKeywords = ['chest pain', 'difficulty breathing', 'unconscious', 'seizure', 'stroke', 'heart attack', 'severe bleeding'];
        // Medium-severity keywords
        $mediumKeywords = ['fever', 'vomiting', 'diarrhea', 'severe headache', 'broken bone', 'infection'];

        $severity   = 'LOW';
        $confidence = 0.60;
        $symptoms   = $this->extractSymptoms($lowerMessage);
        $intent     = 'general_inquiry';

        foreach ($highKeywords as $keyword) {
            if (str_contains($lowerMessage, $keyword)) {
                $severity   = 'HIGH';
                $confidence = 0.85;
                $intent     = 'emergency_triage';
                break;
            }
        }

        if ($severity === 'LOW') {
            foreach ($mediumKeywords as $keyword) {
                if (str_contains($lowerMessage, $keyword)) {
                    $severity   = 'MEDIUM';
                    $confidence = 0.72;
                    $intent     = 'symptom_check';
                    break;
                }
            }
        }

        $recommendations = [
            'HIGH'   => 'Please go to the nearest Emergency Room immediately or call emergency services (119).',
            'MEDIUM' => 'Please visit a clinic or doctor within 24 hours. Monitor your symptoms closely.',
            'LOW'    => 'Get adequate rest, stay hydrated. If symptoms worsen, consult a healthcare provider.',
        ];

        $responses = [
            'HIGH'   => 'Based on your symptoms, this may require immediate medical attention.',
            'MEDIUM' => 'Your symptoms indicate you should see a doctor relatively soon.',
            'LOW'    => 'Your symptoms appear mild. Please rest and monitor your condition.',
        ];

        return [
            'intent'         => $intent,
            'response'       => $responses[$severity],
            'symptoms'       => $symptoms,
            'severity'       => $severity,
            'confidence'     => $confidence,
            'recommendation' => $recommendations[$severity],
        ];
    }

    /**
     * Extract potential symptoms from text
     *
     * @param string $message
     * @return array
     */
    private function extractSymptoms(string $message): array
    {
        $symptomKeywords = [
            'fever', 'cough', 'headache', 'nausea', 'vomiting', 'diarrhea',
            'fatigue', 'dizziness', 'chest pain', 'shortness of breath',
            'sore throat', 'runny nose', 'body aches', 'rash', 'bleeding',
        ];

        $found = [];
        foreach ($symptomKeywords as $symptom) {
            if (str_contains($message, $symptom)) {
                $found[] = $symptom;
            }
        }

        return $found ?: ['symptom_not_identified'];
    }
}
