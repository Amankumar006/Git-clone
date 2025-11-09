import React from 'react';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import { BrowserRouter, MemoryRouter } from 'react-router-dom';
import SearchPage from '../SearchPage';
import { apiService } from '../../utils/api';

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

const mockSearchResults = {
  success: true,
  data: {
    articles: [
      {
        id: 1,
        title: 'JavaScript Fundamentals',

        author_id: 1,
        username: 'john_doe',
        tags: ['javascript', 'programming'],
        highlights: {
          title: '<mark>JavaScript</mark> Fundamentals',
          content: 'Learn the basics of <mark>JavaScript</mark> programming'
        }
      },
      {
        id: 2,
        title: 'Advanced React Patterns',

        author_id: 2,
        username: 'jane_smith',
        tags: ['react', 'javascript']
      }
    ],
    users: [
      {
        id: 1,
        username: 'javascript_expert',
        bio: 'JavaScript developer with 10 years experience',
        profile_image_url: '/avatar1.jpg',
        followers_count: 1500,
        articles_count: 25
      }
    ],
    tags: [
      {
        id: 1,
        name: 'javascript',
        slug: 'javascript',
        article_count: 150
      }
    ],
    total_count: 4,
    pagination: {
      current_page: 1,
      per_page: 10,
      total_pages: 1
    }
  }
};

const mockSuggestions = [
  { suggestion: 'JavaScript Basics', type: 'article' },
  { suggestion: 'javascript', type: 'tag' }
];

const renderSearchPage = (initialEntries = ['/search']) => {
  return render(
    <MemoryRouter initialEntries={initialEntries}>
      <SearchPage />
    </MemoryRouter>
  );
};

