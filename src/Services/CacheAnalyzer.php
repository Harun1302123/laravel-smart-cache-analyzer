<?php

namespace SmartCache\Analyzer\Services;

use Illuminate\Cache\Repository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\DB;
use SmartCache\Analyzer\Models\CacheMetric;
use SmartCache\Analyzer\Models\QueryAnalysis;

class CacheAnalyzer
{
    protected Repository $cache;
    protected DatabaseManager $db;

    public function __construct(Repository $cache, DatabaseManager $db)
    {
        $this->cache = $cache;
        $this->db = $db;
    }

    /**
     * Get overall cache statistics.
     */
    public function getStats(): array
    {
        $metrics = CacheMetric::where('created_at', '>=', now()->subDay())
            ->get();

        $hits = $metrics->sum('hits');
        $misses = $metrics->sum('misses');
        $total = $hits + $misses;

        return [
            'hit_ratio' => $total > 0 ? round(($hits / $total) * 100, 2) : 0,
            'total_hits' => $hits,
            'total_misses' => $misses,
            'total_requests' => $total,
            'memory_usage' => $this->getCacheMemoryUsage(),
            'keys_count' => $this->getCacheKeysCount(),
        ];
    }

    /**
     * Analyze a query for caching potential.
     */
    public function analyzeQuery(string $signature, float $executionTime, ?string $normalizedQuery = null): void
    {
        $hash = md5($signature);
        $displayQuery = $normalizedQuery ?? $signature;
        
        QueryAnalysis::updateOrCreate(
            ['query_hash' => $hash],
            [
                'query' => $displayQuery,
                'execution_count' => DB::raw('execution_count + 1'),
                'total_time' => DB::raw("total_time + {$executionTime}"),
                'avg_time' => DB::raw("(total_time + {$executionTime}) / (execution_count + 1)"),
                'last_executed_at' => now(),
            ]
        );
    }

    /**
     * Get caching recommendations based on analysis.
     */
    public function getRecommendations(): array
    {
        $slowThreshold = config('smart-cache.slow_query_threshold', 100);
        $repeatedThreshold = config('smart-cache.repeated_query_threshold', 5);

        $slowQueries = QueryAnalysis::where('avg_time', '>', $slowThreshold)
            ->orderByDesc('avg_time')
            ->limit(10)
            ->get();

        $repeatedQueries = QueryAnalysis::where('execution_count', '>', $repeatedThreshold)
            ->orderByDesc('execution_count')
            ->limit(10)
            ->get();

        $recommendations = [];

        foreach ($slowQueries as $query) {
            $recommendations[] = [
                'type' => 'slow_query',
                'query' => $query->query,
                'reason' => "Query takes {$query->avg_time}ms on average",
                'suggested_ttl' => $this->suggestTTL($query),
                'priority' => 'high',
                'potential_savings' => $query->execution_count * $query->avg_time,
            ];
        }

        foreach ($repeatedQueries as $query) {
            if (!$slowQueries->contains('id', $query->id)) {
                $recommendations[] = [
                    'type' => 'repeated_query',
                    'query' => $query->query,
                    'reason' => "Executed {$query->execution_count} times",
                    'suggested_ttl' => $this->suggestTTL($query),
                    'priority' => 'medium',
                    'potential_savings' => $query->execution_count * $query->avg_time,
                ];
            }
        }

        return $recommendations;
    }

    /**
     * Get top queries by execution count.
     */
    public function getTopQueries(int $limit = 10): array
    {
        return QueryAnalysis::orderByDesc('execution_count')
            ->limit($limit)
            ->get()
            ->map(function ($query) {
                return [
                    'query' => $query->query,
                    'executions' => $query->execution_count,
                    'avg_time' => round($query->avg_time, 2),
                    'total_time' => round($query->total_time, 2),
                    'last_executed' => $query->last_executed_at->diffForHumans(),
                ];
            })
            ->toArray();
    }

    /**
     * Get cache hit ratio.
     */
    public function getHitRatio(): float
    {
        $stats = $this->getStats();
        return $stats['hit_ratio'];
    }

    /**
     * Suggest TTL based on query patterns.
     */
    protected function suggestTTL(QueryAnalysis $query): int
    {
        $sql = strtolower($query->query);
        
        // Static/reference data (rarely changes)
        if (preg_match('/\b(config|settings|countries|currencies)\b/', $sql)) {
            return config('smart-cache.default_ttls.configuration', 604800);
        }
        
        // User-specific data
        if (preg_match('/\bwhere.*user_id\b/', $sql)) {
            return config('smart-cache.default_ttls.user_data', 3600);
        }
        
        // Frequently changing data
        if (preg_match('/\b(orders|transactions|logs)\b/', $sql)) {
            return config('smart-cache.default_ttls.volatile_data', 300);
        }
        
        // Default for static data
        return config('smart-cache.default_ttls.static_data', 86400);
    }

    /**
     * Get cache memory usage (adapter-specific).
     */
    protected function getCacheMemoryUsage(): string
    {
        try {
            $store = $this->cache->getStore();
            
            if (method_exists($store, 'getRedis')) {
                $redis = $store->getRedis();
                $info = $redis->info('memory');
                return $info['used_memory_human'] ?? 'N/A';
            }
        } catch (\Exception $e) {
            // Silently fail
        }
        
        return 'N/A';
    }

    /**
     * Get count of cache keys.
     */
    protected function getCacheKeysCount(): int
    {
        try {
            $store = $this->cache->getStore();
            
            if (method_exists($store, 'getRedis')) {
                $redis = $store->getRedis();
                return $redis->dbSize();
            }
        } catch (\Exception $e) {
            // Silently fail
        }
        
        return 0;
    }

    /**
     * Get unused cache keys for cleanup.
     */
    public function getUnusedKeys(int $daysUnused = 7): array
    {
        return CacheMetric::where('last_hit_at', '<', now()->subDays($daysUnused))
            ->orWhereNull('last_hit_at')
            ->pluck('cache_key')
            ->toArray();
    }

    /**
     * Record cache hit/miss.
     */
    public function recordCacheAccess(string $key, bool $hit): void
    {
        $metric = CacheMetric::firstOrCreate(['cache_key' => $key]);
        
        if ($hit) {
            $metric->increment('hits');
            $metric->update(['last_hit_at' => now()]);
        } else {
            $metric->increment('misses');
        }
    }
}
