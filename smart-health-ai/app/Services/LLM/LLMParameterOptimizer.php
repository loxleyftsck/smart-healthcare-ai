<?php

namespace App\Services\LLM;

use App\Enums\IntentType;

/**
 * LLM Parameter Optimizer
 * 
 * Intelligently selects optimal parameters bergantung pada:
 * - Intent type
 * - Patient context  
 * - System load
 * - Performance requirements
 */
class LLMParameterOptimizer
{
    /**
     * Get optimal parameters berdasarkan intent
     */
    public static function getParametersByIntent(IntentType $intent): array
    {
        return match ($intent) {
            // EMERGENCY: Ultra-precise, deterministic
            IntentType::EMERGENCY => self::getEmergencyParams(),

            // SYMPTOM QUERY: Balance accuracy + speed
            IntentType::SYMPTOM_QUERY => self::getSymptomParams(),

            // MEDICATION: High accuracy required
            IntentType::MEDICATION_ADVICE => self::getMedicationParams(),

            // LIFESTYLE: More creative suggestions allowed
            IntentType::LIFESTYLE => self::getLifestyleParams(),

            // APPOINTMENT: Simple, fast response
            IntentType::APPOINTMENT => self::getAppointmentParams(),

            // GREETING: Natural, conversational
            IntentType::GREETING => self::getGreetingParams(),

            // FALLBACK: Conservative, safe default
            IntentType::FALLBACK => self::getFallbackParams(),
        };
    }

    /**
     * Emergency: Maximum safety, zero creativity
     */
    private static function getEmergencyParams(): array
    {
        return [
            'temperature' => 0.0,      // Deterministic
            'top_p' => 0.7,
            'top_k' => 20,
            'num_predict' => 150,      // Brief response
            'num_ctx' => 2048,         // Small context
            'repeat_penalty' => 1.1,
        ];
    }

    /**
     * Symptom Query: Balanced accuracy + speed
     */
    private static function getSymptomParams(): array
    {
        return [
            'temperature' => 0.3,      // Low creativity
            'top_p' => 0.85,
            'top_k' => 40,
            'num_predict' => 400,      // Comprehensive
            'num_ctx' => 4096,         // Full context
            'repeat_penalty' => 1.1,
        ];
    }

    /**
     * Medication: High accuracy, follow guidelines
     */
    private static function getMedicationParams(): array
    {
        return [
            'temperature' => 0.2,      // Very focused
            'top_p' => 0.75,
            'top_k' => 30,
            'num_predict' => 350,      // Moderate length
            'num_ctx' => 4096,
            'repeat_penalty' => 1.2,   // Strong repeat penalty
        ];
    }

    /**
     * Lifestyle: Balanced, some creativity for variety
     */
    private static function getLifestyleParams(): array
    {
        return [
            'temperature' => 0.6,      // Moderate creativity
            'top_p' => 0.9,
            'top_k' => 50,
            'num_predict' => 400,
            'num_ctx' => 4096,
            'repeat_penalty' => 1.0,
        ];
    }

    /**
     * Appointment: Fast, simple confirmation
     */
    private static function getAppointmentParams(): array
    {
        return [
            'temperature' => 0.5,
            'top_p' => 0.85,
            'top_k' => 40,
            'num_predict' => 150,      // Very short
            'num_ctx' => 2048,         // Minimal context
            'repeat_penalty' => 1.1,
        ];
    }

    /**
     * Greeting: Natural conversation
     */
    private static function getGreetingParams(): array
    {
        return [
            'temperature' => 0.8,      // More creative
            'top_p' => 0.9,
            'top_k' => 60,
            'num_predict' => 100,      // Short & sweet
            'num_ctx' => 2048,
            'repeat_penalty' => 1.0,
        ];
    }

    /**
     * Fallback: Conservative default
     */
    private static function getFallbackParams(): array
    {
        return [
            'temperature' => 0.4,
            'top_p' => 0.8,
            'top_k' => 40,
            'num_predict' => 250,
            'num_ctx' => 2048,
            'repeat_penalty' => 1.1,
        ];
    }

    /**
     * Get system load aware parameters
     * Adjust based on current system load
     */
    public static function getSystemLoadAwareParams(float $cpuLoad = 0.5, string $baseProfile = 'balanced'): array
    {
        $baseParams = $baseProfile === 'quality'
            ? LLMOptimizationConfig::getProfileQuality()
            : LLMOptimizationConfig::getProfileBalanced();

        // If high CPU load, reduce inference load
        if ($cpuLoad > 0.8) {
            $baseParams['temperature'] = min($baseParams['temperature'] + 0.1, 0.9);
            $baseParams['num_predict'] = max($baseParams['num_predict'] - 100, 100);
            $baseParams['num_batch'] = LLMOptimizationConfig::NUM_BATCH_LOW;
        }

        return $baseParams;
    }

