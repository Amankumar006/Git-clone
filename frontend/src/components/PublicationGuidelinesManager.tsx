import React, { useState, useEffect } from 'react';
import { apiService } from '../utils/api';

interface Guideline {
  id: number;
  title: string;
  content: string;
  category: string;
  is_required: boolean;
  display_order: number;
  created_by: number;
  created_by_username: string;
  created_at: string;
  updated_at: string;
}

interface GuidelineCategory {
  name: string;
  description: string;
}

interface PublicationGuidelinesManagerProps {
  publicationId: number;
  canManage: boolean;
}

const PublicationGuidelinesManager: React.FC<PublicationGuidelinesManagerProps> = ({
  publicationId,
  canManage
}) => {
  const [guidelines, setGuidelines] = useState<Record<string, Guideline[]>>({});
  const [categories, setCategories] = useState<Record<string, GuidelineCategory>>({});
  const [loading, setLoading] = useState(true);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [editingGuideline, setEditingGuideline] = useState<Guideline | null>(null);
  const [selectedCategory, setSelectedCategory] = useState<string>('all');

  const [formData, setFormData] = useState({
    title: '',
    content: '',
    category: 'general',
    is_required: false,
    display_order: 0
  });

  useEffect(() => {
    loadGuidelines();
  }, [publicationId]);

  const loadGuidelines = async () => {
    try {
      const response = await apiService.get(`/workflow/guidelines?publication_id=${publicationId}&grouped=true`);
      if (response.success) {
        const data = response.data as any;
        setGuidelines(data.guidelines);
        setCategories(data.categories);
      }
    } catch (error) {
      console.error('Failed to load guidelines:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleCreateGuideline = async (e: React.FormEvent) => {
    e.preventDefault();
    
    try {
      const endpoint = editingGuideline 
        ? `/workflow/guidelines/${editingGuideline.id}`
        : '/workflow/create-guideline';
      
      const method = editingGuideline ? 'put' : 'post';
      
      const response = editingGuideline 
        ? await apiService.put(endpoint, { publication_id: publicationId, ...formData })
        : await apiService.post(endpoint, { publication_id: publicationId, ...formData });

      if (response.success) {
        loadGuidelines();
        setShowCreateModal(false);
        resetForm();
      }
    } catch (error) {
      console.error('Failed to save guideline:', error);
    }
  };

  const handleDeleteGuideline = async (guidelineId: number) => {
    if (!confirm('Are you sure you want to delete this guideline?')) {
      return;
    }

    try {
      const response = await apiService.delete(`/workflow/guidelines/${guidelineId}`);
      if (response.success) {
        loadGuidelines();
      }
    } catch (error) {
      console.error('Failed to delete guideline:', error);
    }
  };

  const handleReorderGuidelines = async (categoryGuidelines: Guideline[]) => {
    const reorderData = categoryGuidelines.map((guideline, index) => ({
      id: guideline.id,
      display_order: index
    }));

    try {
      const response = await apiService.post(`/workflow/guidelines/reorder`, {
        publication_id: publicationId,
        guidelines: reorderData
      });

      if (response.success) {
        loadGuidelines();
      }
    } catch (error) {
      console.error('Failed to reorder guidelines:', error);
    }
  };

  const createDefaultGuidelines = async () => {
    try {
      const response = await apiService.post('/workflow/guidelines/create-defaults', {
        publication_id: publicationId
      });

      if (response.success) {
        loadGuidelines();
      }
    } catch (error) {
      console.error('Failed to create default guidelines:', error);
    }
  };

  const resetForm = () => {
    setFormData({
      title: '',
      content: '',
      category: 'general',
      is_required: false,
      display_order: 0
    });
    setEditingGuideline(null);
  };

  const editGuideline = (guideline: Guideline) => {
    setFormData({
      title: guideline.title,
      content: guideline.content,
      category: guideline.category,
      is_required: guideline.is_required,
      display_order: guideline.display_order
    });
    setEditingGuideline(guideline);
    setShowCreateModal(true);
  };

  const getFilteredGuidelines = () => {
    if (selectedCategory === 'all') {
      return guidelines;
    }
    return { [selectedCategory]: guidelines[selectedCategory] || [] };
  };

  const getTotalGuidelines = () => {
    return Object.values(guidelines).reduce((total, categoryGuidelines) => total + categoryGuidelines.length, 0);
  };

  const getRequiredGuidelines = () => {
    return Object.values(guidelines).reduce((total, categoryGuidelines) => 
      total + categoryGuidelines.filter(g => g.is_required).length, 0
    );
  };

  if (loading) {
    return (
      <div className="flex justify-center py-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  const filteredGuidelines = getFilteredGuidelines();

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h2 className="text-2xl font-bold text-gray-900">Writing Guidelines</h2>
          <p className="text-gray-600">
            Set guidelines to help writers understand your publication's standards
          </p>
        </div>
        {canManage && (
          <div className="flex space-x-3">
            {getTotalGuidelines() === 0 && (
              <button
                onClick={createDefaultGuidelines}
                className="px-4 py-2 text-sm font-medium text-blue-700 bg-blue-100 border border-blue-300 rounded-md hover:bg-blue-200"
              >
                Create Default Guidelines
              </button>
            )}
            <button
              onClick={() => setShowCreateModal(true)}
              className="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700"
            >
              Add Guideline
            </button>
          </div>
        )}
      </div>

      {/* Stats */}
      <div className="bg-white rounded-lg border border-gray-200 p-6">
        <div className="grid grid-cols-3 gap-4 text-center">
          <div>
            <div className="text-2xl font-bold text-blue-600">{getTotalGuidelines()}</div>
            <div className="text-sm text-gray-500">Total Guidelines</div>
          </div>
          <div>
            <div className="text-2xl font-bold text-orange-600">{getRequiredGuidelines()}</div>
            <div className="text-sm text-gray-500">Required Guidelines</div>
          </div>
          <div>
            <div className="text-2xl font-bold text-green-600">{Object.keys(guidelines).length}</div>
            <div className="text-sm text-gray-500">Categories</div>
          </div>
        </div>
      </div>

      {/* Category Filter */}
      <div className="bg-white rounded-lg border border-gray-200 p-4">
        <div className="flex items-center space-x-4">
          <label className="text-sm font-medium text-gray-700">Filter by category:</label>
          <select
            value={selectedCategory}
            onChange={(e) => setSelectedCategory(e.target.value)}
            className="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="all">All Categories</option>
            {Object.entries(categories).map(([key, category]) => (
              <option key={key} value={key}>{category.name}</option>
            ))}
          </select>
        </div>
      </div>

      {/* Guidelines */}
      {getTotalGuidelines() === 0 ? (
        <div className="bg-white rounded-lg border border-gray-200 p-8 text-center">
          <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <h3 className="mt-2 text-sm font-medium text-gray-900">No guidelines yet</h3>
          <p className="mt-1 text-sm text-gray-500">
            Create guidelines to help writers understand your publication's standards and expectations.
          </p>
        </div>
      ) : (
        <div className="space-y-6">
          {Object.entries(filteredGuidelines).map(([categoryKey, categoryGuidelines]) => (
            <div key={categoryKey} className="bg-white rounded-lg border border-gray-200">
              <div className="px-6 py-4 border-b border-gray-200">
                <div className="flex items-center justify-between">
                  <div>
                    <h3 className="text-lg font-medium text-gray-900">
                      {categories[categoryKey]?.name || categoryKey}
                    </h3>
                    <p className="text-sm text-gray-500">
                      {categories[categoryKey]?.description || ''}
                    </p>
                  </div>
                  <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                    {categoryGuidelines.length} guidelines
                  </span>
                </div>
              </div>

              <div className="divide-y divide-gray-200">
                {categoryGuidelines.map((guideline) => (
                  <div key={guideline.id} className="p-6">
                    <div className="flex items-start justify-between">
                      <div className="flex-1">
                        <div className="flex items-center space-x-2 mb-2">
                          <h4 className="text-lg font-medium text-gray-900">{guideline.title}</h4>
                          {guideline.is_required && (
                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                              Required
                            </span>
                          )}
                        </div>
                        
                        <div className="prose prose-sm max-w-none text-gray-700 mb-3">
                          <div dangerouslySetInnerHTML={{ __html: guideline.content }} />
                        </div>
                        
                        <div className="text-sm text-gray-500">
                          Created by {guideline.created_by_username} • 
                          {new Date(guideline.created_at).toLocaleDateString()}
                          {guideline.updated_at !== guideline.created_at && (
                            <span> • Updated {new Date(guideline.updated_at).toLocaleDateString()}</span>
                          )}
                        </div>
                      </div>

                      {canManage && (
                        <div className="flex space-x-2 ml-4">
                          <button
                            onClick={() => editGuideline(guideline)}
                            className="px-3 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                          >
                            Edit
                          </button>
                          <button
                            onClick={() => handleDeleteGuideline(guideline.id)}
                            className="px-3 py-1 text-sm font-medium text-red-700 bg-red-100 border border-red-300 rounded-md hover:bg-red-200"
                          >
                            Delete
                          </button>
                        </div>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Create/Edit Guideline Modal */}
      {showCreateModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <form onSubmit={handleCreateGuideline} className="p-6">
              <div className="flex justify-between items-center mb-6">
                <h2 className="text-xl font-bold text-gray-900">
                  {editingGuideline ? 'Edit Guideline' : 'Create Guideline'}
                </h2>
                <button
                  type="button"
                  onClick={() => {
                    setShowCreateModal(false);
                    resetForm();
                  }}
                  className="text-gray-400 hover:text-gray-600"
                >
                  <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Guideline Title
                  </label>
                  <input
                    type="text"
                    value={formData.title}
                    onChange={(e) => setFormData(prev => ({ ...prev, title: e.target.value }))}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                    placeholder="e.g., Writing Style Guidelines"
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Category
                  </label>
                  <select
                    value={formData.category}
                    onChange={(e) => setFormData(prev => ({ ...prev, category: e.target.value }))}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    {Object.entries(categories).map(([key, category]) => (
                      <option key={key} value={key}>{category.name}</option>
                    ))}
                  </select>
                  <p className="text-sm text-gray-500 mt-1">
                    {categories[formData.category]?.description}
                  </p>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Content
                  </label>
                  <textarea
                    value={formData.content}
                    onChange={(e) => setFormData(prev => ({ ...prev, content: e.target.value }))}
                    rows={8}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                    placeholder="Describe the guideline in detail. You can use HTML formatting if needed."
                  />
                  <p className="text-sm text-gray-500 mt-1">
                    You can use basic HTML tags for formatting (bold, italic, lists, etc.)
                  </p>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Display Order
                  </label>
                  <input
                    type="number"
                    value={formData.display_order}
                    onChange={(e) => setFormData(prev => ({ ...prev, display_order: parseInt(e.target.value) || 0 }))}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    min="0"
                  />
                  <p className="text-sm text-gray-500 mt-1">
                    Lower numbers appear first. Use 0 for default ordering.
                  </p>
                </div>

                <div className="flex items-center">
                  <input
                    type="checkbox"
                    checked={formData.is_required}
                    onChange={(e) => setFormData(prev => ({ ...prev, is_required: e.target.checked }))}
                    className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  />
                  <label className="ml-2 text-sm text-gray-700">
                    Required guideline (writers must acknowledge before submitting)
                  </label>
                </div>
              </div>

              <div className="flex justify-end space-x-3 mt-6">
                <button
                  type="button"
                  onClick={() => {
                    setShowCreateModal(false);
                    resetForm();
                  }}
                  className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700"
                >
                  {editingGuideline ? 'Update Guideline' : 'Create Guideline'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default PublicationGuidelinesManager;