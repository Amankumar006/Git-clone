import React, { useState, useEffect } from 'react';
import { Publication, Article } from '../types';
import { apiService } from '../utils/api';

interface PublicationDashboardProps {
  publication: Publication & {
    stats?: {
      member_count: number;
      published_articles: number;
      draft_articles: number;
      total_views: number;
      total_claps: number;
      total_comments: number;
    };
  };
}

const PublicationDashboard: React.FC<PublicationDashboardProps> = ({ publication }) => {
  const [articles, setArticles] = useState<Article[]>([]);
  const [pendingArticles, setPendingArticles] = useState<Article[]>([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState<'overview' | 'published' | 'drafts' | 'pending' | 'branding'>('overview');

  useEffect(() => {
    loadArticles();
    loadPendingArticles();
  }, [publication.id]);

  const loadArticles = async () => {
    try {
      const response = await apiService.get(`/publications/articles?id=${publication.id}&status=${activeTab === 'published' ? 'published' : 'draft'}`);
      if (response.success) {
        setArticles(response.data as Article[]);
      }
    } catch (error) {
      console.error('Failed to load articles:', error);
    }
  };

  const loadPendingArticles = async () => {
    try {
      const response = await apiService.get(`/articles/pending-approval?publication_id=${publication.id}`);
      if (response.success) {
        setPendingArticles(response.data as Article[]);
      }
    } catch (error) {
      console.error('Failed to load pending articles:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleApproveArticle = async (articleId: number) => {
    try {
      const response = await apiService.post('/articles/approve', { article_id: articleId });
      if (response.success) {
        // Refresh both lists
        loadArticles();
        loadPendingArticles();
      }
    } catch (error) {
      console.error('Failed to approve article:', error);
    }
  };

  const handleRejectArticle = async (articleId: number) => {
    try {
      const response = await apiService.post('/articles/reject', { article_id: articleId });
      if (response.success) {
        loadPendingArticles();
      }
    } catch (error) {
      console.error('Failed to reject article:', error);
    }
  };

  const stats = publication.stats;

  if (loading) {
    return (
      <div className="flex justify-center items-center py-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Stats Overview */}
      {stats && (
        <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-gray-900">{stats.member_count}</div>
            <div className="text-sm text-gray-500">Members</div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-gray-900">{stats.published_articles}</div>
            <div className="text-sm text-gray-500">Published</div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-gray-900">{stats.draft_articles}</div>
            <div className="text-sm text-gray-500">Drafts</div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-gray-900">{stats.total_views.toLocaleString()}</div>
            <div className="text-sm text-gray-500">Total Views</div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-gray-900">{stats.total_claps.toLocaleString()}</div>
            <div className="text-sm text-gray-500">Total Claps</div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-gray-900">{stats.total_comments.toLocaleString()}</div>
            <div className="text-sm text-gray-500">Total Comments</div>
          </div>
        </div>
      )}

      {/* Pending Approval Section */}
      {pendingArticles.length > 0 && (
        <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
          <h3 className="text-lg font-medium text-yellow-800 mb-4">
            Pending Approval ({pendingArticles.length})
          </h3>
          <div className="space-y-3">
            {pendingArticles.map((article) => (
              <div key={article.id} className="bg-white p-4 rounded-lg border border-yellow-200">
                <div className="flex items-start justify-between">
                  <div className="flex-1">
                    <h4 className="font-medium text-gray-900">{article.title}</h4>
                    <p className="text-sm text-gray-500 mt-1">
                      by {article.username} • {new Date(article.created_at).toLocaleDateString()}
                    </p>
                    {article.subtitle && (
                      <p className="text-sm text-gray-600 mt-2">{article.subtitle}</p>
                    )}
                  </div>
                  <div className="flex space-x-2 ml-4">
                    <button
                      onClick={() => handleApproveArticle(article.id)}
                      className="px-3 py-1 text-sm font-medium text-green-700 bg-green-100 rounded-md hover:bg-green-200"
                    >
                      Approve
                    </button>
                    <button
                      onClick={() => handleRejectArticle(article.id)}
                      className="px-3 py-1 text-sm font-medium text-red-700 bg-red-100 rounded-md hover:bg-red-200"
                    >
                      Reject
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Dashboard Tabs */}
      <div className="bg-white rounded-lg shadow">
        <div className="border-b border-gray-200">
          <nav className="-mb-px flex space-x-8 px-6">
            <button
              onClick={() => setActiveTab('overview')}
              className={`py-4 px-1 border-b-2 font-medium text-sm ${
                activeTab === 'overview'
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              Overview
            </button>
            <button
              onClick={() => setActiveTab('published')}
              className={`py-4 px-1 border-b-2 font-medium text-sm ${
                activeTab === 'published'
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              Published Articles
            </button>
            <button
              onClick={() => setActiveTab('drafts')}
              className={`py-4 px-1 border-b-2 font-medium text-sm ${
                activeTab === 'drafts'
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              Draft Articles
            </button>
            <button
              onClick={() => setActiveTab('branding')}
              className={`py-4 px-1 border-b-2 font-medium text-sm ${
                activeTab === 'branding'
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              Branding Preview
            </button>
          </nav>
        </div>

        <div className="p-6">
          {activeTab === 'overview' && (
            <div className="space-y-6">
              {/* Quick Actions */}
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div className="bg-blue-50 p-4 rounded-lg">
                  <h4 className="font-medium text-blue-900 mb-2">Quick Actions</h4>
                  <div className="space-y-2">
                    <button className="block w-full text-left text-sm text-blue-700 hover:text-blue-900">
                      Invite new members
                    </button>
                    <button className="block w-full text-left text-sm text-blue-700 hover:text-blue-900">
                      Review pending articles
                    </button>
                    <button className="block w-full text-left text-sm text-blue-700 hover:text-blue-900">
                      Update branding
                    </button>
                  </div>
                </div>
                <div className="bg-green-50 p-4 rounded-lg">
                  <h4 className="font-medium text-green-900 mb-2">Recent Activity</h4>
                  <div className="space-y-2 text-sm text-green-700">
                    <p>3 new articles this week</p>
                    <p>2 new members joined</p>
                    <p>156 total views today</p>
                  </div>
                </div>
                <div className="bg-purple-50 p-4 rounded-lg">
                  <h4 className="font-medium text-purple-900 mb-2">Growth</h4>
                  <div className="space-y-2 text-sm text-purple-700">
                    <p>+12% followers this month</p>
                    <p>+8% engagement rate</p>
                    <p>Top performing tag: #tech</p>
                  </div>
                </div>
              </div>

              {/* Recent Articles */}
              <div>
                <h4 className="font-medium text-gray-900 mb-4">Recent Articles</h4>
                {articles.slice(0, 5).map((article) => (
                  <div key={article.id} className="border border-gray-200 rounded-lg p-4 mb-3">
                    <div className="flex items-start justify-between">
                      <div className="flex-1">
                        <h5 className="font-medium text-gray-900">{article.title}</h5>
                        <p className="text-sm text-gray-500 mt-1">
                          by {article.username} • {new Date(article.created_at).toLocaleDateString()}
                        </p>
                        <div className="flex items-center space-x-4 mt-2 text-sm text-gray-500">
                          <span>{article.view_count} views</span>
                          <span>{article.clap_count} claps</span>
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {activeTab === 'branding' && (
            <div className="space-y-6">
              <div className="bg-gray-50 p-6 rounded-lg">
                <h4 className="font-medium text-gray-900 mb-4">Branding Preview</h4>
                
                {/* Publication Header Preview */}
                <div 
                  className="bg-white rounded-lg p-6 border-2 mb-6"
                  style={{ borderColor: publication.theme_color || '#3B82F6' }}
                >
                  <div className="flex items-center space-x-4">
                    {publication.logo_url ? (
                      <img
                        src={publication.logo_url}
                        alt={publication.name}
                        className="w-16 h-16 rounded-lg object-cover"
                      />
                    ) : (
                      <div 
                        className="w-16 h-16 rounded-lg flex items-center justify-center text-white font-bold text-2xl"
                        style={{ backgroundColor: publication.theme_color || '#3B82F6' }}
                      >
                        {publication.name.charAt(0).toUpperCase()}
                      </div>
                    )}
                    <div>
                      <h3 className="text-xl font-bold text-gray-900">{publication.name}</h3>
                      <p className="text-gray-600">{publication.description}</p>
                      {publication.website_url && (
                        <a 
                          href={publication.website_url}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="text-sm hover:underline"
                          style={{ color: publication.theme_color || '#3B82F6' }}
                        >
                          {publication.website_url}
                        </a>
                      )}
                    </div>
                  </div>

                  {/* Social Links Preview */}
                  {publication.social_links && Object.values(publication.social_links).some(link => link) && (
                    <div className="mt-4 pt-4 border-t border-gray-200">
                      <p className="text-sm font-medium text-gray-700 mb-2">Follow us:</p>
                      <div className="flex space-x-3">
                        {publication.social_links.twitter && (
                          <a 
                            href={publication.social_links.twitter}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-sm px-3 py-1 rounded-full border hover:bg-gray-50"
                            style={{ borderColor: publication.theme_color, color: publication.theme_color }}
                          >
                            Twitter
                          </a>
                        )}
                        {publication.social_links.facebook && (
                          <a 
                            href={publication.social_links.facebook}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-sm px-3 py-1 rounded-full border hover:bg-gray-50"
                            style={{ borderColor: publication.theme_color, color: publication.theme_color }}
                          >
                            Facebook
                          </a>
                        )}
                        {publication.social_links.linkedin && (
                          <a 
                            href={publication.social_links.linkedin}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-sm px-3 py-1 rounded-full border hover:bg-gray-50"
                            style={{ borderColor: publication.theme_color, color: publication.theme_color }}
                          >
                            LinkedIn
                          </a>
                        )}
                        {publication.social_links.instagram && (
                          <a 
                            href={publication.social_links.instagram}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-sm px-3 py-1 rounded-full border hover:bg-gray-50"
                            style={{ borderColor: publication.theme_color, color: publication.theme_color }}
                          >
                            Instagram
                          </a>
                        )}
                      </div>
                    </div>
                  )}
                </div>

                {/* Theme Color Info */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <h5 className="font-medium text-gray-900 mb-2">Theme Color</h5>
                    <div className="flex items-center space-x-3">
                      <div 
                        className="w-8 h-8 rounded border border-gray-300"
                        style={{ backgroundColor: publication.theme_color || '#3B82F6' }}
                      ></div>
                      <span className="text-sm text-gray-600">{publication.theme_color || '#3B82F6'}</span>
                    </div>
                  </div>
                  <div>
                    <h5 className="font-medium text-gray-900 mb-2">Custom CSS</h5>
                    <p className="text-sm text-gray-600">
                      {publication.custom_css ? 'Custom styles applied' : 'No custom CSS'}
                    </p>
                  </div>
                </div>

                {/* Custom CSS Preview */}
                {publication.custom_css && (
                  <div className="mt-4">
                    <h5 className="font-medium text-gray-900 mb-2">Custom CSS</h5>
                    <pre className="bg-gray-100 p-3 rounded text-sm overflow-x-auto">
                      {publication.custom_css}
                    </pre>
                  </div>
                )}
              </div>
            </div>
          )}

          {(activeTab === 'published' || activeTab === 'drafts') && (
            <>
              {articles.length > 0 ? (
                <div className="space-y-4">
                  {articles.map((article) => (
                    <div key={article.id} className="border border-gray-200 rounded-lg p-4">
                      <div className="flex items-start justify-between">
                        <div className="flex-1">
                          <h4 className="font-medium text-gray-900">{article.title}</h4>
                          <p className="text-sm text-gray-500 mt-1">
                            by {article.username} • {new Date(article.created_at).toLocaleDateString()}
                          </p>
                          {article.subtitle && (
                            <p className="text-sm text-gray-600 mt-2">{article.subtitle}</p>
                          )}
                          <div className="flex items-center space-x-4 mt-2 text-sm text-gray-500">
                            <span>{article.view_count} views</span>
                            <span>{article.clap_count} claps</span>
                            <span>{article.comment_count} comments</span>
                          </div>
                        </div>
                        <div className="ml-4">
                          <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                            article.status === 'published' 
                              ? 'bg-green-100 text-green-800' 
                              : 'bg-yellow-100 text-yellow-800'
                          }`}>
                            {article.status}
                          </span>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-center py-8 text-gray-500">
                  No {activeTab} articles yet.
                </div>
              )}
            </>
          )}
        </div>
      </div>
    </div>
  );
};

export default PublicationDashboard;