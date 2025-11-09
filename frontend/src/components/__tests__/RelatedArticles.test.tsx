import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import { jest } from '@jest/globals';
import RelatedArticles from '../RelatedArticles';
import { apiService } from '../../utils/api';

// Mock the API service
jest.mock('../../utils/api');
const mockApiService = apiService as jest.Mocked<typeof apiService>;

const mockCurrentArticle = {
  id: 1,
  title: 'Current Article',
  author_id: 1,
  tags: ['react', 'javascript']
};

const mockRelatedArticles = [
  {
    id: 2,
    title: 'Related Article 1',
    subtitle: 'This is a related article',
    author_id: 2,
    username: 'author1',
    author_avatar: 'https://example.com/avatar1.jpg',
    featured_image_url: 'https://example.com/image1.jpg',
    published_at: '2023-01-01T00:00:00Z',
    reading_time: 3,
    view_count: 50,
    clap_count: 10,
    comment_count: 5,
    tags: ['react', 'frontend']
  },
  {
    id: 3,
    title: 'Related Article 2',
    subtitle: 'Another related article',
    author_id: 3,
    username: 'author2',
    author_avatar: 'https://example.com/avatar2.jpg',
    published_at: '2023-01-02T00:00:00Z',
    reading_time: 5,
    view_count: 75,
    clap_count: 15,
    comment_count: 8,
    tags: ['javascript', 'web']
  }
];

const renderRelatedArticles = (currentArticle = mockCurrentArticle) => {
  return render(
    <BrowserRouter>
      <RelatedArticles currentArticle={currentArticle} />
    </BrowserRouter>
  );
};

