import React, { useState, useEffect } from 'react';
import RichTextEditor from './RichTextEditor';
import { useDraftManager, Draft } from '../hooks/useDraftManager';
import { calculateReadingTime } from '../utils/readingTime';
import SimplePublishDialog from './SimplePublishDialog';

interface ArticleEditorProps {
  articleId?: number;
  onSave?: (article: Draft) => void;
  onPublish?: (article: Draft) => void;
  onCancel?: () => void;
}

const ArticleEditor: React.FC<ArticleEditorProps> = ({
  articleId,
  onSave,
  onPublish,
  onCancel
}) => {
  const {
    draft,
    updateDraft,
    saveDraft,
    publishDraft,
    deleteDraft,
    loadDraft,
    isSaving,
    lastSaved,
    hasUnsavedChanges
  } = useDraftManager();

  const [showPublishDialog, setShowPublishDialog] = useState(false);
  const [isPublishing, setIsPublishing] = useState(false);
  const [showRecoveryDialog, setShowRecoveryDialog] = useState(false);
  const [recoveredDraft, setRecoveredDraft] = useState<Draft | null>(null);

  // Check for recovered draft on mount
  useEffect(() => {
    const checkForRecoveredDraft = () => {
      try {
        const stored = localStorage.getItem('medium_clone_draft_backup');
        if (stored && !articleId) { // Only show recovery for new drafts
          const parsedDraft = JSON.parse(stored);
          if (parsedDraft.title || parsedDraft.content) {
            setRecoveredDraft(parsedDraft);
            setShowRecoveryDialog(true);
          }
        }
      } catch (error) {
        console.error('Failed to check for recovered draft:', error);
      }
    };

    checkForRecoveredDraft();
  }, [articleId]);

  // Load existing article if articleId is provided
  useEffect(() => {
    if (articleId) {
      loadDraft(articleId).catch(console.error);
    }
  }, [articleId, loadDraft]);

  // Auto-resize title textarea when title changes
  useEffect(() => {
    const titleTextarea = document.querySelector('.title-input') as HTMLTextAreaElement;
    if (titleTextarea) {
      titleTextarea.style.height = 'auto';
      titleTextarea.style.height = titleTextarea.scrollHeight + 'px';
    }
  }, [draft.title]);

  // Auto-save functionality - now more aggressive
  const handleAutoSave = async () => {
    // Auto-save if there's any content, even without title
    if (draft.title.trim() || draft.content.trim()) {
      try {
        await saveDraft();
      } catch (error) {
        console.error('Auto-save failed:', error);
        // Don't show error to user for auto-save failures
        // Content is still preserved in localStorage
      }
    }
  };

  const handleContentChange = (content: string) => {
    updateDraft({ 
      content,
      readingTime: calculateReadingTime(content)
    });
  };

  const handleTitleChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
    updateDraft({ title: e.target.value });
  };

  const handleTitleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
    if (e.key === 'Enter') {
      if (e.shiftKey) {
        // Shift+Enter: Allow new line in title (default behavior)
        return;
      } else {
        // Enter: Move focus to content editor
        e.preventDefault();
        // Use setTimeout to ensure the DOM is ready
        setTimeout(() => {
          const contentEditor = document.querySelector('.ProseMirror') as HTMLElement;
          if (contentEditor) {
            contentEditor.focus();
            // If content is empty, position cursor at the beginning
            if (!draft.content.trim()) {
              const selection = window.getSelection();
              if (selection && contentEditor.firstChild) {
                selection.removeAllRanges();
                const range = document.createRange();
                range.setStart(contentEditor.firstChild, 0);
                range.collapse(true);
                selection.addRange(range);
              }
            }
          }
        }, 0);
      }
    }
  };



  const validateDraft = (): boolean => {
    if (!draft.title.trim()) {
      alert('Please add a title to your story');
      return false;
    }

    if (!draft.content.trim()) {
      alert('Please add some content to your story');
      return false;
    }

    return true;
  };

  const handleSave = async () => {
    try {
      await saveDraft();
      onSave?.(draft);
    } catch (error) {
      console.error('Failed to save draft:', error);
    }
  };

  const handlePublishClick = async () => {
    if (!validateDraft()) {
      return;
    }
    
    // Auto-save before publishing
    try {
      await saveDraft();
      setShowPublishDialog(true);
    } catch (error) {
      console.error('Failed to save draft before publishing:', error);
      alert('Please save your story first');
    }
  };

  const handlePublishConfirm = async (publishData: any) => {
    setIsPublishing(true);
    try {
      // Update draft with publish data first
      updateDraft({
        title: publishData.title,
        featuredImage: publishData.featuredImage,
        tags: publishData.tags
      });
      
      // Save the updated draft
      await saveDraft();
      
      // Then publish
      const publishedArticle = await publishDraft(publishData.publishOptions);
      setShowPublishDialog(false);
      onPublish?.(publishedArticle);
    } catch (error) {
      console.error('Failed to publish article:', error);
      alert('Failed to publish your story. Please try again.');
    } finally {
      setIsPublishing(false);
    }
  };

  const handleRecoverDraft = () => {
    if (recoveredDraft) {
      updateDraft(recoveredDraft);
      setShowRecoveryDialog(false);
    }
  };

  const handleDiscardRecovery = () => {
    try {
      localStorage.removeItem('medium_clone_draft_backup');
    } catch (error) {
      console.error('Failed to clear localStorage:', error);
    }
    setShowRecoveryDialog(false);
  };

  return (
    <div className="min-h-screen bg-white">
      {/* Clean Header */}
      <div className="sticky top-0 bg-white/95 backdrop-blur-sm border-b border-gray-100 z-10">
        <div className="max-w-6xl mx-auto px-6 py-4">
          <div className="flex justify-between items-center">
            {/* Left side - Logo/Brand */}
            <div className="flex items-center space-x-6">
              <h1 className="text-xl font-bold text-gray-900 tracking-tight">Medium</h1>
              <span className="text-sm text-gray-400 font-light">
                Draft in {draft.title ? draft.title.substring(0, 25) + (draft.title.length > 25 ? '...' : '') : 'Untitled'}
              </span>
            </div>

            {/* Right side - Actions */}
            <div className="flex items-center space-x-3">
              {/* Subtle save indicator */}
              {isSaving && (
                <div className="flex items-center space-x-2">
                  <div className="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></div>
                  <span className="text-xs text-gray-500">Saving</span>
                </div>
              )}
              
              <button
                onClick={handlePublishClick}
                disabled={isPublishing || !draft.title.trim() || !draft.content.trim()}
                className="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-full hover:bg-green-700 disabled:opacity-50 transition-all duration-200 shadow-sm"
              >
                {isPublishing ? 'Publishing...' : 'Publish'}
              </button>
              
              <button
                onClick={onCancel}
                className="p-2 text-gray-400 hover:text-gray-600 transition-colors rounded-full hover:bg-gray-50"
                title="Close"
              >
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* Editor Content */}
      <div className="max-w-4xl mx-auto px-6 py-12">
        {/* Title Input */}
        <div className="mb-8">
          <textarea
            value={draft.title}
            onChange={handleTitleChange}
            onKeyDown={handleTitleKeyDown}
            placeholder="Title"
            rows={1}
            className="title-input w-full text-5xl font-bold placeholder-gray-300 text-gray-900 leading-tight resize-none overflow-hidden"
            style={{
              fontFamily: 'sohne, "Helvetica Neue", Helvetica, Arial, sans-serif',
              letterSpacing: '-0.02em',
              paddingTop: '0.5rem',
              paddingBottom: '0.5rem'
            }}
            onInput={(e) => {
              // Auto-resize textarea based on content
              const target = e.target as HTMLTextAreaElement;
              target.style.height = 'auto';
              target.style.height = target.scrollHeight + 'px';
            }}
          />
        </div>



        {/* Content Editor */}
        <div className="mb-12">
          <RichTextEditor
            content={draft.content}
            onChange={handleContentChange}
            onAutoSave={handleAutoSave}
            placeholder="Tell your story..."
            className="medium-inline-editor"
          />
        </div>
      </div>

      {/* Recovery Dialog */}
      {showRecoveryDialog && recoveredDraft && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 className="text-lg font-semibold mb-4">Recover Your Work?</h3>
            <p className="text-gray-600 mb-6">
              We found unsaved changes from your previous session. Would you like to recover them?
            </p>
            
            {/* Preview of recovered content */}
            <div className="bg-gray-50 p-3 rounded mb-6 max-h-32 overflow-y-auto">
              {recoveredDraft.title && (
                <div className="font-medium text-sm mb-1">{recoveredDraft.title}</div>
              )}

              {recoveredDraft.content && (
                <div className="text-gray-500 text-xs">
                  {recoveredDraft.content.replace(/<[^>]*>/g, '').substring(0, 100)}...
                </div>
              )}
            </div>
            
            <div className="flex space-x-3">
              <button
                onClick={handleRecoverDraft}
                className="flex-1 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors"
              >
                Recover
              </button>
              <button
                onClick={handleDiscardRecovery}
                className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded hover:bg-gray-50 transition-colors"
              >
                Start Fresh
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Simple Publish Dialog */}
      <SimplePublishDialog
        isOpen={showPublishDialog}
        onClose={() => setShowPublishDialog(false)}
        onConfirm={handlePublishConfirm}
        draft={draft}
        isPublishing={isPublishing}
      />
    </div>
  );
};

export default ArticleEditor;