import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import TrendingArticles from '../TrendingArticles';
import { apiService } from '../../utils/api';

// Mock the API
jest.mock('../../utils/api');
const mockedApi = apiService as jest.Mocked<typeof apiService>;

const mockTrendingArticles = [
  {
    id: 1,
    title: 'The Future of JavaScript',
    subtitle: 'Exploring upcoming features',
    author_id: 1,
    username: 'js_expert',
    profile_image_url: '/avatar1.jpg',
    tags: ['javascript', 'future'],
    published_at: '2023-12-01T10:00:00Z',
    view_count: 5000,
    clap_count: 250,
    comment_count: 45,
    trending_score: 95.5,
    reading_time: 8
  },
  {
    id: 2,
    title: 'React Performance Optimization',
    subtitle: 'Advanced techniques for faster apps',
    author_id: 2,
    username: 'react_dev',
    profile_image_url: '/avatar2.jpg',
    tags: ['react', 'performance'],
    published_at: '2023-11-30T15:30:00Z',
    view_count: 3500,
    clap_count: 180,
    comment_count: 32,
    trending_score: 87.2,
    reading_time: 12
  },
  {
    id: 3,
    title: 'Machine Learning Basics',
    subtitle: 'Getting started with ML',
    author_id: 3,
    username: 'ml_researcher',
    profile_image_url: '/avatar3.jpg',
    tags: ['machine-learning', 'python'],
    published_at: '2023-11-29T09:15:00Z',
    view_count: 4200,
    clap_count: 210,
    comment_count: 38,
    trending_score: 82.8,
    reading_time: 15
  }
];

const renderTrendingArticles = (props = {}) => {
  return render(
    <BrowserRouter>
      <TrendingArticles {...props} />
    </BrowserRouter>
  );
};

