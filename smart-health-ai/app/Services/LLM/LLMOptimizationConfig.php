<?php

namespace App\Services\LLM;

/**
 * LLM Optimization Configuration
 * 
 * Tunable parameters untuk Mistral 7B dengan smart defaults
 * bergantung pada mode (speed vs quality)
 */
class LLMOptimizationConfig
{
    // ===== GENERATION PARAMETERS =====
    
    /**
     * Temperature - Controls randomness (0.0 = deterministic, 1.0 = random)
     * 
     * Healthcare Use Cases:
     * - CRITICAL: 0.0-0.2    (deterministic, precise for emergencies)
     * - DIAGNOSTIC: 0.2-0.3  (consistent but slightly creative)
     * - LIFESTYLE: 0.5-0.7   (more varied suggestions)
     * - CHAT: 0.7-0.9        (more natural conversation)
     */
    public const TEMPERATURE_EMERGENCY = 0.0;      // Ultra-precise for critical situations
    public const TEMPERATURE_DIAGNOSTIC = 0.3;     // Default: balanced accuracy
    public const TEMPERATURE_LIFESTYLE = 0.6;      // More varied suggestions
    public const TEMPERATURE_CHAT = 0.8;           // Natural conversation

    /**
     * Top-P (Nucleus Sampling) - Controls diversity
     * Lower = more focused, Higher = more diverse
     * 
     * Recommended:
     * - Focused output: 0.7-0.8
     * - Balanced: 0.85-0.9
     * - Diverse: 0.95+
     */
    public const TOP_P_FOCUSED = 0.7;
    public const TOP_P_BALANCED = 0.85;      // Default
    public const TOP_P_DIVERSE = 0.95;

    /**
     * Top-K - Select from top K probable tokens
     * Lower = more focused (1-10)
     * Higher = more diverse (40-100)
     */
    public const TOP_K_FOCUSED = 20;
    public const TOP_K_BALANCED = 40;        // Default
    public const TOP_K_DIVERSE = 100;

    /**
     * Num Predict - Maximum tokens in response
     * 
     * Medical Context:
     * - Brief: 150-200 tokens (quick response)
     * - Standard: 300-400 tokens (balanced)
     * - Comprehensive: 500-600 tokens (detailed)
     */
    public const NUM_PREDICT_MIN = 100;
    public const NUM_PREDICT_BRIEF = 200;     // Quick responses
    public const NUM_PREDICT_STANDARD = 300;  // Default
    public const NUM_PREDICT_DETAILED = 500;  // Comprehensive responses
    public const NUM_PREDICT_MAX = 800;

    /**
     * Repeat Penalty - Penalize repeated tokens
     * 1.0 = no penalty
     * 1.1-1.5 = moderate (recommended)
     * 2.0+ = strong (may reduce quality)
     */
    public const REPEAT_PENALTY = 1.1;       // Balanced

    /**
     * Repeat Last N - How many tokens to consider for repeat penalty
     * Default: 64 (last 64 tokens)
     */
    public const REPEAT_LAST_N = 64;

    // ===== CONTEXT WINDOW OPTIMIZATION =====

    /**
     * Num Context - Context window size (tokens)
     * 
     * Mistral 7B: supports up to 32k
     * Recommended:
     * - Fast: 1024-2048
     * - Balanced: 2048-4096 (default)
     * - Comprehensive: 4096-8192
     */
    public const NUM_CTX_FAST = 2048;
    public const NUM_CTX_BALANCED = 4096;    // Default
    public const NUM_CTX_COMPREHENSIVE = 8192;

    /**
     * Num Batch - Batch size for inference
     * Higher = better throughput (but more memory)
     * Typical: 512-2048
     */
    public const NUM_BATCH_LOW = 512;
    public const NUM_BATCH_MEDIUM = 1024;    // Default
    public const NUM_BATCH_HIGH = 2048;

    // ===== HARDWARE OPTIMIZATION =====

    /**
     * Num GPU Layers
     * -1 = Auto (use all available)
     * 0 = CPU only (slow)
     * 1-50 = Specific number of layers on GPU
     * Mistral 7B typical: 32-40 layers
     */
    public const NUM_GPU_AUTO = -1;          // Recommended
    public const NUM_GPU_FULL = 40;          // Full GPU
    public const NUM_GPU_HYBRID = 20;        // Half GPU, half CPU
    public const NUM_GPU_CPU_ONLY = 0;       // CPU fallback

    /**
     * Num Threads - CPU threads for inference
     * -1 = Auto detect
     * Typical: 4-16 (match your CPU cores)
     */
    public const NUM_THREAD_AUTO = -1;       // Auto (RECOMMENDED)

    // ===== ADAPTER SETTINGS =====

    /**
     * Num GQA - Grouped-query attention layers
     * Specific to Mistral: improves throughput
     */
    public const NUM_GQA = 8;

