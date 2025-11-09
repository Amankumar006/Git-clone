import React, { useState } from 'react';

interface ReportDialogProps {
  isOpen: boolean;
  onClose: () => void;
  contentType: 'article' | 'comment' | 'user';
  contentId: number;
  onReportSubmitted?: () => void;
}

const ReportDialog: React.FC<ReportDialogProps> = ({
  isOpen,
  onClose,
  contentType,
  contentId,
  onReportSubmitted
}) => {
  const [reason, setReason] = useState('');
  const [description, setDescription] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState('');

  const reportReasons = [
    { value: 'spam', label: 'Spam or unwanted content' },
    { value: 'harassment', label: 'Harassment or bullying' },
    { value: 'inappropriate', label: 'Inappropriate content' },
    { value: 'copyright', label: 'Copyright violation' },
    { value: 'misinformation', label: 'Misinformation' },
    { value: 'other', label: 'Other' }
  ];

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!reason) {
      setError('Please select a reason for reporting');
      return;
    }

    setIsSubmitting(true);
    setError('');

    try {
      const token = localStorage.getItem('token');
      const response = await fetch('/api/moderation/reports', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
          content_type: contentType,
          content_id: contentId,
          reason,
          description: description.trim() || null
        })
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.error || 'Failed to submit report');
      }

      // Reset form
      setReason('');
      setDescription('');
      
      // Show success message
      alert('Report submitted successfully. Thank you for helping keep our community safe.');
      
      onReportSubmitted?.();
      onClose();
      
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to submit report');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleClose = () => {
    if (!isSubmitting) {
      setReason('');
      setDescription('');
      setError('');
      onClose();
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg p-6 w-full max-w-md mx-4">
        <div className="flex justify-between items-center mb-4">
          <h2 className="text-xl font-semibold">Report Content</h2>
          <button
            onClick={handleClose}
            disabled={isSubmitting}
            className="text-gray-500 hover:text-gray-700 disabled:opacity-50"
          >
            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <form onSubmit={handleSubmit}>
          <div className="mb-4">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Why are you reporting this {contentType}?
            </label>
            <div className="space-y-2">
              {reportReasons.map((reasonOption) => (
                <label key={reasonOption.value} className="flex items-center">
                  <input
                    type="radio"
                    name="reason"
                    value={reasonOption.value}
                    checked={reason === reasonOption.value}
                    onChange={(e) => setReason(e.target.value)}
                    className="mr-2"
                    disabled={isSubmitting}
                  />
                  <span className="text-sm">{reasonOption.label}</span>
                </label>
              ))}
            </div>
          </div>

          <div className="mb-4">
            <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-2">
              Additional details (optional)
            </label>
            <textarea
              id="description"
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              placeholder="Provide any additional context that might help us understand the issue..."
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              rows={3}
              maxLength={500}
              disabled={isSubmitting}
            />
            <div className="text-xs text-gray-500 mt-1">
              {description.length}/500 characters
            </div>
          </div>

          {error && (
            <div className="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
              {error}
            </div>
          )}

          <div className="flex justify-end space-x-3">
            <button
              type="button"
              onClick={handleClose}
              disabled={isSubmitting}
              className="px-4 py-2 text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={isSubmitting || !reason}
              className="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {isSubmitting ? 'Submitting...' : 'Submit Report'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default ReportDialog;