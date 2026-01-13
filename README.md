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
];
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
