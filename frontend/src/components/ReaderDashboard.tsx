import React, { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { apiService } from '../utils/api';

// Placeholder icons - replace with actual icon library
const BookmarkIcon = ({ className }: { className?: string }) => <span className={className}>🔖</span>;
const EyeIcon = ({ className }: { className?: string }) => <span className={className}>👁️</span>;
const HeartIcon = ({ className }: { className?: string }) => <span className={className}>❤️</span>;
const ChatBubbleLeftIcon = ({ className }: { className?: string }) => <span className={className}>💬</span>;
const UserGroupIcon = ({ className }: { className?: string }) => <span className={className}>👥</span>;
const ClockIcon = ({ className }: { className?: string }) => <span className={className}>🕐</span>;
const ChartBarIcon = ({ className }: { className?: string }) => <span className={className}>📊</span>;
const SearchIcon = ({ className }: { className?: string }) => <span className={className}>🔍</span>;
const FilterIcon = ({ className }: { className?: string }) => <span className={className}>🔽</span>;

interface ReadingStats {
  total_articles_read?: number;
  total_reading_time?: number;
  articles_bookmarked?: number;
  authors_followed?: number;
  reading_streak?: number;
  favorite_topics?: string[];
}

interface BookmarkedArticle {
  id: number;
  title: string;
  subtitle?: string;
  author_name: string;
  author_avatar?: string;
  bookmarked_at: string;
  reading_time: number;
  tags: string[];
  view_count: number;
  clap_count: number;
}

interface FollowingFeedArticle {
  id: number;
  title: string;
  subtitle?: string;
  author_name: string;
  author_avatar?: string;
  published_at: string;
  reading_time: number;
  tags: string[];
  preview: string;
}

interface ReadingHistoryItem {
  id: number;
  title: string;
  author_name: string;
  read_at: string;
  time_spent: number;
  completion_percentage: number;
}

const ReaderDashboard: React.FC = () => {
  const { user } = useAuth();
  const [stats, setStats] = useState<ReadingStats | null>(null);
  const [bookmarks, setBookmarks] = useState<BookmarkedArticle[]>([]);
  const [followingFeed, setFollowingFeed] = useState<FollowingFeedArticle[]>([]);
  const [readingHistory, setReadingHistory] = useState<ReadingHistoryItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState<'bookmarks' | 'following' | 'history'>('bookmarks');
  const [bookmarkSearch, setBookmarkSearch] = useState('');
  const [bookmarkFilter, setBookmarkFilter] = useState<'all' | 'recent' | 'popular'>('all');

  useEffect(() => {
    fetchReaderData();
  }, []);

  const fetchReaderData = async () => {
    try {
      setLoading(true);
      
      // Fetch all reader dashboard data
      const [statsResponse, bookmarksResponse, feedResponse, historyResponse] = await Promise.all([
        apiService.dashboard.getReaderStats(),
        apiService.dashboard.getBookmarks(),
        apiService.dashboard.getFollowingFeed(),
        apiService.dashboard.getReadingHistory()
      ]);

      // Ensure all numeric fields exist with fallbacks
      const statsData = statsResponse.data || {};
      const bookmarksData = bookmarksResponse.data || {};
      const feedData = feedResponse.data || {};
      const historyData = historyResponse.data || {};
      
      const processedStats = {
        ...statsData,
        total_articles_read: (statsData as any)['total_articles_read'] || 0,
        total_reading_time: (statsData as any)['total_reading_time'] || 0,
        articles_bookmarked: (statsData as any)['articles_bookmarked'] || 0,
        authors_followed: (statsData as any)['authors_followed'] || 0,
        reading_streak: (statsData as any)['reading_streak'] || 0,
        favorite_topics: (statsData as any)['favorite_topics'] || []
      };
      setStats(processedStats);
      setBookmarks((bookmarksData as any)['bookmarks'] || []);
      setFollowingFeed((feedData as any)['articles'] || []);
      setReadingHistory((historyData as any)['history'] || []);
    } catch (error) {
      console.error('Failed to fetch reader dashboard data:', error);
      // Set default empty stats to prevent undefined errors
      setStats({
        total_articles_read: 0,
        total_reading_time: 0,
        articles_bookmarked: 0,
        authors_followed: 0,
        reading_streak: 0,
        favorite_topics: []
      });
    } finally {
      setLoading(false);
    }
  };

  const removeBookmark = async (articleId: number) => {
    try {
      await apiService.bookmarks.remove(articleId);
      setBookmarks(prev => prev.filter(bookmark => bookmark.id !== articleId));
    } catch (error) {
      console.error('Failed to remove bookmark:', error);
    }
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  const formatReadingTime = (minutes: number) => {
    if (minutes < 60) {
      return `${minutes}m`;
    }
    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;
    return `${hours}h ${remainingMinutes}m`;
  };

  const filteredBookmarks = bookmarks.filter(bookmark => {
    const matchesSearch = bookmarkSearch === '' || 
      bookmark.title.toLowerCase().includes(bookmarkSearch.toLowerCase()) ||
      bookmark.author_name.toLowerCase().includes(bookmarkSearch.toLowerCase()) ||
      bookmark.tags.some(tag => tag.toLowerCase().includes(bookmarkSearch.toLowerCase()));
    
    if (!matchesSearch) return false;

    switch (bookmarkFilter) {
      case 'recent':
        const weekAgo = new Date();
        weekAgo.setDate(weekAgo.getDate() - 7);
        return new Date(bookmark.bookmarked_at) > weekAgo;
      case 'popular':
        return bookmark.clap_count > 10 || bookmark.view_count > 100;
      default:
        return true;
    }
  });

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-indigo-600"></div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900">Reader Dashboard</h1>
          <p className="text-gray-600 mt-2">
            Track your reading journey and discover new content, {user?.username}!
          </p>
        </div>

        {/* Stats Overview */}
        {stats && (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div className="bg-white overflow-hidden shadow rounded-lg">
              <div className="p-5">
                <div className="flex items-center">
                  <div className="flex-shrink-0">
                    <EyeIcon className="h-6 w-6 text-gray-400" />
                  </div>
                  <div className="ml-5 w-0 flex-1">
                    <dl>
                      <dt className="text-sm font-medium text-gray-500 truncate">
                        Articles Read
                      </dt>
                      <dd className="text-lg font-medium text-gray-900">
                        {(stats.total_articles_read || 0).toLocaleString()}
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
                        Reading Time
                      </dt>
                      <dd className="text-lg font-medium text-gray-900">
                        {formatReadingTime(stats.total_reading_time || 0)}
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
                    <BookmarkIcon className="h-6 w-6 text-gray-400" />
                  </div>
                  <div className="ml-5 w-0 flex-1">
                    <dl>
                      <dt className="text-sm font-medium text-gray-500 truncate">
                        Bookmarks
                      </dt>
                      <dd className="text-lg font-medium text-gray-900">
                        {(stats.articles_bookmarked || 0).toLocaleString()}
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
                    <UserGroupIcon className="h-6 w-6 text-gray-400" />
                  </div>
                  <div className="ml-5 w-0 flex-1">
                    <dl>
                      <dt className="text-sm font-medium text-gray-500 truncate">
                        Following
                      </dt>
                      <dd className="text-lg font-medium text-gray-900">
                        {(stats.authors_followed || 0).toLocaleString()}
                      </dd>
                    </dl>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Main Content */}
          <div className="lg:col-span-2">
            {/* Tab Navigation */}
            <div className="bg-white shadow rounded-lg">
              <div className="border-b border-gray-200">
                <nav className="-mb-px flex space-x-8 px-6">
                  <button
                    onClick={() => setActiveTab('bookmarks')}
                    className={`py-4 px-1 border-b-2 font-medium text-sm ${
                      activeTab === 'bookmarks'
                        ? 'border-indigo-500 text-indigo-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    }`}
                  >
                    <BookmarkIcon className="h-4 w-4 inline mr-2" />
                    Bookmarks ({bookmarks.length})
                  </button>
                  <button
                    onClick={() => setActiveTab('following')}
                    className={`py-4 px-1 border-b-2 font-medium text-sm ${
                      activeTab === 'following'
                        ? 'border-indigo-500 text-indigo-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    }`}
                  >
                    <UserGroupIcon className="h-4 w-4 inline mr-2" />
                    Following Feed ({followingFeed.length})
                  </button>
                  <button
                    onClick={() => setActiveTab('history')}
                    className={`py-4 px-1 border-b-2 font-medium text-sm ${
                      activeTab === 'history'
                        ? 'border-indigo-500 text-indigo-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    }`}
                  >
                    <ClockIcon className="h-4 w-4 inline mr-2" />
                    Reading History ({readingHistory.length})
                  </button>
                </nav>
              </div>

              {/* Tab Content */}
              <div className="p-6">
                {/* Bookmarks Tab */}
                {activeTab === 'bookmarks' && (
                  <div>
                    {/* Search and Filter */}
                    <div className="mb-6 flex flex-col sm:flex-row gap-4">
                      <div className="flex-1 relative">
                        <SearchIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <input
                          type="text"
                          placeholder="Search bookmarks..."
                          value={bookmarkSearch}
                          onChange={(e) => setBookmarkSearch(e.target.value)}
                          className="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                        />
                      </div>
                      <select
                        value={bookmarkFilter}
                        onChange={(e) => setBookmarkFilter(e.target.value as any)}
                        className="px-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                      >
                        <option value="all">All Bookmarks</option>
                        <option value="recent">Recent (Last 7 days)</option>
                        <option value="popular">Popular</option>
                      </select>
                    </div>

                    {/* Bookmarks List */}
                    {filteredBookmarks.length === 0 ? (
                      <div className="text-center py-12">
                        <BookmarkIcon className="mx-auto h-12 w-12 text-gray-400" />
                        <h3 className="mt-2 text-sm font-medium text-gray-900">No bookmarks found</h3>
                        <p className="mt-1 text-sm text-gray-500">
                          {bookmarkSearch || bookmarkFilter !== 'all' 
                            ? 'Try adjusting your search or filter.'
                            : 'Start bookmarking articles to see them here.'
                          }
                        </p>
                      </div>
                    ) : (
                      <div className="space-y-4">
                        {filteredBookmarks.map((bookmark) => (
                          <div key={bookmark.id} className="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                            <div className="flex justify-between items-start">
                              <div className="flex-1">
                                <h3 className="text-lg font-medium text-gray-900 hover:text-indigo-600">
                                  <a href={`/article/${bookmark.id}`}>
                                    {bookmark.title}
                                  </a>
                                </h3>
                                {bookmark.subtitle && (
                                  <p className="text-gray-600 mt-1">{bookmark.subtitle}</p>
                                )}
                                
                                <div className="flex items-center mt-2 text-sm text-gray-500">
                                  <img
                                    src={bookmark.author_avatar || '/default-avatar.svg'}
                                    alt={bookmark.author_name}
                                    className="h-6 w-6 rounded-full mr-2"
                                    onError={(e) => {
                                      const target = e.target as HTMLImageElement;
                                      if (target.src !== window.location.origin + '/default-avatar.svg') {
                                        target.src = '/default-avatar.svg';
                                      }
                                    }}
                                  />
                                  <span className="mr-4">{bookmark.author_name}</span>
                                  <ClockIcon className="h-4 w-4 mr-1" />
                                  <span className="mr-4">{bookmark.reading_time} min read</span>
                                  <span>Bookmarked {formatDate(bookmark.bookmarked_at)}</span>
                                </div>

                                <div className="flex items-center justify-between mt-3">
                                  <div className="flex flex-wrap gap-1">
                                    {bookmark.tags.slice(0, 3).map((tag, index) => (
                                      <span
                                        key={index}
                                        className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800"
                                      >
                                        {tag}
                                      </span>
                                    ))}
                                    {bookmark.tags.length > 3 && (
                                      <span className="text-xs text-gray-500">
                                        +{bookmark.tags.length - 3} more
                                      </span>
                                    )}
                                  </div>
                                  
                                  <div className="flex items-center space-x-4 text-sm text-gray-500">
                                    <span className="flex items-center">
                                      <EyeIcon className="h-4 w-4 mr-1" />
                                      {bookmark.view_count}
                                    </span>
                                    <span className="flex items-center">
                                      <HeartIcon className="h-4 w-4 mr-1" />
                                      {bookmark.clap_count}
                                    </span>
                                  </div>
                                </div>
                              </div>
                              
                              <button
                                onClick={() => removeBookmark(bookmark.id)}
                                className="ml-4 text-gray-400 hover:text-red-500"
                                title="Remove bookmark"
                              >
                                <BookmarkIcon className="h-5 w-5" />
                              </button>
                            </div>
                          </div>
                        ))}
                      </div>
                    )}
                  </div>
                )}

                {/* Following Feed Tab */}
                {activeTab === 'following' && (
                  <div>
                    {followingFeed.length === 0 ? (
                      <div className="text-center py-12">
                        <UserGroupIcon className="mx-auto h-12 w-12 text-gray-400" />
                        <h3 className="mt-2 text-sm font-medium text-gray-900">No articles from followed authors</h3>
                        <p className="mt-1 text-sm text-gray-500">
                          Follow some authors to see their latest articles here.
                        </p>
                      </div>
                    ) : (
                      <div className="space-y-6">
                        {followingFeed.map((article) => (
                          <div key={article.id} className="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                            <h3 className="text-lg font-medium text-gray-900 hover:text-indigo-600">
                              <a href={`/article/${article.id}`}>
                                {article.title}
                              </a>
                            </h3>
                            {article.subtitle && (
                              <p className="text-gray-600 mt-1">{article.subtitle}</p>
                            )}
                            
                            <p className="text-gray-700 mt-2 line-clamp-3">
                              {article.preview}
                            </p>
                            
                            <div className="flex items-center justify-between mt-4">
                              <div className="flex items-center text-sm text-gray-500">
                                <img
                                  src={article.author_avatar || '/default-avatar.svg'}
                                  alt={article.author_name}
                                  className="h-6 w-6 rounded-full mr-2"
                                  onError={(e) => {
                                    const target = e.target as HTMLImageElement;
                                    if (target.src !== window.location.origin + '/default-avatar.svg') {
                                      target.src = '/default-avatar.svg';
                                    }
                                  }}
                                />
                                <span className="mr-4">{article.author_name}</span>
                                <ClockIcon className="h-4 w-4 mr-1" />
                                <span className="mr-4">{article.reading_time} min read</span>
                                <span>{formatDate(article.published_at)}</span>
                              </div>
                            </div>

                            {article.tags.length > 0 && (
                              <div className="flex flex-wrap gap-1 mt-3">
                                {article.tags.slice(0, 4).map((tag, index) => (
                                  <span
                                    key={index}
                                    className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800"
                                  >
                                    {tag}
                                  </span>
                                ))}
                                {article.tags.length > 4 && (
                                  <span className="text-xs text-gray-500">
                                    +{article.tags.length - 4} more
                                  </span>
                                )}
                              </div>
                            )}
                          </div>
                        ))}
                      </div>
                    )}
                  </div>
                )}

                {/* Reading History Tab */}
                {activeTab === 'history' && (
                  <div>
                    {readingHistory.length === 0 ? (
                      <div className="text-center py-12">
                        <ClockIcon className="mx-auto h-12 w-12 text-gray-400" />
                        <h3 className="mt-2 text-sm font-medium text-gray-900">No reading history</h3>
                        <p className="mt-1 text-sm text-gray-500">
                          Start reading articles to see your history here.
                        </p>
                      </div>
                    ) : (
                      <div className="space-y-3">
                        {readingHistory.map((item) => (
                          <div key={`${item.id}-${item.read_at}`} className="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                            <div className="flex-1">
                              <h4 className="text-sm font-medium text-gray-900 hover:text-indigo-600">
                                <a href={`/article/${item.id}`}>
                                  {item.title}
                                </a>
                              </h4>
                              <p className="text-xs text-gray-500 mt-1">
                                by {item.author_name} • Read {formatDate(item.read_at)}
                              </p>
                            </div>
                            
                            <div className="flex items-center space-x-4 text-xs text-gray-500">
                              <span>{formatReadingTime(item.time_spent)}</span>
                              <div className="flex items-center">
                                <div className="w-16 bg-gray-200 rounded-full h-2">
                                  <div 
                                    className="bg-indigo-600 h-2 rounded-full" 
                                    style={{ width: `${item.completion_percentage}%` }}
                                  ></div>
                                </div>
                                <span className="ml-2">{item.completion_percentage}%</span>
                              </div>
                            </div>
                          </div>
                        ))}
                      </div>
                    )}
                  </div>
                )}
              </div>
            </div>
          </div>

          {/* Sidebar */}
          <div className="space-y-6">
            {/* Reading Statistics */}
            {stats && (
              <div className="bg-white shadow rounded-lg">
                <div className="px-6 py-4 border-b border-gray-200">
                  <h3 className="text-lg font-medium text-gray-900">Reading Statistics</h3>
                </div>
                <div className="p-6 space-y-4">
                  <div className="flex justify-between items-center">
                    <span className="text-sm text-gray-600">Reading Streak</span>
                    <span className="text-sm font-medium text-gray-900">{stats.reading_streak || 0} days</span>
                  </div>
                  
                  <div>
                    <span className="text-sm text-gray-600">Favorite Topics</span>
                    <div className="flex flex-wrap gap-1 mt-2">
                      {(stats.favorite_topics || []).slice(0, 5).map((topic, index) => (
                        <span
                          key={index}
                          className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800"
                        >
                          {topic}
                        </span>
                      ))}
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* Quick Actions */}
            <div className="bg-white shadow rounded-lg">
              <div className="px-6 py-4 border-b border-gray-200">
                <h3 className="text-lg font-medium text-gray-900">Quick Actions</h3>
              </div>
              <div className="p-6 space-y-3">
                <button
                  onClick={() => window.location.href = '/'}
                  className="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                >
                  <EyeIcon className="h-4 w-4 mr-2" />
                  Discover Articles
                </button>
                
                <button
                  onClick={() => window.location.href = '/search'}
                  className="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                >
                  <SearchIcon className="h-4 w-4 mr-2" />
                  Search Content
                </button>
                
                <button
                  onClick={() => window.location.href = '/tags'}
                  className="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                >
                  <FilterIcon className="h-4 w-4 mr-2" />
                  Browse Topics
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ReaderDashboard;