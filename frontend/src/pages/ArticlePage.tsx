import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { apiService } from '../utils/api';
import { Article, Tag } from '../types';
import ArticleHeader from '../components/ArticleHeader';
import ArticleContent from '../components/ArticleContent';
import TableOfContents from '../components/TableOfContents';
import ReadingProgress from '../components/ReadingProgress';
import BackToTop from '../components/BackToTop';
import RelatedArticles from '../components/RelatedArticles';
import MoreFromAuthor from '../components/MoreFromAuthor';

import ClapButton from '../components/ClapButton';
import CommentSection from '../components/CommentSection';
import BookmarkButton from '../components/BookmarkButton';
import FollowButton from '../components/FollowButton';
import ReportDialog from '../components/ReportDialog';
import { generateArticleSEO, updateDocumentSEO, generateBreadcrumbStructuredData } from '../utils/seo';
import { useArticleAnalytics } from '../hooks/useArticleAnalytics';
import SEOHead from '../components/SEOHead';

interface ArticlePageProps { }

const ArticlePage: React.FC<ArticlePageProps> = () => {
  const { id } = useParams<{ id: string }>();
  const [article, setArticle] = useState<Article | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [clapCount, setClapCount] = useState(0);
  const [showReportDialog, setShowReportDialog] = useState(false);

  // Analytics tracking
  const { analytics } = useArticleAnalytics({
    articleId: id || '',
    readingTime: article?.reading_time || article?.readingTime || 0
  });

  useEffect(() => {
    if (id) {
      fetchArticle(id);
    }
  }, [id]);

  // Update SEO metadata when article loads
  useEffect(() => {
    if (article) {
      setClapCount(article.clap_count || 0);
    }
  }, [article]);

  const fetchArticle = async (articleId: string) => {
    try {
      setLoading(true);
      setError(null);
      const response = await apiService.articles.getById(articleId);
      if (response.data.success && response.data.data) {
        setArticle(response.data.data as Article);
      } else {
        setError('Article not found');
      }
    } catch (err: any) {
      setError(err.message || 'Failed to load article');
    } finally {
      setLoading(false);
    }
  };

  const handleShare = async (platform: string) => {
    if (!article) return;

    const url = window.location.href;
    const title = article.title;

    let shareUrl = '';

    switch (platform) {
      case 'twitter':
        shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(title)}&url=${encodeURIComponent(url)}`;
        break;
      case 'facebook':
        shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
        break;
      case 'linkedin':
        shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(url)}`;
        break;
      case 'copy':
        try {
          await navigator.clipboard.writeText(url);
          // TODO: Add toast notification for better UX
          alert('Link copied to clipboard!');
        } catch (err) {
          // Fallback for older browsers
          const textArea = document.createElement('textarea');
          textArea.value = url;
          textArea.style.position = 'fixed';
          textArea.style.left = '-999999px';
          textArea.style.top = '-999999px';
          document.body.appendChild(textArea);
          textArea.focus();
          textArea.select();
          try {
            document.execCommand('copy');
            alert('Link copied to clipboard!');
          } catch (copyErr) {
            alert('Failed to copy link. Please copy manually: ' + url);
          }
          document.body.removeChild(textArea);
        }
        return;
    }

    if (shareUrl) {
      window.open(shareUrl, '_blank', 'width=600,height=400,scrollbars=yes,resizable=yes');
    }
  };

  const handleClapUpdate = (totalClaps: number, userClaps: number) => {
    setClapCount(totalClaps);
    // Optionally update the article state as well
    if (article) {
      setArticle({
        ...article,
        clap_count: totalClaps
      });
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-white">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <div className="animate-pulse">
            <div className="h-8 bg-gray-200 rounded mb-4"></div>
            <div className="h-4 bg-gray-200 rounded mb-2"></div>
            <div className="h-4 bg-gray-200 rounded mb-8"></div>
            <div className="space-y-3">
              <div className="h-4 bg-gray-200 rounded"></div>
              <div className="h-4 bg-gray-200 rounded"></div>
              <div className="h-4 bg-gray-200 rounded w-3/4"></div>
            </div>
          </div>
        </div>
      </div>
    );
  }

  if (error || !article) {
    return (
      <div className="min-h-screen bg-white">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <div className="text-center py-16">
            <h1 className="text-2xl font-bold text-gray-900 mb-4">
              {error || 'Article not found'}
            </h1>
            <Link
              to="/"
              className="text-blue-600 hover:text-blue-800 underline"
            >
              Return to homepage
            </Link>
          </div>
        </div>
      </div>
    );
  }

  // Generate SEO data for the article
  const seoData = article ? (() => {
    const seoMetadata = generateArticleSEO({
      title: article.title,
      content: typeof article.content === 'string' ? article.content : JSON.stringify(article.content),
      tags: Array.isArray(article.tags) ? article.tags.map(tag =>
        typeof tag === 'string' ? tag : tag.name
      ) : [],
      featuredImage: article.featured_image_url,
      author: {
        name: article.username || 'Unknown Author',
        username: article.username || 'unknown'
      },
      publishedAt: article.published_at || article.created_at,
      slug: article.slug,
      readingTime: article.reading_time || article.readingTime
    });

    // Generate breadcrumb structured data
    const breadcrumbData = generateBreadcrumbStructuredData([
      { name: 'Home', url: window.location.origin },
      { name: 'Articles', url: `${window.location.origin}/articles` },
      { name: article.title, url: window.location.href }
    ]);

    return {
      ...seoMetadata,
      structuredData: [seoMetadata.structuredData, breadcrumbData]
    };
  })() : null;

  return (
    <div className="min-h-screen bg-white">
      {/* SEO Head */}
      {seoData && (
        <SEOHead
          title={seoData.title}
          description={seoData.description}
          keywords={seoData.keywords}
          ogImage={seoData.ogImage}
          canonicalUrl={seoData.canonicalUrl}
          structuredData={seoData.structuredData}
          preloadResources={[
            ...(article?.featured_image_url ? [{ href: article.featured_image_url, as: 'image' }] : [])
          ]}
          dnsPrefetch={[
            'https://fonts.googleapis.com',
            'https://fonts.gstatic.com'
          ]}
        />
      )}

      {/* Reading Progress Bar */}
      <ReadingProgress className="fixed top-0 left-0 w-full z-50 bg-gray-200" />

      {/* Article Header */}
      <ArticleHeader article={article} onShare={handleShare} />

      {/* Featured Image */}
      {article.featured_image_url && (
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
          <img
            src={article.featured_image_url}
            alt={article.title}
            className="w-full h-auto rounded-lg shadow-sm"
            loading="lazy"
          />
        </div>
      )}

      {/* Mobile Table of Contents */}
      <div className="lg:hidden max-w-4xl mx-auto px-4 sm:px-6 py-4">
        <details className="bg-gray-50 rounded-lg p-4 border border-gray-200">
          <summary className="cursor-pointer text-sm font-medium text-gray-900 flex items-center space-x-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded">
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 10h16M4 14h16M4 18h16" />
            </svg>
            <span>Table of Contents</span>
          </summary>
          <div className="mt-3">
            <TableOfContents content={article.content} />
          </div>
        </details>
      </div>

      {/* Main Content Area */}
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Table of Contents - Desktop Sidebar (Fixed Position) */}
        <aside className="hidden lg:block fixed left-4 top-24 w-64 z-10">
          <div className="sticky top-24">
            <TableOfContents
              content={article.content}
              className="bg-white p-4 rounded-lg shadow-lg border border-gray-200"
            />
          </div>
        </aside>

        {/* Article Content - Centered */}
        <main className="pb-16">
          {/* Article Content */}
          <div className="bg-white">
            <ArticleContent content={article.content} />
          </div>

          {/* Tags Section */}
          {article.tags && article.tags.length > 0 && (
            <div className="mt-16 pt-12 border-t border-gray-200">
              <h3 className="text-lg font-semibold text-gray-900 mb-6">Tags</h3>
              <div className="flex flex-wrap gap-3">
                {(Array.isArray(article.tags) ? article.tags : []).map((tag: Tag | string, index: number) => (
                  <Link
                    key={index}
                    to={`/tag/${typeof tag === 'string' ? tag : tag.slug}`}
                    className="inline-block bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 sm:px-5 sm:py-3 rounded-full text-sm sm:text-base font-medium transition-colors hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                  >
                    {typeof tag === 'string' ? tag : tag.name}
                  </Link>
                ))}
              </div>
            </div>
          )}

          {/* Engagement Bar */}
          <div className="mt-12 pt-12 border-t border-gray-200">
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-8">
              {/* Engagement Actions */}
              <div className="flex items-center gap-6">
                {/* Clap Button */}
                <ClapButton
                  articleId={article.id}
                  initialClapCount={clapCount}
                  onClapUpdate={handleClapUpdate}
                  className="flex-shrink-0"
                />

                {/* Bookmark Button */}
                <BookmarkButton
                  articleId={article.id}
                  className="flex-shrink-0"
                />

                {/* Follow Button */}
                <FollowButton
                  userId={article.author_id}
                  username={article.username}
                  size="sm"
                  className="flex-shrink-0"
                />

                {/* Report Button */}
                <button
                  onClick={() => setShowReportDialog(true)}
                  className="flex items-center space-x-1 text-gray-500 hover:text-red-600 transition-colors"
                  title="Report this article"
                >
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                  </svg>
                  <span className="text-sm">Report</span>
                </button>

                {/* Other engagement stats */}
                <div className="flex items-center gap-4 text-sm text-gray-500">
                  <span className="flex items-center space-x-1">
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    <span>{article.comment_count.toLocaleString()} comments</span>
                  </span>
                </div>
              </div>

              {/* Article Stats */}
              <div className="flex flex-col sm:flex-row sm:items-center gap-4 text-sm text-gray-500">
                <span className="flex items-center space-x-1">
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  <span>{article.view_count.toLocaleString()} views</span>
                </span>
                <time dateTime={article.updated_at} className="text-xs text-gray-400">
                  Last updated {new Date(article.updated_at).toLocaleDateString()}
                </time>
              </div>
            </div>
          </div>

          {/* More from Author Section */}
          <MoreFromAuthor
            currentArticle={article}
            className="mt-20 pt-12 border-t border-gray-200"
          />

          {/* Comments Section */}
          <CommentSection
            articleId={article.id}
            className="mt-20"
          />
        </main>
      </div>

      {/* Related Articles Section */}
      <div className="bg-gray-50 py-12 sm:py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <RelatedArticles currentArticle={article} />
        </div>
      </div>

      {/* Back to Top Button */}
      <BackToTop />

      {/* Report Dialog */}
      <ReportDialog
        isOpen={showReportDialog}
        onClose={() => setShowReportDialog(false)}
        contentType="article"
        contentId={article.id}
        onReportSubmitted={() => {
          setShowReportDialog(false);
          // Optionally show a success message
        }}
      />
    </div>
  );
};

export default ArticlePage;