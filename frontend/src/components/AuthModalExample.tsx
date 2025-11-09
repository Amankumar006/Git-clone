import React from 'react';
import { useAuth } from '../context/AuthContext';
import { useAuthModal } from '../hooks/useAuthModal';
import AuthModal from './AuthModal';

/**
 * Example component showing how to use the AuthModal
 * This can be used in any component where you need authentication
 */
const AuthModalExample: React.FC = () => {
  const { isAuthenticated, user } = useAuth();
  const { isOpen, activeTab, openLogin, openRegister, close } = useAuthModal();

  if (isAuthenticated) {
    return (
      <div className="p-4">
        <h2 className="text-xl font-bold mb-4">Welcome back, {user?.username}!</h2>
        <p className="text-gray-600">You are successfully authenticated.</p>
      </div>
    );
  }

  return (
    <div className="p-4">
      <h2 className="text-xl font-bold mb-4">Authentication Required</h2>
      <p className="text-gray-600 mb-4">
        Please sign in or create an account to continue.
      </p>
      
      <div className="space-x-4">
        <button onClick={openLogin} className="btn-primary">
          Sign In
        </button>
        <button onClick={openRegister} className="btn-outline">
          Sign Up
        </button>
      </div>

      <AuthModal 
        isOpen={isOpen} 
        onClose={close} 
        defaultTab={activeTab} 
      />
    </div>
  );
};

export default AuthModalExample;