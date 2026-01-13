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

        // Generate query signature (normalized pattern without values)
        $signature = $this->generateSignature($sql);
        
        // Normalize query with bindings for display/debugging
        $normalizedSql = $this->normalizeQuery($sql, $bindings);

        // Analyze the query using signature for grouping
        $this->analyzer->analyzeQuery($signature, $time, $normalizedSql);
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
        $normalized = $sql;
        
        // Replace bindings with their types for better readability
        foreach ($bindings as $binding) {
            if (is_null($binding)) {
                $normalized = preg_replace('/\?/', 'NULL', $normalized, 1);
            } elseif (is_numeric($binding)) {
                $normalized = preg_replace('/\?/', ':number', $normalized, 1);
            } elseif (is_string($binding)) {
                $normalized = preg_replace('/\?/', ':string', $normalized, 1);
            } else {
                $normalized = preg_replace('/\?/', ':value', $normalized, 1);
            }
        }
        
        // Remove extra whitespace
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        
        return trim($normalized);
    }

    /**
     * Generate a unique signature for query pattern matching.
     */
    protected function generateSignature(string $sql): string
    {
        // Normalize the SQL structure
        $signature = $sql;
        
        // Replace all numeric literals with placeholder
        $signature = preg_replace('/\b\d+\b/', '?', $signature);
        
        // Replace string literals with placeholder
        $signature = preg_replace("/\'[^\']*\'/", '?', $signature);
        $signature = preg_replace('/\"[^\"]*\"/i', '?', $signature);
        
        // Replace IN clauses with normalized version
        $signature = preg_replace('/in\s*\([^\)]+\)/i', 'IN (?)', $signature);
        
        // Normalize whitespace
        $signature = preg_replace('/\s+/', ' ', $signature);
        $signature = strtolower(trim($signature));
        
        return $signature;
    }

    /**
     * Check if monitoring is active.
     */
    public function isMonitoring(): bool
    {
        return $this->monitoring;
    }
}
