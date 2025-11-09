import React, { useState, useRef, useEffect } from 'react';
import { useIntersectionObserver } from '../utils/performance';

interface LazyImageProps {
  src: string;
  alt: string;
  className?: string;
  width?: number;
  height?: number;
  placeholder?: string;
  quality?: number;
  sizes?: string;
  srcSet?: string;
  loading?: 'lazy' | 'eager';
  onLoad?: () => void;
  onError?: () => void;
}

const LazyImage: React.FC<LazyImageProps> = ({
  src,
  alt,
  className = '',
  width,
  height,
  placeholder,
  quality = 85,
  sizes,
  srcSet,
  loading = 'lazy',
  onLoad,
  onError
}) => {
  const [isLoaded, setIsLoaded] = useState(false);
  const [isInView, setIsInView] = useState(false);
  const [hasError, setHasError] = useState(false);
  const imgRef = useRef<HTMLImageElement>(null);
  const [imageSrc, setImageSrc] = useState<string>('');

  // Generate optimized image URL
  const getOptimizedSrc = (originalSrc: string) => {
    const url = new URL(originalSrc, window.location.origin);
    if (width) url.searchParams.set('w', width.toString());
    if (height) url.searchParams.set('h', height.toString());
    if (quality) url.searchParams.set('q', quality.toString());
    return url.toString();
  };

  // Generate responsive srcSet
  const getResponsiveSrcSet = (originalSrc: string) => {
    if (srcSet) return srcSet;
    
    const breakpoints = [480, 768, 1024, 1200, 1600];
    return breakpoints
      .map(bp => `${getOptimizedSrc(originalSrc)}?w=${bp} ${bp}w`)
      .join(', ');
  };

  // Intersection Observer for lazy loading
  const observer = useIntersectionObserver(
    (entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting && !isInView) {
          setIsInView(true);
          setImageSrc(getOptimizedSrc(src));
        }
      });
    },
    { rootMargin: '50px' }
  );

  useEffect(() => {
    if (loading === 'eager') {
      setIsInView(true);
      setImageSrc(getOptimizedSrc(src));
    } else if (imgRef.current && observer) {
      observer.observe(imgRef.current);
    }

    return () => {
      if (imgRef.current && observer) {
        observer.unobserve(imgRef.current);
      }
    };
  }, [src, loading, observer]);

  const handleLoad = () => {
    setIsLoaded(true);
    onLoad?.();
  };

  const handleError = () => {
    setHasError(true);
    onError?.();
  };

  // Default placeholder (base64 encoded 1x1 transparent pixel)
  const defaultPlaceholder = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

  return (
    <div className={`relative overflow-hidden ${className}`}>
      {/* Placeholder/Loading state */}
      {!isLoaded && !hasError && (
        <div 
          className="absolute inset-0 bg-gray-200 animate-pulse flex items-center justify-center"
          style={{ width, height }}
        >
          {placeholder ? (
            <img 
              src={placeholder} 
              alt="" 
              className="w-full h-full object-cover opacity-50"
            />
          ) : (
            <svg 
              className="w-8 h-8 text-gray-400" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path 
                strokeLinecap="round" 
                strokeLinejoin="round" 
                strokeWidth={2} 
                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" 
              />
            </svg>
          )}
        </div>
      )}

      {/* Error state */}
      {hasError && (
        <div 
          className="absolute inset-0 bg-gray-100 flex items-center justify-center"
          style={{ width, height }}
        >
          <div className="text-center text-gray-500">
            <svg 
              className="w-8 h-8 mx-auto mb-2" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
            >
              <path 
                strokeLinecap="round" 
                strokeLinejoin="round" 
                strokeWidth={2} 
                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" 
              />
            </svg>
            <span className="text-sm">Failed to load</span>
          </div>
        </div>
      )}

      {/* Main image */}
      <img
        ref={imgRef}
        src={isInView ? imageSrc : defaultPlaceholder}
        srcSet={isInView ? getResponsiveSrcSet(src) : undefined}
        sizes={sizes}
        alt={alt}
        width={width}
        height={height}
        className={`
          transition-opacity duration-300 
          ${isLoaded ? 'opacity-100' : 'opacity-0'}
          ${className}
        `}
        onLoad={handleLoad}
        onError={handleError}
        loading={loading}
      />

      {/* WebP support detection and fallback */}
      <noscript>
        <img 
          src={getOptimizedSrc(src)} 
          alt={alt} 
          width={width} 
          height={height}
          className={className}
        />
      </noscript>
    </div>
  );
};

export default LazyImage;