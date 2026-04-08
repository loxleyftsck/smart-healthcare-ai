<?php

namespace App\Services;

use App\Enums\IntentType;
use Illuminate\Support\Facades\Log;

/**
 * Intent Detector Service
 * 
 * Detects user intent from chat messages using keyword matching.
 * Routes conversations to appropriate response handlers.
 */
class IntentDetectorService
{
    /**
     * Intent keywords mapping
     */
    protected array $intentKeywords = [
        IntentType::GREETING => [
            'halo', 'hai', 'hello', 'selamat pagi', 'selamat siang', 'selamat sore',
            'selamat malam', 'hei', 'halo assalamualaikum', 'assalamualaikum',
            'pagi', 'siang', 'sore', 'malam', 'hey', 'hi', 'apa kabar', 'gimana kabar',
        ],
        IntentType::SYMPTOM_QUERY => [
            'sakit', 'demam', 'batuk', 'sesak', 'nyeri', 'pusing', 'gejala', 'keluhan',
            'tidak enak', 'merasa aneh', 'merasa sakit', 'kena penyakit', 'terkena',
            'mual', 'muntah', 'diare', 'sembelit', 'panas badan', 'sedikit demam',
            'hemoptoe', 'perdarahan', 'lemas', 'lelah', 'kelelahan', 'tidak bisa',
            'sulit', 'berat', 'bengkak', 'ruam', 'gatal', 'alergi', 'dermatitis',
        ],
        IntentType::MEDICATION_ADVICE => [
            'obat', 'minum obat', 'resep', 'dosis', 'paracetamol', 'ibuprofen',
            'amoxicillin', 'antibiotik', 'vitmin', 'supplement', 'medicine',
            'apa obat yang', 'resep apa', 'boleh minum', 'bagaimana cara minum',
            'efek samping', 'interaksi', 'kontraindikasi', 'alergi obat',
        ],
        IntentType::LIFESTYLE => [
            'diet', 'olahraga', 'exercise', 'tidur', 'istirahat', 'makan',
            'stress', 'kesehatan', 'diet sehat', 'hidup sehat', 'wellness',
            'berat badan', 'gemuk', 'kurus', 'fitness', 'gym', 'yoga',
            'meditasi', 'rileks', 'tenang', 'khawatir', 'cemas', 'depresi',
        ],
        IntentType::APPOINTMENT => [
            'jadwal', 'booking', 'daftar', 'antri', 'appointment', 'reservasi',
            'dokter', 'klinik', 'rumah sakit', 'bagaimana caranya', 'bagaimana cara daftar',
            'kapan bisa ketemu', 'bisa kapan', 'jam berapa', 'hari apa', 'biaya',
            'lokasi', 'alamat', 'jam kerja', 'jam praktek',
        ],
        IntentType::EMERGENCY => [
            'darurat', 'parah sekali', 'tidak bisa napas', 'sesak napas berat',
            'pingsan', 'tidak sadarkan diri', 'perdarahan', 'keracunan',
            'stroke', 'serangan jantung', 'nyeri dada', 'tekanan dada',
            'kejang', 'melahirkan', 'kehamilan', '119', 'ambulans',
            'emergency', 'urgent', 'sekarang', 'cepat', 'langsung', 'segera',
        ],
    ];

    /**
     * Detect intent from user message
     */
    public function detect(string $message): IntentType
    {
        // Check for emergency keywords first (highest priority)
        if ($this->hasKeywords($message, IntentType::EMERGENCY)) {
            Log::info('Emergency intent detected', ['message' => substr($message, 0, 50)]);
            return IntentType::EMERGENCY;
        }

        // Check for other intents
        foreach (IntentType::cases() as $intent) {
            if ($intent === IntentType::EMERGENCY) {
                continue; // Already checked
            }

            if ($this->hasKeywords($message, $intent)) {
                Log::info('Intent detected', ['intent' => $intent->value, 'message' => substr($message, 0, 50)]);
                return $intent;
            }
        }

        // Default fallback
        Log::info('Fallback intent (no keywords matched)', ['message' => substr($message, 0, 50)]);
        return IntentType::FALLBACK;
    }

    /**
     * Check if message contains keywords for specific intent
     */
    protected function hasKeywords(string $message, IntentType $intent): bool
    {
        $normalized = $this->normalize($message);
        $keywords = $this->intentKeywords[$intent->value] ?? [];

        foreach ($keywords as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize message for keyword matching
     * - lowercase
     * - strip punctuation
     * - remove extra spaces
     */
    protected function normalize(string $message): string
    {
        $message = strtolower($message);
        $message = preg_replace('/[^\w\s]/u', '', $message);
        $message = preg_replace('/\s+/', ' ', $message);
        return trim($message);
    }

    /**
     * Get all available intents with their keywords
     */
    public function getIntentKeywords(): array
    {
        return $this->intentKeywords;
    }

    /**
     * Add custom keywords for intent (for extensibility)
     */
    public function addKeywords(IntentType $intent, array $keywords): void
    {
        $this->intentKeywords[$intent->value] = array_merge(
            $this->intentKeywords[$intent->value] ?? [],
            $keywords
        );
    }
}
