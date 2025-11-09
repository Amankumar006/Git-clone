/**
 * Enhanced error messaging utility for better user experience
 */

interface ApiError {
  code?: string;
  message?: string;
  details?: any;
}

interface ErrorResponse {
  error?: ApiError;
  message?: string;
}

export const getErrorMessage = (error: any): { title: string; message?: string } => {
  // Handle different error formats
  if (error?.response?.data) {
    return parseApiError(error.response.data);
  }
  
  if (error?.error) {
    return parseApiError(error);
  }
  
  if (typeof error === 'string') {
    return { title: error };
  }
  
  if (error?.message) {
    return { title: error.message };
  }
  
  return { title: 'An unexpected error occurred' };
};

const parseApiError = (errorData: ErrorResponse): { title: string; message?: string } => {
  const errorCode = errorData.error?.code;
  const errorMessage = errorData.error?.message || errorData.message;
  
  // Handle specific error codes with user-friendly messages
  switch (errorCode) {
    case 'AUTHENTICATION_ERROR':
    case 'UNAUTHORIZED':
      return {
        title: 'Authentication Required',
        message: 'Please log in to perform this action'
      };
      
    case 'VALIDATION_ERROR':
      return {
        title: 'Invalid Input',
        message: formatValidationErrors(errorData.error?.details)
      };
      
    case 'NOT_FOUND':
      return {
        title: 'Not Found',
        message: 'The requested resource could not be found'
      };
      
    case 'FORBIDDEN':
      return {
        title: 'Access Denied',
        message: 'You do not have permission to perform this action'
      };
      
    case 'RATE_LIMITED':
      return {
        title: 'Too Many Requests',
        message: 'Please wait a moment before trying again'
      };
      
    case 'INTERNAL_ERROR':
      return {
        title: 'Server Error',
        message: 'Something went wrong on our end. Please try again later'
      };
      
    default:
      return {
        title: errorMessage || 'An error occurred',
        message: getContextualMessage(errorMessage)
      };
  }
};

const formatValidationErrors = (details: any): string | undefined => {
  if (!details) return undefined;
  
  if (typeof details === 'string') return details;
  
  if (Array.isArray(details)) {
    return details.join(', ');
  }
  
  if (typeof details === 'object') {
    const errors = Object.values(details).flat();
    return errors.join(', ');
  }
  
  return undefined;
};

const getContextualMessage = (errorMessage?: string): string | undefined => {
  if (!errorMessage) return undefined;
  
  const lowerMessage = errorMessage.toLowerCase();
  
  // Provide helpful context for common errors
  if (lowerMessage.includes('network') || lowerMessage.includes('connection')) {
    return 'Please check your internet connection and try again';
  }
  
  if (lowerMessage.includes('timeout')) {
    return 'The request took too long. Please try again';
  }
  
  if (lowerMessage.includes('comment') && lowerMessage.includes('not found')) {
    return 'This comment may have been deleted or moved';
  }
  
  if (lowerMessage.includes('article') && lowerMessage.includes('not found')) {
    return 'This article may have been removed or is no longer available';
  }
  
  return undefined;
};

// Comment-specific error messages
export const getCommentErrorMessage = (error: any, action: 'create' | 'update' | 'delete'): { title: string; message?: string } => {
  const baseError = getErrorMessage(error);
  
  // Enhance with action-specific context
  switch (action) {
    case 'create':
      if (baseError.title.includes('Authentication') || baseError.title.includes('Unauthorized')) {
        return {
          title: 'Login Required',
          message: 'Please log in to join the conversation'
        };
      }
      if (baseError.title.includes('Invalid Input')) {
        return {
          title: 'Comment Invalid',
          message: 'Please check your comment and try again'
        };
      }
      return {
        title: 'Failed to Post Comment',
        message: baseError.message || 'Please try again in a moment'
      };
      
    case 'update':
      if (baseError.title.includes('Access Denied') || baseError.title.includes('Forbidden')) {
        return {
          title: 'Cannot Edit Comment',
          message: 'You can only edit your own comments'
        };
      }
      return {
        title: 'Failed to Update Comment',
        message: baseError.message || 'Please try again'
      };
      
    case 'delete':
      if (baseError.title.includes('Access Denied') || baseError.title.includes('Forbidden')) {
        return {
          title: 'Cannot Delete Comment',
          message: 'You can only delete your own comments'
        };
      }
      return {
        title: 'Failed to Delete Comment',
        message: baseError.message || 'Please try again'
      };
      
    default:
      return baseError;
  }
};

// Success messages for comments
export const getCommentSuccessMessage = (action: 'create' | 'update' | 'delete'): { title: string; message?: string } => {
  switch (action) {
    case 'create':
      return {
        title: 'Comment Posted!',
        message: 'Your comment has been added to the conversation'
      };
      
    case 'update':
      return {
        title: 'Comment Updated',
        message: 'Your changes have been saved'
      };
      
    case 'delete':
      return {
        title: 'Comment Deleted',
        message: 'Your comment has been removed'
      };
      
    default:
      return { title: 'Success' };
  }
};