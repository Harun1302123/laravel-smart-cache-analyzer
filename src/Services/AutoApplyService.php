<?php

namespace SmartCache\Analyzer\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SmartCache\Analyzer\Models\CacheRecommendation;
use SmartCache\Analyzer\Models\QueryAnalysis;
use SmartCache\Analyzer\Broadcasting\NewRecommendation;

class AutoApplyService
{
    protected array $config;
    protected CacheAnalyzer $analyzer;

    public function __construct(CacheAnalyzer $analyzer)
    {
        $this->analyzer = $analyzer;
        $this->config = config('smart-cache.auto_apply', []);
    }

    /**
     * Process and apply recommendations automatically.
     */
    public function processRecommendations(): array
    {
        if (!($this->config['enabled'] ?? false)) {
            return [
                'status' => 'disabled',
                'message' => 'Auto-apply is disabled',
            ];
        }

        $isDryRun = $this->config['dry_run'] ?? true;
        $threshold = $this->config['priority_threshold'] ?? 'high';
        $requireApproval = $this->config['require_approval'] ?? true;
        $maxQueries = $this->config['max_queries_per_run'] ?? 10;

        // Get recommendations to process
        $recommendations = $this->getRecommendationsToProcess($threshold, $requireApproval, $maxQueries);

        if ($recommendations->isEmpty()) {
            return [
                'status' => 'success',
                'message' => 'No recommendations to process',
                'processed' => 0,
            ];
        }

        $processed = 0;
        $results = [];

        foreach ($recommendations as $recommendation) {
            $result = $this->applyRecommendation($recommendation, $isDryRun);
            $results[] = $result;
            
            if ($result['success']) {
                $processed++;
            }
        }

        return [
            'status' => 'success',
            'dry_run' => $isDryRun,
            'processed' => $processed,
            'total' => $recommendations->count(),
            'results' => $results,
        ];
    }

    /**
     * Get recommendations that are ready to be processed.
     */
    protected function getRecommendationsToProcess(string $threshold, bool $requireApproval, int $limit)
    {
        $query = CacheRecommendation::priorityThreshold($threshold)
            ->orderByDesc('potential_savings');

        if ($requireApproval) {
            $query->approved();
        } else {
            $query->pending();
        }

        return $query->limit($limit)->get();
    }

    /**
     * Apply a single recommendation.
     */
    protected function applyRecommendation(CacheRecommendation $recommendation, bool $isDryRun): array
    {
        try {
            $config = $this->generateCacheConfig($recommendation);

            if (!$isDryRun) {
                // Store the caching strategy
                $this->storeCacheStrategy($recommendation, $config);
                
                // Mark as applied
                $recommendation->markAsApplied($config);

                Log::info('Smart Cache: Applied recommendation', [
                    'query_hash' => $recommendation->query_hash,
                    'ttl' => $recommendation->suggested_ttl,
                    'priority' => $recommendation->priority,
                ]);
            }

            return [
                'success' => true,
                'recommendation_id' => $recommendation->id,
                'query' => substr($recommendation->query, 0, 100) . '...',
                'ttl' => $recommendation->suggested_ttl,
                'config' => $config,
            ];

        } catch (\Exception $e) {
            Log::error('Smart Cache: Failed to apply recommendation', [
                'recommendation_id' => $recommendation->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'recommendation_id' => $recommendation->id,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate cache configuration for a query.
     */
    protected function generateCacheConfig(CacheRecommendation $recommendation): array
    {
        return [
            'query_hash' => $recommendation->query_hash,
            'ttl' => $recommendation->suggested_ttl,
            'cache_key_prefix' => 'smart_cache_' . $recommendation->query_hash,
            'tags' => ['smart-cache', 'auto-applied'],
            'priority' => $recommendation->priority,
            'applied_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Store caching strategy in a persistent store.
     */
    protected function storeCacheStrategy(CacheRecommendation $recommendation, array $config): void
    {
        // Store in a special cache key that can be checked during query execution
        $strategyKey = 'smart_cache:strategy:' . $recommendation->query_hash;
        
        Cache::forever($strategyKey, $config);
    }

    /**
     * Sync recommendations from query analysis.
     */
    public function syncRecommendations(): int
    {
        $recommendations = $this->analyzer->getRecommendations();
        $synced = 0;

        foreach ($recommendations as $rec) {
            $hash = md5($rec['query']);
            
            // Check if recommendation already exists
            $existing = CacheRecommendation::where('query_hash', $hash)->first();
            
            if (!$existing) {
                $newRecommendation = CacheRecommendation::create([
                    'query_hash' => $hash,
                    'query' => $rec['query'],
                    'priority' => $rec['priority'],
                    'suggested_ttl' => $rec['suggested_ttl'],
                    'reason' => $rec['reason'],
                    'potential_savings' => $rec['potential_savings'],
                    'status' => 'pending',
                ]);
                
                // Broadcast new recommendation if enabled
                if (config('smart-cache.broadcasting.broadcast_recommendations', true)) {
                    event(new NewRecommendation([
                        'id' => $newRecommendation->id,
                        'query_hash' => $newRecommendation->query_hash,
                        'priority' => $newRecommendation->priority,
                        'suggested_ttl' => $newRecommendation->suggested_ttl,
                        'reason' => $newRecommendation->reason,
                    ]));
                }
                
                $synced++;
            }
        }

        return $synced;
    }

    /**
     * Get cached query strategy.
     */
    public static function getCacheStrategy(string $queryHash): ?array
    {
        $strategyKey = 'smart_cache:strategy:' . $queryHash;
        return Cache::get($strategyKey);
    }

    /**
     * Check if query should be cached.
     */
    public static function shouldCacheQuery(string $queryHash): bool
    {
        return self::getCacheStrategy($queryHash) !== null;
    }

    /**
     * Approve multiple recommendations.
     */
    public function approveRecommendations(array $ids): int
    {
        return CacheRecommendation::whereIn('id', $ids)
            ->where('status', 'pending')
            ->update(['status' => 'approved']);
    }

    /**
     * Reject multiple recommendations.
     */
    public function rejectRecommendations(array $ids): int
    {
        return CacheRecommendation::whereIn('id', $ids)
            ->where('status', 'pending')
            ->update(['status' => 'rejected']);
    }
}