describe('RelatedArticles', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('Loading State', () => {
    it('should show loading skeleton while fetching articles', () => {
      mockApiService.articles.getRelated.mockImplementation(() => 
        new Promise(() => {}) // Never resolves
      );

      renderRelatedArticles();

      expect(screen.getByText('Related Articles')).toBeInTheDocument();
      
      // Should show loading skeletons
      const skeletons = screen.getAllByTestId('loading-skeleton') || 
                       document.querySelectorAll('.animate-pulse');
      expect(skeletons.length).toBeGreaterThan(0);
    });
  });

  describe('Successful Data Loading', () => {
    beforeEach(() => {
      mockApiService.articles.getRelated.mockResolvedValue({
        success: true,
        data: mockRelatedArticles,
        message: 'Success'
      });
    });

    it('should render related articles section', async () => {
      renderRelatedArticles();

      await waitFor(() => {
        expect(screen.getByText('Related Articles')).toBeInTheDocument();
        expect(screen.getByText('Related Article 1')).toBeInTheDocument();
        expect(screen.getByText('Related Article 2')).toBeInTheDocument();
      });
    });

    it('should render article cards with proper information', async () => {
      renderRelatedArticles();

      await waitFor(() => {
        // Check titles
        expect(screen.getByText('Related Article 1')).toBeInTheDocument();
        expect(screen.getByText('Related Article 2')).toBeInTheDocument();
        
        // Check subtitles
        expect(screen.getByText('This is a related article')).toBeInTheDocument();
        expect(screen.getByText('Another related article')).toBeInTheDocument();
        
        // Check authors
        expect(screen.getByText('author1')).toBeInTheDocument();
        expect(screen.getByText('author2')).toBeInTheDocument();
      });
    });

    it('should have proper links to articles', async () => {
      renderRelatedArticles();

      await waitFor(() => {
        const articleLinks = screen.getAllByRole('link');
        const articleLink1 = articleLinks.find(link => 
          link.getAttribute('href') === '/article/2'
        );
        const articleLink2 = articleLinks.find(link => 
          link.getAttribute('href') === '/article/3'
        );
        
        expect(articleLink1).toBeInTheDocument();
        expect(articleLink2).toBeInTheDocument();
      });
    });

    it('should apply custom className', async () => {
      const { container } = render(
        <BrowserRouter>
          <RelatedArticles 
            currentArticle={mockCurrentArticle} 
            className="custom-class" 
          />
        </BrowserRouter>
      );

      await waitFor(() => {
        expect(container.firstChild).toHaveClass('custom-class');
      });
    });
  });

  describe('Fallback Behavior', () => {
    it('should fallback to recommended articles when related articles API fails', async () => {
      mockApiService.articles.getRelated.mockRejectedValue(new Error('API Error'));
      mockApiService.articles.getRecommended.mockResolvedValue({
        success: true,
        data: mockRelatedArticles,
        message: 'Success'
      });

      renderRelatedArticles();

      await waitFor(() => {
        expect(screen.getByText('Related Articles')).toBeInTheDocument();
        expect(screen.getByText('Related Article 1')).toBeInTheDocument();
      });

      expect(mockApiService.articles.getRecommended).toHaveBeenCalledWith(6);
    });

    it('should fallback to tag-based search when both related and recommended fail', async () => {
      mockApiService.articles.getRelated.mockRejectedValue(new Error('API Error'));
      mockApiService.articles.getRecommended.mockRejectedValue(new Error('API Error'));
      mockApiService.search.articles.mockResolvedValue({
        success: true,
        data: mockRelatedArticles,
        message: 'Success'
      });

      renderRelatedArticles();

      await waitFor(() => {
        expect(screen.getByText('Related Articles')).toBeInTheDocument();
      });

      expect(mockApiService.search.articles).toHaveBeenCalledWith('', {
        tags: ['react'],
        limit: 6
      });
    });

    it('should filter out current article from results', async () => {
      const articlesWithCurrent = [
        ...mockRelatedArticles,
        { ...mockCurrentArticle, id: 1 } // Current article should be filtered out
      ];

      mockApiService.articles.getRelated.mockResolvedValue({
        success: true,
        data: articlesWithCurrent,
        message: 'Success'
      });

      renderRelatedArticles();

      await waitFor(() => {
        expect(screen.getByText('Related Article 1')).toBeInTheDocument();
        expect(screen.getByText('Related Article 2')).toBeInTheDocument();
        expect(screen.queryByText('Current Article')).not.toBeInTheDocument();
      });
    });
  });

  describe('Error Handling', () => {
    it('should show error message when all API calls fail', async () => {
      mockApiService.articles.getRelated.mockRejectedValue(new Error('API Error'));
      mockApiService.articles.getRecommended.mockRejectedValue(new Error('API Error'));
      mockApiService.search.articles.mockRejectedValue(new Error('API Error'));

      renderRelatedArticles();

      await waitFor(() => {
        expect(screen.getByText('Related Articles')).toBeInTheDocument();
        expect(screen.getByText('Failed to load related articles')).toBeInTheDocument();
      });
    });

    it('should not render section when no articles are available', async () => {
      mockApiService.articles.getRelated.mockResolvedValue({
        success: true,
        data: [],
        message: 'Success'
      });

      const { container } = renderRelatedArticles();

      await waitFor(() => {
        expect(container.firstChild).toBeNull();
      });
    });
  });

  describe('Responsive Design', () => {
    beforeEach(() => {
      mockApiService.articles.getRelated.mockResolvedValue({
        success: true,
        data: mockRelatedArticles,
        message: 'Success'
      });
    });

    it('should have responsive grid classes', async () => {
      renderRelatedArticles();

      await waitFor(() => {
        const grid = screen.getByText('Related Articles').parentElement?.querySelector('.grid');
        expect(grid).toHaveClass('grid-cols-1', 'md:grid-cols-2', 'lg:grid-cols-3');
      });
    });
  });

  describe('Accessibility', () => {
    beforeEach(() => {
      mockApiService.articles.getRelated.mockResolvedValue({
        success: true,
        data: mockRelatedArticles,
        message: 'Success'
      });
    });

    it('should have proper semantic structure', async () => {
      renderRelatedArticles();

      await waitFor(() => {
        const section = screen.getByRole('region') || 
                       screen.getByText('Related Articles').closest('section');
        expect(section).toBeInTheDocument();
      });
    });

    it('should have proper heading hierarchy', async () => {
      renderRelatedArticles();

      await waitFor(() => {
        const heading = screen.getByRole('heading', { name: /related articles/i });
        expect(heading).toBeInTheDocument();
      });
    });
  });

  describe('API Integration', () => {
    it('should call getRelated API with correct parameters', () => {
      mockApiService.articles.getRelated.mockResolvedValue({
        success: true,
        data: mockRelatedArticles,
        message: 'Success'
      });

      renderRelatedArticles();

      expect(mockApiService.articles.getRelated).toHaveBeenCalledWith('1', 6);
    });

    it('should handle different article ID types', () => {
      const articleWithStringId = { ...mockCurrentArticle, id: '123' };
      
      mockApiService.articles.getRelated.mockResolvedValue({
        success: true,
        data: mockRelatedArticles,
        message: 'Success'
      });

      renderRelatedArticles(articleWithStringId);

      expect(mockApiService.articles.getRelated).toHaveBeenCalledWith('123', 6);
    });
  });
});