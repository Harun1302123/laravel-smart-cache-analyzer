# Laravel Smart Cache Analyzer

Intelligent cache analysis and optimization for Laravel applications. Automatically detect caching opportunities, analyze cache performance, and optimize your application's caching strategy.

## Features

- 🔍 **Auto-detect Cacheable Queries** - Identify slow and repeated queries that should be cached
- 📊 **Real-Time Dashboard** - WebSocket support with Vue/React components for live updates
- 🎯 **Smart Expiration Recommendations** - Data-driven suggestions for optimal TTLs
- 🔥 **Cache Warming** - Pre-populate cache before expiration
- 🧹 **Dead Cache Cleanup** - Identify and remove unused cache keys
- 💰 **Performance Metrics** - Track database load reduction and cost savings
- 📈 **Prometheus Exporter** - Export metrics for Grafana and monitoring tools
- 🎛️ **Driver-Specific Analytics** - Redis, Memcached, and File cache specialized monitoring
- 🤖 **Auto-Apply Recommendations** - Automatically implement caching with approval workflow

## Installation

Install via Composer:

```bash
composer require harun1302123/laravel-smart-cache-analyzer

# Optional: Install frontend components for real-time dashboard
npm install @harun1302123/laravel-smart-cache-dashboard
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

### Real-Time Dashboard with Vue/React

**Vue 3 Component:**

```vue
<template>
  <SmartCacheDashboard 
    api-url="/smart-cache"
    websocket-url="ws://localhost:6001"
    :refresh-interval="5000"
  />
</template>

<script setup>
import SmartCacheDashboard from '@harun1302123/laravel-smart-cache-dashboard/vue';
</script>
```

**React Component:**

```jsx
import SmartCacheDashboard from '@harun1302123/laravel-smart-cache-dashboard/react';

function App() {
  return (
    <SmartCacheDashboard 
      apiUrl="/smart-cache"
      websocketUrl="ws://localhost:6001"
      refreshInterval={5000}
    />
  );
}
```

**Features:**
- ✅ Real-time stats updates via WebSocket
- ✅ Automatic fallback to polling if WebSocket unavailable
- ✅ Live recommendations display
- ✅ Connection status indicator
- ✅ Responsive design

### Prometheus Metrics

Export metrics for Grafana or other monitoring tools:

```
GET http://your-app.test/_metrics/smart-cache
```

**Sample metrics:**

```prometheus
# HELP smart_cache_hit_ratio Cache hit ratio percentage
# TYPE smart_cache_hit_ratio gauge
smart_cache_hit_ratio 87.5

# HELP smart_cache_hits_total Total cache hits
# TYPE smart_cache_hits_total counter
smart_cache_hits_total 12450

# HELP smart_cache_memory_bytes Memory usage in bytes
# TYPE smart_cache_memory_bytes gauge
smart_cache_memory_bytes{driver="redis"} 15728640
```

**Grafana Setup:**

1. Add Prometheus data source in Grafana
2. Configure scraping of `/_metrics/smart-cache` endpoint
3. Import pre-built dashboard (coming soon) or create custom panels

### WebSocket Broadcasting Setup

Enable real-time updates by configuring Laravel Broadcasting:

**1. Install Laravel Echo and Pusher:**

```bash
composer require pusher/pusher-php-server
npm install --save laravel-echo pusher-js
```

**2. Configure Broadcasting (.env):**

```env
BROADCAST_DRIVER=pusher
SMART_CACHE_BROADCASTING_ENABLED=true

PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=mt1
```

**3. Initialize Laravel Echo (resources/js/app.js):**

```js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: process.env.MIX_PUSHER_APP_KEY,
    cluster: process.env.MIX_PUSHER_APP_CLUSTER,
    forceTLS: true
});
```

**4. Listen for Events:**

The package automatically broadcasts:
- `CacheStatsUpdated` event on channel `smart-cache-stats`
- `NewRecommendation` event on channel `smart-cache-recommendations`

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

Auto-apply recommendations:
```bash
php artisan cache:auto-apply --sync         # Sync and process recommendations
php artisan cache:auto-apply --list         # List pending recommendations
php artisan cache:auto-apply --approve=1,2  # Approve specific recommendations
php artisan cache:auto-apply --dry-run      # Preview changes without applying
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

// Auto-apply service
$autoApply = app(\SmartCache\Analyzer\Services\AutoApplyService::class);
$autoApply->syncRecommendations();
$result = $autoApply->processRecommendations();
```

### Auto-Apply Recommendations

Enable automatic caching of queries based on analysis:

```env
SMART_CACHE_AUTO_APPLY=true
SMART_CACHE_AUTO_APPLY_THRESHOLD=high      # Only high-priority queries
SMART_CACHE_AUTO_APPLY_APPROVAL=true       # Require manual approval first
SMART_CACHE_AUTO_APPLY_DRY_RUN=false       # Set to false to apply changes
SMART_CACHE_AUTO_APPLY_MAX=10              # Max queries per run
```

**Workflow:**
1. Sync recommendations: `php artisan cache:auto-apply --sync`
2. Review pending: `php artisan cache:auto-apply --list`
3. Approve selected: `php artisan cache:auto-apply --approve=1,2,3`
4. Auto-apply approved: `php artisan cache:auto-apply`
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
