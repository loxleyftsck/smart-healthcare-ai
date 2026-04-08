<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\RequestDeduplicationService;
use App\Services\QueryBatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class RequestDeduplicationServiceTest extends TestCase
{
    private RequestDeduplicationService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RequestDeduplicationService();
        Cache::flush();
    }
    
    /** @test */
    public function it_detects_new_requests(): void
    {
        $request = Request::create('/api/patients', 'POST', ['name' => 'John']);
        
        $result = $this->service->checkAndCache($request, 'user-1');
        
        $this->assertFalse($result['is_duplicate']);
        $this->assertNotNull($result['key']);
        $this->assertNull($result['result']);
    }
    
    /** @test */
    public function it_detects_duplicate_requests(): void
    {
        $request = Request::create('/api/patients', 'POST', ['name' => 'John']);
        
        // First request
        $first = $this->service->checkAndCache($request, 'user-1');
        $this->service->cacheResult($first['key'], ['id' => 1, 'name' => 'John']);
        
        // Duplicate request
        $duplicate = $this->service->checkAndCache($request, 'user-1');
        
        $this->assertTrue($duplicate['is_duplicate']);
        $this->assertEquals($first['key'], $duplicate['key']);
    }
    
    /** @test */
    public function it_extracts_idempotency_key(): void
    {
        $request = Request::create('/api/test');
        $request->headers->set('Idempotency-Key', 'test-123');
        
        $key = $this->service->getIdempotencyKey($request);
        
        $this->assertEquals('test-123', $key);
    }
    
    /** @test */
    public function it_caches_by_idempotency_key(): void
    {
        $idempotencyKey = 'test-123';
        $result = ['status' => 'success', 'id' => 1];
        
        $this->service->storeByIdempotencyKey($idempotencyKey, $result);
        $cached = $this->service->getByIdempotencyKey($idempotencyKey);
        
        $this->assertEquals($result, $cached);
    }
    
    /** @test */
    public function it_clears_deduplication_cache(): void
    {
        $request = Request::create('/api/test', 'POST', ['data' => 'value']);
        $result = $this->service->checkAndCache($request, 'user-1');
        
        $this->service->cacheResult($result['key'], ['id' => 1]);
        $this->service->clearCache($result['key']);
        
        $secondCheck = $this->service->checkAndCache($request, 'user-1');
        $this->assertFalse($secondCheck['is_duplicate']);
    }
}

class QueryBatchingServiceTest extends TestCase
{
    private QueryBatchingService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QueryBatchingService();
    }
    
    /** @test */
    public function it_handles_empty_collections(): void
    {
        $empty = collect();
        $result = $this->service->loadMany($empty, 'consultations');
        
        $this->assertTrue($result->isEmpty());
    }
    
    /** @test */
    public function it_counts_batch_items(): void
    {
        $items = collect([
            (object)['id' => 1],
            (object)['id' => 2],
            (object)['id' => 3],
        ]);
        
        $count = $this->service->countBatch($items);
        
        $this->assertEquals(3, $count);
    }
    
    /** @test */
    public function it_chunks_batch_operations(): void
    {
        $ids = array_map(fn($i) => $i, range(1, 250));
        
        $results = $this->service->processBatch(
            $ids,
            fn($chunk) => collect(array_map(fn($id) => (object)['id' => $id], $chunk)),
            100
        );
        
        $this->assertEquals(250, $results->count());
    }
    
    /** @test */
    public function it_maps_fetched_results(): void
    {
        $ids = [1, 2, 3];
        $items = [
            (object)['id' => 1, 'name' => 'John'],
            (object)['id' => 2, 'name' => 'Jane'],
            (object)['id' => 3, 'name' => 'Bob'],
        ];
        
        $mapped = $this->service->fetchAndMap(
            $ids,
            fn($ids) => collect($items)
        );
        
        $this->assertCount(3, $mapped);
        $this->assertEquals('John', $mapped[1]->name);
    }
}
