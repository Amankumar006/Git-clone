import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { apiService } from '../utils/api';
import ArticleCard from '../components/ArticleCard';
import { useAuth } from '../context/AuthContext';

interface Tag {
  id: number;
  name: string;
  slug: string;
  description?: string;
  article_count: number;
}

interface TagStats {
  total_articles: number;
  unique_authors: number;
  avg_views: number;
  avg_claps: number;
  followers: number;
}

interface Author {
  id: number;
  username: string;
  profile_image_url?: string;
  bio?: string;
  article_count: number;
  total_views: number;
  total_claps: number;
}

const TagPage: React.FC = () => {
  const { slug } = useParams<{ slug: string }>();
  const navigate = useNavigate();
  const { user } = useAuth();

  const [tag, setTag] = useState<Tag | null>(null);
  const [articles, setArticles] = useState<any[]>([]);
  const [stats, setStats] = useState<TagStats | null>(null);
  const [similarTags, setSimilarTags] = useState<Tag[]>([]);
  const [topAuthors, setTopAuthors] = useState<Author[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [isFollowing, setIsFollowing] = useState(false);
  const [followLoading, setFollowLoading] = useState(false);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);
  const [sortBy, setSortBy] = useState('latest');

  // Load tag data
  useEffect(() => {
    if (!slug) return;

    loadTagData();
  }, [slug, sortBy]);

  // Check if user is following tag
  useEffect(() => {
    if (user && tag) {
      checkFollowingStatus();
    }
  }, [user, tag]);

  const loadTagData = async () => {
    setLoading(true);
    setError(null);

    try {
      // Load tag info and articles
      const tagResponse = await apiService.tags.getBySlug(slug!, { page, limit: 10 });

      if (tagResponse.data.success) {
        setTag(tagResponse.data.data.tag);
        setArticles(tagResponse.data.data.articles);
        setHasMore(tagResponse.data.data.articles.length === 10);
      } else {
        setError('Tag not found');
        return;
      }

      // Load tag statistics
      const statsResponse = await apiService.tags.getStats(tagResponse.data.data.tag.id);
      if (statsResponse.data.success) {
        setStats(statsResponse.data.data.stats);
        setSimilarTags(statsResponse.data.data.similar_tags);
        setTopAuthors(statsResponse.data.data.top_authors);
      }

    } catch (err: any) {
      setError(err.response?.data?.error || 'Failed to load tag');
    } finally {
      setLoading(false);
    }
  };

  const checkFollowingStatus = async () => {
    if (!tag || !user) return;

    try {
      const response = await apiService.tags.checkFollowing(tag.id);
      if (response.data.success) {
        setIsFollowing(response.data.data.following);
      }
    } catch (error) {
      console.error('Failed to check following status:', error);
    }
  };

  const handleFollowToggle = async () => {
    if (!user || !tag) return;

    setFollowLoading(true);
    try {
      const response = isFollowing
        ? await apiService.tags.unfollow(tag.id)
        : await apiService.tags.follow(tag.id);

      if (response.data.success) {
        setIsFollowing(!isFollowing);
        // Update follower count in stats
        if (stats) {
          setStats({
            ...stats,
            followers: stats.followers + (isFollowing ? -1 : 1)
          });
        }
      }
    } catch (error) {
      console.error('Failed to toggle follow:', error);
    } finally {
      setFollowLoading(false);
    }
  };

  const loadMoreArticles = async () => {
    if (!hasMore || loading) return;

    try {
      const response = await apiService.tags.getBySlug(slug!, { page: page + 1, limit: 10 });

      if (response.data.success) {
        const newArticles = response.data.data.articles;
        setArticles(prev => [...prev, ...newArticles]);
        setPage(prev => prev + 1);
        setHasMore(newArticles.length === 10);
      }
    } catch (error) {
      console.error('Failed to load more articles:', error);
    }
  };

  if (loading && !tag) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600 mx-auto"></div>
          <p className="mt-2 text-gray-600">Loading tag...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="text-red-500 text-6xl mb-4">⚠️</div>
          <h2 className="text-2xl font-bold text-gray-900 mb-2">Tag Not Found</h2>
          <p className="text-gray-600 mb-4">{error}</p>
          <button
            onClick={() => navigate('/')}
            className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
          >
            Go Home
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-6xl mx-auto px-4 py-8">
        {/* Tag Header */}
        <div className="bg-white rounded-lg shadow-sm p-8 mb-8">
          <div className="flex items-start justify-between">
            <div className="flex-1">
              <div className="flex items-center space-x-4 mb-4">
                <h1 className="text-4xl font-bold text-gray-900">#{tag?.name}</h1>
                {user && (
                  <button
                    onClick={handleFollowToggle}
                    disabled={followLoading}
                    className={`px-4 py-2 rounded-lg font-medium transition-colors ${isFollowing
                      ? 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                      : 'bg-green-600 text-white hover:bg-green-700'
                      } ${followLoading ? 'opacity-50 cursor-not-allowed' : ''}`}
                  >
                    {followLoading ? 'Loading...' : isFollowing ? 'Following' : 'Follow'}
                  </button>
                )}
              </div>

              {tag?.description && (
                <p className="text-gray-600 text-lg mb-6">{tag.description}</p>
              )}

              {/* Tag Statistics */}
              {stats && (
                <div className="grid grid-cols-2 md:grid-cols-4 gap-6">
                  <div className="text-center">
                    <div className="text-2xl font-bold text-gray-900">{stats.total_articles}</div>
                    <div className="text-sm text-gray-600">Articles</div>
                  </div>
                  <div className="text-center">
                    <div className="text-2xl font-bold text-gray-900">{stats.unique_authors}</div>
                    <div className="text-sm text-gray-600">Authors</div>
                  </div>
                  <div className="text-center">
                    <div className="text-2xl font-bold text-gray-900">{Math.round(stats.avg_views)}</div>
                    <div className="text-sm text-gray-600">Avg Views</div>
                  </div>
                  <div className="text-center">
                    <div className="text-2xl font-bold text-gray-900">{stats.followers}</div>
                    <div className="text-sm text-gray-600">Followers</div>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Main Content */}
          <div className="lg:col-span-2">
            {/* Sort Options */}
            <div className="bg-white rounded-lg shadow-sm p-4 mb-6">
              <div className="flex items-center justify-between">
                <h2 className="text-lg font-semibold text-gray-900">Articles</h2>
                <select
                  value={sortBy}
                  onChange={(e) => setSortBy(e.target.value)}
                  className="px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500"
                >
                  <option value="latest">Latest</option>
                  <option value="popular">Most Popular</option>
                  <option value="trending">Trending</option>
                </select>
              </div>
            </div>

            {/* Articles List */}
            <div className="space-y-6">
              {articles.map((article) => (
                <div key={article.id} className="bg-white rounded-lg shadow-sm">
                  <ArticleCard article={article} />
                </div>
              ))}

              {articles.length === 0 && !loading && (
                <div className="text-center py-12">
                  <div className="text-gray-400 text-6xl mb-4">📝</div>
                  <h3 className="text-lg font-medium text-gray-900 mb-2">No articles yet</h3>
                  <p className="text-gray-600">
                    Be the first to write about #{tag?.name}!
                  </p>
                </div>
              )}

              {/* Load More Button */}
              {hasMore && (
                <div className="text-center">
                  <button
                    onClick={loadMoreArticles}
                    className="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                  >
                    Load More Articles
                  </button>
                </div>
              )}
            </div>
          </div>

          {/* Sidebar */}
          <div className="space-y-6">
            {/* Top Authors */}
            {topAuthors.length > 0 && (
              <div className="bg-white rounded-lg shadow-sm p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Top Authors</h3>
                <div className="space-y-4">
                  {topAuthors.map((author) => (
                    <div key={author.id} className="flex items-center space-x-3">
                      <img
                        src={author.profile_image_url || '/default-avatar.svg'}
                        alt={author.username}
                        className="w-10 h-10 rounded-full object-cover"
                        onError={(e) => {
                          const target = e.target as HTMLImageElement;
                          if (target.src !== window.location.origin + '/default-avatar.svg') {
                            target.src = '/default-avatar.svg';
                          }
                        }}
                      />
                      <div className="flex-1">
                        <h4 className="font-medium text-gray-900">{author.username}</h4>
                        <p className="text-sm text-gray-600">{author.article_count} articles</p>
                      </div>
                      <button
                        onClick={() => navigate(`/user/${author.username}`)}
                        className="text-green-600 hover:text-green-700 text-sm font-medium"
                      >
                        View
                      </button>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Similar Tags */}
            {similarTags.length > 0 && (
              <div className="bg-white rounded-lg shadow-sm p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Related Tags</h3>
                <div className="flex flex-wrap gap-2">
                  {similarTags.map((similarTag) => (
                    <button
                      key={similarTag.id}
                      onClick={() => navigate(`/tag/${similarTag.slug}`)}
                      className="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm hover:bg-gray-200 transition-colors"
                    >
                      #{similarTag.name}
                    </button>
                  ))}
                </div>
              </div>
            )}

            {/* Tag Cloud Link */}
            <div className="bg-white rounded-lg shadow-sm p-6">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Explore More</h3>
              <div className="space-y-3">
                <button
                  onClick={() => navigate('/tags')}
                  className="w-full text-left px-4 py-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors"
                >
                  Browse All Tags
                </button>
                <button
                  onClick={() => navigate('/tags/trending')}
                  className="w-full text-left px-4 py-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors"
                >
                  Trending Tags
                </button>
                <button
                  onClick={() => navigate('/search')}
                  className="w-full text-left px-4 py-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors"
                >
                  Advanced Search
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default TagPage;