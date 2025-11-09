import React, { useState, useEffect } from 'react';

interface BackToTopProps {
  className?: string;
  showAfter?: number; // Show button after scrolling this many pixels
}

const BackToTop: React.FC<BackToTopProps> = ({ 
  className = '', 
  showAfter = 300 
}) => {
  const [isVisible, setIsVisible] = useState(false);

  useEffect(() => {
    const toggleVisibility = () => {
      if (window.pageYOffset > showAfter) {
        setIsVisible(true);
      } else {
        setIsVisible(false);
      }
    };

    const throttledToggleVisibility = throttle(toggleVisibility, 100);

    window.addEventListener('scroll', throttledToggleVisibility);
    
    return () => {
      window.removeEventListener('scroll', throttledToggleVisibility);
    };
  }, [showAfter]);

  const scrollToTop = () => {
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  };

  // Throttle function to limit how often visibility is checked
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

  if (!isVisible) {
    return null;
  }

  return (
    <button
      onClick={scrollToTop}
      className={`
        fixed bottom-6 right-6 z-50
        bg-white border border-gray-200 shadow-lg
        hover:bg-gray-50 hover:shadow-xl hover:scale-105
        p-3 rounded-full
        transition-all duration-300 ease-in-out
        focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
        transform ${isVisible ? 'translate-y-0 opacity-100' : 'translate-y-2 opacity-0'}
        ${className}
      `}
      aria-label="Back to top"
      title="Back to top"
    >
      <svg 
        className="w-5 h-5 text-gray-600" 
        fill="none" 
        stroke="currentColor" 
        viewBox="0 0 24 24"
        aria-hidden="true"
      >
        <path 
          strokeLinecap="round" 
          strokeLinejoin="round" 
          strokeWidth={2} 
          d="M5 15l7-7 7 7" 
        />
      </svg>
    </button>
  );
};

export default BackToTop;