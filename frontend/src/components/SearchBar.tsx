import React, { useState, useEffect, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import { apiService } from '../utils/api';

interface SearchSuggestion {
  suggestion: string;
  type: 'article' | 'tag' | 'user';
}

interface SearchBarProps {
  className?: string;
  placeholder?: string;
  onSearch?: (query: string) => void;
}

const SearchBar: React.FC<SearchBarProps> = ({
  className = '',
  placeholder = 'Search...',
  onSearch
}) => {
  const navigate = useNavigate();
  const [query, setQuery] = useState('');
  const [suggestions, setSuggestions] = useState<SearchSuggestion[]>([]);
  const [showSuggestions, setShowSuggestions] = useState(false);
  const [loading, setLoading] = useState(false);
  const searchRef = useRef<HTMLDivElement>(null);
  const inputRef = useRef<HTMLInputElement>(null);

  // Debounce timer
  const debounceTimer = useRef<NodeJS.Timeout>();

  // Get search suggestions
  const getSuggestions = async (searchQuery: string) => {
    if (searchQuery.length < 2) {
      setSuggestions([]);
      return;
    }

    setLoading(true);
    try {
      const response = await apiService.search.getSuggestions(searchQuery);
      if (response.data.success) {
        setSuggestions(response.data.data);
      }
    } catch (error) {
      console.error('Failed to get suggestions:', error);
      setSuggestions([]);
    } finally {
      setLoading(false);
    }
  };

  // Handle input change with debouncing
  const handleInputChange = (value: string) => {
    setQuery(value);
    
    // Clear previous timer
    if (debounceTimer.current) {
      clearTimeout(debounceTimer.current);
    }

    // Set new timer
    debounceTimer.current = setTimeout(() => {
      if (value.trim()) {
        getSuggestions(value);
        setShowSuggestions(true);
      } else {
        setSuggestions([]);
        setShowSuggestions(false);
      }
    }, 300);
  };

  // Handle search submission
  const handleSearch = (searchQuery?: string) => {
    const finalQuery = searchQuery || query;
    if (!finalQuery.trim()) return;

    setShowSuggestions(false);
    
    if (onSearch) {
      onSearch(finalQuery);
    } else {
      navigate(`/search?q=${encodeURIComponent(finalQuery)}`);
    }
  };

  // Handle suggestion click
  const handleSuggestionClick = (suggestion: SearchSuggestion) => {
    setQuery(suggestion.suggestion);
    handleSearch(suggestion.suggestion);
  };

  // Handle keyboard navigation
  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter') {
      handleSearch();
    } else if (e.key === 'Escape') {
      setShowSuggestions(false);
      inputRef.current?.blur();
    }
  };

  // Close suggestions when clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (searchRef.current && !searchRef.current.contains(event.target as Node)) {
        setShowSuggestions(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, []);

  // Cleanup debounce timer
  useEffect(() => {
    return () => {
      if (debounceTimer.current) {
        clearTimeout(debounceTimer.current);
      }
    };
  }, []);

  // Get icon for suggestion type
  const getSuggestionIcon = (type: string) => {
    switch (type) {
      case 'article':
        return '📄';
      case 'tag':
        return '#';
      case 'user':
        return '👤';
      default:
        return '🔍';
    }
  };

  return (
    <div ref={searchRef} className={`relative ${className}`}>
      <div className="relative">
        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <svg
            className="h-5 w-5 text-gray-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
            />
          </svg>
        </div>
        
        <input
          ref={inputRef}
          type="text"
          value={query}
          onChange={(e) => handleInputChange(e.target.value)}
          onKeyDown={handleKeyDown}
          onFocus={() => query.length >= 2 && setShowSuggestions(true)}
          placeholder={placeholder}
          className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-green-500 focus:border-green-500"
        />

        {loading && (
          <div className="absolute inset-y-0 right-0 pr-3 flex items-center">
            <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-green-500"></div>
          </div>
        )}
      </div>

      {/* Search Suggestions Dropdown */}
      {showSuggestions && suggestions.length > 0 && (
        <div className="absolute top-full left-0 right-0 bg-white border border-gray-200 rounded-md shadow-lg z-50 mt-1 max-h-64 overflow-y-auto">
          {suggestions.map((suggestion, index) => (
            <button
              key={index}
              onClick={() => handleSuggestionClick(suggestion)}
              className="w-full px-4 py-3 text-left hover:bg-gray-50 focus:bg-gray-50 focus:outline-none flex items-center space-x-3 border-b border-gray-100 last:border-b-0"
            >
              <span className="text-lg">{getSuggestionIcon(suggestion.type)}</span>
              <div className="flex-1">
                <div className="flex items-center space-x-2">
                  <span className="text-sm text-gray-900">{suggestion.suggestion}</span>
                  <span className="text-xs text-gray-500 capitalize bg-gray-100 px-2 py-1 rounded">
                    {suggestion.type}
                  </span>
                </div>
              </div>
            </button>
          ))}
          
          {/* View all results option */}
          <button
            onClick={() => handleSearch()}
            className="w-full px-4 py-3 text-left hover:bg-gray-50 focus:bg-gray-50 focus:outline-none border-t border-gray-200 text-green-600 font-medium"
          >
            View all results for "{query}"
          </button>
        </div>
      )}

      {/* No suggestions message */}
      {showSuggestions && !loading && suggestions.length === 0 && query.length >= 2 && (
        <div className="absolute top-full left-0 right-0 bg-white border border-gray-200 rounded-md shadow-lg z-50 mt-1">
          <div className="px-4 py-3 text-gray-500 text-sm">
            No suggestions found. Press Enter to search.
          </div>
        </div>
      )}
    </div>
  );
};

export default SearchBar;