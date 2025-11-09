import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { apiService } from '../utils/api';

interface UserProfile {
  id: number;
  username: string;
  email?: string;
  bio?: string;
  profile_image_url?: string;
  social_links?: Record<string, string>;
  created_at: string;
  followers_count?: number;
  following_count?: number;
  articles_count?: number;
}

interface Article {
  id: number;
  title: string;

  featured_image_url?: string;
  published_at: string;
  readingTime: number;
  view_count: number;
  clap_count: number;
  comment_count: number;
}

interface ProfileResponse {
  user: UserProfile;
  is_following?: boolean;
}

interface ArticlesResponse {
  articles: Article[];
  user: UserProfile;
}

const UserProfilePage: React.FC = () => {
  const { username } = useParams<{ username: string }>();
  const { user: currentUser } = useAuth();
  const [profile, setProfile] = useState<UserProfile | null>(null);
  const [articles, setArticles] = useState<Article[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isFollowing, setIsFollowing] = useState(false);
  const [followLoading, setFollowLoading] = useState(false);
  const [error, setError] = useState('');

  const isOwnProfile = currentUser?.username === username;

  useEffect(() => {
    const fetchProfile = async () => {
      try {
        setIsLoading(true);
        setError('');
        
        // Get profile by username
        const profileResponse = await apiService.users.getProfile(username, true);
        
        if (profileResponse.success) {
          const profileData = profileResponse.data as ProfileResponse;
          setProfile(profileData.user);
          setIsFollowing(profileData.is_following || false);
          
          // Get user's articles
          const articlesResponse = await apiService.users.getUserArticles(profileData.user.id.toString());
          
          if (articlesResponse.success) {
            const articlesData = articlesResponse.data as ArticlesResponse;
            setArticles(articlesData.articles);
          }
        } else {
          setError('User not found');
        }
      } catch (err: any) {
        setError(err.message || 'Failed to load profile');
      } finally {
        setIsLoading(false);
      }
    };

    if (username) {
      fetchProfile();
    }
  }, [username]);

  const handleFollow = async () => {
    if (!profile || !currentUser) return;

    setFollowLoading(true);
    try {
      if (isFollowing) {
        await apiService.users.unfollow(profile.id);
        setIsFollowing(false);
      } else {
        await apiService.users.follow(profile.id);
        setIsFollowing(true);
      }
    } catch (err: any) {
      setError(err.message || 'Failed to update follow status');
    } finally {
      setFollowLoading(false);
    }
  };

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <h2 className="text-2xl font-bold text-gray-900 mb-4">Error</h2>
          <p className="text-gray-600">{error}</p>
        </div>
      </div>
    );
  }

  if (!profile) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <h2 className="text-2xl font-bold text-gray-900 mb-4">User not found</h2>
          <Link to="/" className="text-primary-600 hover:text-primary-500">
            Go back to homepage
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-4xl mx-auto px-4 py-8">
      {/* Profile Header */}
      <div className="bg-white rounded-lg shadow-sm border p-8 mb-8">
        <div className="flex flex-col md:flex-row items-start md:items-center space-y-4 md:space-y-0 md:space-x-6">
          {/* Profile Image */}
          <div className="flex-shrink-0">
            {profile.profile_image_url ? (
              <img
                src={profile.profile_image_url}
                alt={profile.username}
                className="w-24 h-24 rounded-full object-cover"
              />
            ) : (
              <div className="w-24 h-24 rounded-full bg-gray-200 flex items-center justify-center">
                <span className="text-2xl font-bold text-gray-500">
                  {profile.username.charAt(0).toUpperCase()}
                </span>
              </div>
            )}
          </div>

          {/* Profile Info */}
          <div className="flex-grow">
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
              <div>
                <h1 className="text-3xl font-bold text-gray-900">{profile.username}</h1>
                {profile.bio && (
                  <p className="text-gray-600 mt-2">{profile.bio}</p>
                )}
                <div className="flex items-center space-x-6 mt-3 text-sm text-gray-600">
                  <span>{profile.followers_count || 0} followers</span>
                  <span>{profile.following_count || 0} following</span>
                  <span>{profile.articles_count || 0} articles</span>
                </div>
                <p className="text-sm text-gray-500 mt-2">
                  Member since {new Date(profile.created_at).toLocaleDateString()}
                </p>
              </div>

              {/* Action Buttons */}
              <div className="mt-4 sm:mt-0 flex space-x-3">
                {isOwnProfile ? (
                  <>
                    <Link
                      to="/settings"
                      className="btn-secondary"
                    >
                      Edit Profile
                    </Link>
                    <Link
                      to="/write"
                      className="btn-primary"
                    >
                      Write Article
                    </Link>
                  </>
                ) : currentUser ? (
                  <button
                    onClick={handleFollow}
                    disabled={followLoading}
                    className={`px-4 py-2 rounded-md font-medium transition-colors ${
                      isFollowing
                        ? 'bg-gray-200 text-gray-800 hover:bg-gray-300'
                        : 'bg-primary-600 text-white hover:bg-primary-700'
                    } disabled:opacity-50 disabled:cursor-not-allowed`}
                  >
                    {followLoading ? 'Loading...' : isFollowing ? 'Following' : 'Follow'}
                  </button>
                ) : (
                  <Link to="/login" className="btn-primary">
                    Follow
                  </Link>
                )}
              </div>
            </div>

            {/* Social Links */}
            {profile.social_links && Object.keys(profile.social_links).length > 0 && (
              <div className="mt-4 flex space-x-4">
                {Object.entries(profile.social_links).map(([platform, url]) => (
                  <a
                    key={platform}
                    href={url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-primary-600 hover:text-primary-500 capitalize"
                  >
                    {platform}
                  </a>
                ))}
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Articles Section */}
      <div className="bg-white rounded-lg shadow-sm border">
        <div className="p-6 border-b">
          <h2 className="text-xl font-bold text-gray-900">
            Published Articles ({articles.length})
          </h2>
        </div>

        {articles.length === 0 ? (
          <div className="p-8 text-center">
            <p className="text-gray-500">
              {isOwnProfile ? "You haven't published any articles yet." : "No articles published yet."}
            </p>
            {isOwnProfile && (
              <Link to="/write" className="btn-primary mt-4 inline-block">
                Write your first article
              </Link>
            )}
          </div>
        ) : (
          <div className="divide-y">
            {articles.map((article) => (
              <div key={article.id} className="p-6 hover:bg-gray-50 transition-colors">
                <Link to={`/article/${article.id}`} className="block">
                  <div className="flex space-x-4">
                    {article.featured_image_url && (
                      <img
                        src={article.featured_image_url}
                        alt={article.title}
                        className="w-20 h-20 object-cover rounded"
                      />
                    )}
                    <div className="flex-grow">
                      <h3 className="text-lg font-semibold text-gray-900 hover:text-primary-600">
                        {article.title}
                      </h3>

                      <div className="flex items-center space-x-4 mt-3 text-sm text-gray-500">
                        <span>{new Date(article.published_at).toLocaleDateString()}</span>
                        <span>{article.readingTime} min read</span>
                        <span>{article.view_count} views</span>
                        <span>{article.clap_count} claps</span>
                        <span>{article.comment_count} comments</span>
                      </div>
                    </div>
                  </div>
                </Link>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default UserProfilePage;