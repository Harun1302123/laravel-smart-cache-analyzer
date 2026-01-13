<?php

namespace SmartCache\Analyzer\Console\Commands;

use Illuminate\Console\Command;
use SmartCache\Analyzer\Services\CacheAnalyzer;

class AnalyzeCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cache:analyze
                            {--json : Output results as JSON}
                            {--top=10 : Number of top queries to display}';

    /**
     * The console command description.
     */
    protected $description = 'Analyze cache performance and generate recommendations';

    /**
     * Execute the console command.
     */
    public function handle(CacheAnalyzer $analyzer): int
    {
        $this->info('🔍 Analyzing cache performance...');
        $this->newLine();

        // Get statistics
        $stats = $analyzer->getStats();
        
        if ($this->option('json')) {
            $this->line(json_encode([
                'stats' => $stats,
                'recommendations' => $analyzer->getRecommendations(),
                'top_queries' => $analyzer->getTopQueries($this->option('top')),
            ], JSON_PRETTY_PRINT));
            
            return self::SUCCESS;
        }

        // Display statistics
        $this->displayStats($stats);
        $this->newLine();

        // Display recommendations
        $this->displayRecommendations($analyzer->getRecommendations());
        $this->newLine();

        // Display top queries
        $this->displayTopQueries($analyzer->getTopQueries($this->option('top')));

        return self::SUCCESS;
    }

    /**
     * Display cache statistics.
     */
    protected function displayStats(array $stats): void
    {
        $this->components->twoColumnDetail('📊 Cache Statistics', '');
        $this->components->twoColumnDetail('Hit Ratio', $stats['hit_ratio'] . '%');
        $this->components->twoColumnDetail('Total Hits', number_format($stats['total_hits']));
        $this->components->twoColumnDetail('Total Misses', number_format($stats['total_misses']));
        $this->components->twoColumnDetail('Total Requests', number_format($stats['total_requests']));
        $this->components->twoColumnDetail('Memory Usage', $stats['memory_usage']);
        $this->components->twoColumnDetail('Cache Keys', number_format($stats['keys_count']));
    }

    /**
     * Display caching recommendations.
     */
    protected function displayRecommendations(array $recommendations): void
    {
        $this->components->twoColumnDetail('💡 Caching Recommendations', '');
        
        if (empty($recommendations)) {
            $this->components->info('No recommendations at this time. Your cache is performing well!');
            return;
        }

        foreach ($recommendations as $rec) {
            $priority = $rec['priority'] === 'high' ? '🔴' : '🟡';
            $this->components->twoColumnDetail(
                $priority . ' ' . $rec['reason'],
                'TTL: ' . $rec['suggested_ttl'] . 's'
            );
            $this->components->bulletList([
                'Query: ' . \Illuminate\Support\Str::limit($rec['query'], 80),
                'Potential savings: ' . round($rec['potential_savings'], 2) . 'ms',
            ]);
            $this->newLine();
        }
    }

    /**
     * Display top queries.
     */
    protected function displayTopQueries(array $queries): void
    {
        $this->components->twoColumnDetail('🔥 Top Queries by Execution Count', '');
        
        if (empty($queries)) {
            $this->components->info('No query data available yet.');
            return;
        }

        $headers = ['Query', 'Executions', 'Avg Time', 'Total Time', 'Last Executed'];
        $rows = array_map(function ($query) {
            return [
                \Illuminate\Support\Str::limit($query['query'], 60),
                number_format($query['executions']),
                $query['avg_time'] . 'ms',
                $query['total_time'] . 'ms',
                $query['last_executed'],
            ];
        }, $queries);

        $this->table($headers, $rows);
    }
}
