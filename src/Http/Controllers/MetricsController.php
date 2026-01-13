<?php

namespace SmartCache\Analyzer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use SmartCache\Analyzer\Services\CacheAnalyzer;
use SmartCache\Analyzer\Models\CacheRecommendation;

class MetricsController extends Controller
{
    protected CacheAnalyzer $analyzer;

    public function __construct(CacheAnalyzer $analyzer)
    {
        $this->analyzer = $analyzer;
    }

    /**
     * Export metrics in Prometheus format.
     */
    public function prometheus(): Response
    {
        $stats = $this->analyzer->getStats();
        $recommendations = CacheRecommendation::pending()->count();
        
        $metrics = [];
        
        // Cache hit ratio
        $metrics[] = '# HELP smart_cache_hit_ratio Cache hit ratio percentage';
        $metrics[] = '# TYPE smart_cache_hit_ratio gauge';
        $metrics[] = sprintf('smart_cache_hit_ratio %s', $stats['hit_ratio']);
        
        // Total hits
        $metrics[] = '# HELP smart_cache_hits_total Total cache hits';
        $metrics[] = '# TYPE smart_cache_hits_total counter';
        $metrics[] = sprintf('smart_cache_hits_total %s', $stats['total_hits']);
        
        // Total misses
        $metrics[] = '# HELP smart_cache_misses_total Total cache misses';
        $metrics[] = '# TYPE smart_cache_misses_total counter';
        $metrics[] = sprintf('smart_cache_misses_total %s', $stats['total_misses']);
        
        // Keys count
        $metrics[] = '# HELP smart_cache_keys_count Number of cache keys';
        $metrics[] = '# TYPE smart_cache_keys_count gauge';
        $metrics[] = sprintf('smart_cache_keys_count %s', $stats['keys_count']);
        
        // Pending recommendations
        $metrics[] = '# HELP smart_cache_recommendations_pending Pending cache recommendations';
        $metrics[] = '# TYPE smart_cache_recommendations_pending gauge';
        $metrics[] = sprintf('smart_cache_recommendations_pending %s', $recommendations);
        
        // Driver-specific metrics
        if (isset($stats['driver_stats'])) {
            $driverStats = $stats['driver_stats'];
            
            // Memory metrics (Redis/Memcached)
            if (isset($driverStats['memory']['bytes_used'])) {
                $metrics[] = '# HELP smart_cache_memory_bytes Memory usage in bytes';
                $metrics[] = '# TYPE smart_cache_memory_bytes gauge';
                $metrics[] = sprintf('smart_cache_memory_bytes{driver="%s"} %s', 
                    $stats['driver'], 
                    $driverStats['memory']['bytes_used']
                );
            }
            
            // Evictions (Redis/Memcached)
            if (isset($driverStats['evictions']['evicted_keys'])) {
                $metrics[] = '# HELP smart_cache_evictions_total Total evicted keys';
                $metrics[] = '# TYPE smart_cache_evictions_total counter';
                $metrics[] = sprintf('smart_cache_evictions_total{driver="%s"} %s', 
                    $stats['driver'], 
                    $driverStats['evictions']['evicted_keys']
                );
            }
            
            // Disk usage (File cache)
            if (isset($driverStats['disk_usage']['total_size'])) {
                $metrics[] = '# HELP smart_cache_disk_bytes Disk usage in bytes';
                $metrics[] = '# TYPE smart_cache_disk_bytes gauge';
                $metrics[] = sprintf('smart_cache_disk_bytes %s', 
                    $driverStats['disk_usage']['total_size']
                );
            }
        }
        
        return response(implode("\n", $metrics) . "\n")
            ->header('Content-Type', 'text/plain; version=0.0.4');
    }

    /**
     * Get real-time stats for WebSocket streaming.
     */
    public function stream(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $this->analyzer->getStats(),
                'top_queries' => $this->analyzer->getTopQueries(5),
                'recommendations' => $this->analyzer->getRecommendations(),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Health check endpoint.
     */
    public function health(): JsonResponse
    {
        $enabled = config('smart-cache.enabled', false);
        
        return response()->json([
            'status' => $enabled ? 'healthy' : 'disabled',
            'enabled' => $enabled,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
