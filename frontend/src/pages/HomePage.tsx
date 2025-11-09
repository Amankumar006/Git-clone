import React, { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { Article } from '../types';
import { apiService } from '../utils/api';
import TrendingArticles from '../components/TrendingArticles';
import ArticleCard from '../components/ArticleCard';

const HomePage: React.FC = () => {
  const { isAuthenticated } = useAuth();
  const [articles, setArticles] = useState<Article[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchArticles = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await apiService.articles.getAll();
      
      if (response.success) {
        setArticles((response.data as any)?.articles || []);
      } else {
        setError('Failed to load articles');
      }
    } catch (err: any) {
      setError(err?.message || 'Failed to load articles');
      console.error('Error fetching articles:', err);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchArticles();
  }, [fetchArticles]);

  return (
    <div className="min-h-screen">
      {/* Hero Section */}
      <section className="bg-gradient-to-r from-blue-600 to-blue-700 text-white py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center">
            <h1 className="text-4xl md:text-6xl font-bold mb-6">
              Where good ideas find you
            </h1>
            <p className="text-xl md:text-2xl mb-8 max-w-3xl mx-auto">
              Read, write and share stories that matter. Join a community of writers and readers who are passionate about sharing ideas.
            </p>
            {!isAuthenticated && (
              <Link 
                to="/register" 
                className="inline-block bg-white text-blue-600 font-semibold px-8 py-3 rounded-full hover:bg-gray-100 transition-colors text-lg"
              >
                Start reading
              </Link>
            )}
          </div>
        </div>
      </section>

      {/* Articles Section */}
      <section className="py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {/* Main Content */}
            <div className="lg:col-span-2">
              <h2 className="text-3xl font-bold text-gray-900 mb-8">
                {isAuthenticated ? 'Your Feed' : 'Latest Articles'}
              </h2>
              
              {loading ? (
                <div className="space-y-6">
                  {[1, 2, 3].map((i) => (
                    <div key={i} className="bg-white p-6 rounded-lg shadow-sm border animate-pulse">
                      <div className="flex items-center space-x-3 mb-4">
                        <div className="w-10 h-10 bg-gray-300 rounded-full"></div>
                        <div className="h-4 bg-gray-300 rounded w-24"></div>
                      </div>
                      <div className="h-6 bg-gray-300 rounded mb-2 w-3/4"></div>
                      <div className="h-4 bg-gray-300 rounded mb-4 w-full"></div>
                      <div className="flex space-x-4">
                        <div className="h-3 bg-gray-300 rounded w-16"></div>
                        <div className="h-3 bg-gray-300 rounded w-12"></div>
                      </div>
                    </div>
                  ))}
                </div>
              ) : error ? (
                <div className="text-center py-12">
                  <div className="bg-red-50 border border-red-200 rounded-lg p-6">
                    <p className="text-red-600 mb-4">{error}</p>
                    <button 
                      onClick={fetchArticles}
                      className="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition-colors"
                    >
                      Try Again
                    </button>
                  </div>
                </div>
              ) : articles.length === 0 ? (
                <div className="text-center py-12">
                  <div className="bg-gray-50 border border-gray-200 rounded-lg p-8">
                    <p className="text-gray-600 mb-4">No articles found.</p>
                    {isAuthenticated && (
                      <Link 
                        to="/write" 
                        className="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition-colors"
                      >
                        Write the first article
                      </Link>
                    )}
                  </div>
                </div>
              ) : (
                <div className="space-y-6">
                  {articles.map((article) => (
                    <div key={article.id} className="bg-white p-6 rounded-lg shadow-sm border hover:shadow-md transition-shadow">
                      <div className="flex items-center space-x-3 mb-4">
                        <img
                          src={article.author_avatar || '/default-avatar.svg'}
                          alt={article.username}
                          className="w-10 h-10 rounded-full object-cover"
                        />
                        <div>
                          <p className="font-medium text-gray-900">{article.username}</p>
                          <p className="text-sm text-gray-500">
                            {new Date(article.published_at || article.created_at).toLocaleDateString()}
                          </p>
                        </div>
                      </div>
                      
                      <Link to={`/article/${article.id}`} className="block group">
                        <h3 className="text-xl font-bold text-gray-900 mb-2 group-hover:text-blue-600 transition-colors">
                          {article.title}
                        </h3>

                      </Link>
                      
                      <div className="flex items-center justify-between text-sm text-gray-500">
                        <div className="flex items-center space-x-4">
                          <span>{article.reading_time} min read</span>
                          <span>{article.view_count} views</span>
                          <span>{article.clap_count} claps</span>
                        </div>
                        
                        {article.tags && (
                          <div className="flex space-x-2">
                            {(() => {
                              let tagsArray: string[] = [];
                              if (typeof article.tags === 'string') {
                                tagsArray = article.tags.split(',').map(tag => tag.trim()).filter(Boolean);
                              } else if (Array.isArray(article.tags)) {
                                tagsArray = article.tags.map(tag => typeof tag === 'string' ? tag : tag.name);
                              }
                              
                              return tagsArray.slice(0, 2).map((tag: string, index: number) => (
                                <span key={index} className="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs">
                                  {tag}
                                </span>
                              ));
                            })()}
                          </div>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>

            {/* Sidebar */}
            <div className="lg:col-span-1">
              <div className="sticky top-8 space-y-6">
                {/* Trending Articles */}
                <TrendingArticles className="bg-white p-6 rounded-lg shadow-sm border" />

                <div className="bg-white p-6 rounded-lg shadow-sm border">
                  <h3 className="text-lg font-semibold text-gray-900 mb-4">
                    Trending Topics
                  </h3>
                  <div className="space-y-2">
                    {['Technology', 'Programming', 'Design', 'Startup', 'AI'].map((topic) => (
                      <Link
                        key={topic}
                        to={`/tag/${topic.toLowerCase()}`}
                        className="block text-gray-600 hover:text-blue-600 transition-colors"
                      >
                        #{topic}
                      </Link>
                    ))}
                  </div>
                </div>

                {!isAuthenticated && (
                  <div className="bg-blue-50 border border-blue-200 p-6 rounded-lg">
                    <h3 className="text-lg font-semibold text-blue-900 mb-2">
                      Join Our Platform
                    </h3>
                    <p className="text-blue-700 mb-4">
                      Discover stories, thinking, and expertise from writers on any topic.
                    </p>
                    <Link 
                      to="/register" 
                      className="block w-full text-center bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition-colors"
                    >
                      Get started
                    </Link>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
};

export default HomePage;