import React from 'react';
import { render, screen } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import '@testing-library/jest-dom';
import PublicationCard from '../PublicationCard';
import { Publication } from '../../types';

// Mock publication data
const mockPublication: Publication & {
  member_count?: number;
  article_count?: number;
  user_role?: string;
} = {
  id: 1,
  name: 'Test Publication',
  description: 'A test publication for unit testing',
  logo_url: 'https://example.com/logo.png',
  owner_id: 1,
  created_at: '2024-01-01T00:00:00Z',
  updated_at: '2024-01-01T00:00:00Z',
  member_count: 5,
  article_count: 10,
  user_role: 'admin'
};

const mockPublicationWithoutLogo: Publication & {
  member_count?: number;
  article_count?: number;
  user_role?: string;
} = {
  ...mockPublication,
  logo_url: undefined,
  description: undefined
};

// Wrapper component for router context
const RouterWrapper: React.FC<{ children: React.ReactNode }> = ({ children }) => (
  <BrowserRouter>{children}</BrowserRouter>
);

describe('PublicationCard', () => {
  it('renders publication information correctly', () => {
    render(
      <RouterWrapper>
        <PublicationCard publication={mockPublication} />
      </RouterWrapper>
    );

    expect(screen.getByText('Test Publication')).toBeInTheDocument();
    expect(screen.getByText('A test publication for unit testing')).toBeInTheDocument();
    expect(screen.getByText('5 members')).toBeInTheDocument();
    expect(screen.getByText('10 articles')).toBeInTheDocument();
    expect(screen.getByText('admin')).toBeInTheDocument();
  });

  it('renders publication logo when provided', () => {
    render(
      <RouterWrapper>
        <PublicationCard publication={mockPublication} />
      </RouterWrapper>
    );

    const logo = screen.getByAltText('Test Publication');
    expect(logo).toBeInTheDocument();
    expect(logo).toHaveAttribute('src', 'https://example.com/logo.png');
  });

  it('renders fallback logo when no logo URL provided', () => {
    render(
      <RouterWrapper>
        <PublicationCard publication={mockPublicationWithoutLogo} />
      </RouterWrapper>
    );

    expect(screen.getByText('T')).toBeInTheDocument(); // First letter fallback
  });

  it('handles missing description gracefully', () => {
    render(
      <RouterWrapper>
        <PublicationCard publication={mockPublicationWithoutLogo} />
      </RouterWrapper>
    );

    expect(screen.getByText('Test Publication')).toBeInTheDocument();
    expect(screen.queryByText('A test publication for unit testing')).not.toBeInTheDocument();
  });

  it('shows manage button when showManageButton is true', () => {
    render(
      <RouterWrapper>
        <PublicationCard publication={mockPublication} showManageButton={true} />
      </RouterWrapper>
    );

    expect(screen.getByText('Manage')).toBeInTheDocument();
  });

  it('hides manage button when showManageButton is false', () => {
    render(
      <RouterWrapper>
        <PublicationCard publication={mockPublication} showManageButton={false} />
      </RouterWrapper>
    );

    expect(screen.queryByText('Manage')).not.toBeInTheDocument();
  });

  it('renders correct publication link', () => {
    render(
      <RouterWrapper>
        <PublicationCard publication={mockPublication} />
      </RouterWrapper>
    );

    const publicationLink = screen.getByRole('link', { name: 'Test Publication' });
    expect(publicationLink).toHaveAttribute('href', '/publication/1');
  });

  it('renders correct manage link when manage button is shown', () => {
    render(
      <RouterWrapper>
        <PublicationCard publication={mockPublication} showManageButton={true} />
      </RouterWrapper>
    );

    const manageLink = screen.getByRole('link', { name: 'Manage' });
    expect(manageLink).toHaveAttribute('href', '/publication/1/manage');
  });

  it('handles singular/plural text correctly', () => {
    const singlePublication = {
      ...mockPublication,
      member_count: 1,
      article_count: 1
    };

    render(
      <RouterWrapper>
        <PublicationCard publication={singlePublication} />
      </RouterWrapper>
    );

    expect(screen.getByText('1 member')).toBeInTheDocument();
    expect(screen.getByText('1 article')).toBeInTheDocument();
  });

  it('formats creation date correctly', () => {
    render(
      <RouterWrapper>
        <PublicationCard publication={mockPublication} />
      </RouterWrapper>
    );

    expect(screen.getByText(/Created/)).toBeInTheDocument();
  });

  it('handles missing member and article counts', () => {
    const publicationWithoutCounts = {
      id: 1,
      name: 'Test Publication',
      owner_id: 1,
      created_at: '2024-01-01T00:00:00Z',
      updated_at: '2024-01-01T00:00:00Z'
    };

    render(
      <RouterWrapper>
        <PublicationCard publication={publicationWithoutCounts} />
      </RouterWrapper>
    );

    expect(screen.getByText('Test Publication')).toBeInTheDocument();
    expect(screen.queryByText(/members/)).not.toBeInTheDocument();
    expect(screen.queryByText(/articles/)).not.toBeInTheDocument();
  });
});