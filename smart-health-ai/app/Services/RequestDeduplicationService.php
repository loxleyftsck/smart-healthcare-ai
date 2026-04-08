<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * RequestDeduplicationService - Prevent duplicate request processing
 * PATH B Day 3: Eliminate costly duplicate operations
 * 
 * Use Cases:
 * - Multiple rapid submits of same form
 * - Retry logic causing duplicate processing
 * - Network glitches causing resends
 * - Concurrent identical requests
 */
class RequestDeduplicationService
{
    /**
     * Deduplication cache TTL (seconds)
     */
    public const DEDUP_TTL = 60;
    
    /**
     * Prefix for dedup cache keys
     */
    private const CACHE_PREFIX = 'request_dedup:';
    
    /**
     * Check if request is duplicate and cache result
     *
     * @param Request $request
     * @param string $userId Authenticated user ID
     * @param int $ttl Cache TTL in seconds
     * @return array ['is_duplicate' => bool, 'key' => string, 'result' => mixed]
     */
    public function checkAndCache(Request $request, ?string $userId = null, int $ttl = self::DEDUP_TTL): array
    {
        $deduplicationKey = $this->generateKey($request, $userId);
        
        // Check if this exact request was recently processed
        $cacheKey = self::CACHE_PREFIX . $deduplicationKey;
        $cachedResult = Cache::get($cacheKey);
        
        if ($cachedResult !== null) {
            return [
                'is_duplicate' => true,
                'key' => $deduplicationKey,
                'result' => $cachedResult,
                'message' => 'Duplicate request detected - returning cached result',
            ];
        }
        
        return [
            'is_duplicate' => false,
            'key' => $deduplicationKey,
            'result' => null,
            'message' => 'Request is unique - processing normally',
        ];
    }
    
    /**
     * Generate deduplication key from request
     *
     * @param Request $request
     * @param ?string $userId
     * @return string
     */
    private function generateKey(Request $request, ?string $userId = null): string
    {
        $parts = [
            $request->method(),
            $request->path(),
            $userId ?? 'anon',
            json_encode($request->all()), // Include all input data
        ];
        
        return hash('sha256', implode('|', $parts));
    }
    
    /**
     * Cache request result for deduplication
     *
     * @param string $deduplicationKey
     * @param mixed $result
     * @param int $ttl TTL in seconds
     * @return void
     */
    public function cacheResult(string $deduplicationKey, mixed $result, int $ttl = self::DEDUP_TTL): void
    {
        Cache::put(
            self::CACHE_PREFIX . $deduplicationKey,
            $result,
            $ttl
        );
    }
    
    /**
     * Clear cached result (explicit invalidation)
     *
     * @param string $deduplicationKey
     * @return void
     */
    public function clearCache(string $deduplicationKey): void
    {
        Cache::forget(self::CACHE_PREFIX . $deduplicationKey);
    }
    
    /**
     * Get all active deduplication keys
     *
     * @return array
     */
    public function getActiveDeduplicationKeys(): array
    {
        // This is a simplified version - in production, you'd iterate Cache store
        return [];
    }
    
    /**
     * Generate idempotency key from request headers
     *
     * @param Request $request
     * @return ?string
     */
    public function getIdempotencyKey(Request $request): ?string
    {
        return $request->header('Idempotency-Key') 
            ?? $request->header('X-Idempotency-Key')
            ?? $request->header('X-Request-ID');
    }
    
    /**
     * Check using idempotency key (common in REST APIs)
     *
     * @param string $idempotencyKey
     * @param int $ttl TTL in seconds
     * @return mixed Cached result or null
     */
    public function getByIdempotencyKey(string $idempotencyKey, int $ttl = self::DEDUP_TTL): mixed
    {
        $cacheKey = self::CACHE_PREFIX . 'idempotent:' . $idempotencyKey;
        return Cache::get($cacheKey);
    }
    
    /**
     * Store result by idempotency key
     *
     * @param string $idempotencyKey
     * @param mixed $result
     * @param int $ttl TTL in seconds
     * @return void
     */
    public function storeByIdempotencyKey(string $idempotencyKey, mixed $result, int $ttl = self::DEDUP_TTL): void
    {
        $cacheKey = self::CACHE_PREFIX . 'idempotent:' . $idempotencyKey;
        Cache::put($cacheKey, $result, $ttl);
    }
}
