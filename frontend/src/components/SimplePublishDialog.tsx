import React, { useState, useEffect } from 'react';
import { Draft } from '../hooks/useDraftManager';
import TagInput from './TagInput';
import ImageUpload from './ImageUpload';
import { formatReadingTime, getWordCount } from '../utils/readingTime';
import { apiService } from '../utils/api';

interface SimplePublishDialogProps {
  isOpen: boolean;
  onClose: () => void;
  onConfirm: (publishData: PublishData) => void;
  draft: Draft;
  isPublishing: boolean;
}

interface PublishData {
  title: string;
  featuredImage?: string;
  tags: string[];
  publishOptions: {
    allowComments: boolean;
    includeInSearch: boolean;
    notifyFollowers: boolean;
    publicationId?: number;
  };
}

const SimplePublishDialog: React.FC<SimplePublishDialogProps> = ({
  isOpen,
  onClose,
  onConfirm,
  draft,
  isPublishing
}) => {
  const [publishData, setPublishData] = useState<PublishData>({
    title: draft.title,
    featuredImage: draft.featuredImage,
    tags: draft.tags,
    publishOptions: {
      allowComments: true,
      includeInSearch: true,
      notifyFollowers: true
    }
  });
  
  const [showImageUpload, setShowImageUpload] = useState(false);
  const [userPublications, setUserPublications] = useState<any[]>([]);
  const [loadingPublications, setLoadingPublications] = useState(false);

  // Reset state when dialog opens
  useEffect(() => {
    if (isOpen) {
      setPublishData({
        title: draft.title,
        featuredImage: draft.featuredImage,
        tags: draft.tags,
        publishOptions: {
          allowComments: true,
          includeInSearch: true,
          notifyFollowers: true
        }
      });
      loadUserPublications();
    }
  }, [isOpen, draft]);

  const loadUserPublications = async () => {
    setLoadingPublications(true);
    try {
      const response = await apiService.publications.getMy();
      if (response.data.success) {
        const responseData = response.data.data;
        const allPublications = [
          ...(responseData.owned || []),
          ...(responseData.member || []).filter((pub: any) => 
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

  const handleImageUpload = (imageUrl: string) => {
    setPublishData(prev => ({ ...prev, featuredImage: imageUrl }));
    setShowImageUpload(false);
  };

  const removeImage = () => {
    setPublishData(prev => ({ ...prev, featuredImage: undefined }));
  };

  const handleTagsChange = (tags: string[]) => {
    setPublishData(prev => ({ ...prev, tags }));
  };

  const handlePublishOptionChange = (option: keyof PublishData['publishOptions'], value: boolean | number | undefined) => {
    setPublishData(prev => ({
      ...prev,
      publishOptions: {
        ...prev.publishOptions,
        [option]: value
      }
    }));
  };

  const handlePublish = () => {
    onConfirm(publishData);
  };

  const getPreviewText = (content: string) => {
    const plainText = content.replace(/<[^>]*>/g, '');
    return plainText.length > 200 ? plainText.substring(0, 200) + '...' : plainText;
  };

  const wordCount = getWordCount(draft.content);
  const readingTime = draft.readingTime || 0;

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="p-6 border-b border-gray-200">
          <div className="flex justify-between items-center">
            <h2 className="text-xl font-semibold text-gray-900">
              Publishing to: Medium Clone
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
        </div>

        <div className="p-6">
          {/* Left side - Story Preview */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div>
              <h3 className="text-lg font-medium text-gray-900 mb-4">Story Preview</h3>
              
              {/* Featured Image */}
              <div className="mb-4">
                {publishData.featuredImage ? (
                  <div className="relative">
                    <img
                      src={publishData.featuredImage}
                      alt="Featured"
                      className="w-full h-32 object-cover rounded-lg"
                    />
                    <button
                      onClick={removeImage}
                      className="absolute top-2 right-2 p-1 bg-red-600 text-white rounded-full hover:bg-red-700 text-xs"
                    >
                      ×
                    </button>
                  </div>
                ) : (
                  <div>
                    {showImageUpload ? (
                      <div>
                        <ImageUpload
                          onImageUploaded={handleImageUpload}
                          className="mb-2"
                        />
                        <button
                          onClick={() => setShowImageUpload(false)}
                          className="text-sm text-gray-600 hover:text-gray-800"
                        >
                          Cancel
                        </button>
                      </div>
                    ) : (
                      <button
                        onClick={() => setShowImageUpload(true)}
                        className="w-full p-4 border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-gray-400 hover:text-gray-800 text-sm"
                      >
                        + Add featured image
                      </button>
                    )}
                  </div>
                )}
              </div>

              {/* Title */}
              <input
                type="text"
                value={publishData.title}
                onChange={(e) => setPublishData(prev => ({ ...prev, title: e.target.value }))}
                className="w-full text-lg font-semibold mb-2 p-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500 focus:border-transparent"
                placeholder="Add a title..."
              />



              {/* Content Preview */}
              <p className="text-gray-700 text-sm mb-3 line-clamp-3">
                {getPreviewText(draft.content)}
              </p>

              {/* Stats */}
              <div className="flex items-center text-sm text-gray-500 space-x-4">
                <span>{formatReadingTime(readingTime)}</span>
                <span>{wordCount} words</span>
              </div>
            </div>

            {/* Right side - Publishing Options */}
            <div>
              <h3 className="text-lg font-medium text-gray-900 mb-4">Publishing to: Medium Clone</h3>
              
              {/* Tags */}
              <div className="mb-6">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Add or change topics (up to 5) so readers know what your story is about
                </label>
                <TagInput
                  tags={publishData.tags}
                  onChange={handleTagsChange}
                  maxTags={5}
                  placeholder="Add a topic..."
                />
              </div>

              {/* Publishing Options */}
              <div className="space-y-4 mb-6">
                <label className="flex items-center">
                  <input
                    type="checkbox"
                    checked={publishData.publishOptions.allowComments}
                    onChange={(e) => handlePublishOptionChange('allowComments', e.target.checked)}
                    className="rounded border-gray-300 text-green-600 focus:ring-green-500"
                    disabled={isPublishing}
                  />
                  <span className="ml-2 text-sm text-gray-700">
                    Allow responses and comments
                  </span>
                </label>
                
                <label className="flex items-center">
                  <input
                    type="checkbox"
                    checked={publishData.publishOptions.notifyFollowers}
                    onChange={(e) => handlePublishOptionChange('notifyFollowers', e.target.checked)}
                    className="rounded border-gray-300 text-green-600 focus:ring-green-500"
                    disabled={isPublishing}
                  />
                  <span className="ml-2 text-sm text-gray-700">
                    Notify your followers when you publish
                  </span>
                </label>
              </div>

              {/* Publication Selection */}
              {userPublications.length > 0 && (
                <div className="mb-6">
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Publish to
                  </label>
                  <div className="space-y-2">
                    <label className="flex items-center">
                      <input
                        type="radio"
                        name="publication"
                        checked={!publishData.publishOptions.publicationId}
                        onChange={() => handlePublishOptionChange('publicationId', undefined)}
                        className="border-gray-300 text-green-600 focus:ring-green-500"
                        disabled={isPublishing}
                      />
                      <span className="ml-2 text-sm text-gray-700">
                        Your profile
                      </span>
                    </label>
                    
                    {loadingPublications ? (
                      <div className="flex items-center text-sm text-gray-500">
                        <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-green-600 mr-2"></div>
                        Loading publications...
                      </div>
                    ) : (
                      userPublications.map((publication) => (
                        <label key={publication.id} className="flex items-center">
                          <input
                            type="radio"
                            name="publication"
                            checked={publishData.publishOptions.publicationId === publication.id}
                            onChange={() => handlePublishOptionChange('publicationId', publication.id)}
                            className="border-gray-300 text-green-600 focus:ring-green-500"
                            disabled={isPublishing}
                          />
                          <div className="ml-2 flex items-center">
                            {publication.logo_url ? (
                              <img
                                src={publication.logo_url}
                                alt={publication.name}
                                className="w-5 h-5 rounded object-cover mr-2"
                              />
                            ) : (
                              <div className="w-5 h-5 rounded bg-gray-200 flex items-center justify-center mr-2">
                                <span className="text-xs font-medium text-gray-500">
                                  {publication.name.charAt(0).toUpperCase()}
                                </span>
                              </div>
                            )}
                            <span className="text-sm text-gray-700">{publication.name}</span>
                          </div>
                        </label>
                      ))
                    )}
                  </div>
                </div>
              )}
            </div>
          </div>

          {/* Action Buttons */}
          <div className="flex justify-end items-center mt-8 pt-6 border-t border-gray-200">
            <div className="flex space-x-3">
              <button
                onClick={onClose}
                className="px-6 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50"
                disabled={isPublishing}
              >
                Cancel
              </button>
              
              <button
                onClick={handlePublish}
                disabled={isPublishing}
                className="px-8 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 flex items-center font-medium"
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
      </div>
    </div>
  );
};

export default SimplePublishDialog;