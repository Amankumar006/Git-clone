import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import PublicationForm, { PublicationFormData } from '../PublicationForm';
import { Publication } from '../../types';

// Mock the ImageUpload component
jest.mock('../ImageUpload', () => {
  return function MockImageUpload({ onImageUpload, onImageRemove, currentImage }: any) {
    return (
      <div data-testid="image-upload">
        {currentImage && <img src={currentImage} alt="Current" />}
        <button onClick={() => onImageUpload('https://example.com/new-logo.png')}>
          Upload Image
        </button>
        <button onClick={onImageRemove}>Remove Image</button>
      </div>
    );
  };
});

const mockPublication: Publication = {
  id: 1,
  name: 'Existing Publication',
  description: 'An existing publication for testing',
  logo_url: 'https://example.com/existing-logo.png',
  owner_id: 1,
  created_at: '2024-01-01T00:00:00Z',
  updated_at: '2024-01-01T00:00:00Z'
};

describe('PublicationForm', () => {
  const mockOnSubmit = jest.fn();
  const mockOnCancel = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders form fields correctly', () => {
    render(
      <PublicationForm
        onSubmit={mockOnSubmit}
        onCancel={mockOnCancel}
      />
    );

    expect(screen.getByLabelText(/Publication Name/)).toBeInTheDocument();
    expect(screen.getByLabelText(/Description/)).toBeInTheDocument();
    expect(screen.getByText(/Publication Logo/)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Create Publication/ })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Cancel/ })).toBeInTheDocument();
  });

  it('populates form with existing publication data', () => {
    render(
      <PublicationForm
        publication={mockPublication}
        onSubmit={mockOnSubmit}
        onCancel={mockOnCancel}
      />
    );

    expect(screen.getByDisplayValue('Existing Publication')).toBeInTheDocument();
    expect(screen.getByDisplayValue('An existing publication for testing')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Update Publication/ })).toBeInTheDocument();
  });

  it('validates required fields', async () => {
    const user = userEvent.setup();
    
    render(
      <PublicationForm
        onSubmit={mockOnSubmit}
        onCancel={mockOnCancel}
      />
    );

    const submitButton = screen.getByRole('button', { name: /Create Publication/ });
    await user.click(submitButton);

    expect(screen.getByText('Publication name is required')).toBeInTheDocument();
    expect(mockOnSubmit).not.toHaveBeenCalled();
  });

  it('validates name length', async () => {
    const user = userEvent.setup();
    
    render(
      <PublicationForm
        onSubmit={mockOnSubmit}
        onCancel={mockOnCancel}
      />
    );

    const nameInput = screen.getByLabelText(/Publication Name/);
    await user.type(nameInput, 'a'.repeat(101)); // Exceed 100 character limit

    const submitButton = screen.getByRole('button', { name: /Create Publication/ });
    await user.click(submitButton);

    expect(screen.getByText('Publication name must be less than 100 characters')).toBeInTheDocument();
    expect(mockOnSubmit).not.toHaveBeenCalled();
  });

  it('validates description length', async () => {
    const user = userEvent.setup();
    
    render(
      <PublicationForm
        onSubmit={mockOnSubmit}
        onCancel={mockOnCancel}
      />
    );

    const nameInput = screen.getByLabelText(/Publication Name/);
    const descriptionInput = screen.getByLabelText(/Description/);
    
    await user.type(nameInput, 'Valid Name');
    await user.type(descriptionInput, 'a'.repeat(1001)); // Exceed 1000 character limit

    const submitButton = screen.getByRole('button', { name: /Create Publication/ });
    await user.click(submitButton);

    expect(screen.getByText('Description must be less than 1000 characters')).toBeInTheDocument();
    expect(mockOnSubmit).not.toHaveBeenCalled();
  });

  it('submits form with valid data', async () => {
    const user = userEvent.setup();
    
    render(
      <PublicationForm
        onSubmit={mockOnSubmit}
        onCancel={mockOnCancel}
      />
    );

    const nameInput = screen.getByLabelText(/Publication Name/);
    const descriptionInput = screen.getByLabelText(/Description/);
    
    await user.type(nameInput, 'New Publication');
    await user.type(descriptionInput, 'A new publication for testing');

    const submitButton = screen.getByRole('button', { name: /Create Publication/ });
    await user.click(submitButton);

    await waitFor(() => {
      expect(mockOnSubmit).toHaveBeenCalledWith({
        name: 'New Publication',
        description: 'A new publication for testing',
        logo_url: ''
      });
    });
  });

  it('handles image upload', async () => {
    const user = userEvent.setup();
    
    render(
      <PublicationForm
        onSubmit={mockOnSubmit}
        onCancel={mockOnCancel}
      />
    );

    const uploadButton = screen.getByText('Upload Image');
    await user.click(uploadButton);

    const nameInput = screen.getByLabelText(/Publication Name/);
    await user.type(nameInput, 'Test Publication');

    const submitButton = screen.getByRole('button', { name: /Create Publication/ });
    await user.click(submitButton);

    await waitFor(() => {
      expect(mockOnSubmit).toHaveBeenCalledWith({
        name: 'Test Publication',
        description: '',
        logo_url: 'https://example.com/new-logo.png'
      });
    });
  });

  it('handles image removal', async () => {
    const user = userEvent.setup();
    
    render(
      <PublicationForm
        publication={mockPublication}
        onSubmit={mockOnSubmit}
        onCancel={mockOnCancel}
      />
    );

    const removeButton = screen.getByText('Remove Image');
    await user.click(removeButton);

    const submitButton = screen.getByRole('button', { name: /Update Publication/ });
    await user.click(submitButton);

    await waitFor(() => {
      expect(mockOnSubmit).toHaveBeenCalledWith({
        name: 'Existing Publication',
        description: 'An existing publication for testing',
        logo_url: ''
      });
    });
  });

  it('calls onCancel when cancel button is clicked', async () => {
    const user = userEvent.setup();
    
    render(
      <PublicationForm
        onSubmit={mockOnSubmit}
        onCancel={mockOnCancel}
      />
    );

    const cancelButton = screen.getByRole('button', { name: /Cancel/ });
    await user.click(cancelButton);

    expect(mockOnCancel).toHaveBeenCalled();
  });

  it('shows loading state', () => {
    render(
      <PublicationForm
        onSubmit={mockOnSubmit}
        onCancel={mockOnCancel}
        isLoading={true}
      />
    );

    expect(screen.getByText('Saving...')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /Saving.../ })).toBeDisabled();
    expect(screen.getByRole('button', { name: /Cancel/ })).toBeDisabled();
  });

  it('clears errors when user starts typing', async () => {
    const user = userEvent.setup();
    
    render(
      <PublicationForm
        onSubmit={mockOnSubmit}
        onCancel={mockOnCancel}
      />
    );

    // Trigger validation error
    const submitButton = screen.getByRole('button', { name: /Create Publication/ });
    await user.click(submitButton);

    expect(screen.getByText('Publication name is required')).toBeInTheDocument();

    // Start typing to clear error
    const nameInput = screen.getByLabelText(/Publication Name/);
    await user.type(nameInput, 'T');

    expect(screen.queryByText('Publication name is required')).not.toBeInTheDocument();
  });

  it('shows character count for description', () => {
    render(
      <PublicationForm
        publication={mockPublication}
        onSubmit={mockOnSubmit}
        onCancel={mockOnCancel}
      />
    );

    expect(screen.getByText('39/1000 characters')).toBeInTheDocument();
  });

  it('updates character count as user types', async () => {
    const user = userEvent.setup();
    
    render(
      <PublicationForm
        onSubmit={mockOnSubmit}
        onCancel={mockOnCancel}
      />
    );

    const descriptionInput = screen.getByLabelText(/Description/);
    await user.type(descriptionInput, 'Hello');

    expect(screen.getByText('5/1000 characters')).toBeInTheDocument();
  });
});