import React, { useState, useEffect } from 'react';

interface ReadingProgressProps {
  className?: string;
}

const ReadingProgress: React.FC<ReadingProgressProps> = ({ className = '' }) => {
  const [progress, setProgress] = useState(0);

  useEffect(() => {
    const updateProgress = () => {
      // Get the main article content element for more accurate progress
      const articleElement = document.querySelector('main') || document.body;
      const articleRect = articleElement.getBoundingClientRect();
      const articleTop = articleRect.top + window.pageYOffset;
      const articleHeight = articleRect.height;
      
      const scrollTop = window.pageYOffset;
      const viewportHeight = window.innerHeight;
      
      // Calculate progress based on article content, not entire page
      const articleStart = articleTop - viewportHeight * 0.2; // Start progress when article is 20% visible
      const articleEnd = articleTop + articleHeight - viewportHeight * 0.8; // End when 80% through
      
      const scrollProgress = (scrollTop - articleStart) / (articleEnd - articleStart);
      const scrollPercent = Math.min(100, Math.max(0, scrollProgress * 100));
      
      setProgress(scrollPercent);
    };

    const throttledUpdateProgress = throttle(updateProgress, 16); // ~60fps

    window.addEventListener('scroll', throttledUpdateProgress, { passive: true });
    window.addEventListener('resize', throttledUpdateProgress, { passive: true });
    
    // Initial calculation
    updateProgress();

    return () => {
      window.removeEventListener('scroll', throttledUpdateProgress);
      window.removeEventListener('resize', throttledUpdateProgress);
    };
  }, []);

  // Throttle function to limit how often the progress is updated
  function throttle(func: Function, limit: number) {
    let inThrottle: boolean;
    return function(this: any, ...args: any[]) {
      if (!inThrottle) {
        func.apply(this, args);
        inThrottle = true;
        setTimeout(() => inThrottle = false, limit);
      }
    };
  }

  return (
    <div className={`reading-progress ${className}`}>
      <div className="h-1 bg-gray-200">
        <div 
          className="h-full bg-gradient-to-r from-blue-500 to-blue-600 transition-all duration-300 ease-out"
          style={{ width: `${progress}%` }}
          role="progressbar"
          aria-valuenow={Math.round(progress)}
          aria-valuemin={0}
          aria-valuemax={100}
          aria-label={`Reading progress: ${Math.round(progress)}%`}
        />
      </div>
    </div>
  );
};

export default ReadingProgress;