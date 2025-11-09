/**
 * Analytics and monitoring integration
 */

// Google Analytics 4 integration
declare global {
  interface Window {
    gtag: (...args: any[]) => void;
    dataLayer: any[];
  }
}

export interface AnalyticsEvent {
  action: string;
  category: string;
  label?: string;
  value?: number;
  custom_parameters?: Record<string, any>;
}

interface UserProperties {
  user_id?: string;
  user_type?: 'reader' | 'writer' | 'admin';
  subscription_status?: 'free' | 'premium';
  registration_date?: string;
}

class AnalyticsService {
  private isInitialized = false;
  private userId: string | null = null;
  private sessionId: string;
  private pageViewStartTime: number = Date.now();

  constructor() {
    this.sessionId = this.generateSessionId();
    this.initializeAnalytics();
  }

  /**
   * Initialize Google Analytics
   */
  private initializeAnalytics() {
    if (typeof window === 'undefined') return;

    // Load Google Analytics script
    const GA_MEASUREMENT_ID = process.env.REACT_APP_GA_MEASUREMENT_ID;
    
    if (GA_MEASUREMENT_ID) {
      const script = document.createElement('script');
      script.async = true;
      script.src = `https://www.googletagmanager.com/gtag/js?id=${GA_MEASUREMENT_ID}`;
      document.head.appendChild(script);

      window.dataLayer = window.dataLayer || [];
      window.gtag = function() {
        window.dataLayer.push(arguments);
      };

      window.gtag('js', new Date());
      window.gtag('config', GA_MEASUREMENT_ID, {
        send_page_view: false, // We'll handle page views manually
        session_id: this.sessionId,
        custom_map: {
          custom_session_id: 'session_id'
        }
      });

      this.isInitialized = true;
    }

    // Initialize other analytics services
    this.initializeHotjar();
    this.initializeMixpanel();
  }

  /**
   * Initialize Hotjar for user behavior analytics
   */
  private initializeHotjar() {
    const HOTJAR_ID = process.env.REACT_APP_HOTJAR_ID;
    
    if (HOTJAR_ID) {
      (function(h: any, o: any, t: any, j: any, a?: any, r?: any) {
        h.hj = h.hj || function() { (h.hj.q = h.hj.q || []).push(arguments) };
        h._hjSettings = { hjid: HOTJAR_ID, hjsv: 6 };
        a = o.getElementsByTagName('head')[0];
        r = o.createElement('script'); r.async = 1;
        r.src = t + h._hjSettings.hjid + j + h._hjSettings.hjsv;
        a.appendChild(r);
      })(window, document, 'https://static.hotjar.com/c/hotjar-', '.js?sv=');
    }
  }

  /**
   * Initialize Mixpanel for event tracking
   */
  private initializeMixpanel() {
    const MIXPANEL_TOKEN = process.env.REACT_APP_MIXPANEL_TOKEN;
    
    if (MIXPANEL_TOKEN) {
      // Mixpanel initialization would go here
      // For now, we'll use a simple implementation
      console.log('Mixpanel initialized with token:', MIXPANEL_TOKEN);
    }
  }

  /**
   * Set user properties
   */
  setUser(userId: string, properties: UserProperties = {}) {
    this.userId = userId;

    if (this.isInitialized && window.gtag) {
      window.gtag('config', process.env.REACT_APP_GA_MEASUREMENT_ID, {
        user_id: userId,
        custom_map: {
          user_type: 'user_type',
          subscription_status: 'subscription_status'
        }
      });

      // Set user properties
      window.gtag('set', {
        user_id: userId,
        ...properties
      });
    }

    // Set Hotjar user attributes
    if ((window as any).hj) {
      (window as any).hj('identify', userId, properties);
    }
  }

  /**
   * Track page view
   */
  trackPageView(path: string, title?: string) {
    this.pageViewStartTime = Date.now();

    if (this.isInitialized && window.gtag) {
      window.gtag('event', 'page_view', {
        page_title: title || document.title,
        page_location: window.location.href,
        page_path: path,
        session_id: this.sessionId,
        user_id: this.userId
      });
    }

    // Track with custom analytics
    this.trackCustomEvent({
      action: 'page_view',
      category: 'navigation',
      custom_parameters: {
        path,
        title: title || document.title,
        referrer: document.referrer,
        user_agent: navigator.userAgent,
        screen_resolution: `${screen.width}x${screen.height}`,
        viewport_size: `${window.innerWidth}x${window.innerHeight}`
      }
    });
  }

  /**
   * Track custom events
   */
  trackEvent(event: AnalyticsEvent) {
    if (this.isInitialized && window.gtag) {
      window.gtag('event', event.action, {
        event_category: event.category,
        event_label: event.label,
        value: event.value,
        session_id: this.sessionId,
        user_id: this.userId,
        ...event.custom_parameters
      });
    }

    this.trackCustomEvent(event);
  }

  /**
   * Track article interactions
   */
  trackArticleInteraction(action: string, articleId: string, additionalData: Record<string, any> = {}) {
    this.trackEvent({
      action,
      category: 'article',
      label: articleId,
      custom_parameters: {
        article_id: articleId,
        timestamp: Date.now(),
        session_id: this.sessionId,
        ...additionalData
      }
    });
  }

