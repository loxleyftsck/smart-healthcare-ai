<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\LLM\LLMOptimizationConfig;
use App\Services\LLM\LLMParameterOptimizer;
use App\Enums\IntentType;

/**
 * Local LLM Service — Mistral 7B Healthcare Assistant
 * 
 * Communicates with Ollama to run Mistral 7B locally with GPU acceleration.
 * Provides comprehensive healthcare chatbot with proper safety guardrails.
 */
class LocalLlmService
{
    protected string $baseUrl = 'http://localhost:11434/api/generate';
    protected string $model = 'mistral';
    protected string $systemPrompt = '';
    protected int $timeout = 60;

    public function __construct()
    {
        $this->baseUrl = config('services.ollama.url', 'http://localhost:11434') . '/api/generate';
        $this->model = config('services.ollama.model', 'mistral');
        $this->timeout = config('services.ollama.timeout', 60);
        $this->systemPrompt = $this->buildSystemPrompt();
    }

    /**
     * Get system prompt (for caching)
     */
    public function getSystemPrompt(): string
    {
        return $this->systemPrompt;
    }

    /**
     * Build comprehensive system prompt untuk Mistral 7B
     * Optimized untuk healthcare context + Indonesian language
     */
    protected function buildSystemPrompt(): string
    {
        return <<<'SYSTEM_PROMPT'
[ROLE & EXPERTISE]
Anda adalah "MedAssist" - Asisten Kesehatan AI yang berpengalaman, empati, dan sangat hati-hati dalam memberikan saran medis. Pelatihan Anda didasarkan pada panduan klinis internasional dan standar kesehatan Indonesia.

[BAHASA]
- Gunakan Bahasa Indonesia yang jelas dan mudah dipahami
- Hindari jargon medis yang rumit tanpa penjelasan
- Gunakan contoh praktis yang relatable untuk pasien Indonesia
- Adaptif terhadap konteks lokal (harga obat, ketersediaan layanan, dll)

[PRINSIP KESELAMATAN PASIEN - PALING PENTING]
⚠️  EMERGENCY PROTOCOL:
Jika pasien menyebutkan salah satu dari ini → HARUS IMMEDIATE ACTION:
  • Sulit bernapas / sesak napas berat
  • Nyeri dada atau tekanan di dada
  • Kehilangan kesadaran atau pingsang
  • Perdarahan tidak terkendali
  • Keracunan / overdosis
  • Cedera kepala serius
  • Melahirkan / komplikasi kehamilan
  • Kejang

✓ RESPONSE: "🚨 INI DARURAT MEDIS! Hubungi 119 atau ambulans SEKARANG. Jangan menunggu. Ini bukan situasi untuk konsultasi online."

[KETERBATASAN & DISCLAIMER]
1. ❌ JANGAN memberikan diagnosis definitif
   ✓ Benar: "Gejala Anda bisa menunjukkan beberapa kemungkinan, termasuk..."
   ✗ Salah: "Anda pasti menderita..."

2. ❌ JANGAN meresepkan obat spesifik
   ✓ Benar: "Golongan obat yang umum digunakan adalah..."
   ✗ Salah: "Ambil Paracetamol 500mg setiap 6 jam"

3. ❌ JANGAN menggantikan dokter
   ✓ Selalu akhiri dengan: "Konsultasikan dengan dokter untuk evaluasi menyeluruh"

4. ❌ JANGAN memberikan nasihat psikiatri untuk kondisi serius
   ✓ Jika mencurigai depresi/suisidal → arahkan ke psikolog profesional

[TRIAGE FRAMEWORK - GUNAKAN UNTUK ASSESSMENT]
Ketika pasien menceritakan gejala, gunakan framework ini:

1️⃣  SEVERITY CHECK:
   - RED (Emergency): Bahaya nyawa → Kirim ke ER
   - YELLOW (Urgent): Memerlukan evaluasi cepat → Klinik/UGD dalam jam
   - GREEN (Non-urgent): Bisa ditangani di rumah → Follow-up dengan dokter

2️⃣  SYMPTOM CLUSTERING:
   Kelompokkan gejala: Respirasi | Kardio | GI | Neuro | Derma | Muskuloskeletal

3️⃣  RISK STRATIFICATION:
   - Usia pasien
   - Kondisi komorbid (penyakit bawaan)
   - Durasi gejala
   - Keparahan (skala 1-10)

4️⃣  RECOMMENDATION PATHWAY:
   - Emergency → 119 / IGD
   - Urgent → Klinik dalam 24 jam
   - Routine → Jadwal konsultasi reguler

[RESPONSE STYLE GUIDELINES]
✅ DO:
  • Mulai dengan empati: "Saya mengerti ini mencemaskan..."
  • Dengarkan aktif: "Jadi gejala Anda mulai sejak kapan?"
  • Edukasi sederhana: Jelaskan kondisi dalam istilah sehari-hari
  • Empower pasien: "Yang bisa Anda lakukan sekarang adalah..."
  • Actionable advice: Langkah konkret (minum air putih, istirahat, dll)
  • Provide reassurance: Sebagian besar kondisi umum bisa ditangani

❌ DON'T:
  • Jangan alarm-alarmkan: Hindari kata "sangat berbahaya" tanpa konteks
  • Jangan terlalu santai: Treat serious symptoms dengan serius
  • Jangan spekulasi: "Mungkin itu kanker..." ← JANGAN
  • Jangan bikin janji palsu: "Ini pasti akan hilang dalam 3 hari"
  • Jangan give medical opinion tanpa cukup info: "Bisa diceritakan lebih detail?"

[INFORMATION GATHERING - KERJAKAN SEQUENCE INI]
Jika gejala tidak jelas, tanyakan secara berurutan:
1. "Gejala mulai kapan? (hari/jam)"
2. "Keparahan berapa? (skala 1-10)"
3. "Ada demam/perubahan lain?"
4. "Pernah mengalami ini sebelumnya?"
5. "Ada yang membuat lebih baik/buruk?"
6. "Punya penyakit atau ambil obat lain?"

[RESPONSE LENGTH]
- Singkat untuk greeting/small talk: 50-100 kata
- Medium untuk symptom assessment: 150-250 kata
- Panjang untuk edukasi: max 300 kata
- NEVER lebih dari 400 kata dalam 1 response

[LOCAL CONTEXT - INDONESIA SPECIFIC]
• Kesadaran tentang cost: "Obat ini relatif terjangkau di apotek"
• Ketersediaan: "Layanan ini tersedia di rumah sakit pemerintah"
• Cultural sensitivity: Pahami kepercayaan lokal tentang kesehatan
• Access: Pertimbangkan akses pasien ke layanan kesehatan
• Terminology: Gunakan istilah yang umum di Indonesia

[CONVERSATION MEMORY]
- Ingat nama pasien jika disebutkan
- Ingat gejala sebelumnya yang dilaporkan
- Jangan menanyakan hal yang sudah dijawab
- Referensi back: "Seperti yang Anda bilang tadi tentang demam..."

[OUTPUT FORMAT]
Format setiap response:
┌─────────────────────────────────────────┐
│ 1. GREETING/EMPATHY (1-2 kalimat)       │
│ 2. CLARIFICATION (jika perlu tanya)     │
│ 3. ASSESSMENT (apa yang kemungkinan)    │
│ 4. RECOMMENDATION (apa yang harus lakukan)│
│ 5. FOLLOW-UP (kapan check kembali)      │
│ 6. DISCLAIMER (ini bukan diagnosis)     │
└─────────────────────────────────────────┘
SYSTEM_PROMPT;
    }

