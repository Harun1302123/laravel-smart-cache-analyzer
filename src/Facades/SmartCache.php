<?php

namespace SmartCache\Analyzer\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array getStats()
 * @method static void analyzeQuery(string $query, float $time)
 * @method static array getRecommendations()
 * @method static array getTopQueries(int $limit = 10)
 * @method static float getHitRatio()
 * @method static array getCacheSuggestions()
 *
 * @see \SmartCache\Analyzer\Services\CacheAnalyzer
 */
class SmartCache extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'smart-cache';
    }
}
