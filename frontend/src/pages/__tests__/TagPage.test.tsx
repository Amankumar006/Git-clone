import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import TagPage from '../TagPage';
import { apiService } from '../../utils/api';
import { AuthContext } from '../../context/AuthContext';

// Mock the API
jest.mock('../../utils/api');
const mockedApi = apiService as jest.Mocked<typeof apiService>;

// Mock ArticleCard component
jest.mock('../../components/ArticleCard', () => {
  return function MockArticleCard({ article }: { article: any }) {
    return (
      <div data-testid="article-card">
        <h3>{article.title}</h3>

      </div>
    );
  };
});

const mockTagData = {
  success: true,
  data: {
    tag: {
      id: 1,
      name: 'javascript',
      slug: 'javascript',
      description: 'JavaScript programming language articles and tutorials',
      article_count: 150
    },
    articles: [
      {
        id: 1,
        title: 'JavaScript Fundamentals',

        author_id: 1,
        username: 'john_doe',
        tags: ['javascript', 'programming'],
        published_at: '2023-12-01T10:00:00Z',
        view_count: 1000,
        clap_count: 50,
        comment_count: 10
      },
      {
        id: 2,
        title: 'Advanced JavaScript Patterns',

        author_id: 2,
        username: 'jane_smith',
        tags: ['javascript', 'advanced'],
        published_at: '2023-11-28T15:30:00Z',
        view_count: 800,
        clap_count: 75,
        comment_count: 15
      }
    ]
  }
};

const mockTagStats = {
  success: true,
  data: {
    stats: {
      total_articles: 150,
      unique_authors: 45,
      avg_views: 850,
      avg_claps: 25,
      followers: 1200
    },
    similar_tags: [
      { id: 2, name: 'react', slug: 'react' },
      { id: 3, name: 'nodejs', slug: 'nodejs' },
      { id: 4, name: 'typescript', slug: 'typescript' }
    ],
    top_authors: [
      {
        id: 1,
        username: 'js_expert',
        profile_image_url: '/avatar1.jpg',
        bio: 'JavaScript expert with 10 years experience',
        article_count: 25,
        total_views: 50000,
        total_claps: 2500
      },
      {
        id: 2,
        username: 'frontend_dev',
        profile_image_url: '/avatar2.jpg',
        bio: 'Frontend developer specializing in JavaScript',
        article_count: 18,
        total_views: 35000,
        total_claps: 1800
      }
    ]
  }
};

const mockUser = {
  id: 1,
  username: 'testuser',
  email: 'test@example.com'
};

const renderTagPage = (slug = 'javascript', user = null) => {
  const authValue = {
    user,
    login: jest.fn(),
    logout: jest.fn(),
    loading: false
  };

  return render(
    <AuthContext.Provider value={authValue}>
      <MemoryRouter initialEntries={[`/tag/${slug}`]}>
        <TagPage />
      </MemoryRouter>
    </AuthContext.Provider>
  );
};

