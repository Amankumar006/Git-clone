import React, { useState, useEffect } from 'react';
import { Publication } from '../types';
import { apiService } from '../utils/api';

interface Guideline {
  id: number;
  title: string;
  content: string;
  category: string;
  is_required: boolean;
}

interface Template {
  id: number;
  name: string;
  description?: string;
  template_content: any;
  is_default: boolean;
}

interface ArticleSubmissionDialogProps {
  isOpen: boolean;
  onClose: () => void;
  onSubmit: (publicationId: number, options?: { templateId?: number; acknowledgedGuidelines?: number[] }) => void;
  articleId: number;
  isSubmitting?: boolean;
}

const ArticleSubmissionDialog: React.FC<ArticleSubmissionDialogProps> = ({
  isOpen,
  onClose,
  onSubmit,
  articleId,
  isSubmitting = false
}) => {
  const [publications, setPublications] = useState<Publication[]>([]);
  const [selectedPublicationId, setSelectedPublicationId] = useState<number | null>(null);
  const [loading, setLoading] = useState(false);
  const [guidelines, setGuidelines] = useState<Guideline[]>([]);
  const [templates, setTemplates] = useState<Template[]>([]);
  const [selectedTemplateId, setSelectedTemplateId] = useState<number | null>(null);
  const [acknowledgedGuidelines, setAcknowledgedGuidelines] = useState<Set<number>>(new Set());
  const [showGuidelines, setShowGuidelines] = useState(false);
  const [showTemplates, setShowTemplates] = useState(false);
  const [complianceCheck, setComplianceCheck] = useState<any>(null);

  useEffect(() => {
    if (isOpen) {
      loadUserPublications();
    }
  }, [isOpen]);

  useEffect(() => {
    if (selectedPublicationId) {
      loadPublicationData(selectedPublicationId);
    } else {
      setGuidelines([]);
      setTemplates([]);
      setSelectedTemplateId(null);
      setAcknowledgedGuidelines(new Set());
      setComplianceCheck(null);
    }
  }, [selectedPublicationId]);

  const loadUserPublications = async () => {
    setLoading(true);
    try {
      const response = await apiService.publications.getMy();
      if (response.data.success) {
        // Get publications where user can submit articles
        const eligiblePublications = [
          ...response.data.data.owned,
          ...response.data.data.member.filter((pub: any) => 
            pub.user_role === 'admin' || pub.user_role === 'editor' || pub.user_role === 'writer'
          )
        ];
        setPublications(eligiblePublications);
      }
    } catch (error) {
      console.error('Failed to load publications:', error);
    } finally {
      setLoading(false);
    }
  };

  const loadPublicationData = async (publicationId: number) => {
    try {
      // Load guidelines
      const guidelinesResponse = await apiService.workflow.getGuidelines(publicationId);
      if (guidelinesResponse.data.success) {
        const allGuidelines = Object.values(guidelinesResponse.data.data.guidelines).flat() as Guideline[];
        setGuidelines(allGuidelines);
      }

      // Load templates
      const templatesResponse = await apiService.workflow.getTemplates(publicationId);
      if (templatesResponse.data.success) {
        setTemplates(templatesResponse.data.data.templates);
        // Auto-select default template
        const defaultTemplate = templatesResponse.data.data.templates.find((t: Template) => t.is_default);
        if (defaultTemplate) {
          setSelectedTemplateId(defaultTemplate.id);
        }
      }

      // Check article compliance
      await checkArticleCompliance(publicationId);
    } catch (error) {
      console.error('Failed to load publication data:', error);
    }
  };

  const checkArticleCompliance = async (publicationId: number) => {
    try {
      // Get article data for compliance check
      const articleResponse = await apiService.articles.getById(articleId.toString());
      if (articleResponse.data.success) {
        const articleData = articleResponse.data.data;
        
        const complianceResponse = await apiService.workflow.checkCompliance({
          publication_id: publicationId,
          article_data: {
            title: articleData.title,
            content: articleData.content,
            tags: articleData.tags
          }
        });

        if (complianceResponse.data.success) {
          setComplianceCheck(complianceResponse.data.data);
        }
      }
    } catch (error) {
      console.error('Failed to check compliance:', error);
    }
  };

  const handleGuidelineAcknowledge = (guidelineId: number) => {
    setAcknowledgedGuidelines(prev => {
      const newSet = new Set(prev);
      if (newSet.has(guidelineId)) {
        newSet.delete(guidelineId);
      } else {
        newSet.add(guidelineId);
      }
      return newSet;
    });
  };

  const canSubmit = () => {
    if (!selectedPublicationId) return false;
    
    // Check if all required guidelines are acknowledged
    const requiredGuidelines = guidelines.filter(g => g.is_required);
    const allRequiredAcknowledged = requiredGuidelines.every(g => acknowledgedGuidelines.has(g.id));
    
    return allRequiredAcknowledged;
  };

  const handleSubmit = () => {
    if (selectedPublicationId && canSubmit()) {
      onSubmit(selectedPublicationId, {
        templateId: selectedTemplateId || undefined,
        acknowledgedGuidelines: Array.from(acknowledgedGuidelines)
      });
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg max-w-md w-full">
        <div className="p-6">
          <div className="flex justify-between items-center mb-6">
            <h2 className="text-xl font-bold text-gray-900">
              Submit to Publication
            </h2>
            <button
              onClick={onClose}
              className="text-gray-400 hover:text-gray-600"
              disabled={isSubmitting}
            >
              <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <p className="text-gray-600 mb-6">
            Choose a publication to submit your article to. It will need to be approved by an admin or editor before being published.
          </p>

          {loading ? (
            <div className="flex justify-center py-8">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>
          ) : publications.length > 0 ? (
            <div className="space-y-3 mb-6">
              {publications.map((publication) => (
                <label
                  key={publication.id}
                  className={`flex items-center p-3 border rounded-lg cursor-pointer transition-colors ${
                    selectedPublicationId === publication.id
                      ? 'border-blue-500 bg-blue-50'
                      : 'border-gray-200 hover:border-gray-300'
                  }`}
                >
                  <input
                    type="radio"
                    name="publication"
                    value={publication.id}
                    checked={selectedPublicationId === publication.id}
                    onChange={() => setSelectedPublicationId(publication.id)}
                    className="sr-only"
                    disabled={isSubmitting}
                  />
                  <div className="flex items-center flex-1">
                    {publication.logo_url ? (
                      <img
                        src={publication.logo_url}
                        alt={publication.name}
                        className="w-10 h-10 rounded object-cover mr-3"
                      />
                    ) : (
                      <div className="w-10 h-10 rounded bg-gray-200 flex items-center justify-center mr-3">
                        <span className="text-sm font-medium text-gray-500">
                          {publication.name.charAt(0).toUpperCase()}
                        </span>
                      </div>
                    )}
                    <div className="flex-1">
                      <div className="font-medium text-gray-900">{publication.name}</div>
                      {publication.description && (
                        <div className="text-sm text-gray-500 line-clamp-1">
                          {publication.description}
                        </div>
                      )}
                      <div className="flex items-center mt-1">
                        <span className="text-xs px-2 py-0.5 bg-blue-100 text-blue-800 rounded">
                          {(publication as any).user_role || 'owner'}
                        </span>
                        {(publication as any).member_count && (
                          <span className="text-xs text-gray-500 ml-2">
                            {(publication as any).member_count} members
                          </span>
                        )}
                      </div>
                    </div>
                  </div>
                  {selectedPublicationId === publication.id && (
                    <div className="text-blue-500">
                      <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                      </svg>
                    </div>
                  )}
                </label>
              ))}
            </div>
          ) : (
            <div className="text-center py-8">
              <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
              </svg>
              <h3 className="mt-2 text-sm font-medium text-gray-900">No publications available</h3>
              <p className="mt-1 text-sm text-gray-500">
                You need to be a member of a publication to submit articles.
              </p>
            </div>
          )}

          {/* Templates Section */}
          {selectedPublicationId && templates.length > 0 && (
            <div className="mb-6">
              <div className="flex items-center justify-between mb-3">
                <h3 className="text-sm font-medium text-gray-700">Article Templates</h3>
                <button
                  onClick={() => setShowTemplates(!showTemplates)}
                  className="text-sm text-blue-600 hover:text-blue-800"
                >
                  {showTemplates ? 'Hide' : 'Show'} Templates
                </button>
              </div>
              
              {showTemplates && (
                <div className="space-y-2">
                  {templates.map((template) => (
                    <label
                      key={template.id}
                      className={`flex items-center p-3 border rounded-lg cursor-pointer transition-colors ${
                        selectedTemplateId === template.id
                          ? 'border-blue-500 bg-blue-50'
                          : 'border-gray-200 hover:border-gray-300'
                      }`}
                    >
                      <input
                        type="radio"
                        name="template"
                        value={template.id}
                        checked={selectedTemplateId === template.id}
                        onChange={() => setSelectedTemplateId(template.id)}
                        className="sr-only"
                        disabled={isSubmitting}
                      />
                      <div className="flex-1">
                        <div className="font-medium text-gray-900">{template.name}</div>
                        {template.description && (
                          <div className="text-sm text-gray-500">{template.description}</div>
                        )}
                        {template.is_default && (
                          <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 mt-1">
                            Default
                          </span>
                        )}
                      </div>
                      {selectedTemplateId === template.id && (
                        <div className="text-blue-500">
                          <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                          </svg>
                        </div>
                      )}
                    </label>
                  ))}
                </div>
              )}
            </div>
          )}

          {/* Compliance Check */}
          {selectedPublicationId && complianceCheck && (
            <div className="mb-6">
              <h3 className="text-sm font-medium text-gray-700 mb-3">Article Compliance</h3>
              <div className={`p-4 rounded-lg border ${
                complianceCheck.overall_score >= 80 
                  ? 'border-green-200 bg-green-50' 
                  : complianceCheck.overall_score >= 60
                  ? 'border-yellow-200 bg-yellow-50'
                  : 'border-red-200 bg-red-50'
              }`}>
                <div className="flex items-center justify-between mb-2">
                  <span className="text-sm font-medium">Compliance Score</span>
                  <span className={`text-sm font-bold ${
                    complianceCheck.overall_score >= 80 
                      ? 'text-green-600' 
                      : complianceCheck.overall_score >= 60
                      ? 'text-yellow-600'
                      : 'text-red-600'
                  }`}>
                    {Math.round(complianceCheck.overall_score)}%
                  </span>
                </div>
                
                {complianceCheck.recommendations.length > 0 && (
                  <div className="mt-3">
                    <div className="text-sm font-medium text-gray-700 mb-2">Recommendations:</div>
                    <ul className="text-sm text-gray-600 space-y-1">
                      {complianceCheck.recommendations.map((rec: string, index: number) => (
                        <li key={index} className="flex items-start">
                          <span className="text-yellow-500 mr-2">•</span>
                          {rec}
                        </li>
                      ))}
                    </ul>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* Guidelines Section */}
          {selectedPublicationId && guidelines.length > 0 && (
            <div className="mb-6">
              <div className="flex items-center justify-between mb-3">
                <h3 className="text-sm font-medium text-gray-700">
                  Publication Guidelines
                  {guidelines.filter(g => g.is_required).length > 0 && (
                    <span className="text-red-500 ml-1">*</span>
                  )}
                </h3>
                <button
                  onClick={() => setShowGuidelines(!showGuidelines)}
                  className="text-sm text-blue-600 hover:text-blue-800"
                >
                  {showGuidelines ? 'Hide' : 'Show'} Guidelines
                </button>
              </div>
              
              {showGuidelines && (
                <div className="space-y-3 max-h-60 overflow-y-auto">
                  {guidelines.map((guideline) => (
                    <div key={guideline.id} className="border border-gray-200 rounded-lg p-4">
                      <div className="flex items-start">
                        {guideline.is_required && (
                          <input
                            type="checkbox"
                            checked={acknowledgedGuidelines.has(guideline.id)}
                            onChange={() => handleGuidelineAcknowledge(guideline.id)}
                            className="mt-1 mr-3 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            disabled={isSubmitting}
                          />
                        )}
                        <div className="flex-1">
                          <div className="flex items-center space-x-2 mb-2">
                            <h4 className="font-medium text-gray-900">{guideline.title}</h4>
                            {guideline.is_required && (
                              <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                Required
                              </span>
                            )}
                            <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                              {guideline.category}
                            </span>
                          </div>
                          <div 
                            className="text-sm text-gray-600 prose prose-sm max-w-none"
                            dangerouslySetInnerHTML={{ __html: guideline.content }}
                          />
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}
              
              {guidelines.filter(g => g.is_required).length > 0 && (
                <p className="text-sm text-gray-500 mt-2">
                  <span className="text-red-500">*</span> You must acknowledge all required guidelines before submitting.
                </p>
              )}
            </div>
          )}

          <div className="flex justify-end space-x-3">
            <button
              onClick={onClose}
              className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              disabled={isSubmitting}
            >
              Cancel
            </button>
            <button
              onClick={handleSubmit}
              disabled={!canSubmit() || isSubmitting}
              className="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {isSubmitting ? (
                <>
                  <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2 inline-block"></div>
                  Submitting...
                </>
              ) : (
                'Submit Article'
              )}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ArticleSubmissionDialog;