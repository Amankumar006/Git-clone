import { useEffect, useRef, useState } from 'react';
import { apiService } from '../utils/api';

interface AnalyticsData {
  articleId: string;
  startTime: number;
  scrollDepth: number;
  timeSpent: number;
  isRead: boolean;
}

interface UseArticleAnalyticsProps {
  articleId: string;
  readingTime: number; // in minutes
}

export const useArticleAnalytics = ({ articleId, readingTime }: UseArticleAnalyticsProps) => {
  const [analytics, setAnalytics] = useState<AnalyticsData>({
    articleId,
    startTime: Date.now(),
    scrollDepth: 0,
    timeSpent: 0,
    isRead: false
  });

  const intervalRef = useRef<NodeJS.Timeout | null>(null);
  const hasTrackedView = useRef(false);
  const hasTrackedRead = useRef(false);

  useEffect(() => {
    // Track initial view
    if (!hasTrackedView.current) {
      trackView();
      hasTrackedView.current = true;
    }

    // Start time tracking
    intervalRef.current = setInterval(() => {
      setAnalytics(prev => {
        const newTimeSpent = Date.now() - prev.startTime;
        const timeSpentMinutes = newTimeSpent / (1000 * 60);
        
        // Consider article "read" if user spent at least 50% of estimated reading time
        // or has been on page for at least 2 minutes
        const isRead = timeSpentMinutes >= (readingTime * 0.5) || timeSpentMinutes >= 2;
        
        if (isRead && !hasTrackedRead.current) {
          trackRead();
          hasTrackedRead.current = true;
        }

        return {
          ...prev,
          timeSpent: newTimeSpent,
          isRead
        };
      });
    }, 1000);

    // Track scroll depth
    const handleScroll = () => {
      const scrollTop = window.pageYOffset;
      const docHeight = document.documentElement.scrollHeight - window.innerHeight;
      const scrollPercent = Math.min(100, Math.max(0, (scrollTop / docHeight) * 100));
      
      setAnalytics(prev => ({
        ...prev,
        scrollDepth: Math.max(prev.scrollDepth, scrollPercent)
      }));
    };

    // Track page visibility changes
    const handleVisibilityChange = () => {
      if (document.hidden) {
        // Page is hidden, send current analytics
        sendAnalytics();
      } else {
        // Page is visible again, reset start time
        setAnalytics(prev => ({
          ...prev,
          startTime: Date.now()
        }));
      }
    };

    // Track page unload
    const handleBeforeUnload = () => {
      sendAnalytics();
    };

    window.addEventListener('scroll', handleScroll, { passive: true });
    document.addEventListener('visibilitychange', handleVisibilityChange);
    window.addEventListener('beforeunload', handleBeforeUnload);

    return () => {
      if (intervalRef.current) {
        clearInterval(intervalRef.current);
      }
      window.removeEventListener('scroll', handleScroll);
      document.removeEventListener('visibilitychange', handleVisibilityChange);
      window.removeEventListener('beforeunload', handleBeforeUnload);
      
      // Send final analytics
      sendAnalytics();
    };
  }, [articleId, readingTime]);

  const trackView = async () => {
    try {
      await apiService.post('/articles/track-view', {
        article_id: articleId,
        timestamp: new Date().toISOString(),
        user_agent: navigator.userAgent,
        referrer: document.referrer
      });
    } catch (error) {
      console.error('Failed to track view:', error);
    }
  };

  const trackRead = async () => {
    try {
      await apiService.post('/articles/track-read', {
        article_id: articleId,
        timestamp: new Date().toISOString(),
        time_spent: analytics.timeSpent,
        scroll_depth: analytics.scrollDepth
      });
    } catch (error) {
      console.error('Failed to track read:', error);
    }
  };

  const sendAnalytics = async () => {
    if (analytics.timeSpent < 5000) return; // Don't send if less than 5 seconds

    try {
      await apiService.post('/articles/analytics', {
        article_id: articleId,
        time_spent: analytics.timeSpent,
        scroll_depth: analytics.scrollDepth,
        is_read: analytics.isRead,
        timestamp: new Date().toISOString()
      });
    } catch (error) {
      console.error('Failed to send analytics:', error);
    }
  };

  return {
    analytics,
    trackView,
    trackRead,
    sendAnalytics
  };
};