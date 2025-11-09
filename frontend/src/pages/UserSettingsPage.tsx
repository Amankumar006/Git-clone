import React, { useState, useRef } from 'react';
import { useAuth } from '../context/AuthContext';
import { apiService } from '../utils/api';

interface UpdateProfileResponse {
  user: any;
}



const UserSettingsPage: React.FC = () => {
  const { user, updateUser } = useAuth();
  const [activeTab, setActiveTab] = useState('profile');
  const [isLoading, setIsLoading] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const fileInputRef = useRef<HTMLInputElement>(null);

  // Profile form state
  const [profileData, setProfileData] = useState({
    bio: user?.bio || '',
    social_links: user?.social_links || {}
  });

  // Password form state
  const [passwordData, setPasswordData] = useState({
    current_password: '',
    new_password: '',
    confirm_password: ''
  });

  // Notification preferences state
  const [notificationPreferences, setNotificationPreferences] = useState({
    email_notifications: {
      follows: true,
      claps: true,
      comments: true,
      publication_invites: true,
      weekly_digest: true
    },
    push_notifications: {
      follows: true,
      claps: true,
      comments: true,
      publication_invites: true
    }
  });

  const handleProfileSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setError('');
    setMessage('');

    try {
      const response = await apiService.users.updateProfile(profileData);
      
      if (response.success) {
        const responseData = response.data as UpdateProfileResponse;
        updateUser(responseData.user);
        setMessage('Profile updated successfully');
      } else {
        setError('Failed to update profile');
      }
    } catch (err: any) {
      setError(err.message || 'Failed to update profile');
    } finally {
      setIsLoading(false);
    }
  };

  const handlePasswordSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setMessage('');

    if (passwordData.new_password !== passwordData.confirm_password) {
      setError('New passwords do not match');
      return;
    }

    setIsLoading(true);

    try {
      const response = await apiService.users.updatePassword({
        current_password: passwordData.current_password,
        new_password: passwordData.new_password
      });
      
      if (response.success) {
        setMessage('Password updated successfully');
        setPasswordData({
          current_password: '',
          new_password: '',
          confirm_password: ''
        });
      } else {
        setError('Failed to update password');
      }
    } catch (err: any) {
      setError(err.message || 'Failed to update password');
    } finally {
      setIsLoading(false);
    }
  };

  const handleAvatarUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    setIsLoading(true);
    setError('');
    setMessage('');

    try {
      const response = await apiService.users.uploadAvatar(file);
      
      if (response.success) {
        const responseData = response.data as UpdateProfileResponse;
        updateUser(responseData.user);
        setMessage('Profile picture updated successfully');
      } else {
        setError('Failed to upload profile picture');
      }
    } catch (err: any) {
      setError(err.message || 'Failed to upload profile picture');
    } finally {
      setIsLoading(false);
    }
  };

  const handleSocialLinkChange = (platform: string, url: string) => {
    setProfileData(prev => ({
      ...prev,
      social_links: {
        ...prev.social_links,
        [platform]: url
      }
    }));
  };

  // Load notification preferences on component mount
  React.useEffect(() => {
    if (user) {
      loadNotificationPreferences();
    }
  }, [user]);

  const loadNotificationPreferences = async () => {
    try {
      const response = await apiService.users.getNotificationPreferences();
      if (response.success && response.data) {
        setNotificationPreferences((response.data as any).preferences);
      }
    } catch (err: any) {
      console.error('Failed to load notification preferences:', err);
    }
  };

  const handleNotificationSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);
    setError('');
    setMessage('');

    try {
      const response = await apiService.users.updateNotificationPreferences(notificationPreferences);
      
      if (response.success) {
        setMessage('Notification preferences updated successfully');
      } else {
        setError('Failed to update notification preferences');
      }
    } catch (err: any) {
      setError(err.message || 'Failed to update notification preferences');
    } finally {
      setIsLoading(false);
    }
  };

  const handleNotificationChange = (category: 'email_notifications' | 'push_notifications', type: string, value: boolean) => {
    setNotificationPreferences(prev => ({
      ...prev,
      [category]: {
        ...prev[category],
        [type]: value
      }
    }));
  };

  const tabs = [
    { id: 'profile', label: 'Profile' },
    { id: 'account', label: 'Account' },
    { id: 'notifications', label: 'Notifications' },
    { id: 'password', label: 'Password' }
  ];

  return (
    <div className="max-w-4xl mx-auto px-4 py-8">
      <div className="bg-white rounded-lg shadow-sm border">
        {/* Header */}
        <div className="p-6 border-b">
          <h1 className="text-2xl font-bold text-gray-900">Settings</h1>
          <p className="text-gray-600 mt-1">Manage your account settings and preferences</p>
        </div>

        {/* Tabs */}
        <div className="border-b">
          <nav className="flex space-x-8 px-6">
            {tabs.map((tab) => (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={`py-4 px-1 border-b-2 font-medium text-sm transition-colors ${
                  activeTab === tab.id
                    ? 'border-primary-500 text-primary-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                {tab.label}
              </button>
            ))}
          </nav>
        </div>

        {/* Messages */}
        {message && (
          <div className="mx-6 mt-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
            {message}
          </div>
        )}
        {error && (
          <div className="mx-6 mt-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
            {error}
          </div>
        )}

        {/* Tab Content */}
        <div className="p-6">
          {activeTab === 'profile' && (
            <form onSubmit={handleProfileSubmit} className="space-y-6">
              {/* Profile Picture */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Profile Picture
                </label>
                <div className="flex items-center space-x-4">
                  {user?.profile_image_url ? (
                    <img
                      src={user.profile_image_url}
                      alt={user.username}
                      className="w-16 h-16 rounded-full object-cover"
                    />
                  ) : (
                    <div className="w-16 h-16 rounded-full bg-gray-200 flex items-center justify-center">
                      <span className="text-xl font-bold text-gray-500">
                        {user?.username.charAt(0).toUpperCase()}
                      </span>
                    </div>
                  )}
                  <div>
                    <button
                      type="button"
                      onClick={() => fileInputRef.current?.click()}
                      className="btn-secondary"
                    >
                      Change Picture
                    </button>
                    <input
                      ref={fileInputRef}
                      type="file"
                      accept="image/*"
                      onChange={handleAvatarUpload}
                      className="hidden"
                    />
                    <p className="text-xs text-gray-500 mt-1">
                      JPG, PNG or GIF. Max size 5MB.
                    </p>
                  </div>
                </div>
              </div>

              {/* Bio */}
              <div>
                <label htmlFor="bio" className="block text-sm font-medium text-gray-700">
                  Bio
                </label>
                <textarea
                  id="bio"
                  rows={4}
                  className="input-field mt-1"
                  placeholder="Tell us about yourself..."
                  value={profileData.bio}
                  onChange={(e) => setProfileData(prev => ({ ...prev, bio: e.target.value }))}
                  maxLength={500}
                />
                <p className="text-xs text-gray-500 mt-1">
                  {profileData.bio.length}/500 characters
                </p>
              </div>

              {/* Profile Preview */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Profile Preview
                </label>
                <div className="bg-gray-50 border border-gray-200 rounded-lg p-4">
                  <div className="flex items-start space-x-4">
                    {user?.profile_image_url ? (
                      <img
                        src={user.profile_image_url}
                        alt={user.username}
                        className="w-12 h-12 rounded-full object-cover"
                      />
                    ) : (
                      <div className="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center">
                        <span className="text-lg font-bold text-gray-500">
                          {user?.username.charAt(0).toUpperCase()}
                        </span>
                      </div>
                    )}
                    <div className="flex-grow">
                      <h3 className="font-semibold text-gray-900">{user?.username}</h3>
                      {profileData.bio && (
                        <p className="text-gray-600 text-sm mt-1">{profileData.bio}</p>
                      )}
                      {Object.keys(profileData.social_links).some(key => profileData.social_links[key]) && (
                        <div className="flex space-x-3 mt-2">
                          {Object.entries(profileData.social_links).map(([platform, url]) => 
                            url && (
                              <a
                                key={platform}
                                href={url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="text-primary-600 hover:text-primary-500 text-sm capitalize"
                              >
                                {platform}
                              </a>
                            )
                          )}
                        </div>
                      )}
                    </div>
                  </div>
                </div>
              </div>

              {/* Social Links */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-3">
                  Social Links
                </label>
                <div className="space-y-3">
                  {['twitter', 'linkedin', 'github', 'website'].map((platform) => (
                    <div key={platform}>
                      <label htmlFor={platform} className="block text-sm text-gray-600 capitalize">
                        {platform}
                      </label>
                      <input
                        id={platform}
                        type="url"
                        className="input-field mt-1"
                        placeholder={`https://${platform === 'website' ? 'yourwebsite.com' : platform + '.com/username'}`}
                        value={profileData.social_links[platform] || ''}
                        onChange={(e) => handleSocialLinkChange(platform, e.target.value)}
                      />
                    </div>
                  ))}
                </div>
              </div>

              <div className="flex justify-end">
                <button
                  type="submit"
                  disabled={isLoading}
                  className="btn-primary disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {isLoading ? 'Saving...' : 'Save Changes'}
                </button>
              </div>
            </form>
          )}

          {activeTab === 'account' && (
            <div className="space-y-6">
              <div>
                <h3 className="text-lg font-medium text-gray-900 mb-4">Account Information</h3>
                <div className="space-y-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700">Username</label>
                    <input
                      type="text"
                      value={user?.username || ''}
                      disabled
                      className="input-field mt-1 bg-gray-50 cursor-not-allowed"
                    />
                    <p className="text-xs text-gray-500 mt-1">Username cannot be changed</p>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700">Email</label>
                    <input
                      type="email"
                      value={user?.email || ''}
                      disabled
                      className="input-field mt-1 bg-gray-50 cursor-not-allowed"
                    />
                    <div className="flex items-center mt-1">
                      {user?.email_verified ? (
                        <span className="text-xs text-green-600 flex items-center">
                          <svg className="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                          </svg>
                          Verified
                        </span>
                      ) : (
                        <span className="text-xs text-red-600">Not verified</span>
                      )}
                    </div>
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700">Member Since</label>
                    <input
                      type="text"
                      value={user ? new Date(user.created_at).toLocaleDateString() : ''}
                      disabled
                      className="input-field mt-1 bg-gray-50 cursor-not-allowed"
                    />
                  </div>
                </div>
              </div>
            </div>
          )}

          {activeTab === 'notifications' && (
            <form onSubmit={handleNotificationSubmit} className="space-y-6">
              <div>
                <h3 className="text-lg font-medium text-gray-900 mb-4">Notification Preferences</h3>
                <p className="text-gray-600 mb-6">Choose how you want to be notified about activity on your account.</p>
                
                {/* Email Notifications */}
                <div className="mb-8">
                  <h4 className="text-md font-medium text-gray-900 mb-4">Email Notifications</h4>
                  <div className="space-y-4">
                    <div className="flex items-center justify-between">
                      <div>
                        <label className="text-sm font-medium text-gray-700">New Followers</label>
                        <p className="text-xs text-gray-500">Get notified when someone follows you</p>
                      </div>
                      <input
                        type="checkbox"
                        checked={notificationPreferences.email_notifications.follows}
                        onChange={(e) => handleNotificationChange('email_notifications', 'follows', e.target.checked)}
                        className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                      />
                    </div>
                    
                    <div className="flex items-center justify-between">
                      <div>
                        <label className="text-sm font-medium text-gray-700">Claps on Articles</label>
                        <p className="text-xs text-gray-500">Get notified when someone claps for your articles</p>
                      </div>
                      <input
                        type="checkbox"
                        checked={notificationPreferences.email_notifications.claps}
                        onChange={(e) => handleNotificationChange('email_notifications', 'claps', e.target.checked)}
                        className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                      />
                    </div>
                    
                    <div className="flex items-center justify-between">
                      <div>
                        <label className="text-sm font-medium text-gray-700">Comments on Articles</label>
                        <p className="text-xs text-gray-500">Get notified when someone comments on your articles</p>
                      </div>
                      <input
                        type="checkbox"
                        checked={notificationPreferences.email_notifications.comments}
                        onChange={(e) => handleNotificationChange('email_notifications', 'comments', e.target.checked)}
                        className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                      />
                    </div>
                    
                    <div className="flex items-center justify-between">
                      <div>
                        <label className="text-sm font-medium text-gray-700">Publication Invites</label>
                        <p className="text-xs text-gray-500">Get notified when you're invited to join a publication</p>
                      </div>
                      <input
                        type="checkbox"
                        checked={notificationPreferences.email_notifications.publication_invites}
                        onChange={(e) => handleNotificationChange('email_notifications', 'publication_invites', e.target.checked)}
                        className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                      />
                    </div>
                    
                    <div className="flex items-center justify-between">
                      <div>
                        <label className="text-sm font-medium text-gray-700">Weekly Digest</label>
                        <p className="text-xs text-gray-500">Get a weekly summary of activity and trending articles</p>
                      </div>
                      <input
                        type="checkbox"
                        checked={notificationPreferences.email_notifications.weekly_digest}
                        onChange={(e) => handleNotificationChange('email_notifications', 'weekly_digest', e.target.checked)}
                        className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                      />
                    </div>
                  </div>
                </div>

                {/* Push Notifications */}
                <div className="mb-8">
                  <h4 className="text-md font-medium text-gray-900 mb-4">In-App Notifications</h4>
                  <div className="space-y-4">
                    <div className="flex items-center justify-between">
                      <div>
                        <label className="text-sm font-medium text-gray-700">New Followers</label>
                        <p className="text-xs text-gray-500">Show notifications when someone follows you</p>
                      </div>
                      <input
                        type="checkbox"
                        checked={notificationPreferences.push_notifications.follows}
                        onChange={(e) => handleNotificationChange('push_notifications', 'follows', e.target.checked)}
                        className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                      />
                    </div>
                    
                    <div className="flex items-center justify-between">
                      <div>
                        <label className="text-sm font-medium text-gray-700">Claps on Articles</label>
                        <p className="text-xs text-gray-500">Show notifications when someone claps for your articles</p>
                      </div>
                      <input
                        type="checkbox"
                        checked={notificationPreferences.push_notifications.claps}
                        onChange={(e) => handleNotificationChange('push_notifications', 'claps', e.target.checked)}
                        className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                      />
                    </div>
                    
                    <div className="flex items-center justify-between">
                      <div>
                        <label className="text-sm font-medium text-gray-700">Comments on Articles</label>
                        <p className="text-xs text-gray-500">Show notifications when someone comments on your articles</p>
                      </div>
                      <input
                        type="checkbox"
                        checked={notificationPreferences.push_notifications.comments}
                        onChange={(e) => handleNotificationChange('push_notifications', 'comments', e.target.checked)}
                        className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                      />
                    </div>
                    
                    <div className="flex items-center justify-between">
                      <div>
                        <label className="text-sm font-medium text-gray-700">Publication Invites</label>
                        <p className="text-xs text-gray-500">Show notifications when you're invited to join a publication</p>
                      </div>
                      <input
                        type="checkbox"
                        checked={notificationPreferences.push_notifications.publication_invites}
                        onChange={(e) => handleNotificationChange('push_notifications', 'publication_invites', e.target.checked)}
                        className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                      />
                    </div>
                  </div>
                </div>
              </div>

              <div className="flex justify-end">
                <button
                  type="submit"
                  disabled={isLoading}
                  className="btn-primary disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {isLoading ? 'Saving...' : 'Save Preferences'}
                </button>
              </div>
            </form>
          )}

          {activeTab === 'password' && (
            <form onSubmit={handlePasswordSubmit} className="space-y-6">
              <div>
                <h3 className="text-lg font-medium text-gray-900 mb-4">Change Password</h3>
                <div className="space-y-4">
                  <div>
                    <label htmlFor="current_password" className="block text-sm font-medium text-gray-700">
                      Current Password
                    </label>
                    <input
                      id="current_password"
                      type="password"
                      required
                      className="input-field mt-1"
                      value={passwordData.current_password}
                      onChange={(e) => setPasswordData(prev => ({ ...prev, current_password: e.target.value }))}
                    />
                  </div>
                  <div>
                    <label htmlFor="new_password" className="block text-sm font-medium text-gray-700">
                      New Password
                    </label>
                    <input
                      id="new_password"
                      type="password"
                      required
                      className="input-field mt-1"
                      value={passwordData.new_password}
                      onChange={(e) => setPasswordData(prev => ({ ...prev, new_password: e.target.value }))}
                    />
                    <p className="text-xs text-gray-500 mt-1">
                      Must be at least 8 characters with uppercase, lowercase, number, and special character.
                    </p>
                  </div>
                  <div>
                    <label htmlFor="confirm_password" className="block text-sm font-medium text-gray-700">
                      Confirm New Password
                    </label>
                    <input
                      id="confirm_password"
                      type="password"
                      required
                      className="input-field mt-1"
                      value={passwordData.confirm_password}
                      onChange={(e) => setPasswordData(prev => ({ ...prev, confirm_password: e.target.value }))}
                    />
                  </div>
                </div>
              </div>

              <div className="flex justify-end">
                <button
                  type="submit"
                  disabled={isLoading}
                  className="btn-primary disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {isLoading ? 'Updating...' : 'Update Password'}
                </button>
              </div>
            </form>
          )}
        </div>
      </div>
    </div>
  );
};

export default UserSettingsPage;