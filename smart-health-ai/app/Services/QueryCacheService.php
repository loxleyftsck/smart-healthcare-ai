<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;

/**
 * QueryCacheService - Intelligent database query result caching
 * PATH B: Database Caching Layer
 * 
 * Improves performance by:
 * - Caching frequent database query results
 * - Smart invalidation on data changes
 * - TTL-based automatic expiration
 */
class QueryCacheService
{
    /**
     * Cache TTL constants (in seconds)
     */
    public const TTL_REALTIME = 5;          // 5 seconds - highly volatile data
    public const TTL_SHORT = 300;           // 5 minutes - consultation history
    public const TTL_MEDIUM = 1800;         // 30 minutes - patient lists
    public const TTL_LONG = 3600;           // 1 hour - reference data
    
    /**
     * Cache key prefixes
     */
    public const PREFIX_PATIENT = 'patient:';
    public const PREFIX_PATIENTS = 'patients:';
    public const PREFIX_CONSULTATION = 'consultation:';
    public const PREFIX_CONSULTATIONS = 'consultations:';
    public const PREFIX_TRIAGE = 'triage:';
    
    /**
     * Get patient from cache or database
     *
     * @param int $patientId
     * @param callable $callback Query callback
     * @param int $ttl Cache TTL in seconds
     * @return mixed
     */
    public function rememberPatient(int $patientId, callable $callback, int $ttl = self::TTL_LONG)
    {
        return Cache::remember(
            self::PREFIX_PATIENT . $patientId,
            $ttl,
            $callback
        );
    }
    
    /**
     * Get paginated patients from cache or database
     *
     * @param int $page
     * @param int $perPage
     * @param callable $callback Query callback
     * @param int $ttl Cache TTL in seconds
     * @return mixed
     */
    public function rememberPatients(int $page = 1, int $perPage = 15, callable $callback = null, int $ttl = self::TTL_MEDIUM)
    {
        $cacheKey = self::PREFIX_PATIENTS . "page:{$page}:per_page:{$perPage}";
        
        return Cache::remember(
            $cacheKey,
            $ttl,
            $callback ?? function () {
                return [];
            }
        );
    }
    
    /**
     * Get consultation from cache or database
     *
     * @param int $consultationId
     * @param callable $callback Query callback
     * @param int $ttl Cache TTL in seconds
     * @return mixed
     */
    public function rememberConsultation(int $consultationId, callable $callback, int $ttl = self::TTL_REALTIME)
    {
        return Cache::remember(
            self::PREFIX_CONSULTATION . $consultationId,
            $ttl,
            $callback
        );
    }
    
    /**
     * Get consultation history from cache or database
     *
     * @param int $patientId
     * @param int $limit
     * @param callable $callback Query callback
     * @param int $ttl Cache TTL in seconds
     * @return mixed
     */
    public function rememberConsultations(int $patientId, int $limit = 50, callable $callback = null, int $ttl = self::TTL_SHORT)
    {
        $cacheKey = self::PREFIX_CONSULTATIONS . "patient:{$patientId}:limit:{$limit}";
        
        return Cache::remember(
            $cacheKey,
            $ttl,
            $callback ?? function () {
                return [];
            }
        );
    }
    
    /**
     * Get triage result from cache
     *
     * @param string $sessionId
     * @param callable $callback Query callback
     * @param int $ttl Cache TTL in seconds
     * @return mixed
     */
    public function rememberTriage(string $sessionId, callable $callback, int $ttl = self::TTL_REALTIME)
    {
        return Cache::remember(
            self::PREFIX_TRIAGE . $sessionId,
            $ttl,
            $callback
        );
    }
    
    /**
     * Invalidate patient cache
     *
     * @param int $patientId
     * @return void
     */
    public function invalidatePatient(int $patientId): void
    {
        Cache::forget(self::PREFIX_PATIENT . $patientId);
    }
    
    /**
     * Invalidate all patients cache
     *
     * @return void
     */
    public function invalidatePatients(): void
    {
        // Clear pagination cache (invalidate all pages)
        for ($page = 1; $page <= 100; $page++) {
            for ($perPage = 15; $perPage <= 100; $perPage += 15) {
                Cache::forget(self::PREFIX_PATIENTS . "page:{$page}:per_page:{$perPage}");
            }
        }
    }
    
    /**
     * Invalidate consultation cache
     *
     * @param int $consultationId
     * @return void
     */
    public function invalidateConsultation(int $consultationId): void
    {
        Cache::forget(self::PREFIX_CONSULTATION . $consultationId);
    }
    
    /**
     * Invalidate patient consultations cache
     *
     * @param int $patientId
     * @return void
     */
    public function invalidatePatientConsultations(int $patientId): void
    {
        // Clear all limits for this patient
        for ($limit = 10; $limit <= 100; $limit += 10) {
            Cache::forget(self::PREFIX_CONSULTATIONS . "patient:{$patientId}:limit:{$limit}");
        }
    }
    
    /**
     * Invalidate triage cache
     *
     * @param string $sessionId
     * @return void
     */
    public function invalidateTriage(string $sessionId): void
    {
        Cache::forget(self::PREFIX_TRIAGE . $sessionId);
    }
    
    /**
     * Clear ALL caches (use carefully)
     *
     * @return void
     */
    public function clearAll(): void
    {
        Cache::flush();
    }
    
    /**
     * Check if specific cache key exists
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return Cache::has($key);
    }
    
    /**
     * Get cache statistics
     *
     * @return array
     */
    public function getStats(): array
    {
        return [
            'driver' => config('cache.default'),
            'ttl_realtime' => self::TTL_REALTIME,
            'ttl_short' => self::TTL_SHORT,
            'ttl_medium' => self::TTL_MEDIUM,
            'ttl_long' => self::TTL_LONG,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
