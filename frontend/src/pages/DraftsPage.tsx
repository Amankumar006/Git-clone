import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { apiService } from '../utils/api';
import { formatReadingTime } from '../utils/readingTime';

interface Draft {
  id: number;
  title: string;
  content: string;
  readingTime: number;
  updated_at: string;
  tags: string[];
}

const DraftsPage: React.FC = () => {
  const [drafts, setDrafts] = useState<Draft[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchDrafts();
  }, []);

  const fetchDrafts = async () => {
    try {
      setLoading(true);
      const response = await apiService.articles.getDrafts();
      if (response.data.success) {
        setDrafts(response.data.data || []);
      }
    } catch (error: any) {
      setError(error.response?.data?.message || 'Failed to fetch drafts');
    } finally {
      setLoading(false);
    }
  };

  const deleteDraft = async (id: number) => {
    if (!window.confirm('Are you sure you want to delete this draft?')) {
      return;
    }

    try {
      await apiService.articles.delete(id.toString());
      setDrafts(drafts.filter(draft => draft.id !== id));
    } catch (error: any) {
      alert(error.response?.data?.message || 'Failed to delete draft');
    }
  };

  const getPreviewText = (content: string) => {
    // Remove HTML tags and get first 150 characters
    const plainText = content.replace(/<[^>]*>/g, '');
    return plainText.length > 150 ? plainText.substring(0, 150) + '...' : plainText;
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-4xl mx-auto py-8 px-4">
        <div className="flex justify-between items-center mb-8">
          <h1 className="text-3xl font-bold text-gray-900">Your Drafts</h1>
          <Link
            to="/editor"
            className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          >
            New Article
          </Link>
        </div>

        {error && (
          <div className="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
            {error}
          </div>
        )}

        {drafts.length === 0 ? (
          <div className="text-center py-16">
            <div className="text-gray-400 mb-4">
              <svg className="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
            <h3 className="text-xl font-medium text-gray-900 mb-2">No drafts yet</h3>
            <p className="text-gray-600 mb-6">Start writing your first article</p>
            <Link
              to="/editor"
              className="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
              Write your first article
            </Link>
          </div>
        ) : (
          <div className="space-y-6">
            {drafts.map((draft) => (
              <div key={draft.id} className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div className="flex justify-between items-start mb-4">
                  <div className="flex-1">
                    <h2 className="text-xl font-semibold text-gray-900 mb-2">
                      {draft.title || 'Untitled Draft'}
                    </h2>

                    <p className="text-gray-700 mb-4">
                      {getPreviewText(draft.content)}
                    </p>
                  </div>
                </div>

                <div className="flex items-center justify-between">
                  <div className="flex items-center space-x-4 text-sm text-gray-500">
                    <span>
                      Last edited: {new Date(draft.updated_at).toLocaleDateString()}
                    </span>
                    <span>{formatReadingTime(draft.readingTime)}</span>
                    {draft.tags.length > 0 && (
                      <div className="flex items-center space-x-1">
                        <span>Tags:</span>
                        <div className="flex space-x-1">
                          {draft.tags.slice(0, 3).map((tag, index) => (
                            <span key={index} className="px-2 py-1 bg-gray-100 text-gray-600 rounded text-xs">
                              {tag}
                            </span>
                          ))}
                          {draft.tags.length > 3 && (
                            <span className="text-gray-400">+{draft.tags.length - 3} more</span>
                          )}
                        </div>
                      </div>
                    )}
                  </div>

                  <div className="flex items-center space-x-3">
                    <Link
                      to={`/editor/${draft.id}`}
                      className="px-4 py-2 text-blue-600 hover:text-blue-800"
                    >
                      Edit
                    </Link>
                    <button
                      onClick={() => deleteDraft(draft.id)}
                      className="px-4 py-2 text-red-600 hover:text-red-800"
                    >
                      Delete
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default DraftsPage;