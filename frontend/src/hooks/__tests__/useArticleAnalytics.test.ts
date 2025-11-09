import { renderHook, act } from '@testing-library/react';
import { jest } from '@jest/globals';
import { useArticleAnalytics } from '../useArticleAnalytics';
import { apiService } from '../../utils/api';

// Mock the API service
jest.mock('../../utils/api');
const mockApiService = apiService as jest.Mocked<typeof apiService>;

// Mock timers
jest.useFakeTimers();

// Mock navigator.clipboard
Object.assign(navigator, {
  userAgent: 'Test User Agent'
});

// Mock document properties
Object.defineProperty(document, 'referrer', {
  value: 'https://example.com',
  writable: true
});

Object.defineProperty(document, 'hidden', {
  value: false,
  writable: true
});

// Mock window properties
Object.defineProperty(window, 'pageYOffset', {
  value: 0,
  writable: true
});

Object.defineProperty(document.documentElement, 'scrollHeight', {
  value: 2000,
  writable: true
});

Object.defineProperty(window, 'innerHeight', {
  value: 800,
  writable: true
});

describe('useArticleAnalytics', () => {
  const defaultProps = {
    articleId: '123',
    readingTime: 5
  };

  beforeEach(() => {
    jest.clearAllMocks();
    jest.clearAllTimers();
    
    // Reset API mocks
    mockApiService.post.mockResolvedValue({
      success: true,
      data: { tracked: true },
      message: 'Success'
    });
  });

  afterEach(() => {
    jest.runOnlyPendingTimers();
    jest.useRealTimers();
  });

  describe('Initialization', () => {
    it('should initialize with correct default values', () => {
      const { result } = renderHook(() => useArticleAnalytics(defaultProps));

      expect(result.current.analytics.articleId).toBe('123');
      expect(result.current.analytics.scrollDepth).toBe(0);
      expect(result.current.analytics.timeSpent).toBe(0);
      expect(result.current.analytics.isRead).toBe(false);
      expect(result.current.analytics.startTime).toBeCloseTo(Date.now(), -2);
    });

    it('should track initial view on mount', () => {
      renderHook(() => useArticleAnalytics(defaultProps));

      expect(mockApiService.post).toHaveBeenCalledWith('/articles/track-view', {
        article_id: '123',
        timestamp: expect.any(String),
        user_agent: 'Test User Agent',
        referrer: 'https://example.com'
      });
    });
  });

  describe('Time Tracking', () => {
    it('should update time spent every second', () => {
      const { result } = renderHook(() => useArticleAnalytics(defaultProps));

      // Fast-forward 3 seconds
      act(() => {
        jest.advanceTimersByTime(3000);
      });

      expect(result.current.analytics.timeSpent).toBeGreaterThanOrEqual(2900);
      expect(result.current.analytics.timeSpent).toBeLessThanOrEqual(3100);
    });

    it('should mark article as read after sufficient time', () => {
      const { result } = renderHook(() => useArticleAnalytics({
        articleId: '123',
        readingTime: 2 // 2 minutes
      }));

      // Fast-forward 1 minute (50% of reading time)
      act(() => {
        jest.advanceTimersByTime(60000);
      });

      expect(result.current.analytics.isRead).toBe(true);
      expect(mockApiService.post).toHaveBeenCalledWith('/articles/track-read', {
        article_id: '123',
        timestamp: expect.any(String),
        time_spent: expect.any(Number),
        scroll_depth: 0
      });
    });

    it('should mark article as read after 2 minutes regardless of reading time', () => {
      const { result } = renderHook(() => useArticleAnalytics({
        articleId: '123',
        readingTime: 10 // 10 minutes
      }));

      // Fast-forward 2 minutes
      act(() => {
        jest.advanceTimersByTime(120000);
      });

      expect(result.current.analytics.isRead).toBe(true);
    });
  });

  describe('Scroll Tracking', () => {
    it('should update scroll depth on scroll events', () => {
      const { result } = renderHook(() => useArticleAnalytics(defaultProps));

      // Simulate scroll to 50%
      Object.defineProperty(window, 'pageYOffset', { value: 600, writable: true });

      act(() => {
        window.dispatchEvent(new Event('scroll'));
      });

      expect(result.current.analytics.scrollDepth).toBeCloseTo(50, 0);
    });

    it('should track maximum scroll depth', () => {
      const { result } = renderHook(() => useArticleAnalytics(defaultProps));

      // Scroll to 75%
      Object.defineProperty(window, 'pageYOffset', { value: 900, writable: true });
      act(() => {
        window.dispatchEvent(new Event('scroll'));
      });

      expect(result.current.analytics.scrollDepth).toBeCloseTo(75, 0);

      // Scroll back to 25%
      Object.defineProperty(window, 'pageYOffset', { value: 300, writable: true });
      act(() => {
        window.dispatchEvent(new Event('scroll'));
      });

      // Should maintain maximum scroll depth
      expect(result.current.analytics.scrollDepth).toBeCloseTo(75, 0);
    });

    it('should handle scroll depth bounds correctly', () => {
      const { result } = renderHook(() => useArticleAnalytics(defaultProps));

      // Scroll beyond 100%
      Object.defineProperty(window, 'pageYOffset', { value: 2000, writable: true });
      act(() => {
        window.dispatchEvent(new Event('scroll'));
      });

      expect(result.current.analytics.scrollDepth).toBe(100);

      // Scroll to negative position
      Object.defineProperty(window, 'pageYOffset', { value: -100, writable: true });
      act(() => {
        window.dispatchEvent(new Event('scroll'));
      });

      // Should maintain 100% (max value)
      expect(result.current.analytics.scrollDepth).toBe(100);
    });
  });

  describe('Visibility Change Tracking', () => {
    it('should send analytics when page becomes hidden', () => {
      renderHook(() => useArticleAnalytics(defaultProps));

      // Advance time to have some data
      act(() => {
        jest.advanceTimersByTime(10000);
      });

      // Simulate page becoming hidden
      Object.defineProperty(document, 'hidden', { value: true, writable: true });
      act(() => {
        document.dispatchEvent(new Event('visibilitychange'));
      });

      expect(mockApiService.post).toHaveBeenCalledWith('/articles/analytics', {
        article_id: '123',
        time_spent: expect.any(Number),
        scroll_depth: 0,
        is_read: false,
        timestamp: expect.any(String)
      });
    });

    it('should reset start time when page becomes visible again', () => {
      const { result } = renderHook(() => useArticleAnalytics(defaultProps));

      const initialStartTime = result.current.analytics.startTime;

      // Advance time
      act(() => {
        jest.advanceTimersByTime(5000);
      });

      // Simulate page becoming visible again
      Object.defineProperty(document, 'hidden', { value: false, writable: true });
      act(() => {
        document.dispatchEvent(new Event('visibilitychange'));
      });

      expect(result.current.analytics.startTime).toBeGreaterThan(initialStartTime);
    });
  });

  describe('Manual Tracking Methods', () => {
    it('should provide trackView method', async () => {
      const { result } = renderHook(() => useArticleAnalytics(defaultProps));

      await act(async () => {
        await result.current.trackView();
      });

      expect(mockApiService.post).toHaveBeenCalledWith('/articles/track-view', {
        article_id: '123',
        timestamp: expect.any(String),
        user_agent: 'Test User Agent',
        referrer: 'https://example.com'
      });
    });

    it('should provide trackRead method', async () => {
      const { result } = renderHook(() => useArticleAnalytics(defaultProps));

      await act(async () => {
        await result.current.trackRead();
      });

      expect(mockApiService.post).toHaveBeenCalledWith('/articles/track-read', {
        article_id: '123',
        timestamp: expect.any(String),
        time_spent: expect.any(Number),
        scroll_depth: 0
      });
    });

    it('should provide sendAnalytics method', async () => {
      const { result } = renderHook(() => useArticleAnalytics(defaultProps));

      // Advance time to meet minimum threshold
      act(() => {
        jest.advanceTimersByTime(6000);
      });

      await act(async () => {
        await result.current.sendAnalytics();
      });

      expect(mockApiService.post).toHaveBeenCalledWith('/articles/analytics', {
        article_id: '123',
        time_spent: expect.any(Number),
        scroll_depth: 0,
        is_read: false,
        timestamp: expect.any(String)
      });
    });

    it('should not send analytics if time spent is less than 5 seconds', async () => {
      const { result } = renderHook(() => useArticleAnalytics(defaultProps));

      // Don't advance time much
      act(() => {
        jest.advanceTimersByTime(3000);
      });

      await act(async () => {
        await result.current.sendAnalytics();
      });

      // Should not call analytics API
      const analyticsCalls = mockApiService.post.mock.calls.filter(
        call => call[0] === '/articles/analytics'
      );
      expect(analyticsCalls).toHaveLength(0);
    });
  });

  describe('Error Handling', () => {
    it('should handle API errors gracefully', async () => {
      mockApiService.post.mockRejectedValue(new Error('Network error'));
      
      const consoleSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

      const { result } = renderHook(() => useArticleAnalytics(defaultProps));

      await act(async () => {
        await result.current.trackView();
      });

      expect(consoleSpy).toHaveBeenCalledWith('Failed to track view:', expect.any(Error));
      
      consoleSpy.mockRestore();
    });
  });

  describe('Cleanup', () => {
    it('should clean up event listeners and intervals on unmount', () => {
      const removeEventListenerSpy = jest.spyOn(window, 'removeEventListener');
      const clearIntervalSpy = jest.spyOn(global, 'clearInterval');

      const { unmount } = renderHook(() => useArticleAnalytics(defaultProps));

      unmount();

      expect(removeEventListenerSpy).toHaveBeenCalledWith('scroll', expect.any(Function));
      expect(removeEventListenerSpy).toHaveBeenCalledWith('beforeunload', expect.any(Function));
      expect(clearIntervalSpy).toHaveBeenCalled();

      removeEventListenerSpy.mockRestore();
      clearIntervalSpy.mockRestore();
    });

    it('should send final analytics on unmount', () => {
      const { unmount } = renderHook(() => useArticleAnalytics(defaultProps));

      // Advance time to meet minimum threshold
      act(() => {
        jest.advanceTimersByTime(6000);
      });

      unmount();

      expect(mockApiService.post).toHaveBeenCalledWith('/articles/analytics', {
        article_id: '123',
        time_spent: expect.any(Number),
        scroll_depth: 0,
        is_read: false,
        timestamp: expect.any(String)
      });
    });
  });
});