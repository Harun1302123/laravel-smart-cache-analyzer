<?php

namespace SmartCache\Analyzer\Services\Drivers;

class MemcachedDriverAnalyzer extends CacheDriverAnalyzer
{
    /**
     * Get Memcached-specific statistics.
     */
    public function getStats(): array
    {
        $stats = [
            'driver' => 'memcached',
            'memory' => null,
            'evictions' => null,
            'hit_rate' => null,
        ];

        try {
            $memcached = $this->getMemcachedConnection();
            $serverStats = $memcached->getStats();
            
            // Get stats from first server
            $firstServer = reset($serverStats);
            
            if ($this->config['analyze_memory'] ?? true) {
                $stats['memory'] = $this->analyzeMemory($firstServer);
            }
            
            if ($this->config['track_evictions'] ?? true) {
                $stats['evictions'] = $this->trackEvictions($firstServer);
            }
            
            if ($this->config['monitor_hit_rate'] ?? true) {
                $stats['hit_rate'] = $this->calculateHitRate($firstServer);
            }
            
        } catch (\Exception $e) {
            $stats['error'] = $e->getMessage();
        }

        return $stats;
    }

    /**
     * Check if Memcached supports specific feature.
     */
    public function supports(string $feature): bool
    {
        return in_array($feature, [
            'memory_analysis',
            'eviction_tracking',
            'hit_rate_monitoring',
        ]);
    }

    /**
     * Analyze memory usage.
     */
    protected function analyzeMemory(array $stats): array
    {
        return [
            'bytes_used' => $stats['bytes'] ?? 0,
            'bytes_used_human' => $this->formatBytes($stats['bytes'] ?? 0),
            'limit_maxbytes' => $stats['limit_maxbytes'] ?? 0,
            'limit_maxbytes_human' => $this->formatBytes($stats['limit_maxbytes'] ?? 0),
            'usage_percent' => $this->calculateUsagePercent($stats),
        ];
    }

    /**
     * Track eviction statistics.
     */
    protected function trackEvictions(array $stats): array
    {
        return [
            'evictions' => $stats['evictions'] ?? 0,
            'reclaimed' => $stats['reclaimed'] ?? 0,
            'curr_items' => $stats['curr_items'] ?? 0,
            'total_items' => $stats['total_items'] ?? 0,
        ];
    }

    /**
     * Calculate hit rate.
     */
    protected function calculateHitRate(array $stats): float
    {
        $hits = $stats['get_hits'] ?? 0;
        $misses = $stats['get_misses'] ?? 0;
        $total = $hits + $misses;
        
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }

    /**
     * Calculate memory usage percentage.
     */
    protected function calculateUsagePercent(array $stats): float
    {
        $used = $stats['bytes'] ?? 0;
        $max = $stats['limit_maxbytes'] ?? 0;
        
        return $max > 0 ? round(($used / $max) * 100, 2) : 0;
    }

    /**
     * Get Memcached connection.
     */
    protected function getMemcachedConnection()
    {
        if (method_exists($this->store, 'getMemcached')) {
            return $this->store->getMemcached();
        }
        
        throw new \RuntimeException('Unable to get Memcached connection');
    }

    /**
     * Format bytes to human-readable format.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        
        return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }

    /**
     * Get driver name.
     */
    public function getDriverName(): string
    {
        return 'memcached';
    }
}
