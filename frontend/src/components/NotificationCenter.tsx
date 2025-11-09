import React, { useState, useEffect, useCallback } from 'react';
import { useAuth } from '../context/AuthContext';
import { apiService } from '../utils/api';

interface Notification {
  id: number;
  type: 'follow' | 'clap' | 'comment' | 'publication_invite';
  content: string;
  related_id?: number;
  is_read: boolean;
  created_at: string;
}

interface NotificationCenterProps {
  className?: string;
}

const NotificationCenter: React.FC<NotificationCenterProps> = ({ className = '' }) => {
  const { user } = useAuth();
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [loading, setLoading] = useState(false);
  const [isOpen, setIsOpen] = useState(false);
  const [showUnreadOnly, setShowUnreadOnly] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Ensure notifications is always an array
  const safeNotifications = Array.isArray(notifications) ? notifications : [];

  const fetchUnreadCount = useCallback(async () => {
    if (!user) return;

    try {
      const response = await apiService.notifications.getUnreadCount();
      if (response.success && response.data) {
        const count = (response.data as { unread_count: number }).unread_count;
        setUnreadCount(count || 0);
      }
    } catch (error) {
      console.error('Error fetching unread count:', error);
      // Silently fail - don't update state to prevent loops
    }
  }, [user]);

  const fetchNotifications = useCallback(async () => {
    if (!user || loading) return;

    // Set a timeout to prevent infinite loading
    const timeoutId = setTimeout(() => {
      console.log('Notifications request timed out');
      setLoading(false);
      setNotifications([]);
    }, 10000); // 10 second timeout

    try {
      setLoading(true);
      setError(null);
      const response = await apiService.notifications.getAll(showUnreadOnly);

      // Clear timeout since request completed
      clearTimeout(timeoutId);

      if (response.success && response.data) {
        // Handle different data structures
        let notificationData: Notification[] = [];
        
        if (Array.isArray(response.data)) {
          notificationData = response.data;
        } else if (response.data && typeof response.data === 'object') {
          const data = response.data as any;
          if (data.notifications && Array.isArray(data.notifications)) {
            notificationData = data.notifications;
          } else if (data.data && Array.isArray(data.data)) {
            notificationData = data.data;
          }
        }
        
        // Also update unread count from the response if available
        if (response.data && typeof response.data === 'object') {
          const data = response.data as any;
          if (typeof data.unread_count === 'number') {
            setUnreadCount(data.unread_count);
          }
        }
        
        setNotifications(notificationData as Notification[]);
      } else {
        setError(response.message || 'Failed to load notifications');
        setNotifications([]);
      }
    } catch (error: any) {
      console.error('Error fetching notifications:', error);
      // Clear timeout since request completed (with error)
      clearTimeout(timeoutId);
      
      // Handle different error types
      if (error?.error?.message) {
        // ApiError type
        setError(error.error.message);
      } else if (error?.message) {
        // Standard error
        setError(error.message);
      } else {
        setError('Failed to load notifications');
      }
      
      // Set empty array on error to prevent map errors
      setNotifications([]);
    } finally {
      setLoading(false);
    }
  }, [user, showUnreadOnly]);

  // Initial fetch when user is available
  useEffect(() => {
    if (user) {
      fetchUnreadCount();
    }
  }, [user, fetchUnreadCount]);

  // Fetch notifications when dropdown opens
  useEffect(() => {
    if (isOpen && user) {
      console.log('Dropdown opened, user:', user);
      fetchNotifications();
    } else if (isOpen && !user) {
      console.log('Dropdown opened but no user authenticated');
      setError('Please log in to view notifications');
      setLoading(false);
    }
  }, [isOpen, user, fetchNotifications]);

  // Optional: Add polling with proper cleanup and error handling
  useEffect(() => {
    if (!user) return;

    // Poll every 60 seconds (increased from 30 to reduce load)
    const interval = setInterval(() => {
      fetchUnreadCount();
    }, 60000);

    return () => clearInterval(interval);
  }, [user, fetchUnreadCount]);

  const markAsRead = async (notificationId: number) => {
    try {
      const response = await apiService.notifications.markAsRead(notificationId);
      if (response.success) {
        setNotifications(prev =>
          prev.map(n =>
            n.id === notificationId ? { ...n, is_read: true } : n
          )
        );
        setUnreadCount((response.data as { unread_count: number }).unread_count);
      }
    } catch (error) {
      console.error('Error marking notification as read:', error);
    }
  };

  const markAllAsRead = async () => {
    try {
      const response = await apiService.notifications.markAllAsRead();
      if (response.success) {
        setNotifications(prev =>
          prev.map(n => ({ ...n, is_read: true }))
        );
        setUnreadCount(0);
      }
    } catch (error) {
      console.error('Error marking all as read:', error);
    }
  };

  const deleteNotification = async (notificationId: number) => {
    try {
      const response = await apiService.notifications.delete(notificationId);
      if (response.success) {
        setNotifications(prev => prev.filter(n => n.id !== notificationId));
        setUnreadCount((response.data as { unread_count: number }).unread_count);
      }
    } catch (error) {
      console.error('Error deleting notification:', error);
    }
  };

  const getNotificationIcon = (type: string) => {
    switch (type) {
      case 'follow':
        return (
          <div className="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
            <svg className="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
        );
      case 'clap':
        return (
          <div className="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
            <svg className="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 24 24">
              <path d="M11.5 2C12.3 2 13 2.7 13 3.5V11H14.5C15.3 11 16 11.7 16 12.5S15.3 14 14.5 14H13V15.5C13 16.3 12.3 17 11.5 17S10 16.3 10 15.5V14H8.5C7.7 14 7 13.3 7 12.5S7.7 11 8.5 11H10V3.5C10 2.7 10.7 2 11.5 2Z" />
            </svg>
          </div>
        );
      case 'comment':
        return (
          <div className="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
            <svg className="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
          </div>
        );
      case 'publication_invite':
        return (
          <div className="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
            <svg className="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
          </div>
        );
      default:
        return (
          <div className="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
            <svg className="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-5 5v-5zM4 19h6v-6H4v6zM16 3h5v5h-5V3zM4 3h6v6H4V3z" />
            </svg>
          </div>
        );
    }
  };

  if (!user) {
    return null;
  }

  return (
    <div className={`relative ${className}`}>
      {/* Notification Bell */}
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="relative p-2 text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-lg"
      >
        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-5 5v-5zM4 19h6v-6H4v6zM16 3h5v5h-5V3zM4 3h6v6H4V3z" />
        </svg>

        {/* Unread Badge */}
        {unreadCount > 0 && (
          <span className="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
            {unreadCount > 99 ? '99+' : unreadCount}
          </span>
        )}
      </button>

      {/* Notification Dropdown */}
      {isOpen && (
        <div className="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
          {/* Header */}
          <div className="p-4 border-b border-gray-200">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-semibold text-gray-900">Notifications</h3>
              <button
                onClick={() => setIsOpen(false)}
                className="text-gray-400 hover:text-gray-600"
              >
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            {/* Controls */}
            <div className="flex items-center justify-between mt-3">
              <label className="flex items-center">
                <input
                  type="checkbox"
                  checked={showUnreadOnly}
                  onChange={(e) => setShowUnreadOnly(e.target.checked)}
                  className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <span className="ml-2 text-sm text-gray-600">Unread only</span>
              </label>

              {unreadCount > 0 && (
                <button
                  onClick={markAllAsRead}
                  className="text-sm text-blue-600 hover:text-blue-800"
                >
                  Mark all read
                </button>
              )}
            </div>
          </div>

          {/* Notifications List */}
          <div className="max-h-96 overflow-y-auto">
            {loading ? (
              <div className="p-4 text-center">
                <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto"></div>
                <p className="text-gray-500 text-sm mt-2">Loading...</p>
              </div>
            ) : error ? (
              <div className="p-8 text-center">
                <svg className="w-12 h-12 text-red-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                <p className="text-gray-500 text-sm mb-3">{error}</p>
                <button
                  onClick={() => fetchNotifications()}
                  className="text-blue-600 hover:text-blue-800 text-sm font-medium"
                >
                  Try again
                </button>
              </div>
            ) : safeNotifications.length === 0 ? (
              <div className="p-8 text-center">
                <svg className="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-5 5v-5zM4 19h6v-6H4v6zM16 3h5v5h-5V3zM4 3h6v6H4V3z" />
                </svg>
                <p className="text-gray-500">No notifications</p>
              </div>
            ) : (
              safeNotifications.map((notification) => (
                <div
                  key={notification.id}
                  className={`p-4 border-b border-gray-100 hover:bg-gray-50 ${!notification.is_read ? 'bg-blue-50' : ''
                    }`}
                >
                  <div className="flex items-start space-x-3">
                    {getNotificationIcon(notification.type)}

                    <div className="flex-1 min-w-0">
                      <p className="text-sm text-gray-900">
                        {notification.content}
                      </p>
                      <p className="text-xs text-gray-500 mt-1">
                        {new Date(notification.created_at).toLocaleDateString()}
                      </p>
                    </div>

                    <div className="flex items-center space-x-1">
                      {!notification.is_read && (
                        <button
                          onClick={() => markAsRead(notification.id)}
                          className="text-blue-600 hover:text-blue-800 text-xs"
                          title="Mark as read"
                        >
                          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                          </svg>
                        </button>
                      )}

                      <button
                        onClick={() => deleteNotification(notification.id)}
                        className="text-red-600 hover:text-red-800 text-xs"
                        title="Delete"
                      >
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              ))
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default NotificationCenter;