import React, { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { apiService } from '../utils/api';
// Note: Using placeholder icons - replace with actual icon library
const ChartBarIcon = ({ className }: { className?: string }) => <span className={className}>📊</span>;
const EyeIcon = ({ className }: { className?: string }) => <span className={className}>👁️</span>;
const HeartIcon = ({ className }: { className?: string }) => <span className={className}>❤️</span>;
const ChatBubbleLeftIcon = ({ className }: { className?: string }) => <span className={className}>💬</span>;
const UserGroupIcon = ({ className }: { className?: string }) => <span className={className}>👥</span>;
const TrendingUpIcon = ({ className }: { className?: string }) => <span className={className}>📈</span>;
const CalendarIcon = ({ className }: { className?: string }) => <span className={className}>📅</span>;

interface AnalyticsData {
  views_over_time: Array<{
    date: string;
    views: number;
  }>;
  engagement_over_time: Array<{
    date: string;
    claps: number;
    comments: number;
    follows: number;
  }>;
  article_performance: Array<{
    id: number;
    title: string;
    view_count: number;
    clap_count: number;
    comment_count: number;
    engagement_score: number;
    published_at: string;
  }>;
  audience_insights: {
    top_readers: Array<{
      id: number;
      username: string;
      profile_image_url?: string;
      comment_count: number;
      clap_count: number;
      engagement_score: number;
    }>;
    engagement_patterns: {
      by_day_of_week: Array<{
        day_name: string;
        day_number: number;
        clap_events: number;
        total_claps: number;
      }>;
      by_hour_of_day: Array<{
        hour: number;
        clap_events: number;
        total_claps: number;
      }>;
    };
    tag_performance: Array<{
      tag_name: string;
      article_count: number;
      avg_views: number;
      avg_claps: number;
      avg_comments: number;
      total_views: number;
    }>;
  };
  timeframe_days: number;
}

const WriterAnalytics: React.FC = () => {
  const { user } = useAuth();
  const [analytics, setAnalytics] = useState<AnalyticsData | null>(null);
  const [loading, setLoading] = useState(true);
  const [timeframe, setTimeframe] = useState<number>(30);

  useEffect(() => {
    fetchAnalytics();
  }, [timeframe]);

  const fetchAnalytics = async () => {
    setLoading(true);
    try {
      const response = await apiService.dashboard.getWriterAnalytics(timeframe);
      if (response.data.success) {
        setAnalytics(response.data.data);
      }
    } catch (error) {
      console.error('Failed to fetch analytics:', error);
    } finally {
      setLoading(false);
    }
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric'
    });
  };

  const getDayName = (dayNumber: number) => {
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    return days[dayNumber - 1];
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
          <p className="text-gray-600">Start publishing articles to see your analytics.</p>
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
              <h1 className="text-3xl font-bold text-gray-900">Analytics</h1>
              <p className="text-gray-600 mt-2">
                Insights into your content performance and audience engagement
              </p>
            </div>
            
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
          </div>
        </div>

        {/* Views Over Time Chart */}
        <div className="bg-white shadow rounded-lg mb-8">
          <div className="px-6 py-4 border-b border-gray-200">
            <h2 className="text-lg font-medium text-gray-900 flex items-center">
              <TrendingUpIcon className="h-5 w-5 mr-2" />
              Views Over Time
            </h2>
          </div>
          <div className="p-6">
            <div className="h-64 flex items-end justify-between space-x-1">
              {analytics.views_over_time.map((data, index) => {
                const maxViews = Math.max(...analytics.views_over_time.map(d => d.views));
                const height = maxViews > 0 ? (data.views / maxViews) * 100 : 0;
                
                return (
                  <div key={index} className="flex flex-col items-center flex-1">
                    <div
                      className="bg-indigo-500 rounded-t w-full min-h-[4px] transition-all duration-300 hover:bg-indigo-600"
                      style={{ height: `${height}%` }}
                      title={`${data.views} views on ${formatDate(data.date)}`}
                    />
                    <div className="text-xs text-gray-500 mt-2 transform -rotate-45 origin-left">
                      {formatDate(data.date)}
                    </div>
                  </div>
                );
              })}
            </div>
            <div className="mt-4 text-sm text-gray-600">
              Total views in the last {timeframe} days: {' '}
              <span className="font-semibold">
                {analytics.views_over_time.reduce((sum, data) => sum + data.views, 0).toLocaleString()}
              </span>
            </div>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
          {/* Engagement Over Time */}
          <div className="bg-white shadow rounded-lg">
            <div className="px-6 py-4 border-b border-gray-200">
              <h2 className="text-lg font-medium text-gray-900">Engagement Trends</h2>
            </div>
            <div className="p-6">
              <div className="space-y-4">
                {/* Claps Trend */}
                <div>
                  <div className="flex items-center justify-between mb-2">
                    <span className="text-sm font-medium text-gray-700 flex items-center">
                      <HeartIcon className="h-4 w-4 mr-1 text-red-500" />
                      Claps
                    </span>
                    <span className="text-sm text-gray-500">
                      {analytics.engagement_over_time.reduce((sum, data) => sum + data.claps, 0)}
                    </span>
                  </div>
                  <div className="h-8 bg-gray-200 rounded-full overflow-hidden">
                    <div className="h-full flex">
                      {analytics.engagement_over_time.map((data, index) => {
                        const maxClaps = Math.max(...analytics.engagement_over_time.map(d => d.claps));
                        const width = analytics.engagement_over_time.length > 0 ? 100 / analytics.engagement_over_time.length : 0;
                        const opacity = maxClaps > 0 ? Math.max(0.1, data.claps / maxClaps) : 0.1;
                        
                        return (
                          <div
                            key={index}
                            className="bg-red-500"
                            style={{ 
                              width: `${width}%`,
                              opacity: opacity
                            }}
                            title={`${data.claps} claps on ${formatDate(data.date)}`}
                          />
                        );
                      })}
                    </div>
                  </div>
                </div>

                {/* Comments Trend */}
                <div>
                  <div className="flex items-center justify-between mb-2">
                    <span className="text-sm font-medium text-gray-700 flex items-center">
                      <ChatBubbleLeftIcon className="h-4 w-4 mr-1 text-blue-500" />
                      Comments
                    </span>
                    <span className="text-sm text-gray-500">
                      {analytics.engagement_over_time.reduce((sum, data) => sum + data.comments, 0)}
                    </span>
                  </div>
                  <div className="h-8 bg-gray-200 rounded-full overflow-hidden">
                    <div className="h-full flex">
                      {analytics.engagement_over_time.map((data, index) => {
                        const maxComments = Math.max(...analytics.engagement_over_time.map(d => d.comments));
                        const width = analytics.engagement_over_time.length > 0 ? 100 / analytics.engagement_over_time.length : 0;
                        const opacity = maxComments > 0 ? Math.max(0.1, data.comments / maxComments) : 0.1;
                        
                        return (
                          <div
                            key={index}
                            className="bg-blue-500"
                            style={{ 
                              width: `${width}%`,
                              opacity: opacity
                            }}
                            title={`${data.comments} comments on ${formatDate(data.date)}`}
                          />
                        );
                      })}
                    </div>
                  </div>
                </div>

                {/* Follows Trend */}
                <div>
                  <div className="flex items-center justify-between mb-2">
                    <span className="text-sm font-medium text-gray-700 flex items-center">
                      <UserGroupIcon className="h-4 w-4 mr-1 text-green-500" />
                      New Followers
                    </span>
                    <span className="text-sm text-gray-500">
                      {analytics.engagement_over_time.reduce((sum, data) => sum + data.follows, 0)}
                    </span>
                  </div>
                  <div className="h-8 bg-gray-200 rounded-full overflow-hidden">
                    <div className="h-full flex">
                      {analytics.engagement_over_time.map((data, index) => {
                        const maxFollows = Math.max(...analytics.engagement_over_time.map(d => d.follows));
                        const width = analytics.engagement_over_time.length > 0 ? 100 / analytics.engagement_over_time.length : 0;
                        const opacity = maxFollows > 0 ? Math.max(0.1, data.follows / maxFollows) : 0.1;
                        
                        return (
                          <div
                            key={index}
                            className="bg-green-500"
                            style={{ 
                              width: `${width}%`,
                              opacity: opacity
                            }}
                            title={`${data.follows} new followers on ${formatDate(data.date)}`}
                          />
                        );
                      })}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Article Performance */}
          <div className="bg-white shadow rounded-lg">
            <div className="px-6 py-4 border-b border-gray-200">
              <h2 className="text-lg font-medium text-gray-900">Article Performance</h2>
            </div>
            <div className="divide-y divide-gray-200 max-h-96 overflow-y-auto">
              {analytics.article_performance.slice(0, 10).map((article) => (
                <div key={article.id} className="px-6 py-4">
                  <h3 className="text-sm font-medium text-gray-900 truncate mb-2">
                    {article.title}
                  </h3>
                  <div className="flex items-center justify-between text-xs text-gray-500">
                    <div className="flex items-center space-x-3">
                      <span className="flex items-center">
                        <EyeIcon className="h-3 w-3 mr-1" />
                        {article.view_count}
                      </span>
                      <span className="flex items-center">
                        <HeartIcon className="h-3 w-3 mr-1" />
                        {article.clap_count}
                      </span>
                      <span className="flex items-center">
                        <ChatBubbleLeftIcon className="h-3 w-3 mr-1" />
                        {article.comment_count}
                      </span>
                    </div>
                    <span className="font-medium">
                      Score: {Math.round(article.engagement_score)}
                    </span>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Top Readers */}
          <div className="bg-white shadow rounded-lg">
            <div className="px-6 py-4 border-b border-gray-200">
              <h2 className="text-lg font-medium text-gray-900">Top Readers</h2>
            </div>
            <div className="divide-y divide-gray-200">
              {analytics.audience_insights.top_readers.slice(0, 5).map((reader) => (
                <div key={reader.id} className="px-6 py-4 flex items-center space-x-3">
                  <div className="flex-shrink-0">
                    {reader.profile_image_url ? (
                      <img
                        className="h-8 w-8 rounded-full"
                        src={reader.profile_image_url}
                        alt={reader.username}
                      />
                    ) : (
                      <div className="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                        <span className="text-xs font-medium text-gray-700">
                          {reader.username.charAt(0).toUpperCase()}
                        </span>
                      </div>
                    )}
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium text-gray-900 truncate">
                      {reader.username}
                    </p>
                    <p className="text-xs text-gray-500">
                      {reader.comment_count} comments, {reader.clap_count} claps
                    </p>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Engagement by Day */}
          <div className="bg-white shadow rounded-lg">
            <div className="px-6 py-4 border-b border-gray-200">
              <h2 className="text-lg font-medium text-gray-900">Best Days</h2>
            </div>
            <div className="p-6">
              <div className="space-y-3">
                {analytics.audience_insights.engagement_patterns.by_day_of_week
                  .sort((a, b) => b.total_claps - a.total_claps)
                  .slice(0, 7)
                  .map((day) => (
                    <div key={day.day_number} className="flex items-center justify-between">
                      <span className="text-sm text-gray-700">
                        {getDayName(day.day_number)}
                      </span>
                      <div className="flex items-center space-x-2">
                        <div className="w-16 bg-gray-200 rounded-full h-2">
                          <div
                            className="bg-indigo-500 h-2 rounded-full"
                            style={{
                              width: `${Math.max(10, (day.total_claps / Math.max(...analytics.audience_insights.engagement_patterns.by_day_of_week.map(d => d.total_claps))) * 100)}%`
                            }}
                          />
                        </div>
                        <span className="text-xs text-gray-500 w-8 text-right">
                          {day.total_claps}
                        </span>
                      </div>
                    </div>
                  ))}
              </div>
            </div>
          </div>

          {/* Tag Performance */}
          <div className="bg-white shadow rounded-lg">
            <div className="px-6 py-4 border-b border-gray-200">
              <h2 className="text-lg font-medium text-gray-900">Top Tags</h2>
            </div>
            <div className="divide-y divide-gray-200">
              {analytics.audience_insights.tag_performance.slice(0, 5).map((tag) => (
                <div key={tag.tag_name} className="px-6 py-4">
                  <div className="flex items-center justify-between mb-2">
                    <span className="text-sm font-medium text-gray-900">
                      {tag.tag_name}
                    </span>
                    <span className="text-xs text-gray-500">
                      {tag.article_count} articles
                    </span>
                  </div>
                  <div className="text-xs text-gray-500">
                    Avg: {Math.round(tag.avg_views)} views, {Math.round(tag.avg_claps)} claps
                  </div>
                  <div className="text-xs font-medium text-indigo-600">
                    Total: {tag.total_views.toLocaleString()} views
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default WriterAnalytics;