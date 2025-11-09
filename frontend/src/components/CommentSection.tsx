import React, { useState, useEffect } from 'react';
import { useAuth, User } from '../context/AuthContext';
import { apiService, ApiResponse } from '../utils/api';
import { useToast } from '../hooks/useToast';
import { getCommentErrorMessage, getCommentSuccessMessage } from '../utils/errorMessages';
import ReportDialog from './ReportDialog';

interface Comment {
  id: number;
  content: string;
  created_at: string;
  updated_at: string;
  user_id: number;
  username: string;
  profile_image_url?: string;
  parent_comment_id?: number;
  replies?: Comment[];
}

interface CommentSectionProps {
  articleId: number;
  className?: string;
}

interface CommentFormProps {
  articleId: number;
  parentCommentId?: number;
  onCommentAdded: (comment: Comment) => void;
  onCancel?: () => void;
  placeholder?: string;
}

const CommentForm: React.FC<CommentFormProps> = ({
  articleId,
  parentCommentId,
  onCommentAdded,
  onCancel,
  placeholder = "Write a comment..."
}) => {
  const { user } = useAuth();
  const { showSuccess, showError, showWarning } = useToast();
  const [content, setContent] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!user) {
      showWarning('Login Required', 'Please log in to join the conversation');
      return;
    }

    if (!content.trim()) {
      setError('Comment cannot be empty');
      return;
    }

    setIsSubmitting(true);
    setError(null);

    try {
      const response = await apiService.post('/comments/create', {
        article_id: articleId,
        content: content.trim(),
        parent_comment_id: parentCommentId
      });

      if (response.success) {
        onCommentAdded(response.data as Comment);
        setContent('');
        if (onCancel) onCancel();
        
        const successMsg = getCommentSuccessMessage('create');
        showSuccess(successMsg.title, successMsg.message);
      }
    } catch (error: any) {
      console.error('Error creating comment:', error);
      const errorMsg = getCommentErrorMessage(error, 'create');
      showError(errorMsg.title, errorMsg.message);
      setError(errorMsg.message || 'Failed to post comment');
    } finally {
      setIsSubmitting(false);
    }
  };

  if (!user) {
    return (
      <div className="bg-gray-50 p-4 rounded-lg text-center">
        <p className="text-gray-600">Please log in to join the conversation</p>
      </div>
    );
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-3">
      <div className="flex space-x-3">
        <img
          src={user.profile_image_url || '/default-avatar.svg'}
          alt={user.username}
          className="w-8 h-8 rounded-full flex-shrink-0"
          onError={(e) => {
            const target = e.target as HTMLImageElement;
            if (target.src !== window.location.origin + '/default-avatar.svg') {
              target.src = '/default-avatar.svg';
            }
          }}
        />
        <div className="flex-1">
          <textarea
            value={content}
            onChange={(e) => setContent(e.target.value)}
            placeholder={placeholder}
            className="w-full p-3 border border-gray-300 rounded-lg resize-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            rows={3}
            maxLength={2000}
          />
          {error && (
            <p className="text-red-500 text-sm mt-1">{error}</p>
          )}
          <div className="flex justify-between items-center mt-2">
            <span className="text-xs text-gray-500">
              {content.length}/2000 characters
            </span>
            <div className="flex space-x-2">
              {onCancel && (
                <button
                  type="button"
                  onClick={onCancel}
                  className="px-3 py-1 text-sm text-gray-600 hover:text-gray-800"
                >
                  Cancel
                </button>
              )}
              <button
                type="submit"
                disabled={isSubmitting || !content.trim()}
                className="px-4 py-1 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {isSubmitting ? 'Posting...' : 'Post'}
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>
  );
};

interface CommentItemProps {
  comment: Comment;
  articleId: number;
  onCommentAdded: (comment: Comment) => void;
  onCommentUpdated: (comment: Comment) => void;
  onCommentDeleted: (commentId: number) => void;
  level?: number;
}