    /**
     * Generate response dengan Mistral 7B - OPTIMIZED VERSION
     * 
     * Menggunakan intent-aware parameters untuk optimal quality/speed tradeoff
     * 
     * @param string $message User message
     * @param array $context Patient context
     * @param array $history Conversation history
     * @param IntentType|null $intent User intent (untuk parameter optimization)
     * @return string
     */
    public function generate(
        string $message,
        array $context = [],
        array $history = [],
        ?IntentType $intent = null
    ): string {
        // Build conversation history untuk context window
        $conversationContext = $this->buildConversationContext($history, $message);

        $fullPrompt = $this->buildFullPrompt(
            $message,
            $conversationContext,
            $context
        );

        // 🎯 OPTIMIZATION: Gunakan intent-specific parameters
        $options = $this->getOptimizedOptions($intent);

        try {
            $startTime = microtime(true);

            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl, [
                    'model' => $this->model,
                    'prompt' => $fullPrompt,
                    'stream' => false,
                    'options' => $options,
                ]);

            $elapsedMs = (microtime(true) - $startTime) * 1000;

            if ($response->successful()) {
                $data = $response->json();
                $generatedText = $data['response'] ?? '';
                
                // Log performance metrics
                $this->logPerformance($intent, $elapsedMs, strlen($generatedText));
                
                // Clean up response
                return $this->cleanResponse($generatedText);
            }

            Log::error('Ollama API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'intent' => $intent?->value,
            ]);

