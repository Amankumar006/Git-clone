import React from 'react';
import { useAuth } from '../context/AuthContext';
import { Link } from 'react-router-dom';

interface AuthStatusProps {
  showEmailVerification?: boolean;
  className?: string;
}

const AuthStatus: React.FC<AuthStatusProps> = ({ 
  showEmailVerification = true, 
  className = '' 
}) => {
  const { user, isAuthenticated, isLoading, resendVerification } = useAuth();

  if (isLoading) {
    return (
      <div className={`flex items-center space-x-2 ${className}`}>
        <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-primary-600"></div>
        <span className="text-sm text-gray-600">Loading...</span>
      </div>
    );
  }

  if (!isAuthenticated) {
    return (
      <div className={`text-center ${className}`}>
        <p className="text-gray-600 mb-4">Please sign in to access this feature.</p>
        <div className="space-x-4">
          <Link to="/login" className="btn-primary">
            Sign In
          </Link>
          <Link to="/register" className="btn-outline">
            Sign Up
          </Link>
        </div>
      </div>
    );
  }

  if (showEmailVerification && user && !user.email_verified) {
    const handleResendVerification = async () => {
      try {
        await resendVerification(user.email);
        alert('Verification email sent! Please check your inbox.');
      } catch (error: any) {
        alert(error.message || 'Failed to send verification email.');
      }
    };

    return (
      <div className={`bg-yellow-50 border border-yellow-200 rounded-lg p-4 ${className}`}>
        <div className="flex items-start">
          <div className="flex-shrink-0">
            <svg className="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
              <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
            </svg>
          </div>
          <div className="ml-3 flex-1">
            <h3 className="text-sm font-medium text-yellow-800">
              Email verification required
            </h3>
            <div className="mt-2 text-sm text-yellow-700">
              <p>
                Please verify your email address to access all features. 
                We sent a verification link to <strong>{user.email}</strong>.
              </p>
            </div>
            <div className="mt-4">
              <div className="-mx-2 -my-1.5 flex">
                <button
                  onClick={handleResendVerification}
                  className="bg-yellow-50 px-2 py-1.5 rounded-md text-sm font-medium text-yellow-800 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-yellow-50 focus:ring-yellow-600"
                >
                  Resend verification email
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className={`flex items-center space-x-3 ${className}`}>
      <div className="flex items-center space-x-2">
        {user?.profile_image_url ? (
          <img 
            src={user.profile_image_url} 
            alt={user.username}
            className="w-8 h-8 rounded-full object-cover"
          />
        ) : (
          <div className="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
            <span className="text-sm font-medium text-primary-700">
              {user?.username?.charAt(0).toUpperCase()}
            </span>
          </div>
        )}
        <div>
          <p className="text-sm font-medium text-gray-900">{user?.username}</p>
          <p className="text-xs text-gray-500">{user?.email}</p>
        </div>
      </div>
      {user?.email_verified && (
        <div className="flex-shrink-0">
          <svg className="h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
          </svg>
        </div>
      )}
    </div>
  );
};

export default AuthStatus;