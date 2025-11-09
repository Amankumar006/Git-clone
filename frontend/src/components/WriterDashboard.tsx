import React, { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { apiService } from '../utils/api';
import Toast from './Toast';
import ConfirmationModal from './ConfirmationModal';
import { useToast } from '../hooks/useToast';
// Note: Using placeholder icons - replace with actual icon library
const PencilIcon = ({ className }: { className?: string }) => <span className={className}>✏️</span>;
const EyeIcon = ({ className }: { className?: string }) => <span className={className}>👁️</span>;
const HeartIcon = ({ className }: { className?: string }) => <span className={className}>❤️</span>;
const ChatBubbleLeftIcon = ({ className }: { className?: string }) => <span className={className}>💬</span>;
const UserGroupIcon = ({ className }: { className?: string }) => <span className={className}>👥</span>;
const ChartBarIcon = ({ className }: { className?: string }) => <span className={className}>📊</span>;
const DocumentTextIcon = ({ className }: { className?: string }) => <span className={className}>📄</span>;
const ArchiveBoxIcon = ({ className }: { className?: string }) => <span className={className}>📦</span>;
const TrashIcon = ({ className }: { className?: string }) => <span className={className}>🗑️</span>;
const PlusIcon = ({ className }: { className?: string }) => <span className={className}>➕</span>;

interface WriterStats {
  article_counts?: {
    draft: number;
    published: number;
    archived: number;
  };
  total_views?: number;
  total_claps?: number;
  total_comments?: number;
  follower_count?: number;
  recent_activity?: Array<{
    type: string;
    data: any;
    timestamp: string;
  }>;
  top_articles?: Array<{
    id: number;
    title: string;
    view_count: number;
    clap_count: number;
    comment_count: number;
    engagement_score: number;
  }>;
}

interface Article {
  id: number;
  title: string;
  subtitle?: string;
  status: 'draft' | 'published' | 'archived';
  view_count: number;
  clap_count: number;
  comment_count: number;
  created_at: string;
  updated_at: string;
  published_at?: string;
  tags?: string[];
  preview: string;
  engagement_score: number;
}

const WriterDashboard: React.FC = () => {
  const { user } = useAuth();
  const { toasts, showSuccess, showError, hideToast } = useToast();
  const [stats, setStats] = useState<WriterStats | null>(null);
  const [articles, setArticles] = useState<Article[]>([]);
  const [loading, setLoading] = useState(true);
  const [articlesLoading, setArticlesLoading] = useState(false);
  const [selectedArticles, setSelectedArticles] = useState<number[]>([]);
  const [currentTab, setCurrentTab] = useState<'all' | 'draft' | 'published' | 'archived'>('all');
  const [sortBy, setSortBy] = useState<'updated_at' | 'created_at' | 'view_count' | 'clap_count'>('updated_at');
  const [sortOrder, setSortOrder] = useState<'asc' | 'desc'>('desc');
  const [page, setPage] = useState(1);
  
  // Modal state
  const [confirmModal, setConfirmModal] = useState<{
    isOpen: boolean;
    operation: string;
    articleIds: number[];
    title: string;
    message: string;
  }>({
    isOpen: false,
    operation: '',
    articleIds: [],
    title: '',
    message: ''
  });
  const [isOperationLoading, setIsOperationLoading] = useState(false);

  useEffect(() => {
    fetchWriterStats();
    fetchUserArticles();
  }, []);

  useEffect(() => {
    fetchUserArticles();
  }, [currentTab, sortBy, sortOrder, page]);

  const fetchWriterStats = async () => {
    try {
      const response = await apiService.dashboard.getWriterStats();
      // Ensure all arrays exist with fallbacks
      const data = response.data || {};
      const statsData = {
        ...data,
        recent_activity: (data as any)['recent_activity'] || [],
        top_articles: (data as any)['top_articles'] || [],
        article_counts: (data as any)['article_counts'] || { draft: 0, published: 0, archived: 0 }
      };
      setStats(statsData);
    } catch (error) {
      console.error('Failed to fetch writer stats:', error);
      // Set default empty stats to prevent undefined errors
      setStats({
        article_counts: { draft: 0, published: 0, archived: 0 },
        total_views: 0,
        total_claps: 0,
        total_comments: 0,
        follower_count: 0,
        recent_activity: [],
        top_articles: []
      });
    } finally {
      setLoading(false);
    }
  };

  const fetchUserArticles = async () => {
    setArticlesLoading(true);
    try {
      const response = await apiService.dashboard.getUserArticles({
        status: currentTab === 'all' ? undefined : currentTab,
        sort_by: sortBy,
        sort_order: sortOrder,
        page: page,
        limit: 10
      });
      const data = response.data || {};
      setArticles((data as any)['articles'] || []);
    } catch (error) {
      console.error('Failed to fetch user articles:', error);
    } finally {
      setArticlesLoading(false);
    }
  };

  const handleBulkOperation = (operation: string) => {
    if (selectedArticles.length === 0) return;

    const getOperationDetails = (op: string) => {
      switch (op) {
        case 'delete':
          return {
            title: 'Delete Articles',
            message: `Are you sure you want to delete ${selectedArticles.length} article(s)? This action cannot be undone.`,
            type: 'danger' as const
          };
        case 'archive':
          return {
            title: 'Archive Articles',
            message: `Are you sure you want to archive ${selectedArticles.length} article(s)?`,
            type: 'warning' as const
          };
        case 'publish':
          return {
            title: 'Publish Articles',
            message: `Are you sure you want to publish ${selectedArticles.length} article(s)?`,
            type: 'info' as const
          };
        case 'unpublish':
          return {
            title: 'Unpublish Articles',
            message: `Are you sure you want to unpublish ${selectedArticles.length} article(s)?`,
            type: 'warning' as const
          };
        default:
          return {
            title: 'Confirm Action',
            message: `Are you sure you want to ${operation} ${selectedArticles.length} article(s)?`,
            type: 'info' as const
          };
      }
    };

    const details = getOperationDetails(operation);
    setConfirmModal({
      isOpen: true,
      operation,
      articleIds: [...selectedArticles],
      title: details.title,
      message: details.message
    });
  };

  const executeOperation = async () => {
    if (!confirmModal.operation || confirmModal.articleIds.length === 0) return;

    setIsOperationLoading(true);
    try {
      const response = await apiService.dashboard.bulkOperations({
        article_ids: confirmModal.articleIds,
        operation: confirmModal.operation
      });

      if (response.success) {
        // Refresh articles list
        fetchUserArticles();
        fetchWriterStats();
        setSelectedArticles([]);
        
        // Show success message
        const data = response.data || {};
        const summary = (data as any)['summary'] || {};
        const successCount = (summary as any)['success'] || 0;
        const errorCount = (summary as any)['errors'] || 0;
        
        if (errorCount > 0) {
          showError('Bulk Operation Completed', `${successCount} successful, ${errorCount} failed`);
        } else {
          showSuccess('Operation Successful', `Successfully ${confirmModal.operation}ed ${successCount} article(s)`);
        }
      }
    } catch (error) {
      console.error(`Failed to perform bulk ${confirmModal.operation}:`, error);
      showError('Operation Failed', `Failed to perform bulk ${confirmModal.operation}`);
    } finally {
      setIsOperationLoading(false);
      setConfirmModal({ isOpen: false, operation: '', articleIds: [], title: '', message: '' });
    }
  };

  const toggleArticleSelection = (articleId: number) => {
    setSelectedArticles(prev => 
      prev.includes(articleId) 
        ? prev.filter(id => id !== articleId)
        : [...prev, articleId]
    );
  };

  const selectAllArticles = () => {
    if (selectedArticles.length === articles.length) {
      setSelectedArticles([]);
    } else {
      setSelectedArticles(articles.map(article => article.id));
    }
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'published': return 'text-green-600 bg-green-100';
      case 'draft': return 'text-yellow-600 bg-yellow-100';
      case 'archived': return 'text-gray-600 bg-gray-100';
      default: return 'text-gray-600 bg-gray-100';
    }
  };

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
          <div className="flex justify-between items-center">
            <div>
              <h1 className="text-3xl font-bold text-gray-900">Writer Dashboard</h1>
              <p className="text-gray-600 mt-2">
                Welcome back, {user?.username}! Here's your writing overview.
              </p>
            </div>
            <button
              onClick={() => window.location.href = '/editor'}
              className="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <PlusIcon className="h-4 w-4 mr-2" />
              New Article
            </button>
          </div>
        </div>

        {/* Stats Overview */}
        {stats && (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div className="bg-white overflow-hidden shadow rounded-lg">
              <div className="p-5">
                <div className="flex items-center">
                  <div className="flex-shrink-0">
                    <DocumentTextIcon className="h-6 w-6 text-gray-400" />
                  </div>
                  <div className="ml-5 w-0 flex-1">
                    <dl>
                      <dt className="text-sm font-medium text-gray-500 truncate">
                        Total Articles
                      </dt>
                      <dd className="text-lg font-medium text-gray-900">
                        {(stats.article_counts?.published || 0) + (stats.article_counts?.draft || 0) + (stats.article_counts?.archived || 0)}
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
                    <EyeIcon className="h-6 w-6 text-gray-400" />
                  </div>
                  <div className="ml-5 w-0 flex-1">
                    <dl>
                      <dt className="text-sm font-medium text-gray-500 truncate">
                        Total Views
                      </dt>
                      <dd className="text-lg font-medium text-gray-900">
                        {(stats.total_views || 0).toLocaleString()}
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
                    <HeartIcon className="h-6 w-6 text-gray-400" />
                  </div>
                  <div className="ml-5 w-0 flex-1">
                    <dl>
                      <dt className="text-sm font-medium text-gray-500 truncate">
                        Total Claps
                      </dt>
                      <dd className="text-lg font-medium text-gray-900">
                        {(stats.total_claps || 0).toLocaleString()}
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
                        Followers
                      </dt>
                      <dd className="text-lg font-medium text-gray-900">
                        {(stats.follower_count || 0).toLocaleString()}
                      </dd>
                    </dl>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Main Content - Articles Management */}
          <div className="lg:col-span-2">
            <div className="bg-white shadow rounded-lg">
              <div className="px-6 py-4 border-b border-gray-200">
                <div className="flex justify-between items-center">
                  <h2 className="text-lg font-medium text-gray-900">Your Articles</h2>
                  
                  {/* Bulk Actions */}
                  {selectedArticles.length > 0 && (
                    <div className="flex flex-wrap gap-2 items-center">
                      <span className="text-sm text-gray-600 font-medium">
                        {selectedArticles.length} article{selectedArticles.length > 1 ? 's' : ''} selected:
                      </span>
                      <button
                        onClick={() => handleBulkOperation('publish')}
                        className="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors"
                      >
                        <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Publish
                      </button>
                      <button
                        onClick={() => handleBulkOperation('archive')}
                        className="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-colors"
                      >
                        <ArchiveBoxIcon className="h-4 w-4 mr-1" />
                        Archive
                      </button>
                      <button
                        onClick={() => handleBulkOperation('delete')}
                        className="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors"
                      >
                        <TrashIcon className="h-4 w-4 mr-1" />
                        Delete
                      </button>
                    </div>
                  )}
                </div>

                {/* Filters and Sorting */}
                <div className="mt-4 flex flex-wrap gap-4">
                  <div className="flex space-x-1">
                    {(['all', 'draft', 'published', 'archived'] as const).map((tab) => (
                      <button
                        key={tab}
                        onClick={() => setCurrentTab(tab)}
                        className={`px-3 py-1 text-sm font-medium rounded-md ${
                          currentTab === tab
                            ? 'bg-indigo-100 text-indigo-700'
                            : 'text-gray-500 hover:text-gray-700'
                        }`}
                      >
                        {tab.charAt(0).toUpperCase() + tab.slice(1)}
                        {stats && stats.article_counts && tab !== 'all' && (
                          <span className="ml-1 text-xs">
                            ({stats.article_counts[tab as keyof typeof stats.article_counts] || 0})
                          </span>
                        )}
                      </button>
                    ))}
                  </div>

                  <select
                    value={sortBy}
                    onChange={(e) => setSortBy(e.target.value as any)}
                    className="text-sm border-gray-300 rounded-md"
                  >
                    <option value="updated_at">Last Updated</option>
                    <option value="created_at">Created Date</option>
                    <option value="view_count">Views</option>
                    <option value="clap_count">Claps</option>
                  </select>

                  <select
                    value={sortOrder}
                    onChange={(e) => setSortOrder(e.target.value as any)}
                    className="text-sm border-gray-300 rounded-md"
                  >
                    <option value="desc">Descending</option>
                    <option value="asc">Ascending</option>
                  </select>
                </div>
              </div>

              {/* Articles List */}
              <div className="divide-y divide-gray-200">
                {articlesLoading ? (
                  <div className="p-6 text-center">
                    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mx-auto"></div>
                  </div>
                ) : articles.length === 0 ? (
                  <div className="p-6 text-center text-gray-500">
                    No articles found. Start writing your first article!
                  </div>
                ) : (
                  <>
                    {/* Select All Header */}
                    <div className="px-6 py-3 bg-gray-50">
                      <label className="flex items-center">
                        <input
                          type="checkbox"
                          checked={selectedArticles.length === articles.length && articles.length > 0}
                          onChange={selectAllArticles}
                          className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                        />
                        <span className="ml-2 text-sm text-gray-600">
                          Select all ({articles.length})
                        </span>
                      </label>
                    </div>

                    {articles.map((article) => (
                      <div key={article.id} className="px-6 py-4 hover:bg-gray-50">
                        <div className="flex items-start space-x-3">
                          <input
                            type="checkbox"
                            checked={selectedArticles.includes(article.id)}
                            onChange={() => toggleArticleSelection(article.id)}
                            className="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                          />
                          
                          <div className="flex-1 min-w-0">
                            <div className="flex items-center justify-between">
                              <h3 className="text-sm font-medium text-gray-900 truncate">
                                {article.title}
                              </h3>
                              <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(article.status)}`}>
                                {article.status}
                              </span>
                            </div>
                            
                            {article.subtitle && (
                              <p className="text-sm text-gray-600 mt-1">{article.subtitle}</p>
                            )}
                            
                            <p className="text-sm text-gray-500 mt-1 line-clamp-2">
                              {article.preview}
                            </p>
                            
                            <div className="flex items-center justify-between mt-3">
                              <div className="flex items-center space-x-4 text-sm text-gray-500">
                                <span className="flex items-center">
                                  <EyeIcon className="h-4 w-4 mr-1" />
                                  {article.view_count}
                                </span>
                                <span className="flex items-center">
                                  <HeartIcon className="h-4 w-4 mr-1" />
                                  {article.clap_count}
                                </span>
                                <span className="flex items-center">
                                  <ChatBubbleLeftIcon className="h-4 w-4 mr-1" />
                                  {article.comment_count}
                                </span>
                              </div>
                              
                              <div className="flex items-center space-x-2">
                                {/* Edit Button */}
                                <a
                                  href={`/editor?id=${article.id}`}
                                  className="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 hover:border-indigo-300 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200"
                                  title="Edit article"
                                >
                                  <svg className="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                  </svg>
                                  Edit
                                </a>
                                
                                {/* View Button (for published articles) */}
                                {article.status === 'published' && (
                                  <a
                                    href={`/article/${article.id}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 hover:border-blue-300 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200"
                                    title="View published article"
                                  >
                                    <svg className="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    View
                                  </a>
                                )}
                                
                                {/* Preview Button (for drafts) */}
                                {article.status === 'draft' && (
                                  <a
                                    href={`/article/${article.id}?preview=true`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 hover:border-green-300 hover:text-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200"
                                    title="Preview draft article"
                                  >
                                    <svg className="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Preview
                                  </a>
                                )}
                              </div>
                            </div>
                            
                            <div className="flex items-center justify-between mt-2">
                              <div className="text-sm text-gray-500">
                                {article.status === 'published' && article.published_at
                                  ? `Published ${formatDate(article.published_at)}`
                                  : `Updated ${formatDate(article.updated_at)}`
                                }
                              </div>
                            </div>
                            
                            {article.tags && article.tags.length > 0 && (
                              <div className="flex flex-wrap gap-1 mt-2">
                                {article.tags.slice(0, 3).map((tag, index) => (
                                  <span
                                    key={index}
                                    className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800"
                                  >
                                    {tag}
                                  </span>
                                ))}
                                {article.tags.length > 3 && (
                                  <span className="text-xs text-gray-500">
                                    +{article.tags.length - 3} more
                                  </span>
                                )}
                              </div>
                            )}
                          </div>
                        </div>
                      </div>
                    ))}
                  </>
                )}
              </div>
            </div>
          </div>

          {/* Sidebar */}
          <div className="space-y-6">
            {/* Top Articles */}
            {stats && stats.top_articles && stats.top_articles.length > 0 && (
              <div className="bg-white shadow rounded-lg">
                <div className="px-6 py-4 border-b border-gray-200">
                  <h3 className="text-lg font-medium text-gray-900">Top Performing Articles</h3>
                </div>
                <div className="divide-y divide-gray-200">
                  {stats.top_articles.map((article, index) => (
                    <div key={article.id} className="px-6 py-4">
                      <div className="flex items-start justify-between">
                        <div className="flex-1 min-w-0">
                          <h4 className="text-sm font-medium text-gray-900 truncate">
                            {article.title}
                          </h4>
                          <div className="flex items-center space-x-3 mt-2 text-xs text-gray-500">
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
                        </div>
                        <span className="text-lg font-bold text-gray-400">
                          #{index + 1}
                        </span>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Recent Activity */}
            {stats && stats.recent_activity && stats.recent_activity.length > 0 && (
              <div className="bg-white shadow rounded-lg">
                <div className="px-6 py-4 border-b border-gray-200">
                  <h3 className="text-lg font-medium text-gray-900">Recent Activity</h3>
                </div>
                <div className="divide-y divide-gray-200">
                  {stats.recent_activity.slice(0, 5).map((activity, index) => (
                    <div key={index} className="px-6 py-4">
                      <div className="flex items-start space-x-3">
                        <div className="flex-shrink-0">
                          {activity.type === 'comment' && <ChatBubbleLeftIcon className="h-5 w-5 text-blue-500" />}
                          {activity.type === 'clap' && <HeartIcon className="h-5 w-5 text-red-500" />}
                          {activity.type === 'follow' && <UserGroupIcon className="h-5 w-5 text-green-500" />}
                        </div>
                        <div className="flex-1 min-w-0">
                          <p className="text-sm text-gray-900">
                            {activity.type === 'comment' && (
                              <>
                                <span className="font-medium">{activity.data.commenter_username}</span>
                                {' '}commented on{' '}
                                <span className="font-medium">{activity.data.article_title}</span>
                              </>
                            )}
                            {activity.type === 'clap' && (
                              <>
                                <span className="font-medium">{activity.data.clapper_username}</span>
                                {' '}clapped for{' '}
                                <span className="font-medium">{activity.data.article_title}</span>
                              </>
                            )}
                            {activity.type === 'follow' && (
                              <>
                                <span className="font-medium">{activity.data.follower_username}</span>
                                {' '}started following you
                              </>
                            )}
                          </p>
                          <p className="text-xs text-gray-500 mt-1">
                            {formatDate(activity.timestamp)}
                          </p>
                        </div>
                      </div>
                    </div>
                  ))}
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
                  onClick={() => window.location.href = '/editor'}
                  className="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                >
                  <PencilIcon className="h-4 w-4 mr-2" />
                  Write New Article
                </button>
                
                <button
                  onClick={() => window.location.href = '/dashboard/analytics'}
                  className="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                >
                  <ChartBarIcon className="h-4 w-4 mr-2" />
                  View Analytics
                </button>
                
                <button
                  onClick={() => window.location.href = '/dashboard/advanced-analytics'}
                  className="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                >
                  <ChartBarIcon className="h-4 w-4 mr-2" />
                  Advanced Analytics
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Confirmation Modal */}
      <ConfirmationModal
        isOpen={confirmModal.isOpen}
        onClose={() => setConfirmModal({ isOpen: false, operation: '', articleIds: [], title: '', message: '' })}
        onConfirm={executeOperation}
        title={confirmModal.title}
        message={confirmModal.message}
        confirmText={confirmModal.operation === 'delete' ? 'Delete' : 'Confirm'}
        type={confirmModal.operation === 'delete' ? 'danger' : 'warning'}
        isLoading={isOperationLoading}
      />

      {/* Toast Notifications */}
      {toasts.map((toast) => (
        <Toast
          key={toast.id}
          message={toast.message}
          type={toast.type}
          isVisible={toast.isVisible}
          onClose={() => hideToast(toast.id)}
        />
      ))}
    </div>
  );
};

export default WriterDashboard;