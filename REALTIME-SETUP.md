# Real-Time Dashboard Setup Guide

This guide explains how to set up the real-time dashboard features including WebSocket support, Prometheus metrics, and Vue/React components.

## Table of Contents

1. [WebSocket Broadcasting Setup](#websocket-broadcasting-setup)
2. [Prometheus Metrics Setup](#prometheus-metrics-setup)
3. [Vue Component Setup](#vue-component-setup)
4. [React Component Setup](#react-component-setup)
5. [Health Monitoring](#health-monitoring)

---

## WebSocket Broadcasting Setup

### Step 1: Install Dependencies

```bash
# Laravel Broadcasting
composer require pusher/pusher-php-server

# Frontend Dependencies
npm install --save laravel-echo pusher-js
```

### Step 2: Configure Broadcasting

Update your `.env` file:

```env
BROADCAST_DRIVER=pusher

# Enable Smart Cache broadcasting
SMART_CACHE_BROADCASTING_ENABLED=true
SMART_CACHE_STATS_UPDATE_INTERVAL=5
SMART_CACHE_BROADCAST_RECOMMENDATIONS=true

# Pusher Configuration
PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_APP_CLUSTER=mt1
```

### Step 3: Initialize Laravel Echo

Create or update `resources/js/bootstrap.js`:

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
    wsHost: import.meta.env.VITE_PUSHER_HOST ?? `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
    wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
    wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

### Step 4: Listen to Events

The package broadcasts two types of events:

#### Cache Stats Updates

```javascript
window.Echo.channel('smart-cache-stats')
    .listen('.stats.updated', (event) => {
        console.log('Stats updated:', event.stats);
        // Update your UI here
    });
```

#### New Recommendations

```javascript
window.Echo.channel('smart-cache-recommendations')
    .listen('.recommendation.created', (event) => {
        console.log('New recommendation:', event.recommendation);
        // Display notification or update list
    });
```

### Alternative: Using Laravel Reverb (Laravel 11+)

For Laravel 11 applications, you can use Laravel Reverb instead of Pusher:

```bash
php artisan install:broadcasting
```

Update `.env`:

```env
BROADCAST_DRIVER=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
```

Start Reverb server:

```bash
php artisan reverb:start
```

---

## Prometheus Metrics Setup

### Step 1: Access Metrics Endpoint

The package automatically exposes metrics at:

```
GET http://your-app.test/_metrics/smart-cache
```

### Step 2: Configure Prometheus

Add to your `prometheus.yml`:

```yaml
scrape_configs:
  - job_name: 'laravel-smart-cache'
    static_configs:
      - targets: ['your-app.test:80']
    metrics_path: '/_metrics/smart-cache'
    scrape_interval: 15s
```

### Step 3: Available Metrics

```prometheus
# Cache Performance
smart_cache_hit_ratio                    # Gauge: Hit ratio percentage
smart_cache_hits_total                   # Counter: Total hits
smart_cache_misses_total                 # Counter: Total misses
smart_cache_keys_count                   # Gauge: Number of keys

# Recommendations
smart_cache_recommendations_pending      # Gauge: Pending recommendations

# Driver-Specific (Redis/Memcached)
smart_cache_memory_bytes{driver="..."}  # Gauge: Memory usage
smart_cache_evictions_total{driver="..."} # Counter: Evicted keys

# File Cache
smart_cache_disk_bytes                   # Gauge: Disk usage
```

### Step 4: Grafana Dashboard

Create a new dashboard in Grafana with panels:

1. **Hit Ratio Over Time**
   - Query: `smart_cache_hit_ratio`
   - Visualization: Time series line chart

2. **Cache Operations**
   - Query: `rate(smart_cache_hits_total[5m])`
   - Visualization: Time series

3. **Memory Usage** (Redis/Memcached)
   - Query: `smart_cache_memory_bytes`
   - Visualization: Gauge

4. **Pending Recommendations**
   - Query: `smart_cache_recommendations_pending`
   - Visualization: Stat panel

---

## Vue Component Setup

### Step 1: Install Component Package

```bash
npm install @harun1302123/laravel-smart-cache-dashboard
```

### Step 2: Import and Use

**Vue 3 with Composition API:**

```vue
<template>
  <div id="app">
    <SmartCacheDashboard 
      api-url="/smart-cache"
      websocket-url="ws://localhost:6001"
      :refresh-interval="5000"
    />
  </div>
</template>

<script setup>
import SmartCacheDashboard from '@harun1302123/laravel-smart-cache-dashboard/vue';
</script>
```

**Vue 3 with Options API:**

```vue
<template>
  <SmartCacheDashboard 
    :api-url="apiUrl"
    :websocket-url="websocketUrl"
    :refresh-interval="refreshInterval"
  />
</template>

<script>
import SmartCacheDashboard from '@harun1302123/laravel-smart-cache-dashboard/vue';

export default {
  components: {
    SmartCacheDashboard
  },
  data() {
    return {
      apiUrl: '/smart-cache',
      websocketUrl: 'ws://localhost:6001',
      refreshInterval: 5000
    }
  }
}
</script>
```

### Component Props

| Prop | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `api-url` | String | Yes | - | Base URL for Smart Cache API |
| `websocket-url` | String | No | null | WebSocket URL (falls back to polling if not provided) |
| `refresh-interval` | Number | No | 5000 | Polling interval in milliseconds |

---

## React Component Setup

### Step 1: Install Component Package

```bash
npm install @harun1302123/laravel-smart-cache-dashboard
```

### Step 2: Import and Use

```jsx
import React from 'react';
import SmartCacheDashboard from '@harun1302123/laravel-smart-cache-dashboard/react';

function App() {
  return (
    <div className="App">
      <SmartCacheDashboard 
        apiUrl="/smart-cache"
        websocketUrl="ws://localhost:6001"
        refreshInterval={5000}
      />
    </div>
  );
}

export default App;
```

### Component Props

| Prop | Type | Required | Default | Description |
|------|------|----------|---------|-------------|
| `apiUrl` | String | Yes | - | Base URL for Smart Cache API |
| `websocketUrl` | String | No | null | WebSocket URL (falls back to polling if not provided) |
| `refreshInterval` | Number | No | 5000 | Polling interval in milliseconds |

---

## Health Monitoring

### Health Check Endpoint

The package provides a health check endpoint:

```
GET http://your-app.test/_health/smart-cache
```

**Response:**

```json
{
  "status": "healthy",
  "enabled": true,
  "timestamp": "2026-01-14T12:00:00Z"
}
```

### Integration with Load Balancers

**NGINX:**

```nginx
location /_health/smart-cache {
    access_log off;
    return 200 "healthy\n";
}
```

**AWS ALB Target Group:**

```hcl
resource "aws_lb_target_group" "app" {
  health_check {
    path                = "/_health/smart-cache"
    healthy_threshold   = 2
    unhealthy_threshold = 2
    timeout             = 5
    interval            = 30
    matcher             = "200"
  }
}
```

---

## Troubleshooting

### WebSocket Not Connecting

1. **Check Pusher credentials:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

2. **Verify broadcasting is enabled:**
   ```bash
   php artisan config:show broadcasting
   ```

3. **Check browser console:**
   ```javascript
   // Should see: Pusher : State changed : connecting -> connected
   ```

### Metrics Not Showing in Prometheus

1. **Test endpoint manually:**
   ```bash
   curl http://your-app.test/_metrics/smart-cache
   ```

2. **Check Prometheus targets:**
   - Visit http://prometheus:9090/targets
   - Verify your app is listed and UP

3. **Verify middleware:**
   - Ensure no authentication middleware on metrics endpoint

### Component Not Updating

1. **Check API endpoint:**
   ```bash
   curl http://your-app.test/smart-cache/api/stream
   ```

2. **Verify Laravel Echo:**
   ```javascript
   console.log(window.Echo); // Should not be undefined
   ```

3. **Check browser network tab:**
   - Look for XHR requests every 5 seconds (polling)
   - Or WebSocket connection if Echo is configured

---

## Performance Tips

### 1. Optimize Broadcasting

```env
# Update stats every 10 seconds instead of 5
SMART_CACHE_STATS_UPDATE_INTERVAL=10
```

### 2. Use Queue for Broadcasting

```env
QUEUE_CONNECTION=redis
```

Update config:

```php
'broadcasting' => [
    'connections' => [
        'pusher' => [
            'driver' => 'pusher',
            'queue' => true, // Enable queuing
        ],
    ],
],
```

### 3. Cache Prometheus Metrics

```php
// In config/smart-cache.php
'cache_metrics' => true,
'metrics_cache_ttl' => 60, // Cache for 60 seconds
```

### 4. Reduce Polling Frequency

```jsx
<SmartCacheDashboard 
  apiUrl="/smart-cache"
  refreshInterval={10000} // 10 seconds instead of 5
/>
```

---

## Security Considerations

### 1. Protect Metrics Endpoint

Add middleware to restrict access:

```php
// In routes/web.php or create routes/metrics.php
Route::get('/_metrics/smart-cache', [MetricsController::class, 'prometheus'])
    ->middleware(['auth', 'admin']);
```

### 2. CORS Configuration

If frontend is on different domain:

```php
// In config/cors.php
'paths' => [
    'api/*',
    'smart-cache/*',
    '_metrics/*',
    '_health/*',
],
```

### 3. Rate Limiting

```php
Route::middleware(['throttle:60,1'])->group(function () {
    Route::get('/_metrics/smart-cache', [MetricsController::class, 'prometheus']);
});
```

---

## Next Steps

1. Set up alerts in Prometheus/Grafana based on metrics
2. Create custom dashboards for your team
3. Integrate with your CI/CD for performance monitoring
4. Add custom events in your application code

For more information, see the main [README.md](../README.md).