    /**
     * Get memory aware parameters
     * Adjust based on available memory
     */
    public static function getMemoryAwareParams(int $availableMemoryMB = 4096): array
    {
        if ($availableMemoryMB < 2048) {
            return LLMOptimizationConfig::getProfileEdge();
        }

        if ($availableMemoryMB < 4096) {
            return LLMOptimizationConfig::getProfileSpeed();
        }

        return LLMOptimizationConfig::getProfileBalanced();
    }

    /**
     * Optimize parameters with constraints
     */
    public static function optimizeWithConstraints(
        array $params,
        array $constraints = []
    ): array {
        $maxResponseTime = $constraints['max_response_time_ms'] ?? PHP_INT_MAX;
        $maxTokens = $constraints['max_tokens'] ?? 500;
        $requireHighAccuracy = $constraints['require_high_accuracy'] ?? false;

        // If response must be fast
        if ($maxResponseTime < 1000) {
            $params['num_predict'] = min($params['num_predict'], 150);
            $params['temperature'] = min($params['temperature'], 0.5);
        }

        // If high accuracy required
        if ($requireHighAccuracy) {
            $params['temperature'] = min($params['temperature'], 0.3);
            $params['repeat_penalty'] = max($params['repeat_penalty'], 1.2);
        }

        return $params;
    }

    /**
     * Compare two parameter sets for quality vs speed tradeoff
     */
    public static function compareParameterSets(array $set1, array $set2): array
    {
        return [
            'set1' => [
                'name' => 'Parameters Set 1',
                'estimated_quality_score' => self::estimateQualityScore($set1),
                'estimated_speed_tokens_sec' => self::estimateSpeed($set1),
                'memory_usage_mb' => self::estimateMemoryUsage($set1),
            ],
            'set2' => [
                'name' => 'Parameters Set 2',
                'estimated_quality_score' => self::estimateQualityScore($set2),
                'estimated_speed_tokens_sec' => self::estimateSpeed($set2),
                'memory_usage_mb' => self::estimateMemoryUsage($set2),
            ],
        ];
    }

    /**
     * Estimate quality score (0-1)
     */
    private static function estimateQualityScore(array $params): float
    {
        $score = 0.0;

        // Lower temperature = higher quality
        $score += (1.0 - min($params['temperature'] ?? 0.5, 1.0)) * 0.3;

        // Higher top_p = higher quality
        $score += ($params['top_p'] ?? 0.85) * 0.2;

        // Higher top_k = higher quality (to a point)
        $score += min(($params['top_k'] ?? 40) / 100, 1.0) * 0.2;

        // Longer context = higher quality
        $score += min(($params['num_ctx'] ?? 4096) / 8192, 1.0) * 0.2;

        // Repeat penalty = higher quality
        $score += (($params['repeat_penalty'] ?? 1.0) - 1.0) / 1.0 * 0.1;

        return round(min($score, 1.0), 2);
    }

    /**
     * Estimate inference speed (tokens/sec)
     */
    private static function estimateSpeed(array $params): int
    {
        $baseSpeed = 120; // Mistral 7B base: ~120 tokens/sec

        // num_predict doesn't affect per-token speed
        // But does affect total response time

        // temperature affects computation slightly (negligible)
        // ctx affects slightly
        $ctxFactor = ($params['num_ctx'] ?? 4096) / 4096;
        $adjustedSpeed = $baseSpeed / (1.0 + ($ctxFactor * 0.1));

        return (int) $adjustedSpeed;
    }

    /**
     * Estimate memory usage
     */
    private static function estimateMemoryUsage(array $params): int
    {
        // Base: ~3500MB for Mistral 7B full model
        $baseMemory = 3500;

        // Context size affects memory
        $ctx = $params['num_ctx'] ?? 4096;
        $ctxMemory = ($ctx / 4096) * 500; // ~500MB per 4096 ctx

        // Batch size affects memory
        $batch = $params['num_batch'] ?? 1024;
        $batchMemory = ($batch / 1024) * 200; // ~200MB per 1024 batch

        return (int) ($baseMemory + $ctxMemory + $batchMemory);
    }
}
