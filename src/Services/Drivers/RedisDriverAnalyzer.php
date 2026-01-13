<?php

namespace SmartCache\Analyzer\Services\Drivers;

class RedisDriverAnalyzer extends CacheDriverAnalyzer
{
    /**
     * Get Redis-specific statistics.
     */
    public function getStats(): array
    {
        $stats = [
            'driver' => 'redis',
            'memory' => null,
            'evictions' => null,
            'keys_count' => null,
            'hit_rate' => null,
        ];

        try {
            $redis = $this->getRedisConnection();
            
            if ($this->config['analyze_memory'] ?? true) {
                $stats['memory'] = $this->analyzeMemory($redis);
            }
            
            if ($this->config['track_evictions'] ?? true) {
                $stats['evictions'] = $this->trackEvictions($redis);
            }
            
            if ($this->config['monitor_key_patterns'] ?? true) {
                $stats['key_patterns'] = $this->analyzeKeyPatterns($redis);
            }
            
            if ($this->config['analyze_ttl_distribution'] ?? true) {
                $stats['ttl_distribution'] = $this->analyzeTTLDistribution($redis);
            }
            
            $stats['keys_count'] = $redis->dbSize();
            $stats['hit_rate'] = $this->calculateHitRate($redis);
            
        } catch (\Exception $e) {
            $stats['error'] = $e->getMessage();
        }

        return $stats;
    }

    /**
     * Check if Redis supports specific feature.
     */
    public function supports(string $feature): bool
    {
        return in_array($feature, [
            'memory_analysis',
            'eviction_tracking',
            'key_patterns',
            'ttl_analysis',
            'persistence',
        ]);
    }

    /**
     * Get memory usage information.
     */
    public function getMemoryUsage(): ?array
    {
        try {
            $redis = $this->getRedisConnection();
            return $this->analyzeMemory($redis);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get eviction statistics.
     */
    public function getEvictionStats(): ?array
    {
        try {
            $redis = $this->getRedisConnection();
            return $this->trackEvictions($redis);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Analyze memory usage.
     */
    protected function analyzeMemory($redis): array
    {
        $info = $redis->info('memory');
        
        return [
            'used_memory' => $info['used_memory'] ?? 0,
            'used_memory_human' => $info['used_memory_human'] ?? 'N/A',
            'used_memory_peak' => $info['used_memory_peak'] ?? 0,
            'used_memory_peak_human' => $info['used_memory_peak_human'] ?? 'N/A',
            'memory_fragmentation_ratio' => $info['mem_fragmentation_ratio'] ?? 0,
            'maxmemory' => $info['maxmemory'] ?? 0,
            'maxmemory_human' => $info['maxmemory_human'] ?? 'N/A',
            'maxmemory_policy' => $info['maxmemory_policy'] ?? 'noeviction',
        ];
    }

    /**
     * Track eviction statistics.
     */
    protected function trackEvictions($redis): array
    {
        $info = $redis->info('stats');
        
        return [
            'evicted_keys' => $info['evicted_keys'] ?? 0,
            'expired_keys' => $info['expired_keys'] ?? 0,
            'keyspace_hits' => $info['keyspace_hits'] ?? 0,
            'keyspace_misses' => $info['keyspace_misses'] ?? 0,
        ];
    }

    /**
     * Analyze key patterns.
     */
    protected function analyzeKeyPatterns($redis): array
    {
        $patterns = [];
        
        try {
            // Sample keys to analyze patterns (limit to 1000 for performance)
            $keys = $redis->keys('*');
            $sampleSize = min(1000, count($keys));
            $sample = array_slice($keys, 0, $sampleSize);
            
            foreach ($sample as $key) {
                // Extract pattern (e.g., "user:*", "cache:*")
                $pattern = preg_replace('/[0-9]+/', '*', $key);
                $pattern = preg_replace('/\*+/', '*', $pattern);
                
                if (!isset($patterns[$pattern])) {
                    $patterns[$pattern] = [
                        'count' => 0,
                        'total_size' => 0,
                    ];
                }
                
                $patterns[$pattern]['count']++;
                $patterns[$pattern]['total_size'] += strlen($redis->get($key) ?? '');
            }
            
            // Sort by count
            uasort($patterns, function ($a, $b) {
                return $b['count'] <=> $a['count'];
            });
            
        } catch (\Exception $e) {
            // Silently fail if keys command is disabled
        }
        
        return array_slice($patterns, 0, 10); // Top 10 patterns
    }

    /**
     * Analyze TTL distribution.
     */
    protected function analyzeTTLDistribution($redis): array
    {
        $distribution = [
            'no_expiry' => 0,
            '0-1h' => 0,
            '1h-1d' => 0,
            '1d-1w' => 0,
            '1w+' => 0,
        ];
        
        try {
            $keys = $redis->keys('*');
            $sampleSize = min(1000, count($keys));
            $sample = array_slice($keys, 0, $sampleSize);
            
            foreach ($sample as $key) {
                $ttl = $redis->ttl($key);
                
                if ($ttl === -1) {
                    $distribution['no_expiry']++;
                } elseif ($ttl <= 3600) {
                    $distribution['0-1h']++;
                } elseif ($ttl <= 86400) {
                    $distribution['1h-1d']++;
                } elseif ($ttl <= 604800) {
                    $distribution['1d-1w']++;
                } else {
                    $distribution['1w+']++;
                }
            }
        } catch (\Exception $e) {
            // Silently fail
        }
        
        return $distribution;
    }

    /**
     * Calculate hit rate from Redis stats.
     */
    protected function calculateHitRate($redis): float
    {
        $info = $redis->info('stats');
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;
        
        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }

    /**
     * Get Redis connection.
     */
    protected function getRedisConnection()
    {
        if (method_exists($this->store, 'connection')) {
            return $this->store->connection();
        }
        
        if (method_exists($this->store, 'getRedis')) {
            return $this->store->getRedis();
        }
        
        throw new \RuntimeException('Unable to get Redis connection');
    }

    /**
     * Get driver name.
     */
    public function getDriverName(): string
    {
        return 'redis';
    }
}
