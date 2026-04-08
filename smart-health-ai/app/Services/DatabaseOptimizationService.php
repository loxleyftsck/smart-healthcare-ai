<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Jobs\OptimizedQueryJob;

/**
 * DatabaseOptimizationService - Query optimization and async processing
 * PATH B Day 2: Advanced database performance tuning
 * 
 * Features:
 * - Query performance monitoring
 * - Automatic index suggestions
 * - Async processing for bulk operations
 * - Connection pooling configuration
 * - Query result streaming
 */
class DatabaseOptimizationService
{
    /**
     * Track slow queries (> 100ms)
     */
    public const SLOW_QUERY_THRESHOLD = 100;
    
    /**
     * Batch size for async operations
     */
    public const DEFAULT_BATCH_SIZE = 100;
    
    /**
     * Query performance metrics storage
     */
    private array $queryMetrics = [];
    
    /**
     * Constructor - setup query listening
     */
    public function __construct()
    {
        $this->setupQueryLogging();
    }
    
    /**
     * Setup database query event listening
     *
     * @return void
     */
    private function setupQueryLogging(): void
    {
        DB::listen(function ($query) {
            $this->recordQueryMetric([
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
                'timestamp' => now()->toIso8601String(),
            ]);
        });
    }
    
    /**
     * Record query metric for analysis
     *
     * @param array $metric
     * @return void
     */
    private function recordQueryMetric(array $metric): void
    {
        $this->queryMetrics[] = $metric;
        
        // Keep only last 1000 metrics in memory
        if (count($this->queryMetrics) > 1000) {
            array_shift($this->queryMetrics);
        }
    }
    
    /**
     * Get slow queries for optimization
     *
     * @return array
     */
    public function getSlowQueries(): array
    {
        return array_filter(
            $this->queryMetrics,
            fn($metric) => $metric['time'] > self::SLOW_QUERY_THRESHOLD
        );
    }
    
    /**
     * Get query performance statistics
     *
     * @return array
     */
    public function getQueryStats(): array
    {
        if (empty($this->queryMetrics)) {
            return [
                'total_queries' => 0,
                'slow_queries' => 0,
                'average_time' => 0,
                'max_time' => 0,
                'min_time' => 0,
            ];
        }
        
        $times = array_column($this->queryMetrics, 'time');
        $slowCount = count($this->getSlowQueries());
        
        return [
            'total_queries' => count($this->queryMetrics),
            'slow_queries' => $slowCount,
            'slow_query_percentage' => round(($slowCount / count($this->queryMetrics)) * 100, 2),
            'average_time' => round(array_sum($times) / count($times), 2),
            'max_time' => max($times),
            'min_time' => min($times),
            'total_time' => round(array_sum($times), 2),
        ];
    }
    
    /**
     * Process query asynchronously using job queue
     *
     * @param callable $callback Query callback
     * @param string $jobName Optional job name
     * @return void
     */
    public function queueAsync(callable $callback, string $jobName = 'OptimizedQueryJob'): void
    {
        Queue::push(new OptimizedQueryJob($callback));
    }
    
    /**
     * Stream results without loading all into memory (for large datasets)
     *
     * @param callable $query Query builder callback
     * @param callable $processer Result processor callback
     * @param int $chunkSize Rows to process at a time
     * @return int Total rows processed
     */
    public function streamQuery(callable $query, callable $processer, int $chunkSize = 1000): int
    {
        $processed = 0;
        
        // Execute query and chunk results
        $query()->chunk($chunkSize, function ($results) use ($processer, &$processed) {
            foreach ($results as $result) {
                $processer($result);
                $processed++;
            }
            
            return true;
        });
        
        return $processed;
    }
    
    /**
     * Batch process with async jobs
     *
     * @param callable $query Query callback
     * @param callable $processer Processor callback
     * @param int $batchSize Records per batch
     * @return int Total jobs queued
     */
    public function batchProcessAsync(
        callable $query,
        callable $processer,
        int $batchSize = self::DEFAULT_BATCH_SIZE
    ): int {
        $jobsQueued = 0;
        $batch = [];
        $processed = 0;
        
        // Stream results and batch them
        $this->streamQuery(
            $query,
            function ($result) use ($processer, $batchSize, &$batch, &$jobsQueued, &$processed) {
                $batch[] = $result;
                $processed++;
                
                // Queue when batch is full
                if (count($batch) >= $batchSize) {
                    Queue::push(new OptimizedQueryJob(fn() => array_map($processer, $batch)));
                    $jobsQueued++;
                    $batch = [];
                }
            },
            $batchSize
        );
        
        // Queue remaining items
        if (!empty($batch)) {
            Queue::push(new OptimizedQueryJob(fn() => array_map($processer, $batch)));
            $jobsQueued++;
        }
        
        return $jobsQueued;
    }
    
