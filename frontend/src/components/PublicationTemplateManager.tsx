import React, { useState, useEffect } from 'react';
import { apiService } from '../utils/api';

interface Template {
  id: number;
  name: string;
  description?: string;
  template_content: any;
  is_default: boolean;
  is_active: boolean;
  created_by: number;
  created_by_username: string;
  created_at: string;
  updated_at: string;
}

interface PredefinedTemplate {
  name: string;
  description: string;
  template_content: any;
}

interface PublicationTemplateManagerProps {
  publicationId: number;
  canManage: boolean;
}

const PublicationTemplateManager: React.FC<PublicationTemplateManagerProps> = ({
  publicationId,
  canManage
}) => {
  const [templates, setTemplates] = useState<Template[]>([]);
  const [predefinedTemplates, setPredefinedTemplates] = useState<Record<string, PredefinedTemplate>>({});
  const [loading, setLoading] = useState(true);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [showPreviewModal, setShowPreviewModal] = useState(false);
  const [selectedTemplate, setSelectedTemplate] = useState<Template | null>(null);
  const [editingTemplate, setEditingTemplate] = useState<Template | null>(null);

  const [formData, setFormData] = useState<{
    name: string;
    description: string;
    template_content: {
      sections: Array<{
        type: string;
        title: string;
        placeholder: string;
        required: boolean;
        description: string;
      }>;
      formatting: any;
    };
    is_default: boolean;
    is_active: boolean;
  }>({
    name: '',
    description: '',
    template_content: {
      sections: [],
      formatting: {}
    },
    is_default: false,
    is_active: true
  });

  useEffect(() => {
    loadTemplates();
  }, [publicationId]);

  const loadTemplates = async () => {
    try {
      const response = await apiService.workflow.getTemplates(publicationId);
      if (response.data.success) {
        setTemplates(response.data.data.templates);
        setPredefinedTemplates(response.data.data.predefined);
      }
    } catch (error) {
      console.error('Failed to load templates:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleCreateTemplate = async (e: React.FormEvent) => {
    e.preventDefault();
    
    try {
      const response = editingTemplate
        ? await apiService.workflow.updateTemplate(editingTemplate.id, formData)
        : await apiService.workflow.createTemplate({
            publication_id: publicationId,
            ...formData
          });

      if (response.data.success) {
        loadTemplates();
        setShowCreateModal(false);
        resetForm();
      }
    } catch (error) {
      console.error('Failed to create/update template:', error);
    }
  };

  const handleUpdateTemplate = async (templateId: number, updates: Partial<Template>) => {
    try {
      const response = await apiService.workflow.updateTemplate(templateId, updates);
      if (response.data.success) {
        loadTemplates();
      }
    } catch (error) {
      console.error('Failed to update template:', error);
    }
  };

  const handleDeleteTemplate = async (templateId: number) => {
    if (!confirm('Are you sure you want to delete this template?')) {
      return;
    }

    try {
      const response = await apiService.workflow.deleteTemplate(templateId);
      if (response.data.success) {
        loadTemplates();
      }
    } catch (error) {
      console.error('Failed to delete template:', error);
    }
  };

  const handleSetDefault = async (templateId: number) => {
    try {
      const response = await apiService.workflow.setDefaultTemplate(templateId);
      if (response.data.success) {
        loadTemplates();
      }
    } catch (error) {
      console.error('Failed to set default template:', error);
    }
  };

  const createFromPredefined = (type: string, predefined: PredefinedTemplate) => {
    setFormData({
      name: predefined.name,
      description: predefined.description,
      template_content: predefined.template_content,
      is_default: templates.length === 0,
      is_active: true
    });
    setShowCreateModal(true);
  };

  const resetForm = () => {
    setFormData({
      name: '',
      description: '',
      template_content: {
        sections: [],
        formatting: {}
      },
      is_default: false,
      is_active: true
    });
    setEditingTemplate(null);
  };

  const addSection = () => {
    setFormData(prev => ({
      ...prev,
      template_content: {
        ...prev.template_content,
        sections: [
          ...prev.template_content.sections,
          {
            type: 'content',
            title: '',
            placeholder: '',
            required: false,
            description: ''
          }
        ]
      }
    }));
  };

  const updateSection = (index: number, updates: any) => {
    setFormData(prev => ({
      ...prev,
      template_content: {
        ...prev.template_content,
        sections: prev.template_content.sections.map((section: any, i: number) =>
          i === index ? { ...section, ...updates } : section
        )
      }
    }));
  };

  const removeSection = (index: number) => {
    setFormData(prev => ({
      ...prev,
      template_content: {
        ...prev.template_content,
        sections: prev.template_content.sections.filter((_: any, i: number) => i !== index)
      }
    }));
  };

  if (loading) {
    return (
      <div className="flex justify-center py-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h2 className="text-2xl font-bold text-gray-900">Article Templates</h2>
          <p className="text-gray-600">Manage templates to help writers create consistent content</p>
        </div>
        {canManage && (
          <button
            onClick={() => setShowCreateModal(true)}
            className="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Create Template
          </button>
        )}
      </div>

      {/* Predefined Templates */}
      {canManage && Object.keys(predefinedTemplates).length > 0 && (
        <div className="bg-white rounded-lg border border-gray-200 p-6">
          <h3 className="text-lg font-medium text-gray-900 mb-4">Quick Start Templates</h3>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {Object.entries(predefinedTemplates).map(([type, template]) => (
              <div key={type} className="border border-gray-200 rounded-lg p-4">
                <h4 className="font-medium text-gray-900 mb-2">{template.name}</h4>
                <p className="text-sm text-gray-600 mb-3">{template.description}</p>
                <button
                  onClick={() => createFromPredefined(type, template)}
                  className="w-full px-3 py-2 text-sm font-medium text-blue-700 bg-blue-100 border border-blue-300 rounded-md hover:bg-blue-200"
                >
                  Use This Template
                </button>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Existing Templates */}
      <div className="bg-white rounded-lg border border-gray-200">
        <div className="px-6 py-4 border-b border-gray-200">
          <h3 className="text-lg font-medium text-gray-900">
            Publication Templates ({templates.length})
          </h3>
        </div>

        {templates.length === 0 ? (
          <div className="text-center py-8">
            <svg className="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 className="mt-2 text-sm font-medium text-gray-900">No templates</h3>
            <p className="mt-1 text-sm text-gray-500">
              Create your first template to help writers structure their articles.
            </p>
          </div>
        ) : (
          <div className="divide-y divide-gray-200">
            {templates.map((template) => (
              <div key={template.id} className="p-6">
                <div className="flex items-start justify-between">
                  <div className="flex-1">
                    <div className="flex items-center space-x-2 mb-2">
                      <h4 className="text-lg font-medium text-gray-900">{template.name}</h4>
                      {template.is_default && (
                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                          Default
                        </span>
                      )}
                      {!template.is_active && (
                        <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                          Inactive
                        </span>
                      )}
                    </div>
                    
                    {template.description && (
                      <p className="text-gray-600 mb-3">{template.description}</p>
                    )}
                    
                    <div className="text-sm text-gray-500 mb-3">
                      {template.template_content.sections?.length || 0} sections • 
                      Created by {template.created_by_username} • 
                      {new Date(template.created_at).toLocaleDateString()}
                    </div>

                    {/* Template Sections Preview */}
                    {template.template_content.sections && template.template_content.sections.length > 0 && (
                      <div className="bg-gray-50 rounded-lg p-3">
                        <div className="text-sm font-medium text-gray-700 mb-2">Sections:</div>
                        <div className="flex flex-wrap gap-2">
                          {template.template_content.sections.slice(0, 3).map((section: any, index: number) => (
                            <span key={index} className="px-2 py-1 bg-white text-gray-600 rounded text-xs border">
                              {section.title || section.type}
                              {section.required && <span className="text-red-500 ml-1">*</span>}
                            </span>
                          ))}
                          {template.template_content.sections.length > 3 && (
                            <span className="px-2 py-1 text-gray-500 text-xs">
                              +{template.template_content.sections.length - 3} more
                            </span>
                          )}
                        </div>
                      </div>
                    )}
                  </div>

                  <div className="flex space-x-2 ml-4">
                    <button
                      onClick={() => {
                        setSelectedTemplate(template);
                        setShowPreviewModal(true);
                      }}
                      className="px-3 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                    >
                      Preview
                    </button>
                    
                    {canManage && (
                      <>
                        {!template.is_default && (
                          <button
                            onClick={() => handleSetDefault(template.id)}
                            className="px-3 py-1 text-sm font-medium text-blue-700 bg-blue-100 border border-blue-300 rounded-md hover:bg-blue-200"
                          >
                            Set Default
                          </button>
                        )}
                        
                        <button
                          onClick={() => {
                            setEditingTemplate(template);
                            setFormData({
                              name: template.name,
                              description: template.description || '',
                              template_content: template.template_content,
                              is_default: template.is_default,
                              is_active: template.is_active
                            });
                            setShowCreateModal(true);
                          }}
                          className="px-3 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                        >
                          Edit
                        </button>
                        
                        <button
                          onClick={() => handleDeleteTemplate(template.id)}
                          className="px-3 py-1 text-sm font-medium text-red-700 bg-red-100 border border-red-300 rounded-md hover:bg-red-200"
                        >
                          Delete
                        </button>
                      </>
                    )}
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Create/Edit Template Modal */}
      {showCreateModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <form onSubmit={handleCreateTemplate} className="p-6">
              <div className="flex justify-between items-center mb-6">
                <h2 className="text-xl font-bold text-gray-900">
                  {editingTemplate ? 'Edit Template' : 'Create Template'}
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
                    Template Name
                  </label>
                  <input
                    type="text"
                    value={formData.name}
                    onChange={(e) => setFormData(prev => ({ ...prev, name: e.target.value }))}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Description
                  </label>
                  <textarea
                    value={formData.description}
                    onChange={(e) => setFormData(prev => ({ ...prev, description: e.target.value }))}
                    rows={3}
                    className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>

                {/* Template Sections */}
                <div>
                  <div className="flex justify-between items-center mb-3">
                    <label className="block text-sm font-medium text-gray-700">
                      Template Sections
                    </label>
                    <button
                      type="button"
                      onClick={addSection}
                      className="px-3 py-1 text-sm font-medium text-blue-700 bg-blue-100 border border-blue-300 rounded-md hover:bg-blue-200"
                    >
                      Add Section
                    </button>
                  </div>

                  <div className="space-y-3">
                    {formData.template_content.sections.map((section: any, index: number) => (
                      <div key={index} className="border border-gray-200 rounded-lg p-4">
                        <div className="flex justify-between items-start mb-3">
                          <h4 className="text-sm font-medium text-gray-900">Section {index + 1}</h4>
                          <button
                            type="button"
                            onClick={() => removeSection(index)}
                            className="text-red-500 hover:text-red-700"
                          >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                            </svg>
                          </button>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                          <div>
                            <label className="block text-xs font-medium text-gray-700 mb-1">
                              Section Title
                            </label>
                            <input
                              type="text"
                              value={section.title || ''}
                              onChange={(e) => updateSection(index, { title: e.target.value })}
                              className="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
                            />
                          </div>

                          <div>
                            <label className="block text-xs font-medium text-gray-700 mb-1">
                              Section Type
                            </label>
                            <select
                              value={section.type || 'content'}
                              onChange={(e) => updateSection(index, { type: e.target.value })}
                              className="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
                            >
                              <option value="introduction">Introduction</option>
                              <option value="body">Body</option>
                              <option value="conclusion">Conclusion</option>
                              <option value="content">General Content</option>
                              <option value="overview">Overview</option>
                              <option value="steps">Steps</option>
                              <option value="pros">Pros</option>
                              <option value="cons">Cons</option>
                            </select>
                          </div>
                        </div>

                        <div className="mt-3">
                          <label className="block text-xs font-medium text-gray-700 mb-1">
                            Description/Instructions
                          </label>
                          <textarea
                            value={section.description || ''}
                            onChange={(e) => updateSection(index, { description: e.target.value })}
                            rows={2}
                            className="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
                            placeholder="Instructions for writers on what to include in this section"
                          />
                        </div>

                        <div className="mt-3 flex items-center">
                          <input
                            type="checkbox"
                            checked={section.required || false}
                            onChange={(e) => updateSection(index, { required: e.target.checked })}
                            className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                          />
                          <label className="ml-2 text-xs text-gray-700">
                            Required section
                          </label>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>

                <div className="flex items-center space-x-4">
                  <label className="flex items-center">
                    <input
                      type="checkbox"
                      checked={formData.is_default}
                      onChange={(e) => setFormData(prev => ({ ...prev, is_default: e.target.checked }))}
                      className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    />
                    <span className="ml-2 text-sm text-gray-700">Set as default template</span>
                  </label>

                  <label className="flex items-center">
                    <input
                      type="checkbox"
                      checked={formData.is_active}
                      onChange={(e) => setFormData(prev => ({ ...prev, is_active: e.target.checked }))}
                      className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    />
                    <span className="ml-2 text-sm text-gray-700">Active</span>
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
                  {editingTemplate ? 'Update Template' : 'Create Template'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Preview Modal */}
      {showPreviewModal && selectedTemplate && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div className="p-6">
              <div className="flex justify-between items-center mb-6">
                <h2 className="text-xl font-bold text-gray-900">
                  Template Preview: {selectedTemplate.name}
                </h2>
                <button
                  onClick={() => setShowPreviewModal(false)}
                  className="text-gray-400 hover:text-gray-600"
                >
                  <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              {selectedTemplate.description && (
                <p className="text-gray-600 mb-6">{selectedTemplate.description}</p>
              )}

              <div className="space-y-4">
                {selectedTemplate.template_content.sections?.map((section: any, index: number) => (
                  <div key={index} className="border border-gray-200 rounded-lg p-4">
                    <div className="flex items-center justify-between mb-2">
                      <h3 className="font-medium text-gray-900">{section.title || section.type}</h3>
                      {section.required && (
                        <span className="text-xs text-red-500 font-medium">Required</span>
                      )}
                    </div>
                    {section.description && (
                      <p className="text-sm text-gray-600 mb-2">{section.description}</p>
                    )}
                    <div className="bg-gray-50 rounded p-3 text-sm text-gray-500">
                      [Content for {section.title || section.type} section would go here]
                    </div>
                  </div>
                ))}
              </div>

              <div className="flex justify-end mt-6">
                <button
                  onClick={() => setShowPreviewModal(false)}
                  className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                >
                  Close
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default PublicationTemplateManager;