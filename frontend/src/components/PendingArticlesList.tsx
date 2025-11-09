import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Article, Publication } from '../types';
import { apiService } from '../utils/api';

interface Submission {
  id: number;
  article_id: number;
  article_title: string;

  status: string;
  submitted_at: string;
  reviewed_at?: string;
  review_notes?: string;
  revision_notes?: string;
  author_username: string;
  author_avatar?: string;
  reading_time: number;
  featured_image_url?: string;
  article_created_at: string;
}

interface PendingArticlesListProps {
  publication: Publication;
  onArticleApproved?: () => void;
  onArticleRejected?: () => void;
}

const PendingArticlesList: React.FC<PendingArticlesListProps> = ({
  publication,
  onArticleApproved,
  onArticleRejected
}) => {
  const [pendingSubmissions, setPendingSubmissions] = useState<Submission[]>([]);
  const [loading, setLoading] = useState(true);
  const [processingSubmissions, setProcessingSubmissions] = useState<Set<number>>(new Set());
  const [showReviewModal, setShowReviewModal] = useState(false);
  const [selectedSubmission, setSelectedSubmission] = useState<Submission | null>(null);
  const [reviewNotes, setReviewNotes] = useState('');
  const [revisionNotes, setRevisionNotes] = useState('');

  useEffect(() => {
    loadPendingSubmissions();
  }, [publication.id]);

  const loadPendingSubmissions = async () => {
    try {
      const response = await apiService.workflow.getPendingSubmissions(publication.id);
      if (response.data.success) {
        setPendingSubmissions(response.data.data.submissions);
      }
    } catch (error) {
      console.error('Failed to load pending submissions:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleApprove = async (submissionId: number) => {
    setProcessingSubmissions(prev => new Set(prev).add(submissionId));
    try {
      const response = await apiService.workflow.approveSubmission({ 
        submission_id: submissionId,
        review_notes: reviewNotes
      });
      if (response.data.success) {
        setPendingSubmissions(prev => prev.filter(submission => submission.id !== submissionId));
        onArticleApproved?.();
        setShowReviewModal(false);
        setReviewNotes('');
      }
    } catch (error) {
      console.error('Failed to approve submission:', error);
    } finally {
      setProcessingSubmissions(prev => {
        const newSet = new Set(prev);
        newSet.delete(submissionId);
        return newSet;
      });
    }
  };

  const handleReject = async (submissionId: number) => {
    setProcessingSubmissions(prev => new Set(prev).add(submissionId));
    try {
      const response = await apiService.workflow.rejectSubmission({ 
        submission_id: submissionId,
        review_notes: reviewNotes
      });
      if (response.data.success) {
        setPendingSubmissions(prev => prev.filter(submission => submission.id !== submissionId));
        onArticleRejected?.();
        setShowReviewModal(false);
        setReviewNotes('');
      }
    } catch (error) {
      console.error('Failed to reject submission:', error);
    } finally {
      setProcessingSubmissions(prev => {
        const newSet = new Set(prev);
        newSet.delete(submissionId);
        return newSet;
      });
    }
  };

  const handleRequestRevision = async (submissionId: number) => {
    if (!revisionNotes.trim()) {
      alert('Please provide revision notes');
      return;
    }

    setProcessingSubmissions(prev => new Set(prev).add(submissionId));
    try {
      const response = await apiService.workflow.requestRevision({ 
        submission_id: submissionId,
        revision_notes: revisionNotes
      });
      if (response.data.success) {
        setPendingSubmissions(prev => prev.filter(submission => submission.id !== submissionId));
        setShowReviewModal(false);
        setRevisionNotes('');
      }
    } catch (error) {
      console.error('Failed to request revision:', error);
    } finally {
      setProcessingSubmissions(prev => {
        const newSet = new Set(prev);
        newSet.delete(submissionId);
        return newSet;
      });
    }
  };

  const openReviewModal = (submission: Submission) => {
    setSelectedSubmission(submission);
    setShowReviewModal(true);
    setReviewNotes('');
    setRevisionNotes('');
  };

  const getPreviewText = (content: any) => {
    if (typeof content === 'string') {
      return content.substring(0, 150) + (content.length > 150 ? '...' : '');
    }
    if (typeof content === 'object') {
      const text = JSON.stringify(content);
      return text.substring(0, 150) + (text.length > 150 ? '...' : '');
    }
    return '';
  };

  if (loading) {
    return (
      <div className="flex justify-center py-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (pendingSubmissions.length === 0) {
    return (
      <div className="text-center py-8">
        <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 className="mt-2 text-sm font-medium text-gray-900">No pending submissions</h3>
        <p className="mt-1 text-sm text-gray-500">
          All submitted articles have been reviewed.
        </p>
      </div>
    );
  }

  return (
    <>
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <h3 className="text-lg font-medium text-gray-900">
            Pending Submissions ({pendingSubmissions.length})
          </h3>
        </div>

        <div className="space-y-4">
          {pendingSubmissions.map((submission) => (
            <div key={submission.id} className="bg-white border border-gray-200 rounded-lg p-6">
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <div className="flex items-center space-x-2 mb-2">
                    <h4 className="text-lg font-medium text-gray-900">{submission.article_title}</h4>
                    <span className="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">
                      {submission.status === 'pending' ? 'Pending Review' : 'Under Review'}
                    </span>
                  </div>
                  

                  
                  <div className="flex items-center space-x-4 text-sm text-gray-500 mb-4">
                    <div className="flex items-center">
                      {submission.author_avatar ? (
                        <img
                          src={submission.author_avatar}
                          alt={submission.author_username}
                          className="w-6 h-6 rounded-full mr-2"
                        />
                      ) : (
                        <div className="w-6 h-6 rounded-full bg-gray-300 flex items-center justify-center mr-2">
                          <span className="text-xs font-medium text-gray-600">
                            {submission.author_username?.charAt(0).toUpperCase()}
                          </span>
                        </div>
                      )}
                      <span>by {submission.author_username}</span>
                    </div>
                    <span>•</span>
                    <span>Submitted {new Date(submission.submitted_at).toLocaleDateString()}</span>
                    <span>•</span>
                    <span>{submission.reading_time} min read</span>
                  </div>

                  {submission.featured_image_url && (
                    <img
                      src={submission.featured_image_url}
                      alt="Featured"
                      className="w-full h-48 object-cover rounded-lg mb-4"
                    />
                  )}
                </div>
              </div>

              <div className="flex items-center justify-between pt-4 border-t border-gray-200">
                <div className="flex space-x-2">
                  <Link
                    to={`/article/${submission.article_id}/preview`}
                    className="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                  >
                    Preview Article
                  </Link>
                </div>

                <div className="flex space-x-2">
                  <button
                    onClick={() => openReviewModal(submission)}
                    disabled={processingSubmissions.has(submission.id)}
                    className="px-4 py-2 text-sm font-medium text-blue-700 bg-blue-100 border border-blue-300 rounded-md hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Review
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Review Modal */}
      {showReviewModal && selectedSubmission && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div className="p-6">
              <div className="flex justify-between items-center mb-6">
                <h2 className="text-xl font-bold text-gray-900">
                  Review Submission: {selectedSubmission.article_title}
                </h2>
                <button
                  onClick={() => setShowReviewModal(false)}
                  className="text-gray-400 hover:text-gray-600"
                >
                  <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <div className="space-y-4 mb-6">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Review Notes (optional)
                  </label>
                  <textarea
                    value={reviewNotes}
                    onChange={(e) => setReviewNotes(e.target.value)}
                    rows={3}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Add notes about your review decision..."
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Revision Notes (if requesting changes)
                  </label>
                  <textarea
                    value={revisionNotes}
                    onChange={(e) => setRevisionNotes(e.target.value)}
                    rows={4}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Specify what changes are needed for the article..."
                  />
                </div>
              </div>

              <div className="flex justify-end space-x-3">
                <button
                  onClick={() => setShowReviewModal(false)}
                  className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                >
                  Cancel
                </button>
                
                <button
                  onClick={() => handleReject(selectedSubmission.id)}
                  disabled={processingSubmissions.has(selectedSubmission.id)}
                  className="px-4 py-2 text-sm font-medium text-red-700 bg-red-100 border border-red-300 rounded-md hover:bg-red-200 disabled:opacity-50"
                >
                  Reject
                </button>
                
                <button
                  onClick={() => handleRequestRevision(selectedSubmission.id)}
                  disabled={processingSubmissions.has(selectedSubmission.id) || !revisionNotes.trim()}
                  className="px-4 py-2 text-sm font-medium text-orange-700 bg-orange-100 border border-orange-300 rounded-md hover:bg-orange-200 disabled:opacity-50"
                >
                  Request Revision
                </button>
                
                <button
                  onClick={() => handleApprove(selectedSubmission.id)}
                  disabled={processingSubmissions.has(selectedSubmission.id)}
                  className="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 disabled:opacity-50"
                >
                  Approve & Publish
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </>
  );
};

export default PendingArticlesList;