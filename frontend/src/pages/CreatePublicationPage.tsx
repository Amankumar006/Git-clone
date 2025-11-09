import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { apiService } from '../utils/api';
import { useAuth } from '../context/AuthContext';
import PublicationForm, { PublicationFormData } from '../components/PublicationForm';

const CreatePublicationPage: React.FC = () => {
  const navigate = useNavigate();
  const { user } = useAuth();
  const [isCreating, setIsCreating] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleCreatePublication = async (data: PublicationFormData) => {
    setIsCreating(true);
    setError(null);

    try {
      const response = await apiService.publications.create(data);

      if (response.data.success) {
        const publicationId = response.data.data.id;
        navigate(`/publication/${publicationId}/manage`);
      } else {
        setError(response.data.message || 'Failed to create publication');
      }
    } catch (error: any) {
      console.error('Failed to create publication:', error);
      setError(error.response?.data?.message || 'Failed to create publication');
    } finally {
      setIsCreating(false);
    }
  };

  const handleCancel = () => {
    navigate('/dashboard');
  };

  if (!user) {
    return (
      <div className="min-h-screen bg-gray-50 flex justify-center items-center">
        <div className="text-center">
          <h2 className="text-2xl font-bold text-gray-900">Authentication Required</h2>
          <p className="text-gray-600 mt-2">You need to be logged in to create a publication.</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="bg-white rounded-lg shadow-sm">
          <div className="px-6 py-4 border-b border-gray-200">
            <h1 className="text-2xl font-bold text-gray-900">Create New Publication</h1>
            <p className="text-gray-600 mt-2">
              Start your own publication to collaborate with other writers and build a community around your content.
            </p>
          </div>

          <div className="p-6">
            {error && (
              <div className="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
                <div className="flex">
                  <div className="flex-shrink-0">
                    <svg className="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                      <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                    </svg>
                  </div>
                  <div className="ml-3">
                    <h3 className="text-sm font-medium text-red-800">Error</h3>
                    <div className="mt-2 text-sm text-red-700">
                      {error}
                    </div>
                  </div>
                </div>
              </div>
            )}

            <PublicationForm
              onSubmit={handleCreatePublication}
              onCancel={handleCancel}
              isLoading={isCreating}
            />
          </div>
        </div>

        {/* Info Section */}
        <div className="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
          <h3 className="text-lg font-medium text-blue-900 mb-4">What is a Publication?</h3>
          <div className="space-y-3 text-sm text-blue-800">
            <p>
              A publication is a collaborative space where multiple writers can contribute articles under a shared brand and identity.
            </p>
            <p>
              As the owner, you can:
            </p>
            <ul className="list-disc list-inside space-y-1 ml-4">
              <li>Invite writers, editors, and admins to join your publication</li>
              <li>Review and approve articles before they're published</li>
              <li>Customize your publication's branding and description</li>
              <li>Track analytics and engagement across all articles</li>
              <li>Build a community around your publication's topics</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  );
};

export default CreatePublicationPage;