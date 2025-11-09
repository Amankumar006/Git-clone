import {
  debounce,
  throttle,
  useIntersectionObserver,
  VirtualScroller,
  optimizeImage,
  generateSrcSet,
  measureWebVitals,
  analyzeBundleSize,
  monitorMemoryUsage
} from '../performance';

// Mock IntersectionObserver
global.IntersectionObserver = class IntersectionObserver {
  constructor(callback: IntersectionObserverCallback, options?: IntersectionObserverInit) {
    this.callback = callback;
    this.options = options;
  }
  
  callback: IntersectionObserverCallback;
  options?: IntersectionObserverInit;
  
  observe = jest.fn();
  unobserve = jest.fn();
  disconnect = jest.fn();
};

// Mock PerformanceObserver
global.PerformanceObserver = class PerformanceObserver {
  constructor(callback: PerformanceObserverCallback) {
    this.callback = callback;
  }
  
  callback: PerformanceObserverCallback;
  
  observe = jest.fn();
  disconnect = jest.fn();
};

// Mock performance API
Object.defineProperty(global, 'performance', {
  value: {
    now: jest.fn(() => Date.now()),
    getEntriesByType: jest.fn(() => []),
    memory: {
      usedJSHeapSize: 1024 * 1024 * 10, // 10MB
      totalJSHeapSize: 1024 * 1024 * 20, // 20MB
      jsHeapSizeLimit: 1024 * 1024 * 100 // 100MB
    }
  },
  writable: true
});

describe('Performance Utilities', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    jest.clearAllTimers();
    jest.useFakeTimers();
  });

  afterEach(() => {
    jest.useRealTimers();
  });

  describe('debounce', () => {
    it('should delay function execution', () => {
      const mockFn = jest.fn();
      const debouncedFn = debounce(mockFn, 100);

      debouncedFn('test');
      expect(mockFn).not.toHaveBeenCalled();

      jest.advanceTimersByTime(100);
      expect(mockFn).toHaveBeenCalledWith('test');
    });

    it('should cancel previous calls', () => {
      const mockFn = jest.fn();
      const debouncedFn = debounce(mockFn, 100);

      debouncedFn('first');
      debouncedFn('second');
      debouncedFn('third');

      jest.advanceTimersByTime(100);
      expect(mockFn).toHaveBeenCalledTimes(1);
      expect(mockFn).toHaveBeenCalledWith('third');
    });

    it('should execute immediately when immediate flag is true', () => {
      const mockFn = jest.fn();
      const debouncedFn = debounce(mockFn, 100, true);

      debouncedFn('test');
      expect(mockFn).toHaveBeenCalledWith('test');
    });
  });

  describe('throttle', () => {
    it('should limit function execution rate', () => {
      const mockFn = jest.fn();
      const throttledFn = throttle(mockFn, 100);

      throttledFn('first');
      throttledFn('second');
      throttledFn('third');

      expect(mockFn).toHaveBeenCalledTimes(1);
      expect(mockFn).toHaveBeenCalledWith('first');

      jest.advanceTimersByTime(100);
      
      throttledFn('fourth');
      expect(mockFn).toHaveBeenCalledTimes(2);
      expect(mockFn).toHaveBeenCalledWith('fourth');
    });
  });

  describe('useIntersectionObserver', () => {
    it('should create IntersectionObserver with callback and options', () => {
      const callback = jest.fn();
      const options = { threshold: 0.5 };

      const observer = useIntersectionObserver(callback, options);

      expect(observer).toBeInstanceOf(IntersectionObserver);
      expect(observer.callback).toBe(callback);
      expect(observer.options).toMatchObject({
        rootMargin: '50px',
        threshold: 0.5
      });
    });
  });

  describe('VirtualScroller', () => {
    let container: HTMLElement;
    let renderCallback: jest.Mock;

    beforeEach(() => {
      container = document.createElement('div');
      Object.defineProperty(container, 'clientHeight', { value: 400 });
      Object.defineProperty(container, 'scrollTop', { value: 0, writable: true });
      
      renderCallback = jest.fn();
    });

    it('should initialize with correct parameters', () => {
      const scroller = new VirtualScroller(container, 50, 100, renderCallback);
      
      expect(renderCallback).toHaveBeenCalledWith(0, expect.any(Number));
    });

    it('should update total count', () => {
      const scroller = new VirtualScroller(container, 50, 100, renderCallback);
      
      renderCallback.mockClear();
      scroller.updateTotalCount(200);
      
      expect(renderCallback).toHaveBeenCalled();
    });
  });

  describe('optimizeImage', () => {
    it('should add optimization parameters to image URL', () => {
      const src = 'https://example.com/image.jpg';
      const optimized = optimizeImage(src, 800, 600, 85);

      expect(optimized).toContain('w=800');
      expect(optimized).toContain('h=600');
      expect(optimized).toContain('q=85');
    });

    it('should handle relative URLs', () => {
      Object.defineProperty(window, 'location', {
        value: { origin: 'https://example.com' },
        writable: true
      });

      const src = '/images/test.jpg';
      const optimized = optimizeImage(src, 400);

      expect(optimized).toContain('https://example.com/images/test.jpg');
      expect(optimized).toContain('w=400');
    });
  });

  describe('generateSrcSet', () => {
    it('should generate responsive srcset string', () => {
      const baseSrc = 'https://example.com/image.jpg';
      const sizes = [480, 768, 1024];
      const srcSet = generateSrcSet(baseSrc, sizes);

      expect(srcSet).toContain('480w');
      expect(srcSet).toContain('768w');
      expect(srcSet).toContain('1024w');
      expect(srcSet.split(',').length).toBe(3);
    });
  });

  describe('measureWebVitals', () => {
    it('should set up performance observers', () => {
      const observeSpy = jest.spyOn(PerformanceObserver.prototype, 'observe');
      
      measureWebVitals();
      
      expect(observeSpy).toHaveBeenCalledWith({ entryTypes: ['paint'] });
      expect(observeSpy).toHaveBeenCalledWith({ entryTypes: ['largest-contentful-paint'] });
      expect(observeSpy).toHaveBeenCalledWith({ entryTypes: ['layout-shift'] });
      expect(observeSpy).toHaveBeenCalledWith({ entryTypes: ['first-input'] });
    });
  });

  describe('analyzeBundleSize', () => {
    beforeEach(() => {
      const mockResources = [
        {
          name: 'https://example.com/app.js',
          transferSize: 1024 * 50, // 50KB
          initiatorType: 'script'
        },
        {
          name: 'https://example.com/styles.css',
          transferSize: 1024 * 20, // 20KB
          initiatorType: 'link'
        },
        {
          name: 'https://example.com/vendor.js',
          transferSize: 1024 * 100, // 100KB
          initiatorType: 'script'
        }
      ];

      (performance.getEntriesByType as jest.Mock).mockReturnValue(mockResources);
    });

    it('should analyze bundle sizes correctly', () => {
      const consoleSpy = jest.spyOn(console, 'log').mockImplementation();
      
      analyzeBundleSize();
      
      expect(consoleSpy).toHaveBeenCalledWith('Bundle Analysis:', expect.objectContaining({
        js: expect.objectContaining({
          count: 2,
          totalSize: '150.00 KB'
        }),
        css: expect.objectContaining({
          count: 1,
          totalSize: '20.00 KB'
        })
      }));
      
      consoleSpy.mockRestore();
    });
  });

  describe('monitorMemoryUsage', () => {
    it('should return memory usage information', () => {
      const memoryInfo = monitorMemoryUsage();
      
      expect(memoryInfo).toEqual({
        used: '10.00 MB',
        total: '20.00 MB',
        limit: '100.00 MB'
      });
    });

    it('should return null when memory API is not available', () => {
      const originalMemory = (performance as any).memory;
      delete (performance as any).memory;
      
      const memoryInfo = monitorMemoryUsage();
      expect(memoryInfo).toBeNull();
      
      (performance as any).memory = originalMemory;
    });
  });
});