  /**
   * Track user engagement
   */
  trackEngagement(action: string, target: string, value?: number) {
    this.trackEvent({
      action,
      category: 'engagement',
      label: target,
      value,
      custom_parameters: {
        target,
        timestamp: Date.now(),
        session_id: this.sessionId
      }
    });
  }

  /**
   * Track performance metrics
   */
  trackPerformance(metric: string, value: number, additionalData: Record<string, any> = {}) {
    this.trackEvent({
      action: 'performance_metric',
      category: 'performance',
      label: metric,
      value,
      custom_parameters: {
        metric,
        value,
        timestamp: Date.now(),
        ...additionalData
      }
    });
  }

  /**
   * Track errors
   */
  trackError(error: Error, context: string = 'unknown') {
    this.trackEvent({
      action: 'error',
      category: 'error',
      label: error.message,
      custom_parameters: {
        error_message: error.message,
        error_stack: error.stack,
        context,
        timestamp: Date.now(),
        user_agent: navigator.userAgent,
        url: window.location.href
      }
    });

    // Send to error tracking service
    console.error('Analytics Error:', error, { context });
  }

  /**
   * Track conversion events
   */
  trackConversion(action: string, value?: number, currency: string = 'USD') {
    if (this.isInitialized && window.gtag) {
      window.gtag('event', 'conversion', {
        send_to: process.env.REACT_APP_GA_MEASUREMENT_ID,
        event_category: 'conversion',
        event_label: action,
        value,
        currency
      });
    }

    this.trackEvent({
      action: 'conversion',
      category: 'conversion',
      label: action,
      value,
      custom_parameters: {
        currency,
        timestamp: Date.now()
      }
    });
  }

  /**
   * Track time on page
   */
  trackTimeOnPage() {
    const timeSpent = Date.now() - this.pageViewStartTime;
    
    this.trackEvent({
      action: 'time_on_page',
      category: 'engagement',
      value: Math.round(timeSpent / 1000), // Convert to seconds
      custom_parameters: {
        time_spent_ms: timeSpent,
        page_path: window.location.pathname
      }
    });
  }

  /**
   * Track scroll depth
   */
  trackScrollDepth(percentage: number) {
    this.trackEvent({
      action: 'scroll_depth',
      category: 'engagement',
      value: percentage,
      custom_parameters: {
        scroll_percentage: percentage,
        page_path: window.location.pathname
      }
    });
  }

  /**
   * Track search queries
   */
  trackSearch(query: string, resultsCount: number) {
    if (this.isInitialized && window.gtag) {
      window.gtag('event', 'search', {
        search_term: query,
        results_count: resultsCount
      });
    }

    this.trackEvent({
      action: 'search',
      category: 'search',
      label: query,
      value: resultsCount,
      custom_parameters: {
        search_query: query,
        results_count: resultsCount
      }
    });
  }

  /**
   * Custom event tracking for internal analytics
   */
  private trackCustomEvent(event: AnalyticsEvent) {
    // Send to custom analytics endpoint
    const analyticsData = {
      ...event,
      timestamp: Date.now(),
      session_id: this.sessionId,
      user_id: this.userId,
      url: window.location.href,
      referrer: document.referrer,
      user_agent: navigator.userAgent
    };

    // Send to backend analytics service
    fetch('/api/analytics/track', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(analyticsData)
    }).catch(error => {
      console.error('Failed to send analytics data:', error);
    });
  }

  /**
   * Generate unique session ID
   */
  private generateSessionId(): string {
    return Date.now().toString(36) + Math.random().toString(36).substr(2);
  }
}

// Create singleton instance
const analytics = new AnalyticsService();

// Export convenience functions
export const trackPageView = (path: string, title?: string) => analytics.trackPageView(path, title);
export const trackEvent = (event: AnalyticsEvent) => analytics.trackEvent(event);
export const trackArticleView = (articleId: string) => analytics.trackArticleInteraction('view', articleId);
export const trackArticleRead = (articleId: string, readingTime: number) => 
  analytics.trackArticleInteraction('read_complete', articleId, { reading_time: readingTime });
export const trackClap = (articleId: string, clapCount: number) => 
  analytics.trackArticleInteraction('clap', articleId, { clap_count: clapCount });
export const trackComment = (articleId: string) => analytics.trackArticleInteraction('comment', articleId);
export const trackBookmark = (articleId: string) => analytics.trackArticleInteraction('bookmark', articleId);
export const trackFollow = (userId: string) => analytics.trackEngagement('follow', userId);
export const trackShare = (articleId: string, platform: string) => 
  analytics.trackArticleInteraction('share', articleId, { platform });
export const trackSearch = (query: string, resultsCount: number) => analytics.trackSearch(query, resultsCount);
export const trackError = (error: Error, context?: string) => analytics.trackError(error, context);
export const trackPerformance = (metric: string, value: number, additionalData?: Record<string, any>) => 
  analytics.trackPerformance(metric, value, additionalData);
export const setUser = (userId: string, properties?: UserProperties) => analytics.setUser(userId, properties);
export const trackTimeOnPage = () => analytics.trackTimeOnPage();
export const trackScrollDepth = (percentage: number) => analytics.trackScrollDepth(percentage);

export default analytics;