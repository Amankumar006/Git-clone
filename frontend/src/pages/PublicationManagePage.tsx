import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Publication, PublicationMember, User } from '../types';
import { apiService } from '../utils/api';
import { useAuth } from '../context/AuthContext';
import PublicationForm, { PublicationFormData } from '../components/PublicationForm';
import PublicationMemberList from '../components/PublicationMemberList';
import PublicationDashboard from '../components/PublicationDashboard';

const PublicationManagePage: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { user } = useAuth();
  const [publication, setPublication] = useState<Publication & {
    stats?: any;
    members?: (PublicationMember & { user: User })[];
    user_role?: string;
    can_manage?: boolean;
  } | null>(null);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState<'dashboard' | 'settings' | 'members'>('dashboard');
  const [isEditing, setIsEditing] = useState(false);
  const [isSaving, setIsSaving] = useState(false);

  useEffect(() => {
    if (id) {
      loadPublication();
    }
  }, [id]);

  const loadPublication = async () => {
    try {
      const response = await apiService.publications.getById(id!);
      if (response.data.success) {
        setPublication(response.data.data);
        
        // Check if user has permission to manage
        if (!response.data.data.can_manage) {
          navigate(`/publication/${id}`);
          return;
        }
      }
    } catch (error) {
      console.error('Failed to load publication:', error);
      navigate('/dashboard');
    } finally {
      setLoading(false);
    }
  };

  const handleUpdatePublication = async (data: PublicationFormData) => {
    if (!publication) return;

    setIsSaving(true);
    try {
      const response = await apiService.publications.update(publication.id.toString(), data);

      if (response.data.success) {
        setPublication(prev => prev ? { ...prev, ...data } : null);
        setIsEditing(false);
      }
    } catch (error) {
      console.error('Failed to update publication:', error);
    } finally {
      setIsSaving(false);
    }
  };

  const handleInviteMember = async (email: string, role: 'admin' | 'editor' | 'writer') => {
    if (!publication) return;

    try {
      const response = await apiService.publications.invite(publication.id.toString(), email, role);

      if (response.data.success) {
        // Reload publication to get updated members list
        loadPublication();
      }
    } catch (error) {
      console.error('Failed to invite member:', error);
    }
  };

  const handleRoleChange = async (userId: number, role: 'admin' | 'editor' | 'writer') => {
    if (!publication) return;

    try {
      const response = await apiService.publications.updateRole(publication.id.toString(), userId, role);

      if (response.data.success) {
        // Update local state
        setPublication(prev => {
          if (!prev || !prev.members) return prev;
          return {
            ...prev,
            members: prev.members.map(member =>
              member.user_id === userId ? { ...member, role } : member
            )
          } as any;
        });
      }
    } catch (error) {
      console.error('Failed to update member role:', error);
    }
  };

  const handleRemoveMember = async (userId: number) => {
    if (!publication) return;

    if (!window.confirm('Are you sure you want to remove this member?')) {
      return;
    }

    try {
      const response = await apiService.publications.removeMember(publication.id.toString(), userId);

      if (response.data.success) {
        // Update local state
        setPublication(prev => {
          if (!prev || !prev.members) return prev;
          return {
            ...prev,
            members: prev.members.filter(member => member.user_id !== userId)
          } as any;
        });
      }
    } catch (error) {
      console.error('Failed to remove member:', error);
    }
  };

  const handleDeletePublication = async () => {
    if (!publication) return;

    if (!window.confirm('Are you sure you want to delete this publication? This action cannot be undone.')) {
      return;
    }

    try {
      const response = await apiService.publications.delete(publication.id.toString());

      if (response.data.success) {
        navigate('/dashboard');
      }
    } catch (error) {
      console.error('Failed to delete publication:', error);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex justify-center items-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (!publication || !user) {
    return (
      <div className="min-h-screen bg-gray-50 flex justify-center items-center">
        <div className="text-center">
          <h2 className="text-2xl font-bold text-gray-900">Publication not found</h2>
          <p className="text-gray-600 mt-2">The publication you're looking for doesn't exist or you don't have access to it.</p>
        </div>
      </div>
    );
  }

  const canManageMembers = publication.user_role === 'owner' || publication.user_role === 'admin';

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="bg-white rounded-lg shadow-sm p-6 mb-6">
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-4">
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
              <div>
                <h1 className="text-2xl font-bold text-gray-900">{publication.name}</h1>
                <p className="text-gray-600">{publication.description}</p>
              </div>
            </div>
            <button
              onClick={() => navigate(`/publication/${publication.id}`)}
              className="px-4 py-2 text-sm font-medium text-blue-600 hover:text-blue-800 border border-blue-600 hover:border-blue-800 rounded-md transition-colors"
            >
              View Public Page
            </button>
          </div>
        </div>

        {/* Navigation Tabs */}
        <div className="bg-white rounded-lg shadow-sm mb-6">
          <div className="border-b border-gray-200">
            <nav className="-mb-px flex space-x-8 px-6">
              <button
                onClick={() => setActiveTab('dashboard')}
                className={`py-4 px-1 border-b-2 font-medium text-sm ${
                  activeTab === 'dashboard'
                    ? 'border-blue-500 text-blue-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                Dashboard
              </button>
              <button
                onClick={() => setActiveTab('members')}
                className={`py-4 px-1 border-b-2 font-medium text-sm ${
                  activeTab === 'members'
                    ? 'border-blue-500 text-blue-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                Members
              </button>
              <button
                onClick={() => setActiveTab('settings')}
                className={`py-4 px-1 border-b-2 font-medium text-sm ${
                  activeTab === 'settings'
                    ? 'border-blue-500 text-blue-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                Settings
              </button>
            </nav>
          </div>

          <div className="p-6">
            {activeTab === 'dashboard' && (
              <PublicationDashboard publication={publication} />
            )}

            {activeTab === 'members' && publication.members && (
              <PublicationMemberList
                members={publication.members}
                currentUserId={user.id}
                canManageMembers={canManageMembers}
                onRoleChange={handleRoleChange}
                onRemoveMember={handleRemoveMember}
                onInviteMember={handleInviteMember}
              />
            )}

            {activeTab === 'settings' && (
              <div className="space-y-6">
                <div className="flex items-center justify-between">
                  <h3 className="text-lg font-medium text-gray-900">Publication Settings</h3>
                  {!isEditing && (
                    <button
                      onClick={() => setIsEditing(true)}
                      className="px-4 py-2 text-sm font-medium text-blue-600 hover:text-blue-800 border border-blue-600 hover:border-blue-800 rounded-md transition-colors"
                    >
                      Edit Publication
                    </button>
                  )}
                </div>

                {isEditing ? (
                  <PublicationForm
                    publication={publication}
                    onSubmit={handleUpdatePublication}
                    onCancel={() => setIsEditing(false)}
                    isLoading={isSaving}
                  />
                ) : (
                  <div className="space-y-6">
                    <div className="bg-gray-50 p-4 rounded-lg">
                      <dl className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                          <dt className="text-sm font-medium text-gray-500">Name</dt>
                          <dd className="text-sm text-gray-900">{publication.name}</dd>
                        </div>
                        <div>
                          <dt className="text-sm font-medium text-gray-500">Created</dt>
                          <dd className="text-sm text-gray-900">{new Date(publication.created_at).toLocaleDateString()}</dd>
                        </div>
                        <div className="md:col-span-2">
                          <dt className="text-sm font-medium text-gray-500">Description</dt>
                          <dd className="text-sm text-gray-900">{publication.description || 'No description'}</dd>
                        </div>
                        {publication.website_url && (
                          <div>
                            <dt className="text-sm font-medium text-gray-500">Website</dt>
                            <dd className="text-sm text-gray-900">
                              <a href={publication.website_url} target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline">
                                {publication.website_url}
                              </a>
                            </dd>
                          </div>
                        )}
                        <div>
                          <dt className="text-sm font-medium text-gray-500">Theme Color</dt>
                          <dd className="text-sm text-gray-900 flex items-center space-x-2">
                            <div 
                              className="w-4 h-4 rounded border border-gray-300"
                              style={{ backgroundColor: publication.theme_color || '#3B82F6' }}
                            ></div>
                            <span>{publication.theme_color || '#3B82F6'}</span>
                          </dd>
                        </div>
                      </dl>
                    </div>

                    {/* Social Links */}
                    {publication.social_links && Object.values(publication.social_links).some(link => link) && (
                      <div className="bg-gray-50 p-4 rounded-lg">
                        <h4 className="text-sm font-medium text-gray-500 mb-3">Social Media Links</h4>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                          {publication.social_links.twitter && (
                            <div>
                              <dt className="text-xs font-medium text-gray-400">Twitter</dt>
                              <dd className="text-sm text-gray-900">
                                <a href={publication.social_links.twitter} target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline">
                                  {publication.social_links.twitter}
                                </a>
                              </dd>
                            </div>
                          )}
                          {publication.social_links.facebook && (
                            <div>
                              <dt className="text-xs font-medium text-gray-400">Facebook</dt>
                              <dd className="text-sm text-gray-900">
                                <a href={publication.social_links.facebook} target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline">
                                  {publication.social_links.facebook}
                                </a>
                              </dd>
                            </div>
                          )}
                          {publication.social_links.linkedin && (
                            <div>
                              <dt className="text-xs font-medium text-gray-400">LinkedIn</dt>
                              <dd className="text-sm text-gray-900">
                                <a href={publication.social_links.linkedin} target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline">
                                  {publication.social_links.linkedin}
                                </a>
                              </dd>
                            </div>
                          )}
                          {publication.social_links.instagram && (
                            <div>
                              <dt className="text-xs font-medium text-gray-400">Instagram</dt>
                              <dd className="text-sm text-gray-900">
                                <a href={publication.social_links.instagram} target="_blank" rel="noopener noreferrer" className="text-blue-600 hover:underline">
                                  {publication.social_links.instagram}
                                </a>
                              </dd>
                            </div>
                          )}
                        </div>
                      </div>
                    )}

                    {/* Custom CSS */}
                    {publication.custom_css && (
                      <div className="bg-gray-50 p-4 rounded-lg">
                        <h4 className="text-sm font-medium text-gray-500 mb-3">Custom CSS</h4>
                        <pre className="bg-white p-3 rounded border text-xs overflow-x-auto max-h-32">
                          {publication.custom_css}
                        </pre>
                      </div>
                    )}
                  </div>
                )}

                {/* Danger Zone */}
                {publication.user_role === 'owner' && (
                  <div className="border-t pt-6">
                    <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                      <h4 className="text-lg font-medium text-red-800 mb-2">Danger Zone</h4>
                      <p className="text-sm text-red-700 mb-4">
                        Once you delete a publication, there is no going back. Please be certain.
                      </p>
                      <button
                        onClick={handleDeletePublication}
                        className="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                      >
                        Delete Publication
                      </button>
                    </div>
                  </div>
                )}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default PublicationManagePage;