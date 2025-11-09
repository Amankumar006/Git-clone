/**
 * Frontend performance optimization utilities
 */

/**
 * Lazy loading utility for images
 */
export const setupLazyLoading = () => {
  if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target as HTMLImageElement;
          const src = img.dataset.src;
          
          if (src) {
            img.src = src;
            img.classList.remove('lazy');
            img.classList.add('loaded');
            observer.unobserve(img);
          }
        }
      });
    });

    // Observe all lazy images
    document.querySelectorAll('img[data-src]').forEach(img => {
      imageObserver.observe(img);
    });
  }
};

/**
 * Preload critical resources
 */
export const preloadCriticalResources = (resources: Array<{href: string, as: string, type?: string}>) => {
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
};

/**
 * Debounce function for performance optimization
 */
export const debounce = <T extends (...args: any[]) => any>(
  func: T,
  wait: number,
  immediate?: boolean
): ((...args: Parameters<T>) => void) => {
  let timeout: NodeJS.Timeout | null = null;
  
  return (...args: Parameters<T>) => {
    const later = () => {
      timeout = null;
      if (!immediate) func(...args);
    };
    
    const callNow = immediate && !timeout;
    
    if (timeout) clearTimeout(timeout);
    timeout = setTimeout(later, wait);
    
    if (callNow) func(...args);
  };
};

/**
 * Throttle function for performance optimization
 */
export const throttle = <T extends (...args: any[]) => any>(
  func: T,
  limit: number
): ((...args: Parameters<T>) => void) => {
  let inThrottle: boolean;
  
  return (...args: Parameters<T>) => {
    if (!inThrottle) {
      func(...args);
      inThrottle = true;
      setTimeout(() => inThrottle = false, limit);
    }
  };
};

/**
 * Intersection Observer hook for lazy loading
 */
export const useIntersectionObserver = (
  callback: (entries: IntersectionObserverEntry[]) => void,
  options?: IntersectionObserverInit
) => {
  const observer = new IntersectionObserver(callback, {
    rootMargin: '50px',
    threshold: 0.1,
    ...options
  });
  
  return observer;
};

/**
 * Virtual scrolling utility for large lists
 */
export class VirtualScroller {
  private container: HTMLElement;
  private itemHeight: number;
  private visibleCount: number;
  private totalCount: number;
  private scrollTop: number = 0;
  private renderCallback: (startIndex: number, endIndex: number) => void;
  
  constructor(
    container: HTMLElement,
    itemHeight: number,
    totalCount: number,
    renderCallback: (startIndex: number, endIndex: number) => void
  ) {
    this.container = container;
    this.itemHeight = itemHeight;
    this.totalCount = totalCount;
    this.renderCallback = renderCallback;
    this.visibleCount = Math.ceil(container.clientHeight / itemHeight) + 2; // Buffer
    
    this.setupScrollListener();
    this.render();
  }
  
  private setupScrollListener() {
    this.container.addEventListener('scroll', throttle(() => {
      this.scrollTop = this.container.scrollTop;
      this.render();
    }, 16)); // ~60fps
  }
  
  private render() {
    const startIndex = Math.floor(this.scrollTop / this.itemHeight);
    const endIndex = Math.min(startIndex + this.visibleCount, this.totalCount);
    
    this.renderCallback(startIndex, endIndex);
  }
  
  public updateTotalCount(newCount: number) {
    this.totalCount = newCount;
    this.render();
  }
}

/**
 * Image optimization utilities
 */
export const optimizeImage = (
  src: string,
  width?: number,
  height?: number,
  quality?: number
): string => {
  const url = new URL(src, window.location.origin);
  
  if (width) url.searchParams.set('w', width.toString());
  if (height) url.searchParams.set('h', height.toString());
  if (quality) url.searchParams.set('q', quality.toString());
  
  return url.toString();
};

/**
 * Generate responsive image srcset
 */
export const generateSrcSet = (baseSrc: string, sizes: number[]): string => {
  return sizes
    .map(size => `${optimizeImage(baseSrc, size)} ${size}w`)
    .join(', ');
};

/**
 * Web Vitals monitoring
 */
