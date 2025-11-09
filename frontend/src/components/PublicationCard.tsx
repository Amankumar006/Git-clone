import React from 'react';
import { Link } from 'react-router-dom';
import { Publication } from '../types';

interface PublicationCardProps {
  publication: Publication & {
    member_count?: number;
    article_count?: number;
    user_role?: string;
  };
  showManageButton?: boolean;
}

const PublicationCard: React.FC<PublicationCardProps> = ({ 
  publication, 
  showManageButton = false 
}) => {
  return (
    <div className="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
      <div className="flex items-start space-x-4">
        {publication.logo_url ? (
          <img
            src={publication.logo_url}
            alt={publication.name}
            className="w-16 h-16 rounded-lg object-cover"
          />
        ) : (
          <div className="w-16 h-16 rounded-lg bg-gray-200 flex items-center justify-center">
            <span className="text-2xl font-bold text-gray-500">
              {publication.name.charAt(0).toUpperCase()}
            </span>
          </div>
        )}
        
        <div className="flex-1">
          <div className="flex items-start justify-between">
            <div>
              <Link
                to={`/publication/${publication.id}`}
                className="text-xl font-bold text-gray-900 hover:text-blue-600 transition-colors"
              >
                {publication.name}
              </Link>
              {publication.user_role && (
                <span className="ml-2 px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                  {publication.user_role}
                </span>
              )}
            </div>
            
            {showManageButton && (
              <Link
                to={`/publication/${publication.id}/manage`}
                className="px-3 py-1 text-sm font-medium text-blue-600 hover:text-blue-800 border border-blue-600 hover:border-blue-800 rounded-md transition-colors"
              >
                Manage
              </Link>
            )}
          </div>
          
          {publication.description && (
            <p className="text-gray-600 mt-2 line-clamp-2">
              {publication.description}
            </p>
          )}
          
          <div className="flex items-center space-x-4 mt-3 text-sm text-gray-500">
            {publication.member_count !== undefined && (
              <span>
                {publication.member_count} member{publication.member_count !== 1 ? 's' : ''}
              </span>
            )}
            {publication.article_count !== undefined && (
              <span>
                {publication.article_count} article{publication.article_count !== 1 ? 's' : ''}
              </span>
            )}
            <span>
              Created {new Date(publication.created_at).toLocaleDateString()}
            </span>
          </div>
        </div>
      </div>
    </div>
  );
};

export default PublicationCard;