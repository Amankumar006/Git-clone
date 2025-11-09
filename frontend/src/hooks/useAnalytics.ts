import { useEffect, useCallback, useRef } from 'react';
import { useLocation } from 'react-router-dom';
import { 
  trackPageView, 
  trackEvent, 
  trackTimeOnPage, 
  trackScrollDepth,
  trackPerformance,
  AnalyticsEvent 
} from '../utils/analytics';
import { debounce, throttle } from '../utils/performance';

interface UseAnalyticsOptions {
  trackPageViews?: boolean;
  trackScrollDepth?: boolean;
  trackTimeOnPage?: boolean;
  trackPerformance?: boolean;
  scrollDepthThresholds?: number[];
}

export const useAnalytics = (options: UseAnalyticsOptions = {}) => {
  const {
    trackPageViews = true,
    trackScrollDepth = true,
    trackTimeOnPage = true,
    trackPerformance = true,
    scrollDepthThresholds = [25, 50, 75, 90, 100]
  } = options;

  const location = useLocation();
  const scrollDepthRef = useRef<Set<number>>(new Set());
  const pageStartTime = useRef<number>(Date.now());

  // Track page views
  useEffect(() => {
    if (trackPageViews) {
      pageStartTime.current = Date.now();
      scrollDepthRef.current.clear();
      trackPageView(location.pathname, document.title);
    }
  }, [location.pathname, trackPageViews]);

  // Track time on page when leaving
  useEffect(() => {
    const handleBeforeUnload = () => {
      if (trackTimeOnPage) {
        // Track time spent on page
        const timeSpent = Date.now() - pageStartTime.current;
        if (timeSpent > 1000) { // Only track if more than 1 second
          trackEvent({
            action: 'time_on_page',
            category: 'engagement',
            value: Math.round(timeSpent / 1000)
          });
        }
      }
    };

    window.addEventListener('beforeunload', handleBeforeUnload);
    return () => window.removeEventListener('beforeunload', handleBeforeUnload);
  }, [trackTimeOnPage]);

  // Track scroll depth
  useEffect(() => {
    if (!trackScrollDepth) return;

    const handleScroll = throttle(() => {
      const scrollTop = window.pageYOffset;
      const documentHeight = document.documentElement.scrollHeight - window.innerHeight;
      const scrollPercentage = Math.round((scrollTop / documentHeight) * 100);

      scrollDepthThresholds.forEach(threshold => {
        if (scrollPercentage >= threshold && !scrollDepthRef.current.has(threshold)) {
          scrollDepthRef.current.add(threshold);
          trackEvent({
            action: 'scroll_depth',
            category: 'engagement',
            label: `${threshold}%`,
            value: threshold
          });
        }
      });
    }, 100);

    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, [trackScrollDepth, scrollDepthThresholds]);

  // Track performance metrics
  useEffect(() => {
    if (!trackPerformance) return;

    const measurePerformance = () => {
      if ('performance' in window) {
        // Track page load time
        const navigation = performance.getEntriesByType('navigation')[0] as PerformanceNavigationTiming;
        if (navigation) {
          trackEvent({
            action: 'page_load_time',
            category: 'performance',
            value: Math.round(navigation.loadEventEnd - navigation.fetchStart)
          });
          trackEvent({
            action: 'dom_content_loaded',
            category: 'performance',
            value: Math.round(navigation.domContentLoadedEventEnd - navigation.fetchStart)
          });
          trackEvent({
            action: 'first_byte',
            category: 'performance',
            value: Math.round(navigation.responseStart - navigation.fetchStart)
          });
        }

        // Track resource loading times
        const resources = performance.getEntriesByType('resource') as PerformanceResourceTiming[];
        resources.forEach(resource => {
          if (resource.name.includes('.js') || resource.name.includes('.css')) {
            trackEvent({
              action: 'resource_load_time',
              category: 'performance',
              value: Math.round(resource.responseEnd - resource.fetchStart),
              custom_parameters: {
                resource_name: resource.name.split('/').pop(),
                resource_type: resource.name.includes('.js') ? 'javascript' : 'css'
              }
            });
          }
        });
      }
    };

    // Measure performance after page load
    if (document.readyState === 'complete') {
      measurePerformance();
    } else {
      window.addEventListener('load', measurePerformance);
    }

    return () => window.removeEventListener('load', measurePerformance);
  }, [trackPerformance]);

  // Return tracking functions
  const track = useCallback((event: AnalyticsEvent) => {
    trackEvent(event);
  }, []);

  const trackClick = useCallback((element: string, additionalData?: Record<string, any>) => {
    track({
      action: 'click',
      category: 'interaction',
      label: element,
      custom_parameters: additionalData
    });
  }, [track]);

  const trackFormSubmit = useCallback((formName: string, success: boolean, additionalData?: Record<string, any>) => {
    track({
      action: success ? 'form_submit_success' : 'form_submit_error',
      category: 'form',
      label: formName,
      custom_parameters: additionalData
    });
  }, [track]);

  const trackSearch = useCallback((query: string, resultsCount: number, filters?: Record<string, any>) => {
    track({
      action: 'search',
      category: 'search',
      label: query,
      value: resultsCount,
      custom_parameters: { filters }
    });
  }, [track]);

  const trackVideoPlay = useCallback((videoId: string, duration?: number) => {
    track({
      action: 'video_play',
      category: 'media',
      label: videoId,
      value: duration,
      custom_parameters: { video_id: videoId }
    });
  }, [track]);

  const trackDownload = useCallback((fileName: string, fileType: string, fileSize?: number) => {
    track({
      action: 'download',
      category: 'file',
      label: fileName,
      value: fileSize,
      custom_parameters: { file_type: fileType }
    });
  }, [track]);

  const trackError = useCallback((error: Error, context: string, severity: 'low' | 'medium' | 'high' | 'critical' = 'medium') => {
    track({
      action: 'error',
      category: 'error',
      label: error.message,
      custom_parameters: {
        error_stack: error.stack,
        context,
        severity,
        url: window.location.href,
        user_agent: navigator.userAgent
      }
    });
  }, [track]);

  return {
    track,
    trackClick,
    trackFormSubmit,
    trackSearch,
    trackVideoPlay,
    trackDownload,
    trackError
  };
};

// Hook for article-specific analytics
export const useArticleAnalytics = (articleId: string) => {
  const { track } = useAnalytics();
  const readingStartTime = useRef<number>(Date.now());
  const hasTrackedRead = useRef<boolean>(false);

  useEffect(() => {
    readingStartTime.current = Date.now();
    hasTrackedRead.current = false;

    // Track article view
    track({
      action: 'view',
      category: 'article',
      label: articleId,
      custom_parameters: { article_id: articleId }
    });
  }, [articleId, track]);

  const trackArticleRead = useCallback((readingTime?: number) => {
    if (hasTrackedRead.current) return;
    
    const actualReadingTime = readingTime || Math.round((Date.now() - readingStartTime.current) / 1000);
    
    track({
      action: 'read_complete',
      category: 'article',
      label: articleId,
      value: actualReadingTime,
      custom_parameters: { 
        article_id: articleId,
        reading_time: actualReadingTime
      }
    });
    
    hasTrackedRead.current = true;
  }, [articleId, track]);

  const trackClap = useCallback((clapCount: number) => {
    track({
      action: 'clap',
      category: 'article',
      label: articleId,
      value: clapCount,
      custom_parameters: { 
        article_id: articleId,
        clap_count: clapCount
      }
    });
  }, [articleId, track]);

  const trackComment = useCallback((commentLength?: number) => {
    track({
      action: 'comment',
      category: 'article',
      label: articleId,
      value: commentLength,
      custom_parameters: { 
        article_id: articleId,
        comment_length: commentLength
      }
    });
  }, [articleId, track]);

  const trackBookmark = useCallback(() => {
    track({
      action: 'bookmark',
      category: 'article',
      label: articleId,
      custom_parameters: { article_id: articleId }
    });
  }, [articleId, track]);

  const trackShare = useCallback((platform: string) => {
    track({
      action: 'share',
      category: 'article',
      label: articleId,
      custom_parameters: { 
        article_id: articleId,
        platform
      }
    });
  }, [articleId, track]);

  return {
    trackArticleRead,
    trackClap,
    trackComment,
    trackBookmark,
    trackShare
  };
};

// Hook for user engagement analytics
export const useEngagementAnalytics = () => {
  const { track } = useAnalytics();

  const trackFollow = useCallback((userId: string, username: string) => {
    track({
      action: 'follow',
      category: 'engagement',
      label: username,
      custom_parameters: { 
        target_user_id: userId,
        target_username: username
      }
    });
  }, [track]);

  const trackUnfollow = useCallback((userId: string, username: string) => {
    track({
      action: 'unfollow',
      category: 'engagement',
      label: username,
      custom_parameters: { 
        target_user_id: userId,
        target_username: username
      }
    });
  }, [track]);

  const trackProfileView = useCallback((userId: string, username: string) => {
    track({
      action: 'profile_view',
      category: 'engagement',
      label: username,
      custom_parameters: { 
        target_user_id: userId,
        target_username: username
      }
    });
  }, [track]);

  const trackSubscription = useCallback((planType: string, price?: number) => {
    track({
      action: 'subscription',
      category: 'conversion',
      label: planType,
      value: price,
      custom_parameters: { 
        plan_type: planType,
        price
      }
    });
  }, [track]);

  return {
    trackFollow,
    trackUnfollow,
    trackProfileView,
    trackSubscription
  };
};