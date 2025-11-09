import React, { useState, useEffect, useCallback } from 'react';
import { useSearchParams, useNavigate } from 'react-router-dom';
import { apiService } from '../utils/api';
import ArticleCard from '../components/ArticleCard';

interface SearchResult {
  articles: any[];
  users: any[];
  tags: any[];
  total_count: number;
  pagination: {
    current_page: number;
    per_page: number;
    total_pages: number;
  };
}

interface SearchFilters {
  type?: string;
  author?: string;
  tag?: string;
  date_from?: string;
  date_to?: string;
  sort?: string;
  order?: string;
}

const SearchPage: React.FC = () => {
  const [searchParams, setSearchParams] = useSearchParams();
  const navigate = useNavigate();
  
  const [query, setQuery] = useState(searchParams.get('q') || '');
  const [results, setResults] = useState<SearchResult | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [activeTab, setActiveTab] = useState('all');
  const [showFilters, setShowFilters] = useState(false);
  
  const [filters, setFilters] = useState<SearchFilters>({
    type: searchParams.get('type') || '',
    author: searchParams.get('author') || '',
    tag: searchParams.get('tag') || '',
    date_from: searchParams.get('date_from') || '',
    date_to: searchParams.get('date_to') || '',
    sort: searchParams.get('sort') || 'relevance',
    order: searchParams.get('order') || 'desc'
  });

  const [suggestions, setSuggestions] = useState<any[]>([]);
  const [showSuggestions, setShowSuggestions] = useState(false);

  // Debounced search function
  const performSearch = useCallback(async (searchQuery: string, searchFilters: SearchFilters, page = 1) => {
    if (!searchQuery.trim()) {
      setResults(null);
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const params = new URLSearchParams({
        q: searchQuery,
        page: page.toString(),
        limit: '10',
        ...Object.fromEntries(
          Object.entries(searchFilters).filter(([_, value]) => value)
        )
      });

      const response = await apiService.search.articles(searchQuery, filters);
      
      if (response.data.success) {
        setResults(response.data.data);
      } else {
        setError('Search failed');
      }
    } catch (err: any) {
      setError(err.response?.data?.error || 'Search failed');
    } finally {
      setLoading(false);
    }
  }, []);

  // Get search suggestions
  const getSuggestions = useCallback(async (searchQuery: string) => {
    if (searchQuery.length < 2) {
      setSuggestions([]);
      return;
    }

    try {
      const response = await apiService.search.getSuggestions(searchQuery);
      if (response.data.success) {
        setSuggestions(response.data.data);
      }
    } catch (err) {
      console.error('Failed to get suggestions:', err);
    }
  }, []);

  // Handle search input change
  const handleSearchChange = (value: string) => {
    setQuery(value);
    getSuggestions(value);
    setShowSuggestions(true);
  };

  // Handle search submission
  const handleSearch = (searchQuery?: string) => {
    const finalQuery = searchQuery || query;
    if (!finalQuery.trim()) return;

    setShowSuggestions(false);
    
    // Update URL params
    const params = new URLSearchParams({
      q: finalQuery,
      ...Object.fromEntries(
        Object.entries(filters).filter(([_, value]) => value)
      )
    });
    setSearchParams(params);
    
    performSearch(finalQuery, filters);
  };

  // Handle filter change
  const handleFilterChange = (key: keyof SearchFilters, value: string) => {
    const newFilters = { ...filters, [key]: value };
    setFilters(newFilters);
    
    if (query) {
      performSearch(query, newFilters);
    }
  };

  // Clear filters
  const clearFilters = () => {
    const clearedFilters: SearchFilters = {
      type: '',
      author: '',
      tag: '',
      date_from: '',
      date_to: '',
      sort: 'relevance',
      order: 'desc'
    };
    setFilters(clearedFilters);
    
    if (query) {
      performSearch(query, clearedFilters);
    }
  };

  // Load search on component mount
  useEffect(() => {
    const initialQuery = searchParams.get('q');
    if (initialQuery) {
      setQuery(initialQuery);
      performSearch(initialQuery, filters);
    }
  }, []);

  // Filter results by type for tabs
  const getFilteredResults = () => {
    if (!results) return { articles: [], users: [], tags: [] };
    
    switch (activeTab) {
      case 'articles':
        return { articles: results.articles, users: [], tags: [] };
      case 'users':
        return { articles: [], users: results.users, tags: [] };
      case 'tags':
        return { articles: [], users: [], tags: results.tags };
      default:
        return results;
    }
  };

  const filteredResults = getFilteredResults();

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-4xl mx-auto px-4 py-8">
        {/* Search Header */}
        <div className="bg-white rounded-lg shadow-sm p-6 mb-6">
          <div className="relative">
            <div className="flex items-center space-x-4">
              <div className="flex-1 relative">
                <input
                  type="text"
                  value={query}
                  onChange={(e) => handleSearchChange(e.target.value)}
                  onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
                  placeholder="Search articles, authors, and topics..."
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                />
                
                {/* Search Suggestions */}
                {showSuggestions && suggestions.length > 0 && (
                  <div className="absolute top-full left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-lg z-10 mt-1">
                    {suggestions.map((suggestion, index) => (
                      <button
                        key={index}
                        onClick={() => handleSearch(suggestion.suggestion)}
                        className="w-full px-4 py-2 text-left hover:bg-gray-50 flex items-center space-x-2"
                      >
                        <span className="text-sm text-gray-500 capitalize">
                          {suggestion.type}:
                        </span>
                        <span>{suggestion.suggestion}</span>
                      </button>
                    ))}
                  </div>
                )}
              </div>
              
              <button
                onClick={() => handleSearch()}
                className="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
              >
                Search
              </button>
              
              <button
                onClick={() => setShowFilters(!showFilters)}
                className="px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
              >
                Filters
              </button>
            </div>
          </div>

          {/* Advanced Filters */}
          {showFilters && (
            <div className="mt-6 p-4 bg-gray-50 rounded-lg">
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Content Type
                  </label>
                  <select
                    value={filters.type || ''}
                    onChange={(e) => handleFilterChange('type', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500"
                  >
                    <option value="">All Types</option>
                    <option value="articles">Articles Only</option>
                  </select>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Author
                  </label>
                  <input
                    type="text"
                    value={filters.author || ''}
                    onChange={(e) => handleFilterChange('author', e.target.value)}
                    placeholder="Author username"
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Tag
                  </label>
                  <input
                    type="text"
                    value={filters.tag || ''}
                    onChange={(e) => handleFilterChange('tag', e.target.value)}
                    placeholder="Tag name"
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    From Date
                  </label>
                  <input
                    type="date"
                    value={filters.date_from || ''}
                    onChange={(e) => handleFilterChange('date_from', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    To Date
                  </label>
                  <input
                    type="date"
                    value={filters.date_to || ''}
                    onChange={(e) => handleFilterChange('date_to', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Sort By
                  </label>
                  <select
                    value={filters.sort || 'relevance'}
                    onChange={(e) => handleFilterChange('sort', e.target.value)}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500"
                  >
                    <option value="relevance">Relevance</option>
                    <option value="date">Date</option>
                    <option value="popularity">Popularity</option>
                  </select>
                </div>
              </div>

              <div className="mt-4 flex justify-end space-x-2">
                <button
                  onClick={clearFilters}
                  className="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors"
                >
                  Clear Filters
                </button>
              </div>
            </div>
          )}
        </div>

        {/* Search Results */}
        {loading && (
          <div className="text-center py-8">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600 mx-auto"></div>
            <p className="mt-2 text-gray-600">Searching...</p>
          </div>
        )}

        {error && (
          <div className="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <p className="text-red-600">{error}</p>
          </div>
        )}

        {results && !loading && (
          <>
            {/* Results Tabs */}
            <div className="bg-white rounded-lg shadow-sm mb-6">
              <div className="border-b border-gray-200">
                <nav className="flex space-x-8 px-6">
                  {[
                    { key: 'all', label: `All (${results.total_count})` },
                    { key: 'articles', label: `Articles (${results.articles.length})` },
                    { key: 'users', label: `Authors (${results.users.length})` },
                    { key: 'tags', label: `Tags (${results.tags.length})` }
                  ].map((tab) => (
                    <button
                      key={tab.key}
                      onClick={() => setActiveTab(tab.key)}
                      className={`py-4 px-1 border-b-2 font-medium text-sm ${
                        activeTab === tab.key
                          ? 'border-green-500 text-green-600'
                          : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                      }`}
                    >
                      {tab.label}
                    </button>
                  ))}
                </nav>
              </div>
            </div>

            {/* Results Content */}
            <div className="space-y-6">
              {/* Articles */}
              {filteredResults.articles.length > 0 && (
                <div>
                  {activeTab === 'all' && (
                    <h3 className="text-lg font-semibold text-gray-900 mb-4">Articles</h3>
                  )}
                  <div className="space-y-4">
                    {filteredResults.articles.map((article) => (
                      <div key={article.id} className="bg-white rounded-lg shadow-sm">
                        <ArticleCard article={article} />
                        {article.highlights && (
                          <div className="px-6 pb-4">
                            <div className="text-sm text-gray-600">
                              {article.highlights.content && (
                                <div className="mt-2">
                                  <span className="font-medium">Excerpt: </span>
                                  <span dangerouslySetInnerHTML={{ __html: article.highlights.content }} />
                                </div>
                              )}
                            </div>
                          </div>
                        )}
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Users */}
              {filteredResults.users.length > 0 && (
                <div>
                  {activeTab === 'all' && (
                    <h3 className="text-lg font-semibold text-gray-900 mb-4">Authors</h3>
                  )}
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {filteredResults.users.map((user) => (
                      <div key={user.id} className="bg-white rounded-lg shadow-sm p-6">
                        <div className="flex items-center space-x-4">
                          <img
                            src={user.profile_image_url || '/default-avatar.svg'}
                            alt={user.username}
                            className="w-12 h-12 rounded-full object-cover"
                            onError={(e) => {
                              const target = e.target as HTMLImageElement;
                              if (target.src !== window.location.origin + '/default-avatar.svg') {
                                target.src = '/default-avatar.svg';
                              }
                            }}
                          />
                          <div className="flex-1">
                            <h4 className="font-semibold text-gray-900">{user.username}</h4>
                            <p className="text-sm text-gray-600 line-clamp-2">{user.bio}</p>
                            <div className="flex items-center space-x-4 mt-2 text-sm text-gray-500">
                              <span>{user.followers_count} followers</span>
                              <span>{user.articles_count} articles</span>
                            </div>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Tags */}
              {filteredResults.tags.length > 0 && (
                <div>
                  {activeTab === 'all' && (
                    <h3 className="text-lg font-semibold text-gray-900 mb-4">Tags</h3>
                  )}
                  <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {filteredResults.tags.map((tag) => (
                      <div key={tag.id} className="bg-white rounded-lg shadow-sm p-4">
                        <div className="flex items-center justify-between">
                          <div>
                            <h4 className="font-semibold text-gray-900">#{tag.name}</h4>
                            <p className="text-sm text-gray-600">{tag.article_count} articles</p>
                          </div>
                          <button
                            onClick={() => navigate(`/tag/${tag.slug}`)}
                            className="text-green-600 hover:text-green-700 text-sm font-medium"
                          >
                            View
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* No Results */}
              {results.total_count === 0 && (
                <div className="text-center py-12">
                  <div className="text-gray-400 text-6xl mb-4">🔍</div>
                  <h3 className="text-lg font-medium text-gray-900 mb-2">No results found</h3>
                  <p className="text-gray-600">
                    Try adjusting your search terms or filters to find what you're looking for.
                  </p>
                </div>
              )}
            </div>
          </>
        )}
      </div>
    </div>
  );
};

export default SearchPage;