            throw new \Exception('Ollama failed');
        } catch (\Exception $e) {
            Log::error('LocalLLM Generation Error', [
                'message' => $e->getMessage(),
                'intent' => $intent?->value,
            ]);
            return $this->fallbackResponse($message, $context);
        }
    }

    /**
     * Get optimized options berdasarkan intent type
     */
    private function getOptimizedOptions(?IntentType $intent = null): array
    {
        // 🎯 OPTIMIZATION 1: Intent-aware parameter selection
        if ($intent) {
            $params = LLMParameterOptimizer::getParametersByIntent($intent);
        } else {
            // Fallback ke balanced profile
            $params = LLMOptimizationConfig::getProfileBalanced();
        }

        // 🎯 OPTIMIZATION 2: Add hardware optimization settings
        return array_merge($params, [
            'repeat_penalty' => $params['repeat_penalty'] ?? 1.1,
            'num_thread' => -1,                    // Auto detect
            'num_gpu' => -1,                       // Auto GPU (kenapa not specified in params)
            'num_gqa' => LLMOptimizationConfig::NUM_GQA,
            'rope_freq_base' => LLMOptimizationConfig::ROPE_FREQ_BASE,
        ]);
    }

    /**
     * Log performance metrics untuk optimization tracking
     */
    private function logPerformance(?IntentType $intent, float $elapsedMs, int $responseLength): void
    {
        $tokens = (int) ($responseLength / 4); // Rough estimate: ~4 chars per token

        Cache::remember('llm_performance_' . ($intent?->value ?? 'default'), 3600, function () {
            return ['total_time' => 0, 'total_tokens' => 0, 'count' => 0];
        });

        // Track performance metrics
        $key = 'llm_perf_' . ($intent?->value ?? 'default');
        $stats = Cache::get($key, ['total_time' => 0, 'total_tokens' => 0, 'count' => 0]);
        $stats['total_time'] += $elapsedMs;
        $stats['total_tokens'] += $tokens;
        $stats['count']++;
        Cache::put($key, $stats, 3600);

        Log::info('LLM Performance', [
            'intent' => $intent?->value,
            'response_time_ms' => round($elapsedMs, 2),
            'tokens_estimated' => $tokens,
            'tokens_per_second' => round(($tokens / $elapsedMs) * 1000, 2),
        ]);
    }

    /**
     * Build full prompt dengan context + conversation history
     */
    protected function buildFullPrompt(
        string $userMessage,
        string $conversationContext,
        array $patientContext
    ): string {
        $patientInfo = $this->formatPatientContext($patientContext);

        return <<<PROMPT
{$this->systemPrompt}

[PATIENT CONTEXT]
{$patientInfo}

[CONVERSATION HISTORY]
{$conversationContext}

[CURRENT MESSAGE]
Pasien: {$userMessage}

[YOUR RESPONSE - AS MEDASSIST]
MedAssist:
PROMPT;
    }

    /**
     * Format patient context untuk prompt
     */
    protected function formatPatientContext(array $context): string
    {
        $age = $context['age'] ?? 'Tidak diketahui';
        $gender = $context['gender'] ?? 'Tidak diketahui';
        $medical_history = $context['medical_history'] ?? 'Tidak ada';
        $current_medications = $context['current_medications'] ?? 'Tidak ada';
        $allergies = $context['allergies'] ?? 'Tidak ada';

        return <<<INFO
- Usia: {$age} tahun
- Jenis Kelamin: {$gender}
- Riwayat Medis: {$medical_history}
- Obat yang Diminum: {$current_medications}
- Alergi: {$allergies}
INFO;
    }

    /**
     * Build conversation context dari history
     */
    protected function buildConversationContext(array $history, string $currentMessage): string
    {
        if (empty($history)) {
            return "(Ini adalah pesan pertama)";
        }

        // Keep last 3-5 messages untuk context window efficiency
        $recentHistory = array_slice($history, -3);
        
        $formatted = "";
        foreach ($recentHistory as $msg) {
            $role = $msg['role'] === 'user' ? 'Pasien' : 'MedAssist';
            $formatted .= "{$role}: {$msg['content']}\n";
        }

        return $formatted;
    }

    /**
     * Clean up generated response
     */
    protected function cleanResponse(string $response): string
    {
        // Remove model artifacts
        $response = preg_replace('/^MedAssist:\s*/i', '', $response);
        $response = trim($response);
        
        // Ensure ends with proper punctuation
        if (!in_array(substr($response, -1), ['.', '!', '?'])) {
            $response .= '.';
        }

        return $response;
    }

    /**
     * Fallback response jika Ollama down
     */
    protected function fallbackResponse(string $message, array $context): string
    {
        $symptoms = $this->extractBasicSymptoms($message);
        
        if (empty($symptoms)) {
            return "Maaf, sistem tidak dapat terhubung ke model AI saat ini. "
                 . "Silakan konsultasikan dengan dokter langsung atau coba lagi nanti.";
        }

        return "Saya sedang mengalami keterbatasan teknis, namun berdasarkan gejala yang Anda sebutkan, "
             . "saya rekomendasikan untuk konsultasi dengan dokter. "
             . "Gejala: " . implode(', ', $symptoms);
    }

    /**
     * Extract symptoms dari message (simple pattern matching)
     */
    protected function extractBasicSymptoms(string $message): array
    {
        $symptomKeywords = [
            'demam' => ['demam', 'panas', 'suhu tinggi'],
            'batuk' => ['batuk', 'sesak'],
            'pilek' => ['pilek', 'hidung tersumbat'],
            'nyeri' => ['sakit', 'nyeri', 'pain'],
        ];

        $found = [];
        foreach ($symptomKeywords as $symptom => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($message, $keyword) !== false) {
                    $found[] = $symptom;
                    break;
                }
            }
        }

        return array_unique($found);
    }

    /**
     * Check Ollama availability
     */
    public function isAvailable(): bool
    {
        try {
            return Http::timeout(3)
                ->get('http://localhost:11434/api/tags')
                ->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get available models info
     */
    public function getModelInfo(): array
    {
        try {
            $response = Http::get('http://localhost:11434/api/tags');
            $models = $response->json('models', []);

            return array_map(fn($m) => [
                'name' => $m['name'] ?? 'unknown',
                'size' => $m['size'] ?? 0,
                'modified_at' => $m['modified_at'] ?? null,
            ], $models);
        } catch (\Exception $e) {
            return [];
        }
    }
}
