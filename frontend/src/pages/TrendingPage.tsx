import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { apiService } from '../utils/api';
import { Article } from '../types';
import { formatReadingTime } from '../utils/readingTime';

const TrendingPage: React.FC = () => {
  const [articles, setArticles] = useState<Article[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchTrendingArticles();
  }, []);

  const fetchTrendingArticles = async () => {
    try {
      setLoading(true);
      setError(null);

      // Try to fetch trending articles
      const response = await apiService.search.trending();

      if (response.success && response.data) {
        const articlesData = Array.isArray(response.data) ? response.data : [];
        setArticles(articlesData);
      } else {
        // Fallback: get recommended articles
        const recommendedResponse = await apiService.articles.getRecommended(20);
        if (recommendedResponse.success && recommendedResponse.data) {
          const recommendedData = Array.isArray(recommendedResponse.data) ? recommendedResponse.data : [];
          setArticles(recommendedData);
        }
      }
    } catch (err: any) {
      console.error('Error fetching trending articles:', err);
      setError('Failed to load trending articles');

      // Final fallback: get latest articles
      try {
        const latestResponse = await apiService.articles.getAll({
          limit: 20,
          sort: 'latest'
        });
        if (latestResponse.success && latestResponse.data) {
          const latestData = Array.isArray(latestResponse.data) ? latestResponse.data : [];
          setArticles(latestData);
          setError(null);
        }
      } catch (fallbackErr) {
        console.error('Fallback also failed:', fallbackErr);
      }
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="max-w-4xl mx-auto px-4 py-8">
        <div className="flex items-center space-x-3 mb-8">
          <svg className="w-8 h-8 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
            <path d="M13.5 2c-5.621 0-10.211 4.443-10.475 10H3c-.552 0-1 .449-1 1 0 .551.448 1 1 1h.025C3.289 19.557 7.879 24 13.5 24 19.299 24 24 19.299 24 13.5S19.299 2 13.5 2zm0 20c-4.687 0-8.5-3.813-8.5-8.5S8.813 4 13.5 4 22 7.813 22 13.5 18.187 22 13.5 22z" />
            <path d="M17.5 13.5c0-2.206-1.794-4-4-4s-4 1.794-4 4 1.794 4 4 4 4-1.794 4-4z" />
          </svg>
          <h1 className="text-3xl font-bold text-gray-900">Trending Articles</h1>
        </div>

        <div className="space-y-6">
          {[...Array(10)].map((_, index) => (
            <div key={index} className="animate-pulse">
              <div className="flex space-x-4">
                <div className="w-16 h-16 bg-gray-200 rounded"></div>
                <div className="flex-1 space-y-3">
                  <div className="h-4 bg-gray-200 rounded w-3/4"></div>
                  <div className="h-3 bg-gray-200 rounded w-1/2"></div>
                  <div className="h-3 bg-gray-200 rounded w-1/4"></div>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    );
  }

  if (error && articles.length === 0) {
    return (
      <div className="max-w-4xl mx-auto px-4 py-8">
        <div className="flex items-center space-x-3 mb-8">
          <svg className="w-8 h-8 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
            <path d="M13.5 2c-5.621 0-10.211 4.443-10.475 10H3c-.552 0-1 .449-1 1 0 .551.448 1 1 1h.025C3.289 19.557 7.879 24 13.5 24 19.299 24 24 19.299 24 13.5S19.299 2 13.5 2zm0 20c-4.687 0-8.5-3.813-8.5-8.5S8.813 4 13.5 4 22 7.813 22 13.5 18.187 22 13.5 22z" />
            <path d="M17.5 13.5c0-2.206-1.794-4-4-4s-4 1.794-4 4 1.794 4 4 4 4-1.794 4-4z" />
          </svg>
          <h1 className="text-3xl font-bold text-gray-900">Trending Articles</h1>
        </div>

        <div className="text-center py-12">
          <div className="text-gray-500 mb-4">
            <svg className="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p className="text-lg">Unable to load trending articles</p>
            <p className="text-sm mt-2">Please try again later</p>
          </div>
          <button
            onClick={fetchTrendingArticles}
            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            Try Again
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="max-w-4xl mx-auto px-4 py-8">
      <div className="flex items-center space-x-3 mb-8">
        <svg className="w-8 h-8 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
          <path d="M13.5 2c-5.621 0-10.211 4.443-10.475 10H3c-.552 0-1 .449-1 1 0 .551.448 1 1 1h.025C3.289 19.557 7.879 24 13.5 24 19.299 24 24 19.299 24 13.5S19.299 2 13.5 2zm0 20c-4.687 0-8.5-3.813-8.5-8.5S8.813 4 13.5 4 22 7.813 22 13.5 18.187 22 13.5 22z" />
          <path d="M17.5 13.5c0-2.206-1.794-4-4-4s-4 1.794-4 4 1.794 4 4 4 4-1.794 4-4z" />
        </svg>
        <h1 className="text-3xl font-bold text-gray-900">Trending Articles</h1>
      </div>

      {articles.length === 0 ? (
        <div className="text-center py-12">
          <div className="text-gray-500 mb-4">
            <svg className="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
            </svg>
            <p className="text-lg">No trending articles found</p>
            <p className="text-sm mt-2">Check back later for trending content</p>
          </div>
        </div>
      ) : (
        <div className="space-y-6">
          {articles.map((article, index) => (
            <article key={article.id} className="group border-b border-gray-200 pb-6 last:border-b-0">
              <Link to={`/article/${article.id}`} className="block">
                <div className="flex space-x-4">
                  {/* Ranking Number */}
                  <div className="flex-shrink-0 w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                    <span className="text-lg font-bold text-gray-600">
                      {String(index + 1).padStart(2, '0')}
                    </span>
                  </div>

                  <div className="flex-1 min-w-0">
                    {/* Author */}
                    <div className="flex items-center space-x-2 mb-2">
                      <img
                        src={article.author_avatar || '/default-avatar.svg'}
                        alt={article.username || 'Author'}
                        className="w-6 h-6 rounded-full object-cover"
                        onError={(e) => {
                          const target = e.target as HTMLImageElement;
                          if (target.src !== window.location.origin + '/default-avatar.svg') {
                            target.src = '/default-avatar.svg';
                          }
                        }}
                      />
                      <span className="text-sm text-gray-600 font-medium">
                        {article.username}
                      </span>
                    </div>

                    {/* Title */}
                    <h2 className="text-xl font-bold text-gray-900 line-clamp-2 group-hover:text-blue-600 transition-colors mb-2">
                      {article.title}
                    </h2>



                    {/* Metadata */}
                    <div className="flex items-center space-x-4 text-sm text-gray-500">
                      <time dateTime={article.published_at}>
                        {new Date(article.published_at || article.created_at).toLocaleDateString('en-US', {
                          month: 'long',
                          day: 'numeric',
                          year: 'numeric'
                        })}
                      </time>
                      <span>·</span>
                      <span>{formatReadingTime(article.reading_time || article.readingTime || 0)}</span>
                      
                      {/* Engagement */}
                      <div className="flex items-center space-x-4 ml-auto">
                        <span className="flex items-center space-x-1">
                          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3.5M3 16.5h18" />
                          </svg>
                          <span>{article.clap_count || 0}</span>
                        </span>

                        <span className="flex items-center space-x-1">
                          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                          </svg>
                          <span>{article.comment_count || 0}</span>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </Link>
            </article>
          ))}
        </div>
      )}

      {/* Back to Home */}
      <div className="mt-12 pt-8 border-t border-gray-200 text-center">
        <Link
          to="/"
          className="inline-flex items-center space-x-2 text-blue-600 hover:text-blue-800 font-medium"
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span>Back to Home</span>
        </Link>
      </div>
    </div>
  );
};

export default TrendingPage;