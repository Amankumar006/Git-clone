import React, { useState, useEffect } from 'react';
import { apiService } from '../utils/api';

interface AnalyticsData {
  basic_stats: {
    total_views: number;
    total_reads: number;
    avg_time_spent: number;
    avg_scroll_depth: number;
    unique_viewers: number;
    unique_readers: number;
  };
  view_trends: Array<{
    date: string;
    views: number;
    unique_views: number;
  }>;
  reading_stats: {
    avg_time_spent: number;
    max_time_spent: number;
    avg_scroll_depth: number;
    deep_reads: number;
    total_reads: number;
  };
  referrer_stats: Array<{
    source: string;
    views: number;
  }>;
}

interface ArticleAnalyticsProps {
  articleId: string;
  className?: string;
}

const ArticleAnalytics: React.FC<ArticleAnalyticsProps> = ({ 
  articleId, 
  className = '' 
}) => {
  const [analytics, setAnalytics] = useState<AnalyticsData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchAnalytics();
  }, [articleId]);

  const fetchAnalytics = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const response = await apiService.get(`/articles/analytics/${articleId}`);
      
      if (response.success && response.data) {
        setAnalytics(response.data as AnalyticsData);
      } else {
        setError('Failed to load analytics data');
      }
    } catch (err: any) {
      setError(err.message || 'Failed to load analytics');
    } finally {
      setLoading(false);
    }
  };

  const formatTime = (milliseconds: number): string => {
    const seconds = Math.floor(milliseconds / 1000);
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    
    if (minutes > 0) {
      return `${minutes}m ${remainingSeconds}s`;
    }
    return `${remainingSeconds}s`;
  };

  const formatPercentage = (value: number): string => {
    return `${Math.round(value)}%`;
  };

  if (loading) {
    return (
      <div className={`analytics-dashboard ${className}`}>
        <div className="animate-pulse">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            {[...Array(6)].map((_, index) => (
              <div key={index} className="bg-gray-200 h-24 rounded-lg"></div>
            ))}
          </div>
        </div>
      </div>
    );
  }

  if (error || !analytics) {
    return (
      <div className={`analytics-dashboard ${className}`}>
        <div className="text-center py-8">
          <p className="text-gray-500">{error || 'No analytics data available'}</p>
        </div>
      </div>
    );
  }

  const readRate = analytics.basic_stats.total_views > 0 
    ? (analytics.basic_stats.total_reads / analytics.basic_stats.total_views) * 100 
    : 0;

  return (
    <div className={`analytics-dashboard ${className}`}>
      <h3 className="text-xl font-bold text-gray-900 mb-6">Article Analytics</h3>
      
      {/* Key Metrics */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div className="bg-white p-6 rounded-lg border border-gray-200">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <svg className="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-500">Total Views</p>
              <p className="text-2xl font-bold text-gray-900">
                {analytics.basic_stats.total_views.toLocaleString()}
              </p>
            </div>
          </div>
        </div>

        <div className="bg-white p-6 rounded-lg border border-gray-200">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <svg className="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
              </svg>
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-500">Total Reads</p>
              <p className="text-2xl font-bold text-gray-900">
                {analytics.basic_stats.total_reads.toLocaleString()}
              </p>
            </div>
          </div>
        </div>

        <div className="bg-white p-6 rounded-lg border border-gray-200">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <svg className="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-500">Read Rate</p>
              <p className="text-2xl font-bold text-gray-900">
                {formatPercentage(readRate)}
              </p>
            </div>
          </div>
        </div>

        <div className="bg-white p-6 rounded-lg border border-gray-200">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <svg className="w-8 h-8 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-500">Avg. Time Spent</p>
              <p className="text-2xl font-bold text-gray-900">
                {formatTime(analytics.reading_stats.avg_time_spent || 0)}
              </p>
            </div>
          </div>
        </div>

        <div className="bg-white p-6 rounded-lg border border-gray-200">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <svg className="w-8 h-8 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
              </svg>
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-500">Avg. Scroll Depth</p>
              <p className="text-2xl font-bold text-gray-900">
                {formatPercentage(analytics.reading_stats.avg_scroll_depth || 0)}
              </p>
            </div>
          </div>
        </div>

        <div className="bg-white p-6 rounded-lg border border-gray-200">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <svg className="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-500">Unique Readers</p>
              <p className="text-2xl font-bold text-gray-900">
                {analytics.basic_stats.unique_readers.toLocaleString()}
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Traffic Sources */}
      {analytics.referrer_stats.length > 0 && (
        <div className="bg-white p-6 rounded-lg border border-gray-200 mb-6">
          <h4 className="text-lg font-semibold text-gray-900 mb-4">Traffic Sources</h4>
          <div className="space-y-3">
            {analytics.referrer_stats.map((source, index) => {
              const percentage = analytics.basic_stats.total_views > 0 
                ? (source.views / analytics.basic_stats.total_views) * 100 
                : 0;
              
              return (
                <div key={index} className="flex items-center justify-between">
                  <div className="flex items-center">
                    <div className="w-3 h-3 bg-blue-500 rounded-full mr-3"></div>
                    <span className="text-sm font-medium text-gray-700">{source.source}</span>
                  </div>
                  <div className="flex items-center space-x-2">
                    <span className="text-sm text-gray-500">{source.views} views</span>
                    <span className="text-sm font-medium text-gray-900">
                      {formatPercentage(percentage)}
                    </span>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* Reading Engagement */}
      <div className="bg-white p-6 rounded-lg border border-gray-200">
        <h4 className="text-lg font-semibold text-gray-900 mb-4">Reading Engagement</h4>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <p className="text-sm font-medium text-gray-500 mb-2">Deep Reads (75%+ scroll)</p>
            <p className="text-xl font-bold text-gray-900">
              {analytics.reading_stats.deep_reads} 
              <span className="text-sm font-normal text-gray-500 ml-1">
                ({analytics.reading_stats.total_reads > 0 
                  ? formatPercentage((analytics.reading_stats.deep_reads / analytics.reading_stats.total_reads) * 100)
                  : '0%'})
              </span>
            </p>
          </div>
          <div>
            <p className="text-sm font-medium text-gray-500 mb-2">Max Time Spent</p>
            <p className="text-xl font-bold text-gray-900">
              {formatTime(analytics.reading_stats.max_time_spent || 0)}
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ArticleAnalytics;