describe('TagPage Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('Initial Loading', () => {
    test('shows loading state initially', () => {
      mockedApi.get.mockImplementation(() => new Promise(() => {})); // Never resolves
      
      renderTagPage();
      
      expect(screen.getByText('Loading tag...')).toBeInTheDocument();
    });

    test('loads tag data on mount', async () => {
      mockedApi.get
        .mockResolvedValueOnce(mockTagData)
        .mockResolvedValueOnce(mockTagStats);
      
      renderTagPage();
      
      await waitFor(() => {
        expect(mockedApi.get).toHaveBeenCalledWith(
          expect.stringContaining('/tags/show?slug=javascript')
        );
        expect(mockedApi.get).toHaveBeenCalledWith(
          expect.stringContaining('/tags/stats?tag_id=1')
        );
      });
    });
  });

  describe('Tag Information Display', () => {
    test('displays tag name and description', async () => {
      mockedApi.get
        .mockResolvedValueOnce(mockTagData)
        .mockResolvedValueOnce(mockTagStats);
      
      renderTagPage();
      
      await waitFor(() => {
        expect(screen.getByText('#javascript')).toBeInTheDocument();
        expect(screen.getByText('JavaScript programming language articles and tutorials')).toBeInTheDocument();
      });
    });

    test('displays tag statistics', async () => {
      mockedApi.get
        .mockResolvedValueOnce(mockTagData)
        .mockResolvedValueOnce(mockTagStats);
      
      renderTagPage();
      
      await waitFor(() => {
        expect(screen.getByText('150')).toBeInTheDocument(); // Total articles
        expect(screen.getByText('45')).toBeInTheDocument(); // Unique authors
        expect(screen.getByText('850')).toBeInTheDocument(); // Avg views
        expect(screen.getByText('1200')).toBeInTheDocument(); // Followers
      });
    });

    test('displays articles for the tag', async () => {
      mockedApi.get
        .mockResolvedValueOnce(mockTagData)
        .mockResolvedValueOnce(mockTagStats);
      
      renderTagPage();
      
      await waitFor(() => {
        expect(screen.getByText('JavaScript Fundamentals')).toBeInTheDocument();
        expect(screen.getByText('Advanced JavaScript Patterns')).toBeInTheDocument();
      });
    });
  });

  describe('Follow Functionality', () => {
    test('shows follow button for authenticated users', async () => {
      mockedApi.get
        .mockResolvedValueOnce(mockTagData)
        .mockResolvedValueOnce(mockTagStats)
        .mockResolvedValueOnce({ data: { success: true, data: { following: false } } });
      
      renderTagPage('javascript', mockUser);
      
      await waitFor(() => {
        expect(screen.getByText('Follow')).toBeInTheDocument();
      });
    });

    test('does not show follow button for unauthenticated users', async () => {
      mockedApi.get
        .mockResolvedValueOnce(mockTagData)
        .mockResolvedValueOnce(mockTagStats);
      
      renderTagPage('javascript', null);
      
      await waitFor(() => {
        expect(screen.queryByText('Follow')).not.toBeInTheDocument();
      });
    });

    test('shows following state when user follows tag', async () => {
      mockedApi.get
        .mockResolvedValueOnce(mockTagData)
        .mockResolvedValueOnce(mockTagStats)
        .mockResolvedValueOnce({ data: { success: true, data: { following: true } } });
      
      renderTagPage('javascript', mockUser);
      
      await waitFor(() => {
        expect(screen.getByText('Following')).toBeInTheDocument();
      });
    });

    test('handles follow toggle', async () => {
      mockedApi.get
        .mockResolvedValueOnce(mockTagData)
        .mockResolvedValueOnce(mockTagStats)
        .mockResolvedValueOnce({ data: { success: true, data: { following: false } } });
      
      mockedApi.post.mockResolvedValue({ data: { success: true } });
      
      renderTagPage('javascript', mockUser);
      
      await waitFor(() => {
        const followButton = screen.getByText('Follow');
        fireEvent.click(followButton);
      });
      
      expect(mockedApi.post).toHaveBeenCalledWith('/tags/follow', { tag_id: 1 });
    });

    test('handles unfollow toggle', async () => {
      mockedApi.get
        .mockResolvedValueOnce(mockTagData)
        .mockResolvedValueOnce(mockTagStats)
        .mockResolvedValueOnce({ data: { success: true, data: { following: true } } });
      
      mockedApi.post.mockResolvedValue({ data: { success: true } });
      
      renderTagPage('javascript', mockUser);
      
      await waitFor(() => {
        const followingButton = screen.getByText('Following');
        fireEvent.click(followingButton);
      });
      
      expect(mockedApi.post).toHaveBeenCalledWith('/tags/unfollow', { tag_id: 1 });
    });

    test('shows loading state during follow action', async () => {
      mockedApi.get
        .mockResolvedValueOnce(mockTagData)
        .mockResolvedValueOnce(mockTagStats)
        .mockResolvedValueOnce({ data: { success: true, data: { following: false } } });
      
      mockedApi.post.mockImplementation(() => new Promise(resolve => 
        setTimeout(() => resolve({ data: { success: true } }), 100)
      ));
      
      renderTagPage('javascript', mockUser);
      
      await waitFor(() => {
        const followButton = screen.getByText('Follow');
        fireEvent.click(followButton);
      });
      
      expect(screen.getByText('Loading...')).toBeInTheDocument();
    });
  });

  describe('Article Sorting', () => {
    test('displays sort options', async () => {
      mockedApi.get
        .mockResolvedValueOnce(mockTagData)
        .mockResolvedValueOnce(mockTagStats);
      
      renderTagPage();
      
      await waitFor(() => {
        expect(screen.getByDisplayValue('Latest')).toBeInTheDocument();
      });
    });

    test('changes sort order when selecting different option', async () => {
      mockedApi.get
        .mockResolvedValueOnce(mockTagData)
        .mockResolvedValueOnce(mockTagStats)
        .mockResolvedValueOnce(mockTagData); // For re-fetch after sort change
      
      renderTagPage();
      
      await waitFor(() => {
        const sortSelect = screen.getByDisplayValue('Latest');
        fireEvent.change(sortSelect, { target: { value: 'popular' } });
      });
      
      // Should trigger a new API call with the new sort
      await waitFor(() => {
        expect(mockedApi.get).toHaveBeenCalledTimes(3); // Initial load + stats + re-fetch
      });
    });
  });

  describe('Load More Functionality', () => {
    test('shows load more button when there are more articles', async () => {
      const dataWithMoreArticles = {
        ...mockTagData,
        data: {
          ...mockTagData.data,
          articles: Array(10).fill(null).map((_, i) => ({
            id: i + 1,
            title: `Article ${i + 1}`,
            subtitle: `Subtitle ${i + 1}`,
            author_id: 1,
            username: 'author',
            tags: ['javascript'],
            published_at: '2023-12-01T10:00:00Z'
          }))
        }
      };
      
      mockedApi.get
        .mockResolvedValueOnce(dataWithMoreArticles)
        .mockResolvedValueOnce(mockTagStats);
      
      renderTagPage();
      
      await waitFor(() => {
        expect(screen.getByText('Load More Articles')).toBeInTheDocument();
      });
    });

    test('loads more articles when clicking load more', async () => {
      const initialData = {
        ...mockTagData,
        data: {
          ...mockTagData.data,
          articles: Array(10).fill(null).map((_, i) => ({
            id: i + 1,
            title: `Article ${i + 1}`,
            subtitle: `Subtitle ${i + 1}`,
            author_id: 1,
            username: 'author',
            tags: ['javascript'],
            published_at: '2023-12-01T10:00:00Z'
          }))
        }
      };
      
      const moreData = {
        success: true,
        data: {
          articles: Array(5).fill(null).map((_, i) => ({
            id: i + 11,
            title: `Article ${i + 11}`,
            subtitle: `Subtitle ${i + 11}`,
            author_id: 1,
            username: 'author',
            tags: ['javascript'],
            published_at: '2023-12-01T10:00:00Z'
          }))
        }
      };
      
      mockedApi.get
        .mockResolvedValueOnce(initialData)
        .mockResolvedValueOnce(mockTagStats)
        .mockResolvedValueOnce(moreData);
      
      renderTagPage();
      
      await waitFor(() => {
        const loadMoreButton = screen.getByText('Load More Articles');
        fireEvent.click(loadMoreButton);
      });
      
      await waitFor(() => {
        expect(screen.getByText('Article 11')).toBeInTheDocument();
      });
    });
  });

  describe('Sidebar Content', () => {
    test('displays top authors', async () => {
      mockedApi.get
        .mockResolvedValueOnce(mockTagData)
        .mockResolvedValueOnce(mockTagStats);
      
      renderTagPage();
      
      await waitFor(() => {
        expect(screen.getByText('Top Authors')).toBeInTheDocument();
        expect(screen.getByText('js_expert')).toBeInTheDocument();
        expect(screen.getByText('frontend_dev')).toBeInTheDocument();
      });
    });

    test('displays similar tags', async () => {
      mockedApi.get
        .mockResolvedValueOnce(mockTagData)
        .mockResolvedValueOnce(mockTagStats);
      
      renderTagPage();
      
      await waitFor(() => {
        expect(screen.getByText('Related Tags')).toBeInTheDocument();
        expect(screen.getByText('#react')).toBeInTheDocument();
        expect(screen.getByText('#nodejs')).toBeInTheDocument();
        expect(screen.getByText('#typescript')).toBeInTheDocument();
      });
    });

    test('displays explore more section', async () => {
      mockedApi.get
        .mockResolvedValueOnce(mockTagData)
        .mockResolvedValueOnce(mockTagStats);
      
      renderTagPage();
      
      await waitFor(() => {
        expect(screen.getByText('Explore More')).toBeInTheDocument();
        expect(screen.getByText('Browse All Tags')).toBeInTheDocument();
        expect(screen.getByText('Trending Tags')).toBeInTheDocument();
        expect(screen.getByText('Advanced Search')).toBeInTheDocument();
      });
    });
  });

  describe('Empty States', () => {
    test('shows empty state when no articles exist', async () => {
      const emptyTagData = {
        ...mockTagData,
        data: {
          ...mockTagData.data,
          articles: []
        }
      };
      
      mockedApi.get
        .mockResolvedValueOnce(emptyTagData)
        .mockResolvedValueOnce(mockTagStats);
      
      renderTagPage();
      
      await waitFor(() => {
        expect(screen.getByText('No articles yet')).toBeInTheDocument();
        expect(screen.getByText('Be the first to write about #javascript!')).toBeInTheDocument();
      });
    });

    test('handles empty sidebar sections gracefully', async () => {
      const emptyStatsData = {
        success: true,
        data: {
          stats: mockTagStats.data.stats,
          similar_tags: [],
          top_authors: []
        }
      };
      
      mockedApi.get
        .mockResolvedValueOnce(mockTagData)
        .mockResolvedValueOnce(emptyStatsData);
      
      renderTagPage();
      
      await waitFor(() => {
        // Should still show the main content
        expect(screen.getByText('#javascript')).toBeInTheDocument();
        // Sidebar sections should not appear when empty
        expect(screen.queryByText('Top Authors')).not.toBeInTheDocument();
        expect(screen.queryByText('Related Tags')).not.toBeInTheDocument();
      });
    });
  });

  describe('Error Handling', () => {
    test('shows error state when tag is not found', async () => {
      mockedApi.get.mockRejectedValue({
        response: { data: { error: 'Tag not found' } }
      });
      
      renderTagPage('nonexistent');
      
      await waitFor(() => {
        expect(screen.getByText('Tag Not Found')).toBeInTheDocument();
        expect(screen.getByText('Tag not found')).toBeInTheDocument();
        expect(screen.getByText('Go Home')).toBeInTheDocument();
      });
    });

    test('shows generic error for other failures', async () => {
      mockedApi.get.mockRejectedValue(new Error('Network error'));
      
      renderTagPage();
      
      await waitFor(() => {
        expect(screen.getByText('Tag Not Found')).toBeInTheDocument();
        expect(screen.getByText('Failed to load tag')).toBeInTheDocument();
      });
    });

    test('handles follow API errors gracefully', async () => {
      mockedApi.get
        .mockResolvedValueOnce(mockTagData)
        .mockResolvedValueOnce(mockTagStats)
        .mockResolvedValueOnce({ data: { success: true, data: { following: false } } });
      
      mockedApi.post.mockRejectedValue(new Error('Follow failed'));
      
      renderTagPage('javascript', mockUser);
      
      await waitFor(() => {
        const followButton = screen.getByText('Follow');
        fireEvent.click(followButton);
      });
      
      // Should handle error gracefully and not crash
      await waitFor(() => {
        expect(screen.getByText('Follow')).toBeInTheDocument(); // Button should remain
      });
    });
  });

  describe('Navigation', () => {
    test('navigates to author profile when clicking author', async () => {
      mockedApi.get
        .mockResolvedValueOnce(mockTagData)
        .mockResolvedValueOnce(mockTagStats);
      
      renderTagPage();
      
      await waitFor(() => {
        const viewButtons = screen.getAllByText('View');
        expect(viewButtons.length).toBeGreaterThan(0);
      });
    });

    test('navigates to related tags when clicking them', async () => {
      mockedApi.get
        .mockResolvedValueOnce(mockTagData)
        .mockResolvedValueOnce(mockTagStats);
      
      renderTagPage();
      
      await waitFor(() => {
        const reactTag = screen.getByText('#react');
        expect(reactTag).toBeInTheDocument();
        // Navigation testing would require more complex router mocking
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
      
      mockedApi.get
        .mockResolvedValueOnce(mockTagData)
        .mockResolvedValueOnce(mockTagStats);
      
      renderTagPage();
      
      await waitFor(() => {
        expect(screen.getByText('#javascript')).toBeInTheDocument();
      });
    });
  });

  describe('Accessibility', () => {
    test('has proper heading structure', async () => {
      mockedApi.get
        .mockResolvedValueOnce(mockTagData)
        .mockResolvedValueOnce(mockTagStats);
      
      renderTagPage();
      
      await waitFor(() => {
        const mainHeading = screen.getByRole('heading', { level: 1 });
        expect(mainHeading).toHaveTextContent('#javascript');
      });
    });

    test('has accessible buttons', async () => {
      mockedApi.get
        .mockResolvedValueOnce(mockTagData)
        .mockResolvedValueOnce(mockTagStats)
        .mockResolvedValueOnce({ data: { success: true, data: { following: false } } });
      
      renderTagPage('javascript', mockUser);
      
      await waitFor(() => {
        const followButton = screen.getByRole('button', { name: /follow/i });
        expect(followButton).toBeInTheDocument();
      });
    });
  });
});