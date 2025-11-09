import React, { ReactNode } from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import AuthLoading from './AuthLoading';

interface ProtectedRouteProps {
  children: ReactNode;
  requireEmailVerification?: boolean;
}

const ProtectedRoute: React.FC<ProtectedRouteProps> = ({ 
  children, 
  requireEmailVerification = false 
}) => {
  const { isAuthenticated, isLoading, user } = useAuth();
  const location = useLocation();

  // Show loading spinner while checking authentication
  if (isLoading) {
    return <AuthLoading message="Checking authentication..." />;
  }

  // Redirect to login if not authenticated
  if (!isAuthenticated) {
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  // Check email verification if required
  if (requireEmailVerification && user && !user.email_verified) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div className="max-w-md w-full space-y-8">
          <div className="text-center">
            <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100">
              <svg className="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
            </div>
            <h2 className="mt-6 text-center text-3xl font-bold text-gray-900">
              Email verification required
            </h2>
            <p className="mt-2 text-center text-sm text-gray-600">
              Please verify your email address to access this feature.
            </p>
            <p className="mt-1 text-center text-sm text-gray-500">
              Check your inbox for a verification email sent to <strong>{user.email}</strong>
            </p>
          </div>
          
          <div className="text-center space-y-4">
            <button
              onClick={() => {
                // This would trigger resend verification
                window.location.href = '/verify-email';
              }}
              className="btn-primary"
            >
              Resend verification email
            </button>
            <div>
              <button
                onClick={() => window.location.href = '/'}
                className="text-sm text-primary-600 hover:text-primary-500"
              >
                Go to Homepage
              </button>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return <>{children}</>;
};

export default ProtectedRoute;