import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { apiService } from '../utils/api';

interface Tag {
  id: number;
  name: string;
  slug: string;
  description?: string;
  article_count: number;
  size_class?: string;
}

interface TagCategory {
  category: string;
  tags: Tag[];
}

const TagsPage: React.FC = () => {
  const navigate = useNavigate();
  
  const [activeView, setActiveView] = useState<'cloud' | 'categories' | 'list'>('cloud');
  const [tagCloud, setTagCloud] = useState<Tag[]>([]);
  const [categories, setCategories] = useState<TagCategory[]>([]);
  const [allTags, setAllTags] = useState<Tag[]>([]);
  const [trendingTags, setTrendingTags] = useState<Tag[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [searchQuery, setSearchQuery] = useState('');
  const [filteredTags, setFilteredTags] = useState<Tag[]>([]);

  useEffect(() => {
    loadTagsData();
  }, []);

  useEffect(() => {
    // Filter tags based on search query
    if (searchQuery.trim()) {
      const filtered = allTags.filter(tag =>
        tag.name.toLowerCase().includes(searchQuery.toLowerCase())
      );
      setFilteredTags(filtered);
    } else {
      setFilteredTags(allTags);
    }
  }, [searchQuery, allTags]);

  const loadTagsData = async () => {
    setLoading(true);
    setError(null);

    try {
      // Load tag cloud
      const cloudResponse = await apiService.tags.getCloud(100);
      if (cloudResponse.data.success) {
        setTagCloud(cloudResponse.data.data.tags);
      }

      // Load categories
      const categoriesResponse = await apiService.tags.getCategories();
      if (categoriesResponse.data.success) {
        setCategories(categoriesResponse.data.data.categories);
      }

      // Load all tags
      const allTagsResponse = await apiService.tags.getAll(200);
      if (allTagsResponse.data.success) {
        setAllTags(allTagsResponse.data.data || allTagsResponse.data);
      }

      // Load trending tags
      const trendingResponse = await apiService.tags.getTrending(20);
      if (trendingResponse.data.success) {
        setTrendingTags(trendingResponse.data.data || trendingResponse.data);
      }

    } catch (err: any) {
      setError(err.response?.data?.error || 'Failed to load tags');
    } finally {
      setLoading(false);
    }
  };

  const getSizeClass = (sizeClass: string) => {
    switch (sizeClass) {
      case 'xl':
        return 'text-3xl font-bold';
      case 'lg':
        return 'text-2xl font-semibold';
      case 'md':
        return 'text-xl font-medium';
      case 'sm':
        return 'text-lg';
      default:
        return 'text-base';
    }
  };

  const getColorClass = (index: number) => {
    const colors = [
      'text-blue-600 hover:text-blue-700',
      'text-green-600 hover:text-green-700',
      'text-purple-600 hover:text-purple-700',
      'text-red-600 hover:text-red-700',
      'text-yellow-600 hover:text-yellow-700',
      'text-indigo-600 hover:text-indigo-700',
      'text-pink-600 hover:text-pink-700',
      'text-gray-600 hover:text-gray-700'
    ];
    return colors[index % colors.length];
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600 mx-auto"></div>
          <p className="mt-2 text-gray-600">Loading tags...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="text-red-500 text-6xl mb-4">⚠️</div>
          <h2 className="text-2xl font-bold text-gray-900 mb-2">Error Loading Tags</h2>
          <p className="text-gray-600 mb-4">{error}</p>
          <button
            onClick={loadTagsData}
            className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
          >
            Try Again
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-6xl mx-auto px-4 py-8">
        {/* Header */}
        <div className="bg-white rounded-lg shadow-sm p-8 mb-8">
          <div className="text-center mb-8">
            <h1 className="text-4xl font-bold text-gray-900 mb-4">Explore Topics</h1>
            <p className="text-xl text-gray-600 max-w-2xl mx-auto">
              Discover articles across different topics and find content that interests you.
            </p>
          </div>

          {/* Search Bar */}
          <div className="max-w-md mx-auto mb-6">
            <div className="relative">
              <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg className="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Search tags..."
                className="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
              />
            </div>
          </div>

          {/* View Toggle */}
          <div className="flex justify-center space-x-1 bg-gray-100 rounded-lg p-1 max-w-md mx-auto">
            {[
              { key: 'cloud', label: 'Tag Cloud', icon: '☁️' },
              { key: 'categories', label: 'Categories', icon: '📂' },
              { key: 'list', label: 'List View', icon: '📋' }
            ].map((view) => (
              <button
                key={view.key}
                onClick={() => setActiveView(view.key as any)}
                className={`flex-1 px-4 py-2 rounded-md text-sm font-medium transition-colors ${
                  activeView === view.key
                    ? 'bg-white text-green-600 shadow-sm'
                    : 'text-gray-600 hover:text-gray-900'
                }`}
              >
                <span className="mr-2">{view.icon}</span>
                {view.label}
              </button>
            ))}
          </div>
        </div>

        {/* Trending Tags */}
        {trendingTags.length > 0 && (
          <div className="bg-white rounded-lg shadow-sm p-6 mb-8">
            <h2 className="text-xl font-semibold text-gray-900 mb-4">🔥 Trending This Week</h2>
            <div className="flex flex-wrap gap-3">
              {trendingTags.slice(0, 10).map((tag) => (
                <button
                  key={tag.id}
                  onClick={() => navigate(`/tag/${tag.slug}`)}
                  className="px-4 py-2 bg-gradient-to-r from-green-500 to-blue-500 text-white rounded-full text-sm font-medium hover:from-green-600 hover:to-blue-600 transition-all transform hover:scale-105"
                >
                  #{tag.name} ({tag.article_count})
                </button>
              ))}
            </div>
          </div>
        )}

        {/* Main Content */}
        <div className="bg-white rounded-lg shadow-sm p-8">
          {/* Tag Cloud View */}
          {activeView === 'cloud' && (
            <div>
              <h2 className="text-2xl font-semibold text-gray-900 mb-6 text-center">Tag Cloud</h2>
              <div className="text-center leading-relaxed">
                {(searchQuery ? filteredTags : tagCloud).map((tag, index) => (
                  <button
                    key={tag.id}
                    onClick={() => navigate(`/tag/${tag.slug}`)}
                    className={`inline-block m-2 transition-all hover:scale-110 ${
                      getSizeClass(tag.size_class || 'sm')
                    } ${getColorClass(index)}`}
                    title={`${tag.article_count} articles`}
                  >
                    #{tag.name}
                  </button>
                ))}
              </div>
              {(searchQuery ? filteredTags : tagCloud).length === 0 && (
                <div className="text-center py-12">
                  <p className="text-gray-600">No tags found matching your search.</p>
                </div>
              )}
            </div>
          )}

          {/* Categories View */}
          {activeView === 'categories' && (
            <div>
              <h2 className="text-2xl font-semibold text-gray-900 mb-6 text-center">Browse by Category</h2>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {categories.map((category) => (
                  <div key={category.category} className="border border-gray-200 rounded-lg p-6">
                    <h3 className="text-lg font-semibold text-gray-900 mb-4">{category.category}</h3>
                    <div className="space-y-2">
                      {category.tags.map((tag) => (
                        <button
                          key={tag.id}
                          onClick={() => navigate(`/tag/${tag.slug}`)}
                          className="block w-full text-left px-3 py-2 text-gray-700 hover:bg-gray-50 rounded-md transition-colors"
                        >
                          <span className="font-medium">#{tag.name}</span>
                          <span className="text-sm text-gray-500 ml-2">({tag.article_count})</span>
                        </button>
                      ))}
                    </div>
                  </div>
                ))}
              </div>
              {categories.length === 0 && (
                <div className="text-center py-12">
                  <p className="text-gray-600">No categories available.</p>
                </div>
              )}
            </div>
          )}

          {/* List View */}
          {activeView === 'list' && (
            <div>
              <h2 className="text-2xl font-semibold text-gray-900 mb-6 text-center">All Tags</h2>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {(searchQuery ? filteredTags : allTags).map((tag) => (
                  <button
                    key={tag.id}
                    onClick={() => navigate(`/tag/${tag.slug}`)}
                    className="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-green-500 hover:bg-green-50 transition-all"
                  >
                    <div className="text-left">
                      <div className="font-medium text-gray-900">#{tag.name}</div>
                      {tag.description && (
                        <div className="text-sm text-gray-600 line-clamp-2">{tag.description}</div>
                      )}
                    </div>
                    <div className="text-sm text-gray-500 ml-4">
                      {tag.article_count} articles
                    </div>
                  </button>
                ))}
              </div>
              {(searchQuery ? filteredTags : allTags).length === 0 && (
                <div className="text-center py-12">
                  <p className="text-gray-600">
                    {searchQuery ? 'No tags found matching your search.' : 'No tags available.'}
                  </p>
                </div>
              )}
            </div>
          )}
        </div>

        {/* Call to Action */}
        <div className="bg-gradient-to-r from-green-500 to-blue-500 rounded-lg p-8 mt-8 text-center text-white">
          <h2 className="text-2xl font-bold mb-4">Can't find what you're looking for?</h2>
          <p className="text-lg mb-6 opacity-90">
            Use our advanced search to find exactly what you need, or start writing about a new topic!
          </p>
          <div className="space-x-4">
            <button
              onClick={() => navigate('/search')}
              className="px-6 py-3 bg-white text-green-600 rounded-lg font-medium hover:bg-gray-100 transition-colors"
            >
              Advanced Search
            </button>
            <button
              onClick={() => navigate('/write')}
              className="px-6 py-3 bg-transparent border-2 border-white text-white rounded-lg font-medium hover:bg-white hover:text-green-600 transition-colors"
            >
              Start Writing
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default TagsPage;