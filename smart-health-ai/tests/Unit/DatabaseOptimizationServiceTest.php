<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\DatabaseOptimizationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DatabaseOptimizationServiceTest extends TestCase
{
    private DatabaseOptimizationService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DatabaseOptimizationService();
    }
    
    /** @test */
    public function it_tracks_query_metrics(): void
    {
        // Get stats before
        $statsBefore = $this->service->getQueryStats();
        
        // Perform a query (simulated)
        $stats = $this->service->getQueryStats();
        
        // Verify stats structure
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_queries', $stats);
        $this->assertArrayHasKey('slow_queries', $stats);
        $this->assertArrayHasKey('average_time', $stats);
    }
    
    /** @test */
    public function it_identifies_slow_queries(): void
    {
        $slowQueries = $this->service->getSlowQueries();
        $this->assertIsArray($slowQueries);
    }
    
    /** @test */
    public function it_returns_query_statistics(): void
    {
        $stats = $this->service->getQueryStats();
        
        $this->assertIsArray($stats);
        $this->assertGreaterThanOrEqual(0, $stats['total_queries']);
        $this->assertGreaterThanOrEqual(0, $stats['slow_queries']);
        $this->assertGreaterThanOrEqual(0, $stats['average_time']);
    }
    
    /** @test */
    public function it_provides_index_recommendations(): void
    {
        $recommendations = $this->service->getIndexRecommendations();
        
        $this->assertIsArray($recommendations);
        $this->assertArrayHasKey('patients', $recommendations);
        $this->assertArrayHasKey('consultations', $recommendations);
        $this->assertArrayHasKey('triage_logs', $recommendations);
        
        // Verify structure of recommendations
        foreach ($recommendations['patients'] as $index) {
            $this->assertArrayHasKey('column', $index);
            $this->assertArrayHasKey('type', $index);
            $this->assertArrayHasKey('reason', $index);
        }
    }
    
    /** @test */
    public function it_returns_connection_pool_status(): void
    {
        $status = $this->service->getConnectionPoolStatus();
        
        $this->assertIsArray($status);
        $this->assertArrayHasKey('driver', $status);
        $this->assertArrayHasKey('pool_size', $status);
        $this->assertArrayHasKey('min_idle', $status);
        $this->assertArrayHasKey('timestamp', $status);
    }
    
    /** @test */
    public function it_executes_query_with_retry(): void
    {
        $callCount = 0;
        $result = $this->service->queryWithRetry(
            function () use (&$callCount) {
                $callCount++;
                return 'success';
            }
        );
        
        $this->assertEquals('success', $result);
        $this->assertEquals(1, $callCount);
    }
    
    /** @test */
    public function it_retries_on_failure(): void
    {
        $callCount = 0;
        
        try {
            $this->service->queryWithRetry(
                function () use (&$callCount) {
                    $callCount++;
                    throw new \Exception('Test failure');
                },
                3,
                10
            );
        } catch (\Exception $e) {
            $this->assertEquals(3, $callCount);
            $this->assertEquals('Test failure', $e->getMessage());
        }
    }
    
    /** @test */
    public function it_returns_database_metrics(): void
    {
        $metrics = $this->service->getDatabaseMetrics();
        
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('queries', $metrics);
        $this->assertArrayHasKey('connection_pool', $metrics);
        $this->assertArrayHasKey('slow_query_threshold', $metrics);
        $this->assertArrayHasKey('timestamp', $metrics);
        
        $this->assertEquals(
            DatabaseOptimizationService::SLOW_QUERY_THRESHOLD,
            $metrics['slow_query_threshold']
        );
    }
    
    /** @test */
    public function it_clears_metrics(): void
    {
        $before = $this->service->getQueryStats();
        
        $this->service->clearMetrics();
        
        $after = $this->service->getQueryStats();
        
        // After clearing, should have no/reset metrics
        $this->assertEquals(0, $after['total_queries']);
    }
}
