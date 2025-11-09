import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { apiService } from '../utils/api';
import { Article } from '../types';
import ArticleCard from './ArticleCard';

interface MoreFromAuthorProps {
  currentArticle: Article;
  className?: string;
  limit?: number;
}

const MoreFromAuthor: React.FC<MoreFromAuthorProps> = ({ 
  currentArticle, 
  className = '',
  limit = 4
}) => {
  const [articles, setArticles] = useState<Article[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchMoreFromAuthor();
  }, [currentArticle.id, currentArticle.author_id]);

  const fetchMoreFromAuthor = async () => {
    try {
      setLoading(true);
      setError(null);
      
      // Try to fetch more articles from the same author
      const response = await apiService.articles.getMoreFromAuthor(
        currentArticle.author_id.toString(),
        currentArticle.id.toString(),
        limit
      );
      
      if (response.success && response.data) {
        setArticles(response.data as Article[]);
      } else {
        // Fallback: get user articles and filter out current one
        const userResponse = await apiService.users.getUserArticles(
          currentArticle.author_id.toString()
        );
        
        if (userResponse.success && userResponse.data) {
          const filtered = (userResponse.data as Article[]).filter(
            (article: Article) => article.id !== currentArticle.id && article.status === 'published'
          );
          setArticles(filtered.slice(0, limit));
        }
      }
    } catch (err: any) {
      console.error('Error fetching more from author:', err);
      setError('Failed to load more articles from this author');
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <section className={`more-from-author ${className}`}>
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-xl font-bold text-gray-900">
            More from {currentArticle.username}
          </h2>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {[...Array(Math.min(limit, 4))].map((_, index) => (
            <div key={index} className="animate-pulse">
              <div className="bg-gray-200 h-32 rounded-lg mb-3"></div>
              <div className="space-y-2">
                <div className="h-3 bg-gray-200 rounded w-3/4"></div>
                <div className="h-3 bg-gray-200 rounded w-1/2"></div>
              </div>
            </div>
          ))}
        </div>
      </section>
    );
  }

  if (error || articles.length === 0) {
    return null; // Don't show section if no articles or error
  }

  return (
    <section className={`more-from-author ${className}`}>
      <div className="flex items-center justify-between mb-6">
        <div className="flex items-center space-x-3">
          <img
            src={currentArticle.author_avatar || '/default-avatar.svg'}
            alt={currentArticle.username || 'Author'}
            className="w-8 h-8 rounded-full object-cover"
            onError={(e) => {
              const target = e.target as HTMLImageElement;
              if (target.src !== window.location.origin + '/default-avatar.svg') {
                target.src = '/default-avatar.svg';
              }
            }}
          />
          <h2 className="text-xl font-bold text-gray-900">
            More from {currentArticle.username}
          </h2>
        </div>
        
        <Link
          to={`/user/${currentArticle.username}`}
          className="text-sm text-blue-600 hover:text-blue-800 font-medium"
        >
          View all →
        </Link>
      </div>
      
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        {articles.map((article) => (
          <div key={article.id} className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
            <Link to={`/article/${article.id}`} className="block">
              <h3 className="font-semibold text-gray-900 mb-2 line-clamp-2 hover:text-blue-600 transition-colors">
                {article.title}
              </h3>
              
              {article.subtitle && (
                <p className="text-sm text-gray-600 mb-3 line-clamp-2">
                  {article.subtitle}
                </p>
              )}
              
              <div className="flex items-center justify-between text-xs text-gray-500">
                <time dateTime={article.published_at}>
                  {new Date(article.published_at || article.created_at).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                  })}
                </time>
                
                <div className="flex items-center space-x-3">
                  <span className="flex items-center space-x-1">
                    <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <span>{article.view_count}</span>
                  </span>
                  
                  <span className="flex items-center space-x-1">
                    <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3.5M3 16.5h18" />
                    </svg>
                    <span>{article.clap_count}</span>
                  </span>
                </div>
              </div>
            </Link>
          </div>
        ))}
      </div>
    </section>
  );
};

export default MoreFromAuthor;