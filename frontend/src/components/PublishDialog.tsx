import React, { useState, useEffect } from 'react';
import { Draft } from '../hooks/useDraftManager';
import { formatReadingTime } from '../utils/readingTime';
import { generateSlug, generateArticleSEO, updateDocumentSEO } from '../utils/seo';
import { apiService } from '../utils/api';

interface PublishDialogProps {
  isOpen: boolean;
  onClose: () => void;
  onConfirm: (options: PublishingOptions) => void;
  draft: Draft;
  isPublishing: boolean;
}

interface PublishingOptions {
  allowComments: boolean;
  includeInSearch: boolean;
  notifyFollowers: boolean;
  publicationId?: number;
  submitToPublication?: boolean;
}

interface ArticlePreview {
  preview: Draft & {
    slug: string;
    word_count?: number;
  };
  seo_preview?: any;
  social_preview: {
    title: string;
    description: string;
    image?: string;
    url: string;
  };
}

const PublishDialog: React.FC<PublishDialogProps> = ({
  isOpen,
  onClose,
  onConfirm,
  draft,
  isPublishing
}) => {
  const [showPreview, setShowPreview] = useState(false);
  const [publishingOptions, setPublishingOptions] = useState<PublishingOptions>({
    allowComments: true,
    includeInSearch: true,
    notifyFollowers: true
  });
  const [articlePreview, setArticlePreview] = useState<ArticlePreview | null>(null);
  const [isLoadingPreview, setIsLoadingPreview] = useState(false);
  const [userPublications, setUserPublications] = useState<any[]>([]);
  const [loadingPublications, setLoadingPublications] = useState(false);

  // Load article preview data when dialog opens
  useEffect(() => {
    if (isOpen && draft.id) {
      loadArticlePreview();
    }
  }, [isOpen, draft.id]);

  // Load user publications when dialog opens
  useEffect(() => {
    if (isOpen) {
      loadUserPublications();
    }
  }, [isOpen]);

  const loadUserPublications = async () => {
    setLoadingPublications(true);
    try {
      const response = await apiService.get('/publications/my');
      if (response.success) {
        // Combine owned and member publications where user can submit
        const data = response.data as any;
        const allPublications = [
          ...data.owned,
          ...data.member.filter((pub: any) => 
            pub.user_role === 'admin' || pub.user_role === 'editor' || pub.user_role === 'writer'
          )
        ];
        setUserPublications(allPublications);
      }
    } catch (error) {
      console.error('Failed to load publications:', error);
    } finally {
      setLoadingPublications(false);
    }
  };

  const loadArticlePreview = async () => {
    if (!draft.id) return;
    
    setIsLoadingPreview(true);
    try {
      const response = await apiService.get(`/articles/preview/${draft.id}`);
      setArticlePreview(response.data as ArticlePreview);
    } catch (error) {
      console.error('Failed to load article preview:', error);
      // Fallback to generating preview from draft data
      generateFallbackPreview();
    } finally {
      setIsLoadingPreview(false);
    }
  };

  const generateFallbackPreview = () => {
    const slug = generateSlug(draft.title);
    const articleUrl = `${window.location.origin}/article/${slug}`;
    
    setArticlePreview({
      preview: {
        ...draft,
        slug,
        word_count: getWordCount(draft.content)
      },
      seo_preview: generateArticleSEO({
        title: draft.title,
        content: draft.content,
        tags: draft.tags,
        featuredImage: draft.featuredImage,
        author: {
          name: 'Current User', // This would come from auth context
          username: 'current-user'
        },
        publishedAt: new Date().toISOString(),
        slug
      }),
      social_preview: {
        title: draft.title,
        description: getPreviewText(draft.content),
        image: draft.featuredImage,
        url: articleUrl
      }
    });
  };

  const handlePublishingOptionChange = (option: keyof PublishingOptions, value: boolean | number | undefined) => {
    setPublishingOptions(prev => ({
      ...prev,
      [option]: value
    }));
  };

  const handleConfirm = () => {
    onConfirm(publishingOptions);
  };

  if (!isOpen) return null;

  const getPreviewText = (content: string) => {
    // Remove HTML tags and get first 200 characters
    const plainText = content.replace(/<[^>]*>/g, '');
    return plainText.length > 200 ? plainText.substring(0, 200) + '...' : plainText;
  };

  const getWordCount = (content: string): number => {
    const plainText = content.replace(/<[^>]*>/g, '');
    return plainText.split(/\s+/).filter(word => word.length > 0).length;
  };

  const currentPreview = articlePreview?.preview || draft;
  const articleUrl = articlePreview?.social_preview?.url || `${window.location.origin}/article/${generateSlug(draft.title)}`;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div className="p-6">
          <div className="flex justify-between items-center mb-6">
            <h2 className="text-2xl font-bold text-gray-900">
              Ready to publish?
            </h2>
            <button
              onClick={onClose}
              className="text-gray-400 hover:text-gray-600"
              disabled={isPublishing}
            >
              <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          {/* Loading State */}
          {isLoadingPreview && (
            <div className="mb-6 flex items-center justify-center p-8">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
              <span className="ml-2 text-gray-600">Loading preview...</span>
            </div>
          )}

          {/* Article Preview */}
          {!isLoadingPreview && (
            <div className="mb-6">
              <div className="border rounded-lg p-4 bg-gray-50">
                {currentPreview.featuredImage && (
                  <img
                    src={currentPreview.featuredImage}
                    alt="Featured"
                    className="w-full h-48 object-cover rounded-lg mb-4"
                  />
                )}
                
                <h3 className="text-xl font-bold text-gray-900 mb-2">
                  {currentPreview.title}
                </h3>
                

                
                <p className="text-gray-700 mb-4">
                  {getPreviewText(currentPreview.content)}
                </p>
                
                <div className="flex items-center justify-between text-sm text-gray-500">
                  <div className="flex items-center space-x-4">
                    <span>{formatReadingTime(currentPreview.readingTime || 0)}</span>
                    {articlePreview?.preview?.word_count && (
                      <span>{articlePreview.preview?.word_count} words</span>
                    )}
                    {currentPreview.tags.length > 0 && (
                      <div className="flex space-x-1">
                        {currentPreview.tags.map((tag, index) => (
                          <span key={index} className="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                            {tag}
                          </span>
                        ))}
                      </div>
                    )}
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Article URL */}
          <div className="mb-6">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Article URL
            </label>
            <div className="flex items-center p-3 bg-gray-100 rounded-lg">
              <span className="text-gray-600 text-sm break-all">
                {articleUrl}
              </span>
            </div>
            <p className="text-xs text-gray-500 mt-1">
              This URL is generated from your article title
            </p>
          </div>

          {/* SEO Preview */}
          {!isLoadingPreview && (
            <div className="mb-6">
              <h4 className="text-sm font-medium text-gray-700 mb-3">
                How this will appear in search results:
              </h4>
              <div className="border rounded-lg p-4">
                <div className="text-blue-600 text-lg hover:underline cursor-pointer">
                  {articlePreview?.seo_preview?.title || currentPreview.title}
                </div>
                <div className="text-green-700 text-sm">
                  {articleUrl}
                </div>
                <div className="text-gray-600 text-sm mt-1">
                  {articlePreview?.seo_preview?.description || getPreviewText(currentPreview.content)}
                </div>
              </div>
            </div>
          )}

          {/* Social Media Preview */}
          {!isLoadingPreview && (
            <div className="mb-6">
              <h4 className="text-sm font-medium text-gray-700 mb-3">
                Social media preview:
              </h4>
              <div className="border rounded-lg overflow-hidden">
                {(articlePreview?.social_preview?.image || currentPreview.featuredImage) && (
                  <img
                    src={articlePreview?.social_preview?.image || currentPreview.featuredImage}
                    alt="Social preview"
                    className="w-full h-32 object-cover"
                  />
                )}
                <div className="p-4">
                  <div className="font-semibold text-gray-900 mb-1">
                    {articlePreview?.social_preview?.title || currentPreview.title}
                  </div>
                  <div className="text-gray-600 text-sm mb-2">
                    {articlePreview?.social_preview?.description || getPreviewText(currentPreview.content)}
                  </div>
                  <div className="text-gray-500 text-xs">
                    {window.location.hostname}
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Publishing Options */}
          <div className="mb-6">
            <h4 className="text-sm font-medium text-gray-700 mb-3">
              Publishing options:
            </h4>
            <div className="space-y-3">
              <label className="flex items-center">
                <input
                  type="checkbox"
                  checked={publishingOptions.allowComments}
                  onChange={(e) => handlePublishingOptionChange('allowComments', e.target.checked)}
                  className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  disabled={isPublishing}
                />
                <span className="ml-2 text-sm text-gray-700">
                  Allow responses and comments
                </span>
              </label>
              
              <label className="flex items-center">
                <input
                  type="checkbox"
                  checked={publishingOptions.includeInSearch}
                  onChange={(e) => handlePublishingOptionChange('includeInSearch', e.target.checked)}
                  className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  disabled={isPublishing}
                />
                <span className="ml-2 text-sm text-gray-700">
                  Include in search results
                </span>
              </label>
              
              <label className="flex items-center">
                <input
                  type="checkbox"
                  checked={publishingOptions.notifyFollowers}
                  onChange={(e) => handlePublishingOptionChange('notifyFollowers', e.target.checked)}
                  className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  disabled={isPublishing}
                />
                <span className="ml-2 text-sm text-gray-700">
                  Send notification to followers
                </span>
              </label>
            </div>
          </div>

          {/* Publication Selection */}
          {userPublications.length > 0 && (
            <div className="mb-6">
              <h4 className="text-sm font-medium text-gray-700 mb-3">
                Publication:
              </h4>
              <div className="space-y-3">
                <label className="flex items-center">
                  <input
                    type="radio"
                    name="publication"
                    checked={!publishingOptions.publicationId}
                    onChange={() => handlePublishingOptionChange('publicationId', undefined)}
                    className="border-gray-300 text-blue-600 focus:ring-blue-500"
                    disabled={isPublishing}
                  />
                  <span className="ml-2 text-sm text-gray-700">
                    Publish to your personal profile
                  </span>
                </label>
                
                {loadingPublications ? (
                  <div className="flex items-center text-sm text-gray-500">
                    <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mr-2"></div>
                    Loading publications...
                  </div>
                ) : (
                  userPublications.map((publication) => (
                    <label key={publication.id} className="flex items-center">
                      <input
                        type="radio"
                        name="publication"
                        checked={publishingOptions.publicationId === publication.id}
                        onChange={() => handlePublishingOptionChange('publicationId', publication.id)}
                        className="border-gray-300 text-blue-600 focus:ring-blue-500"
                        disabled={isPublishing}
                      />
                      <div className="ml-2 flex items-center">
                        {publication.logo_url ? (
                          <img
                            src={publication.logo_url}
                            alt={publication.name}
                            className="w-6 h-6 rounded object-cover mr-2"
                          />
                        ) : (
                          <div className="w-6 h-6 rounded bg-gray-200 flex items-center justify-center mr-2">
                            <span className="text-xs font-medium text-gray-500">
                              {publication.name.charAt(0).toUpperCase()}
                            </span>
                          </div>
                        )}
                        <div>
                          <span className="text-sm text-gray-700">{publication.name}</span>
                          {publication.user_role && (
                            <span className="ml-2 px-2 py-0.5 text-xs bg-blue-100 text-blue-800 rounded">
                              {publication.user_role}
                            </span>
                          )}
                        </div>
                      </div>
                    </label>
                  ))
                )}
                
                {publishingOptions.publicationId && (
                  <div className="ml-6 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                    <p className="text-sm text-yellow-800">
                      <strong>Note:</strong> This article will be submitted for review and must be approved by a publication admin or editor before it's published.
                    </p>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* Action Buttons */}
          <div className="flex justify-between items-center">
            <button
              onClick={() => setShowPreview(!showPreview)}
              className="text-blue-600 hover:text-blue-800"
              disabled={isPublishing}
            >
              {showPreview ? 'Hide' : 'Show'} full preview
            </button>
            
            <div className="flex space-x-3">
              <button
                onClick={onClose}
                className="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50"
                disabled={isPublishing}
              >
                Cancel
              </button>
              
              <button
                onClick={handleConfirm}
                disabled={isPublishing || isLoadingPreview}
                className="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 flex items-center"
              >
                {isPublishing ? (
                  <>
                    <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                    Publishing...
                  </>
                ) : (
                  'Publish now'
                )}
              </button>
            </div>
          </div>
        </div>

        {/* Full Preview Modal */}
        {showPreview && !isLoadingPreview && (
          <div className="border-t p-6 bg-gray-50">
            <h4 className="text-lg font-semibold mb-4">Full Article Preview</h4>
            <div className="bg-white rounded-lg p-6 max-h-96 overflow-y-auto">
              {currentPreview.featuredImage && (
                <img
                  src={currentPreview.featuredImage}
                  alt="Featured"
                  className="w-full h-64 object-cover rounded-lg mb-6"
                />
              )}
              
              <h1 className="text-3xl font-bold text-gray-900 mb-4">
                {currentPreview.title}
              </h1>
              

              
              <div 
                className="prose prose-lg max-w-none"
                dangerouslySetInnerHTML={{ __html: currentPreview.content }}
              />
              
              {/* Article metadata */}
              <div className="mt-6 pt-6 border-t border-gray-200">
                <div className="flex items-center justify-between text-sm text-gray-500">
                  <div className="flex items-center space-x-4">
                    <span>{formatReadingTime(currentPreview.readingTime || 0)}</span>
                    {articlePreview?.preview?.word_count && (
                      <span>{articlePreview.preview?.word_count} words</span>
                    )}
                  </div>
                  <div className="flex space-x-1">
                    {currentPreview.tags.map((tag, index) => (
                      <span key={index} className="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                        {tag}
                      </span>
                    ))}
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default PublishDialog;