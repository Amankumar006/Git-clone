import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import '@testing-library/jest-dom';
import ArticleSubmissionDialog from '../ArticleSubmissionDialog';
import PendingArticlesList from '../PendingArticlesList';
import PublishDialog from '../PublishDialog';
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

describe('Publication Workflow Components', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  describe('ArticleSubmissionDialog', () => {
    const mockArticle = {
      id: 1,
      title: 'Test Article',
      subtitle: 'Test subtitle',
      content: 'Test content',
      author_id: 1,
      status: 'draft'
    };

    const mockPublications = [
      { id: 1, name: 'Tech Publication', role: 'writer' },
      { id: 2, name: 'Science Publication', role: 'editor' },
      { id: 3, name: 'My Publication', role: 'owner' }
    ];

    const mockOnSubmit = jest.fn();
    const mockOnClose = jest.fn();

    beforeEach(() => {
      mockApi.get.mockResolvedValue({
        data: { success: true, data: { owned: [], member: mockPublications } }
      });
    });

    it('renders submission dialog correctly', async () => {
      render(
        <TestWrapper>
          <ArticleSubmissionDialog
            article={mockArticle}
            isOpen={true}
            onSubmit={mockOnSubmit}
            onClose={mockOnClose}
          />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(screen.getByText('Submit to Publication')).toBeInTheDocument();
      });

      expect(screen.getByText('Test Article')).toBeInTheDocument();
      expect(screen.getByText(/select a publication/i)).toBeInTheDocument();
    });

    it('loads user publications', async () => {
      render(
        <TestWrapper>
          <ArticleSubmissionDialog
            article={mockArticle}
            isOpen={true}
            onSubmit={mockOnSubmit}
            onClose={mockOnClose}
          />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(screen.getByText('Tech Publication')).toBeInTheDocument();
      });

      expect(screen.getByText('Science Publication')).toBeInTheDocument();
      expect(screen.getByText('My Publication')).toBeInTheDocument();
    });

    it('shows user roles for each publication', async () => {
      render(
        <TestWrapper>
          <ArticleSubmissionDialog
            article={mockArticle}
            isOpen={true}
            onSubmit={mockOnSubmit}
            onClose={mockOnClose}
          />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(screen.getByText('Writer')).toBeInTheDocument();
      });

      expect(screen.getByText('Editor')).toBeInTheDocument();
      expect(screen.getByText('Owner')).toBeInTheDocument();
    });

    it('handles publication selection and submission', async () => {
      mockApi.post.mockResolvedValue({
        data: { success: true }
      });

      render(
        <TestWrapper>
          <ArticleSubmissionDialog
            article={mockArticle}
            isOpen={true}
            onSubmit={mockOnSubmit}
            onClose={mockOnClose}
          />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(screen.getByText('Tech Publication')).toBeInTheDocument();
      });

      // Select a publication
      const publicationOption = screen.getByText('Tech Publication').closest('div');
      fireEvent.click(publicationOption!);

      // Submit
      const submitButton = screen.getByRole('button', { name: /submit article/i });
      fireEvent.click(submitButton);

      await waitFor(() => {
        expect(mockApi.post).toHaveBeenCalledWith('/articles/submit-to-publication', {
          article_id: 1,
          publication_id: 1
        });
      });

      expect(mockOnSubmit).toHaveBeenCalledWith(1);
    });

    it('requires publication selection', async () => {
      render(
        <TestWrapper>
          <ArticleSubmissionDialog
            article={mockArticle}
            isOpen={true}
            onSubmit={mockOnSubmit}
            onClose={mockOnClose}
          />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(screen.getByRole('button', { name: /submit article/i })).toBeInTheDocument();
      });

      const submitButton = screen.getByRole('button', { name: /submit article/i });
      fireEvent.click(submitButton);

      expect(screen.getByText(/please select a publication/i)).toBeInTheDocument();
      expect(mockOnSubmit).not.toHaveBeenCalled();
    });

    it('handles submission errors', async () => {
      mockApi.post.mockRejectedValue({
        response: { data: { error: 'Article already submitted to this publication' } }
      });

      render(
        <TestWrapper>
          <ArticleSubmissionDialog
            article={mockArticle}
            isOpen={true}
            onSubmit={mockOnSubmit}
            onClose={mockOnClose}
          />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(screen.getByText('Tech Publication')).toBeInTheDocument();
      });

      const publicationOption = screen.getByText('Tech Publication').closest('div');
      fireEvent.click(publicationOption!);

      const submitButton = screen.getByRole('button', { name: /submit article/i });
      fireEvent.click(submitButton);

      await waitFor(() => {
        expect(screen.getByText(/article already submitted/i)).toBeInTheDocument();
      });

      expect(mockOnSubmit).not.toHaveBeenCalled();
    });

    it('closes dialog when close button is clicked', () => {
      render(
        <TestWrapper>
          <ArticleSubmissionDialog
            article={mockArticle}
            isOpen={true}
            onSubmit={mockOnSubmit}
            onClose={mockOnClose}
          />
        </TestWrapper>
      );

      const closeButton = screen.getByRole('button', { name: /close/i });
      fireEvent.click(closeButton);

      expect(mockOnClose).toHaveBeenCalled();
    });
  });

  describe('PendingArticlesList', () => {
    const mockPendingArticles = [
      {
        id: 1,
        title: 'Pending Article 1',
        subtitle: 'First pending article',
        author_username: 'writer1',
        author_avatar: null,
        created_at: '2023-12-01T10:00:00Z',
        reading_time: 5
      },
      {
        id: 2,
        title: 'Pending Article 2',
        subtitle: 'Second pending article',
        author_username: 'writer2',
        author_avatar: 'https://example.com/avatar.jpg',
        created_at: '2023-12-02T15:30:00Z',
        reading_time: 8
      }
    ];

    const mockOnApprove = jest.fn();
    const mockOnReject = jest.fn();

    beforeEach(() => {
      mockApi.get.mockResolvedValue({
        data: { success: true, data: mockPendingArticles }
      });
    });

    it('renders pending articles list correctly', async () => {
      render(
        <TestWrapper>
          <PendingArticlesList
            publicationId={1}
            onApprove={mockOnApprove}
            onReject={mockOnReject}
          />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(screen.getByText('Pending Articles (2)')).toBeInTheDocument();
      });

      expect(screen.getByText('Pending Article 1')).toBeInTheDocument();
      expect(screen.getByText('Pending Article 2')).toBeInTheDocument();
      expect(screen.getByText('writer1')).toBeInTheDocument();
      expect(screen.getByText('writer2')).toBeInTheDocument();
    });

    it('displays article metadata correctly', async () => {
      render(
        <TestWrapper>
          <PendingArticlesList
            publicationId={1}
            onApprove={mockOnApprove}
            onReject={mockOnReject}
          />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(screen.getByText('5 min read')).toBeInTheDocument();
      });

      expect(screen.getByText('8 min read')).toBeInTheDocument();
      expect(screen.getByText('First pending article')).toBeInTheDocument();
      expect(screen.getByText('Second pending article')).toBeInTheDocument();
    });

    it('shows approve and reject buttons', async () => {
      render(
        <TestWrapper>
          <PendingArticlesList
            publicationId={1}
            onApprove={mockOnApprove}
            onReject={mockOnReject}
          />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(screen.getAllByRole('button', { name: /approve/i })).toHaveLength(2);
      });

      expect(screen.getAllByRole('button', { name: /reject/i })).toHaveLength(2);
    });

    it('handles article approval', async () => {
      mockApi.post.mockResolvedValue({
        data: { success: true }
      });

      render(
        <TestWrapper>
          <PendingArticlesList
            publicationId={1}
            onApprove={mockOnApprove}
            onReject={mockOnReject}
          />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(screen.getAllByRole('button', { name: /approve/i })).toHaveLength(2);
      });

      const approveButtons = screen.getAllByRole('button', { name: /approve/i });
      fireEvent.click(approveButtons[0]);

      await waitFor(() => {
        expect(mockApi.post).toHaveBeenCalledWith('/articles/approve-for-publication', {
          article_id: 1
        });
      });

      expect(mockOnApprove).toHaveBeenCalledWith(1);
    });

    it('handles article rejection', async () => {
      mockApi.post.mockResolvedValue({
        data: { success: true }
      });

      render(
        <TestWrapper>
          <PendingArticlesList
            publicationId={1}
            onApprove={mockOnApprove}
            onReject={mockOnReject}
          />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(screen.getAllByRole('button', { name: /reject/i })).toHaveLength(2);
      });

      const rejectButtons = screen.getAllByRole('button', { name: /reject/i });
      fireEvent.click(rejectButtons[0]);

      // Confirm rejection
      const confirmButton = screen.getByRole('button', { name: /confirm/i });
      fireEvent.click(confirmButton);

      await waitFor(() => {
        expect(mockApi.post).toHaveBeenCalledWith('/articles/reject-submission', {
          article_id: 1
        });
      });

      expect(mockOnReject).toHaveBeenCalledWith(1);
    });

    it('shows empty state when no pending articles', async () => {
      mockApi.get.mockResolvedValue({
        data: { success: true, data: [] }
      });

      render(
        <TestWrapper>
          <PendingArticlesList
            publicationId={1}
            onApprove={mockOnApprove}
            onReject={mockOnReject}
          />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(screen.getByText(/no pending articles/i)).toBeInTheDocument();
      });
    });

    it('handles loading state', () => {
      mockApi.get.mockImplementation(() => new Promise(() => {})); // Never resolves

      render(
        <TestWrapper>
          <PendingArticlesList
            publicationId={1}
            onApprove={mockOnApprove}
            onReject={mockOnReject}
          />
        </TestWrapper>
      );

      expect(screen.getByText(/loading/i)).toBeInTheDocument();
    });

    it('handles error state', async () => {
      mockApi.get.mockRejectedValue({
        response: { data: { error: 'Failed to load pending articles' } }
      });

      render(
        <TestWrapper>
          <PendingArticlesList
            publicationId={1}
            onApprove={mockOnApprove}
            onReject={mockOnReject}
          />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(screen.getByText(/failed to load/i)).toBeInTheDocument();
      });
    });
  });

  describe('PublishDialog Integration', () => {
    const mockArticle = {
      id: 1,
      title: 'Test Article',
      subtitle: 'Test subtitle',
      content: 'Test content',
      author_id: 1,
      status: 'draft',
      tags: ['tech', 'javascript']
    };

    const mockOnPublish = jest.fn();
    const mockOnClose = jest.fn();

    it('includes publication selection in publish dialog', async () => {
      mockApi.get.mockResolvedValue({
        data: { success: true, data: { owned: [], member: [] } }
      });

      render(
        <TestWrapper>
          <PublishDialog
            article={mockArticle}
            isOpen={true}
            onPublish={mockOnPublish}
            onClose={mockOnClose}
          />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(screen.getByText(/publish article/i)).toBeInTheDocument();
      });

      expect(screen.getByText(/publication/i)).toBeInTheDocument();
      expect(screen.getByText(/publish independently/i)).toBeInTheDocument();
    });

    it('allows publishing to selected publication', async () => {
      const mockPublications = [
        { id: 1, name: 'Tech Publication', role: 'writer' }
      ];

      mockApi.get.mockResolvedValue({
        data: { success: true, data: { owned: [], member: mockPublications } }
      });

      mockApi.post.mockResolvedValue({
        data: { success: true, data: { id: 1, status: 'published' } }
      });

      render(
        <TestWrapper>
          <PublishDialog
            article={mockArticle}
            isOpen={true}
            onPublish={mockOnPublish}
            onClose={mockOnClose}
          />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(screen.getByText('Tech Publication')).toBeInTheDocument();
      });

      // Select publication
      const publicationOption = screen.getByLabelText('Tech Publication');
      fireEvent.click(publicationOption);

      // Publish
      const publishButton = screen.getByRole('button', { name: /publish/i });
      fireEvent.click(publishButton);

      await waitFor(() => {
        expect(mockApi.post).toHaveBeenCalledWith('/articles/publish', {
          id: 1,
          publication_id: 1,
          status: 'published'
        });
      });

      expect(mockOnPublish).toHaveBeenCalled();
    });

    it('allows independent publishing', async () => {
      mockApi.get.mockResolvedValue({
        data: { success: true, data: { owned: [], member: [] } }
      });

      mockApi.post.mockResolvedValue({
        data: { success: true, data: { id: 1, status: 'published' } }
      });

      render(
        <TestWrapper>
          <PublishDialog
            article={mockArticle}
            isOpen={true}
            onPublish={mockOnPublish}
            onClose={mockOnClose}
          />
        </TestWrapper>
      );

      await waitFor(() => {
        expect(screen.getByLabelText(/publish independently/i)).toBeInTheDocument();
      });

      // Select independent publishing
      const independentOption = screen.getByLabelText(/publish independently/i);
      fireEvent.click(independentOption);

      // Publish
      const publishButton = screen.getByRole('button', { name: /publish/i });
      fireEvent.click(publishButton);

      await waitFor(() => {
        expect(mockApi.post).toHaveBeenCalledWith('/articles/publish', {
          id: 1,
          publication_id: null,
          status: 'published'
        });
      });

      expect(mockOnPublish).toHaveBeenCalled();
    });
  });
});