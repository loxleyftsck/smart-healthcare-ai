<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\RequestDeduplicationService;
use Illuminate\Http\Request;
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
