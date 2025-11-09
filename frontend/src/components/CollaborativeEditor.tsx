import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import ArticleEditor from './ArticleEditor';
import ArticleRevisionHistory from './ArticleRevisionHistory';
import ArticleSubmissionDialog from './ArticleSubmissionDialog';
import { apiService } from '../utils/api';
import { useAuth } from '../context/AuthContext';

interface CollaborativeEditorProps {
  articleId?: number;
}

const CollaborativeEditor: React.FC<CollaborativeEditorProps> = ({ articleId }) => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { user } = useAuth();
  const [activeTab, setActiveTab] = useState('editor');
  const [article, setArticle] = useState<any>(null);
  const [canEdit, setCanEdit] = useState(false);
  const [loading, setLoading] = useState(true);
  const [showSubmissionDialog, setShowSubmissionDialog] = useState(false);

  const currentArticleId = articleId || (id ? parseInt(id) : undefined);

  useEffect(() => {
    if (currentArticleId) {
      loadArticle();
    } else {
      setLoading(false);
    }
  }, [currentArticleId]);

  const loadArticle = async () => {
    if (!currentArticleId) return;

    try {
      const response = await apiService.articles.getById(currentArticleId.toString());
      if (response.data.success) {
        const articleData = response.data.data;
        setArticle(articleData);
        
        // Check if user can edit this article
        const canUserEdit = 
          articleData.author_id === user?.id || // Author can edit
          (articleData.publication_id && await checkPublicationPermissions(articleData.publication_id));
        
        setCanEdit(canUserEdit);
      }
    } catch (error) {
      console.error('Failed to load article:', error);
    } finally {
      setLoading(false);
    }
  };

  const checkPublicationPermissions = async (publicationId: number): Promise<boolean> => {
    try {
      const response = await apiService.publications.getById(publicationId.toString());
      if (response.data.success) {
        const publication = response.data.data;
        const userRole = publication.user_role;
        return ['admin', 'editor'].includes(userRole);
      }
    } catch (error) {
      console.error('Failed to check publication permissions:', error);
    }
    return false;
  };

  const handleCreateRevision = async (revisionData: any, changeSummary: string, isMajor: boolean = false) => {
    if (!currentArticleId) return;

    try {
      const response = await apiService.workflow.createRevision({
        article_id: currentArticleId,
        revision_data: revisionData,
        change_summary: changeSummary,
        is_major: isMajor
      });

      if (response.data.success) {
        // Refresh the article data
        loadArticle();
        return response.data.data;
      }
    } catch (error) {
      console.error('Failed to create revision:', error);
      throw error;
    }
  };

  const handleSubmitToPublication = (publicationId: number, options?: any) => {
    setShowSubmissionDialog(false);
    // The ArticleEditor will handle the actual submission
  };

  const tabs = [
    { id: 'editor', name: 'Editor', show: true },
    { id: 'revisions', name: 'Revision History', show: currentArticleId && canEdit },
    { id: 'collaboration', name: 'Collaboration', show: currentArticleId && article?.publication_id }
  ].filter(tab => tab.show);

  if (loading) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center py-4">
            <div className="flex items-center space-x-4">
              <button
                onClick={() => navigate(-1)}
                className="text-gray-600 hover:text-gray-800"
              >
                ← Back
              </button>
              <h1 className="text-xl font-semibold text-gray-900">
                {article?.title || 'New Article'}
              </h1>
              {article?.is_collaborative && (
                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  Collaborative
                </span>
              )}
            </div>

            <div className="flex items-center space-x-3">
              {article?.publication_id && (
                <div className="text-sm text-gray-500">
                  Publication: {article.publication_name}
                </div>
              )}
              
              {currentArticleId && canEdit && (
                <button
                  onClick={() => setShowSubmissionDialog(true)}
                  className="px-4 py-2 text-sm font-medium text-blue-700 bg-blue-100 border border-blue-300 rounded-md hover:bg-blue-200"
                >
                  Submit to Publication
                </button>
              )}
            </div>
          </div>

          {/* Tab Navigation */}
          {tabs.length > 1 && (
            <div className="border-b border-gray-200">
              <nav className="-mb-px flex space-x-8">
                {tabs.map((tab) => (
                  <button
                    key={tab.id}
                    onClick={() => setActiveTab(tab.id)}
                    className={`py-2 px-1 border-b-2 font-medium text-sm ${
                      activeTab === tab.id
                        ? 'border-blue-500 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    }`}
                  >
                    {tab.name}
                  </button>
                ))}
              </nav>
            </div>
          )}
        </div>
      </div>

      {/* Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {activeTab === 'editor' && (
          <ArticleEditor
            articleId={currentArticleId}
            onSave={(draft) => {
              // Auto-create revision for collaborative articles
              if (article?.is_collaborative && canEdit) {
                handleCreateRevision(
                  {
                    title: draft.title,
                    content: draft.content,
                    featured_image_url: draft.featuredImage,
                    tags: draft.tags
                  },
                  'Auto-save revision',
                  false
                ).catch(console.error);
              }
            }}
            onPublish={(draft) => {
              navigate(`/article/${draft.id}`);
            }}
            onCancel={() => navigate(-1)}
          />
        )}

        {activeTab === 'revisions' && currentArticleId && (
          <ArticleRevisionHistory
            articleId={currentArticleId}
            canEdit={canEdit}
          />
        )}

        {activeTab === 'collaboration' && article?.publication_id && (
          <div className="space-y-6">
            <div className="bg-white rounded-lg border border-gray-200 p-6">
              <h2 className="text-lg font-medium text-gray-900 mb-4">Collaboration Settings</h2>
              
              <div className="space-y-4">
                <div className="flex items-center justify-between">
                  <div>
                    <h3 className="text-sm font-medium text-gray-900">Collaborative Editing</h3>
                    <p className="text-sm text-gray-500">
                      Allow other publication members to edit this article
                    </p>
                  </div>
                  <button
                    onClick={async () => {
                      if (!currentArticleId) return;
                      try {
                        await apiService.articles.update(currentArticleId.toString(), {
                          is_collaborative: !article.is_collaborative
                        });
                        loadArticle();
                      } catch (error) {
                        console.error('Failed to update collaboration setting:', error);
                      }
                    }}
                    className={`relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 ${
                      article.is_collaborative ? 'bg-blue-600' : 'bg-gray-200'
                    }`}
                  >
                    <span
                      className={`pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out ${
                        article.is_collaborative ? 'translate-x-5' : 'translate-x-0'
                      }`}
                    />
                  </button>
                </div>

                {article.is_collaborative && (
                  <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div className="flex">
                      <div className="flex-shrink-0">
                        <svg className="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                          <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                        </svg>
                      </div>
                      <div className="ml-3">
                        <h3 className="text-sm font-medium text-blue-800">
                          Collaborative editing is enabled
                        </h3>
                        <div className="mt-2 text-sm text-blue-700">
                          <p>
                            Publication members with editor or admin roles can now edit this article.
                            All changes will be tracked in the revision history.
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                )}
              </div>
            </div>

            {/* Contributors */}
            {article.contributors && article.contributors.length > 0 && (
              <div className="bg-white rounded-lg border border-gray-200 p-6">
                <h2 className="text-lg font-medium text-gray-900 mb-4">Contributors</h2>
                <div className="flex flex-wrap gap-3">
                  {article.contributors.map((contributor: any) => (
                    <div key={contributor.id} className="flex items-center space-x-2 bg-gray-50 rounded-lg px-3 py-2">
                      {contributor.profile_image_url ? (
                        <img
                          src={contributor.profile_image_url}
                          alt={contributor.username}
                          className="w-8 h-8 rounded-full"
                        />
                      ) : (
                        <div className="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center">
                          <span className="text-sm font-medium text-gray-600">
                            {contributor.username.charAt(0).toUpperCase()}
                          </span>
                        </div>
                      )}
                      <div>
                        <div className="text-sm font-medium text-gray-900">{contributor.username}</div>
                        <div className="text-xs text-gray-500">{contributor.revision_count} edits</div>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
        )}
      </div>

      {/* Submission Dialog */}
      {showSubmissionDialog && currentArticleId && (
        <ArticleSubmissionDialog
          isOpen={showSubmissionDialog}
          onClose={() => setShowSubmissionDialog(false)}
          onSubmit={handleSubmitToPublication}
          articleId={currentArticleId}
        />
      )}
    </div>
  );
};

export default CollaborativeEditor;