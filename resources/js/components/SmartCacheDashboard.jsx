import React, { useState, useEffect } from 'react';

const SmartCacheDashboard = ({ 
  apiUrl, 
  websocketUrl = null, 
  refreshInterval = 5000 
}) => {
  const [stats, setStats] = useState({
    hit_ratio: 0,
    total_hits: 0,
    total_misses: 0,
    keys_count: 0
  });
  const [topQueries, setTopQueries] = useState([]);
  const [recommendations, setRecommendations] = useState([]);
  const [isConnected, setIsConnected] = useState(false);
  const [lastUpdate, setLastUpdate] = useState('Never');

  useEffect(() => {
    if (websocketUrl && window.Echo) {
      connectWebSocket();
    } else {
      startPolling();
    }

    return () => cleanup();
  }, []);

  const fetchData = async () => {
    try {
      const response = await fetch(`${apiUrl}/api/stream`);
      const data = await response.json();
      
      if (data.success) {
        setStats(data.data.stats);
        setTopQueries(data.data.top_queries || []);
        setRecommendations(data.data.recommendations || []);
        setLastUpdate(new Date().toLocaleTimeString());
        setIsConnected(true);
      }
    } catch (error) {
      console.error('Failed to fetch cache data:', error);
      setIsConnected(false);
    }
  };

  const connectWebSocket = () => {
    if (!window.Echo) {
      console.warn('Laravel Echo not available, falling back to polling');
      startPolling();
      return;
    }

    window.Echo.channel('smart-cache-stats')
      .listen('.stats.updated', (e) => {
        setStats(e.stats);
        setLastUpdate(new Date().toLocaleTimeString());
        setIsConnected(true);
      });

    window.Echo.channel('smart-cache-recommendations')
      .listen('.recommendation.created', (e) => {
        setRecommendations(prev => [e.recommendation, ...prev.slice(0, 9)]);
      });

    setIsConnected(true);
    fetchData();
  };

  const startPolling = () => {
    fetchData();
    const timer = setInterval(fetchData, refreshInterval);
    return () => clearInterval(timer);
  };

  const cleanup = () => {
    if (window.Echo) {
      window.Echo.leave('smart-cache-stats');
      window.Echo.leave('smart-cache-recommendations');
    }
  };

  const formatNumber = (num) => {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return num.toString();
  };

  const getTrend = (ratio) => {
    if (ratio >= 80) return { text: '↑ Excellent', class: 'positive' };
    if (ratio >= 60) return { text: '→ Good', class: 'neutral' };
    return { text: '↓ Needs Attention', class: 'negative' };
  };

  const truncate = (str, length) => {
    return str.length > length ? str.substring(0, length) + '...' : str;
  };

  const trend = getTrend(stats.hit_ratio);

  return (
    <div className="smart-cache-dashboard">
      <div className="stats-grid">
        <div className="stat-card">
          <div className="stat-label">Hit Ratio</div>
          <div className="stat-value">{stats.hit_ratio}%</div>
          <div className={`stat-trend ${trend.class}`}>{trend.text}</div>
        </div>

        <div className="stat-card">
          <div className="stat-label">Total Hits</div>
          <div className="stat-value">{formatNumber(stats.total_hits)}</div>
        </div>

        <div className="stat-card">
          <div className="stat-label">Total Misses</div>
          <div className="stat-value">{formatNumber(stats.total_misses)}</div>
        </div>

        <div className="stat-card">
          <div className="stat-label">Keys Count</div>
          <div className="stat-value">{formatNumber(stats.keys_count)}</div>
        </div>
      </div>

      <div className="section">
        <h3>Top Queries ({topQueries.length})</h3>
        <div className="queries-list">
          {topQueries.map(query => (
            <div key={query.id} className="query-item">
              <div className="query-sql">{query.query}</div>
              <div className="query-stats">
                <span>Executions: {query.execution_count}</span>
                <span>Avg: {query.avg_duration}ms</span>
                <span>Max: {query.max_duration}ms</span>
              </div>
            </div>
          ))}
        </div>
      </div>

      <div className="section">
        <h3>Cache Recommendations ({recommendations.length})</h3>
        <div className="recommendations-list">
          {recommendations.map(rec => (
            <div 
              key={rec.id} 
              className={`recommendation-item priority-${rec.priority}`}
            >
              <div className="rec-header">
                <span className="priority-badge">{rec.priority} Priority</span>
                <span className="ttl-badge">TTL: {rec.suggested_ttl}s</span>
              </div>
              <div className="rec-reason">{rec.reason}</div>
              <div className="rec-query">{truncate(rec.query_hash, 50)}</div>
            </div>
          ))}
        </div>
      </div>

      <div className="status-bar">
        <span className={`status-indicator ${isConnected ? 'connected' : ''}`}></span>
        <span>{isConnected ? 'Live' : 'Disconnected'}</span>
        <span className="last-update">Last update: {lastUpdate}</span>
      </div>

      <style jsx>{`
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
      `}</style>
    </div>
  );
};

export default SmartCacheDashboard;
