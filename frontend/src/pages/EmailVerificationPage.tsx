import React, { useState, useEffect } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

const EmailVerificationPage: React.FC = () => {
  const [isLoading, setIsLoading] = useState(true);
  const [isSuccess, setIsSuccess] = useState(false);
  const [error, setError] = useState('');
  const [resendLoading, setResendLoading] = useState(false);

  const [searchParams] = useSearchParams();
  const { verifyEmail, resendVerification, user } = useAuth();

  const token = searchParams.get('token');

  useEffect(() => {
    const handleVerification = async () => {
      if (!token) {
        setError('Invalid verification token');
        setIsLoading(false);
        return;
      }

      try {
        await verifyEmail(token);
        setIsSuccess(true);
      } catch (err: any) {
        setError(err.message || 'Failed to verify email. The link may be expired or invalid.');
      } finally {
        setIsLoading(false);
      }
    };

    handleVerification();
  }, [token, verifyEmail]);

  const handleResendVerification = async () => {
    if (!user?.email) {
      setError('No email address found. Please log in again.');
      return;
    }

    setResendLoading(true);
    setError('');

    try {
      await resendVerification(user.email);
      setError(''); // Clear any previous errors
      // Show success message
      alert('Verification email sent! Please check your inbox.');
    } catch (err: any) {
      setError(err.message || 'Failed to resend verification email.');
    } finally {
      setResendLoading(false);
    }
  };

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div className="max-w-md w-full space-y-8">
          <div className="text-center">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto"></div>
            <h2 className="mt-6 text-center text-3xl font-bold text-gray-900">
              Verifying your email...
            </h2>
            <p className="mt-2 text-center text-sm text-gray-600">
              Please wait while we verify your email address.
            </p>
          </div>
        </div>
      </div>
    );
  }

  if (isSuccess) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div className="max-w-md w-full space-y-8">
          <div className="text-center">
            <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
              <svg className="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <h2 className="mt-6 text-center text-3xl font-bold text-gray-900">
              Email verified successfully!
            </h2>
            <p className="mt-2 text-center text-sm text-gray-600">
              Your email address has been verified. You can now access all features of Medium Clone.
            </p>
          </div>
          
          <div className="text-center space-y-4">
            <Link 
              to="/" 
              className="btn-primary inline-block"
            >
              Go to Homepage
            </Link>
            <div>
              <Link 
                to="/dashboard" 
                className="text-sm text-primary-600 hover:text-primary-500"
              >
                Go to Dashboard
              </Link>
            </div>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-md w-full space-y-8">
        <div className="text-center">
          <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
            <svg className="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </div>
          <h2 className="mt-6 text-center text-3xl font-bold text-gray-900">
            Email verification failed
          </h2>
          <p className="mt-2 text-center text-sm text-gray-600">
            {error}
          </p>
        </div>
        
        {user && !user.email_verified && (
          <div className="text-center space-y-4">
            <button
              onClick={handleResendVerification}
              disabled={resendLoading}
              className="btn-primary disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {resendLoading ? 'Sending...' : 'Resend verification email'}
            </button>
            <p className="text-xs text-gray-500">
              We'll send a new verification link to {user.email}
            </p>
          </div>
        )}
        
        <div className="text-center space-y-2">
          <div>
            <Link 
              to="/" 
              className="text-sm text-primary-600 hover:text-primary-500"
            >
              Go to Homepage
            </Link>
          </div>
          <div>
            <Link 
              to="/login" 
              className="text-sm text-primary-600 hover:text-primary-500"
            >
              Sign in again
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
};

export default EmailVerificationPage;