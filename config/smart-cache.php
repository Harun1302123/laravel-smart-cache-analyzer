<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Smart Cache Analyzer Enabled
    |--------------------------------------------------------------------------
    |
    | Enable or disable the Smart Cache Analyzer. When disabled, no queries
    | will be monitored and no analytics will be collected.
    |
    */
    'enabled' => env('SMART_CACHE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Dashboard Path
    |--------------------------------------------------------------------------
    |
    | The URI path where the Smart Cache dashboard will be accessible.
    | Default: smart-cache (accessible at /smart-cache)
    |
    */
    'dashboard_path' => env('SMART_CACHE_DASHBOARD_PATH', 'smart-cache'),

    /*
    |--------------------------------------------------------------------------
    | Dashboard Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware to apply to the dashboard routes. You should add
    | authentication middleware to protect the dashboard in production.
    |
    */
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Slow Query Threshold
    |--------------------------------------------------------------------------
    |
    | Queries taking longer than this threshold (in milliseconds) will be
    | flagged as candidates for caching.
    |
    */
    'slow_query_threshold' => env('SMART_CACHE_SLOW_QUERY_THRESHOLD', 100),

    /*
    |--------------------------------------------------------------------------
    | Repeated Query Threshold
    |--------------------------------------------------------------------------
    |
    | Queries executed more than this number of times within the analysis
    | window will be flagged as candidates for caching.
    |
    */
    'repeated_query_threshold' => env('SMART_CACHE_REPEATED_QUERY_THRESHOLD', 5),

    /*
    |--------------------------------------------------------------------------
    | Analysis Interval
    |--------------------------------------------------------------------------
    |
    | How often (in seconds) to run the automatic cache analysis.
    | Set to 0 to disable automatic analysis.
    |
    */
    'analyze_interval' => env('SMART_CACHE_ANALYZE_INTERVAL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Query Sampling Rate
    |--------------------------------------------------------------------------
    |
    | Percentage of queries to analyze (1-100). Lower values reduce overhead.
    | For example, 10 means only 10% of queries will be analyzed.
    |
    */
    'sampling_rate' => env('SMART_CACHE_SAMPLING_RATE', 10),

    /*
    |--------------------------------------------------------------------------
    | Async Processing
    |--------------------------------------------------------------------------
    |
    | Enable queued processing for query analysis to reduce real-time overhead.
    |
    */
    'async_processing' => env('SMART_CACHE_ASYNC_PROCESSING', false),

    /*
    |--------------------------------------------------------------------------
    | Batch Insert Size
    |--------------------------------------------------------------------------
    |
    | Number of queries to buffer before bulk inserting. Higher values reduce
    | database writes but increase memory usage.
    |
    */
    'batch_size' => env('SMART_CACHE_BATCH_SIZE', 50),

    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    |
    | How long (in days) to retain cache analytics data.
    |
    */
    'data_retention_days' => env('SMART_CACHE_DATA_RETENTION_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Auto-warming Enabled
    |--------------------------------------------------------------------------
    |
    | Enable automatic cache warming before keys expire.
    |
    */
    'auto_warming_enabled' => env('SMART_CACHE_AUTO_WARMING', false),

    /*
    |--------------------------------------------------------------------------
    | Auto-cleanup Enabled
    |--------------------------------------------------------------------------
    |
    | Enable automatic cleanup of unused cache keys.
    |
    */
    'auto_cleanup_enabled' => env('SMART_CACHE_AUTO_CLEANUP', false),

    /*
    |--------------------------------------------------------------------------
    | Auto-Apply Recommendations
    |--------------------------------------------------------------------------
    |
    | Automatically apply caching recommendations based on analysis.
    |
    */
    'auto_apply' => [
        'enabled' => env('SMART_CACHE_AUTO_APPLY', false),
        'priority_threshold' => env('SMART_CACHE_AUTO_APPLY_THRESHOLD', 'high'), // 'high', 'medium', 'low'
        'require_approval' => env('SMART_CACHE_AUTO_APPLY_APPROVAL', true),
        'dry_run' => env('SMART_CACHE_AUTO_APPLY_DRY_RUN', true),
        'max_queries_per_run' => env('SMART_CACHE_AUTO_APPLY_MAX', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Real-Time Dashboard
    |--------------------------------------------------------------------------
    |
    | Configure WebSocket broadcasting for real-time dashboard updates.
    | Requires Laravel Broadcasting and Pusher or other WebSocket driver.
    |
    */

    'broadcasting' => [
        'enabled' => env('SMART_CACHE_BROADCASTING_ENABLED', false),
        'stats_update_interval' => env('SMART_CACHE_STATS_UPDATE_INTERVAL', 5), // seconds
        'broadcast_recommendations' => env('SMART_CACHE_BROADCAST_RECOMMENDATIONS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Tables
    |--------------------------------------------------------------------------
    |
    | Tables to exclude from cache analysis (e.g., logs, migrations).
    |
    */
    'excluded_tables' => [
        'migrations',
        'jobs',
        'failed_jobs',
        'password_resets',
        'cache',
        'cache_locks',
        'sessions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Stores to Monitor
    |--------------------------------------------------------------------------
    |
    | Which cache stores to monitor. Leave empty to monitor all stores.
    |
    */
    'monitored_stores' => [
        // 'redis',
        // 'memcached',
        // 'file',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Driver Optimizations
    |--------------------------------------------------------------------------
    |
    | Driver-specific features and optimizations.
    |
    */
    'drivers' => [
        'redis' => [
            'analyze_memory' => true,
            'track_evictions' => true,
            'monitor_key_patterns' => true,
            'analyze_ttl_distribution' => true,
        ],
        'memcached' => [
            'analyze_memory' => true,
            'track_evictions' => true,
            'monitor_hit_rate' => true,
        ],
        'file' => [
            'track_disk_usage' => true,
            'analyze_file_sizes' => true,
            'monitor_cleanup_frequency' => true,
        ],
        'database' => [
            'track_query_count' => true,
            'monitor_lock_wait' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default TTL Suggestions
    |--------------------------------------------------------------------------
    |
    | Default TTL values (in seconds) for different query patterns.
    |
    */
    'default_ttls' => [
        'static_data' => 86400,      // 24 hours
        'user_data' => 3600,          // 1 hour
        'volatile_data' => 300,       // 5 minutes
        'configuration' => 604800,    // 7 days
    ],
];