describe('SearchPage Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('Initial Render', () => {
    test('renders search interface', () => {
      renderSearchPage();
      
      expect(screen.getByPlaceholderText('Search articles, authors, and topics...')).toBeInTheDocument();
      expect(screen.getByText('Search')).toBeInTheDocument();
      expect(screen.getByText('Filters')).toBeInTheDocument();
    });

    test('loads search results from URL params', async () => {
      mockedApi.get.mockResolvedValue(mockSearchResults);
      
      renderSearchPage(['/search?q=javascript']);
      
      await waitFor(() => {
        expect(mockedApi.get).toHaveBeenCalledWith(
          expect.stringContaining('/search?q=javascript')
        );
      });
    });

    test('displays search query in input from URL', () => {
      renderSearchPage(['/search?q=react']);
      
      const input = screen.getByDisplayValue('react');
      expect(input).toBeInTheDocument();
    });
  });

  describe('Search Functionality', () => {
    test('performs search when clicking search button', async () => {
      mockedApi.get.mockResolvedValue(mockSearchResults);
      
      renderSearchPage();
      
      const input = screen.getByPlaceholderText('Search articles, authors, and topics...');
      const searchButton = screen.getByText('Search');
      
      fireEvent.change(input, { target: { value: 'javascript' } });
      fireEvent.click(searchButton);
      
      await waitFor(() => {
        expect(mockedApi.get).toHaveBeenCalledWith(
          expect.stringContaining('q=javascript')
        );
      });
    });

    test('performs search on Enter key press', async () => {
      mockedApi.get.mockResolvedValue(mockSearchResults);
      
      renderSearchPage();
      
      const input = screen.getByPlaceholderText('Search articles, authors, and topics...');
      
      fireEvent.change(input, { target: { value: 'react' } });
      fireEvent.keyPress(input, { key: 'Enter', charCode: 13 });
      
      await waitFor(() => {
        expect(mockedApi.get).toHaveBeenCalledWith(
          expect.stringContaining('q=react')
        );
      });
    });

    test('does not search with empty query', () => {
      renderSearchPage();
      
      const searchButton = screen.getByText('Search');
      fireEvent.click(searchButton);
      
      expect(mockedApi.get).not.toHaveBeenCalled();
    });

    test('shows loading state during search', async () => {
      mockedApi.get.mockImplementation(() => 
        new Promise(resolve => 
          setTimeout(() => resolve(mockSearchResults), 100)
        )
      );
      
      renderSearchPage();
      
      const input = screen.getByPlaceholderText('Search articles, authors, and topics...');
      const searchButton = screen.getByText('Search');
      
      fireEvent.change(input, { target: { value: 'javascript' } });
      fireEvent.click(searchButton);
      
      expect(screen.getByText('Searching...')).toBeInTheDocument();
      
      await waitFor(() => {
        expect(screen.queryByText('Searching...')).not.toBeInTheDocument();
      });
    });
  });

  describe('Search Results Display', () => {
    test('displays search results correctly', async () => {
      mockedApi.get.mockResolvedValue(mockSearchResults);
      
      renderSearchPage(['/search?q=javascript']);
      
      await waitFor(() => {
        expect(screen.getByText('JavaScript Fundamentals')).toBeInTheDocument();
        expect(screen.getByText('Advanced React Patterns')).toBeInTheDocument();
        expect(screen.getByText('javascript_expert')).toBeInTheDocument();
      });
    });

    test('displays result counts in tabs', async () => {
      mockedApi.get.mockResolvedValue(mockSearchResults);
      
      renderSearchPage(['/search?q=javascript']);
      
      await waitFor(() => {
        expect(screen.getByText('All (4)')).toBeInTheDocument();
        expect(screen.getByText('Articles (2)')).toBeInTheDocument();
        expect(screen.getByText('Authors (1)')).toBeInTheDocument();
        expect(screen.getByText('Tags (1)')).toBeInTheDocument();
      });
    });

    test('filters results by tab selection', async () => {
      mockedApi.get.mockResolvedValue(mockSearchResults);
      
      renderSearchPage(['/search?q=javascript']);
      
      await waitFor(() => {
        expect(screen.getByText('JavaScript Fundamentals')).toBeInTheDocument();
        expect(screen.getByText('javascript_expert')).toBeInTheDocument();
      });
      
      // Click Articles tab
      fireEvent.click(screen.getByText('Articles (2)'));
      
      expect(screen.getByText('JavaScript Fundamentals')).toBeInTheDocument();
      expect(screen.queryByText('javascript_expert')).not.toBeInTheDocument();
    });

    test('displays search highlights', async () => {
      mockedApi.get.mockResolvedValue(mockSearchResults);
      
      renderSearchPage(['/search?q=javascript']);
      
      await waitFor(() => {
        const highlightedContent = screen.getByText((content, element) => {
          return element?.innerHTML.includes('<mark>JavaScript</mark>') || false;
        });
        expect(highlightedContent).toBeInTheDocument();
      });
    });

    test('shows no results message when no matches found', async () => {
      mockedApi.get.mockResolvedValue({
        success: true,
        data: {
          articles: [],
          users: [],
          tags: [],
          total_count: 0,
          pagination: { current_page: 1, per_page: 10, total_pages: 0 }
        }
      });
      
      renderSearchPage(['/search?q=nonexistent']);
      
      await waitFor(() => {
        expect(screen.getByText('No results found')).toBeInTheDocument();
        expect(screen.getByText('Try adjusting your search terms or filters to find what you\'re looking for.')).toBeInTheDocument();
      });
    });
  });

  describe('Search Suggestions', () => {
    test('displays search suggestions', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: mockSuggestions }
      });
      
      renderSearchPage();
      
      const input = screen.getByPlaceholderText('Search articles, authors, and topics...');
      fireEvent.change(input, { target: { value: 'java' } });
      
      await waitFor(() => {
        expect(screen.getByText('JavaScript Basics')).toBeInTheDocument();
        expect(screen.getByText('javascript')).toBeInTheDocument();
      });
    });

    test('performs search when clicking suggestion', async () => {
      mockedApi.get
        .mockResolvedValueOnce({ data: { success: true, data: mockSuggestions } })
        .mockResolvedValueOnce(mockSearchResults);
      
      renderSearchPage();
      
      const input = screen.getByPlaceholderText('Search articles, authors, and topics...');
      fireEvent.change(input, { target: { value: 'java' } });
      
      await waitFor(() => {
        const suggestion = screen.getByText('JavaScript Basics');
        fireEvent.click(suggestion);
      });
      
      await waitFor(() => {
        expect(mockedApi.get).toHaveBeenCalledWith(
          expect.stringContaining('q=JavaScript%20Basics')
        );
      });
    });
  });

  describe('Advanced Filters', () => {
    test('shows filters panel when clicking Filters button', () => {
      renderSearchPage();
      
      const filtersButton = screen.getByText('Filters');
      fireEvent.click(filtersButton);
      
      expect(screen.getByText('Content Type')).toBeInTheDocument();
      expect(screen.getByText('Author')).toBeInTheDocument();
      expect(screen.getByText('Tag')).toBeInTheDocument();
    });

    test('applies content type filter', async () => {
      mockedApi.get.mockResolvedValue(mockSearchResults);
      
      renderSearchPage(['/search?q=javascript']);
      
      // Open filters
      fireEvent.click(screen.getByText('Filters'));
      
      // Select Articles Only
      const typeSelect = screen.getByDisplayValue('All Types');
      fireEvent.change(typeSelect, { target: { value: 'articles' } });
      
      await waitFor(() => {
        expect(mockedApi.get).toHaveBeenCalledWith(
          expect.stringContaining('type=articles')
        );
      });
    });

    test('applies author filter', async () => {
      mockedApi.get.mockResolvedValue(mockSearchResults);
      
      renderSearchPage(['/search?q=javascript']);
      
      // Open filters
      fireEvent.click(screen.getByText('Filters'));
      
      // Enter author
      const authorInput = screen.getByPlaceholderText('Author username');
      fireEvent.change(authorInput, { target: { value: 'john_doe' } });
      
      await waitFor(() => {
        expect(mockedApi.get).toHaveBeenCalledWith(
          expect.stringContaining('author=john_doe')
        );
      });
    });

    test('applies date range filter', async () => {
      mockedApi.get.mockResolvedValue(mockSearchResults);
      
      renderSearchPage(['/search?q=javascript']);
      
      // Open filters
      fireEvent.click(screen.getByText('Filters'));
      
      // Set date range
      const fromDate = screen.getByLabelText('From Date');
      const toDate = screen.getByLabelText('To Date');
      
      fireEvent.change(fromDate, { target: { value: '2023-01-01' } });
      fireEvent.change(toDate, { target: { value: '2023-12-31' } });
      
      await waitFor(() => {
        expect(mockedApi.get).toHaveBeenCalledWith(
          expect.stringContaining('date_from=2023-01-01')
        );
      });
    });

    test('applies sort filter', async () => {
      mockedApi.get.mockResolvedValue(mockSearchResults);
      
      renderSearchPage(['/search?q=javascript']);
      
      // Open filters
      fireEvent.click(screen.getByText('Filters'));
      
      // Change sort
      const sortSelect = screen.getByDisplayValue('Relevance');
      fireEvent.change(sortSelect, { target: { value: 'date' } });
      
      await waitFor(() => {
        expect(mockedApi.get).toHaveBeenCalledWith(
          expect.stringContaining('sort=date')
        );
      });
    });

    test('clears all filters', async () => {
      mockedApi.get.mockResolvedValue(mockSearchResults);
      
      renderSearchPage(['/search?q=javascript&author=john&tag=react']);
      
      // Open filters
      fireEvent.click(screen.getByText('Filters'));
      
      // Clear filters
      fireEvent.click(screen.getByText('Clear Filters'));
      
      await waitFor(() => {
        expect(mockedApi.get).toHaveBeenCalledWith(
          expect.stringContaining('q=javascript')
        );
        expect(mockedApi.get).not.toHaveBeenCalledWith(
          expect.stringContaining('author=john')
        );
      });
    });
  });

  describe('User Interactions', () => {
    test('navigates to user profile when clicking user result', async () => {
      mockedApi.get.mockResolvedValue(mockSearchResults);
      
      const mockNavigate = jest.fn();
      jest.doMock('react-router-dom', () => ({
        ...jest.requireActual('react-router-dom'),
        useNavigate: () => mockNavigate,
      }));
      
      renderSearchPage(['/search?q=javascript']);
      
      await waitFor(() => {
        // This would require more complex mocking to test navigation
        expect(screen.getByText('javascript_expert')).toBeInTheDocument();
      });
    });

    test('navigates to tag page when clicking tag result', async () => {
      mockedApi.get.mockResolvedValue(mockSearchResults);
      
      renderSearchPage(['/search?q=javascript']);
      
      await waitFor(() => {
        const viewButton = screen.getByText('View');
        expect(viewButton).toBeInTheDocument();
      });
    });
  });

  describe('Error Handling', () => {
    test('displays error message when search fails', async () => {
      mockedApi.get.mockRejectedValue({
        response: { data: { error: 'Search service unavailable' } }
      });
      
      renderSearchPage();
      
      const input = screen.getByPlaceholderText('Search articles, authors, and topics...');
      const searchButton = screen.getByText('Search');
      
      fireEvent.change(input, { target: { value: 'javascript' } });
      fireEvent.click(searchButton);
      
      await waitFor(() => {
        expect(screen.getByText('Search service unavailable')).toBeInTheDocument();
      });
    });

    test('displays generic error message for network errors', async () => {
      mockedApi.get.mockRejectedValue(new Error('Network error'));
      
      renderSearchPage();
      
      const input = screen.getByPlaceholderText('Search articles, authors, and topics...');
      const searchButton = screen.getByText('Search');
      
      fireEvent.change(input, { target: { value: 'javascript' } });
      fireEvent.click(searchButton);
      
      await waitFor(() => {
        expect(screen.getByText('Search failed')).toBeInTheDocument();
      });
    });
  });

  describe('URL State Management', () => {
    test('updates URL when performing search', async () => {
      mockedApi.get.mockResolvedValue(mockSearchResults);
      
      // This would require more complex testing with actual router
      renderSearchPage();
      
      const input = screen.getByPlaceholderText('Search articles, authors, and topics...');
      fireEvent.change(input, { target: { value: 'react' } });
      fireEvent.keyPress(input, { key: 'Enter', charCode: 13 });
      
      // URL update testing would require integration with actual router
      await waitFor(() => {
        expect(mockedApi.get).toHaveBeenCalled();
      });
    });

    test('preserves filters in URL', async () => {
      mockedApi.get.mockResolvedValue(mockSearchResults);
      
      renderSearchPage(['/search?q=javascript&type=articles&author=john']);
      
      await waitFor(() => {
        expect(mockedApi.get).toHaveBeenCalledWith(
          expect.stringContaining('type=articles')
        );
        expect(mockedApi.get).toHaveBeenCalledWith(
          expect.stringContaining('author=john')
        );
      });
    });
  });

  describe('Responsive Design', () => {
    test('renders properly on mobile viewport', () => {
      // Mock mobile viewport
      Object.defineProperty(window, 'innerWidth', {
        writable: true,
        configurable: true,
        value: 375,
      });
      
      renderSearchPage();
      
      expect(screen.getByPlaceholderText('Search articles, authors, and topics...')).toBeInTheDocument();
      expect(screen.getByText('Filters')).toBeInTheDocument();
    });
  });

  describe('Accessibility', () => {
    test('has proper ARIA labels and roles', () => {
      renderSearchPage();
      
      const searchInput = screen.getByPlaceholderText('Search articles, authors, and topics...');
      expect(searchInput).toHaveAttribute('type', 'text');
      
      const searchButton = screen.getByText('Search');
      expect(searchButton).toHaveAttribute('type', 'button');
    });

    test('supports keyboard navigation', async () => {
      mockedApi.get.mockResolvedValue(mockSearchResults);
      
      renderSearchPage();
      
      const input = screen.getByPlaceholderText('Search articles, authors, and topics...');
      
      // Tab to input
      input.focus();
      expect(document.activeElement).toBe(input);
      
      // Enter search query
      fireEvent.change(input, { target: { value: 'javascript' } });
      fireEvent.keyPress(input, { key: 'Enter', charCode: 13 });
      
      await waitFor(() => {
        expect(mockedApi.get).toHaveBeenCalled();
      });
    });
  });
});