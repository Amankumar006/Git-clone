import React from 'react';
import { useParams } from 'react-router-dom';

const ProfilePage: React.FC = () => {
  const { username } = useParams<{ username: string }>();

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="text-center py-16">
          <h1 className="text-2xl font-bold text-gray-900 mb-4">
            User Profile
          </h1>
          <p className="text-gray-600">
            Username: {username}
          </p>
          <p className="text-gray-500 mt-4">
            User profile and articles will be displayed here once the backend is implemented.
          </p>
        </div>
      </div>
    </div>
  );
};

export default ProfilePage;