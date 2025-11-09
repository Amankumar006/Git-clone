import React from 'react';

interface AuthLoadingProps {
  message?: string;
  className?: string;
}

const AuthLoading: React.FC<AuthLoadingProps> = ({ 
  message = 'Loading...', 
  className = '' 
}) => {
  return (
    <div className={`min-h-screen flex items-center justify-center bg-gray-50 ${className}`}>
      <div className="text-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto mb-4"></div>
        <p className="text-gray-600 text-lg">{message}</p>
      </div>
    </div>
  );
};

export default AuthLoading;