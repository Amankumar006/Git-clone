import React from 'react';
import { render, screen, waitFor, fireEvent } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import { jest } from '@jest/globals';
import ArticlePage from '../../pages/ArticlePage';
import { apiService } from '../../utils/api';

// Mock the API service
jest.mock('../../utils/api');
const mockApiService = apiService as jest.Mocked<typeof apiService>;

// Mock the SEO utils
jest.mock('../../utils/seo', () => ({
  updateSEOMetadata: jest.fn()
}));

// Mock the analytics hook
jest.mock('../../hooks/useArticleAnalytics', () => ({
  useArticleAnalytics: () => ({
    analytics: {
      articleId: '1',
      startTime: Date.now(),
      scrollDepth: 0,
      timeSpent: 0,
      isRead: false
    }
  })
}));

// Mock react-router-dom
jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useParams: () => ({ id: '1' })
}));

const mockArticle = {
  id: 1,
  title: 'Test Article Title',
  subtitle: 'Test Article Subtitle',
  content: [
    {
      type: 'paragraph',
      content: [
        {
          type: 'text',
          text: 'This is a test article content.'
        }
      ]
    },
    {
      type: 'heading',
      attrs: { level: 2 },
      content: [
        {
          type: 'text',
          text: 'Test Heading'
        }
      ]
    }
  ],
  author_id: 1,
  username: 'testuser',
  author_avatar: 'https://example.com/avatar.jpg',
  featured_image_url: 'https://example.com/featured.jpg',
  status: 'published',
  published_at: '2023-01-01T00:00:00Z',
  reading_time: 5,
  view_count: 100,
  clap_count: 25,
  comment_count: 10,
  created_at: '2023-01-01T00:00:00Z',
  updated_at: '2023-01-01T00:00:00Z',
  tags: ['test', 'article']
};

const renderArticlePage = () => {
  return render(
    <BrowserRouter>
      <ArticlePage />
    </BrowserRouter>
  );
};