const CommentItem: React.FC<CommentItemProps> = ({
  comment,
  articleId,
  onCommentAdded,
  onCommentUpdated,
  onCommentDeleted,
  level = 0
}) => {
  const { user } = useAuth();
  const { showSuccess, showError } = useToast();
  const [showReplyForm, setShowReplyForm] = useState(false);
  const [isEditing, setIsEditing] = useState(false);
  const [editContent, setEditContent] = useState(comment.content);
  const [isUpdating, setIsUpdating] = useState(false);
  const [isDeleting, setIsDeleting] = useState(false);
  const [showReportDialog, setShowReportDialog] = useState(false);

  const canEdit = user && user.id === comment.user_id;
  const canReply = level < 2; // Max 3 levels (0, 1, 2)

  const handleEdit = async () => {
    if (!editContent.trim()) return;

    setIsUpdating(true);
    try {
      const response = await apiService.put(`/comments/update/${comment.id}`, {
        content: editContent.trim()
      });

      if (response.success) {
        onCommentUpdated(response.data as Comment);
        setIsEditing(false);
        
        const successMsg = getCommentSuccessMessage('update');
        showSuccess(successMsg.title, successMsg.message);
      }
    } catch (error: any) {
      console.error('Error updating comment:', error);
      const errorMsg = getCommentErrorMessage(error, 'update');
      showError(errorMsg.title, errorMsg.message);
    } finally {
      setIsUpdating(false);
    }
  };

  const handleDelete = async () => {
    if (!window.confirm('Are you sure you want to delete this comment?')) return;

    setIsDeleting(true);
    try {
      const response = await apiService.delete(`/comments/delete/${comment.id}`);

      if (response.success) {
        onCommentDeleted(comment.id);
        
        const successMsg = getCommentSuccessMessage('delete');
        showSuccess(successMsg.title, successMsg.message);
      }
    } catch (error: any) {
      console.error('Error deleting comment:', error);
      const errorMsg = getCommentErrorMessage(error, 'delete');
      showError(errorMsg.title, errorMsg.message);
    } finally {
      setIsDeleting(false);
    }
  };

  const handleReplyAdded = (newComment: Comment) => {
    onCommentAdded(newComment);
    setShowReplyForm(false);
  };

  return (
    <div className={`${level > 0 ? 'ml-8 mt-4' : 'mt-6'} ${level > 0 ? 'border-l-2 border-gray-200 pl-4' : ''}`}>
      <div className="flex space-x-3">
        <img
          src={comment.profile_image_url || '/default-avatar.svg'}
          alt={comment.username}
          className="w-8 h-8 rounded-full flex-shrink-0"
          onError={(e) => {
            const target = e.target as HTMLImageElement;
            if (target.src !== window.location.origin + '/default-avatar.svg') {
              target.src = '/default-avatar.svg';
            }
          }}
        />
        <div className="flex-1">
          <div className="bg-gray-50 rounded-lg p-3">
            <div className="flex items-center space-x-2 mb-2">
              <span className="font-medium text-sm text-gray-900">
                {comment.username}
              </span>
              <span className="text-xs text-gray-500">
                {new Date(comment.created_at).toLocaleDateString()}
              </span>
              {comment.updated_at !== comment.created_at && (
                <span className="text-xs text-gray-400">(edited)</span>
              )}
            </div>
            
            {isEditing ? (
              <div className="space-y-2">
                <textarea
                  value={editContent}
                  onChange={(e) => setEditContent(e.target.value)}
                  className="w-full p-2 border border-gray-300 rounded resize-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  rows={3}
                  maxLength={2000}
                />
                <div className="flex space-x-2">
                  <button
                    onClick={handleEdit}
                    disabled={isUpdating || !editContent.trim()}
                    className="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 disabled:opacity-50"
                  >
                    {isUpdating ? 'Saving...' : 'Save'}
                  </button>
                  <button
                    onClick={() => {
                      setIsEditing(false);
                      setEditContent(comment.content);
                    }}
                    className="px-3 py-1 text-gray-600 text-sm hover:text-gray-800"
                  >
                    Cancel
                  </button>
                </div>
              </div>
            ) : (
              <p className="text-gray-800 text-sm whitespace-pre-wrap">
                {comment.content}
              </p>
            )}
          </div>
          
          {!isEditing && (
            <div className="flex items-center space-x-4 mt-2 text-xs text-gray-500">
              {canReply && (
                <button
                  onClick={() => setShowReplyForm(!showReplyForm)}
                  className="hover:text-blue-600"
                >
                  Reply
                </button>
              )}
              {canEdit && (
                <>
                  <button
                    onClick={() => setIsEditing(true)}
                    className="hover:text-blue-600"
                  >
                    Edit
                  </button>
                  <button
                    onClick={handleDelete}
                    disabled={isDeleting}
                    className="hover:text-red-600"
                  >
                    {isDeleting ? 'Deleting...' : 'Delete'}
                  </button>
                </>
              )}
              {user && user.id !== comment.user_id && (
                <button
                  onClick={() => setShowReportDialog(true)}
                  className="hover:text-red-600"
                >
                  Report
                </button>
              )}
            </div>
          )}
          
          {showReplyForm && (
            <div className="mt-3">
              <CommentForm
                articleId={articleId}
                parentCommentId={comment.id}
                onCommentAdded={handleReplyAdded}
                onCancel={() => setShowReplyForm(false)}
                placeholder={`Reply to ${comment.username}...`}
              />
            </div>
          )}
          
          {/* Render replies */}
          {comment.replies && comment.replies.length > 0 && (
            <div className="mt-2">
              {comment.replies.map((reply) => (
                <CommentItem
                  key={reply.id}
                  comment={reply}
                  articleId={articleId}
                  onCommentAdded={onCommentAdded}
                  onCommentUpdated={onCommentUpdated}
                  onCommentDeleted={onCommentDeleted}
                  level={level + 1}
                />
              ))}
            </div>
          )}
        </div>
      </div>
      
      {/* Report Dialog */}
      <ReportDialog
        isOpen={showReportDialog}
        onClose={() => setShowReportDialog(false)}
        contentType="comment"
        contentId={comment.id}
        onReportSubmitted={() => {
          setShowReportDialog(false);
        }}
      />
    </div>
  );
};

