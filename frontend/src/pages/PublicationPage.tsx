import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { Publication, Article, PublicationMember, User } from '../types';
import { apiService } from '../utils/api';
import { useAuth } from '../context/AuthContext';
import ArticleCard from '../components/ArticleCard';

const PublicationPage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const { user } = useAuth();
  const [publication, setPublication] = useState<Publication & {
    stats?: any;
    members?: (PublicationMember & { user: User })[];
    user_role?: string;
    can_manage?: boolean;
    is_following?: boolean;
    followers_count?: number;
  } | null>(null);
  const [articles, setArticles] = useState<Article[]>([]);
  const [loading, setLoading] = useState(true);
  const [articlesLoading, setArticlesLoading] = useState(false);
  const [activeTab, setActiveTab] = useState<'articles' | 'about' | 'members'>('articles');
  const [isFollowing, setIsFollowing] = useState(false);
  const [followLoading, setFollowLoading] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [sortBy, setSortBy] = useState('newest');
  const [selectedAuthor, setSelectedAuthor] = useState('');
  const [selectedTag, setSelectedTag] = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');

  useEffect(() => {
    if (id) {
      loadPublication();
      loadArticles();
    }
  }, [id]);

  useEffect(() => {
    if (id) {
      loadArticles();
    }
  }, [searchQuery, sortBy, selectedAuthor, selectedTag, dateFrom, dateTo]);

  const loadPublication = async () => {
    try {
      const response = await apiService.publications.getById(id!);
      if (response.data.success) {
        const pubData = response.data.data;
        setPublication(pubData);
        setIsFollowing(pubData.is_following || false);
      }
    } catch (error) {
      console.error('Failed to load publication:', error);
    } finally {
      setLoading(false);
    }
  };

  const loadArticles = async () => {
    setArticlesLoading(true);
    try {
      // Build query parameters for filtering
      const filters = {
        page: 1,
        limit: 20,
        search: searchQuery || undefined,
        sort: sortBy !== 'newest' ? sortBy : undefined,
        author: selectedAuthor || undefined,
        tag: selectedTag || undefined,
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined
      };

      const response = await apiService.publications.getFilteredArticles(id!, filters);
      if (response.data.success) {
        setArticles(response.data.data);
      }
    } catch (error) {
      console.error('Failed to load articles:', error);
    } finally {
      setArticlesLoading(false);
    }
  };

  const handleFollow = async () => {
    if (!user) {
      // Redirect to login or show login modal
      return;
    }

    setFollowLoading(true);
    try {
      const response = isFollowing 
        ? await apiService.publications.unfollow(id!)
        : await apiService.publications.follow(id!);

      if (response.data.success) {
        setIsFollowing(!isFollowing);
        // Update followers count in publication state
        if (publication) {
          setPublication({
            ...publication,
            followers_count: response.data.data.followers_count
          });
        }
      }
    } catch (error) {
      console.error('Failed to follow/unfollow publication:', error);
    } finally {
      setFollowLoading(false);
    }
  };

  const clearFilters = () => {
    setSearchQuery('');
    setSortBy('newest');
    setSelectedAuthor('');
    setSelectedTag('');
    setDateFrom('');
    setDateTo('');
  };

  const getUniqueAuthors = () => {
    const authors = articles.map(article => ({
      id: article.author_id,
      username: article.author_username
    }));
    return authors.filter((author, index, self) =>
      index === self.findIndex(a => a.id === author.id)
    );
  };

  const getUniqueTags = () => {
    const allTags = articles.flatMap(article => {
      if (!article.tags) return [];

      if (typeof article.tags === 'string') {
        return article.tags.split(',').map((tag: string) => tag.trim());
      } else if (Array.isArray(article.tags)) {
        return article.tags.map(tag => typeof tag === 'string' ? tag : tag.name);
      }

      return [];
    });
    return Array.from(new Set(allTags)).filter(Boolean);
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex justify-center items-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (!publication) {
    return (
      <div className="min-h-screen bg-gray-50 flex justify-center items-center">
        <div className="text-center">
          <h2 className="text-2xl font-bold text-gray-900">Publication not found</h2>
          <p className="text-gray-600 mt-2">The publication you're looking for doesn't exist.</p>
        </div>
      </div>
    );
  }

  const stats = publication.stats;

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Hero Section */}
      <div className="bg-white border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
          <div className="flex items-start space-x-6">
            {publication.logo_url ? (
              <img
                src={publication.logo_url}
                alt={publication.name}
                className="w-24 h-24 rounded-lg object-cover"
              />
            ) : (
              <div className="w-24 h-24 rounded-lg bg-gray-200 flex items-center justify-center">
                <span className="text-3xl font-bold text-gray-500">
                  {publication.name.charAt(0).toUpperCase()}
                </span>
              </div>
            )}

            <div className="flex-1">
              <h1 className="text-4xl font-bold text-gray-900 mb-2">{publication.name}</h1>
              {publication.description && (
                <p className="text-xl text-gray-600 mb-4">{publication.description}</p>
              )}

              {stats && (
                <div className="flex items-center space-x-6 text-sm text-gray-500 mb-4">
                  <span>{stats.published_articles} articles</span>
                  <span>{stats.member_count} writers</span>
                  <span>{publication.followers_count || 0} followers</span>
                  <span>{stats.total_views.toLocaleString()} views</span>
                </div>
              )}

              <div className="flex items-center space-x-3">
                {user && (
                  <button
                    onClick={handleFollow}
                    disabled={followLoading}
                    className={`px-4 py-2 text-sm font-medium rounded-md transition-colors ${isFollowing
                      ? 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                      : 'bg-blue-600 text-white hover:bg-blue-700'
                      } disabled:opacity-50`}
                  >
                    {followLoading ? 'Loading...' : isFollowing ? 'Following' : 'Follow'}
                  </button>
                )}

                {publication.can_manage && (
                  <Link
                    to={`/publication/${publication.id}/manage`}
                    className="px-4 py-2 text-sm font-medium text-blue-600 hover:text-blue-800 border border-blue-600 hover:border-blue-800 rounded-md transition-colors"
                  >
                    Manage
                  </Link>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Navigation Tabs */}
      <div className="bg-white border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <nav className="-mb-px flex space-x-8">
            <button
              onClick={() => setActiveTab('articles')}
              className={`py-4 px-1 border-b-2 font-medium text-sm ${activeTab === 'articles'
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
            >
              Articles
            </button>
            <button
              onClick={() => setActiveTab('about')}
              className={`py-4 px-1 border-b-2 font-medium text-sm ${activeTab === 'about'
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
            >
              About
            </button>
            <button
              onClick={() => setActiveTab('members')}
              className={`py-4 px-1 border-b-2 font-medium text-sm ${activeTab === 'members'
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
            >
              Writers
            </button>
          </nav>
        </div>
      </div>

      {/* Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {activeTab === 'articles' && (
          <div>
            {/* Search and Filter Controls */}
            <div className="bg-white rounded-lg shadow-sm p-6 mb-6">
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                {/* Search */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Search Articles
                  </label>
                  <input
                    type="text"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    placeholder="Search by title or content..."
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>

                {/* Sort */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Sort By
                  </label>
                  <select
                    value={sortBy}
                    onChange={(e) => setSortBy(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  >
                    <option value="newest">Newest First</option>
                    <option value="oldest">Oldest First</option>
                    <option value="popular">Most Popular</option>
                    <option value="views">Most Views</option>
                    <option value="claps">Most Claps</option>
                    <option value="comments">Most Comments</option>
                  </select>
                </div>

                {/* Author Filter */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Author
                  </label>
                  <select
                    value={selectedAuthor}
                    onChange={(e) => setSelectedAuthor(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  >
                    <option value="">All Authors</option>
                    {getUniqueAuthors().map((author) => (
                      <option key={author.id} value={author.id}>
                        {author.username}
                      </option>
                    ))}
                  </select>
                </div>

                {/* Tag Filter */}
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Tag
                  </label>
                  <select
                    value={selectedTag}
                    onChange={(e) => setSelectedTag(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  >
                    <option value="">All Tags</option>
                    {getUniqueTags().map((tag) => (
                      <option key={tag} value={tag}>
                        {tag}
                      </option>
                    ))}
                  </select>
                </div>
              </div>

              {/* Date Range */}
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    From Date
                  </label>
                  <input
                    type="date"
                    value={dateFrom}
                    onChange={(e) => setDateFrom(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    To Date
                  </label>
                  <input
                    type="date"
                    value={dateTo}
                    onChange={(e) => setDateTo(e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                </div>
                <div className="flex items-end">
                  <button
                    onClick={clearFilters}
                    className="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors"
                  >
                    Clear Filters
                  </button>
                </div>
              </div>
            </div>

            {/* Articles Grid */}
            {articlesLoading ? (
              <div className="flex justify-center py-8">
                <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
              </div>
            ) : articles.length > 0 ? (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {articles.map((article) => (
                  <ArticleCard key={article.id} article={article} />
                ))}
              </div>
            ) : (
              <div className="text-center py-12">
                <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 className="mt-2 text-sm font-medium text-gray-900">No articles found</h3>
                <p className="mt-1 text-sm text-gray-500">
                  {searchQuery || selectedAuthor || selectedTag || dateFrom || dateTo
                    ? 'Try adjusting your filters to find more articles.'
                    : 'This publication hasn\'t published any articles yet.'
                  }
                </p>
              </div>
            )}
          </div>
        )}

        {activeTab === 'about' && (
          <div className="max-w-4xl">
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
              {/* Main About Section */}
              <div className="lg:col-span-2">
                <div className="bg-white rounded-lg shadow-sm p-6">
                  <h2 className="text-2xl font-bold text-gray-900 mb-4">About {publication.name}</h2>

                  {publication.description ? (
                    <div className="prose prose-lg max-w-none mb-6">
                      <p className="text-gray-700 leading-relaxed">{publication.description}</p>
                    </div>
                  ) : (
                    <p className="text-gray-500 italic mb-6">No description available.</p>
                  )}

                  <div className="border-t border-gray-200 pt-6">
                    <h3 className="text-lg font-medium text-gray-900 mb-4">Publication Details</h3>
                    <dl className="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                      <div>
                        <dt className="text-sm font-medium text-gray-500">Founded</dt>
                        <dd className="mt-1 text-sm text-gray-900">
                          {new Date(publication.created_at).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                          })}
                        </dd>
                      </div>
                      <div>
                        <dt className="text-sm font-medium text-gray-500">Owner</dt>
                        <dd className="mt-1 text-sm text-gray-900">
                          <Link
                            to={`/user/${publication.owner_username}`}
                            className="text-blue-600 hover:text-blue-800"
                          >
                            {publication.owner_username}
                          </Link>
                        </dd>
                      </div>
                      <div>
                        <dt className="text-sm font-medium text-gray-500">Writers</dt>
                        <dd className="mt-1 text-sm text-gray-900">
                          {(publication.members?.length || 0) + 1} {/* +1 for owner */}
                        </dd>
                      </div>
                      <div>
                        <dt className="text-sm font-medium text-gray-500">Followers</dt>
                        <dd className="mt-1 text-sm text-gray-900">
                          {publication.followers_count || 0}
                        </dd>
                      </div>
                    </dl>
                  </div>
                </div>
              </div>

              {/* Statistics Sidebar */}
              <div className="lg:col-span-1">
                <div className="bg-white rounded-lg shadow-sm p-6">
                  <h3 className="text-lg font-medium text-gray-900 mb-4">Statistics</h3>

                  {stats ? (
                    <div className="space-y-4">
                      <div className="flex justify-between items-center py-2 border-b border-gray-100">
                        <span className="text-sm text-gray-600">Published Articles</span>
                        <span className="text-lg font-semibold text-gray-900">{stats.published_articles}</span>
                      </div>
                      <div className="flex justify-between items-center py-2 border-b border-gray-100">
                        <span className="text-sm text-gray-600">Total Views</span>
                        <span className="text-lg font-semibold text-gray-900">{stats.total_views.toLocaleString()}</span>
                      </div>
                      <div className="flex justify-between items-center py-2 border-b border-gray-100">
                        <span className="text-sm text-gray-600">Total Claps</span>
                        <span className="text-lg font-semibold text-gray-900">{stats.total_claps.toLocaleString()}</span>
                      </div>
                      <div className="flex justify-between items-center py-2 border-b border-gray-100">
                        <span className="text-sm text-gray-600">Total Comments</span>
                        <span className="text-lg font-semibold text-gray-900">{stats.total_comments.toLocaleString()}</span>
                      </div>
                      <div className="flex justify-between items-center py-2">
                        <span className="text-sm text-gray-600">Draft Articles</span>
                        <span className="text-lg font-semibold text-gray-900">{stats.draft_articles}</span>
                      </div>
                    </div>
                  ) : (
                    <p className="text-gray-500 text-sm">Statistics not available</p>
                  )}
                </div>

                {/* Recent Activity */}
                {publication.recent_activity && publication.recent_activity.length > 0 && (
                  <div className="bg-white rounded-lg shadow-sm p-6 mt-6">
                    <h3 className="text-lg font-medium text-gray-900 mb-4">Recent Activity</h3>
                    <div className="space-y-3">
                      {publication.recent_activity.slice(0, 5).map((activity, index) => (
                        <div key={index} className="flex items-start space-x-3">
                          <div className={`w-2 h-2 rounded-full mt-2 ${activity.activity_type === 'article_published' ? 'bg-green-500' :
                            activity.activity_type === 'article_submitted' ? 'bg-yellow-500' :
                              'bg-blue-500'
                            }`}></div>
                          <div className="flex-1 min-w-0">
                            <p className="text-sm text-gray-900 truncate">
                              {activity.article_title}
                            </p>
                            <p className="text-xs text-gray-500">
                              {new Date(activity.activity_date).toLocaleDateString('en-US', {
                                month: 'short',
                                day: 'numeric'
                              })}
                            </p>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>
        )}

        {activeTab === 'members' && (
          <div>
            {/* Publication Owner */}
            {publication && (
              <div className="mb-8">
                <h3 className="text-lg font-medium text-gray-900 mb-4">Publication Owner</h3>
                <div className="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
                  <div className="flex items-center space-x-4">
                    {publication.owner_avatar ? (
                      <img
                        src={publication.owner_avatar}
                        alt={publication.owner_username}
                        className="w-20 h-20 rounded-full object-cover"
                      />
                    ) : (
                      <div className="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center">
                        <span className="text-2xl font-medium text-gray-500">
                          {publication.owner_username?.charAt(0).toUpperCase()}
                        </span>
                      </div>
                    )}
                    <div className="flex-1">
                      <Link
                        to={`/user/${publication.owner_username}`}
                        className="text-xl font-medium text-gray-900 hover:text-blue-600"
                      >
                        {publication.owner_username}
                      </Link>
                      <div className="flex items-center mt-1">
                        <span className="px-3 py-1 text-sm font-medium rounded-full bg-blue-100 text-blue-800">
                          Owner
                        </span>
                      </div>
                      <p className="text-sm text-gray-600 mt-2">
                        Founded this publication on {new Date(publication.created_at).toLocaleDateString('en-US', {
                          year: 'numeric',
                          month: 'long',
                          day: 'numeric'
                        })}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* Publication Members */}
            {publication.members && publication.members.length > 0 && (
              <div>
                <h3 className="text-lg font-medium text-gray-900 mb-4">Writers & Editors</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                  {publication.members.map((member) => (
                    <div key={member.user_id} className="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
                      <div className="flex items-start space-x-4">
                        {member.user?.profile_image_url ? (
                          <img
                            src={member.user?.profile_image_url}
                            alt={member.user?.username}
                            className="w-16 h-16 rounded-full object-cover"
                          />
                        ) : (
                          <div className="w-16 h-16 rounded-full bg-gray-200 flex items-center justify-center">
                            <span className="text-xl font-medium text-gray-500">
                              {member.user?.username?.charAt(0).toUpperCase()}
                            </span>
                          </div>
                        )}
                        <div className="flex-1 min-w-0">
                          <Link
                            to={`/user/${member.user?.username}`}
                            className="text-lg font-medium text-gray-900 hover:text-blue-600 block truncate"
                          >
                            {member.user?.username}
                          </Link>
                          <div className="flex items-center mt-1">
                            <span className={`px-2 py-1 text-xs font-medium rounded-full ${member.role === 'admin' ? 'bg-red-100 text-red-800' :
                              member.role === 'editor' ? 'bg-yellow-100 text-yellow-800' :
                                'bg-green-100 text-green-800'
                              }`}>
                              {member.role}
                            </span>
                          </div>
                          {member.user?.bio && (
                            <p className="text-sm text-gray-600 mt-2 line-clamp-3">
                              {member.user.bio}
                            </p>
                          )}
                          <p className="text-xs text-gray-500 mt-2">
                            Joined {member.joined_at ? new Date(member.joined_at).toLocaleDateString('en-US', {
                              year: 'numeric',
                              month: 'short'
                            }) : 'Unknown'}
                          </p>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {(!publication.members || publication.members.length === 0) && (
              <div className="text-center py-12">
                <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                </svg>
                <h3 className="mt-2 text-sm font-medium text-gray-900">No additional writers yet</h3>
                <p className="mt-1 text-sm text-gray-500">
                  This publication currently only has its owner as a writer.
                </p>
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
};

export default PublicationPage;