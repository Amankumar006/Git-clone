import React, { useState, useEffect } from 'react';

interface TOCItem {
  id: string;
  text: string;
  level: number;
}

interface TableOfContentsProps {
  content: any;
  className?: string;
}

const TableOfContents: React.FC<TableOfContentsProps> = ({ content, className = '' }) => {
  const [tocItems, setTocItems] = useState<TOCItem[]>([]);
  const [activeId, setActiveId] = useState<string>('');

  useEffect(() => {
    // Extract headings from content
    const headings = extractHeadings(content);
    setTocItems(headings);

    // Set up intersection observer for active heading tracking (if available)
    if (typeof IntersectionObserver !== 'undefined') {
      try {
        const observer = new IntersectionObserver(
          (entries) => {
            entries.forEach((entry) => {
              if (entry.isIntersecting) {
                setActiveId(entry.target.id);
              }
            });
          },
          {
            rootMargin: '-20% 0% -35% 0%',
            threshold: 0
          }
        );

        // Observe all heading elements
        headings.forEach((heading) => {
          const element = document.getElementById(heading.id);
          if (element && observer && typeof observer.observe === 'function') {
            observer.observe(element);
          }
        });

        return () => {
          if (observer && typeof observer.disconnect === 'function') {
            observer.disconnect();
          }
        };
      } catch (error) {
        // IntersectionObserver not available or failed to initialize
        console.warn('IntersectionObserver not available:', error);
      }
    }
  }, [content]);

  const extractHeadings = (content: any): TOCItem[] => {
    const headings: TOCItem[] = [];
    
    if (!Array.isArray(content)) return headings;
    
    content.forEach((block, index) => {
      if (block?.type === 'heading' && block.attrs?.level <= 3) {
        const text = extractTextFromContent(block.content);
        if (text.trim()) {
          headings.push({
            id: `heading-${index}`,
            text: text.trim(),
            level: block.attrs.level
          });
        }
      }
    });
    
    return headings;
  };

  const extractTextFromContent = (content: any[]): string => {
    if (!Array.isArray(content)) return '';
    
    return content
      .map((item) => {
        if (item?.type === 'text') {
          return item.text || '';
        }
        return '';
      })
      .join('');
  };

  const scrollToHeading = (id: string) => {
    const element = document.getElementById(id);
    if (element) {
      const offset = 80; // Account for fixed header
      const elementPosition = element.getBoundingClientRect().top;
      const offsetPosition = elementPosition + window.pageYOffset - offset;

      window.scrollTo({
        top: offsetPosition,
        behavior: 'smooth'
      });
    }
  };

  if (tocItems.length === 0) {
    return null;
  }

  return (
    <nav className={`table-of-contents ${className}`} aria-label="Table of contents">
      <div className="flex items-center space-x-2 mb-4">
        <svg className="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 10h16M4 14h16M4 18h16" />
        </svg>
        <h3 className="text-sm font-semibold text-gray-900">Table of Contents</h3>
      </div>
      <ul className="space-y-2">
        {tocItems.map((item) => (
          <li key={item.id}>
            <button
              type="button"
              onClick={() => scrollToHeading(item.id)}
              className={`
                block w-full text-left text-sm transition-all duration-200 py-1 px-2 rounded
                ${item.level === 1 ? 'font-medium' : ''}
                ${item.level === 2 ? 'pl-4' : ''}
                ${item.level === 3 ? 'pl-6' : ''}
                ${
                  activeId === item.id
                    ? 'text-blue-600 font-medium bg-blue-50 border-l-2 border-blue-600'
                    : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
                }
              `}
              title={`Go to: ${item.text}`}
            >
              {item.text}
            </button>
          </li>
        ))}
      </ul>
    </nav>
  );
};

export default TableOfContents;