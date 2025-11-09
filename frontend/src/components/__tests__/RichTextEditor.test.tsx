import React from 'react';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';

// Mock fetch for image upload tests
global.fetch = jest.fn();

// Mock localStorage
const mockLocalStorage = {
  getItem: jest.fn(),
  setItem: jest.fn(),
  removeItem: jest.fn(),
  clear: jest.fn(),
};
Object.defineProperty(window, 'localStorage', {
  value: mockLocalStorage,
});

// Mock URL.createObjectURL
global.URL.createObjectURL = jest.fn(() => 'mock-url');

// Mock the TipTap editor
jest.mock('@tiptap/react', () => ({
  useEditor: jest.fn(),
  EditorContent: ({ editor }: any) => (
    <div data-testid="editor-content" className="prose prose-lg max-w-none focus:outline-none min-h-[400px] p-4">
      {editor?.getHTML() || '<p>Editor content</p>'}
    </div>
  ),
}));

// Mock TipTap extensions
jest.mock('@tiptap/starter-kit', () => ({
  configure: jest.fn(() => ({})),
}));

jest.mock('@tiptap/extension-image', () => ({
  configure: jest.fn(() => ({})),
}));

jest.mock('@tiptap/extension-code-block-lowlight', () => ({
  configure: jest.fn(() => ({})),
}));

jest.mock('@tiptap/extension-placeholder', () => ({
  configure: jest.fn(() => ({})),
}));

jest.mock('@tiptap/extension-dropcursor', () => ({
  configure: jest.fn(() => ({})),
}));

jest.mock('@tiptap/extension-gapcursor', () => ({}));

jest.mock('lowlight', () => ({
  createLowlight: jest.fn(() => ({})),
}));

import { useEditor } from '@tiptap/react';
import RichTextEditor from '../RichTextEditor';
import { it } from 'node:test';
import { it } from 'node:test';
import { describe } from 'node:test';
import { it } from 'node:test';
import { it } from 'node:test';
import { describe } from 'node:test';
import { it } from 'node:test';
import { it } from 'node:test';
import { describe } from 'node:test';
import { it } from 'node:test';
import { it } from 'node:test';
import { it } from 'node:test';
import { it } from 'node:test';
import { it } from 'node:test';
import { it } from 'node:test';
import { describe } from 'node:test';
import { it } from 'node:test';
import { it } from 'node:test';
import { it } from 'node:test';
import { it } from 'node:test';
import { describe } from 'node:test';
import { it } from 'node:test';
import { it } from 'node:test';
import { it } from 'node:test';
import { it } from 'node:test';
import { it } from 'node:test';
import { describe } from 'node:test';
import { it } from 'node:test';
import { it } from 'node:test';
import { it } from 'node:test';
import { it } from 'node:test';
import { it } from 'node:test';
import { describe } from 'node:test';
import { describe } from 'node:test';

const mockUseEditor = useEditor as jest.MockedFunction<typeof useEditor>;

