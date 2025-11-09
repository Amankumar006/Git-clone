import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import CollaborativeWorkflowDashboard from '../components/CollaborativeWorkflowDashboard';
import { apiService } from '../utils/api';
import { useAuth } from '../context/AuthContext';

interface Publication {
  id: number;
  name: string;
  description?: string;
  logo_url?: string;
  user_role: string;
  member_count: number;
  workflow_enabled: boolean;
  auto_approval: boolean;
  require_review: boolean;
  review_deadline_days: number;
}

const WorkflowManagementPage: React.FC = () => {
  const { publicationId } = useParams<{ publicationId: string }>();
  const { user } = useAuth();
  const [publication, setPublication] = useState<Publication | null>(null);
  const [loading, setLoading] = useState(true);
  const [canManage, setCanManage] = useState(false);

  useEffect(() => {
    if (publicationId) {
      loadPublication();
    }
  }, [publicationId]);

  const loadPublication = async () => {
    if (!publicationId) return;

    try {
      const response = await apiService.publications.getById(publicationId);
      if (response.data.success) {
        const pubData = response.data.data;
        setPublication(pubData);
        
        // Check if user can manage workflow
        const userRole = pubData.user_role;
        setCanManage(['admin', 'owner'].includes(userRole));
      }
    } catch (error) {
      console.error('Failed to load publication:', error);
    } finally {
      setLoading(false);
    }
  };

  const updateWorkflowSettings = async (settings: Partial<Publication>) => {
    if (!publicationId) return;

    try {
      const response = await apiService.publications.update(publicationId, settings);
      if (response.data.success) {
        setPublication(prev => prev ? { ...prev, ...settings } : null);
      }
    } catch (error) {
      console.error('Failed to update workflow settings:', error);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (!publication) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <h1 className="text-2xl font-bold text-gray-900 mb-4">Publication Not Found</h1>
          <p className="text-gray-600 mb-6">The publication you're looking for doesn't exist or you don't have access to it.</p>
          <Link
            to="/publications"
            className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          >
            Back to Publications
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white border-b border-gray-200">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center py-6">
            <div className="flex items-center space-x-4">
              <Link
                to={`/publication/${publicationId}`}
                className="text-gray-600 hover:text-gray-800"
              >
                ← Back to Publication
              </Link>
              <div className="flex items-center space-x-3">
                {publication.logo_url ? (
                  <img
                    src={publication.logo_url}
                    alt={publication.name}
                    className="w-10 h-10 rounded object-cover"
                  />
                ) : (
                  <div className="w-10 h-10 rounded bg-gray-200 flex items-center justify-center">
                    <span className="text-sm font-medium text-gray-500">
                      {publication.name.charAt(0).toUpperCase()}
                    </span>
                  </div>
                )}
                <div>
                  <h1 className="text-2xl font-bold text-gray-900">{publication.name}</h1>
                  <p className="text-sm text-gray-500">Workflow Management</p>
                </div>
              </div>
            </div>

            <div className="flex items-center space-x-3">
              <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                {publication.user_role}
              </span>
              {publication.workflow_enabled ? (
                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                  Workflow Enabled
                </span>
              ) : (
                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                  Workflow Disabled
                </span>
              )}
            </div>
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Workflow Settings */}
        {canManage && (
          <div className="bg-white rounded-lg border border-gray-200 p-6 mb-8">
            <h2 className="text-lg font-medium text-gray-900 mb-6">Workflow Settings</h2>
            
            <div className="space-y-6">
              {/* Enable/Disable Workflow */}
              <div className="flex items-center justify-between">
                <div>
                  <h3 className="text-sm font-medium text-gray-900">Enable Collaborative Workflow</h3>
                  <p className="text-sm text-gray-500">
                    Allow writers to submit articles for review before publishing
                  </p>
                </div>
                <button
                  onClick={() => updateWorkflowSettings({ workflow_enabled: !publication.workflow_enabled })}
                  className={`relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 ${
                    publication.workflow_enabled ? 'bg-blue-600' : 'bg-gray-200'
                  }`}
                >
                  <span
                    className={`pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out ${
                      publication.workflow_enabled ? 'translate-x-5' : 'translate-x-0'
                    }`}
                  />
                </button>
              </div>

              {publication.workflow_enabled && (
                <>
                  {/* Auto Approval */}
                  <div className="flex items-center justify-between">
                    <div>
                      <h3 className="text-sm font-medium text-gray-900">Auto Approval</h3>
                      <p className="text-sm text-gray-500">
                        Automatically approve and publish articles from trusted writers
                      </p>
                    </div>
                    <button
                      onClick={() => updateWorkflowSettings({ auto_approval: !publication.auto_approval })}
                      className={`relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 ${
                        publication.auto_approval ? 'bg-blue-600' : 'bg-gray-200'
                      }`}
                    >
                      <span
                        className={`pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out ${
                          publication.auto_approval ? 'translate-x-5' : 'translate-x-0'
                        }`}
                      />
                    </button>
                  </div>

                  {/* Require Review */}
                  <div className="flex items-center justify-between">
                    <div>
                      <h3 className="text-sm font-medium text-gray-900">Require Review</h3>
                      <p className="text-sm text-gray-500">
                        All submissions must be reviewed by an admin or editor
                      </p>
                    </div>
                    <button
                      onClick={() => updateWorkflowSettings({ require_review: !publication.require_review })}
                      className={`relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 ${
                        publication.require_review ? 'bg-blue-600' : 'bg-gray-200'
                      }`}
                    >
                      <span
                        className={`pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out ${
                          publication.require_review ? 'translate-x-5' : 'translate-x-0'
                        }`}
                      />
                    </button>
                  </div>

                  {/* Review Deadline */}
                  <div>
                    <label className="block text-sm font-medium text-gray-900 mb-2">
                      Review Deadline (days)
                    </label>
                    <div className="flex items-center space-x-3">
                      <input
                        type="number"
                        min="1"
                        max="30"
                        value={publication.review_deadline_days}
                        onChange={(e) => {
                          const days = parseInt(e.target.value) || 7;
                          updateWorkflowSettings({ review_deadline_days: days });
                        }}
                        className="w-20 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                      <span className="text-sm text-gray-500">
                        Target time for reviewing submissions
                      </span>
                    </div>
                  </div>
                </>
              )}
            </div>
          </div>
        )}

        {/* Workflow Dashboard */}
        {publication.workflow_enabled ? (
          <CollaborativeWorkflowDashboard
            publicationId={publication.id}
            userRole={publication.user_role}
            canManage={canManage}
          />
        ) : (
          <div className="bg-white rounded-lg border border-gray-200 p-8 text-center">
            <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
            <h3 className="mt-2 text-sm font-medium text-gray-900">Workflow Disabled</h3>
            <p className="mt-1 text-sm text-gray-500">
              Enable the collaborative workflow to start managing article submissions and reviews.
            </p>
            {canManage && (
              <div className="mt-6">
                <button
                  onClick={() => updateWorkflowSettings({ workflow_enabled: true })}
                  className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                >
                  Enable Workflow
                </button>
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
};

export default WorkflowManagementPage;