import React, { useState, useEffect } from 'react';

interface Report {
  id: number;
  reporter_username: string;
  reported_content_type: string;
  reported_content_id: number;
  reason: string;
  description: string;
  status: string;
  content_preview: string;
  created_at: string;
  admin_username?: string;
  admin_notes?: string;
}

interface ReportStats {
  total_reports: number;
  pending_reports: number;
  reviewing_reports: number;
  resolved_reports: number;
  dismissed_reports: number;
}

const ModerationQueue: React.FC = () => {
  const [reports, setReports] = useState<Report[]>([]);
  const [stats, setStats] = useState<ReportStats | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [selectedReport, setSelectedReport] = useState<Report | null>(null);
  const [actionLoading, setActionLoading] = useState(false);

  useEffect(() => {
    fetchReports();
  }, []);

  const fetchReports = async () => {
    try {
      const token = localStorage.getItem('token');
      const response = await fetch('/api/moderation/reports', {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.error || 'Failed to fetch reports');
      }

      setReports(data.reports);
      setStats(data.stats);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to fetch reports');
    } finally {
      setLoading(false);
    }
  };

  const updateReportStatus = async (reportId: number, status: string, adminNotes?: string) => {
    setActionLoading(true);
    try {
      const token = localStorage.getItem('token');
      const response = await fetch(`/api/moderation/reports/${reportId}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
          status,
          admin_notes: adminNotes
        })
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.error || 'Failed to update report');
      }

      // Refresh reports
      await fetchReports();
      setSelectedReport(null);
      
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to update report');
    } finally {
      setActionLoading(false);
    }
  };

  const moderateContent = async (action: string, contentType: string, contentId: number, reason: string) => {
    setActionLoading(true);
    try {
      const token = localStorage.getItem('token');
      const response = await fetch(`/api/moderation/${action}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
          content_type: contentType,
          content_id: contentId,
          reason
        })
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.error || `Failed to ${action} content`);
      }

      // Refresh reports
      await fetchReports();
      
    } catch (err) {
      setError(err instanceof Error ? err.message : `Failed to ${action} content`);
    } finally {
      setActionLoading(false);
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'pending': return 'bg-yellow-100 text-yellow-800';
      case 'reviewing': return 'bg-blue-100 text-blue-800';
      case 'resolved': return 'bg-green-100 text-green-800';
      case 'dismissed': return 'bg-gray-100 text-gray-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const getReasonColor = (reason: string) => {
    switch (reason) {
      case 'spam': return 'bg-orange-100 text-orange-800';
      case 'harassment': return 'bg-red-100 text-red-800';
      case 'inappropriate': return 'bg-purple-100 text-purple-800';
      case 'copyright': return 'bg-indigo-100 text-indigo-800';
      case 'misinformation': return 'bg-pink-100 text-pink-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Stats Overview */}
      {stats && (
        <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-gray-900">{stats.total_reports}</div>
            <div className="text-sm text-gray-600">Total Reports</div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-yellow-600">{stats.pending_reports}</div>
            <div className="text-sm text-gray-600">Pending</div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-blue-600">{stats.reviewing_reports}</div>
            <div className="text-sm text-gray-600">Reviewing</div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-green-600">{stats.resolved_reports}</div>
            <div className="text-sm text-gray-600">Resolved</div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-gray-600">{stats.dismissed_reports}</div>
            <div className="text-sm text-gray-600">Dismissed</div>
          </div>
        </div>
      )}

      {error && (
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
          {error}
        </div>
      )}

      {/* Reports List */}
      <div className="bg-white shadow rounded-lg">
        <div className="px-6 py-4 border-b border-gray-200">
          <h2 className="text-lg font-medium text-gray-900">Moderation Queue</h2>
        </div>
        
        <div className="divide-y divide-gray-200">
          {reports.length === 0 ? (
            <div className="px-6 py-8 text-center text-gray-500">
              No reports to review
            </div>
          ) : (
            reports.map((report) => (
              <div key={report.id} className="px-6 py-4 hover:bg-gray-50">
                <div className="flex items-center justify-between">
                  <div className="flex-1">
                    <div className="flex items-center space-x-2 mb-2">
                      <span className={`px-2 py-1 text-xs font-medium rounded-full ${getStatusColor(report.status)}`}>
                        {report.status}
                      </span>
                      <span className={`px-2 py-1 text-xs font-medium rounded-full ${getReasonColor(report.reason)}`}>
                        {report.reason}
                      </span>
                      <span className="text-xs text-gray-500">
                        {report.reported_content_type}
                      </span>
                    </div>
                    
                    <div className="text-sm text-gray-900 mb-1">
                      Reported by: <span className="font-medium">{report.reporter_username}</span>
                    </div>
                    
                    {report.content_preview && (
                      <div className="text-sm text-gray-600 mb-2">
                        Content: "{report.content_preview}..."
                      </div>
                    )}
                    
                    {report.description && (
                      <div className="text-sm text-gray-600 mb-2">
                        Description: {report.description}
                      </div>
                    )}
                    
                    <div className="text-xs text-gray-500">
                      {new Date(report.created_at).toLocaleString()}
                    </div>
                  </div>
                  
                  <div className="flex space-x-2">
                    <button
                      onClick={() => setSelectedReport(report)}
                      className="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
                    >
                      Review
                    </button>
                  </div>
                </div>
              </div>
            ))
          )}
        </div>
      </div>

      {/* Report Detail Modal */}
      {selectedReport && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg p-6 w-full max-w-2xl mx-4 max-h-screen overflow-y-auto">
            <div className="flex justify-between items-center mb-4">
              <h2 className="text-xl font-semibold">Review Report</h2>
              <button
                onClick={() => setSelectedReport(null)}
                disabled={actionLoading}
                className="text-gray-500 hover:text-gray-700"
              >
                <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <div className="space-y-4">
              <div>
                <strong>Reporter:</strong> {selectedReport.reporter_username}
              </div>
              <div>
                <strong>Content Type:</strong> {selectedReport.reported_content_type}
              </div>
              <div>
                <strong>Reason:</strong> {selectedReport.reason}
              </div>
              {selectedReport.description && (
                <div>
                  <strong>Description:</strong> {selectedReport.description}
                </div>
              )}
              {selectedReport.content_preview && (
                <div>
                  <strong>Content Preview:</strong> "{selectedReport.content_preview}..."
                </div>
              )}
              <div>
                <strong>Reported:</strong> {new Date(selectedReport.created_at).toLocaleString()}
              </div>
            </div>

            <div className="mt-6 space-y-4">
              <div>
                <h3 className="text-lg font-medium mb-2">Content Actions</h3>
                <div className="flex space-x-2">
                  <button
                    onClick={() => moderateContent('approve', selectedReport.reported_content_type, selectedReport.reported_content_id, 'Content approved after review')}
                    disabled={actionLoading}
                    className="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50"
                  >
                    Approve Content
                  </button>
                  <button
                    onClick={() => moderateContent('remove', selectedReport.reported_content_type, selectedReport.reported_content_id, 'Content removed due to policy violation')}
                    disabled={actionLoading}
                    className="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50"
                  >
                    Remove Content
                  </button>
                </div>
              </div>

              <div>
                <h3 className="text-lg font-medium mb-2">Report Actions</h3>
                <div className="flex space-x-2">
                  <button
                    onClick={() => updateReportStatus(selectedReport.id, 'resolved', 'Report resolved - appropriate action taken')}
                    disabled={actionLoading}
                    className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
                  >
                    Mark Resolved
                  </button>
                  <button
                    onClick={() => updateReportStatus(selectedReport.id, 'dismissed', 'Report dismissed - no policy violation found')}
                    disabled={actionLoading}
                    className="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 disabled:opacity-50"
                  >
                    Dismiss Report
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default ModerationQueue;