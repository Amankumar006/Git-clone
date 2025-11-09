import React, { useState } from 'react';
import { useAuth } from '../context/AuthContext';
import WriterDashboard from '../components/WriterDashboard';
import ReaderDashboard from '../components/ReaderDashboard';

const DashboardPage: React.FC = () => {
  const { user } = useAuth();
  const [activeTab, setActiveTab] = useState<'writer' | 'reader'>('writer');

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Tab Navigation */}
      <div className="bg-white shadow">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex space-x-8">
            <button
              onClick={() => setActiveTab('writer')}
              className={`py-4 px-1 border-b-2 font-medium text-sm ${
                activeTab === 'writer'
                  ? 'border-indigo-500 text-indigo-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              Writer Dashboard
            </button>
            <button
              onClick={() => setActiveTab('reader')}
              className={`py-4 px-1 border-b-2 font-medium text-sm ${
                activeTab === 'reader'
                  ? 'border-indigo-500 text-indigo-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              Reader Dashboard
            </button>
          </div>
        </div>
      </div>

      {/* Tab Content */}
      {activeTab === 'writer' && <WriterDashboard />}
      {activeTab === 'reader' && <ReaderDashboard />}
    </div>
  );
};

export default DashboardPage;