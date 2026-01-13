<?php

namespace SmartCache\Analyzer\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class QueryMonitor
{
    protected CacheAnalyzer $analyzer;
    protected array $config;
    protected bool $monitoring = false;

    public function __construct(CacheAnalyzer $analyzer, array $config)
    {
        $this->analyzer = $analyzer;
        $this->config = $config;
    }

    /**
     * Start monitoring database queries.
     */
    public function start(): void
    {
        if ($this->monitoring) {
            return;
        }

        DB::listen(function ($query) {
            $this->handleQuery($query->sql, $query->time, $query->bindings);
        });

        $this->monitoring = true;
    }

    /**
     * Stop monitoring database queries.
     */
    public function stop(): void
    {
        $this->monitoring = false;
    }

    /**
     * Handle a database query.
     */
    protected function handleQuery(string $sql, float $time, array $bindings): void
    {
        // Skip if query is from excluded tables
        if ($this->shouldExcludeQuery($sql)) {
            return;
        }

        // Normalize query (remove specific values)
        $normalizedSql = $this->normalizeQuery($sql, $bindings);

        // Analyze the query
        $this->analyzer->analyzeQuery($normalizedSql, $time);
    }

    /**
     * Check if query should be excluded from monitoring.
     */
    protected function shouldExcludeQuery(string $sql): bool
    {
        $excludedTables = $this->config['excluded_tables'] ?? [];
        
        foreach ($excludedTables as $table) {
            if (stripos($sql, $table) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize query by removing specific values.
     */
    protected function normalizeQuery(string $sql, array $bindings): string
    {
        // Replace binding placeholders with generic markers
        $normalized = preg_replace('/\?/', '?', $sql);
        
        // Remove extra whitespace
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        
        return trim($normalized);
    }

    /**
     * Check if monitoring is active.
     */
    public function isMonitoring(): bool
    {
        return $this->monitoring;
    }
}
