<?php

namespace SmartCache\Analyzer\Services\Drivers;

abstract class CacheDriverAnalyzer
{
    protected $store;
    protected array $config;

    public function __construct($store, array $config = [])
    {
        $this->store = $store;
        $this->config = $config;
    }

    /**
     * Get driver-specific statistics.
     */
    abstract public function getStats(): array;

    /**
     * Check if driver supports specific feature.
     */
    abstract public function supports(string $feature): bool;

    /**
     * Get memory usage information.
     */
    public function getMemoryUsage(): ?array
    {
        return null;
    }

    /**
     * Get eviction statistics.
     */
    public function getEvictionStats(): ?array
    {
        return null;
    }

    /**
     * Get driver name.
     */
    abstract public function getDriverName(): string;
}