export const measureWebVitals = () => {
  // Measure First Contentful Paint
  const measureFCP = () => {
    const observer = new PerformanceObserver((list) => {
      const entries = list.getEntries();
      const fcp = entries.find(entry => entry.name === 'first-contentful-paint');
      if (fcp) {
        console.log('FCP:', fcp.startTime);
        // Send to analytics
      }
    });
    observer.observe({ entryTypes: ['paint'] });
  };

  // Measure Largest Contentful Paint
  const measureLCP = () => {
    const observer = new PerformanceObserver((list) => {
      const entries = list.getEntries();
      const lcp = entries[entries.length - 1];
      console.log('LCP:', lcp.startTime);
      // Send to analytics
    });
    observer.observe({ entryTypes: ['largest-contentful-paint'] });
  };

  // Measure Cumulative Layout Shift
  const measureCLS = () => {
    let clsValue = 0;
    const observer = new PerformanceObserver((list) => {
      for (const entry of list.getEntries()) {
        if (!(entry as any).hadRecentInput) {
          clsValue += (entry as any).value;
        }
      }
      console.log('CLS:', clsValue);
      // Send to analytics
    });
    observer.observe({ entryTypes: ['layout-shift'] });
  };

  // Measure First Input Delay
  const measureFID = () => {
    const observer = new PerformanceObserver((list) => {
      const entries = list.getEntries();
      const fid = entries[0];
      console.log('FID:', (fid as any).processingStart - fid.startTime);
      // Send to analytics
    });
    observer.observe({ entryTypes: ['first-input'] });
  };

  if ('PerformanceObserver' in window) {
    measureFCP();
    measureLCP();
    measureCLS();
    measureFID();
  }
};

/**
 * Bundle size analyzer
 */
export const analyzeBundleSize = () => {
  if ('performance' in window && 'getEntriesByType' in performance) {
    const resources = performance.getEntriesByType('resource') as PerformanceResourceTiming[];
    
    const jsResources = resources.filter(resource => 
      resource.name.includes('.js') && resource.transferSize
    );
    
    const cssResources = resources.filter(resource => 
      resource.name.includes('.css') && resource.transferSize
    );
    
    const totalJSSize = jsResources.reduce((total, resource) => total + resource.transferSize, 0);
    const totalCSSSize = cssResources.reduce((total, resource) => total + resource.transferSize, 0);
    
    console.log('Bundle Analysis:', {
      js: {
        count: jsResources.length,
        totalSize: `${(totalJSSize / 1024).toFixed(2)} KB`,
        resources: jsResources.map(r => ({
          name: r.name.split('/').pop(),
          size: `${(r.transferSize / 1024).toFixed(2)} KB`
        }))
      },
      css: {
        count: cssResources.length,
        totalSize: `${(totalCSSSize / 1024).toFixed(2)} KB`,
        resources: cssResources.map(r => ({
          name: r.name.split('/').pop(),
          size: `${(r.transferSize / 1024).toFixed(2)} KB`
        }))
      }
    });
  }
};

/**
 * Memory usage monitoring
 */
export const monitorMemoryUsage = () => {
  if ('memory' in performance) {
    const memory = (performance as any).memory;
    
    return {
      used: `${(memory.usedJSHeapSize / 1024 / 1024).toFixed(2)} MB`,
      total: `${(memory.totalJSHeapSize / 1024 / 1024).toFixed(2)} MB`,
      limit: `${(memory.jsHeapSizeLimit / 1024 / 1024).toFixed(2)} MB`
    };
  }
  
  return null;
};

/**
 * Service Worker registration for caching
 */
export const registerServiceWorker = async () => {
  // Temporarily disable service worker to fix infinite refresh
  console.log('Service Worker registration disabled for debugging');
  
  // Unregister existing service workers
  if ('serviceWorker' in navigator) {
    try {
      const registrations = await navigator.serviceWorker.getRegistrations();
      for (const registration of registrations) {
        await registration.unregister();
        console.log('Unregistered service worker:', registration);
      }
    } catch (error) {
      console.error('Error unregistering service workers:', error);
    }
  }
  
  return null;
};

/**
 * Critical CSS inlining utility
 */
export const inlineCriticalCSS = (css: string) => {
  const style = document.createElement('style');
  style.textContent = css;
  document.head.appendChild(style);
};

/**
 * Resource hints utility
 */
export const addResourceHints = (hints: Array<{rel: string, href: string, as?: string}>) => {
  hints.forEach(hint => {
    const link = document.createElement('link');
    link.rel = hint.rel;
    link.href = hint.href;
    if (hint.as) link.setAttribute('as', hint.as);
    document.head.appendChild(link);
  });
};