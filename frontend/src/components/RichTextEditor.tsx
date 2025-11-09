import React, { useCallback, useEffect, useRef, useState } from 'react';
import { useEditor, EditorContent } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import CodeBlockLowlight from '@tiptap/extension-code-block-lowlight';
import Placeholder from '@tiptap/extension-placeholder';
import Dropcursor from '@tiptap/extension-dropcursor';
import Gapcursor from '@tiptap/extension-gapcursor';
import Typography from '@tiptap/extension-typography';
import { createLowlight } from 'lowlight';
import './RichTextEditor.css';

const lowlight = createLowlight();

interface RichTextEditorProps {
  content: string;
  onChange: (content: string) => void;
  onAutoSave?: () => void;
  placeholder?: string;
  className?: string;
  autoSaveInterval?: number; // in milliseconds, default 30000 (30 seconds)
}

const RichTextEditor: React.FC<RichTextEditorProps> = ({
  content,
  onChange,
  onAutoSave,
  placeholder = "Tell your story...",
  className = "",
  autoSaveInterval = 10000
}) => {
  const [isUploading, setIsUploading] = useState(false);
  const [showBubbleMenu, setShowBubbleMenu] = useState(false);
  const [bubbleMenuPosition, setBubbleMenuPosition] = useState({ x: 0, y: 0 });
  const [showFloatingMenu, setShowFloatingMenu] = useState(false);
  const [showPlusIcon, setShowPlusIcon] = useState(false);
  const [plusIconPosition, setPlusIconPosition] = useState({ x: 0, y: 0 });
  const fileInputRef = useRef<HTMLInputElement>(null);
  const dragCounter = useRef(0);
  const bubbleMenuRef = useRef<HTMLDivElement>(null);
  const selectionTimeoutRef = useRef<NodeJS.Timeout | null>(null);
  const lastSelectionRef = useRef<{ from: number; to: number } | null>(null);

  const editor = useEditor({
    extensions: [
      StarterKit.configure({
        codeBlock: false,
        heading: {
          levels: [1, 2, 3],
        },
      }),
      Typography,
      Image.configure({
        HTMLAttributes: {
          class: 'max-w-full h-auto my-8 rounded-sm',
        },
        allowBase64: true,
      }),
      CodeBlockLowlight.configure({
        lowlight,
        HTMLAttributes: {
          class: 'rounded-sm bg-gray-50 p-6 font-mono text-sm border-l-4 border-gray-200 my-6',
        },
      }),
      Placeholder.configure({
        placeholder: ({ node }) => {
          if (node.type.name === 'heading') {
            return 'Heading';
          }
          return placeholder;
        },
        showOnlyWhenEditable: true,
        showOnlyCurrent: false,
      }),
      Dropcursor.configure({
        color: '#10b981',
        width: 2,
      }),
      Gapcursor,

    ],
    content,
    onUpdate: ({ editor }) => {
      const html = editor.getHTML();
      onChange(html);
    },
    onSelectionUpdate: ({ editor }) => {
      // Clear any existing timeout
      if (selectionTimeoutRef.current) {
        clearTimeout(selectionTimeoutRef.current);
      }

      // Debounce the selection update to prevent jittering
      selectionTimeoutRef.current = setTimeout(() => {
        const { from, to, empty } = editor.state.selection;

        // Check if selection actually changed
        const currentSelection = { from, to };
        const lastSelection = lastSelectionRef.current;

        if (lastSelection && lastSelection.from === from && lastSelection.to === to) {
          return; // Selection hasn't changed, don't update
        }

        lastSelectionRef.current = currentSelection;

        // Only show bubble menu for actual text selections (minimum 1 character)
        if (!empty && from !== to && (to - from) >= 1) {
          const { view } = editor;
          try {
            const start = view.coordsAtPos(from);
            const end = view.coordsAtPos(to);

            // Calculate position relative to viewport
            const centerX = (start.left + end.left) / 2;
            const topY = Math.min(start.top, end.top) - 60;

            setBubbleMenuPosition({
              x: centerX,
              y: Math.max(topY, 10) // Ensure it doesn't go above viewport
            });
            setShowBubbleMenu(true);
            setShowFloatingMenu(false); // Hide floating menu when showing bubble menu
          } catch (error) {
            // Handle edge cases where coordinates can't be calculated
            setShowBubbleMenu(false);
          }
        } else {
          setShowBubbleMenu(false);
          // Show plus icon for empty lines or at the beginning of empty blocks
          if (empty) {
            const { view } = editor;
            try {
              const coords = view.coordsAtPos(from);
              const $pos = editor.state.doc.resolve(from);
              const node = $pos.parent;
              
              // Show plus icon if:
              // 1. Editor is completely empty
              // 2. Cursor is in an empty paragraph
              // 3. Cursor is at the start of a line (beginning of a paragraph)
              const shouldShowPlusIcon = 
                editor.isEmpty || 
                (node && node.type.name === 'paragraph' && node.content.size === 0) ||
                ($pos.parentOffset === 0 && node.type.name === 'paragraph');
              
              if (shouldShowPlusIcon) {
                setPlusIconPosition({
                  x: coords.left - 40, // Position to the left of the text
                  y: coords.top
                });
                setShowPlusIcon(true);
                setShowFloatingMenu(false);
              } else {
                setShowPlusIcon(false);
                setShowFloatingMenu(false);
              }
            } catch (error) {
              setShowPlusIcon(false);
              setShowFloatingMenu(false);
            }
          } else {
            setShowPlusIcon(false);
            setShowFloatingMenu(false);
          }
        }
      }, 150); // Longer debounce for better stability
    },
    onFocus: ({ editor }) => {
      const { empty, from } = editor.state.selection;
      if (empty) {
        const { view } = editor;
        try {
          const coords = view.coordsAtPos(from);
          const $pos = editor.state.doc.resolve(from);
          const node = $pos.parent;
          
          // Show plus icon if:
          // 1. Editor is completely empty
          // 2. Cursor is in an empty paragraph
          // 3. Cursor is at the start of a line (beginning of a paragraph)
          const shouldShowPlusIcon = 
            editor.isEmpty || 
            (node && node.type.name === 'paragraph' && node.content.size === 0) ||
            ($pos.parentOffset === 0 && node.type.name === 'paragraph');
          
          if (shouldShowPlusIcon) {
            setPlusIconPosition({
              x: coords.left - 40,
              y: coords.top
            });
            setShowPlusIcon(true);
          }
        } catch (error) {
          // Handle edge cases
        }
      }
    },
    onBlur: () => {
      // Delay hiding to allow clicking on menu buttons
      setTimeout(() => {
        setShowBubbleMenu(false);
        setShowFloatingMenu(false);
        setShowPlusIcon(false);
      }, 150);
    },
    editorProps: {
      attributes: {
        class: 'prose prose-xl max-w-none focus:outline-none min-h-[200px]',
        style: 'font-family: charter, Georgia, Cambria, "Times New Roman", Times, serif; line-height: 1.58; letter-spacing: -.003em;',
      },
      handleDrop: (view, event, slice, moved) => {
        if (!moved && event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0]) {
          const file = event.dataTransfer.files[0];
          if (file.type.startsWith('image/')) {
            event.preventDefault();
            handleImageUpload(file);
            return true;
          }
        }
        return false;
      },
      handlePaste: (view, event, slice) => {
        const items = Array.from(event.clipboardData?.items || []);
        for (const item of items) {
          if (item.type.startsWith('image/')) {
            const file = item.getAsFile();
            if (file) {
              event.preventDefault();
              handleImageUpload(file);
              return true;
            }
          }
        }
        return false;
      },
      handleKeyDown: (view, event) => {
        // Hide menus on escape
        if (event.key === 'Escape') {
          setShowBubbleMenu(false);
          setShowFloatingMenu(false);
          setShowPlusIcon(false);
          return false;
        }

        // Hide bubble menu when typing
        if (showBubbleMenu && event.key.length === 1) {
          setShowBubbleMenu(false);
        }

        // Hide plus icon when typing
        if (showPlusIcon && event.key.length === 1) {
          setShowPlusIcon(false);
        }

        return false;
      },
    },
  });

  // Auto-save functionality - more aggressive and reliable
  useEffect(() => {
    if (!onAutoSave || !editor) return;

    const interval = setInterval(() => {
      // Auto-save even if editor appears empty but has content
      const hasContent = !editor.isEmpty || editor.getHTML().trim() !== '<p></p>';
      if (hasContent) {
        onAutoSave();
      }
    }, autoSaveInterval);

    return () => clearInterval(interval);
  }, [editor, onAutoSave, autoSaveInterval]);

  // Update editor content when prop changes
  useEffect(() => {
    if (editor && content !== editor.getHTML()) {
      editor.commands.setContent(content);
    }
  }, [content, editor]);

  // Close menus on click outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      const target = event.target as Element;

      // Close bubble menu if clicking outside (but not on the menu itself)
      if (showBubbleMenu && bubbleMenuRef.current && !bubbleMenuRef.current.contains(target)) {
        setShowBubbleMenu(false);
      }

      // Close floating menu if clicking outside
      if (showFloatingMenu && !target.closest('.floating-menu-container')) {
        setShowFloatingMenu(false);
      }

      // Close plus icon if clicking outside the editor
      if (showPlusIcon && !target.closest('.medium-inline-editor') && !target.closest('.plus-icon-button')) {
        setShowPlusIcon(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [showBubbleMenu, showFloatingMenu, showPlusIcon]);

  // Cleanup timeout on unmount
  useEffect(() => {
    return () => {
      if (selectionTimeoutRef.current) {
        clearTimeout(selectionTimeoutRef.current);
      }
    };
  }, []);

  const handleImageUpload = useCallback(async (file: File) => {
    if (!file || !editor) return;

    // Validate file type and size
    if (!file.type.startsWith('image/')) {
      alert('Please select an image file');
      return;
    }

    if (file.size > 5 * 1024 * 1024) { // 5MB limit
      alert('Image size must be less than 5MB');
      return;
    }

    setIsUploading(true);

    try {
      const formData = new FormData();
      formData.append('image', file);

      // Upload to backend
      const getAuthToken = () => {
        try {
          const tokensData = localStorage.getItem('authTokens');
          if (tokensData) {
            const tokens = JSON.parse(tokensData);
            console.log('Auth tokens found:', { hasAccessToken: !!tokens.access_token });
            return tokens.access_token;
          } else {
            console.warn('No auth tokens found in localStorage');
          }
        } catch (error) {
          console.error('Error parsing auth tokens:', error);
        }
        return null;
      };

      const token = getAuthToken();
      const headers: Record<string, string> = {};
      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
        console.log('Uploading with authentication token');
      } else {
        console.warn('Uploading without authentication token - this will likely fail');
      }

      const response = await fetch('/api/upload/image', {
        method: 'POST',
        headers,
        body: formData,
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success && data.data.url) {
          editor.chain().focus().setImage({ src: data.data.url }).run();
        } else {
          throw new Error(data.message || 'Upload failed - invalid response');
        }
      } else {
        // Get error details from response
        let errorMessage = `Upload failed (${response.status})`;
        try {
          const errorData = await response.json();
          if (errorData.error && errorData.error.message) {
            errorMessage = errorData.error.message;
          } else if (errorData.message) {
            errorMessage = errorData.message;
          }
        } catch (e) {
          // If we can't parse the error response, use the status text
          errorMessage = `Upload failed: ${response.statusText}`;
        }
        throw new Error(errorMessage);
      }
    } catch (error) {
      console.error('Image upload error:', error);
      
      // Show error to user
      console.error('Image upload failed:', error);
      alert(`Image upload failed: ${error instanceof Error ? error.message : 'Unknown error'}. Please try again or check your connection.`);
      
      // Don't insert image if upload fails
      return;
    } finally {
      setIsUploading(false);
    }
  }, [editor]);

  const handleFileInputChange = useCallback((event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (file) {
      handleImageUpload(file);
    }
    // Reset input value to allow selecting the same file again
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  }, [handleImageUpload]);

  // Drag and drop handlers for visual feedback
  const handleDragEnter = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    dragCounter.current++;
  }, []);

  const handleDragLeave = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    dragCounter.current--;
  }, []);

  const handleDragOver = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
  }, []);

  const handleDrop = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    dragCounter.current = 0;

    const files = Array.from(e.dataTransfer.files);
    const imageFile = files.find(file => file.type.startsWith('image/'));

    if (imageFile) {
      handleImageUpload(imageFile);
    }
  }, [handleImageUpload]);

  if (!editor) {
    return <div className="animate-pulse bg-gray-200 h-96 rounded-lg"></div>;
  }

  return (
    <div
      className={`relative ${className} bg-white transition-colors duration-200`}
      onDragEnter={handleDragEnter}
      onDragLeave={handleDragLeave}
      onDragOver={handleDragOver}
      onDrop={handleDrop}
    >
      {/* Upload Status */}
      {isUploading && (
        <div className="fixed top-20 right-6 z-50">
          <div className="text-xs text-green-600 flex items-center bg-white px-3 py-2 rounded-full shadow-lg border border-gray-100">
            <span className="w-1.5 h-1.5 bg-green-500 rounded-full mr-2 animate-pulse"></span>
            Uploading...
          </div>
        </div>
      )}

      {/* Hidden file input */}
      <input
        ref={fileInputRef}
        type="file"
        accept="image/*"
        onChange={handleFileInputChange}
        className="hidden"
      />

      {/* Editor Content */}
      <div className="relative">
        <EditorContent
          editor={editor}
          className="medium-inline-editor"
        />

        {/* Bubble Menu - Appears on text selection */}
        {showBubbleMenu && editor && (
          <div
            ref={bubbleMenuRef}
            className="bubble-menu fixed z-50 bg-gray-900/95 backdrop-blur-sm text-white rounded-xl shadow-2xl px-3 py-2 flex items-center gap-1 transition-all duration-200 ease-out border border-gray-700/50"
            style={{
              left: bubbleMenuPosition.x,
              top: bubbleMenuPosition.y,
              transform: 'translateX(-50%)',
              pointerEvents: 'auto'
            }}
            onMouseDown={(e) => e.preventDefault()} // Prevent losing focus when clicking buttons
          >
            <button
              onMouseDown={(e) => {
                e.preventDefault();
                editor.chain().focus().toggleBold().run();
              }}
              className={`p-2.5 rounded-lg hover:bg-gray-700 transition-all duration-150 ${editor.isActive('bold') ? 'bg-gray-700 text-white' : 'text-gray-300 hover:text-white'
                }`}
              title="Bold (Ctrl+B)"
            >
              <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4 4 4 0 0 1 4 4 4 4 0 0 1-4 4H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zm2 5V7h6a2 2 0 1 1 0 4H8zm0 2h6a2 2 0 1 1 0 4H8v-4z"/>
              </svg>
            </button>

            <button
              onMouseDown={(e) => {
                e.preventDefault();
                editor.chain().focus().toggleItalic().run();
              }}
              className={`p-2.5 rounded-lg hover:bg-gray-700 transition-all duration-150 ${editor.isActive('italic') ? 'bg-gray-700 text-white' : 'text-gray-300 hover:text-white'
                }`}
              title="Italic (Ctrl+I)"
            >
              <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M10 4v3h2.21l-3.42 8H6v3h8v-3h-2.21l3.42-8H18V4h-8z"/>
              </svg>
            </button>



            <div className="w-px h-5 bg-gray-600 mx-2"></div>

            <button
              onMouseDown={(e) => {
                e.preventDefault();
                editor.chain().focus().toggleHeading({ level: 1 }).run();
              }}
              className={`px-3 py-2 rounded-lg text-sm font-semibold hover:bg-gray-700 transition-all duration-150 ${editor.isActive('heading', { level: 1 }) ? 'bg-gray-700 text-white' : 'text-gray-300 hover:text-white'
                }`}
              title="Heading 1"
            >
              H1
            </button>

            <button
              onMouseDown={(e) => {
                e.preventDefault();
                editor.chain().focus().toggleHeading({ level: 2 }).run();
              }}
              className={`px-3 py-2 rounded-lg text-sm font-semibold hover:bg-gray-700 transition-all duration-150 ${editor.isActive('heading', { level: 2 }) ? 'bg-gray-700 text-white' : 'text-gray-300 hover:text-white'
                }`}
              title="Heading 2"
            >
              H2
            </button>

            <div className="w-px h-5 bg-gray-600 mx-2"></div>

            <button
              onMouseDown={(e) => {
                e.preventDefault();
                editor.chain().focus().toggleBulletList().run();
              }}
              className={`p-2.5 rounded-lg hover:bg-gray-700 transition-all duration-150 ${editor.isActive('bulletList') ? 'bg-gray-700 text-white' : 'text-gray-300 hover:text-white'
                }`}
              title="Bullet List"
            >
              <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M4 6a2 2 0 1 1 0 4 2 2 0 0 1 0-4zm0 6a2 2 0 1 1 0 4 2 2 0 0 1 0-4zM6 4a2 2 0 1 1 0 4 2 2 0 0 1 0-4z"/>
                <path d="M10 6h10v2H10V6zm0 6h10v2H10v-2zm0 6h10v2H10v-2z"/>
              </svg>
            </button>

            <button
              onMouseDown={(e) => {
                e.preventDefault();
                editor.chain().focus().toggleOrderedList().run();
              }}
              className={`p-2.5 rounded-lg hover:bg-gray-700 transition-all duration-150 ${editor.isActive('orderedList') ? 'bg-gray-700 text-white' : 'text-gray-300 hover:text-white'
                }`}
              title="Numbered List"
            >
              <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M2 17h2v.5H3v1h1v.5H2v1h3v-4H2v1zm1-9h1V4H2v1h1v3zm-1 3h1.8L2 13.1v.9h3v-1H3.2L5 10.9V10H2v1zm5-6v2h14V6H7zm0 14h14v-2H7v2zm0-6h14v-2H7v2z"/>
              </svg>
            </button>

            <button
              onMouseDown={(e) => {
                e.preventDefault();
                editor.chain().focus().toggleBlockquote().run();
              }}
              className={`p-2.5 rounded-lg hover:bg-gray-700 transition-all duration-150 ${editor.isActive('blockquote') ? 'bg-gray-700 text-white' : 'text-gray-300 hover:text-white'
                }`}
              title="Quote"
            >
              <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M14 17h3l2-4V7h-6v6h3M6 17h3l2-4V7H5v6h3l-2 4z"/>
              </svg>
            </button>

            <button
              onMouseDown={(e) => {
                e.preventDefault();
                editor.chain().focus().toggleCodeBlock().run();
              }}
              className={`p-2.5 rounded-lg hover:bg-gray-700 transition-all duration-150 ${editor.isActive('codeBlock') ? 'bg-gray-700 text-white' : 'text-gray-300 hover:text-white'
                }`}
              title="Code Block"
            >
              <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0L19.2 12l-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"/>
              </svg>
            </button>

            <div className="w-px h-5 bg-gray-600 mx-2"></div>

            <button
              onMouseDown={(e) => {
                e.preventDefault();
                const url = window.prompt('Enter URL:');
                if (url) {
                  editor.chain().focus().setLink({ href: url }).run();
                }
              }}
              className={`p-2.5 rounded-lg hover:bg-gray-700 transition-all duration-150 ${editor.isActive('link') ? 'bg-gray-700 text-white' : 'text-gray-300 hover:text-white'
                }`}
              title="Add Link"
            >
              <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H6.9C4.29 7 2.2 9.09 2.2 11.7s2.09 4.7 4.7 4.7h4v-1.9H6.9c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9.1-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.61 0 4.7-2.09 4.7-4.7S19.71 7 17.1 7z"/>
              </svg>
            </button>
          </div>
        )}

        {/* Plus Icon - Appears inline with empty blocks */}
        {showPlusIcon && editor && !showBubbleMenu && !showFloatingMenu && (
          <button
            className="plus-icon-button fixed z-40 w-8 h-8 bg-white border border-gray-300 rounded-full flex items-center justify-center hover:bg-gray-50 transition-all duration-200 shadow-sm hover:shadow-md"
            style={{
              left: plusIconPosition.x,
              top: plusIconPosition.y + 2, // Slight vertical adjustment
            }}
            onClick={(e) => {
              e.preventDefault();
              setShowFloatingMenu(true);
              setShowPlusIcon(false);
            }}
            onMouseDown={(e) => e.preventDefault()}
          >
            <svg className="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
              <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
            </svg>
          </button>
        )}

        {/* Floating Menu - Appears when plus icon is clicked */}
        {showFloatingMenu && editor && !showBubbleMenu && (
          <div 
            className="floating-menu-container fixed z-50 bg-white border border-gray-200 rounded-lg shadow-lg py-2 min-w-[180px]"
            style={{
              left: plusIconPosition.x + 40, // Position to the right of the plus icon
              top: plusIconPosition.y,
            }}
          >
            <button
              onClick={() => {
                fileInputRef.current?.click();
                setShowFloatingMenu(false);
              }}
              className="w-full px-3 py-2 text-left hover:bg-gray-50 flex items-center gap-3 transition-colors"
            >
              <span className="text-lg">🖼️</span>
              <span className="text-sm text-gray-700">Add image</span>
            </button>

            <button
              onClick={() => {
                editor.chain().focus().setHorizontalRule().run();
                setShowFloatingMenu(false);
              }}
              className="w-full px-3 py-2 text-left hover:bg-gray-50 flex items-center gap-3 transition-colors"
            >
              <span className="text-lg">➖</span>
              <span className="text-sm text-gray-700">Divider</span>
            </button>
          </div>
        )}

        {/* Drag overlay */}
        {dragCounter.current > 0 && (
          <div className="absolute inset-0 bg-green-50 bg-opacity-95 border-2 border-dashed border-green-300 rounded-lg flex items-center justify-center z-40">
            <div className="text-green-700 text-center">
              <div className="text-3xl mb-3">📸</div>
              <div className="font-medium text-lg">Drop your image here</div>
              <div className="text-sm text-green-600 mt-1">JPG, PNG, GIF up to 5MB</div>
            </div>
          </div>
        )}
      </div>



      {/* Writing tips for empty editor */}
      {editor && editor.isEmpty && (
        <div className="mt-8 text-center">
          <div className="text-sm text-gray-400 space-y-2">

            <p>� Drag  & drop images anywhere</p>
            <p>✨ Select text for formatting options</p>
          </div>
        </div>
      )}
    </div>
  );
};

export default RichTextEditor;