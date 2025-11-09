import React, { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { apiService } from '../utils/api';

// Placeholder icons
const ChartBarIcon = ({ className }: { className?: string }) => <span className={className}>📊</span>;
const ArrowDownTrayIcon = ({ className }: { className?: string }) => <span className={className}>⬇️</span>;
const CalendarIcon = ({ className }: { className?: string }) => <span className={className}>📅</span>;
const GlobeAltIcon = ({ className }: { className?: string }) => <span className={className}>🌍</span>;
const DevicePhoneMobileIcon = ({ className }: { className?: string }) => <span className={className}>📱</span>;
const ComputerDesktopIcon = ({ className }: { className?: string }) => <span className={className}>🖥️</span>;
const ClockIcon = ({ className }: { className?: string }) => <span className={className}>⏰</span>;
const TrendingUpIcon = ({ className }: { className?: string }) => <span className={className}>📈</span>;
const TrendingDownIcon = ({ className }: { className?: string }) => <span className={className}>📉</span>;

interface AdvancedAnalyticsData {
  performance_metrics: Array<{
    id: number;
    title: string;
    view_count: number;
    clap_count: number;
    comment_count: number;
    engagement_score: number;
    read_completion_rate: number;
    avg_time_spent: number;
    avg_scroll_depth: number;
  }>;
  reader_demographics: {
    geographic_distribution: Array<{
      ip_prefix: string;
      view_count: number;
      unique_readers: number;
    }>;
    reading_behavior: {
      avg_reading_time: number;
      avg_scroll_depth: number;
      full_reads: number;
      partial_reads: number;
      quick_scans: number;
      total_reads: number;
    };
    device_analytics: Array<{
      device_type: string;
      view_count: number;
      unique_users: number;
    }>;
    retention_metrics: {
      total_readers: number;
      returning_readers: number;
      retention_rate: number;
    };
  };
  engagement_patterns: {
    engagement_velocity: Array<{
      id: number;
      title: string;
      published_at: string;
      claps_1h: number;
      claps_24h: number;
      claps_7d: number;
    }>;
  };
  comparative_analytics?: {
    current_period: any;
    previous_period: any;
    changes: any;
  };
  content_insights: any;
  timeframe_days: number;
  generated_at: string;
}

const AdvancedAnalytics: React.FC = () => {
  const { user } = useAuth();
  const [analytics, setAnalytics] = useState<AdvancedAnalyticsData | null>(null);
  const [loading, setLoading] = useState(true);
  const [timeframe, setTimeframe] = useState<number>(30);
  const [compareWith, setCompareWith] = useState<string>('');
  const [selectedArticles, setSelectedArticles] = useState<string>('');
  const [exportFormat, setExportFormat] = useState<string>('json');
  const [exporting, setExporting] = useState(false);

  useEffect(() => {
    fetchAdvancedAnalytics();
  }, [timeframe, compareWith]);

  const fetchAdvancedAnalytics = async () => {
    setLoading(true);
    try {
      const params = new URLSearchParams({
        timeframe: timeframe.toString(),
        ...(compareWith && { compare_with: compareWith }),
        ...(selectedArticles && { article_ids: selectedArticles })
      });

      const response = await apiService.dashboard.getAdvancedAnalytics(timeframe, compareWith, selectedArticles);
      if (response.data.success) {
        setAnalytics(response.data.data);
      }
    } catch (error) {
      console.error('Failed to fetch advanced analytics:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleExport = async () => {
    setExporting(true);
    try {
      const params = new URLSearchParams({
        format: exportFormat,
        timeframe: timeframe.toString(),
        data_type: 'all'
      });

      const response = await apiService.dashboard.exportAnalytics(exportFormat, timeframe, 'all');

      // Handle the export response
      if (response.data.success && response.data.data.download_url) {
        // If the API returns a download URL
        const link = document.createElement('a');
        link.href = response.data.data.download_url;
        link.setAttribute('download', `analytics_${exportFormat}_${new Date().toISOString().split('T')[0]}.${exportFormat}`);
        document.body.appendChild(link);
        link.click();
        link.remove();
      } else if (response.data.data) {
        // If the API returns the data directly, create a blob
        const dataStr = exportFormat === 'json' 
          ? JSON.stringify(response.data.data, null, 2)
          : response.data.data;
        const blob = new Blob([dataStr], { type: exportFormat === 'json' ? 'application/json' : 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `analytics_${exportFormat}_${new Date().toISOString().split('T')[0]}.${exportFormat}`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
      }
    } catch (error) {
      console.error('Failed to export analytics:', error);
    } finally {
      setExporting(false);
    }
  };

  const formatPercentage = (value: number) => {
    return `${value?.toFixed(1) || 0}%`;
  };

  const formatTime = (milliseconds: number) => {
    if (!milliseconds) return '0s';
    const seconds = Math.floor(milliseconds / 1000);
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return minutes > 0 ? `${minutes}m ${remainingSeconds}s` : `${remainingSeconds}s`;
  };

  const getChangeIcon = (change: number) => {
    if (change > 0) return <TrendingUpIcon className="h-4 w-4 text-green-500" />;
    if (change < 0) return <TrendingDownIcon className="h-4 w-4 text-red-500" />;
    return <span className="h-4 w-4 text-gray-400">—</span>;
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-indigo-600"></div>
      </div>
    );
  }

  if (!analytics) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <h2 className="text-2xl font-bold text-gray-900 mb-4">No Analytics Data</h2>
          <p className="text-gray-600">Start publishing articles to see your advanced analytics.</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="mb-8">
          <div className="flex justify-between items-center">
            <div>
              <h1 className="text-3xl font-bold text-gray-900">Advanced Analytics</h1>
              <p className="text-gray-600 mt-2">
                Deep insights into your content performance and audience behavior
              </p>
            </div>
            
            {/* Controls */}
            <div className="flex items-center space-x-4">
              {/* Timeframe Selector */}
              <div className="flex items-center space-x-2">
                <CalendarIcon className="h-5 w-5 text-gray-400" />
                <select
                  value={timeframe}
                  onChange={(e) => setTimeframe(Number(e.target.value))}
                  className="border-gray-300 rounded-md text-sm"
                >
                  <option value={7}>Last 7 days</option>
                  <option value={30}>Last 30 days</option>
                  <option value={90}>Last 90 days</option>
                  <option value={365}>Last year</option>
                </select>
              </div>

              {/* Comparison Selector */}
              <select
                value={compareWith}
                onChange={(e) => setCompareWith(e.target.value)}
                className="border-gray-300 rounded-md text-sm"
              >
                <option value="">No comparison</option>
                <option value="previous_period">Previous period</option>
                <option value="same_period_last_year">Same period last year</option>
              </select>

              {/* Export Button */}
              <div className="flex items-center space-x-2">
                <select
                  value={exportFormat}
                  onChange={(e) => setExportFormat(e.target.value)}
                  className="border-gray-300 rounded-md text-sm"
                >
                  <option value="json">JSON</option>
                  <option value="csv">CSV</option>
                  <option value="xlsx">Excel</option>
                </select>
                <button
                  onClick={handleExport}
                  disabled={exporting}
                  className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                >
                  <ArrowDownTrayIcon className="h-4 w-4 mr-2" />
                  {exporting ? 'Exporting...' : 'Export'}
                </button>
              </div>
            </div>
          </div>
        </div>

        {/* Performance Metrics Overview */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div className="bg-white overflow-hidden shadow rounded-lg">
            <div className="p-5">
              <div className="flex items-center">
                <div className="flex-shrink-0">
                  <ChartBarIcon className="h-6 w-6 text-gray-400" />
                </div>
                <div className="ml-5 w-0 flex-1">
                  <dl>
                    <dt className="text-sm font-medium text-gray-500 truncate">
                      Avg Engagement Score
                    </dt>
                    <dd className="text-lg font-medium text-gray-900">
                      {analytics.performance_metrics.length > 0 
                        ? Math.round(analytics.performance_metrics.reduce((sum, article) => sum + article.engagement_score, 0) / analytics.performance_metrics.length)
                        : 0
                      }
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div className="bg-white overflow-hidden shadow rounded-lg">
            <div className="p-5">
              <div className="flex items-center">
                <div className="flex-shrink-0">
                  <ClockIcon className="h-6 w-6 text-gray-400" />
                </div>
                <div className="ml-5 w-0 flex-1">
                  <dl>
                    <dt className="text-sm font-medium text-gray-500 truncate">
                      Avg Reading Time
                    </dt>
                    <dd className="text-lg font-medium text-gray-900">
                      {formatTime(analytics.reader_demographics.reading_behavior?.avg_reading_time || 0)}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div className="bg-white overflow-hidden shadow rounded-lg">
            <div className="p-5">
              <div className="flex items-center">
                <div className="flex-shrink-0">
                  <GlobeAltIcon className="h-6 w-6 text-gray-400" />
                </div>
                <div className="ml-5 w-0 flex-1">
                  <dl>
                    <dt className="text-sm font-medium text-gray-500 truncate">
                      Reader Retention
                    </dt>
                    <dd className="text-lg font-medium text-gray-900">
                      {formatPercentage(analytics.reader_demographics.retention_metrics?.retention_rate || 0)}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div className="bg-white overflow-hidden shadow rounded-lg">
            <div className="p-5">
              <div className="flex items-center">
                <div className="flex-shrink-0">
                  <TrendingUpIcon className="h-6 w-6 text-gray-400" />
                </div>
                <div className="ml-5 w-0 flex-1">
                  <dl>
                    <dt className="text-sm font-medium text-gray-500 truncate">
                      Completion Rate
                    </dt>
                    <dd className="text-lg font-medium text-gray-900">
                      {analytics.performance_metrics.length > 0 
                        ? formatPercentage(analytics.performance_metrics.reduce((sum, article) => sum + (article.read_completion_rate || 0), 0) / analytics.performance_metrics.length)
                        : '0%'
                      }
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
          {/* Reading Behavior Analysis */}
          <div className="bg-white shadow rounded-lg">
            <div className="px-6 py-4 border-b border-gray-200">
              <h2 className="text-lg font-medium text-gray-900">Reading Behavior</h2>
            </div>
            <div className="p-6">
              {analytics.reader_demographics.reading_behavior && (
                <div className="space-y-4">
                  <div className="flex justify-between items-center">
                    <span className="text-sm text-gray-600">Full Reads (90%+)</span>
                    <div className="flex items-center space-x-2">
                      <div className="w-32 bg-gray-200 rounded-full h-2">
                        <div
                          className="bg-green-500 h-2 rounded-full"
                          style={{
                            width: `${(analytics.reader_demographics.reading_behavior.full_reads / analytics.reader_demographics.reading_behavior.total_reads) * 100}%`
                          }}
                        />
                      </div>
                      <span className="text-sm font-medium text-gray-900">
                        {analytics.reader_demographics.reading_behavior.full_reads}
                      </span>
                    </div>
                  </div>

                  <div className="flex justify-between items-center">
                    <span className="text-sm text-gray-600">Partial Reads (50-89%)</span>
                    <div className="flex items-center space-x-2">
                      <div className="w-32 bg-gray-200 rounded-full h-2">
                        <div
                          className="bg-yellow-500 h-2 rounded-full"
                          style={{
                            width: `${(analytics.reader_demographics.reading_behavior.partial_reads / analytics.reader_demographics.reading_behavior.total_reads) * 100}%`
                          }}
                        />
                      </div>
                      <span className="text-sm font-medium text-gray-900">
                        {analytics.reader_demographics.reading_behavior.partial_reads}
                      </span>
                    </div>
                  </div>

                  <div className="flex justify-between items-center">
                    <span className="text-sm text-gray-600">Quick Scans (&lt;50%)</span>
                    <div className="flex items-center space-x-2">
                      <div className="w-32 bg-gray-200 rounded-full h-2">
                        <div
                          className="bg-red-500 h-2 rounded-full"
                          style={{
                            width: `${(analytics.reader_demographics.reading_behavior.quick_scans / analytics.reader_demographics.reading_behavior.total_reads) * 100}%`
                          }}
                        />
                      </div>
                      <span className="text-sm font-medium text-gray-900">
                        {analytics.reader_demographics.reading_behavior.quick_scans}
                      </span>
                    </div>
                  </div>
                </div>
              )}
            </div>
          </div>

          {/* Device Analytics */}
          <div className="bg-white shadow rounded-lg">
            <div className="px-6 py-4 border-b border-gray-200">
              <h2 className="text-lg font-medium text-gray-900">Device Distribution</h2>
            </div>
            <div className="p-6">
              <div className="space-y-4">
                {analytics.reader_demographics.device_analytics?.map((device, index) => (
                  <div key={index} className="flex items-center justify-between">
                    <div className="flex items-center space-x-3">
                      {device.device_type === 'Mobile' && <DevicePhoneMobileIcon className="h-5 w-5 text-gray-400" />}
                      {device.device_type === 'Desktop' && <ComputerDesktopIcon className="h-5 w-5 text-gray-400" />}
                      {device.device_type === 'Tablet' && <DevicePhoneMobileIcon className="h-5 w-5 text-gray-400" />}
                      <span className="text-sm font-medium text-gray-900">{device.device_type}</span>
                    </div>
                    <div className="flex items-center space-x-4">
                      <div className="text-right">
                        <div className="text-sm font-medium text-gray-900">{device.view_count}</div>
                        <div className="text-xs text-gray-500">{device.unique_users} users</div>
                      </div>
                      <div className="w-16 bg-gray-200 rounded-full h-2">
                        <div
                          className="bg-indigo-500 h-2 rounded-full"
                          style={{
                            width: `${(device.view_count / Math.max(...analytics.reader_demographics.device_analytics.map(d => d.view_count))) * 100}%`
                          }}
                        />
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>

        {/* Article Performance Comparison */}
        <div className="bg-white shadow rounded-lg mb-8">
          <div className="px-6 py-4 border-b border-gray-200">
            <h2 className="text-lg font-medium text-gray-900">Article Performance Comparison</h2>
          </div>
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Article
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Views
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Engagement
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Completion Rate
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Avg Time
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Score
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {analytics.performance_metrics.slice(0, 10).map((article) => (
                  <tr key={article.id}>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm font-medium text-gray-900 truncate max-w-xs">
                        {article.title}
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {article.view_count.toLocaleString()}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {article.clap_count + article.comment_count}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {formatPercentage(article.read_completion_rate || 0)}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {formatTime(article.avg_time_spent || 0)}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                        {Math.round(article.engagement_score)}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        {/* Engagement Velocity */}
        {analytics.engagement_patterns.engagement_velocity && analytics.engagement_patterns.engagement_velocity.length > 0 && (
          <div className="bg-white shadow rounded-lg">
            <div className="px-6 py-4 border-b border-gray-200">
              <h2 className="text-lg font-medium text-gray-900">Engagement Velocity</h2>
              <p className="text-sm text-gray-600">How quickly your articles gain engagement after publishing</p>
            </div>
            <div className="p-6">
              <div className="space-y-4">
                {analytics.engagement_patterns.engagement_velocity.slice(0, 5).map((article) => (
                  <div key={article.id} className="border rounded-lg p-4">
                    <h3 className="text-sm font-medium text-gray-900 mb-3">{article.title}</h3>
                    <div className="grid grid-cols-3 gap-4 text-center">
                      <div>
                        <div className="text-lg font-semibold text-gray-900">{article.claps_1h}</div>
                        <div className="text-xs text-gray-500">First Hour</div>
                      </div>
                      <div>
                        <div className="text-lg font-semibold text-gray-900">{article.claps_24h}</div>
                        <div className="text-xs text-gray-500">First Day</div>
                      </div>
                      <div>
                        <div className="text-lg font-semibold text-gray-900">{article.claps_7d}</div>
                        <div className="text-xs text-gray-500">First Week</div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default AdvancedAnalytics;