const CommentSection: React.FC<CommentSectionProps> = ({ articleId, className = '' }) => {
  const { showError } = useToast();
  const [comments, setComments] = useState<Comment[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);

  useEffect(() => {
    fetchComments();
  }, [articleId]);

  const fetchComments = async (pageNum = 1) => {
    try {
      setLoading(true);
      const response = await apiService.get(`/comments/article/${articleId}?page=${pageNum}&limit=20`);
      
      if (response.success) {
        const data = response.data as { comments: Comment[]; pagination: any };
        if (pageNum === 1) {
          setComments(data.comments);
        } else {
          setComments(prev => [...prev, ...data.comments]);
        }
        
        setHasMore(data.pagination.current_page < data.pagination.total_pages);
        setPage(pageNum);
      }
    } catch (error: any) {
      console.error('Error fetching comments:', error);
      const errorMessage = 'Failed to load comments';
      setError(errorMessage);
      showError('Loading Error', 'Unable to load comments. Please refresh the page.');
    } finally {
      setLoading(false);
    }
  };

  const handleCommentAdded = (newComment: Comment) => {
    if (newComment.parent_comment_id) {
      // It's a reply, refresh to get proper threading
      // In the future, we could optimize this by updating the specific parent's replies array
      fetchComments(1);
    } else {
      // It's a top-level comment, add it to the beginning
      const commentWithReplies = { ...newComment, replies: [] };
      setComments(prev => [commentWithReplies, ...prev]);
    }
  };

  const handleCommentUpdated = (updatedComment: Comment) => {
    // Refresh comments to get updated threading
    fetchComments(1);
  };

  const handleCommentDeleted = (commentId: number) => {
    // Refresh comments to handle cascade deletions
    fetchComments(1);
  };

  const loadMore = () => {
    if (hasMore && !loading) {
      fetchComments(page + 1);
    }
  };

  return (
    <div className={`${className}`}>
      <div className="border-t border-gray-200 pt-8">
        <h3 className="text-xl font-bold text-gray-900 mb-6">
          Comments ({comments.length})
        </h3>
        
        {/* Comment Form */}
        <div className="mb-8">
          <CommentForm
            articleId={articleId}
            onCommentAdded={handleCommentAdded}
          />
        </div>
        
        {/* Comments List */}
        {loading && comments.length === 0 ? (
          <div className="text-center py-8">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
            <p className="text-gray-500 mt-2">Loading comments...</p>
          </div>
        ) : error ? (
          <div className="text-center py-8">
            <p className="text-red-500">{error}</p>
            <button
              onClick={() => fetchComments(1)}
              className="mt-2 text-blue-600 hover:text-blue-800"
            >
              Try again
            </button>
          </div>
        ) : comments.length === 0 ? (
          <div className="text-center py-8">
            <p className="text-gray-500">No comments yet. Be the first to comment!</p>
          </div>
        ) : (
          <div>
            {comments.map((comment) => (
              <CommentItem
                key={comment.id}
                comment={comment}
                articleId={articleId}
                onCommentAdded={handleCommentAdded}
                onCommentUpdated={handleCommentUpdated}
                onCommentDeleted={handleCommentDeleted}
              />
            ))}
            
            {hasMore && (
              <div className="text-center mt-8">
                <button
                  onClick={loadMore}
                  disabled={loading}
                  className="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 disabled:opacity-50"
                >
                  {loading ? 'Loading...' : 'Load more comments'}
                </button>
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
};

export default CommentSection;