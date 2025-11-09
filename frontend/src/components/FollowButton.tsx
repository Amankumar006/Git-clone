import React, { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { apiService } from '../utils/api';

interface FollowButtonProps {
  userId: number;
  username?: string;
  initialFollowing?: boolean;
  initialFollowerCount?: number;
  onFollowUpdate?: (isFollowing: boolean, followerCount: number) => void;
  className?: string;
  size?: 'sm' | 'md' | 'lg';
}

const FollowButton: React.FC<FollowButtonProps> = ({
  userId,
  username,
  initialFollowing = false,
  initialFollowerCount = 0,
  onFollowUpdate,
  className = '',
  size = 'md'
}) => {
  const { user } = useAuth();
  const [isFollowing, setIsFollowing] = useState(initialFollowing);
  const [followerCount, setFollowerCount] = useState(initialFollowerCount);
  const [isLoading, setIsLoading] = useState(false);

  // Don't show follow button for own profile
  const isOwnProfile = user && user.id === userId;

  // Fetch follow status when component mounts or user changes
  useEffect(() => {
    if (user && userId && !isOwnProfile) {
      fetchFollowStatus();
    }
  }, [user, userId, isOwnProfile]);

  const fetchFollowStatus = async () => {
    try {
      const response = await apiService.get(`/follows/status/${userId}`);
      if (response.success) {
        const data = response.data as { is_following: boolean; follower_count: number };
        setIsFollowing(data.is_following);
        setFollowerCount(data.follower_count);
      }
    } catch (error) {
      console.error('Error fetching follow status:', error);
    }
  };

  const handleFollowToggle = async () => {
    if (!user) {
      alert('Please log in to follow users');
      return;
    }

    if (isLoading) return;

    setIsLoading(true);

    try {
      let response;
      if (isFollowing) {
        response = await apiService.delete('/follows/unfollow', {
          data: { user_id: userId }
        });
      } else {
        response = await apiService.post('/follows/follow', {
          user_id: userId
        });
      }

      if (response.success) {
        const data = response.data as { is_following: boolean; follower_count: number };
        const newFollowingState = data.is_following;
        const newFollowerCount = data.follower_count;
        
        setIsFollowing(newFollowingState);
        setFollowerCount(newFollowerCount);
        
        if (onFollowUpdate) {
          onFollowUpdate(newFollowingState, newFollowerCount);
        }
      }
    } catch (error: any) {
      console.error('Error toggling follow:', error);
      if (error.error?.message) {
        alert(error.error.message);
      }
    } finally {
      setIsLoading(false);
    }
  };

  // Don't render if it's the user's own profile
  if (isOwnProfile) {
    return null;
  }

  const sizeClasses = {
    sm: 'px-3 py-1 text-sm',
    md: 'px-4 py-2 text-sm',
    lg: 'px-6 py-3 text-base'
  };

  return (
    <button
      onClick={handleFollowToggle}
      disabled={!user || isLoading}
      className={`
        flex items-center space-x-2 rounded-lg font-medium transition-all duration-200
        ${isFollowing 
          ? 'bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-300' 
          : 'bg-blue-600 text-white hover:bg-blue-700'
        }
        ${!user ? 'opacity-50 cursor-not-allowed' : 'hover:scale-105'}
        ${isLoading ? 'opacity-75' : ''}
        ${sizeClasses[size]}
        ${className}
      `}
      title={isFollowing ? `Unfollow ${username || 'user'}` : `Follow ${username || 'user'}`}
    >
      {/* Follow/Unfollow Icon */}
      {isFollowing ? (
        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
        </svg>
      ) : (
        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
      )}

      {/* Loading spinner */}
      {isLoading ? (
        <div className="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin"></div>
      ) : (
        <span>
          {isFollowing ? 'Following' : 'Follow'}
        </span>
      )}
    </button>
  );
};

export default FollowButton;