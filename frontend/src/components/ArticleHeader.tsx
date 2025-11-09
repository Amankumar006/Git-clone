import React from 'react';
import { Link } from 'react-router-dom';
import { Article } from '../types';
import { formatReadingTime } from '../utils/readingTime';

interface ArticleHeaderProps {
  article: Article;
  onShare: (platform: string) => void;
}

const ArticleHeader: React.FC<ArticleHeaderProps> = ({ article, onShare }) => {
  return (
    <header className="border-b border-gray-200 bg-white">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        {/* Article Title */}
        <h1 className="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 mb-3 sm:mb-4 leading-tight">
          {article.title}
        </h1>
        
        {/* Article Subtitle */}
        {article.subtitle && (
          <h2 className="text-lg sm:text-xl text-gray-600 mb-6 leading-relaxed">
            {article.subtitle}
          </h2>
        )}
        
        {/* Author and metadata section */}
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          {/* Author info */}
          <div className="flex items-center space-x-3 min-w-0 flex-1">
            <Link 
              to={`/user/${article.username}`} 
              className="flex items-center space-x-3 hover:opacity-80 transition-opacity min-w-0"
            >
              <img
                src={article.author_avatar || '/default-avatar.svg'}
                alt={article.username || 'Author'}
                className="w-10 h-10 sm:w-12 sm:h-12 rounded-full object-cover flex-shrink-0"
                onError={(e) => {
                  const target = e.target as HTMLImageElement;
                  if (target.src !== window.location.origin + '/default-avatar.svg') {
                    target.src = '/default-avatar.svg';
                  }
                }}
              />
              <div className="min-w-0">
                <p className="font-medium text-gray-900 truncate text-sm sm:text-base">
                  {article.username}
                </p>
                <div className="flex items-center space-x-1 sm:space-x-2 text-xs sm:text-sm text-gray-500 flex-wrap">
                  <time dateTime={article.published_at}>
                    {new Date(article.published_at || article.created_at).toLocaleDateString('en-US', {
                      year: 'numeric',
                      month: 'long',
                      day: 'numeric'
                    })}
                  </time>
                  <span className="text-gray-400">·</span>
                  <span>{formatReadingTime(article.reading_time || article.readingTime || 0)}</span>
                </div>
              </div>
            </Link>
          </div>
          
          {/* Social sharing buttons */}
          <div className="flex items-center space-x-1 flex-shrink-0">
            <button
              onClick={() => onShare('twitter')}
              className="p-2 text-gray-500 hover:text-blue-500 hover:bg-blue-50 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
              title="Share on Twitter"
              aria-label="Share on Twitter"
            >
              <svg className="w-4 h-4 sm:w-5 sm:h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
              </svg>
            </button>
            
            <button
              onClick={() => onShare('facebook')}
              className="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2"
              title="Share on Facebook"
              aria-label="Share on Facebook"
            >
              <svg className="w-4 h-4 sm:w-5 sm:h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
              </svg>
            </button>
            
            <button
              onClick={() => onShare('linkedin')}
              className="p-2 text-gray-500 hover:text-blue-700 hover:bg-blue-50 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-700 focus:ring-offset-2"
              title="Share on LinkedIn"
              aria-label="Share on LinkedIn"
            >
              <svg className="w-4 h-4 sm:w-5 sm:h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
              </svg>
            </button>
            
            <button
              onClick={() => onShare('copy')}
              className="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
              title="Copy link"
              aria-label="Copy link"
            >
              <svg className="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </header>
  );
};

export default ArticleHeader;