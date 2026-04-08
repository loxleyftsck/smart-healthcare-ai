<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * OptimizedQueryJob - Async job for database operations
 * Executes database operations in background queues
 */
class OptimizedQueryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    /**
     * Callback to execute
     */
    private $callback;
    
    /**
     * Constructor
     *
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }
    
    /**
     * Execute the job
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            $callback = $this->callback;
            $callback();
        } catch (\Exception $e) {
            \Log::error('OptimizedQueryJob failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
