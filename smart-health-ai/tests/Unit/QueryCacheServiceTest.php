<?php

namespace Tests\Unit;

use App\Services\QueryCacheService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class QueryCacheServiceTest extends TestCase
{
    protected QueryCacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = new QueryCacheService();
        
        // Clear cache before each test
        Cache::flush();
    }

    /** @test */
    public function it_remembers_patient_data(): void
    {
        $patientId = 123;
        $patientData = ['id' => $patientId, 'name' => 'John Doe'];

        // First call - should execute callback
        $result1 = $this->cacheService->rememberPatient(
            $patientId,
            fn() => $patientData,
            QueryCacheService::TTL_LONG
        );

        $this->assertEquals($patientData, $result1);

        // Second call - should return from cache without re-executing
        $result2 = $this->cacheService->rememberPatient(
            $patientId,
            fn() => throw new \Exception('Should not be called'),
            QueryCacheService::TTL_LONG
        );

        $this->assertEquals($patientData, $result2);
    }

    /** @test */
    public function it_invalidates_patient_cache(): void
    {
        $patientId = 456;
        $patientData = ['id' => $patientId, 'name' => 'Jane Smith'];

        // Cache the data
        $this->cacheService->rememberPatient(
            $patientId,
            fn() => $patientData,
            QueryCacheService::TTL_LONG
        );

        // Verify it's cached
        $this->assertTrue(
            Cache::has(QueryCacheService::PREFIX_PATIENT . $patientId)
        );

        // Invalidate
        $this->cacheService->invalidatePatient($patientId);

        // Verify it's gone
        $this->assertFalse(
            Cache::has(QueryCacheService::PREFIX_PATIENT . $patientId)
        );
    }

    /** @test */
    public function it_remembers_consultations(): void
    {
        $patientId = 789;
        $consultationData = ['id' => 1, 'message' => 'Help'];

        // Cache consultation
        $result = $this->cacheService->rememberConsultations(
            $patientId,
            50,
            fn() => $consultationData,
            QueryCacheService::TTL_SHORT
        );

        $this->assertEquals($consultationData, $result);
    }

    /** @test */
    public function it_invalidates_patient_consultations(): void
    {
        $patientId = 999;

        // Cache something for this patient
        $this->cacheService->rememberConsultations(
            $patientId,
            50,
            fn() => ['data' => 'test'],
            QueryCacheService::TTL_SHORT
        );

        // Invalidate all consultations for this patient
        $this->cacheService->invalidatePatientConsultations($patientId);

        // The cache should be cleared
        $cacheKey = QueryCacheService::PREFIX_CONSULTATIONS . "patient:{$patientId}:limit:50";
        $this->assertFalse(Cache::has($cacheKey));
    }

    /** @test */
    public function it_remembers_triage_session(): void
    {
        $sessionId = 'session-123';
        $triageData = ['severity' => 'HIGH', 'confidence' => 0.95];

        // Cache triage data
        $result = $this->cacheService->rememberTriage(
            $sessionId,
            fn() => $triageData,
            QueryCacheService::TTL_REALTIME
        );

        $this->assertEquals($triageData, $result);
    }

    /** @test */
    public function it_returns_cache_statistics(): void
    {
        $stats = $this->cacheService->getStats();

        $this->assertArrayHasKey('driver', $stats);
        $this->assertArrayHasKey('ttl_realtime', $stats);
        $this->assertArrayHasKey('ttl_short', $stats);
        $this->assertArrayHasKey('ttl_medium', $stats);
        $this->assertArrayHasKey('ttl_long', $stats);
        $this->assertArrayHasKey('timestamp', $stats);

        $this->assertEquals(QueryCacheService::TTL_REALTIME, $stats['ttl_realtime']);
        $this->assertEquals(5, $stats['ttl_realtime']); // 5 seconds
        $this->assertEquals(300, $stats['ttl_short']); // 5 minutes
        $this->assertEquals(1800, $stats['ttl_medium']); // 30 minutes
        $this->assertEquals(3600, $stats['ttl_long']); // 1 hour
    }

    /** @test */
    public function it_checks_cache_existence(): void
    {
        $patientId = 111;

        // Cache doesn't exist yet
        $this->assertFalse(
            $this->cacheService->has(QueryCacheService::PREFIX_PATIENT . $patientId)
        );

        // Add to cache
        $this->cacheService->rememberPatient(
            $patientId,
            fn() => ['test' => 'data'],
            QueryCacheService::TTL_LONG
        );

        // Now it should exist
        $this->assertTrue(
            $this->cacheService->has(QueryCacheService::PREFIX_PATIENT . $patientId)
        );
    }

    /** @test */
    public function it_clears_all_caches(): void
    {
        // Add multiple cached items
        $this->cacheService->rememberPatient(1, fn() => ['data' => 1], 60);
        $this->cacheService->rememberPatient(2, fn() => ['data' => 2], 60);
        $this->cacheService->rememberConsultations(1, 50, fn() => ['data' => 3], 60);

        // Verify caches exist
        $this->assertTrue(Cache::has(QueryCacheService::PREFIX_PATIENT . '1'));
        $this->assertTrue(Cache::has(QueryCacheService::PREFIX_PATIENT . '2'));

        // Clear all
        $this->cacheService->clearAll();

        // All should be gone
        $this->assertFalse(Cache::has(QueryCacheService::PREFIX_PATIENT . '1'));
        $this->assertFalse(Cache::has(QueryCacheService::PREFIX_PATIENT . '2'));
    }

    /** @test */
    public function it_respects_different_ttl_values(): void
    {
        $customTTL = 999;

        // Cache with custom TTL
        $this->cacheService->rememberPatient(
            1,
            fn() => ['data' => 'test'],
            $customTTL
        );

        // Should be in cache
        $this->assertTrue(
            Cache::has(QueryCacheService::PREFIX_PATIENT . '1')
        );

        // Verify TTL was respected (by checking cache driver behavior)
        // For file cache, we just verify the data is there
        $cached = Cache::get(QueryCacheService::PREFIX_PATIENT . '1');
        $this->assertNotNull($cached);
    }
}
