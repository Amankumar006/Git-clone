import React, { useState, useEffect } from 'react';

interface AnalyticsData {
  user_growth: Array<{
    date: string;
    new_users: number;
    cumulative_users: number;
  }>;
  content: Array<{
    date: string;
    articles_created: number;
    articles_published: number;
    comments_created: number;
  }>;
  engagement: Array<{
    date: string;
    type: string;
    count: number;
  }>;
  health_metrics: {
    active_users_24h: number;
    articles_published_24h: number;
    avg_reading_time: number;
    engagement_rate: number;
    health_score: number;
  };
  comparative: Array<{
    metric: string;
    current_period: number;
    previous_period: number;
    percentage_change: number;
  }>;
}

interface TopContent {
  top_articles: Array<{
    id: number;
    title: string;
    view_count: number;
    clap_count: number;
    comment_count: number;
    author_username: string;
    engagement_score: number;
  }>;
  trending_topics: Array<{
    name: string;
    article_count: number;
    total_views: number;
    total_claps: number;
    avg_views_per_article: number;
  }>;
}

const PlatformAnalytics: React.FC = () => {
  const [analyticsData, setAnalyticsData] = useState<AnalyticsData | null>(null);
  const [topContent, setTopContent] = useState<TopContent | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [selectedPeriod, setSelectedPeriod] = useState(30);
  const [activeTab, setActiveTab] = useState('overview');

  useEffect(() => {
    fetchAnalytics();
  }, [selectedPeriod]);

  const fetchAnalytics = async () => {
    try {
      setLoading(true);
      const token = localStorage.getItem('token');
      
      // Fetch overview analytics
      const overviewResponse = await fetch(`/api/analytics/platform?type=overview&days=${selectedPeriod}`, {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      
      if (!overviewResponse.ok) {
        throw new Error('Failed to fetch analytics');
      }
      
      const overviewData = await overviewResponse.json();
      setAnalyticsData(overviewData);
      
      // Fetch top content
      const topContentResponse = await fetch(`/api/analytics/platform?type=top_content&days=${selectedPeriod}&limit=10`, {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      
      if (topContentResponse.ok) {
        const topContentData = await topContentResponse.json();
        setTopContent(topContentData);
      }
      
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to fetch analytics');
    } finally {
      setLoading(false);
    }
  };

  const exportData = async (type: string, format: string = 'json') => {
    try {
      const token = localStorage.getItem('token');
      const response = await fetch(`/api/analytics/export?type=${type}&days=${selectedPeriod}&format=${format}`, {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      
      if (!response.ok) {
        throw new Error('Failed to export data');
      }
      
      if (format === 'csv') {
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `analytics_${type}_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
      } else {
        const data = await response.json();
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `analytics_${type}_${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
      }
      
    } catch (err) {
      alert('Failed to export data: ' + (err instanceof Error ? err.message : 'Unknown error'));
    }
  };

  const getHealthStatusColor = (score: number) => {
    if (score >= 80) return 'text-green-600 bg-green-100';
    if (score >= 60) return 'text-blue-600 bg-blue-100';
    if (score >= 40) return 'text-yellow-600 bg-yellow-100';
    if (score >= 20) return 'text-orange-600 bg-orange-100';
    return 'text-red-600 bg-red-100';
  };

  const getHealthStatusText = (score: number) => {
    if (score >= 80) return 'Excellent';
    if (score >= 60) return 'Good';
    if (score >= 40) return 'Fair';
    if (score >= 20) return 'Poor';
    return 'Critical';
  };

  const getChangeColor = (change: number) => {
    if (change > 0) return 'text-green-600';
    if (change < 0) return 'text-red-600';
    return 'text-gray-600';
  };

  const getChangeIcon = (change: number) => {
    if (change > 0) return '↗️';
    if (change < 0) return '↘️';
    return '➡️';
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        {error}
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold text-gray-900">Platform Analytics</h1>
        <div className="flex items-center space-x-4">
          <select
            value={selectedPeriod}
            onChange={(e) => setSelectedPeriod(Number(e.target.value))}
            className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value={7}>Last 7 days</option>
            <option value={30}>Last 30 days</option>
            <option value={90}>Last 90 days</option>
            <option value={365}>Last year</option>
          </select>
          <button
            onClick={fetchAnalytics}
            className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
          >
            Refresh
          </button>
        </div>
      </div>

      {/* Tab Navigation */}
      <div className="border-b border-gray-200">
        <nav className="-mb-px flex space-x-8">
          {[
            { id: 'overview', label: 'Overview' },
            { id: 'growth', label: 'Growth' },
            { id: 'engagement', label: 'Engagement' },
            { id: 'content', label: 'Top Content' }
          ].map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={`py-2 px-1 border-b-2 font-medium text-sm ${
                activeTab === tab.id
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              {tab.label}
            </button>
          ))}
        </nav>
      </div>

      {/* Overview Tab */}
      {activeTab === 'overview' && analyticsData && (
        <div className="space-y-6">
          {/* Health Metrics */}
          <div className="bg-white p-6 rounded-lg shadow">
            <h2 className="text-lg font-semibold mb-4">Platform Health</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
              <div className="text-center">
                <div className={`text-3xl font-bold px-4 py-2 rounded-lg ${getHealthStatusColor(analyticsData.health_metrics.health_score)}`}>
                  {analyticsData.health_metrics.health_score}
                </div>
                <div className="text-sm text-gray-600 mt-1">
                  Health Score ({getHealthStatusText(analyticsData.health_metrics.health_score)})
                </div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold text-blue-600">{analyticsData.health_metrics.active_users_24h}</div>
                <div className="text-sm text-gray-600">Active Users (24h)</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold text-green-600">{analyticsData.health_metrics.articles_published_24h}</div>
                <div className="text-sm text-gray-600">Articles Published (24h)</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold text-purple-600">{Math.round(analyticsData.health_metrics.avg_reading_time)}m</div>
                <div className="text-sm text-gray-600">Avg Reading Time</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold text-orange-600">{analyticsData.health_metrics.engagement_rate.toFixed(1)}%</div>
                <div className="text-sm text-gray-600">Engagement Rate</div>
              </div>
            </div>
          </div>

          {/* Comparative Analytics */}
          <div className="bg-white p-6 rounded-lg shadow">
            <h2 className="text-lg font-semibold mb-4">Period Comparison</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              {analyticsData.comparative.map((metric) => (
                <div key={metric.metric} className="border rounded-lg p-4">
                  <div className="flex items-center justify-between">
                    <div>
                      <div className="text-2xl font-bold">{metric.current_period}</div>
                      <div className="text-sm text-gray-600 capitalize">{metric.metric.replace('_', ' ')}</div>
                    </div>
                    <div className={`text-right ${getChangeColor(metric.percentage_change)}`}>
                      <div className="text-lg font-semibold">
                        {getChangeIcon(metric.percentage_change)} {Math.abs(metric.percentage_change)}%
                      </div>
                      <div className="text-xs">vs previous period</div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* Growth Tab */}
      {activeTab === 'growth' && analyticsData && (
        <div className="bg-white p-6 rounded-lg shadow">
          <div className="flex justify-between items-center mb-4">
            <h2 className="text-lg font-semibold">User Growth</h2>
            <button
              onClick={() => exportData('user_growth', 'csv')}
              className="px-3 py-1 text-sm bg-gray-600 text-white rounded hover:bg-gray-700"
            >
              Export CSV
            </button>
          </div>
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">New Users</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cumulative</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {analyticsData.user_growth.slice(-10).map((day) => (
                  <tr key={day.date}>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {new Date(day.date).toLocaleDateString()}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{day.new_users}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{day.cumulative_users}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Engagement Tab */}
      {activeTab === 'engagement' && analyticsData && (
        <div className="bg-white p-6 rounded-lg shadow">
          <div className="flex justify-between items-center mb-4">
            <h2 className="text-lg font-semibold">Engagement Analytics</h2>
            <button
              onClick={() => exportData('engagement', 'csv')}
              className="px-3 py-1 text-sm bg-gray-600 text-white rounded hover:bg-gray-700"
            >
              Export CSV
            </button>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {['claps', 'comments', 'bookmarks', 'follows'].map((type) => {
              const typeData = analyticsData.engagement.filter(item => item.type === type);
              const total = typeData.reduce((sum, item) => sum + item.count, 0);
              return (
                <div key={type} className="border rounded-lg p-4">
                  <div className="text-2xl font-bold text-blue-600">{total}</div>
                  <div className="text-sm text-gray-600 capitalize">{type}</div>
                  <div className="text-xs text-gray-500">Last {selectedPeriod} days</div>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* Top Content Tab */}
      {activeTab === 'content' && topContent && (
        <div className="space-y-6">
          {/* Top Articles */}
          <div className="bg-white p-6 rounded-lg shadow">
            <div className="flex justify-between items-center mb-4">
              <h2 className="text-lg font-semibold">Top Performing Articles</h2>
              <button
                onClick={() => exportData('top_articles', 'csv')}
                className="px-3 py-1 text-sm bg-gray-600 text-white rounded hover:bg-gray-700"
              >
                Export CSV
              </button>
            </div>
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Article</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Author</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Views</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Claps</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Comments</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {topContent.top_articles.map((article) => (
                    <tr key={article.id}>
                      <td className="px-6 py-4 text-sm text-gray-900">
                        <div className="max-w-xs truncate">{article.title}</div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{article.author_username}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{article.view_count}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{article.clap_count}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{article.comment_count}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600">{article.engagement_score}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>

          {/* Trending Topics */}
          <div className="bg-white p-6 rounded-lg shadow">
            <div className="flex justify-between items-center mb-4">
              <h2 className="text-lg font-semibold">Trending Topics</h2>
              <button
                onClick={() => exportData('trending_topics', 'csv')}
                className="px-3 py-1 text-sm bg-gray-600 text-white rounded hover:bg-gray-700"
              >
                Export CSV
              </button>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {topContent.trending_topics.map((topic) => (
                <div key={topic.name} className="border rounded-lg p-4">
                  <div className="font-medium text-gray-900 mb-2">#{topic.name}</div>
                  <div className="space-y-1 text-sm text-gray-600">
                    <div>{topic.article_count} articles</div>
                    <div>{topic.total_views} total views</div>
                    <div>{topic.total_claps} total claps</div>
                    <div>{Math.round(topic.avg_views_per_article)} avg views/article</div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default PlatformAnalytics;