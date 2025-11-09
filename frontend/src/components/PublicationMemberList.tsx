import React, { useState } from 'react';
import { PublicationMember, User } from '../types';

interface PublicationMemberListProps {
  members: (PublicationMember & { user: User })[];
  currentUserId: number;
  canManageMembers: boolean;
  onRoleChange: (userId: number, role: 'admin' | 'editor' | 'writer') => void;
  onRemoveMember: (userId: number) => void;
  onInviteMember: (email: string, role: 'admin' | 'editor' | 'writer') => void;
}

const PublicationMemberList: React.FC<PublicationMemberListProps> = ({
  members,
  currentUserId,
  canManageMembers,
  onRoleChange,
  onRemoveMember,
  onInviteMember
}) => {
  const [showInviteForm, setShowInviteForm] = useState(false);
  const [inviteEmail, setInviteEmail] = useState('');
  const [inviteRole, setInviteRole] = useState<'admin' | 'editor' | 'writer'>('writer');
  const [isInviting, setIsInviting] = useState(false);

  const handleInvite = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!inviteEmail.trim()) return;

    setIsInviting(true);
    try {
      await onInviteMember(inviteEmail, inviteRole);
      setInviteEmail('');
      setInviteRole('writer');
      setShowInviteForm(false);
    } finally {
      setIsInviting(false);
    }
  };

  const getRoleBadgeColor = (role: string) => {
    switch (role) {
      case 'admin':
        return 'bg-red-100 text-red-800';
      case 'editor':
        return 'bg-yellow-100 text-yellow-800';
      case 'writer':
        return 'bg-green-100 text-green-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h3 className="text-lg font-medium text-gray-900">
          Members ({members.length})
        </h3>
        {canManageMembers && (
          <button
            onClick={() => setShowInviteForm(!showInviteForm)}
            className="px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Invite Member
          </button>
        )}
      </div>

      {showInviteForm && (
        <div className="bg-gray-50 p-4 rounded-lg">
          <form onSubmit={handleInvite} className="space-y-4">
            <div>
              <label htmlFor="invite-email" className="block text-sm font-medium text-gray-700">
                Email Address
              </label>
              <input
                type="email"
                id="invite-email"
                value={inviteEmail}
                onChange={(e) => setInviteEmail(e.target.value)}
                className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                placeholder="Enter email address"
                required
              />
            </div>
            <div>
              <label htmlFor="invite-role" className="block text-sm font-medium text-gray-700">
                Role
              </label>
              <select
                id="invite-role"
                value={inviteRole}
                onChange={(e) => setInviteRole(e.target.value as 'admin' | 'editor' | 'writer')}
                className="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="writer">Writer</option>
                <option value="editor">Editor</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            <div className="flex justify-end space-x-2">
              <button
                type="button"
                onClick={() => setShowInviteForm(false)}
                className="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                disabled={isInviting}
              >
                Cancel
              </button>
              <button
                type="submit"
                className="px-3 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50"
                disabled={isInviting}
              >
                {isInviting ? 'Inviting...' : 'Send Invite'}
              </button>
            </div>
          </form>
        </div>
      )}

      <div className="space-y-3">
        {members.map((member) => (
          <div
            key={member.user_id}
            className="flex items-center justify-between p-4 bg-white border border-gray-200 rounded-lg"
          >
            <div className="flex items-center space-x-3">
              {member.user.profile_image_url ? (
                <img
                  src={member.user.profile_image_url}
                  alt={member.user.username}
                  className="w-10 h-10 rounded-full object-cover"
                />
              ) : (
                <div className="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center">
                  <span className="text-sm font-medium text-gray-700">
                    {member.user.username.charAt(0).toUpperCase()}
                  </span>
                </div>
              )}
              <div>
                <p className="text-sm font-medium text-gray-900">
                  {member.user.username}
                  {member.user_id === currentUserId && (
                    <span className="ml-2 text-xs text-gray-500">(You)</span>
                  )}
                </p>
                <p className="text-sm text-gray-500">{member.user.email}</p>
              </div>
            </div>

            <div className="flex items-center space-x-3">
              <span className={`px-2 py-1 text-xs font-medium rounded-full ${getRoleBadgeColor(member.role)}`}>
                {member.role}
              </span>

              {canManageMembers && member.user_id !== currentUserId && (
                <div className="flex items-center space-x-2">
                  <select
                    value={member.role}
                    onChange={(e) => onRoleChange(member.user_id, e.target.value as 'admin' | 'editor' | 'writer')}
                    className="text-sm border border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-1 focus:ring-blue-500"
                  >
                    <option value="writer">Writer</option>
                    <option value="editor">Editor</option>
                    <option value="admin">Admin</option>
                  </select>
                  <button
                    onClick={() => onRemoveMember(member.user_id)}
                    className="text-red-600 hover:text-red-800 text-sm font-medium"
                  >
                    Remove
                  </button>
                </div>
              )}
            </div>
          </div>
        ))}
      </div>

      {members.length === 0 && (
        <div className="text-center py-8 text-gray-500">
          No members yet. Invite some writers to get started!
        </div>
      )}
    </div>
  );
};

export default PublicationMemberList;