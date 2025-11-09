import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Publication } from '../types';
import { apiService } from '../utils/api';
import { useAuth } from '../context/AuthContext';
import PublicationCard from '../components/PublicationCard';

const PublicationsPage: React.FC = () => {
  const { user } = useAuth();
  const [myPublications, setMyPublications] = useState<{
    owned: Publication[];
    member: Publication[];
  }>({ owned: [], member: [] });
  const [allPublications, setAllPublications] = useState<Publication[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState<Publication[]>([]);
  const [searching, setSearching] = useState(false);

  useEffect(() => {
    loadPublications();
  }, []);

  useEffect(() => {
    if (searchQuery.trim()) {
      searchPublications();
    } else {
      setSearchResults([]);
    }
  }, [searchQuery]);

  const loadPublications = async () => {
    try {
      // Load user's publications if authenticated
      if (user) {
        const myResponse = await apiService.publications.getMy();
        if (myResponse.data.success) {
          setMyPublications(myResponse.data.data);
        }
      }

      // Load all public publications
      const allResponse = await apiService.publications.search('');
      if (allResponse.data.success) {
        setAllPublications(allResponse.data.data);
      }
    } catch (error) {
      console.error('Failed to load publications:', error);
    } finally {
      setLoading(false);
    }
  };

  const searchPublications = async () => {
    if (!searchQuery.trim()) return;

    setSearching(true);
    try {
      const response = await apiService.publications.search(searchQuery);
      if (response.data.success) {
        setSearchResults(response.data.data);
      }
    } catch (error) {
      console.error('Failed to search publications:', error);
    } finally {
      setSearching(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex justify-center items-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  const displayPublications = searchQuery.trim() ? searchResults : allPublications;

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="text-center mb-8">
          <h1 className="text-3xl font-bold text-gray-900">Publications</h1>
          <p className="text-gray-600 mt-2 max-w-2xl mx-auto">
            Discover publications from writers around the world, or create your own to start collaborating with other writers.
          </p>
        </div>

        {/* Search Bar */}
        <div className="max-w-md mx-auto mb-8">
          <div className="relative">
            <input
              type="text"
              placeholder="Search publications..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full px-4 py-2 pl-10 pr-4 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg className="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            {searching && (
              <div className="absolute inset-y-0 right-0 pr-3 flex items-center">
                <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
              </div>
            )}
          </div>
        </div>

        {/* Create Publication Button */}
        {user && (
          <div className="text-center mb-8">
            <Link
              to="/publications/create"
              className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
              </svg>
              Create Publication
            </Link>
          </div>
        )}

        {/* My Publications */}
        {user && (myPublications.owned.length > 0 || myPublications.member.length > 0) && !searchQuery && (
          <div className="mb-12">
            <h2 className="text-2xl font-bold text-gray-900 mb-6">My Publications</h2>
            
            {myPublications.owned.length > 0 && (
              <div className="mb-8">
                <h3 className="text-lg font-medium text-gray-900 mb-4">Owned by me</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                  {myPublications.owned.map((publication) => (
                    <PublicationCard
                      key={publication.id}
                      publication={{ ...publication, user_role: 'owner' }}
                      showManageButton={true}
                    />
                  ))}
                </div>
              </div>
            )}

            {myPublications.member.length > 0 && (
              <div className="mb-8">
                <h3 className="text-lg font-medium text-gray-900 mb-4">Member of</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                  {myPublications.member.map((publication) => (
                    <PublicationCard
                      key={publication.id}
                      publication={publication}
                      showManageButton={publication.user_role === 'admin' || publication.user_role === 'owner'}
                    />
                  ))}
                </div>
              </div>
            )}
          </div>
        )}

        {/* All Publications */}
        <div>
          <h2 className="text-2xl font-bold text-gray-900 mb-6">
            {searchQuery ? `Search Results (${displayPublications.length})` : 'Discover Publications'}
          </h2>
          
          {displayPublications.length > 0 ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {displayPublications.map((publication) => (
                <PublicationCard
                  key={publication.id}
                  publication={publication}
                />
              ))}
            </div>
          ) : (
            <div className="text-center py-12">
              <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
              </svg>
              <h3 className="mt-2 text-sm font-medium text-gray-900">
                {searchQuery ? 'No publications found' : 'No publications yet'}
              </h3>
              <p className="mt-1 text-sm text-gray-500">
                {searchQuery 
                  ? 'Try adjusting your search terms.' 
                  : 'Be the first to create a publication!'
                }
              </p>
              {!searchQuery && user && (
                <div className="mt-6">
                  <Link
                    to="/publications/create"
                    className="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                  >
                    <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                    </svg>
                    Create Publication
                  </Link>
                </div>
              )}
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default PublicationsPage;