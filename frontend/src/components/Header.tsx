import React from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import NotificationCenter from './NotificationCenter';
import SearchBar from './SearchBar';

const Header: React.FC = () => {
  const { user, isAuthenticated, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = () => {
    logout();
    navigate('/');
  };

  return (
    <header className="bg-white border-b border-gray-200 sticky top-0 z-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">
          {/* Logo */}
          <Link to="/" className="flex items-center">
            <h1 className="text-2xl font-bold text-gray-900">Medium</h1>
          </Link>

          {/* Search Bar */}
          <div className="flex-1 max-w-lg mx-8">
            <SearchBar placeholder="Search articles, authors, and topics..." />
          </div>

          {/* Navigation */}
          <nav className="hidden md:flex items-center space-x-8">
            <Link 
              to="/" 
              className="text-gray-600 hover:text-gray-900 transition-colors"
            >
              Home
            </Link>
            <Link 
              to="/tags" 
              className="text-gray-600 hover:text-gray-900 transition-colors"
            >
              Topics
            </Link>
            <Link 
              to="/publications" 
              className="text-gray-600 hover:text-gray-900 transition-colors"
            >
              Publications
            </Link>
            {isAuthenticated && (
              <Link 
                to="/write" 
                className="text-gray-600 hover:text-gray-900 transition-colors"
              >
                Write
              </Link>
            )}
          </nav>

          {/* User actions */}
          <div className="flex items-center space-x-4">
            {isAuthenticated ? (
              <>
                <NotificationCenter />
                <Link 
                  to="/write" 
                  className="btn-primary"
                >
                  Write
                </Link>
                <div className="relative group">
                  <button className="flex items-center space-x-2 text-gray-600 hover:text-gray-900">
                    {user?.profile_image_url ? (
                      <img 
                        src={user.profile_image_url} 
                        alt={user.username}
                        className="w-8 h-8 rounded-full object-cover"
                      />
                    ) : (
                      <div className="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                        <span className="text-sm font-medium text-gray-700">
                          {user?.username?.charAt(0).toUpperCase()}
                        </span>
                      </div>
                    )}
                  </button>
                  
                  {/* Dropdown menu */}
                  <div className="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                    <Link 
                      to={`/user/${user?.username}`}
                      className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                    >
                      Profile
                    </Link>
                    <Link 
                      to="/dashboard"
                      className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                    >
                      Dashboard
                    </Link>
                    <button 
                      onClick={handleLogout}
                      className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                    >
                      Sign out
                    </button>
                  </div>
                </div>
              </>
            ) : (
              <>
                <Link 
                  to="/login" 
                  className="text-gray-600 hover:text-gray-900 transition-colors"
                >
                  Sign in
                </Link>
                <Link 
                  to="/register" 
                  className="btn-primary"
                >
                  Get started
                </Link>
              </>
            )}
          </div>
        </div>
      </div>
    </header>
  );
};

export default Header;