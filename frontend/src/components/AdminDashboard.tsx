import React, { useState, useEffect } from 'react';

interface DashboardStats {
  users: {
    total_users: number;
    new_users_30d: number;
    new_users_7d: number;
    suspended_users: number;
    admin_users: number;
    moderator_users: number;
  };
  content: {
    total_articles: number;
    new_articles_30d: number;
    published_articles: number;
    flagged_articles: number;
    total_comments: number;
    new_comments_30d: number;
    flagged_comments: number;
  };
  engagement: {
    claps_30d: number;
    comments_30d: number;
    bookmarks_30d: number;
    follows_30d: number;
  };
  moderation: {
    total_actions: number;
    approvals: number;
    removals: number;
    warnings: number;
    suspensions: number;
    bans: number;
  };
}

const AdminDashboard: React.FC = () => {
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    fetchDashboardStats();
  }, []);

  const fetchDashboardStats = async () => {
    try {
      const token = localStorage.getItem('token');
      const response = await fetch('/api/admin/dashboard', {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.error || 'Failed to fetch dashboard stats');
      }

      setStats(data);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to fetch dashboard stats');
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        {error}
      </div>
    );
  }

  if (!stats) {
    return (
      <div className="text-center text-gray-500">
        No dashboard data available
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold text-gray-900">Admin Dashboard</h1>
        <button
          onClick={fetchDashboardStats}
          className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
        >
          Refresh
        </button>
      </div>

      {/* User Statistics */}
      <div>
        <h2 className="text-lg font-semibold text-gray-900 mb-4">User Statistics</h2>
        <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-blue-600">{stats.users.total_users}</div>
            <div className="text-sm text-gray-600">Total Users</div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-green-600">{stats.users.new_users_30d}</div>
            <div className="text-sm text-gray-600">New (30d)</div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-green-500">{stats.users.new_users_7d}</div>
            <div className="text-sm text-gray-600">New (7d)</div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-red-600">{stats.users.suspended_users}</div>
            <div className="text-sm text-gray-600">Suspended</div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-purple-600">{stats.users.admin_users}</div>
            <div className="text-sm text-gray-600">Admins</div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-indigo-600">{stats.users.moderator_users}</div>
            <div className="text-sm text-gray-600">Moderators</div>
          </div>
        </div>
      </div>

      {/* Content Statistics */}
      <div>
        <h2 className="text-lg font-semibold text-gray-900 mb-4">Content Statistics</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-blue-600">{stats.content.total_articles}</div>
            <div className="text-sm text-gray-600">Total Articles</div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-green-600">{stats.content.new_articles_30d}</div>
            <div className="text-sm text-gray-600">New Articles (30d)</div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-yellow-600">{stats.content.flagged_articles}</div>
            <div className="text-sm text-gray-600">Flagged Articles</div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-orange-600">{stats.content.flagged_comments}</div>
            <div className="text-sm text-gray-600">Flagged Comments</div>
          </div>
        </div>
      </div>

      {/* Engagement Statistics */}
      <div>
        <h2 className="text-lg font-semibold text-gray-900 mb-4">Engagement (30 days)</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-red-500">{stats.engagement.claps_30d}</div>
            <div className="text-sm text-gray-600">Claps</div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-blue-500">{stats.engagement.comments_30d}</div>
            <div className="text-sm text-gray-600">Comments</div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-yellow-500">{stats.engagement.bookmarks_30d}</div>
            <div className="text-sm text-gray-600">Bookmarks</div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-green-500">{stats.engagement.follows_30d}</div>
            <div className="text-sm text-gray-600">Follows</div>
          </div>
        </div>
      </div>

      {/* Moderation Statistics */}
      <div>
        <h2 className="text-lg font-semibold text-gray-900 mb-4">Moderation Actions (30 days)</h2>
        <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-gray-600">{stats.moderation.total_actions}</div>
            <div className="text-sm text-gray-600">Total Actions</div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-green-600">{stats.moderation.approvals}</div>
            <div className="text-sm text-gray-600">Approvals</div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-red-600">{stats.moderation.removals}</div>
            <div className="text-sm text-gray-600">Removals</div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-yellow-600">{stats.moderation.warnings}</div>
            <div className="text-sm text-gray-600">Warnings</div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-orange-600">{stats.moderation.suspensions}</div>
            <div className="text-sm text-gray-600">Suspensions</div>
          </div>
          <div className="bg-white p-4 rounded-lg shadow">
            <div className="text-2xl font-bold text-red-800">{stats.moderation.bans}</div>
            <div className="text-sm text-gray-600">Bans</div>
          </div>
        </div>
      </div>

      {/* Quick Actions */}
      <div>
        <h2 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <a
            href="/admin/users"
            className="bg-white p-4 rounded-lg shadow hover:shadow-md transition-shadow border-l-4 border-blue-500"
          >
            <div className="font-medium text-gray-900">Manage Users</div>
            <div className="text-sm text-gray-600">View and manage user accounts</div>
          </a>
          <a
            href="/admin/moderation"
            className="bg-white p-4 rounded-lg shadow hover:shadow-md transition-shadow border-l-4 border-yellow-500"
          >
            <div className="font-medium text-gray-900">Moderation Queue</div>
            <div className="text-sm text-gray-600">Review reported content</div>
          </a>
          <a
            href="/admin/content"
            className="bg-white p-4 rounded-lg shadow hover:shadow-md transition-shadow border-l-4 border-green-500"
          >
            <div className="font-medium text-gray-900">Content Management</div>
            <div className="text-sm text-gray-600">Manage articles and comments</div>
          </a>
          <a
            href="/admin/settings"
            className="bg-white p-4 rounded-lg shadow hover:shadow-md transition-shadow border-l-4 border-purple-500"
          >
            <div className="font-medium text-gray-900">System Settings</div>
            <div className="text-sm text-gray-600">Configure platform settings</div>
          </a>
        </div>
      </div>
    </div>
  );
};

export default AdminDashboard;