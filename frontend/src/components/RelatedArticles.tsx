import React, { useState, useEffect } from 'react';
import { apiService } from '../utils/api';
import { Article } from '../types';
import ArticleCard from './ArticleCard';

interface RelatedArticlesProps {
  currentArticle: Article;
  className?: string;
}

const RelatedArticles: React.FC<RelatedArticlesProps> = ({ 
  currentArticle, 
  className = '' 
}) => {
  const [relatedArticles, setRelatedArticles] = useState<Article[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchRelatedArticles();
  }, [currentArticle.id]);

  const fetchRelatedArticles = async () => {
    try {
      setLoading(true);
      setError(null);
      
      // Try to fetch related articles from API
      const response = await apiService.articles.getRelated(currentArticle.id.toString(), 6);
      
      if (response.success && response.data) {
        setRelatedArticles(response.data as Article[]);
      } else {
        // Fallback: fetch recommended articles if related articles API doesn't exist
        const recommendedResponse = await apiService.articles.getRecommended(6);
        if (recommendedResponse.success && recommendedResponse.data) {
          // Filter out current article
          const filtered = (recommendedResponse.data as Article[]).filter(
            (article: Article) => article.id !== currentArticle.id
          );
          setRelatedArticles(filtered.slice(0, 6));
        }
      }
    } catch (err: any) {
      console.error('Error fetching related articles:', err);
      setError('Failed to load related articles');
      
      // Try fallback approach - get articles with similar tags
      try {
        if (currentArticle.tags && currentArticle.tags.length > 0) {
          const tagName = typeof currentArticle.tags[0] === 'string' 
            ? currentArticle.tags[0] 
            : currentArticle.tags[0].name;
          
          const tagResponse = await apiService.search.articles('', { 
            tags: [tagName],
            limit: 6 
          });
          
          if (tagResponse.data.success && tagResponse.data.data) {
            const filtered = (tagResponse.data.data as Article[]).filter(
              (article: Article) => article.id !== currentArticle.id
            );
            setRelatedArticles(filtered.slice(0, 6));
            setError(null);
          }
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
      <section className={`related-articles ${className}`}>
        <h2 className="text-2xl font-bold text-gray-900 mb-6">Related Articles</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {[...Array(6)].map((_, index) => (
            <div key={index} className="animate-pulse">
              <div className="bg-gray-200 h-48 rounded-lg mb-4"></div>
              <div className="space-y-2">
                <div className="h-4 bg-gray-200 rounded w-3/4"></div>
                <div className="h-4 bg-gray-200 rounded w-1/2"></div>
              </div>
            </div>
          ))}
        </div>
      </section>
    );
  }

  if (error && relatedArticles.length === 0) {
    return (
      <section className={`related-articles ${className}`}>
        <h2 className="text-2xl font-bold text-gray-900 mb-6">Related Articles</h2>
        <div className="text-center py-8">
          <p className="text-gray-500">{error}</p>
        </div>
      </section>
    );
  }

  if (relatedArticles.length === 0) {
    return null;
  }

  return (
    <section className={`related-articles ${className}`}>
      <div className="flex items-center space-x-2 mb-6">
        <svg className="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
        </svg>
        <h2 className="text-2xl font-bold text-gray-900">Related Articles</h2>
      </div>
      
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {relatedArticles.map((article) => (
          <ArticleCard
            key={article.id}
            article={article}
            showAuthor={true}
            className="h-full"
          />
        ))}
      </div>
    </section>
  );
};

export default RelatedArticles;