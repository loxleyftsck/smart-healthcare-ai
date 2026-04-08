<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * QueryBatchingService - Combine multiple queries into one
 * PATH B Day 3: Reduce query count and network overhead
 * 
 * Pattern: N+1 Query Problem Solution
 * Instead of:   Patient -> get consultations -> get trials (3 queries)
 * Use batching: Get all at once (1-2 queries)
 */
class QueryBatchingService
{
    /**
     * Batch load related models (solves N+1 problem)
     *
     * @param Collection $models
     * @param string|array $relations Eager load relations
     * @return Collection
     */
    public function loadMany(Collection $models, string|array $relations): Collection
    {
        if ($models->isEmpty()) {
            return $models;
        }
        
        return $models->load($relations);
    }
    
    /**
     * Batch load with constraints
     *
     * @param Collection $models
     * @param array $relations Format: ['relation' => callable]
     * @return Collection
     */
    public function loadManyWith(Collection $models, array $relations): Collection
    {
        if ($models->isEmpty()) {
            return $models;
        }
        
        foreach ($relations as $relation => $callback) {
            $models->load([$relation => $callback]);
        }
        
        return $models;
    }
    
    /**
     * Batch process many items with deferred queries
     *
     * @param array $ids
     * @param callable $query Query builder callback
     * @param int $batchSize Size of batches to process
     * @return Collection
     */
    public function processBatch(array $ids, callable $query, int $batchSize = 100): Collection
    {
        $results = collect();
        
        // Split IDs into chunks
        $chunks = array_chunk($ids, $batchSize);
        
        foreach ($chunks as $chunk) {
            // Execute query for this batch
            $batchResults = $query($chunk);
            $results = $results->concat($batchResults);
        }
        
        return $results;
    }
    
    /**
     * Batch update multiple records
     *
     * @param Collection $models Model instances
     * @param array $data Data to update
     * @return int Total updated
     */
    public function updateBatch(Collection $models, array $data): int
    {
        $updated = 0;
        
        foreach ($models as $model) {
            if ($model->update($data)) {
                $updated++;
            }
        }
        
        return $updated;
    }
    
    /**
     * Batch insert records (more efficient than individual saves)
     *
     * @param string $modelClass Fully qualified model class
     * @param array $records Array of model data
     * @param int $chunkSize Max records per insert
     * @return int Total inserted
     */
    public function insertBatch(string $modelClass, array $records, int $chunkSize = 1000): int
    {
        if (empty($records)) {
            return 0;
        }
        
        $inserted = 0;
        $chunks = array_chunk($records, $chunkSize);
        
        foreach ($chunks as $chunk) {
            if ($modelClass::insert($chunk)) {
                $inserted += count($chunk);
            }
        }
        
        return $inserted;
    }
    
    /**
     * Batch delete records
     *
     * @param Collection $models
     * @return int Total deleted
     */
    public function deleteBatch(Collection $models): int
    {
        $deleted = 0;
        
        foreach ($models as $model) {
            if ($model->delete()) {
                $deleted++;
            }
        }
        
        return $deleted;
    }
    
    /**
     * Count records without loading all data
     *
     * @param Collection $models
     * @return int
     */
    public function countBatch(Collection $models): int
    {
        return $models->count();
    }
    
    /**
     * Get related totals in single batched query
     *
     * Example: Get all patients with their consultation counts
     *
     * @param string $modelClass
     * @param string $relationName
     * @param string $countAlias
     * @return Collection
     */
    public function withCounts(string $modelClass, string $relationName, string $countAlias = null): Collection
    {
        $countAlias = $countAlias ?? $relationName . '_count';
        
        return $modelClass::withCount($relationName)
            ->get()
            ->mapWithKeys(function ($model) use ($countAlias) {
                return [$model->id => $model->{$countAlias}];
            });
    }
    
    /**
     * Batch fetch and map (common pattern)
     *
     * @param array $ids
     * @param callable $query
     * @return Collection Keyed by ID
     */
    public function fetchAndMap(array $ids, callable $query): Collection
    {
        $results = $query($ids);
        
        return $results->keyBy(function ($item) {
            return $item->id ?? $item->getId();
        });
    }
}