describe('TrendingArticles Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('Data Loading', () => {
    test('fetches trending articles on mount', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: mockTrendingArticles }
      });

      renderTrendingArticles();

      await waitFor(() => {
        expect(mockedApi.get).toHaveBeenCalledWith('/feed/trending?limit=5&timeframe=7%20days');
      });
    });

    test('displays loading state initially', () => {
      mockedApi.get.mockImplementation(() => new Promise(() => {})); // Never resolves

      renderTrendingArticles();

      expect(screen.getByText('Loading trending articles...')).toBeInTheDocument();
    });

    test('displays trending articles after loading', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: mockTrendingArticles }
      });

      renderTrendingArticles();

      await waitFor(() => {
        expect(screen.getByText('The Future of JavaScript')).toBeInTheDocument();
        expect(screen.getByText('React Performance Optimization')).toBeInTheDocument();
        expect(screen.getByText('Machine Learning Basics')).toBeInTheDocument();
      });
    });
  });

  describe('Article Display', () => {
    test('displays article information correctly', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: mockTrendingArticles }
      });

      renderTrendingArticles();

      await waitFor(() => {
        // Check titles and subtitles
        expect(screen.getByText('The Future of JavaScript')).toBeInTheDocument();
        expect(screen.getByText('Exploring upcoming features')).toBeInTheDocument();
        
        // Check author information
        expect(screen.getByText('js_expert')).toBeInTheDocument();
        expect(screen.getByText('react_dev')).toBeInTheDocument();
        
        // Check reading time
        expect(screen.getByText('8 min read')).toBeInTheDocument();
        expect(screen.getByText('12 min read')).toBeInTheDocument();
      });
    });

    test('displays engagement metrics', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: mockTrendingArticles }
      });

      renderTrendingArticles();

      await waitFor(() => {
        expect(screen.getByText('250')).toBeInTheDocument(); // Claps
        expect(screen.getByText('45')).toBeInTheDocument(); // Comments
      });
    });

    test('displays tags for articles', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: mockTrendingArticles }
      });

      renderTrendingArticles();

      await waitFor(() => {
        expect(screen.getByText('javascript')).toBeInTheDocument();
        expect(screen.getByText('react')).toBeInTheDocument();
        expect(screen.getByText('machine-learning')).toBeInTheDocument();
      });
    });

    test('shows trending indicators', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: mockTrendingArticles }
      });

      renderTrendingArticles();

      await waitFor(() => {
        // Should show trending indicators (fire emoji or similar)
        const trendingIndicators = screen.getAllByText('🔥');
        expect(trendingIndicators.length).toBeGreaterThan(0);
      });
    });
  });

  describe('Ranking and Sorting', () => {
    test('displays articles in trending score order', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: mockTrendingArticles }
      });

      renderTrendingArticles();

      await waitFor(() => {
        const articles = screen.getAllByTestId('trending-article');
        
        // First article should be the one with highest trending score
        expect(articles[0]).toHaveTextContent('The Future of JavaScript');
        expect(articles[1]).toHaveTextContent('React Performance Optimization');
        expect(articles[2]).toHaveTextContent('Machine Learning Basics');
      });
    });

    test('shows ranking numbers', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: mockTrendingArticles }
      });

      renderTrendingArticles();

      await waitFor(() => {
        expect(screen.getByText('1')).toBeInTheDocument();
        expect(screen.getByText('2')).toBeInTheDocument();
        expect(screen.getByText('3')).toBeInTheDocument();
      });
    });
  });

  describe('Customization Props', () => {
    test('respects custom limit prop', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: mockTrendingArticles.slice(0, 2) }
      });

      renderTrendingArticles({ limit: 2 });

      await waitFor(() => {
        expect(mockedApi.get).toHaveBeenCalledWith('/feed/trending?limit=2&timeframe=7%20days');
      });
    });

    test('respects custom timeframe prop', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: mockTrendingArticles }
      });

      renderTrendingArticles({ timeframe: '30 days' });

      await waitFor(() => {
        expect(mockedApi.get).toHaveBeenCalledWith('/feed/trending?limit=5&timeframe=30%20days');
      });
    });

    test('uses custom title when provided', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: mockTrendingArticles }
      });

      renderTrendingArticles({ title: 'Hot Topics This Week' });

      await waitFor(() => {
        expect(screen.getByText('Hot Topics This Week')).toBeInTheDocument();
      });
    });

    test('applies custom className', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: mockTrendingArticles }
      });

      const { container } = renderTrendingArticles({ className: 'custom-trending' });

      await waitFor(() => {
        expect(container.firstChild).toHaveClass('custom-trending');
      });
    });
  });

  describe('Empty States', () => {
    test('shows empty state when no trending articles', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: [] }
      });

      renderTrendingArticles();

      await waitFor(() => {
        expect(screen.getByText('No trending articles right now')).toBeInTheDocument();
        expect(screen.getByText('Check back later for the latest trending content!')).toBeInTheDocument();
      });
    });

    test('shows appropriate message for different timeframes', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: [] }
      });

      renderTrendingArticles({ timeframe: '1 day' });

      await waitFor(() => {
        expect(screen.getByText('No trending articles today')).toBeInTheDocument();
      });
    });
  });

  describe('Error Handling', () => {
    test('handles API errors gracefully', async () => {
      mockedApi.get.mockRejectedValue({
        response: { data: { error: 'Failed to fetch trending articles' } }
      });

      renderTrendingArticles();

      await waitFor(() => {
        expect(screen.getByText('Failed to load trending articles')).toBeInTheDocument();
        expect(screen.getByText('Try Again')).toBeInTheDocument();
      });
    });

    test('handles network errors', async () => {
      mockedApi.get.mockRejectedValue(new Error('Network error'));

      renderTrendingArticles();

      await waitFor(() => {
        expect(screen.getByText('Failed to load trending articles')).toBeInTheDocument();
      });
    });

    test('allows retry after error', async () => {
      mockedApi.get
        .mockRejectedValueOnce(new Error('Network error'))
        .mockResolvedValueOnce({
          data: { success: true, data: mockTrendingArticles }
        });

      renderTrendingArticles();

      await waitFor(() => {
        const retryButton = screen.getByText('Try Again');
        retryButton.click();
      });

      await waitFor(() => {
        expect(screen.getByText('The Future of JavaScript')).toBeInTheDocument();
      });
    });
  });

  describe('Navigation', () => {
    test('navigates to article when clicked', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: mockTrendingArticles }
      });

      renderTrendingArticles();

      await waitFor(() => {
        const articleLinks = screen.getAllByRole('link');
        expect(articleLinks.length).toBeGreaterThan(0);
        
        // Check that links have correct href attributes
        const firstArticleLink = articleLinks.find(link => 
          link.getAttribute('href')?.includes('/article/1')
        );
        expect(firstArticleLink).toBeInTheDocument();
      });
    });

    test('navigates to author profile when author clicked', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: mockTrendingArticles }
      });

      renderTrendingArticles();

      await waitFor(() => {
        const authorLinks = screen.getAllByRole('link');
        const authorLink = authorLinks.find(link => 
          link.getAttribute('href')?.includes('/user/js_expert')
        );
        expect(authorLink).toBeInTheDocument();
      });
    });

    test('navigates to tag page when tag clicked', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: mockTrendingArticles }
      });

      renderTrendingArticles();

      await waitFor(() => {
        const tagLinks = screen.getAllByRole('link');
        const tagLink = tagLinks.find(link => 
          link.getAttribute('href')?.includes('/tag/javascript')
        );
        expect(tagLink).toBeInTheDocument();
      });
    });
  });

  describe('Responsive Design', () => {
    test('renders properly on mobile viewport', async () => {
      Object.defineProperty(window, 'innerWidth', {
        writable: true,
        configurable: true,
        value: 375,
      });

      mockedApi.get.mockResolvedValue({
        data: { success: true, data: mockTrendingArticles }
      });

      renderTrendingArticles();

      await waitFor(() => {
        expect(screen.getByText('The Future of JavaScript')).toBeInTheDocument();
      });
    });

    test('adjusts layout for different screen sizes', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: mockTrendingArticles }
      });

      const { container } = renderTrendingArticles();

      await waitFor(() => {
        // Check that responsive classes are applied
        const trendingContainer = container.querySelector('.trending-articles');
        expect(trendingContainer).toHaveClass('responsive-grid');
      });
    });
  });

  describe('Performance', () => {
    test('memoizes article data to prevent unnecessary re-renders', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: mockTrendingArticles }
      });

      const { rerender } = renderTrendingArticles();

      await waitFor(() => {
        expect(screen.getByText('The Future of JavaScript')).toBeInTheDocument();
      });

      // Re-render with same props
      rerender(
        <BrowserRouter>
          <TrendingArticles />
        </BrowserRouter>
      );

      // Should not make additional API calls
      expect(mockedApi.get).toHaveBeenCalledTimes(1);
    });

    test('handles large datasets efficiently', async () => {
      const largeDataset = Array(100).fill(null).map((_, index) => ({
        ...mockTrendingArticles[0],
        id: index + 1,
        title: `Article ${index + 1}`,
        trending_score: 100 - index
      }));

      mockedApi.get.mockResolvedValue({
        data: { success: true, data: largeDataset }
      });

      renderTrendingArticles({ limit: 100 });

      await waitFor(() => {
        // Should render without performance issues
        expect(screen.getByText('Article 1')).toBeInTheDocument();
      });
    });
  });

  describe('Accessibility', () => {
    test('has proper ARIA labels and roles', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: mockTrendingArticles }
      });

      renderTrendingArticles();

      await waitFor(() => {
        const trendingSection = screen.getByRole('region', { name: /trending/i });
        expect(trendingSection).toBeInTheDocument();
      });
    });

    test('supports keyboard navigation', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: mockTrendingArticles }
      });

      renderTrendingArticles();

      await waitFor(() => {
        const links = screen.getAllByRole('link');
        links.forEach(link => {
          expect(link).toHaveAttribute('tabIndex');
        });
      });
    });

    test('has proper heading structure', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: mockTrendingArticles }
      });

      renderTrendingArticles();

      await waitFor(() => {
        const heading = screen.getByRole('heading', { level: 2 });
        expect(heading).toHaveTextContent('Trending Articles');
      });
    });
  });
});