    /**
     * Rope Freq Base - RoPE frequency scaling
     * Affects position embeddings
     * Default: 10000
     */
    public const ROPE_FREQ_BASE = 10000;

    // ===== PERFORMANCE PROFILES =====

    /**
     * Performance profiles untuk berbagai use cases
     */
    public static function getProfileSpeed(): array
    {
        return [
            'temperature' => self::TEMPERATURE_CHAT,
            'top_p' => self::TOP_P_FOCUSED,
            'top_k' => self::TOP_K_FOCUSED,
            'num_predict' => self::NUM_PREDICT_BRIEF,
            'num_ctx' => self::NUM_CTX_FAST,
            'num_batch' => self::NUM_BATCH_HIGH,
            'num_gpu' => self::NUM_GPU_AUTO,
            'repeat_penalty' => self::REPEAT_PENALTY,
        ];
    }

    /**
     * Balanced profile (DEFAULT)
     */
    public static function getProfileBalanced(): array
    {
        return [
            'temperature' => self::TEMPERATURE_DIAGNOSTIC,
            'top_p' => self::TOP_P_BALANCED,
            'top_k' => self::TOP_K_BALANCED,
            'num_predict' => self::NUM_PREDICT_STANDARD,
            'num_ctx' => self::NUM_CTX_BALANCED,
            'num_batch' => self::NUM_BATCH_MEDIUM,
            'num_gpu' => self::NUM_GPU_AUTO,
            'repeat_penalty' => self::REPEAT_PENALTY,
        ];
    }

    /**
     * Quality profile (slower but more accurate)
     */
    public static function getProfileQuality(): array
    {
        return [
            'temperature' => self::TEMPERATURE_DIAGNOSTIC,
            'top_p' => self::TOP_P_FOCUSED,
            'top_k' => self::TOP_K_FOCUSED,
            'num_predict' => self::NUM_PREDICT_DETAILED,
            'num_ctx' => self::NUM_CTX_COMPREHENSIVE,
            'num_batch' => self::NUM_BATCH_MEDIUM,
            'num_gpu' => self::NUM_GPU_AUTO,
            'repeat_penalty' => self::REPEAT_PENALTY,
        ];
    }

    /**
     * Emergency profile (maximum safety, deterministic)
     */
    public static function getProfileEmergency(): array
    {
        return [
            'temperature' => self::TEMPERATURE_EMERGENCY,
            'top_p' => self::TOP_P_FOCUSED,
            'top_k' => self::TOP_K_FOCUSED,
            'num_predict' => self::NUM_PREDICT_BRIEF,
            'num_ctx' => self::NUM_CTX_BALANCED,
            'num_batch' => self::NUM_BATCH_MEDIUM,
            'num_gpu' => self::NUM_GPU_AUTO,
            'repeat_penalty' => self::REPEAT_PENALTY,
        ];
    }

    /**
     * Edge device profile (minimal resources)
     */
    public static function getProfileEdge(): array
    {
        return [
            'temperature' => self::TEMPERATURE_CHAT,
            'top_p' => self::TOP_P_FOCUSED,
            'top_k' => self::TOP_K_FOCUSED,
            'num_predict' => self::NUM_PREDICT_BRIEF,
            'num_ctx' => self::NUM_CTX_FAST,
            'num_batch' => self::NUM_BATCH_LOW,
            'num_gpu' => self::NUM_GPU_HYBRID,     // 50% GPU
            'repeat_penalty' => self::REPEAT_PENALTY,
        ];
    }

    // ===== MODEL VARIANTS =====

    /**
     * Mistral variants dan spesifikasinya
     */
    public static function getModelVariant(string $model): array
    {
        $variants = [
            'mistral' => [
                'name' => 'Mistral 7B',
                'size' => '4.1GB',
                'params' => '7B',
                'layers' => 32,
                'max_ctx' => 32768,
                'quantization' => 'f32',
                'throughput' => '~120 tokens/sec',
                'vram' => '3.5GB',
            ],
            'mistral:7b-q4' => [
                'name' => 'Mistral 7B (4-bit Quantized)',
                'size' => '1.4GB',
                'params' => '7B',
                'layers' => 32,
                'max_ctx' => 32768,
                'quantization' => 'q4',
                'throughput' => '~200 tokens/sec',  // Much faster!
                'vram' => '1.5GB',
            ],
            'mistral:7b-q5' => [
                'name' => 'Mistral 7B (5-bit Quantized)',
                'size' => '2.0GB',
                'params' => '7B',
                'layers' => 32,
                'max_ctx' => 32768,
                'quantization' => 'q5',
                'throughput' => '~150 tokens/sec',
                'vram' => '2.0GB',
            ],
        ];

        return $variants[$model] ?? $variants['mistral'];
    }
}
