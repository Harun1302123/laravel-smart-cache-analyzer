<?php

namespace SmartCache\Analyzer\Console\Commands;

use Illuminate\Console\Command;
use SmartCache\Analyzer\Services\CacheAnalyzer;
use SmartCache\Analyzer\Models\QueryAnalysis;

class WarmCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cache:warm
                            {--top=20 : Number of top queries to warm}
                            {--force : Skip confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Warm cache with frequently executed queries';

    /**
     * Execute the console command.
     */
    public function handle(CacheAnalyzer $analyzer): int
    {
        $limit = (int) $this->option('top');

        $this->info("🔥 Preparing to warm cache with top {$limit} queries...");
        $this->newLine();

        $topQueries = QueryAnalysis::orderByDesc('execution_count')
            ->limit($limit)
            ->get();

        if ($topQueries->isEmpty()) {
            $this->components->warn('No query data available for cache warming');
            $this->components->info('Run your application to collect query data first');
            return self::SUCCESS;
        }

        $this->components->info('Top queries to warm:');
        foreach ($topQueries->take(5) as $query) {
            $this->components->bulletList([
                \Illuminate\Support\Str::limit($query->query, 80) . ' (' . $query->execution_count . ' executions)'
            ]);
        }

        if ($topQueries->count() > 5) {
            $this->components->info('... and ' . ($topQueries->count() - 5) . ' more');
        }

        $this->newLine();

        if (!$this->option('force') && !$this->confirm('Do you want to execute these queries to warm the cache?', false)) {
            $this->components->info('Cache warming cancelled');
            return self::SUCCESS;
        }

        $warmed = 0;
        $failed = 0;
        $progressBar = $this->output->createProgressBar($topQueries->count());
        $progressBar->start();

        foreach ($topQueries as $queryAnalysis) {
            try {
                // Execute the query to warm cache
                // Note: This is a simplified version. In production, you'd need
                // to properly reconstruct and execute queries with proper bindings
                \DB::select($queryAnalysis->query);
                $warmed++;
            } catch (\Exception $e) {
                $failed++;
                // Log error but continue
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->components->info("Successfully warmed {$warmed} queries");
        
        if ($failed > 0) {
            $this->components->warn("{$failed} queries failed to warm");
        }

        return self::SUCCESS;
    }
}
