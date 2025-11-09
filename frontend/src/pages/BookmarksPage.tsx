import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { apiService } from '../utils/api';
import { Article } from '../types';
import ArticleCard from '../components/ArticleCard';

interface BookmarksPageProps {}

const BookmarksPage: React.FC<BookmarksPageProps> = () => {
  const { user } = useAuth();
  const [bookmarks, setBookmarks] = useState<Article[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);

  useEffect(() => {
    if (user) {
      fetchBookmarks();
    }
  }, [user]);

  const fetchBookmarks = async (pageNum = 1) => {
    try {
      setLoading(true);
      const response = await apiService.bookmarks.getUserBookmarks(undefined, pageNum, 20);
      
      if (response.success) {
        const data = response.data as { bookmarks: Article[]; pagination: any };
        if (pageNum === 1) {
          setBookmarks(data.bookmarks);
        } else {
          setBookmarks(prev => [...prev, ...data.bookmarks]);
        }
        
        setHasMore(data.pagination.current_page < data.pagination.total_pages);
        setPage(pageNum);
      }
    } catch (error: any) {
      console.error('Error fetching bookmarks:', error);
      setError('Failed to load bookmarks');
    } finally {
      setLoading(false);
    }
  };

  const loadMore = () => {
    if (hasMore && !loading) {
      fetchBookmarks(page + 1);
    }
  };

  const handleBookmarkRemoved = (articleId: number) => {
    setBookmarks(prev => prev.filter(article => article.id !== articleId));
  };

  if (!user) {
    return (
      <div className="min-h-screen bg-gray-50">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
          <div className="text-center">
            <h1 className="text-2xl font-bold text-gray-900 mb-4">
              Please log in to view your bookmarks
            </h1>
            <Link 
              to="/login" 
              className="text-blue-600 hover:text-blue-800 underline"
            >
              Log in
            </Link>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">
            Your Bookmarks
          </h1>
          <p className="text-gray-600">
            Articles you've saved for later reading
          </p>
        </div>

        {/* Content */}
        {loading && bookmarks.length === 0 ? (
          <div className="text-center py-16">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
            <p className="text-gray-500 mt-4">Loading your bookmarks...</p>
          </div>
        ) : error ? (
          <div className="text-center py-16">
            <div className="text-red-500 mb-4">
              <svg className="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h2 className="text-xl font-semibold text-gray-900 mb-2">
              Failed to load bookmarks
            </h2>
            <p className="text-gray-600 mb-4">{error}</p>
            <button
              onClick={() => fetchBookmarks(1)}
              className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
              Try again
            </button>
          </div>
        ) : bookmarks.length === 0 ? (
          <div className="text-center py-16">
            <div className="text-gray-400 mb-4">
              <svg className="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
              </svg>
            </div>
            <h2 className="text-xl font-semibold text-gray-900 mb-2">
              No bookmarks yet
            </h2>
            <p className="text-gray-600 mb-6">
              Start bookmarking articles you want to read later
            </p>
            <Link
              to="/"
              className="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
              Explore articles
            </Link>
          </div>
        ) : (
          <div>
            {/* Bookmarks Grid */}
            <div className="grid gap-6 md:gap-8">
              {bookmarks.map((article) => (
                <div key={article.id} className="bg-white rounded-lg shadow-sm border border-gray-200">
                  <ArticleCard 
                    article={article}
                  />
                </div>
              ))}
            </div>

            {/* Load More Button */}
            {hasMore && (
              <div className="text-center mt-12">
                <button
                  onClick={loadMore}
                  disabled={loading}
                  className="px-6 py-3 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {loading ? (
                    <div className="flex items-center space-x-2">
                      <div className="w-4 h-4 border-2 border-gray-400 border-t-transparent rounded-full animate-spin"></div>
                      <span>Loading...</span>
                    </div>
                  ) : (
                    'Load more bookmarks'
                  )}
                </button>
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
};

export default BookmarksPage;