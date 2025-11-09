import React from 'react';
import { Link } from 'react-router-dom';
import { Article } from '../types';
import { formatReadingTime } from '../utils/readingTime';

interface ArticleCardProps {
  article: Article;
  showAuthor?: boolean;
  showPublication?: boolean;
  className?: string;
}

const ArticleCard: React.FC<ArticleCardProps> = ({
  article,
  showAuthor = true,
  showPublication = false,
  className = ''
}) => {
  const getExcerpt = (content: any, maxLength: number = 150): string => {
    if (typeof content === 'string') {
      const plainText = content.replace(/<[^>]*>/g, '');
      return plainText.length > maxLength
        ? plainText.substring(0, maxLength) + '...'
        : plainText;
    }

    // Handle rich text content
    if (Array.isArray(content)) {
      let text = '';
      for (const block of content) {
        if (block?.type === 'paragraph' && block.content) {
          for (const inline of block.content) {
            if (inline?.type === 'text' && inline.text) {
              text += inline.text + ' ';
              if (text.length > maxLength) break;
            }
          }
          if (text.length > maxLength) break;
        }
      }
      return text.length > maxLength
        ? text.substring(0, maxLength) + '...'
        : text.trim();
    }

    return '';
  };

  return (
    <article className={`group bg-white border-b border-gray-200 py-8 px-0 hover:bg-gray-50 transition-all duration-150 ${className}`}>
      <div className="flex gap-8 items-start">
        {/* Content Section */}
        <div className="flex-1 min-w-0">
          {/* Publication Info */}
          {showPublication && article.publication_name && (
            <div className="flex items-center gap-2 mb-3 text-sm">
              {article.publication_logo ? (
                <img
                  src={article.publication_logo}
                  alt={article.publication_name}
                  className="w-5 h-5 rounded object-cover"
                />
              ) : (
                <div className="w-5 h-5 rounded bg-gray-200 flex items-center justify-center">
                  <span className="text-xs font-medium text-gray-500">
                    {article.publication_name.charAt(0).toUpperCase()}
                  </span>
                </div>
              )}
              <span className="text-gray-600">In</span>
              <Link
                to={`/publication/${article.publication_id}`}
                className="text-gray-900 hover:text-gray-700 font-medium"
                onClick={(e) => e.stopPropagation()}
              >
                {article.publication_name}
              </Link>
              {showAuthor && (
                <>
                  <span className="text-gray-600">by</span>
                  <Link
                    to={`/profile/${article.author_id}`}
                    className="text-gray-900 hover:text-gray-700 font-medium"
                    onClick={(e) => e.stopPropagation()}
                  >
                    {article.username || article.author_username}
                  </Link>
                </>
              )}
            </div>
          )}

          {/* Author Info (when no publication) */}
          {showAuthor && !showPublication && (
            <div className="flex items-center gap-2 mb-3">
              <img
                src={article.author_avatar || '/default-avatar.svg'}
                alt={article.username || article.author_username || 'Author'}
                className="w-6 h-6 rounded-full object-cover"
                onError={(e) => {
                  const target = e.target as HTMLImageElement;
                  if (target.src !== window.location.origin + '/default-avatar.svg') {
                    target.src = '/default-avatar.svg';
                  }
                }}
              />
              <Link
                to={`/profile/${article.author_id}`}
                className="text-sm text-gray-900 hover:text-gray-700 font-medium"
                onClick={(e) => e.stopPropagation()}
              >
                {article.username || article.author_username}
              </Link>
              <span className="text-gray-400 text-sm">·</span>
              <time dateTime={article.published_at} className="text-sm text-gray-600">
                {new Date(article.published_at || article.created_at).toLocaleDateString('en-US', {
                  month: 'short',
                  day: 'numeric'
                })}
              </time>
            </div>
          )}

          <Link to={`/article/${article.id}`} className="block group">
            {/* Title */}
            <h2 className="text-2xl font-bold text-gray-900 mb-2 line-clamp-2 group-hover:text-gray-700 transition-colors leading-tight">
              {article.title}
            </h2>

            {/* Subtitle or Excerpt */}
            {(article.subtitle || article.content) && (
              <p className="text-gray-600 text-base mb-4 line-clamp-2 leading-relaxed">
                {article.subtitle || getExcerpt(article.content)}
              </p>
            )}
          </Link>

          {/* Bottom Metadata Row */}
          <div className="flex items-center justify-between mt-6">
            <div className="flex items-center gap-3 text-sm text-gray-600">
              {/* Star emoji for featured/trending */}
              {article.clap_count > 50 && (
                <span className="text-lg">⭐</span>
              )}

              {/* Date */}
              <time dateTime={article.published_at}>
                {(() => {
                  const publishedDate = new Date(article.published_at || article.created_at);
                  const now = new Date();
                  const diffTime = Math.abs(now.getTime() - publishedDate.getTime());
                  const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

                  if (diffDays === 0) return 'Today';
                  if (diffDays === 1) return '1d ago';
                  if (diffDays < 7) return `${diffDays}d ago`;
                  if (diffDays < 30) return `${Math.floor(diffDays / 7)}w ago`;
                  if (diffDays < 365) return `${Math.floor(diffDays / 30)}mo ago`;
                  return `${Math.floor(diffDays / 365)}y ago`;
                })()}
              </time>

              {/* Reading Time */}
              <span>{formatReadingTime(article.reading_time || article.readingTime || 0)}</span>

              {/* Claps with emoji */}
              <span className="flex items-center gap-1">
                <span>👏</span>
                <span>{article.clap_count}</span>
              </span>

              {/* Comments with emoji */}
              <span className="flex items-center gap-1">
                <span>💬</span>
                <span>{article.comment_count}</span>
              </span>

              {/* Tags */}
              {article.tags && (
                <>
                  {(() => {
                    let tagsArray: string[] = [];
                    if (typeof article.tags === 'string') {
                      tagsArray = article.tags.split(',').map(tag => tag.trim()).filter(Boolean);
                    } else if (Array.isArray(article.tags)) {
                      tagsArray = article.tags.map(tag => typeof tag === 'string' ? tag : tag.name);
                    }

                    return tagsArray.slice(0, 2).map((tag: string, index: number) => (
                      <Link
                        key={index}
                        to={`/tag/${tag}`}
                        className="inline-block bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded-full transition-colors"
                        onClick={(e) => e.stopPropagation()}
                      >
                        {tag}
                      </Link>
                    ));
                  })()}
                </>
              )}
            </div>

            {/* Action Buttons */}
            <div className="flex items-center gap-1">
              {/* Dismiss/Hide button */}
              <button
                className="p-2 hover:bg-gray-200 rounded-full transition-colors"
                onClick={(e) => e.preventDefault()}
                title="Not interested"
              >
                <svg className="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <circle cx="12" cy="12" r="10" strokeWidth={2} />
                  <line x1="15" y1="9" x2="9" y2="15" strokeWidth={2} strokeLinecap="round" />
                </svg>
              </button>

              {/* Bookmark button */}
              <button
                className="p-2 hover:bg-gray-200 rounded-full transition-colors"
                onClick={(e) => e.preventDefault()}
                title="Save"
              >
                <svg className="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                </svg>
              </button>

              {/* More options button */}
              <button
                className="p-2 hover:bg-gray-200 rounded-full transition-colors"
                onClick={(e) => e.preventDefault()}
                title="More options"
              >
                <svg className="w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        {/* Featured Image */}
        {article.featured_image_url && (
          <Link to={`/article/${article.id}`} className="flex-shrink-0 hidden sm:block">
            <div className="w-32 h-32 md:w-40 md:h-40">
              <img
                src={article.featured_image_url}
                alt={article.title}
                className="w-full h-full object-cover rounded"
                loading="lazy"
              />
            </div>
          </Link>
        )}
      </div>
    </article>
  );
};

export default ArticleCard;