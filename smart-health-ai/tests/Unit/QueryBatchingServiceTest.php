<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\QueryBatchingService;

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
