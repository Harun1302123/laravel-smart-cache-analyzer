<template>
  <div class="smart-cache-dashboard">
    <div class="stats-grid">
      <!-- Hit Ratio -->
      <div class="stat-card">
        <div class="stat-label">Hit Ratio</div>
        <div class="stat-value">{{ stats.hit_ratio }}%</div>
        <div class="stat-trend" :class="trendClass(stats.hit_ratio)">
          {{ trend(stats.hit_ratio) }}
        </div>
      </div>

      <!-- Total Hits -->
      <div class="stat-card">
        <div class="stat-label">Total Hits</div>
        <div class="stat-value">{{ formatNumber(stats.total_hits) }}</div>
      </div>

      <!-- Total Misses -->
      <div class="stat-card">
        <div class="stat-label">Total Misses</div>
        <div class="stat-value">{{ formatNumber(stats.total_misses) }}</div>
      </div>

      <!-- Keys Count -->
      <div class="stat-card">
        <div class="stat-label">Keys Count</div>
        <div class="stat-value">{{ formatNumber(stats.keys_count) }}</div>
      </div>
    </div>

    <!-- Top Queries -->
    <div class="section">
      <h3>Top Queries ({{ topQueries.length }})</h3>
      <div class="queries-list">
        <div 
          v-for="query in topQueries" 
          :key="query.id"
          class="query-item"
        >
          <div class="query-sql">{{ query.query }}</div>
          <div class="query-stats">
            <span>Executions: {{ query.execution_count }}</span>
            <span>Avg: {{ query.avg_duration }}ms</span>
            <span>Max: {{ query.max_duration }}ms</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Recommendations -->
    <div class="section">
      <h3>Cache Recommendations ({{ recommendations.length }})</h3>
      <div class="recommendations-list">
        <div 
          v-for="rec in recommendations" 
          :key="rec.id"
          class="recommendation-item"
          :class="`priority-${rec.priority}`"
        >
          <div class="rec-header">
            <span class="priority-badge">{{ rec.priority }} Priority</span>
            <span class="ttl-badge">TTL: {{ rec.suggested_ttl }}s</span>
          </div>
          <div class="rec-reason">{{ rec.reason }}</div>
          <div class="rec-query">{{ truncate(rec.query_hash, 50) }}</div>
        </div>
      </div>
    </div>

    <!-- Live Status -->
    <div class="status-bar">
      <span class="status-indicator" :class="{ connected: isConnected }"></span>
      <span>{{ isConnected ? 'Live' : 'Disconnected' }}</span>
      <span class="last-update">Last update: {{ lastUpdate }}</span>
    </div>
  </div>
</template>

<script>
export default {
  name: 'SmartCacheDashboard',
  
  props: {
    apiUrl: {
      type: String,
      required: true
    },
    websocketUrl: {
      type: String,
      default: null
    },
    refreshInterval: {
      type: Number,
      default: 5000
    }
  },

  data() {
    return {
      stats: {
        hit_ratio: 0,
        total_hits: 0,
        total_misses: 0,
        keys_count: 0
      },
      topQueries: [],
      recommendations: [],
      isConnected: false,
      lastUpdate: 'Never',
      ws: null,
      pollTimer: null
    }
  },

  mounted() {
    if (this.websocketUrl) {
      this.connectWebSocket()
    } else {
      this.startPolling()
    }
  },

  beforeUnmount() {
    this.cleanup()
  },

  methods: {
    async fetchData() {
      try {
        const response = await fetch(`${this.apiUrl}/api/stream`)
        const data = await response.json()
        
        if (data.success) {
          this.stats = data.data.stats
          this.topQueries = data.data.top_queries || []
          this.recommendations = data.data.recommendations || []
          this.lastUpdate = new Date().toLocaleTimeString()
          this.isConnected = true
        }
      } catch (error) {
        console.error('Failed to fetch cache data:', error)
        this.isConnected = false
      }
    },

    connectWebSocket() {
      if (!window.Echo) {
        console.warn('Laravel Echo not available, falling back to polling')
        this.startPolling()
        return
      }

      // Listen for stats updates
      window.Echo.channel('smart-cache-stats')
        .listen('.stats.updated', (e) => {
          this.stats = e.stats
          this.lastUpdate = new Date().toLocaleTimeString()
          this.isConnected = true
        })

      // Listen for new recommendations
      window.Echo.channel('smart-cache-recommendations')
        .listen('.recommendation.created', (e) => {
          this.recommendations.unshift(e.recommendation)
          if (this.recommendations.length > 10) {
            this.recommendations.pop()
          }
        })

      this.isConnected = true
      this.fetchData() // Initial load
    },

    startPolling() {
      this.fetchData()
      this.pollTimer = setInterval(() => {
        this.fetchData()
      }, this.refreshInterval)
    },

    cleanup() {
      if (this.ws) {
        this.ws.close()
      }
      if (this.pollTimer) {
        clearInterval(this.pollTimer)
      }
      if (window.Echo) {
        window.Echo.leave('smart-cache-stats')
        window.Echo.leave('smart-cache-recommendations')
      }
    },

    formatNumber(num) {
      if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M'
      }
      if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K'
      }
      return num.toString()
    },

    trend(ratio) {
      if (ratio >= 80) return '↑ Excellent'
      if (ratio >= 60) return '→ Good'
      return '↓ Needs Attention'
    },

    trendClass(ratio) {
      if (ratio >= 80) return 'positive'
      if (ratio >= 60) return 'neutral'
      return 'negative'
    },

    truncate(str, length) {
      return str.length > length ? str.substring(0, length) + '...' : str
    }
  }
}
</script>

<style scoped>
.smart-cache-dashboard {
  padding: 20px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.stat-label {
  color: #666;
  font-size: 14px;
  margin-bottom: 8px;
}

.stat-value {
  font-size: 32px;
  font-weight: bold;
  color: #333;
}

.stat-trend {
  font-size: 14px;
  margin-top: 8px;
}

.stat-trend.positive { color: #22c55e; }
.stat-trend.neutral { color: #f59e0b; }
.stat-trend.negative { color: #ef4444; }

.section {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  margin-bottom: 20px;
}

.section h3 {
  margin: 0 0 15px 0;
  color: #333;
}

.queries-list, .recommendations-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.query-item, .recommendation-item {
  padding: 15px;
  background: #f9fafb;
  border-radius: 6px;
  border-left: 4px solid #3b82f6;
}

.query-sql {
  font-family: 'Courier New', monospace;
  font-size: 13px;
  margin-bottom: 8px;
  color: #333;
}

.query-stats {
  display: flex;
  gap: 15px;
  font-size: 12px;
  color: #666;
}

.recommendation-item.priority-high {
  border-left-color: #ef4444;
}

.recommendation-item.priority-medium {
  border-left-color: #f59e0b;
}

.recommendation-item.priority-low {
  border-left-color: #3b82f6;
}

.rec-header {
  display: flex;
  gap: 10px;
  margin-bottom: 8px;
}

.priority-badge, .ttl-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
}

.priority-badge {
  background: #fee2e2;
  color: #991b1b;
}

.ttl-badge {
  background: #dbeafe;
  color: #1e40af;
}

.rec-reason {
  font-size: 14px;
  color: #333;
  margin-bottom: 6px;
}

.rec-query {
  font-family: 'Courier New', monospace;
  font-size: 12px;
  color: #666;
}

.status-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 20px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  font-size: 14px;
}

.status-indicator {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #ef4444;
}

.status-indicator.connected {
  background: #22c55e;
}

.last-update {
  margin-left: auto;
  color: #666;
}
</style>