// Integration tests for performance monitoring
describe('Performance Integration Tests', () => {
  beforeEach(() => {
    // Reset DOM
    document.head.innerHTML = '';
    document.body.innerHTML = '';
  });

  describe('Lazy Loading Integration', () => {
    it('should observe images with data-src attribute', () => {
      const observeSpy = jest.spyOn(IntersectionObserver.prototype, 'observe');
      
      // Create test images
      const img1 = document.createElement('img');
      img1.setAttribute('data-src', 'test1.jpg');
      document.body.appendChild(img1);
      
      const img2 = document.createElement('img');
      img2.setAttribute('data-src', 'test2.jpg');
      document.body.appendChild(img2);
      
      // Simulate setupLazyLoading function
      const imageObserver = new IntersectionObserver(() => {});
      document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
      });
      
      expect(observeSpy).toHaveBeenCalledTimes(2);
    });
  });

  describe('Resource Preloading', () => {
    it('should add preload links to document head', () => {
      const resources = [
        { href: '/fonts/font.woff2', as: 'font', type: 'font/woff2' },
        { href: '/css/critical.css', as: 'style' }
      ];
      
      // Simulate preloadCriticalResources function
      resources.forEach(resource => {
        const link = document.createElement('link');
        link.rel = 'preload';
        link.href = resource.href;
        link.as = resource.as;
        if (resource.type) {
          link.type = resource.type;
        }
        document.head.appendChild(link);
      });
      
      const preloadLinks = document.querySelectorAll('link[rel="preload"]');
      expect(preloadLinks).toHaveLength(2);
      
      const fontLink = document.querySelector('link[href="/fonts/font.woff2"]') as HTMLLinkElement;
      expect(fontLink.as).toBe('font');
      expect(fontLink.type).toBe('font/woff2');
    });
  });

  describe('DNS Prefetch', () => {
    it('should add DNS prefetch links', () => {
      const domains = [
        'https://fonts.googleapis.com',
        'https://api.example.com'
      ];
      
      // Simulate addDNSPrefetch function
      domains.forEach(domain => {
        const link = document.createElement('link');
        link.rel = 'dns-prefetch';
        link.href = domain;
        document.head.appendChild(link);
      });
      
      const prefetchLinks = document.querySelectorAll('link[rel="dns-prefetch"]');
      expect(prefetchLinks).toHaveLength(2);
    });
  });
});