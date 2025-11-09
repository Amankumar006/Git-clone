import React, { useState, useEffect } from 'react';

interface Article {
  id: number;
  title: string;

  status: string;
  moderation_status: string;
  username: string;
  email: string;
  created_at: string;
  clap_count: number;
  comment_count: number;
  bookmark_count: number;
}

interface Comment {
  id: number;
  content: string;
  moderation_status: string;
  username: string;
  email: string;
  article_title: string;
  created_at: string;
}

interface FeaturedContent {
  id: number;
  content_type: string;
  content_id: number;
  content_title: string;
  author_name: string;
  position: number;
  featured_at: string;
  expires_at: string | null;
}

interface ContentActionModalProps {
  isOpen: boolean;
  onClose: () => void;
  onAction: (action: string, reason: string) => void;
  contentType: string;
  contentTitle: string;
}

const ContentActionModal: React.FC<ContentActionModalProps> = ({
  isOpen,
  onClose,
  onAction,
  contentType,
  contentTitle
}) => {
  const [action, setAction] = useState('');
  const [reason, setReason] = useState('');

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!action || !reason.trim()) {
      alert('Please select an action and provide a reason');
      return;
    }
    onAction(action, reason);
    setAction('');
    setReason('');
    onClose();
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <div className="flex justify-between items-center mb-4">
          <h2 className="text-xl font-semibold">Manage {contentType}</h2>
          <button onClick={onClose} className="text-gray-500 hover:text-gray-700">
            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div className="mb-4 p-3 bg-gray-50 rounded">
          <p className="text-sm text-gray-600 font-medium">{contentTitle}</p>
        </div>

        <form onSubmit={handleSubmit}>
          <div className="mb-4">
            <label className="block text-sm font-medium text-gray-700 mb-2">Action</label>
            <select
              value={action}
              onChange={(e) => setAction(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              required
            >
              <option value="">Select an action</option>
              <option value="approve">Approve</option>
              <option value="reject">Reject</option>
              <option value="delete">Delete</option>
              {contentType === 'article' && <option value="feature">Feature</option>}
            </select>
          </div>

          <div className="mb-4">
            <label className="block text-sm font-medium text-gray-700 mb-2">Reason</label>
            <textarea
              value={reason}
              onChange={(e) => setReason(e.target.value)}
              placeholder="Provide a reason for this action..."
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              rows={3}
              required
            />
          </div>

          <div className="flex justify-end space-x-3">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50"
            >
              Cancel
            </button>
            <button
              type="submit"
              className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
            >
              Execute Action
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

const ContentManagement: React.FC = () => {
  const [activeTab, setActiveTab] = useState('articles');
  const [articles, setArticles] = useState<Article[]>([]);
  const [comments, setComments] = useState<Comment[]>([]);
  const [featuredContent, setFeaturedContent] = useState<FeaturedContent[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [selectedContent, setSelectedContent] = useState<any>(null);
  const [showActionModal, setShowActionModal] = useState(false);

  useEffect(() => {
    fetchContent();
  }, [activeTab, search, statusFilter]);

  const fetchContent = async () => {
    try {
      const params = new URLSearchParams({
        type: activeTab,
        limit: '50'
      });

      if (search) params.append('search', search);
      if (statusFilter) params.append('status', statusFilter);

      const token = localStorage.getItem('token');
      const response = await fetch(`/api/admin/content?${params}`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.error || 'Failed to fetch content');
      }

      if (data.articles) setArticles(data.articles);
      if (data.comments) setComments(data.comments);
      if (data.featured) setFeaturedContent(data.featured);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to fetch content');
    } finally {
      setLoading(false);
    }
  };

  const handleContentAction = async (action: string, reason: string) => {
    if (!selectedContent) return;

    try {
      const token = localStorage.getItem('token');
      const response = await fetch('/api/admin/content/action', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
          content_type: activeTab === 'articles' ? 'article' : 'comment',
          content_id: selectedContent.id,
          action,
          reason
        })
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.error || 'Failed to execute action');
      }

      await fetchContent();
      alert('Action executed successfully');
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Failed to execute action');
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'published': return 'bg-green-100 text-green-800';
      case 'draft': return 'bg-yellow-100 text-yellow-800';
      case 'archived': return 'bg-gray-100 text-gray-800';
      case 'approved': return 'bg-green-100 text-green-800';
      case 'rejected': return 'bg-red-100 text-red-800';
      case 'flagged': return 'bg-orange-100 text-orange-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const tabs = [
    { id: 'articles', label: 'Articles', count: articles.length },
    { id: 'comments', label: 'Comments', count: comments.length },
    { id: 'featured', label: 'Featured Content', count: featuredContent.length }
  ];

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold text-gray-900">Content Management</h1>
      </div>

      {/* Tabs */}
      <div className="border-b border-gray-200">
        <nav className="-mb-px flex space-x-8">
          {tabs.map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              className={`py-2 px-1 border-b-2 font-medium text-sm ${
                activeTab === tab.id
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              {tab.label} ({tab.count})
            </button>
          ))}
        </nav>
      </div>

      {/* Filters */}
      {activeTab !== 'featured' && (
        <div className="bg-white p-4 rounded-lg shadow">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Search</label>
              <input
                type="text"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                placeholder="Search content..."
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">All Status</option>
                {activeTab === 'articles' ? (
                  <>
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                    <option value="archived">Archived</option>
                  </>
                ) : (
                  <>
                    <option value="approved">Approved</option>
                    <option value="flagged">Flagged</option>
                    <option value="rejected">Rejected</option>
                  </>
                )}
              </select>
            </div>
            <div className="flex items-end">
              <button
                onClick={fetchContent}
                className="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
              >
                Search
              </button>
            </div>
          </div>
        </div>
      )}

      {error && (
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
          {error}
        </div>
      )}

      {/* Content Tables */}
      {activeTab === 'articles' && (
        <div className="bg-white shadow rounded-lg overflow-hidden">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Article
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Author
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Status
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Engagement
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Created
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {articles.map((article) => (
                <tr key={article.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4">
                    <div>
                      <div className="text-sm font-medium text-gray-900 truncate max-w-xs">
                        {article.title}
                      </div>

                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm text-gray-900">{article.username}</div>
                    <div className="text-sm text-gray-500">{article.email}</div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span className={`px-2 py-1 text-xs font-medium rounded-full ${getStatusColor(article.status)}`}>
                      {article.status}
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <div>{article.clap_count} claps</div>
                    <div>{article.comment_count} comments</div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {new Date(article.created_at).toLocaleDateString()}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button
                      onClick={() => {
                        setSelectedContent(article);
                        setShowActionModal(true);
                      }}
                      className="text-blue-600 hover:text-blue-900"
                    >
                      Manage
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {activeTab === 'comments' && (
        <div className="bg-white shadow rounded-lg overflow-hidden">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Comment
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Author
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Article
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Status
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Created
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {comments.map((comment) => (
                <tr key={comment.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4">
                    <div className="text-sm text-gray-900 truncate max-w-xs">
                      {comment.content}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm text-gray-900">{comment.username}</div>
                    <div className="text-sm text-gray-500">{comment.email}</div>
                  </td>
                  <td className="px-6 py-4">
                    <div className="text-sm text-gray-900 truncate max-w-xs">
                      {comment.article_title}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span className={`px-2 py-1 text-xs font-medium rounded-full ${getStatusColor(comment.moderation_status || 'pending')}`}>
                      {comment.moderation_status || 'pending'}
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {new Date(comment.created_at).toLocaleDateString()}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button
                      onClick={() => {
                        setSelectedContent(comment);
                        setShowActionModal(true);
                      }}
                      className="text-blue-600 hover:text-blue-900"
                    >
                      Manage
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {activeTab === 'featured' && (
        <div className="bg-white shadow rounded-lg overflow-hidden">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Content
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Type
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Author
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Position
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Featured
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {featuredContent.map((content) => (
                <tr key={content.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4">
                    <div className="text-sm font-medium text-gray-900 truncate max-w-xs">
                      {content.content_title}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <span className="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                      {content.content_type}
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {content.author_name || '-'}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {content.position}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {new Date(content.featured_at).toLocaleDateString()}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button
                      onClick={async () => {
                        if (confirm('Remove from featured content?')) {
                          try {
                            const token = localStorage.getItem('token');
                            await fetch('/api/admin/featured', {
                              method: 'POST',
                              headers: {
                                'Content-Type': 'application/json',
                                'Authorization': `Bearer ${token}`
                              },
                              body: JSON.stringify({
                                content_id: content.content_id,
                                content_type: content.content_type,
                                action: 'unfeature'
                              })
                            });
                            fetchContent();
                          } catch (err) {
                            alert('Failed to remove from featured content');
                          }
                        }
                      }}
                      className="text-red-600 hover:text-red-900"
                    >
                      Remove
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Content Action Modal */}
      <ContentActionModal
        isOpen={showActionModal}
        onClose={() => {
          setShowActionModal(false);
          setSelectedContent(null);
        }}
        onAction={handleContentAction}
        contentType={activeTab === 'articles' ? 'article' : 'comment'}
        contentTitle={selectedContent?.title || selectedContent?.content || ''}
      />
    </div>
  );
};

export default ContentManagement;