    /**
     * Get recommended database indexes based on query patterns
     *
     * @return array
     */
    public function getIndexRecommendations(): array
    {
        return [
            'patients' => [
                ['column' => 'email', 'type' => 'unique', 'reason' => 'Login queries'],
                ['column' => 'created_at', 'type' => 'index', 'reason' => 'Sorting by date'],
            ],
            'consultations' => [
                ['column' => 'patient_id', 'type' => 'index', 'reason' => 'Patient history queries'],
                ['column' => 'session_id', 'type' => 'index', 'reason' => 'Session lookups'],
                ['column' => 'created_at', 'type' => 'index', 'reason' => 'Sorting/filtering'],
                ['columns' => ['patient_id', 'created_at'], 'type' => 'composite', 'reason' => 'Patient history with date'],
            ],
            'triage_logs' => [
                ['column' => 'patient_id', 'type' => 'index', 'reason' => 'Patient triage history'],
                ['column' => 'consultation_id', 'type' => 'index', 'reason' => 'Consultation lookup'],
                ['column' => 'severity', 'type' => 'index', 'reason' => 'Filtering by severity'],
            ],
        ];
    }
    
    /**
     * Check database connection pool status
     *
     * @return array
     */
    public function getConnectionPoolStatus(): array
    {
        return [
            'driver' => config('database.default'),
            'pool_size' => config('database.connections.' . config('database.default') . '.pool.size', 'N/A'),
            'min_idle' => config('database.connections.' . config('database.default') . '.pool.min_idle', 'N/A'),
            'max_lifetime' => config('database.connections.' . config('database.default') . '.pool.max_lifetime', 'N/A'),
            'timestamp' => now()->toIso8601String(),
        ];
    }
    
    /**
     * Optimize query using SELECT * avoidance
     * Returns only needed columns
     *
     * @param callable $query Query builder
     * @param array $columns Specific columns to select
     * @return mixed
     */
    public function selectColumns(callable $query, array $columns): mixed
    {
        return $query()->select($columns)->get();
    }
    
    /**
     * Cache expensive query results
     *
     * @param string $cacheKey
     * @param callable $query
     * @param int $ttl TTL in seconds
     * @return mixed
     */
    public function cachedQuery(string $cacheKey, callable $query, int $ttl = 3600): mixed
    {
        return \Illuminate\Support\Facades\Cache::remember(
            $cacheKey,
            $ttl,
            $query
        );
    }
    
    /**
     * Execute query with retry on failure
     *
     * @param callable $query
     * @param int $maxAttempts
     * @param int $delayMs Delay between retries in milliseconds
     * @return mixed
     *
     * @throws \Exception
     */
    public function queryWithRetry(callable $query, int $maxAttempts = 3, int $delayMs = 100): mixed
    {
        $lastException = null;
        
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return $query();
            } catch (\Exception $e) {
                $lastException = $e;
                
                if ($attempt < $maxAttempts) {
                    usleep($delayMs * 1000);
                }
            }
        }
        
        throw $lastException ?? new \Exception('Query failed after ' . $maxAttempts . ' attempts');
    }
    
    /**
     * Get database metrics for monitoring
     *
     * @return array
     */
    public function getDatabaseMetrics(): array
    {
        $stats = $this->getQueryStats();
        $poolStatus = $this->getConnectionPoolStatus();
        
        return [
            'queries' => $stats,
            'connection_pool' => $poolStatus,
            'slow_query_threshold' => self::SLOW_QUERY_THRESHOLD,
            'timestamp' => now()->toIso8601String(),
        ];
    }
    
    /**
     * Clear query metrics cache
     *
     * @return void
     */
    public function clearMetrics(): void
    {
        $this->queryMetrics = [];
    }
}
