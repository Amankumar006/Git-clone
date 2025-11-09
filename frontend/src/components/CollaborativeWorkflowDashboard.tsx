import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { apiService } from '../utils/api';
import PendingArticlesList from './PendingArticlesList';
import PublicationTemplateManager from './PublicationTemplateManager';
import PublicationGuidelinesManager from './PublicationGuidelinesManager';

interface Submission {
  id: number;
  article_id: number;
  article_title: string;
  article_subtitle?: string;
  status: string;
  submitted_at: string;
  reviewed_at?: string;
  review_notes?: string;
  revision_notes?: string;
  publication_name: string;
  publication_logo?: string;
  author_username: string;
  author_avatar?: string;
  reviewer_username?: string;
}

interface WorkflowStats {
  pending: number;
  under_review: number;
  approved: number;
  rejected: number;
  revision_requested: number;
  avg_review_time_hours: number;
}

interface CollaborativeWorkflowDashboardProps {
  publicationId: number;
  userRole: string;
  canManage: boolean;
}

const CollaborativeWorkflowDashboard: React.FC<CollaborativeWorkflowDashboardProps> = ({
  publicationId,
  userRole,
  canManage
}) => {
  const [activeTab, setActiveTab] = useState('submissions');
  const [submissions, setSubmissions] = useState<Submission[]>([]);
  const [mySubmissions, setMySubmissions] = useState<Submission[]>([]);
  const [stats, setStats] = useState<WorkflowStats | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadWorkflowData();
  }, [publicationId]);

  const loadWorkflowData = async () => {
    try {
      // Load pending submissions if user can review
      if (canManage || userRole === 'editor') {
        const submissionsResponse = await apiService.workflow.getPendingSubmissions(publicationId);
        if (submissionsResponse.data.success) {
          setSubmissions(submissionsResponse.data.data.submissions);
        }
      }

      // Load user's own submissions
      const mySubmissionsResponse = await apiService.workflow.getMySubmissions();
      if (mySubmissionsResponse.data.success) {
        const userSubmissions = mySubmissionsResponse.data.data.submissions.filter(
          (sub: Submission) => sub.publication_name // Filter by current publication if needed
        );
        setMySubmissions(userSubmissions);
      }

      // Load workflow statistics
      const statsResponse = await apiService.publications.getWorkflowStats(publicationId);
      if (statsResponse.data.success) {
        setStats(statsResponse.data.data);
      }

    } catch (error) {
      console.error('Failed to load workflow data:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleArticleApproved = () => {
    loadWorkflowData();
  };

  const handleArticleRejected = () => {
    loadWorkflowData();
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'pending':
        return 'bg-yellow-100 text-yellow-800';
      case 'under_review':
        return 'bg-blue-100 text-blue-800';
      case 'approved':
        return 'bg-green-100 text-green-800';
      case 'rejected':
        return 'bg-red-100 text-red-800';
      case 'revision_requested':
        return 'bg-orange-100 text-orange-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const formatStatus = (status: string) => {
    return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
  };

  const tabs = [
    { id: 'submissions', name: 'Submissions', show: canManage || userRole === 'editor' },
    { id: 'my-submissions', name: 'My Submissions', show: true },
    { id: 'templates', name: 'Templates', show: true },
    { id: 'guidelines', name: 'Guidelines', show: true }
  ].filter(tab => tab.show);

  if (loading) {
    return (
      <div className="flex justify-center py-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Collaborative Workflow</h1>
        <p className="text-gray-600">Manage article submissions, templates, and guidelines</p>
      </div>

      {/* Stats Overview */}
      {stats && (canManage || userRole === 'editor') && (
        <div className="bg-white rounded-lg border border-gray-200 p-6">
          <h2 className="text-lg font-medium text-gray-900 mb-4">Workflow Statistics</h2>
          <div className="grid grid-cols-2 md:grid-cols-6 gap-4">
            <div className="text-center">
              <div className="text-2xl font-bold text-yellow-600">{stats.pending}</div>
              <div className="text-sm text-gray-500">Pending</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-blue-600">{stats.under_review}</div>
              <div className="text-sm text-gray-500">Under Review</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-green-600">{stats.approved}</div>
              <div className="text-sm text-gray-500">Approved</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-red-600">{stats.rejected}</div>
              <div className="text-sm text-gray-500">Rejected</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-orange-600">{stats.revision_requested}</div>
              <div className="text-sm text-gray-500">Needs Revision</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-purple-600">
                {stats.avg_review_time_hours ? Math.round(stats.avg_review_time_hours) : 0}h
              </div>
              <div className="text-sm text-gray-500">Avg Review Time</div>
            </div>
          </div>
        </div>
      )}

      {/* Tab Navigation */}
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

      {/* Tab Content */}
      <div>
        {activeTab === 'submissions' && (canManage || userRole === 'editor') && (
          <div>
            <PendingArticlesList
              publication={{ id: publicationId } as any}
              onArticleApproved={handleArticleApproved}
              onArticleRejected={handleArticleRejected}
            />
          </div>
        )}

        {activeTab === 'my-submissions' && (
          <div className="bg-white rounded-lg border border-gray-200">
            <div className="px-6 py-4 border-b border-gray-200">
              <h3 className="text-lg font-medium text-gray-900">
                My Submissions ({mySubmissions.length})
              </h3>
            </div>

            {mySubmissions.length === 0 ? (
              <div className="text-center py-8">
                <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 className="mt-2 text-sm font-medium text-gray-900">No submissions</h3>
                <p className="mt-1 text-sm text-gray-500">
                  You haven't submitted any articles to publications yet.
                </p>
              </div>
            ) : (
              <div className="divide-y divide-gray-200">
                {mySubmissions.map((submission) => (
                  <div key={submission.id} className="p-6">
                    <div className="flex items-start justify-between">
                      <div className="flex-1">
                        <div className="flex items-center space-x-2 mb-2">
                          <h4 className="text-lg font-medium text-gray-900">
                            {submission.article_title}
                          </h4>
                          <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(submission.status)}`}>
                            {formatStatus(submission.status)}
                          </span>
                        </div>
                        
                        {submission.article_subtitle && (
                          <p className="text-gray-600 mb-2">{submission.article_subtitle}</p>
                        )}
                        
                        <div className="flex items-center space-x-4 text-sm text-gray-500 mb-3">
                          <div className="flex items-center">
                            {submission.publication_logo ? (
                              <img
                                src={submission.publication_logo}
                                alt={submission.publication_name}
                                className="w-5 h-5 rounded mr-2"
                              />
                            ) : (
                              <div className="w-5 h-5 rounded bg-gray-300 flex items-center justify-center mr-2">
                                <span className="text-xs font-medium text-gray-600">
                                  {submission.publication_name.charAt(0).toUpperCase()}
                                </span>
                              </div>
                            )}
                            <span>to {submission.publication_name}</span>
                          </div>
                          <span>•</span>
                          <span>Submitted {formatDate(submission.submitted_at)}</span>
                          {submission.reviewed_at && (
                            <>
                              <span>•</span>
                              <span>Reviewed {formatDate(submission.reviewed_at)}</span>
                            </>
                          )}
                        </div>

                        {submission.review_notes && (
                          <div className="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-3">
                            <p className="text-sm text-blue-800">
                              <strong>Review Notes:</strong> {submission.review_notes}
                            </p>
                          </div>
                        )}

                        {submission.revision_notes && (
                          <div className="bg-orange-50 border border-orange-200 rounded-lg p-3 mb-3">
                            <p className="text-sm text-orange-800">
                              <strong>Revision Requested:</strong> {submission.revision_notes}
                            </p>
                          </div>
                        )}
                      </div>

                      <div className="flex space-x-2 ml-4">
                        <Link
                          to={`/article/${submission.article_id}/edit`}
                          className="px-3 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                        >
                          Edit Article
                        </Link>
                        
                        {submission.status === 'revision_requested' && (
                          <button
                            onClick={() => {
                              // Handle resubmission
                              apiService.workflow.resubmit(submission.id)
                                .then(() => loadWorkflowData());
                            }}
                            className="px-3 py-1 text-sm font-medium text-blue-700 bg-blue-100 border border-blue-300 rounded-md hover:bg-blue-200"
                          >
                            Resubmit
                          </button>
                        )}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        )}

        {activeTab === 'templates' && (
          <PublicationTemplateManager
            publicationId={publicationId}
            canManage={canManage}
          />
        )}

        {activeTab === 'guidelines' && (
          <PublicationGuidelinesManager
            publicationId={publicationId}
            canManage={canManage}
          />
        )}
      </div>
    </div>
  );
};

export default CollaborativeWorkflowDashboard;