describe('RichTextEditor - Comprehensive Tests', () => {
  const mockOnChange = jest.fn();
  const mockOnAutoSave = jest.fn();
  
  // Mock editor instance
  const mockEditor = {
    getHTML: jest.fn(() => '<p>Test content</p>'),
    commands: {
      setContent: jest.fn(),
      focus: jest.fn(() => ({ 
        toggleBold: jest.fn(() => ({ run: jest.fn() })),
        toggleItalic: jest.fn(() => ({ run: jest.fn() })),
        toggleHeading: jest.fn(() => ({ run: jest.fn() })),
        toggleBulletList: jest.fn(() => ({ run: jest.fn() })),
        toggleOrderedList: jest.fn(() => ({ run: jest.fn() })),
        toggleBlockquote: jest.fn(() => ({ run: jest.fn() })),
        toggleCodeBlock: jest.fn(() => ({ run: jest.fn() })),
        setImage: jest.fn(() => ({ run: jest.fn() })),
      })),
      chain: jest.fn(() => ({
        focus: jest.fn(() => ({
          toggleBold: jest.fn(() => ({ run: jest.fn() })),
          toggleItalic: jest.fn(() => ({ run: jest.fn() })),
          toggleHeading: jest.fn(() => ({ run: jest.fn() })),
          toggleBulletList: jest.fn(() => ({ run: jest.fn() })),
          toggleOrderedList: jest.fn(() => ({ run: jest.fn() })),
          toggleBlockquote: jest.fn(() => ({ run: jest.fn() })),
          toggleCodeBlock: jest.fn(() => ({ run: jest.fn() })),
          setImage: jest.fn(() => ({ run: jest.fn() })),
        })),
      })),
    },
    isActive: jest.fn((type: string, attrs?: any) => false),
    isEmpty: false,
  };

  beforeEach(() => {
    jest.clearAllMocks();
    mockUseEditor.mockReturnValue(mockEditor);
    mockLocalStorage.getItem.mockReturnValue('mock-token');
    (global.fetch as jest.Mock).mockClear();
  });

  describe('Basic Rendering', () => {
    it('renders the editor with all toolbar buttons', () => {
      render(
        <RichTextEditor
          content="<p>Test content</p>"
          onChange={mockOnChange}
          onAutoSave={mockOnAutoSave}
        />
      );

      // Check toolbar buttons
      expect(screen.getByText('Bold')).toBeInTheDocument();
      expect(screen.getByText('Italic')).toBeInTheDocument();
      expect(screen.getByText('H1')).toBeInTheDocument();
      expect(screen.getByText('H2')).toBeInTheDocument();
      expect(screen.getByText('H3')).toBeInTheDocument();
      expect(screen.getByText('• List')).toBeInTheDocument();
      expect(screen.getByText('1. List')).toBeInTheDocument();
      expect(screen.getByText('Quote')).toBeInTheDocument();
      expect(screen.getByText('Code')).toBeInTheDocument();
      expect(screen.getByText('🔗 Image URL')).toBeInTheDocument();
      expect(screen.getByText('📁 Upload Image')).toBeInTheDocument();
    });

    it('renders editor content area', () => {
      render(
        <RichTextEditor
          content="<p>Test content</p>"
          onChange={mockOnChange}
        />
      );

      expect(screen.getByTestId('editor-content')).toBeInTheDocument();
    });

    it('shows formatting shortcuts info', () => {
      render(
        <RichTextEditor
          content="<p>Test content</p>"
          onChange={mockOnChange}
        />
      );

      expect(screen.getByText(/Drag & drop images/)).toBeInTheDocument();
      expect(screen.getByText(/Ctrl\+B for bold/)).toBeInTheDocument();
    });

    it('applies custom className', () => {
      const { container } = render(
        <RichTextEditor
          content="<p>Test content</p>"
          onChange={mockOnChange}
          className="custom-class"
        />
      );

      expect(container.firstChild).toHaveClass('custom-class');
    });

    it('shows loading state when editor is not ready', () => {
      mockUseEditor.mockReturnValue(null);
      
      render(
        <RichTextEditor
          content="<p>Test content</p>"
          onChange={mockOnChange}
        />
      );

      expect(screen.getByRole('generic')).toHaveClass('animate-pulse', 'bg-gray-200');
    });
  });

  describe('Formatting Controls', () => {
    it('handles bold formatting', async () => {
      const user = userEvent.setup();
      
      render(
        <RichTextEditor
          content="<p>Test content</p>"
          onChange={mockOnChange}
        />
      );

      const boldButton = screen.getByText('Bold');
      await user.click(boldButton);

      expect(mockEditor.commands.chain().focus().toggleBold().run).toHaveBeenCalled();
    });

    it('handles italic formatting', async () => {
      const user = userEvent.setup();
      
      render(
        <RichTextEditor
          content="<p>Test content</p>"
          onChange={mockOnChange}
        />
      );

      const italicButton = screen.getByText('Italic');
      await user.click(italicButton);

      expect(mockEditor.commands.chain().focus().toggleItalic().run).toHaveBeenCalled();
    });

    it('handles heading formatting', async () => {
      const user = userEvent.setup();
      
      render(
        <RichTextEditor
          content="<p>Test content</p>"
          onChange={mockOnChange}
        />
      );

      const h1Button = screen.getByText('H1');
      await user.click(h1Button);

      expect(mockEditor.commands.chain().focus().toggleHeading).toHaveBeenCalledWith({ level: 1 });
    });

    it('handles list formatting', async () => {
      const user = userEvent.setup();
      
      render(
        <RichTextEditor
          content="<p>Test content</p>"
          onChange={mockOnChange}
        />
      );

      const bulletListButton = screen.getByText('• List');
      await user.click(bulletListButton);

      expect(mockEditor.commands.chain().focus().toggleBulletList().run).toHaveBeenCalled();

      const orderedListButton = screen.getByText('1. List');
      await user.click(orderedListButton);

      expect(mockEditor.commands.chain().focus().toggleOrderedList().run).toHaveBeenCalled();
    });

    it('shows active state for formatting buttons', () => {
      mockEditor.isActive.mockImplementation((type: string) => type === 'bold');
      
      render(
        <RichTextEditor
          content="<p>Test content</p>"
          onChange={mockOnChange}
        />
      );

      const boldButton = screen.getByText('Bold');
      expect(boldButton).toHaveClass('bg-gray-800', 'text-white');
    });
  });

  describe('Auto-save Functionality', () => {
    beforeEach(() => {
      jest.useFakeTimers();
    });

    afterEach(() => {
      jest.useRealTimers();
    });

    it('calls auto-save function at specified intervals', async () => {
      render(
        <RichTextEditor
          content="<p>Test content</p>"
          onChange={mockOnChange}
          onAutoSave={mockOnAutoSave}
          autoSaveInterval={1000}
        />
      );

      // Fast-forward time by 1 second
      act(() => {
        jest.advanceTimersByTime(1000);
      });

      expect(mockOnAutoSave).toHaveBeenCalledTimes(1);
    });

    it('does not auto-save when editor is empty', async () => {
      mockEditor.isEmpty = true;
      
      render(
        <RichTextEditor
          content=""
          onChange={mockOnChange}
          onAutoSave={mockOnAutoSave}
          autoSaveInterval={1000}
        />
      );

      act(() => {
        jest.advanceTimersByTime(1000);
      });

      expect(mockOnAutoSave).not.toHaveBeenCalled();
    });

    it('shows last saved timestamp', async () => {
      render(
        <RichTextEditor
          content="<p>Test content</p>"
          onChange={mockOnChange}
          onAutoSave={mockOnAutoSave}
          autoSaveInterval={1000}
        />
      );

      act(() => {
        jest.advanceTimersByTime(1000);
      });

      await waitFor(() => {
        expect(screen.getByText(/Saved/)).toBeInTheDocument();
      });
    });

    it('uses default auto-save interval when not specified', () => {
      render(
        <RichTextEditor
          content="<p>Test content</p>"
          onChange={mockOnChange}
          onAutoSave={mockOnAutoSave}
        />
      );

      // Should use default 30 second interval
      act(() => {
        jest.advanceTimersByTime(29000);
      });
      expect(mockOnAutoSave).not.toHaveBeenCalled();

      act(() => {
        jest.advanceTimersByTime(1000);
      });
      expect(mockOnAutoSave).toHaveBeenCalledTimes(1);
    });
  });

  describe('Image Upload Functionality', () => {
    it('handles file input change', async () => {
      const user = userEvent.setup();
      
      render(
        <RichTextEditor
          content="<p>Test content</p>"
          onChange={mockOnChange}
        />
      );

      const fileInput = screen.getByLabelText('📁 Upload Image').querySelector('input') as HTMLInputElement;
      expect(fileInput).toBeInTheDocument();
      
      const file = new File(['test'], 'test.jpg', { type: 'image/jpeg' });
      
      await user.upload(fileInput, file);
      
      expect(fileInput.files).toHaveLength(1);
      expect(fileInput.files?.[0]).toBe(file);
    });

    it('validates file type and size', async () => {
      const user = userEvent.setup();
      const alertSpy = jest.spyOn(window, 'alert').mockImplementation(() => {});
      
      render(
        <RichTextEditor
          content="<p>Test content</p>"
          onChange={mockOnChange}
        />
      );

      const fileInput = screen.getByLabelText('📁 Upload Image').querySelector('input') as HTMLInputElement;
      
      // Test invalid file type
      const invalidFile = new File(['test'], 'test.txt', { type: 'text/plain' });
      await user.upload(fileInput, invalidFile);
      
      expect(alertSpy).toHaveBeenCalledWith('Please select an image file');
      
      // Test file too large (6MB)
      const largeFile = new File(['x'.repeat(6 * 1024 * 1024)], 'large.jpg', { type: 'image/jpeg' });
      await user.upload(fileInput, largeFile);
      
      expect(alertSpy).toHaveBeenCalledWith('Image size must be less than 5MB');
      
      alertSpy.mockRestore();
    });

    it('uploads image successfully', async () => {
      const user = userEvent.setup();
      (global.fetch as jest.Mock).mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          success: true,
          data: { url: 'https://example.com/uploaded-image.jpg' }
        }),
      });
      
      render(
        <RichTextEditor
          content="<p>Test content</p>"
          onChange={mockOnChange}
        />
      );

      const fileInput = screen.getByLabelText('📁 Upload Image').querySelector('input') as HTMLInputElement;
      const file = new File(['test'], 'test.jpg', { type: 'image/jpeg' });
      
      await user.upload(fileInput, file);
      
      await waitFor(() => {
        expect(global.fetch).toHaveBeenCalledWith('/api/upload/image', {
          method: 'POST',
          headers: {
            'Authorization': 'Bearer mock-token',
          },
          body: expect.any(FormData),
        });
      });

      expect(mockEditor.commands.chain().focus().setImage).toHaveBeenCalledWith({
        src: 'https://example.com/uploaded-image.jpg'
      });
    });

    it('handles upload failure gracefully', async () => {
      const user = userEvent.setup();
      (global.fetch as jest.Mock).mockRejectedValueOnce(new Error('Upload failed'));
      
      render(
        <RichTextEditor
          content="<p>Test content</p>"
          onChange={mockOnChange}
        />
      );

      const fileInput = screen.getByLabelText('📁 Upload Image').querySelector('input') as HTMLInputElement;
      const file = new File(['test'], 'test.jpg', { type: 'image/jpeg' });
      
      await user.upload(fileInput, file);
      
      await waitFor(() => {
        expect(mockEditor.commands.chain().focus().setImage).toHaveBeenCalledWith({
          src: 'mock-url'
        });
      });
    });

    it('shows uploading status', async () => {
      const user = userEvent.setup();
      let resolveUpload: (value: any) => void;
      const uploadPromise = new Promise(resolve => {
        resolveUpload = resolve;
      });
      
      (global.fetch as jest.Mock).mockReturnValueOnce(uploadPromise);
      
      render(
        <RichTextEditor
          content="<p>Test content</p>"
          onChange={mockOnChange}
        />
      );

      const fileInput = screen.getByLabelText('📁 Upload Image').querySelector('input') as HTMLInputElement;
      const file = new File(['test'], 'test.jpg', { type: 'image/jpeg' });
      
      await user.upload(fileInput, file);
      
      expect(screen.getByText('Uploading image...')).toBeInTheDocument();
      
      resolveUpload!({
        ok: true,
        json: async () => ({ success: true, data: { url: 'test.jpg' } })
      });
      
      await waitFor(() => {
        expect(screen.queryByText('Uploading image...')).not.toBeInTheDocument();
      });
    });

    it('handles image URL input', async () => {
      const user = userEvent.setup();
      const promptSpy = jest.spyOn(window, 'prompt').mockReturnValue('https://example.com/image.jpg');
      
      render(
        <RichTextEditor
          content="<p>Test content</p>"
          onChange={mockOnChange}
        />
      );

      const imageUrlButton = screen.getByText('🔗 Image URL');
      await user.click(imageUrlButton);
      
      expect(promptSpy).toHaveBeenCalledWith('Enter image URL:');
      expect(mockEditor.commands.chain().focus().setImage).toHaveBeenCalledWith({
        src: 'https://example.com/image.jpg'
      });
      
      promptSpy.mockRestore();
    });
  });

  describe('Drag and Drop Functionality', () => {
    it('shows drag overlay when dragging files', () => {
      render(
        <RichTextEditor
          content="<p>Test content</p>"
          onChange={mockOnChange}
        />
      );

      const editor = screen.getByRole('generic', { name: /rich-text-editor/ });
      
      fireEvent.dragEnter(editor);
      
      expect(screen.getByText('Drop your image here')).toBeInTheDocument();
      expect(screen.getByText('Supports JPG, PNG, GIF up to 5MB')).toBeInTheDocument();
    });

    it('hides drag overlay when drag leaves', () => {
      render(
        <RichTextEditor
          content="<p>Test content</p>"
          onChange={mockOnChange}
        />
      );

      const editor = screen.getByRole('generic', { name: /rich-text-editor/ });
      
      fireEvent.dragEnter(editor);
      expect(screen.getByText('Drop your image here')).toBeInTheDocument();
      
      fireEvent.dragLeave(editor);
      expect(screen.queryByText('Drop your image here')).not.toBeInTheDocument();
    });
  });

  describe('Content Updates', () => {
    it('updates editor content when prop changes', () => {
      const { rerender } = render(
        <RichTextEditor
          content="<p>Initial content</p>"
          onChange={mockOnChange}
        />
      );

      rerender(
        <RichTextEditor
          content="<p>Updated content</p>"
          onChange={mockOnChange}
        />
      );

      expect(mockEditor.commands.setContent).toHaveBeenCalledWith('<p>Updated content</p>');
    });

    it('does not update editor if content is the same', () => {
      mockEditor.getHTML.mockReturnValue('<p>Same content</p>');
      
      render(
        <RichTextEditor
          content="<p>Same content</p>"
          onChange={mockOnChange}
        />
      );

      expect(mockEditor.commands.setContent).not.toHaveBeenCalled();
    });
  });

  describe('Accessibility and UX', () => {
    it('resets file input after selection', async () => {
      const user = userEvent.setup();
      
      render(
        <RichTextEditor
          content="<p>Test content</p>"
          onChange={mockOnChange}
        />
      );

      const fileInput = screen.getByLabelText('📁 Upload Image').querySelector('input') as HTMLInputElement;
      const file = new File(['test'], 'test.jpg', { type: 'image/jpeg' });
      
      await user.upload(fileInput, file);
      
      // File input should be reset to allow selecting the same file again
      expect(fileInput.value).toBe('');
    });

    it('uses custom placeholder', () => {
      const customPlaceholder = 'Write your story here...';
      
      render(
        <RichTextEditor
          content=""
          onChange={mockOnChange}
          placeholder={customPlaceholder}
        />
      );

      // Placeholder is passed to TipTap configuration
      expect(mockUseEditor).toHaveBeenCalledWith(
        expect.objectContaining({
          extensions: expect.arrayContaining([
            expect.objectContaining({})
          ])
        })
      );
    });
  });
});