describe('ArticlePage', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('Loading State', () => {
    it('should show loading skeleton while fetching article', () => {
      mockApiService.articles.getById.mockImplementation(() => 
        new Promise(() => {}) // Never resolves
      );

      renderArticlePage();

      expect(screen.getByTestId('loading-skeleton') || screen.getByText(/loading/i)).toBeInTheDocument();
    });
  });

  describe('Article Display', () => {
    beforeEach(() => {
      mockApiService.articles.getById.mockResolvedValue({
        success: true,
        data: mockArticle,
        message: 'Success'
      });
    });

    it('should render article title and subtitle', async () => {
      renderArticlePage();

      await waitFor(() => {
        expect(screen.getByText('Test Article Title')).toBeInTheDocument();
        expect(screen.getByText('Test Article Subtitle')).toBeInTheDocument();
      });
    });

    it('should render author information', async () => {
      renderArticlePage();

      await waitFor(() => {
        expect(screen.getByText('testuser')).toBeInTheDocument();
        expect(screen.getByAltText('testuser')).toBeInTheDocument();
      });
    });

    it('should render article metadata', async () => {
      renderArticlePage();

      await waitFor(() => {
        expect(screen.getByText('5 min read')).toBeInTheDocument();
        expect(screen.getByText(/January 1, 2023/)).toBeInTheDocument();
      });
    });

    it('should render featured image when present', async () => {
      renderArticlePage();

      await waitFor(() => {
        const featuredImage = screen.getByAltText('Test Article Title');
        expect(featuredImage).toBeInTheDocument();
        expect(featuredImage).toHaveAttribute('src', 'https://example.com/featured.jpg');
      });
    });

    it('should render article content', async () => {
      renderArticlePage();

      await waitFor(() => {
        expect(screen.getByText('This is a test article content.')).toBeInTheDocument();
        expect(screen.getByText('Test Heading')).toBeInTheDocument();
      });
    });

    it('should render article statistics', async () => {
      renderArticlePage();

      await waitFor(() => {
        expect(screen.getByText('100 views')).toBeInTheDocument();
        expect(screen.getByText('25 claps')).toBeInTheDocument();
        expect(screen.getByText('10 comments')).toBeInTheDocument();
      });
    });

    it('should render tags when present', async () => {
      renderArticlePage();

      await waitFor(() => {
        expect(screen.getByText('test')).toBeInTheDocument();
        expect(screen.getByText('article')).toBeInTheDocument();
      });
    });
  });

  describe('Social Sharing', () => {
    beforeEach(() => {
      mockApiService.articles.getById.mockResolvedValue({
        success: true,
        data: mockArticle,
        message: 'Success'
      });

      // Mock window.open
      global.open = jest.fn();
      
      // Mock clipboard API
      Object.assign(navigator, {
        clipboard: {
          writeText: jest.fn().mockResolvedValue(undefined)
        }
      });
    });

    it('should have social sharing buttons', async () => {
      renderArticlePage();

      await waitFor(() => {
        expect(screen.getByLabelText('Share on Twitter')).toBeInTheDocument();
        expect(screen.getByLabelText('Share on Facebook')).toBeInTheDocument();
        expect(screen.getByLabelText('Share on LinkedIn')).toBeInTheDocument();
        expect(screen.getByLabelText('Copy link')).toBeInTheDocument();
      });
    });

    it('should open Twitter share dialog when Twitter button is clicked', async () => {
      renderArticlePage();

      await waitFor(() => {
        const twitterButton = screen.getByLabelText('Share on Twitter');
        fireEvent.click(twitterButton);
        
        expect(global.open).toHaveBeenCalledWith(
          expect.stringContaining('twitter.com/intent/tweet'),
          '_blank',
          'width=600,height=400,scrollbars=yes,resizable=yes'
        );
      });
    });

    it('should copy link to clipboard when copy button is clicked', async () => {
      renderArticlePage();

      await waitFor(() => {
        const copyButton = screen.getByLabelText('Copy link');
        fireEvent.click(copyButton);
        
        expect(navigator.clipboard.writeText).toHaveBeenCalledWith(window.location.href);
      });
    });
  });

  describe('Responsive Design', () => {
    beforeEach(() => {
      mockApiService.articles.getById.mockResolvedValue({
        success: true,
        data: mockArticle,
        message: 'Success'
      });
    });

    it('should have responsive classes for mobile layout', async () => {
      renderArticlePage();

      await waitFor(() => {
        const mainContent = screen.getByRole('main');
        expect(mainContent).toHaveClass('max-w-4xl');
      });
    });

    it('should show mobile table of contents on small screens', async () => {
      renderArticlePage();

      await waitFor(() => {
        // Mobile TOC should be present but hidden on large screens
        const mobileTOC = screen.getByText('Table of Contents');
        expect(mobileTOC).toBeInTheDocument();
      });
    });
  });

  describe('Error Handling', () => {
    it('should show error message when article fetch fails', async () => {
      mockApiService.articles.getById.mockRejectedValue(new Error('Network error'));

      renderArticlePage();

      await waitFor(() => {
        expect(screen.getByText(/failed to load article/i)).toBeInTheDocument();
      });
    });

    it('should show not found message when article does not exist', async () => {
      mockApiService.articles.getById.mockResolvedValue({
        success: false,
        message: 'Article not found'
      });

      renderArticlePage();

      await waitFor(() => {
        expect(screen.getByText(/article not found/i)).toBeInTheDocument();
      });
    });

    it('should show return to homepage link on error', async () => {
      mockApiService.articles.getById.mockRejectedValue(new Error('Network error'));

      renderArticlePage();

      await waitFor(() => {
        const homeLink = screen.getByText(/return to homepage/i);
        expect(homeLink).toBeInTheDocument();
        expect(homeLink.closest('a')).toHaveAttribute('href', '/');
      });
    });
  });

  describe('SEO and Accessibility', () => {
    beforeEach(() => {
      mockApiService.articles.getById.mockResolvedValue({
        success: true,
        data: mockArticle,
        message: 'Success'
      });
    });

    it('should have proper heading hierarchy', async () => {
      renderArticlePage();

      await waitFor(() => {
        const h1 = screen.getByRole('heading', { level: 1 });
        expect(h1).toHaveTextContent('Test Article Title');
        
        const h2 = screen.getByRole('heading', { level: 2, name: /test heading/i });
        expect(h2).toBeInTheDocument();
      });
    });

    it('should have proper alt text for images', async () => {
      renderArticlePage();

      await waitFor(() => {
        const authorAvatar = screen.getByAltText('testuser');
        expect(authorAvatar).toBeInTheDocument();
        
        const featuredImage = screen.getByAltText('Test Article Title');
        expect(featuredImage).toBeInTheDocument();
      });
    });

    it('should have proper ARIA labels for interactive elements', async () => {
      renderArticlePage();

      await waitFor(() => {
        expect(screen.getByLabelText('Share on Twitter')).toBeInTheDocument();
        expect(screen.getByLabelText('Share on Facebook')).toBeInTheDocument();
        expect(screen.getByLabelText('Share on LinkedIn')).toBeInTheDocument();
        expect(screen.getByLabelText('Copy link')).toBeInTheDocument();
      });
    });
  });
});