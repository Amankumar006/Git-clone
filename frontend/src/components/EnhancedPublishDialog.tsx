import React, { useState, useEffect } from 'react';
import { Draft } from '../hooks/useDraftManager';
import TagInput from './TagInput';
import ImageUpload from './ImageUpload';
import { formatReadingTime, getWordCount } from '../utils/readingTime';
import { generateSlug } from '../utils/seo';
import { apiService } from '../utils/api';

interface EnhancedPublishDialogProps {
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

const EnhancedPublishDialog: React.FC<EnhancedPublishDialogProps> = ({
  isOpen,
  onClose,
  onConfirm,
  draft,
  isPublishing
}) => {
  const [currentStep, setCurrentStep] = useState(1);
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
      setCurrentStep(1);
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
        const allPublications = [
          ...response.data.data.owned,
          ...response.data.data.member.filter((pub: any) =>
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

  const handleNext = () => {
    if (currentStep < 3) {
      setCurrentStep(currentStep + 1);
    }
  };

  const handleBack = () => {
    if (currentStep > 1) {
      setCurrentStep(currentStep - 1);
    }
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
  const articleUrl = `${window.location.origin}/article/${generateSlug(publishData.title)}`;

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="p-6 border-b border-gray-200">
          <div className="flex justify-between items-center">
            <h2 className="text-2xl font-bold text-gray-900">
              {currentStep === 1 && 'Story Preview'}
              {currentStep === 2 && 'Add details'}
              {currentStep === 3 && 'Publishing to: Medium Clone'}
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

          {/* Progress Steps */}
          <div className="flex items-center mt-4 space-x-4">
            {[1, 2, 3].map((step) => (
              <div key={step} className="flex items-center">
                <div className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium ${step <= currentStep
                  ? 'bg-green-600 text-white'
                  : 'bg-gray-200 text-gray-600'
                  }`}>
                  {step}
                </div>
                {step < 3 && (
                  <div className={`w-12 h-0.5 ml-2 ${step < currentStep ? 'bg-green-600' : 'bg-gray-200'
                    }`} />
                )}
              </div>
            ))}
          </div>
        </div>

        {/* Step 1: Story Preview */}
        {currentStep === 1 && (
          <div className="p-6">
            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Preview
              </label>
              <div className="border rounded-lg p-4 bg-gray-50">
                {publishData.featuredImage && (
                  <img
                    src={publishData.featuredImage}
                    alt="Featured"
                    className="w-full h-48 object-cover rounded-lg mb-4"
                  />
                )}

                <h3 className="text-xl font-bold text-gray-900 mb-2">
                  {publishData.title}
                </h3>


                <p className="text-gray-700 mb-4">
                  {getPreviewText(draft.content)}
                </p>

                <div className="flex items-center justify-between text-sm text-gray-500">
                  <div className="flex items-center space-x-4">
                    <span>{formatReadingTime(readingTime)}</span>
                    <span>{wordCount} words</span>
                  </div>
                  {publishData.tags.length > 0 && (
                    <div className="flex space-x-1">
                      {publishData.tags.slice(0, 3).map((tag, index) => (
                        <span key={index} className="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                          {tag}
                        </span>
                      ))}
                      {publishData.tags.length > 3 && (
                        <span className="px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs">
                          +{publishData.tags.length - 3}
                        </span>
                      )}
                    </div>
                  )}
                </div>
              </div>
            </div>

            <div className="flex justify-end">
              <button
                onClick={handleNext}
                className="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
              >
                Continue
              </button>
            </div>
          </div>
        )}

        {/* Step 2: Add Details */}
        {currentStep === 2 && (
          <div className="p-6 space-y-6">
            {/* Featured Image */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Add or change featured image
              </label>
              {publishData.featuredImage ? (
                <div className="relative">
                  <img
                    src={publishData.featuredImage}
                    alt="Featured"
                    className="w-full h-48 object-cover rounded-lg"
                  />
                  <button
                    onClick={removeImage}
                    className="absolute top-2 right-2 p-2 bg-red-600 text-white rounded-full hover:bg-red-700"
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
                        className="mb-4"
                      />
                      <button
                        onClick={() => setShowImageUpload(false)}
                        className="text-gray-600 hover:text-gray-800"
                      >
                        Cancel
                      </button>
                    </div>
                  ) : (
                    <button
                      onClick={() => setShowImageUpload(true)}
                      className="w-full p-8 border-2 border-dashed border-gray-300 rounded-lg text-gray-600 hover:border-gray-400 hover:text-gray-800"
                    >
                      + Add featured image
                    </button>
                  )}
                </div>
              )}
            </div>

            {/* Title */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Title
              </label>
              <input
                type="text"
                value={publishData.title}
                onChange={(e) => setPublishData(prev => ({ ...prev, title: e.target.value }))}
                className="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                placeholder="Add a title..."
              />
            </div>



            {/* Tags */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Tags (up to 5)
              </label>
              <TagInput
                tags={publishData.tags}
                onChange={handleTagsChange}
                maxTags={5}
                placeholder="Add tags to help readers find your story..."
              />
            </div>

            <div className="flex justify-between">
              <button
                onClick={handleBack}
                className="px-6 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50"
              >
                Back
              </button>
              <button
                onClick={handleNext}
                className="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
              >
                Continue
              </button>
            </div>
          </div>
        )}

        {/* Step 3: Publishing Options */}
        {currentStep === 3 && (
          <div className="p-6 space-y-6">
            {/* Publishing Options */}
            <div>
              <h4 className="text-sm font-medium text-gray-700 mb-3">
                Publishing options
              </h4>
              <div className="space-y-3">
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
                    checked={publishData.publishOptions.includeInSearch}
                    onChange={(e) => handlePublishOptionChange('includeInSearch', e.target.checked)}
                    className="rounded border-gray-300 text-green-600 focus:ring-green-500"
                    disabled={isPublishing}
                  />
                  <span className="ml-2 text-sm text-gray-700">
                    Include in search results
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
                    Send notification to followers
                  </span>
                </label>
              </div>
            </div>

            {/* Publication Selection */}
            {userPublications.length > 0 && (
              <div>
                <h4 className="text-sm font-medium text-gray-700 mb-3">
                  Publish to
                </h4>
                <div className="space-y-3">
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
                              className="w-6 h-6 rounded object-cover mr-2"
                            />
                          ) : (
                            <div className="w-6 h-6 rounded bg-gray-200 flex items-center justify-center mr-2">
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

            {/* Article URL Preview */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Story URL
              </label>
              <div className="p-3 bg-gray-100 rounded-lg">
                <span className="text-sm text-gray-600 break-all">
                  {articleUrl}
                </span>
              </div>
            </div>

            <div className="flex justify-between">
              <button
                onClick={handleBack}
                className="px-6 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50"
                disabled={isPublishing}
              >
                Back
              </button>
              <button
                onClick={handlePublish}
                disabled={isPublishing}
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
        )}
      </div>
    </div>
  );
};

export default EnhancedPublishDialog;