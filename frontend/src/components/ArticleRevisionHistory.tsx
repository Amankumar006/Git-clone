import React, { useState, useEffect } from 'react';
import { apiService } from '../utils/api';

interface Revision {
  id: number;
  revision_number: number;
  title: string;
  subtitle?: string;
  content: string;
  featured_image_url?: string;
  tags: string[];
  created_by: number;
  created_by_username: string;
  creator_avatar?: string;
  created_at: string;
  change_summary?: string;
  is_major_revision: boolean;
}

interface RevisionStats {
  total_revisions: number;
  major_revisions: number;
  unique_contributors: number;
  first_revision: string;
  last_revision: string;
}

interface Contributor {
  id: number;
  username: string;
  profile_image_url?: string;
  revision_count: number;
  last_contribution: string;
}

interface ArticleRevisionHistoryProps {
  articleId: number;
  canEdit: boolean;
}

const ArticleRevisionHistory: React.FC<ArticleRevisionHistoryProps> = ({
  articleId,
  canEdit
}) => {
  const [revisions, setRevisions] = useState<Revision[]>([]);
  const [stats, setStats] = useState<RevisionStats | null>(null);
  const [contributors, setContributors] = useState<Contributor[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedRevisions, setSelectedRevisions] = useState<number[]>([]);
  const [showComparison, setShowComparison] = useState(false);
  const [comparisonData, setComparisonData] = useState<any>(null);

  useEffect(() => {
    loadRevisions();
  }, [articleId]);

  const loadRevisions = async () => {
    try {
      const response = await apiService.get(`/workflow/article-revisions?article_id=${articleId}`);
      if (response.success) {
        const data = response.data as any;
        setRevisions(data.revisions);
        setStats(data.stats);
        setContributors(data.contributors);
      }
    } catch (error) {
      console.error('Failed to load revisions:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleRevisionSelect = (revisionNumber: number) => {
    setSelectedRevisions(prev => {
      if (prev.includes(revisionNumber)) {
        return prev.filter(r => r !== revisionNumber);
      } else if (prev.length < 2) {
        return [...prev, revisionNumber];
      } else {
        return [prev[1], revisionNumber];
      }
    });
  };

  const compareRevisions = async () => {
    if (selectedRevisions.length !== 2) return;

    try {
      const [from, to] = selectedRevisions.sort((a, b) => a - b);
      const response = await apiService.get(
        `/workflow/compare-revisions?article_id=${articleId}&from=${from}&to=${to}`
      );
      
      if (response.success) {
        setComparisonData(response.data);
        setShowComparison(true);
      }
    } catch (error) {
      console.error('Failed to compare revisions:', error);
    }
  };

  const restoreRevision = async (revisionNumber: number) => {
    if (!confirm(`Are you sure you want to restore to revision #${revisionNumber}? This will create a new revision with the restored content.`)) {
      return;
    }

    try {
      const response = await apiService.post('/workflow/restore-revision', {
        article_id: articleId,
        revision_number: revisionNumber
      });

      if (response.success) {
        loadRevisions();
        alert('Revision restored successfully');
      }
    } catch (error) {
      console.error('Failed to restore revision:', error);
      alert('Failed to restore revision');
    }
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString();
  };

  const getPreviewText = (content: string) => {
    const plainText = content.replace(/<[^>]*>/g, '');
    return plainText.length > 150 ? plainText.substring(0, 150) + '...' : plainText;
  };

  if (loading) {
    return (
      <div className="flex justify-center py-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Stats Overview */}
      {stats && (
        <div className="bg-white rounded-lg border border-gray-200 p-6">
          <h3 className="text-lg font-medium text-gray-900 mb-4">Revision Statistics</h3>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="text-center">
              <div className="text-2xl font-bold text-blue-600">{stats.total_revisions}</div>
              <div className="text-sm text-gray-500">Total Revisions</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-green-600">{stats.major_revisions}</div>
              <div className="text-sm text-gray-500">Major Revisions</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-purple-600">{stats.unique_contributors}</div>
              <div className="text-sm text-gray-500">Contributors</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-orange-600">
                {Math.ceil((new Date().getTime() - new Date(stats.first_revision).getTime()) / (1000 * 60 * 60 * 24))}
              </div>
              <div className="text-sm text-gray-500">Days Active</div>
            </div>
          </div>
        </div>
      )}

      {/* Contributors */}
      {contributors.length > 0 && (
        <div className="bg-white rounded-lg border border-gray-200 p-6">
          <h3 className="text-lg font-medium text-gray-900 mb-4">Contributors</h3>
          <div className="flex flex-wrap gap-3">
            {contributors.map((contributor) => (
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
                  <div className="text-xs text-gray-500">{contributor.revision_count} revisions</div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Revision Comparison Controls */}
      {canEdit && revisions.length > 1 && (
        <div className="bg-white rounded-lg border border-gray-200 p-4">
          <div className="flex items-center justify-between">
            <div>
              <h4 className="text-sm font-medium text-gray-900">Compare Revisions</h4>
              <p className="text-sm text-gray-500">
                Select two revisions to compare ({selectedRevisions.length}/2 selected)
              </p>
            </div>
            <button
              onClick={compareRevisions}
              disabled={selectedRevisions.length !== 2}
              className="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Compare Selected
            </button>
          </div>
        </div>
      )}

      {/* Revision List */}
      <div className="bg-white rounded-lg border border-gray-200">
        <div className="px-6 py-4 border-b border-gray-200">
          <h3 className="text-lg font-medium text-gray-900">Revision History</h3>
        </div>
        
        <div className="divide-y divide-gray-200">
          {revisions.map((revision) => (
            <div key={revision.id} className="p-6">
              <div className="flex items-start justify-between">
                <div className="flex items-start space-x-3 flex-1">
                  {canEdit && revisions.length > 1 && (
                    <input
                      type="checkbox"
                      checked={selectedRevisions.includes(revision.revision_number)}
                      onChange={() => handleRevisionSelect(revision.revision_number)}
                      className="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    />
                  )}
                  
                  <div className="flex-1">
                    <div className="flex items-center space-x-2 mb-2">
                      <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        Revision #{revision.revision_number}
                      </span>
                      {revision.is_major_revision && (
                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                          Major
                        </span>
                      )}
                    </div>
                    
                    <h4 className="text-lg font-medium text-gray-900 mb-1">{revision.title}</h4>
                    
                    {revision.subtitle && (
                      <p className="text-gray-600 mb-2">{revision.subtitle}</p>
                    )}
                    
                    <p className="text-gray-500 text-sm mb-3">
                      {getPreviewText(revision.content)}
                    </p>
                    
                    {revision.change_summary && (
                      <div className="bg-gray-50 rounded-lg p-3 mb-3">
                        <p className="text-sm text-gray-700">
                          <strong>Changes:</strong> {revision.change_summary}
                        </p>
                      </div>
                    )}
                    
                    <div className="flex items-center space-x-4 text-sm text-gray-500">
                      <div className="flex items-center">
                        {revision.creator_avatar ? (
                          <img
                            src={revision.creator_avatar}
                            alt={revision.created_by_username}
                            className="w-5 h-5 rounded-full mr-2"
                          />
                        ) : (
                          <div className="w-5 h-5 rounded-full bg-gray-300 flex items-center justify-center mr-2">
                            <span className="text-xs font-medium text-gray-600">
                              {revision.created_by_username.charAt(0).toUpperCase()}
                            </span>
                          </div>
                        )}
                        <span>by {revision.created_by_username}</span>
                      </div>
                      <span>•</span>
                      <span>{formatDate(revision.created_at)}</span>
                      {revision.tags.length > 0 && (
                        <>
                          <span>•</span>
                          <div className="flex space-x-1">
                            {revision.tags.slice(0, 3).map((tag, index) => (
                              <span key={index} className="px-2 py-0.5 bg-blue-100 text-blue-800 rounded text-xs">
                                {tag}
                              </span>
                            ))}
                            {revision.tags.length > 3 && (
                              <span className="text-xs text-gray-500">+{revision.tags.length - 3} more</span>
                            )}
                          </div>
                        </>
                      )}
                    </div>
                  </div>
                </div>
                
                {canEdit && (
                  <div className="flex space-x-2 ml-4">
                    <button
                      onClick={() => restoreRevision(revision.revision_number)}
                      className="px-3 py-1 text-sm font-medium text-blue-700 bg-blue-100 border border-blue-300 rounded-md hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                      Restore
                    </button>
                  </div>
                )}
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Comparison Modal */}
      {showComparison && comparisonData && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div className="p-6">
              <div className="flex justify-between items-center mb-6">
                <h2 className="text-xl font-bold text-gray-900">
                  Compare Revisions #{comparisonData.from.revision_number} → #{comparisonData.to.revision_number}
                </h2>
                <button
                  onClick={() => setShowComparison(false)}
                  className="text-gray-400 hover:text-gray-600"
                >
                  <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <div className="grid grid-cols-2 gap-6">
                <div>
                  <h3 className="text-lg font-medium text-gray-900 mb-3">
                    Revision #{comparisonData.from.revision_number}
                  </h3>
                  <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                    <h4 className="font-medium text-gray-900 mb-2">{comparisonData.from.title}</h4>
                    {comparisonData.from.subtitle && (
                      <p className="text-gray-600 mb-2">{comparisonData.from.subtitle}</p>
                    )}
                    <div className="text-sm text-gray-700">
                      {getPreviewText(comparisonData.from.content)}
                    </div>
                  </div>
                </div>

                <div>
                  <h3 className="text-lg font-medium text-gray-900 mb-3">
                    Revision #{comparisonData.to.revision_number}
                  </h3>
                  <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                    <h4 className="font-medium text-gray-900 mb-2">{comparisonData.to.title}</h4>
                    {comparisonData.to.subtitle && (
                      <p className="text-gray-600 mb-2">{comparisonData.to.subtitle}</p>
                    )}
                    <div className="text-sm text-gray-700">
                      {getPreviewText(comparisonData.to.content)}
                    </div>
                  </div>
                </div>
              </div>

              <div className="mt-6">
                <h3 className="text-lg font-medium text-gray-900 mb-3">Changes Summary</h3>
                <div className="bg-gray-50 rounded-lg p-4">
                  <div className="grid grid-cols-2 gap-4 text-sm">
                    <div>
                      <strong>Title Changed:</strong> {comparisonData.changes.title ? 'Yes' : 'No'}
                    </div>
                    <div>
                      <strong>Subtitle Changed:</strong> {comparisonData.changes.subtitle ? 'Yes' : 'No'}
                    </div>
                    <div>
                      <strong>Content Changed:</strong> {comparisonData.changes.content ? 'Yes' : 'No'}
                    </div>
                    <div>
                      <strong>Featured Image Changed:</strong> {comparisonData.changes.featured_image ? 'Yes' : 'No'}
                    </div>
                  </div>
                  
                  {comparisonData.diff && (
                    <div className="mt-4">
                      <strong>Content Changes:</strong>
                      <div className="mt-2 text-sm">
                        <div className="text-green-600">
                          +{comparisonData.diff.added_words?.length || 0} words added
                        </div>
                        <div className="text-red-600">
                          -{comparisonData.diff.removed_words?.length || 0} words removed
                        </div>
                        <div className="text-blue-600">
                          Net change: {comparisonData.diff.word_count_change || 0} words
                        </div>
                      </div>
                    </div>
                  )}
                </div>
              </div>

              <div className="flex justify-end mt-6">
                <button
                  onClick={() => setShowComparison(false)}
                  className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                  Close
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default ArticleRevisionHistory;