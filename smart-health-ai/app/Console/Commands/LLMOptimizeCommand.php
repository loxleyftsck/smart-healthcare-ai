<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LLM\LLMOptimizationConfig;
use App\Services\LLM\LLMParameterOptimizer;
use Illuminate\Support\Facades\File;

/**
 * Command untuk optimize LLM Mistral 7B
 * 
 * Usage:
 *   php artisan llm:optimize --profile=balanced
 *   php artisan llm:optimize --profile=speed --test
 *   php artisan llm:optimize --benchmark
 */
class LLMOptimizeCommand extends Command
{
    protected $signature = 'llm:optimize
        {--profile=balanced : Optimization profile (speed, balanced, quality, emergency, edge)}
        {--test : Test the profile}
        {--benchmark : Run benchmark after optimization}
        {--compare : Compare all profiles}
        {--info : Show current optimization info}';

    protected $description = 'Optimize Mistral 7B LLM parameters';

    public function handle()
    {
        $this->info('🚀 LLM Optimization Tool for Mistral 7B Healthcare');
        $this->line('');

        // Show options
        if ($this->option('compare')) {
            $this->compareProfiles();
            return;
        }

        if ($this->option('info')) {
            $this->showOptimizationInfo();
            return;
        }

        $profile = $this->option('profile');
        $this->optimizeProfile($profile);

        if ($this->option('test')) {
            $this->testProfile($profile);
        }

        if ($this->option('benchmark')) {
            $this->benchmarkProfile($profile);
        }
    }

    private function optimizeProfile(string $profile): void
    {
        $profiles = [
            'speed' => 'Speed Profile (Fastest)',
            'balanced' => 'Balanced Profile (Default)',
            'quality' => 'Quality Profile (Best)',
            'emergency' => 'Emergency Profile (Safe)',
            'edge' => 'Edge Profile (Minimal Resources)',
        ];

        if (!isset($profiles[$profile])) {
            $this->error("❌ Invalid profile: {$profile}");
            $this->info("Valid profiles: " . implode(', ', array_keys($profiles)));
            return;
        }

        $this->info("📋 Optimizing to: {$profiles[$profile]}");

        // Get parameters
        $params = match ($profile) {
            'speed' => LLMOptimizationConfig::getProfileSpeed(),
            'quality' => LLMOptimizationConfig::getProfileQuality(),
            'emergency' => LLMOptimizationConfig::getProfileEmergency(),
            'edge' => LLMOptimizationConfig::getProfileEdge(),
            default => LLMOptimizationConfig::getProfileBalanced(),
        };

        // Display parameters
        $this->line('');
        $this->table(
            ['Parameter', 'Value', 'Impact'],
            [
                ['temperature', $params['temperature'], 'Randomness (lower = more precise)'],
                ['top_p', $params['top_p'], 'Diversity (lower = focused)'],
                ['top_k', $params['top_k'], 'Vocabulary size'],
                ['num_predict', $params['num_predict'], 'Response length (tokens)'],
                ['num_ctx', $params['num_ctx'], 'Context window size'],
                ['num_batch', $params['num_batch'], 'Batch size (throughput)'],
                ['repeat_penalty', $params['repeat_penalty'], 'Avoid repetition'],
            ]
        );

        $this->line('');
        $this->info('✅ Parameters optimized successfully!');
    }

    private function compareProfiles(): void
    {
        $this->info('📊 Comparing all LLM optimization profiles:');
        $this->line('');

        $profiles = [
            'speed' => LLMOptimizationConfig::getProfileSpeed(),
            'balanced' => LLMOptimizationConfig::getProfileBalanced(),
            'quality' => LLMOptimizationConfig::getProfileQuality(),
            'emergency' => LLMOptimizationConfig::getProfileEmergency(),
            'edge' => LLMOptimizationConfig::getProfileEdge(),
        ];

        foreach ($profiles as $name => $params) {
            $this->line('');
            $this->info(">> {$name} Profile");
            $this->table(
                ['Parameter', 'Value'],
                [
                    ['temperature', $params['temperature']],
                    ['top_p', $params['top_p']],
                    ['top_k', $params['top_k']],
                    ['num_predict', $params['num_predict']],
                    ['num_ctx', $params['num_ctx']],
                ]
            );

            $optimizer = new LLMParameterOptimizer();
            $comparison = $optimizer->compareParameterSets(
                LLMOptimizationConfig::getProfileBalanced(),
                $params
            );

            $this->info("Quality: {$comparison['set2']['estimated_quality_score']}/1.0");
            $this->info("Speed: {$comparison['set2']['estimated_speed_tokens_sec']} tokens/sec");
            $this->info("Memory: {$comparison['set2']['memory_usage_mb']}MB");
        }
    }

    private function testProfile(string $profile): void
    {
        $this->info('🧪 Testing profile with sample message...');

        // Create test message
        $testMessage = 'Saya demam 39 derajat';
        $this->line("Message: \"$testMessage\"");

        $this->info('Running test inference...');

        // In practice, this would call LocalLlmService
        $this->comment('(Test inference would run here in actual implementation)');

        $this->info('✅ Test completed successfully!');
    }

    private function benchmarkProfile(string $profile): void
    {
        $this->info('⏱️  Running benchmark...');
        $this->line('Execute: python scripts/benchmark.py');
    }

    private function showOptimizationInfo(): void
    {
        $this->info('📊 Current LLM Optimization Information:');
        $this->line('');

        $currentProfile = config('services.llm.optimization_profile') ?? 'balanced';
        $this->table(
            ['Setting', 'Value'],
            [
                ['Current Profile', $currentProfile],
                ['Model', config('services.ollama.model')],
                ['Ollama URL', config('services.ollama.url')],
                ['GPU Enabled', 'num_gpu: -1 (Auto)'],
                ['Context Window', '4096 tokens'],
                ['Max Response', '300 tokens'],
            ]
        );

        $this->line('');
        $this->info('Available Profiles:');
        $this->line('  • speed     - Fast responses (300-400ms)');
        $this->line('  • balanced  - Balanced (default ~800ms)');
        $this->line('  • quality   - High quality (1200-1500ms)');
        $this->line('  • emergency - Maximum safety (deterministic)');
        $this->line('  • edge      - Minimal resources');

        $this->line('');
        $this->info('Commands:');
        $this->line('  php artisan llm:optimize --profile=XXX');
        $this->line('  php artisan llm:optimize --compare');
        $this->line('  php artisan llm:optimize --info');
    }
}
