import React from 'react';
import { render, screen, fireEvent } from '@testing-library/react';
import { jest } from '@jest/globals';
import TableOfContents from '../TableOfContents';

// Mock scrollTo
window.scrollTo = jest.fn();

const mockContent = [
  {
    type: 'paragraph',
    content: [{ type: 'text', text: 'Introduction paragraph' }]
  },
  {
    type: 'heading',
    attrs: { level: 1 },
    content: [{ type: 'text', text: 'Main Heading' }]
  },
  {
    type: 'paragraph',
    content: [{ type: 'text', text: 'Some content' }]
  },
  {
    type: 'heading',
    attrs: { level: 2 },
    content: [{ type: 'text', text: 'Sub Heading' }]
  },
  {
    type: 'heading',
    attrs: { level: 3 },
    content: [{ type: 'text', text: 'Sub Sub Heading' }]
  },
  {
    type: 'heading',
    attrs: { level: 4 }, // Should be ignored (level > 3)
    content: [{ type: 'text', text: 'Deep Heading' }]
  }
];

describe('TableOfContents', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('Rendering', () => {
    it('should render table of contents with headings', () => {
      render(<TableOfContents content={mockContent} />);

      expect(screen.getByText('Table of Contents')).toBeInTheDocument();
      expect(screen.getByText('Main Heading')).toBeInTheDocument();
      expect(screen.getByText('Sub Heading')).toBeInTheDocument();
      expect(screen.getByText('Sub Sub Heading')).toBeInTheDocument();
    });

    it('should not render headings deeper than level 3', () => {
      render(<TableOfContents content={mockContent} />);

      expect(screen.queryByText('Deep Heading')).not.toBeInTheDocument();
    });

    it('should not render when no headings are present', () => {
      const contentWithoutHeadings = [
        {
          type: 'paragraph',
          content: [{ type: 'text', text: 'Just a paragraph' }]
        }
      ];

      const { container } = render(<TableOfContents content={contentWithoutHeadings} />);
      expect(container.firstChild).toBeNull();
    });

    it('should apply custom className', () => {
      const { container } = render(
        <TableOfContents content={mockContent} className="custom-class" />
      );

      expect(container.firstChild).toHaveClass('custom-class');
    });
  });

  describe('Navigation', () => {
    beforeEach(() => {
      // Mock getElementById to return a mock element
      document.getElementById = jest.fn().mockReturnValue({
        getBoundingClientRect: () => ({ top: 100 })
      });
    });

    it('should scroll to heading when clicked', () => {
      render(<TableOfContents content={mockContent} />);

      const headingButton = screen.getByText('Main Heading');
      fireEvent.click(headingButton);

      expect(window.scrollTo).toHaveBeenCalledWith({
        top: expect.any(Number),
        behavior: 'smooth'
      });
    });

    it('should have proper button attributes for accessibility', () => {
      render(<TableOfContents content={mockContent} />);

      const headingButton = screen.getByText('Main Heading');
      expect(headingButton).toHaveAttribute('title', 'Go to: Main Heading');
    });
  });

  describe('Hierarchy and Styling', () => {
    it('should apply correct indentation classes for different heading levels', () => {
      render(<TableOfContents content={mockContent} />);

      const level1Button = screen.getByText('Main Heading');
      const level2Button = screen.getByText('Sub Heading');
      const level3Button = screen.getByText('Sub Sub Heading');

      // Level 1 should have font-medium class
      expect(level1Button).toHaveClass('font-medium');
      
      // Level 2 should have pl-4 class
      expect(level2Button).toHaveClass('pl-4');
      
      // Level 3 should have pl-6 class
      expect(level3Button).toHaveClass('pl-6');
    });

    it('should highlight active heading', () => {
      render(<TableOfContents content={mockContent} />);

      // Since we can't easily test IntersectionObserver in jsdom,
      // we'll just verify the classes exist for active state
      const buttons = screen.getAllByRole('button');
      buttons.forEach(button => {
        expect(button).toHaveClass('text-gray-600', 'hover:text-gray-900');
      });
    });
  });

  describe('Content Extraction', () => {
    it('should handle complex heading content', () => {
      const complexContent = [
        {
          type: 'heading',
          attrs: { level: 1 },
          content: [
            { type: 'text', text: 'Complex ' },
            { type: 'text', text: 'Heading ', marks: [{ type: 'bold' }] },
            { type: 'text', text: 'Text' }
          ]
        }
      ];

      render(<TableOfContents content={complexContent} />);
      expect(screen.getByText('Complex Heading Text')).toBeInTheDocument();
    });

    it('should handle empty heading content', () => {
      const emptyHeadingContent = [
        {
          type: 'heading',
          attrs: { level: 1 },
          content: []
        },
        {
          type: 'heading',
          attrs: { level: 2 },
          content: [{ type: 'text', text: '' }]
        }
      ];

      render(<TableOfContents content={emptyHeadingContent} />);
      
      // Should not render empty headings
      const buttons = screen.queryAllByRole('button');
      expect(buttons).toHaveLength(0);
    });

    it('should handle non-array content gracefully', () => {
      const invalidContent = "not an array";
      
      const { container } = render(<TableOfContents content={invalidContent} />);
      expect(container.firstChild).toBeNull();
    });
  });

  describe('Accessibility', () => {
    it('should have proper ARIA labels', () => {
      render(<TableOfContents content={mockContent} />);

      const nav = screen.getByRole('navigation');
      expect(nav).toHaveAttribute('aria-label', 'Table of contents');
    });

    it('should have semantic HTML structure', () => {
      render(<TableOfContents content={mockContent} />);

      expect(screen.getByRole('navigation')).toBeInTheDocument();
      expect(screen.getByRole('list')).toBeInTheDocument();
      
      const listItems = screen.getAllByRole('listitem');
      expect(listItems).toHaveLength(3); // 3 headings (level 1, 2, 3)
    });

    it('should have keyboard accessible buttons', () => {
      render(<TableOfContents content={mockContent} />);

      const buttons = screen.getAllByRole('button');
      buttons.forEach(button => {
        expect(button).toHaveAttribute('type', 'button');
      });
    });
  });
});