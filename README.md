# Laravel Smart Cache Analyzer

Intelligent cache analysis and optimization for Laravel applications. Automatically detect caching opportunities, analyze cache performance, and optimize your application's caching strategy.

## Features

- 🔍 **Auto-detect Cacheable Queries** - Identify slow and repeated queries that should be cached
- 📊 **Cache Analytics Dashboard** - Visual metrics showing cache effectiveness
- 🎯 **Smart Expiration Recommendations** - Data-driven suggestions for optimal TTLs
- 🔥 **Cache Warming** - Pre-populate cache before expiration
- 🧹 **Dead Cache Cleanup** - Identify and remove unused cache keys
- 💰 **Performance Metrics** - Track database load reduction and cost savings

## Installation

Install via Composer:

```bash
composer require harun1302123/laravel-smart-cache-analyzer
```

Publish the configuration:

```bash
php artisan vendor:publish --provider="SmartCache\Analyzer\SmartCacheServiceProvider"
```

Run migrations:

```bash
php artisan migrate
```

## Configuration

The configuration file will be published to `config/smart-cache.php`:

```php
return [
    'enabled' => env('SMART_CACHE_ENABLED', true),
    'dashboard_path' => 'smart-cache',
    'slow_query_threshold' => 100, // milliseconds
    'analyze_interval' => 3600, // seconds
    
    // Performance Optimizations
    'sampling_rate' => env('SMART_CACHE_SAMPLING_RATE', 10), // Analyze 10% of queries
    'async_processing' => env('SMART_CACHE_ASYNC_PROCESSING', false), // Queue analysis
    'batch_size' => env('SMART_CACHE_BATCH_SIZE', 50), // Buffer queries before DB write
];
```

### Performance Tuning

**Sampling Rate**: Reduce overhead by analyzing only a percentage of queries:
```env
SMART_CACHE_SAMPLING_RATE=10  # Analyze 10% of queries (recommended for high-traffic apps)
```

**Async Processing**: Queue query analysis to avoid blocking requests:
```env
SMART_CACHE_ASYNC_PROCESSING=true
```

**Batch Processing**: Buffer queries and bulk insert to reduce database writes:
```env
SMART_CACHE_BATCH_SIZE=100  # Buffer 100 queries before inserting
```

### Driver-Specific Optimizations

The package automatically detects your cache driver and provides specific optimizations:

**Redis**:
- Memory usage analysis with fragmentation ratio
- Eviction tracking (evicted/expired keys)
- Key pattern analysis
- TTL distribution monitoring
- Hit/miss ratio from Redis stats

**Memcached**:
- Memory usage and limits
- Eviction statistics
- Item counts and reclaimed items
- Server-level hit rates

**File Cache**:
- Disk usage tracking
- File size distribution analysis
- Largest cache files identification
- Disk space monitoring

Configure driver features in `config/smart-cache.php`:
```php
'drivers' => [
    'redis' => [
        'analyze_memory' => true,
        'track_evictions' => true,
        'monitor_key_patterns' => true,
    ],
    'file' => [
        'track_disk_usage' => true,
        'analyze_file_sizes' => true,
    ],
],
```

## Usage

### Dashboard

Access the analytics dashboard at:

```
http://your-app.test/smart-cache
```

### CLI Commands

Analyze cache performance:
```bash
php artisan cache:analyze
```

Clean dead cache keys:
```bash
php artisan cache:cleanup
```

Warm cache:
```bash
php artisan cache:warm
```

### Programmatic Usage

```php
use SmartCache\Analyzer\Facades\SmartCache;

// Get cache statistics
$stats = SmartCache::getStats();

// Analyze a specific query
SmartCache::analyzeQuery($query);

// Get caching recommendations
$recommendations = SmartCache::getRecommendations();
```

## How It Works

1. **Query Monitoring** - Listens to database queries and tracks execution times
2. **Pattern Detection** - Identifies queries that are executed frequently
3. **Analytics** - Calculates hit/miss ratios and performance metrics
4. **Recommendations** - Suggests optimal caching strategies based on usage patterns
5. **Automation** - Optionally auto-apply caching strategies

## Dashboard Preview

The dashboard provides:
- Real-time cache hit/miss ratio
- Top cacheable queries
- Cache memory usage
- TTL recommendations
- Cost savings calculator

## Requirements

- PHP 8.1 or higher
- Laravel 10.0 or higher

## Testing

```bash
composer test
```

## License

MIT License. See LICENSE file for details.

## Contributing

Contributions are welcome! Please see CONTRIBUTING.md for details.

## Support

For issues and questions, please use the GitHub issue tracker.
