import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Publication, Article } from '../types';
import { apiService } from '../utils/api';
import ArticleCard from './ArticleCard';

interface FollowedPublicationsProps {
  showArticles?: boolean;
}

const FollowedPublications: React.FC<FollowedPublicationsProps> = ({ showArticles = false }) => {
  const [publications, setPublications] = useState<Publication[]>([]);
  const [articles, setArticles] = useState<Article[]>([]);
  const [loading, setLoading] = useState(true);
  const [activeView, setActiveView] = useState<'publications' | 'articles'>('publications');

  useEffect(() => {
    loadFollowedPublications();
    if (showArticles) {
      loadFollowedArticles();
    }
  }, [showArticles]);

  const loadFollowedPublications = async () => {
    try {
      const response = await apiService.publications.getFollowed();
      if (response.data.success) {
        setPublications(response.data.data);
      }
    } catch (error) {
      console.error('Failed to load followed publications:', error);
    } finally {
      setLoading(false);
    }
  };

  const loadFollowedArticles = async () => {
    try {
      const response = await apiService.publications.getFollowedArticles();
      if (response.data.success) {
        setArticles(response.data.data);
      }
    } catch (error) {
      console.error('Failed to load followed publications articles:', error);
    }
  };

  const handleUnfollow = async (publicationId: number) => {
    try {
      const response = await apiService.publications.unfollow(publicationId.toString());
      if (response.data.success) {
        setPublications(publications.filter(pub => pub.id !== publicationId));
        // Also remove articles from this publication
        setArticles(articles.filter(article => article.publication_id !== publicationId));
      }
    } catch (error) {
      console.error('Failed to unfollow publication:', error);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center py-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {showArticles && (
        <div className="flex space-x-4 border-b border-gray-200">
          <button
            onClick={() => setActiveView('publications')}
            className={`pb-2 px-1 border-b-2 font-medium text-sm ${
              activeView === 'publications'
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700'
            }`}
          >
            Publications ({publications.length})
          </button>
          <button
            onClick={() => setActiveView('articles')}
            className={`pb-2 px-1 border-b-2 font-medium text-sm ${
              activeView === 'articles'
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700'
            }`}
          >
            Recent Articles ({articles.length})
          </button>
        </div>
      )}

      {(!showArticles || activeView === 'publications') && (
        <div>
          {publications.length > 0 ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {publications.map((publication) => (
                <div key={publication.id} className="bg-white rounded-lg shadow-sm p-6 hover:shadow-md transition-shadow">
                  <div className="flex items-start space-x-4">
                    {publication.logo_url ? (
                      <img
                        src={publication.logo_url}
                        alt={publication.name}
                        className="w-16 h-16 rounded-lg object-cover"
                      />
                    ) : (
                      <div className="w-16 h-16 rounded-lg bg-gray-200 flex items-center justify-center">
                        <span className="text-xl font-bold text-gray-500">
                          {publication.name.charAt(0).toUpperCase()}
                        </span>
                      </div>
                    )}
                    
                    <div className="flex-1 min-w-0">
                      <Link
                        to={`/publication/${publication.id}`}
                        className="text-lg font-medium text-gray-900 hover:text-blue-600 block truncate"
                      >
                        {publication.name}
                      </Link>
                      
                      {publication.description && (
                        <p className="text-sm text-gray-600 mt-1 line-clamp-2">
                          {publication.description}
                        </p>
                      )}
                      
                      <div className="flex items-center justify-between mt-3">
                        <div className="flex items-center space-x-4 text-xs text-gray-500">
                          <span>{publication.stats?.published_articles || 0} articles</span>
                          <span>{publication.stats?.member_count || 0} writers</span>
                        </div>
                        
                        <button
                          onClick={() => handleUnfollow(publication.id)}
                          className="text-xs text-gray-500 hover:text-red-600 transition-colors"
                        >
                          Unfollow
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="text-center py-12">
              <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
              </svg>
              <h3 className="mt-2 text-sm font-medium text-gray-900">No followed publications</h3>
              <p className="mt-1 text-sm text-gray-500">
                Start following publications to see them here.
              </p>
              <div className="mt-6">
                <Link
                  to="/publications"
                  className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                >
                  Discover Publications
                </Link>
              </div>
            </div>
          )}
        </div>
      )}

      {showArticles && activeView === 'articles' && (
        <div>
          {articles.length > 0 ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {articles.map((article) => (
                <ArticleCard key={article.id} article={article} showPublication={true} />
              ))}
            </div>
          ) : (
            <div className="text-center py-12">
              <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <h3 className="mt-2 text-sm font-medium text-gray-900">No recent articles</h3>
              <p className="mt-1 text-sm text-gray-500">
                Articles from your followed publications will appear here.
              </p>
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default FollowedPublications;