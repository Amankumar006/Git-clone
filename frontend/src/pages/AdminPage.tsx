import React, { useState } from 'react';
import { useAuth } from '../context/AuthContext';
import AdminDashboard from '../components/AdminDashboard';
import UserManagement from '../components/UserManagement';
import ModerationQueue from '../components/ModerationQueue';
import ContentManagement from '../components/ContentManagement';
import SystemSettings from '../components/SystemSettings';

const AdminPage: React.FC = () => {
  const { user } = useAuth();
  const [activeTab, setActiveTab] = useState('dashboard');

  // Check if user has admin access
  if (!user || !user.role || !['admin', 'moderator'].includes(user.role)) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="bg-white p-8 rounded-lg shadow-md text-center">
          <h1 className="text-2xl font-bold text-gray-900 mb-4">Access Denied</h1>
          <p className="text-gray-600">You don't have permission to access the admin panel.</p>
        </div>
      </div>
    );
  }

  const tabs = [
    { id: 'dashboard', label: 'Dashboard', icon: '📊' },
    { id: 'users', label: 'Users', icon: '👥' },
    { id: 'moderation', label: 'Moderation', icon: '🛡️' },
    { id: 'content', label: 'Content', icon: '📝' },
    { id: 'settings', label: 'Settings', icon: '⚙️' }
  ];

  const renderContent = () => {
    switch (activeTab) {
      case 'dashboard':
        return <AdminDashboard />;
      case 'users':
        return <UserManagement />;
      case 'moderation':
        return <ModerationQueue />;
      case 'content':
        return <ContentManagement />;
      case 'settings':
        return <SystemSettings />;
      default:
        return <AdminDashboard />;
    }
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white shadow">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center py-6">
            <div>
              <h1 className="text-2xl font-bold text-gray-900">Admin Panel</h1>
              <p className="text-sm text-gray-600">Welcome back, {user.username}</p>
            </div>
            <div className="flex items-center space-x-4">
              <span className="px-3 py-1 bg-purple-100 text-purple-800 text-sm font-medium rounded-full">
                {user.role || 'user'}
              </span>
              <a
                href="/"
                className="text-blue-600 hover:text-blue-800 text-sm font-medium"
              >
                ← Back to Site
              </a>
            </div>
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="flex flex-col lg:flex-row gap-8">
          {/* Sidebar Navigation */}
          <div className="lg:w-64 flex-shrink-0">
            <nav className="bg-white rounded-lg shadow p-4">
              <ul className="space-y-2">
                {tabs.map((tab) => (
                  <li key={tab.id}>
                    <button
                      onClick={() => setActiveTab(tab.id)}
                      className={`w-full flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors ${
                        activeTab === tab.id
                          ? 'bg-blue-100 text-blue-700'
                          : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                      }`}
                    >
                      <span className="mr-3">{tab.icon}</span>
                      {tab.label}
                    </button>
                  </li>
                ))}
              </ul>
            </nav>
          </div>

          {/* Main Content */}
          <div className="flex-1">
            {renderContent()}
          </div>
        </div>
      </div>
    </div>
  );
};

export default AdminPage;