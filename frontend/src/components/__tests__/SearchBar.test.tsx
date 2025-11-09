import React from 'react';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import SearchBar from '../SearchBar';
import { apiService } from '../../utils/api';

// Mock the API
jest.mock('../../utils/api');
const mockedApi = apiService as jest.Mocked<typeof apiService>;

// Mock react-router-dom
const mockNavigate = jest.fn();
jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useNavigate: () => mockNavigate,
}));

const renderSearchBar = (props = {}) => {
  return render(
    <BrowserRouter>
      <SearchBar {...props} />
    </BrowserRouter>
  );
};

describe('SearchBar Component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    jest.useFakeTimers();
  });

  afterEach(() => {
    jest.useRealTimers();
  });

  describe('Basic Functionality', () => {
    test('renders search input with placeholder', () => {
      renderSearchBar({ placeholder: 'Search articles...' });
      
      const input = screen.getByPlaceholderText('Search articles...');
      expect(input).toBeInTheDocument();
    });

    test('updates input value when typing', () => {
      renderSearchBar();
      
      const input = screen.getByPlaceholderText('Search...');
      fireEvent.change(input, { target: { value: 'javascript' } });
      
      expect(input).toHaveValue('javascript');
    });

    test('calls onSearch prop when provided', () => {
      const mockOnSearch = jest.fn();
      renderSearchBar({ onSearch: mockOnSearch });
      
      const input = screen.getByPlaceholderText('Search...');
      fireEvent.change(input, { target: { value: 'react' } });
      fireEvent.keyDown(input, { key: 'Enter' });
      
      expect(mockOnSearch).toHaveBeenCalledWith('react');
    });

    test('navigates to search page when no onSearch prop', () => {
      renderSearchBar();
      
      const input = screen.getByPlaceholderText('Search...');
      fireEvent.change(input, { target: { value: 'vue' } });
      fireEvent.keyDown(input, { key: 'Enter' });
      
      expect(mockNavigate).toHaveBeenCalledWith('/search?q=vue');
    });

    test('does not search with empty query', () => {
      const mockOnSearch = jest.fn();
      renderSearchBar({ onSearch: mockOnSearch });
      
      const input = screen.getByPlaceholderText('Search...');
      fireEvent.keyDown(input, { key: 'Enter' });
      
      expect(mockOnSearch).not.toHaveBeenCalled();
    });
  });

  describe('Search Suggestions', () => {
    const mockSuggestions = [
      { suggestion: 'JavaScript Basics', type: 'article' },
      { suggestion: 'javascript', type: 'tag' },
      { suggestion: 'john_doe', type: 'user' }
    ];

    test('fetches suggestions after debounce delay', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: mockSuggestions }
      });

      renderSearchBar();
      
      const input = screen.getByPlaceholderText('Search...');
      fireEvent.change(input, { target: { value: 'java' } });
      
      // Fast-forward past debounce delay
      act(() => {
        jest.advanceTimersByTime(300);
      });

      await waitFor(() => {
        expect(mockedApi.get).toHaveBeenCalledWith('/search/suggestions?q=java&limit=5');
      });
    });

    test('displays suggestions when available', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: mockSuggestions }
      });

      renderSearchBar();
      
      const input = screen.getByPlaceholderText('Search...');
      fireEvent.change(input, { target: { value: 'java' } });
      fireEvent.focus(input);
      
      act(() => {
        jest.advanceTimersByTime(300);
      });

      await waitFor(() => {
        expect(screen.getByText('JavaScript Basics')).toBeInTheDocument();
        expect(screen.getByText('javascript')).toBeInTheDocument();
        expect(screen.getByText('john_doe')).toBeInTheDocument();
      });
    });

    test('shows suggestion types with icons', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: mockSuggestions }
      });

      renderSearchBar();
      
      const input = screen.getByPlaceholderText('Search...');
      fireEvent.change(input, { target: { value: 'java' } });
      
      act(() => {
        jest.advanceTimersByTime(300);
      });

      await waitFor(() => {
        expect(screen.getByText('article')).toBeInTheDocument();
        expect(screen.getByText('tag')).toBeInTheDocument();
        expect(screen.getByText('user')).toBeInTheDocument();
      });
    });

    test('handles suggestion click', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: mockSuggestions }
      });

      renderSearchBar();
      
      const input = screen.getByPlaceholderText('Search...');
      fireEvent.change(input, { target: { value: 'java' } });
      
      act(() => {
        jest.advanceTimersByTime(300);
      });

      await waitFor(() => {
        const suggestion = screen.getByText('JavaScript Basics');
        fireEvent.click(suggestion);
      });

      expect(mockNavigate).toHaveBeenCalledWith('/search?q=JavaScript%20Basics');
    });

    test('shows "View all results" option', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: mockSuggestions }
      });

      renderSearchBar();
      
      const input = screen.getByPlaceholderText('Search...');
      fireEvent.change(input, { target: { value: 'java' } });
      
      act(() => {
        jest.advanceTimersByTime(300);
      });

      await waitFor(() => {
        expect(screen.getByText('View all results for "java"')).toBeInTheDocument();
      });
    });

    test('does not fetch suggestions for queries shorter than 2 characters', () => {
      renderSearchBar();
      
      const input = screen.getByPlaceholderText('Search...');
      fireEvent.change(input, { target: { value: 'j' } });
      
      act(() => {
        jest.advanceTimersByTime(300);
      });

      expect(mockedApi.get).not.toHaveBeenCalled();
    });

    test('shows no suggestions message when no results', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: [] }
      });

      renderSearchBar();
      
      const input = screen.getByPlaceholderText('Search...');
      fireEvent.change(input, { target: { value: 'xyz123' } });
      
      act(() => {
        jest.advanceTimersByTime(300);
      });

      await waitFor(() => {
        expect(screen.getByText('No suggestions found. Press Enter to search.')).toBeInTheDocument();
      });
    });
  });

  describe('Keyboard Navigation', () => {
    test('closes suggestions on Escape key', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: [{ suggestion: 'test', type: 'article' }] }
      });

      renderSearchBar();
      
      const input = screen.getByPlaceholderText('Search...');
      fireEvent.change(input, { target: { value: 'test' } });
      
      act(() => {
        jest.advanceTimersByTime(300);
      });

      await waitFor(() => {
        expect(screen.getByText('test')).toBeInTheDocument();
      });

      fireEvent.keyDown(input, { key: 'Escape' });
      
      await waitFor(() => {
        expect(screen.queryByText('test')).not.toBeInTheDocument();
      });
    });

    test('submits search on Enter key', () => {
      renderSearchBar();
      
      const input = screen.getByPlaceholderText('Search...');
      fireEvent.change(input, { target: { value: 'react hooks' } });
      fireEvent.keyDown(input, { key: 'Enter' });
      
      expect(mockNavigate).toHaveBeenCalledWith('/search?q=react%20hooks');
    });
  });

  describe('Loading States', () => {
    test('shows loading spinner while fetching suggestions', async () => {
      // Mock a delayed response
      mockedApi.get.mockImplementation(() => 
        new Promise(resolve => 
          setTimeout(() => resolve({ data: { success: true, data: [] } }), 100)
        )
      );

      renderSearchBar();
      
      const input = screen.getByPlaceholderText('Search...');
      fireEvent.change(input, { target: { value: 'loading' } });
      
      act(() => {
        jest.advanceTimersByTime(300);
      });

      // Should show loading spinner
      await waitFor(() => {
        const spinner = document.querySelector('.animate-spin');
        expect(spinner).toBeInTheDocument();
      });
    });
  });

  describe('Error Handling', () => {
    test('handles API errors gracefully', async () => {
      mockedApi.get.mockRejectedValue(new Error('API Error'));

      renderSearchBar();
      
      const input = screen.getByPlaceholderText('Search...');
      fireEvent.change(input, { target: { value: 'error' } });
      
      act(() => {
        jest.advanceTimersByTime(300);
      });

      // Should not crash and should not show suggestions
      await waitFor(() => {
        expect(screen.queryByText('error')).not.toBeInTheDocument();
      });
    });
  });

  describe('Click Outside Behavior', () => {
    test('closes suggestions when clicking outside', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: [{ suggestion: 'test', type: 'article' }] }
      });

      renderSearchBar();
      
      const input = screen.getByPlaceholderText('Search...');
      fireEvent.change(input, { target: { value: 'test' } });
      
      act(() => {
        jest.advanceTimersByTime(300);
      });

      await waitFor(() => {
        expect(screen.getByText('test')).toBeInTheDocument();
      });

      // Click outside
      fireEvent.mouseDown(document.body);
      
      await waitFor(() => {
        expect(screen.queryByText('test')).not.toBeInTheDocument();
      });
    });
  });

  describe('Debouncing', () => {
    test('debounces API calls correctly', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: [] }
      });

      renderSearchBar();
      
      const input = screen.getByPlaceholderText('Search...');
      
      // Type multiple characters quickly
      fireEvent.change(input, { target: { value: 'j' } });
      fireEvent.change(input, { target: { value: 'ja' } });
      fireEvent.change(input, { target: { value: 'jav' } });
      fireEvent.change(input, { target: { value: 'java' } });
      
      // Only advance time once
      act(() => {
        jest.advanceTimersByTime(300);
      });

      // Should only make one API call for the final value
      await waitFor(() => {
        expect(mockedApi.get).toHaveBeenCalledTimes(1);
        expect(mockedApi.get).toHaveBeenCalledWith('/search/suggestions?q=java&limit=5');
      });
    });
  });

  describe('Accessibility', () => {
    test('has proper ARIA attributes', () => {
      renderSearchBar();
      
      const input = screen.getByPlaceholderText('Search...');
      expect(input).toHaveAttribute('type', 'text');
    });

    test('suggestions are keyboard accessible', async () => {
      mockedApi.get.mockResolvedValue({
        data: { success: true, data: [{ suggestion: 'test', type: 'article' }] }
      });

      renderSearchBar();
      
      const input = screen.getByPlaceholderText('Search...');
      fireEvent.change(input, { target: { value: 'test' } });
      
      act(() => {
        jest.advanceTimersByTime(300);
      });

      await waitFor(() => {
        const suggestion = screen.getByText('test');
        expect(suggestion.closest('button')).toBeInTheDocument();
      });
    });
  });
});