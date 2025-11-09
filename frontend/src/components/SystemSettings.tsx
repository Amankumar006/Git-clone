import React, { useState, useEffect } from 'react';

interface Setting {
  value: any;
  type: string;
  description: string;
}

interface Settings {
  [key: string]: Setting;
}

const SystemSettings: React.FC = () => {
  const [settings, setSettings] = useState<Settings>({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [activeCategory, setActiveCategory] = useState('general');

  useEffect(() => {
    fetchSettings();
  }, []);

  const fetchSettings = async () => {
    try {
      const token = localStorage.getItem('token');
      const response = await fetch('/api/admin/settings', {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.error || 'Failed to fetch settings');
      }

      setSettings(data.settings);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to fetch settings');
    } finally {
      setLoading(false);
    }
  };

  const updateSetting = (key: string, value: any) => {
    setSettings(prev => ({
      ...prev,
      [key]: {
        ...prev[key],
        value
      }
    }));
  };

  const saveSettings = async () => {
    setSaving(true);
    setError('');
    setSuccess('');

    try {
      const settingsToUpdate: { [key: string]: any } = {};
      
      Object.entries(settings).forEach(([key, setting]) => {
        settingsToUpdate[key] = setting.value;
      });

      const token = localStorage.getItem('token');
      const response = await fetch('/api/admin/settings', {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(settingsToUpdate)
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.error || 'Failed to save settings');
      }

      setSuccess('Settings saved successfully');
      setTimeout(() => setSuccess(''), 3000);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to save settings');
    } finally {
      setSaving(false);
    }
  };

  const renderSettingInput = (key: string, setting: Setting) => {
    const { value, type, description } = setting;

    switch (type) {
      case 'boolean':
        return (
          <div className="flex items-center">
            <input
              type="checkbox"
              id={key}
              checked={value}
              onChange={(e) => updateSetting(key, e.target.checked)}
              className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <label htmlFor={key} className="ml-2 block text-sm text-gray-900">
              {description}
            </label>
          </div>
        );

      case 'number':
        return (
          <div>
            <input
              type="number"
              id={key}
              value={value}
              onChange={(e) => updateSetting(key, parseInt(e.target.value) || 0)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <p className="mt-1 text-sm text-gray-500">{description}</p>
          </div>
        );

      case 'json':
        return (
          <div>
            <textarea
              id={key}
              value={JSON.stringify(value, null, 2)}
              onChange={(e) => {
                try {
                  const parsed = JSON.parse(e.target.value);
                  updateSetting(key, parsed);
                } catch {
                  // Invalid JSON, don't update
                }
              }}
              rows={4}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"
            />
            <p className="mt-1 text-sm text-gray-500">{description}</p>
          </div>
        );

      case 'string':
      default:
        return (
          <div>
            <input
              type="text"
              id={key}
              value={value}
              onChange={(e) => updateSetting(key, e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <p className="mt-1 text-sm text-gray-500">{description}</p>
          </div>
        );
    }
  };

  const getSettingsByCategory = (category: string) => {
    const categoryPrefixes: { [key: string]: string[] } = {
      general: ['site_name', 'site_description', 'registration_enabled', 'maintenance_mode'],
      content: ['content_approval_required', 'max_articles_per_day', 'featured_articles_limit', 'comment_moderation_enabled'],
      moderation: ['spam_detection_enabled', 'user_verification_required', 'max_comments_per_day'],
      uploads: ['max_upload_size', 'allowed_file_types'],
      notifications: ['email_notifications_enabled'],
      social: ['social_login_enabled']
    };

    const prefixes = categoryPrefixes[category] || [];
    return Object.entries(settings).filter(([key]) => 
      prefixes.some(prefix => key.startsWith(prefix) || prefixes.includes(key))
    );
  };

  const categories = [
    { id: 'general', label: 'General', icon: '⚙️' },
    { id: 'content', label: 'Content', icon: '📝' },
    { id: 'moderation', label: 'Moderation', icon: '🛡️' },
    { id: 'uploads', label: 'Uploads', icon: '📁' },
    { id: 'notifications', label: 'Notifications', icon: '🔔' },
    { id: 'social', label: 'Social', icon: '🌐' }
  ];

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold text-gray-900">System Settings</h1>
        <button
          onClick={saveSettings}
          disabled={saving}
          className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
        >
          {saving ? 'Saving...' : 'Save Settings'}
        </button>
      </div>

      {error && (
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
          {error}
        </div>
      )}

      {success && (
        <div className="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
          {success}
        </div>
      )}

      <div className="flex flex-col lg:flex-row gap-6">
        {/* Category Navigation */}
        <div className="lg:w-64 flex-shrink-0">
          <nav className="bg-white rounded-lg shadow p-4">
            <ul className="space-y-2">
              {categories.map((category) => (
                <li key={category.id}>
                  <button
                    onClick={() => setActiveCategory(category.id)}
                    className={`w-full flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors ${
                      activeCategory === category.id
                        ? 'bg-blue-100 text-blue-700'
                        : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
                    }`}
                  >
                    <span className="mr-3">{category.icon}</span>
                    {category.label}
                  </button>
                </li>
              ))}
            </ul>
          </nav>
        </div>

        {/* Settings Form */}
        <div className="flex-1">
          <div className="bg-white rounded-lg shadow p-6">
            <h2 className="text-lg font-semibold text-gray-900 mb-6">
              {categories.find(c => c.id === activeCategory)?.label} Settings
            </h2>

            <div className="space-y-6">
              {getSettingsByCategory(activeCategory).map(([key, setting]) => (
                <div key={key}>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    {key.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')}
                  </label>
                  {renderSettingInput(key, setting)}
                </div>
              ))}

              {getSettingsByCategory(activeCategory).length === 0 && (
                <div className="text-center text-gray-500 py-8">
                  No settings available for this category.
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Advanced Settings Warning */}
      <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div className="flex">
          <div className="flex-shrink-0">
            <svg className="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
              <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
            </svg>
          </div>
          <div className="ml-3">
            <h3 className="text-sm font-medium text-yellow-800">
              Important Notice
            </h3>
            <div className="mt-2 text-sm text-yellow-700">
              <p>
                Changes to these settings will affect the entire platform. Please review carefully before saving.
                Some changes may require users to log out and log back in to take effect.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default SystemSettings;