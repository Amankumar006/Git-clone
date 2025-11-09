import React, { useState, useEffect } from 'react';
import { useAuth } from '../context/AuthContext';
import { apiService } from '../utils/api';
import './ClapButton.css';

interface ClapButtonProps {
  articleId: number;
  initialClapCount?: number;
  initialUserClaps?: number;
  onClapUpdate?: (totalClaps: number, userClaps: number) => void;
  className?: string;
}

interface ClapStatus {
  user_clap_count: number;
  total_claps: number;
  can_clap: boolean;
  max_claps: number;
}

const ClapButton: React.FC<ClapButtonProps> = ({
  articleId,
  initialClapCount = 0,
  initialUserClaps = 0,
  onClapUpdate,
  className = ''
}) => {
  const { user } = useAuth();
  const [totalClaps, setTotalClaps] = useState(initialClapCount);
  const [userClaps, setUserClaps] = useState(initialUserClaps);
  const [canClap, setCanClap] = useState(true);
  const [isLoading, setIsLoading] = useState(false);
  const [isClicked, setIsClicked] = useState(false);
  const [showBubble, setShowBubble] = useState(false);
  const [displayCount, setDisplayCount] = useState(initialUserClaps);
  const [pendingClaps, setPendingClaps] = useState(0);

  // Refs for managing timeouts
  const clickTimeoutRef = React.useRef<NodeJS.Timeout | null>(null);
  const bubbleTimeoutRef = React.useRef<NodeJS.Timeout | null>(null);
  const requestTimeoutRef = React.useRef<NodeJS.Timeout | null>(null);

  // Cleanup timeouts on unmount
  useEffect(() => {
    return () => {
      if (clickTimeoutRef.current) clearTimeout(clickTimeoutRef.current);
      if (bubbleTimeoutRef.current) clearTimeout(bubbleTimeoutRef.current);
      if (requestTimeoutRef.current) clearTimeout(requestTimeoutRef.current);
    };
  }, []);

  // Fetch clap status when component mounts or user changes
  useEffect(() => {
    if (user && articleId) {
      fetchClapStatus();
    }
  }, [user, articleId]);

  const fetchClapStatus = async () => {
    try {
      const response = await apiService.claps.getStatus(articleId);
      if (response.success) {
        const status = response.data as ClapStatus;
        setTotalClaps(status.total_claps);
        setUserClaps(status.user_clap_count);
        setDisplayCount(status.user_clap_count);
        setCanClap(status.can_clap);
      }
    } catch (error) {
      console.error('Error fetching clap status:', error);
    }
  };

  const handleClap = async () => {
    if (!user) {
      // Redirect to login or show login modal
      alert('Please log in to clap for articles');
      return;
    }

    if (!canClap || isLoading || userClaps + pendingClaps >= 50) {
      return;
    }

    // Clear existing visual timeouts but keep request timeout
    if (clickTimeoutRef.current) clearTimeout(clickTimeoutRef.current);
    if (bubbleTimeoutRef.current) clearTimeout(bubbleTimeoutRef.current);

    // Increment pending claps first
    const newPendingClaps = pendingClaps + 1;
    setPendingClaps(newPendingClaps);

    // Calculate the new count that will be shown in bubble
    const newDisplayCount = userClaps + newPendingClaps;

    // Immediate visual feedback
    setIsClicked(true);
    setShowBubble(true);
    setDisplayCount(newDisplayCount);

    // Reset clicked animation
    clickTimeoutRef.current = setTimeout(() => {
      setIsClicked(false);
    }, 200);

    // Hide bubble after delay
    bubbleTimeoutRef.current = setTimeout(() => {
      setShowBubble(false);
    }, 1200);

    // Clear existing request timeout and create new one
    if (requestTimeoutRef.current) clearTimeout(requestTimeoutRef.current);

    // Debounce API calls to prevent spam
    requestTimeoutRef.current = setTimeout(async () => {
      const clapsToAdd = pendingClaps;
      if (clapsToAdd === 0) return;

      setIsLoading(true);

      try {
        const response = await apiService.claps.add(articleId, clapsToAdd);

        if (response.success) {
          const data = response.data as ClapStatus;
          const newTotalClaps = data.total_claps;
          const newUserClaps = data.user_clap_count;

          setTotalClaps(newTotalClaps);
          setUserClaps(newUserClaps);
          setDisplayCount(newUserClaps);
          setCanClap(newUserClaps < 50);
          setPendingClaps(0);

          // Call callback if provided
          if (onClapUpdate) {
            onClapUpdate(newTotalClaps, newUserClaps);
          }
        }
      } catch (error: any) {
        console.error('Error adding clap:', error);

        // Revert optimistic update on error
        setDisplayCount(userClaps);
        setPendingClaps(0);

        if (error.error?.message) {
          alert(error.error.message);
        }
      } finally {
        setIsLoading(false);
      }
    }, 300); // Debounce API calls by 300ms
  };

  const handleRemoveClap = async () => {
    if (!user || userClaps === 0 || isLoading) {
      return;
    }

    // Clear any pending clap requests
    if (requestTimeoutRef.current) clearTimeout(requestTimeoutRef.current);
    setPendingClaps(0);

    setIsLoading(true);

    try {
      const response = await apiService.claps.remove(articleId);

      if (response.success) {
        const data = response.data as ClapStatus;
        const newTotalClaps = data.total_claps;

        setTotalClaps(newTotalClaps);
        setUserClaps(0);
        setDisplayCount(0);
        setCanClap(true);

        // Call callback if provided
        if (onClapUpdate) {
          onClapUpdate(newTotalClaps, 0);
        }
      }
    } catch (error: any) {
      console.error('Error removing clap:', error);
      if (error.error?.message) {
        alert(error.error.message);
      }
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className={`flex items-center space-x-2 ${className}`}>
      {/* Enhanced Clap Button */}
      <div className="relative">
        <button
          onClick={handleClap}
          disabled={!canClap || isLoading || userClaps >= 50}
          className={`
              relative flex items-center justify-center w-12 h-12 rounded-full
              transition-all duration-150 ease-out transform-gpu
              ${userClaps > 0 || displayCount > 0
              ? 'bg-green-500 text-white shadow-lg hover:bg-green-600'
              : 'bg-gray-100 hover:bg-gray-200 text-gray-600'
            }
              ${isClicked ? 'scale-110' : 'scale-100 hover:scale-105'}
              ${!canClap || userClaps >= 50 ? 'opacity-50 cursor-not-allowed' : ''}
              ${isLoading ? 'opacity-75' : ''}
              focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50
            `}
          style={{
            border: 'none',
            willChange: 'transform',
            backfaceVisibility: 'hidden'
          }}
          title={`${displayCount}/50 claps given`}
        >
          {/* Spark animations */}
          <div className={`spark-container left ${isClicked ? 'animate' : ''}`}>
            <div className="spark">✨</div>
          </div>
          <div className={`spark-container right ${isClicked ? 'animate' : ''}`}>
            <div className="spark">✨</div>
          </div>

          {/* Clap Icon (Clapping hands) */}
          <span
            className={`clap-hands text-xl transition-transform duration-150 ${isClicked ? 'clicked' : ''}`}
            style={{ willChange: 'transform' }}
          >
            👏
          </span>

          {/* Bubble with count */}
          {showBubble && displayCount > 0 && (
            <div
              key={`bubble-${displayCount}`}
              className={`clap-bubble active ${isClicked ? 'clicked' : ''}`}
            >
              <span>
                {displayCount}
              </span>
            </div>
          )}

          {/* Loading indicator */}
          {isLoading && (
            <div className="absolute -top-1 -right-1 w-3 h-3 bg-blue-500 rounded-full animate-pulse"></div>
          )}
        </button>
      </div>

      {/* Clap Count Display */}
      <div className="flex flex-col items-start">
        <span className="text-sm font-medium text-gray-900">
          {totalClaps.toLocaleString()}
        </span>
        {userClaps > 0 && (
          <button
            onClick={handleRemoveClap}
            className="text-xs text-gray-500 hover:text-red-500 transition-colors"
            disabled={isLoading}
          >
            You clapped {userClaps} time{userClaps !== 1 ? 's' : ''}
          </button>
        )}
      </div>

      {/* Progress indicator for user claps */}
      {user && userClaps > 0 && (
        <div className="flex flex-col items-center">
          <div className="w-16 h-1 bg-gray-200 rounded-full overflow-hidden">
            <div
              className="h-full bg-green-500 transition-all duration-300"
              style={{ width: `${(userClaps / 50) * 100}%` }}
            />
          </div>
          <span className="text-xs text-gray-500 mt-1">
            {userClaps}/50
          </span>
        </div>
      )}
    </div>
  );
};

export default ClapButton;