import React, { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { apiService } from '../utils/api';

interface BookmarkButtonProps {
  articleId: number;
  initialBookmarked?: boolean;
  onBookmarkUpdate?: (isBookmarked: boolean) => void;
  className?: string;
  showText?: boolean;
}

const BookmarkButton: React.FC<BookmarkButtonProps> = ({
  articleId,
  initialBookmarked = false,
  onBookmarkUpdate,
  className = '',
  showText = false
}) => {
  const { user } = useAuth();
  const [isBookmarked, setIsBookmarked] = useState(initialBookmarked);
  const [isLoading, setIsLoading] = useState(false);

  // Fetch bookmark status when component mounts or user changes
  useEffect(() => {
    if (user && articleId) {
      fetchBookmarkStatus();
    }
  }, [user, articleId]);

  const fetchBookmarkStatus = async () => {
    try {
      const response = await apiService.get(`/bookmarks/status/${articleId}`);
      if (response.success) {
        setIsBookmarked((response.data as { is_bookmarked: boolean }).is_bookmarked);
      }
    } catch (error) {
      console.error('Error fetching bookmark status:', error);
    }
  };

  const handleBookmarkToggle = async () => {
    if (!user) {
      alert('Please log in to bookmark articles');
      return;
    }

    if (isLoading) return;

    setIsLoading(true);

    try {
      let response;
      if (isBookmarked) {
        response = await apiService.delete('/bookmarks/remove', {
          data: { article_id: articleId }
        });
      } else {
        response = await apiService.post('/bookmarks/add', {
          article_id: articleId
        });
      }

      if (response.success) {
        const newBookmarkState = !isBookmarked;
        setIsBookmarked(newBookmarkState);
        
        if (onBookmarkUpdate) {
          onBookmarkUpdate(newBookmarkState);
        }
      }
    } catch (error: any) {
      console.error('Error toggling bookmark:', error);
      if (error.error?.message) {
        alert(error.error.message);
      }
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <button
      onClick={handleBookmarkToggle}
      disabled={!user || isLoading}
      className={`
        flex items-center space-x-2 px-3 py-2 rounded-lg transition-all duration-200
        ${isBookmarked 
          ? 'bg-blue-50 text-blue-600 hover:bg-blue-100' 
          : 'bg-gray-50 text-gray-600 hover:bg-gray-100'
        }
        ${!user ? 'opacity-50 cursor-not-allowed' : 'hover:scale-105'}
        ${isLoading ? 'opacity-75' : ''}
        ${className}
      `}
      title={isBookmarked ? 'Remove bookmark' : 'Bookmark article'}
    >
      {/* Bookmark Icon */}
      <svg
        className={`w-5 h-5 transition-colors duration-200 ${
          isBookmarked ? 'fill-current' : 'stroke-current fill-none'
        }`}
        viewBox="0 0 24 24"
        strokeWidth={2}
      >
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z"
        />
      </svg>

      {/* Loading spinner */}
      {isLoading && (
        <div className="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin"></div>
      )}

      {/* Text */}
      {showText && !isLoading && (
        <span className="text-sm font-medium">
          {isBookmarked ? 'Bookmarked' : 'Bookmark'}
        </span>
      )}
    </button>
  );
};

export default BookmarkButton;