<?php

namespace App\Services;

use App\Enums\IntentType;
use Illuminate\Support\Facades\Cache;

/**
 * Prompt Cache Service
 * 
 * Caching strategy untuk performa GPU acceleration.
 * Cache system prompt dan intent-specific templates untuk quick retrieval.
 */
class PromptCacheService
{
    const CACHE_TTL = 3600; // 1 hour

    /**
     * Cache system prompt untuk quick retrieval
     */
    public static function getCachedSystemPrompt(): string
    {
        return Cache::remember('llm:system_prompt', self::CACHE_TTL, function () {
            return app(LocalLlmService::class)->getSystemPrompt();
        });
    }

    /**
     * Cache intent-specific templates
     */
    public static function getCachedTemplate(IntentType $intent): string
    {
        $cacheKey = "llm:intent_template:{$intent->value}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($intent) {
            return PromptTemplateService::getPromptTemplate($intent);
        });
    }

    /**
     * Cache symptom extraction patterns
     */
    public static function getCachedSymptomPatterns(): array
    {
        return Cache::remember('llm:symptom_patterns', self::CACHE_TTL, function () {
            return self::loadSymptomPatterns();
        });
    }

    /**
     * Load symptom patterns from JSON
     */
    private static function loadSymptomPatterns(): array
    {
        $path = storage_path('app/datasets/symptom_patterns.json');
        
        if (!file_exists($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true) ?? [];
    }

    /**
     * Pre-warm cache on application startup
     */
    public static function warmCache(): void
    {
        // Cache system prompt
        self::getCachedSystemPrompt();

        // Cache all intent templates
        foreach (IntentType::cases() as $intent) {
            self::getCachedTemplate($intent);
        }

        // Cache symptom patterns
        self::getCachedSymptomPatterns();
    }

    /**
     * Clear all LLM-related cache
     */
    public static function clearCache(): void
    {
        Cache::forget('llm:system_prompt');
        
        foreach (IntentType::cases() as $intent) {
            Cache::forget("llm:intent_template:{$intent->value}");
        }
        
        Cache::forget('llm:symptom_patterns');
    }
}
