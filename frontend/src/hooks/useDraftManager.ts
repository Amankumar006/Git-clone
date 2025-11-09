import { useState, useEffect, useCallback } from 'react';
import { apiService } from '../utils/api';

export interface Draft {
  id?: number;
  title: string;
  content: string;
  featuredImage?: string;
  tags: string[];
  status: 'draft' | 'published' | 'archived';
  lastSaved?: string;
  readingTime?: number; // in minutes
}

// Local storage key for draft backup
const DRAFT_STORAGE_KEY = 'medium_clone_draft_backup';

interface UseDraftManagerReturn {
  draft: Draft;
  updateDraft: (updates: Partial<Draft>) => void;
  saveDraft: () => Promise<Draft>;
  publishDraft: (options?: any) => Promise<Draft>;
  deleteDraft: () => Promise<void>;
  loadDraft: (id: number) => Promise<void>;
  isSaving: boolean;
  lastSaved: string | null;
  hasUnsavedChanges: boolean;
}

export const useDraftManager = (initialDraft?: Partial<Draft>): UseDraftManagerReturn => {
  // Load from localStorage on initialization
  const loadFromStorage = (): Draft => {
    try {
      const stored = localStorage.getItem(DRAFT_STORAGE_KEY);
      if (stored) {
        const parsedDraft = JSON.parse(stored);
        // Only use stored draft if it has content and no initialDraft is provided
        if (!initialDraft && (parsedDraft.title || parsedDraft.content)) {
          return {
            title: '',
            content: '',
            tags: [],
            status: 'draft',
            ...parsedDraft,
          };
        }
      }
    } catch (error) {
      console.error('Failed to load draft from localStorage:', error);
    }

    return {
      title: '',
      content: '',
      tags: [],
      status: 'draft',
      ...initialDraft,
    };
  };

  const [draft, setDraft] = useState<Draft>(loadFromStorage);
  const [isSaving, setIsSaving] = useState(false);
  const [lastSaved, setLastSaved] = useState<string | null>(null);
  const [hasUnsavedChanges, setHasUnsavedChanges] = useState(false);

  // Save to localStorage whenever draft changes
  useEffect(() => {
    try {
      localStorage.setItem(DRAFT_STORAGE_KEY, JSON.stringify(draft));
    } catch (error) {
      console.error('Failed to save draft to localStorage:', error);
    }
  }, [draft]);

  // Track changes to mark as unsaved
  useEffect(() => {
    setHasUnsavedChanges(true);
  }, [draft.title, draft.content, draft.tags, draft.featuredImage]);

  const updateDraft = useCallback((updates: Partial<Draft>) => {
    setDraft(prev => ({ ...prev, ...updates }));
  }, []);

  // Clear localStorage when draft is successfully saved to server
  const clearLocalStorage = useCallback(() => {
    try {
      localStorage.removeItem(DRAFT_STORAGE_KEY);
    } catch (error) {
      console.error('Failed to clear localStorage:', error);
    }
  }, []);

  const saveDraft = useCallback(async () => {
    if (isSaving) return draft;

    setIsSaving(true);
    try {
      // Only send fields that the backend expects
      const articleData = {
        title: draft.title,
        content: draft.content,
        tags: draft.tags,
        status: draft.status,
        ...(draft.featuredImage && { featured_image_url: draft.featuredImage })
      };

      const response = draft.id
        ? await apiService.articles.update(draft.id.toString(), articleData)
        : await apiService.articles.create({ ...articleData, status: 'draft' });

      if (response.success) {
        const savedDraft = response.data as any;
        const updatedDraft = { ...draft, id: savedDraft.id };
        setDraft(updatedDraft);
        setLastSaved(new Date().toLocaleTimeString());
        setHasUnsavedChanges(false);
        // Don't clear localStorage immediately - keep as backup until user navigates away
        return updatedDraft;
      }
      return draft;
    } catch (error) {
      console.error('Failed to save draft:', error);
      throw error;
    } finally {
      setIsSaving(false);
    }
  }, [draft, isSaving]);

  const publishDraft = useCallback(async (options?: any) => {
    if (!draft.title.trim() || !draft.content.trim()) {
      throw new Error('Title and content are required to publish');
    }

    setIsSaving(true);
    try {
      // First save as draft if not already saved
      let currentDraft = draft;
      if (!draft.id) {
        currentDraft = await saveDraft();
      }

      // Then publish with options
      if (currentDraft.id) {
        const response = await apiService.post(`/articles/publish/${currentDraft.id}`, {
          allow_comments: options?.allowComments ?? true,
          include_in_search: options?.includeInSearch ?? true,
          notify_followers: options?.notifyFollowers ?? true
        });

        if (response.success) {
          const publishedArticle = (response.data as any)?.article || response.data;
          setDraft(prev => ({ ...prev, ...publishedArticle }));
          setLastSaved(new Date().toLocaleTimeString());
          setHasUnsavedChanges(false);
          // Clear localStorage after successful publish
          clearLocalStorage();
          return publishedArticle;
        } else {
          throw new Error('Failed to publish article');
        }
      } else {
        throw new Error('Article must be saved before publishing');
      }
    } catch (error) {
      console.error('Failed to publish article:', error);
      throw error;
    } finally {
      setIsSaving(false);
    }
  }, [draft, saveDraft]);

  const deleteDraft = useCallback(async () => {
    if (!draft.id) return;

    try {
      await apiService.articles.delete(draft.id.toString());
      // Reset to empty draft
      setDraft({
        title: '',
        content: '',
        tags: [],
        status: 'draft',
      });
      setLastSaved(null);
      setHasUnsavedChanges(false);
      clearLocalStorage();
    } catch (error) {
      console.error('Failed to delete draft:', error);
      throw error;
    }
  }, [draft.id]);

  const loadDraft = useCallback(async (id: number) => {
    console.log('Loading draft with ID:', id);
    try {
      const response = await apiService.articles.getById(id.toString());
      console.log('API response:', response);

      if (response.data.success && response.data.data) {
        // Transform the article data to match Draft interface
        const articleData = response.data.data as any;
        console.log('Article data received:', articleData);

        const loadedDraft: Draft = {
          id: articleData.id,
          title: articleData.title || '',
          content: articleData.content || '',
          tags: Array.isArray(articleData.tags)
            ? articleData.tags.map((tag: any) => typeof tag === 'string' ? tag : tag.name)
            : [],
          status: articleData.status || 'draft',
          featuredImage: articleData.featured_image_url || undefined,
          readingTime: articleData.reading_time || articleData.readingTime || 0
        };

        console.log('Transformed draft:', loadedDraft);
        setDraft(loadedDraft);
        setLastSaved(null);
        setHasUnsavedChanges(false);
        // Clear localStorage when loading existing draft
        clearLocalStorage();
      } else {
        console.error('API response not successful:', response);
        throw new Error('Failed to load article data');
      }
    } catch (error) {
      console.error('Failed to load draft:', error);
      throw error;
    }
  }, [clearLocalStorage]);

  return {
    draft,
    updateDraft,
    saveDraft,
    publishDraft,
    deleteDraft,
    loadDraft,
    isSaving,
    lastSaved,
    hasUnsavedChanges,
  };
};