import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import '@testing-library/jest-dom';
import PublicationForm from '../PublicationForm';
import PublicationDashboard from '../PublicationDashboard';
import PublicationMemberList from '../PublicationMemberList';
import { AuthContext } from '../../context/AuthContext';

// Mock API calls
jest.mock('../../utils/api', () => ({
  get: jest.fn(),
  post: jest.fn(),
  put: jest.fn(),
  delete: jest.fn(),
}));

const mockApi = require('../../utils/api');

// Mock auth context
const mockAuthContext = {
  user: {
    id: 1,
    username: 'testuser',
    email: 'test@example.com'
  },
  token: 'mock-token',
  login: jest.fn(),
  logout: jest.fn(),
  loading: false
};

// Test wrapper component
const TestWrapper: React.FC<{ children: React.ReactNode }> = ({ children }) => (
  <BrowserRouter>
    <AuthContext.Provider value={mockAuthContext}>
      {children}
    </AuthContext.Provider>
  </BrowserRouter>
);

describe('Publication Management Components', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('PublicationForm', () => {
    const mockOnSubmit = jest.fn();
    const mockOnCancel = jest.fn();

    it('renders create form correctly', () => {
      render(
        <TestWrapper>
          <PublicationForm
            onSubmit={mockOnSubmit}
            onCancel={mockOnCancel}
          />
        </TestWrapper>
      );

      expect(screen.getByText('Create Publication')).toBeInTheDocument();
      expect(screen.getByLabelText(/publication name/i)).toBeInTheDocument();
      expect(screen.getByLabelText(/description/i)).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /create publication/i })).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /cancel/i })).toBeInTheDocument();
    });

    it('renders edit form with initial data', () => {
      const initialData = {
        id: 1,
        name: 'Test Publication',
        description: 'Test description',
        logo_url: 'https://example.com/logo.png'
      };

      render(
        <TestWrapper>
          <PublicationForm
            initialData={initialData}
            onSubmit={mockOnSubmit}
            onCancel={mockOnCancel}
          />
        </TestWrapper>
      );

      expect(screen.getByText('Edit Publication')).toBeInTheDocument();
      expect(screen.getByDisplayValue('Test Publication')).toBeInTheDocument();
      expect(screen.getByDisplayValue('Test description')).toBeInTheDocument();
      expect(screen.getByRole('button', { name: /update publication/i })).toBeInTheDocument();
    });

    it('validates required fields', async () => {
      render(
        <TestWrapper>
          <PublicationForm
            onSubmit={mockOnSubmit}
            onCancel={mockOnCancel}
          />
        </TestWrapper>
      );

      const submitButton = screen.getByRole('button', { name: /create publication/i });
      fireEvent.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText(/publication name is required/i)).toBeInTheDocument();
      });

      expect(mockOnSubmit).not.toHaveBeenCalled();
    });

    it('submits form with valid data', async () => {
      mockApi.post.mockResolvedValue({
        data: { success: true, data: { id: 1, name: 'New Publication' } }
      });

      render(
        <TestWrapper>
          <PublicationForm
            onSubmit={mockOnSubmit}
            onCancel={mockOnCancel}
          />
        </TestWrapper>
      );

      const nameInput = screen.getByLabelText(/publication name/i);
      const descriptionInput = screen.getByLabelText(/description/i);
      const submitButton = screen.getByRole('button', { name: /create publication/i });

      fireEvent.change(nameInput, { target: { value: 'New Publication' } });
      fireEvent.change(descriptionInput, { target: { value: 'New description' } });
      fireEvent.click(submitButton);

      await waitFor(() => {
        expect(mockApi.post).toHaveBeenCalledWith('/publications/create', {
          name: 'New Publication',
          description: 'New description',
          logo_url: ''
        });
      });

      expect(mockOnSubmit).toHaveBeenCalledWith({
        id: 1,
        name: 'New Publication'
      });
    });

    it('handles form submission errors', async () => {
      mockApi.post.mockRejectedValue({
        response: { data: { error: 'Publication name already exists' } }
      });

      render(
        <TestWrapper>
          <PublicationForm
            onSubmit={mockOnSubmit}
            onCancel={mockOnCancel}
          />
        </TestWrapper>
      );

      const nameInput = screen.getByLabelText(/publication name/i);
      const submitButton = screen.getByRole('button', { name: /create publication/i });

      fireEvent.change(nameInput, { target: { value: 'Existing Publication' } });
      fireEvent.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText(/publication name already exists/i)).toBeInTheDocument();
      });

      expect(mockOnSubmit).not.toHaveBeenCalled();
    });

    it('calls onCancel when cancel button is clicked', () => {
      render(
        <TestWrapper>
          <PublicationForm
            onSubmit={mockOnSubmit}
            onCancel={mockOnCancel}
          />
        </TestWrapper>
      );

      const cancelButton = screen.getByRole('button', { name: /cancel/i });
      fireEvent.click(cancelButton);

      expect(mockOnCancel).toHaveBeenCalled();
    });
  });

  describe('PublicationDashboard', () => {
    const mockPublication = {
      id: 1,
      name: 'Test Publication',
      description: 'Test description',
      owner_id: 1,
      owner_username: 'testuser',
      stats: {
        member_count: 5,
        published_articles: 10,
        draft_articles: 3,
        total_views: 1000,
        total_claps: 150,
        total_comments: 25
      },
      members: [
        { id: 1, username: 'testuser', role: 'owner' },
        { id: 2, username: 'writer1', role: 'writer' },
        { id: 3, username: 'editor1', role: 'editor' }
      ],
      recent_activity: [
        {
          activity_type: 'article_published',
          article_title: 'New Article',
          author_username: 'writer1',
          activity_date: '2023-12-01T10:00:00Z'
        }
      ]
    };

    beforeEach(() => {
      mockApi.get.mockResolvedValue({
        data: { success: true, data: mockPublication }
      });
    });

    it('renders publication dashboard correctly', async () => {
      render(
        <TestWrapper>
          <PublicationDashboard publicationId={1} />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(screen.getByText('Test Publication')).toBeInTheDocument();
      });

      expect(screen.getByText('Test description')).toBeInTheDocument();
      expect(screen.getByText('5')).toBeInTheDocument(); // member count
      expect(screen.getByText('10')).toBeInTheDocument(); // published articles
      expect(screen.getByText('1,000')).toBeInTheDocument(); // total views
    });

    it('displays statistics correctly', async () => {
      render(
        <TestWrapper>
          <PublicationDashboard publicationId={1} />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(screen.getByText(/members/i)).toBeInTheDocument();
      });

      expect(screen.getByText(/published articles/i)).toBeInTheDocument();
      expect(screen.getByText(/draft articles/i)).toBeInTheDocument();
      expect(screen.getByText(/total views/i)).toBeInTheDocument();
      expect(screen.getByText(/total claps/i)).toBeInTheDocument();
    });

    it('shows recent activity', async () => {
      render(
        <TestWrapper>
          <PublicationDashboard publicationId={1} />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(screen.getByText(/recent activity/i)).toBeInTheDocument();
      });

      expect(screen.getByText('New Article')).toBeInTheDocument();
      expect(screen.getByText('writer1')).toBeInTheDocument();
    });

    it('handles loading state', () => {
      mockApi.get.mockImplementation(() => new Promise(() => {})); // Never resolves

      render(
        <TestWrapper>
          <PublicationDashboard publicationId={1} />
        </TestWrapper>
      );

      expect(screen.getByText(/loading/i)).toBeInTheDocument();
    });

    it('handles error state', async () => {
      mockApi.get.mockRejectedValue({
        response: { data: { error: 'Publication not found' } }
      });

      render(
        <TestWrapper>
          <PublicationDashboard publicationId={1} />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(screen.getByText(/publication not found/i)).toBeInTheDocument();
      });
    });
  });

  describe('PublicationMemberList', () => {
    const mockMembers = [
      {
        id: 1,
        username: 'owner',
        email: 'owner@example.com',
        role: 'owner',
        profile_image_url: null,
        joined_at: '2023-01-01T00:00:00Z'
      },
      {
        id: 2,
        username: 'editor1',
        email: 'editor@example.com',
        role: 'editor',
        profile_image_url: 'https://example.com/avatar.jpg',
        joined_at: '2023-06-01T00:00:00Z'
      },
      {
        id: 3,
        username: 'writer1',
        email: 'writer@example.com',
        role: 'writer',
        profile_image_url: null,
        joined_at: '2023-11-01T00:00:00Z'
      }
    ];

    const mockOnInvite = jest.fn();
    const mockOnRemove = jest.fn();
    const mockOnRoleChange = jest.fn();

    it('renders member list correctly', () => {
      render(
        <TestWrapper>
          <PublicationMemberList
            members={mockMembers}
            currentUserId={1}
            canManage={true}
            onInvite={mockOnInvite}
            onRemove={mockOnRemove}
            onRoleChange={mockOnRoleChange}
          />
        </TestWrapper>
      );

      expect(screen.getByText('Members (3)')).toBeInTheDocument();
      expect(screen.getByText('owner')).toBeInTheDocument();
      expect(screen.getByText('editor1')).toBeInTheDocument();
      expect(screen.getByText('writer1')).toBeInTheDocument();
    });

    it('displays member roles correctly', () => {
      render(
        <TestWrapper>
          <PublicationMemberList
            members={mockMembers}
            currentUserId={1}
            canManage={true}
            onInvite={mockOnInvite}
            onRemove={mockOnRemove}
            onRoleChange={mockOnRoleChange}
          />
        </TestWrapper>
      );

      expect(screen.getByText('Owner')).toBeInTheDocument();
      expect(screen.getByText('Editor')).toBeInTheDocument();
      expect(screen.getByText('Writer')).toBeInTheDocument();
    });

    it('shows invite button for managers', () => {
      render(
        <TestWrapper>
          <PublicationMemberList
            members={mockMembers}
            currentUserId={1}
            canManage={true}
            onInvite={mockOnInvite}
            onRemove={mockOnRemove}
            onRoleChange={mockOnRoleChange}
          />
        </TestWrapper>
      );

      expect(screen.getByRole('button', { name: /invite member/i })).toBeInTheDocument();
    });

    it('hides management buttons for non-managers', () => {
      render(
        <TestWrapper>
          <PublicationMemberList
            members={mockMembers}
            currentUserId={2}
            canManage={false}
            onInvite={mockOnInvite}
            onRemove={mockOnRemove}
            onRoleChange={mockOnRoleChange}
          />
        </TestWrapper>
      );

      expect(screen.queryByRole('button', { name: /invite member/i })).not.toBeInTheDocument();
      expect(screen.queryByRole('button', { name: /remove/i })).not.toBeInTheDocument();
    });

    it('handles member invitation', async () => {
      mockApi.post.mockResolvedValue({
        data: { success: true, data: { member: { id: 4, username: 'newwriter', role: 'writer' } } }
      });

      render(
        <TestWrapper>
          <PublicationMemberList
            members={mockMembers}
            currentUserId={1}
            canManage={true}
            onInvite={mockOnInvite}
            onRemove={mockOnRemove}
            onRoleChange={mockOnRoleChange}
          />
        </TestWrapper>
      );

      const inviteButton = screen.getByRole('button', { name: /invite member/i });
      fireEvent.click(inviteButton);

      // Fill in invitation form
      const emailInput = screen.getByLabelText(/email/i);
      const roleSelect = screen.getByLabelText(/role/i);
      const sendButton = screen.getByRole('button', { name: /send invitation/i });

      fireEvent.change(emailInput, { target: { value: 'newwriter@example.com' } });
      fireEvent.change(roleSelect, { target: { value: 'writer' } });
      fireEvent.click(sendButton);

      await waitFor(() => {
        expect(mockApi.post).toHaveBeenCalledWith('/publications/invite', {
          publication_id: expect.any(Number),
          email: 'newwriter@example.com',
          role: 'writer'
        });
      });

      expect(mockOnInvite).toHaveBeenCalledWith({
        id: 4,
        username: 'newwriter',
        role: 'writer'
      });
    });

    it('handles member removal', async () => {
      mockApi.post.mockResolvedValue({
        data: { success: true }
      });

      render(
        <TestWrapper>
          <PublicationMemberList
            members={mockMembers}
            currentUserId={1}
            canManage={true}
            onInvite={mockOnInvite}
            onRemove={mockOnRemove}
            onRoleChange={mockOnRoleChange}
          />
        </TestWrapper>
      );

      // Find remove button for writer1 (should be visible for managers)
      const removeButtons = screen.getAllByRole('button', { name: /remove/i });
      fireEvent.click(removeButtons[0]);

      // Confirm removal
      const confirmButton = screen.getByRole('button', { name: /confirm/i });
      fireEvent.click(confirmButton);

      await waitFor(() => {
        expect(mockApi.post).toHaveBeenCalledWith('/publications/remove-member', {
          publication_id: expect.any(Number),
          user_id: expect.any(Number)
        });
      });

      expect(mockOnRemove).toHaveBeenCalled();
    });

    it('handles role changes', async () => {
      mockApi.post.mockResolvedValue({
        data: { success: true }
      });

      render(
        <TestWrapper>
          <PublicationMemberList
            members={mockMembers}
            currentUserId={1}
            canManage={true}
            onInvite={mockOnInvite}
            onRemove={mockOnRemove}
            onRoleChange={mockOnRoleChange}
          />
        </TestWrapper>
      );

      // Find role select for writer1
      const roleSelects = screen.getAllByDisplayValue(/writer|editor/i);
      fireEvent.change(roleSelects[0], { target: { value: 'editor' } });

      await waitFor(() => {
        expect(mockApi.post).toHaveBeenCalledWith('/publications/update-role', {
          publication_id: expect.any(Number),
          user_id: expect.any(Number),
          role: 'editor'
        });
      });

      expect(mockOnRoleChange).toHaveBeenCalled();
    });

    it('prevents owner from being removed or role changed', () => {
      render(
        <TestWrapper>
          <PublicationMemberList
            members={mockMembers}
            currentUserId={1}
            canManage={true}
            onInvite={mockOnInvite}
            onRemove={mockOnRemove}
            onRoleChange={mockOnRoleChange}
          />
        </TestWrapper>
      );

      // Owner should not have remove button or role select
      const ownerRow = screen.getByText('owner').closest('tr');
      expect(ownerRow).not.toHaveTextContent('Remove');
      
      // Role should be displayed as text, not select
      expect(screen.getByText('Owner')).toBeInTheDocument();
    });
  });
});