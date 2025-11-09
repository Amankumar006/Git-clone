import React, { useState, useEffect } from 'react';
import { Publication } from '../types';
import ImageUpload from './ImageUpload';

interface PublicationFormProps {
  publication?: Publication;
  onSubmit: (data: PublicationFormData) => void;
  onCancel: () => void;
  isLoading?: boolean;
}

export interface PublicationFormData {
  name: string;
  description: string;
  logo_url?: string;
  website_url?: string;
  social_links?: {
    twitter?: string;
    facebook?: string;
    linkedin?: string;
    instagram?: string;
  };
  theme_color?: string;
  custom_css?: string;
}

const PublicationForm: React.FC<PublicationFormProps> = ({
  publication,
  onSubmit,
  onCancel,
  isLoading = false
}) => {
  const [formData, setFormData] = useState<PublicationFormData>({
    name: publication?.name || '',
    description: publication?.description || '',
    logo_url: publication?.logo_url || '',
    website_url: publication?.website_url || '',
    social_links: {
      twitter: publication?.social_links?.twitter || '',
      facebook: publication?.social_links?.facebook || '',
      linkedin: publication?.social_links?.linkedin || '',
      instagram: publication?.social_links?.instagram || ''
    },
    theme_color: publication?.theme_color || '#3B82F6',
    custom_css: publication?.custom_css || ''
  });
  
  const [errors, setErrors] = useState<Record<string, string>>({});

  useEffect(() => {
    if (publication) {
      setFormData({
        name: publication.name,
        description: publication.description || '',
        logo_url: publication.logo_url || '',
        website_url: publication.website_url || '',
        social_links: {
          twitter: publication.social_links?.twitter || '',
          facebook: publication.social_links?.facebook || '',
          linkedin: publication.social_links?.linkedin || '',
          instagram: publication.social_links?.instagram || ''
        },
        theme_color: publication.theme_color || '#3B82F6',
        custom_css: publication.custom_css || ''
      });
    }
  }, [publication]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    // Validate form
    const newErrors: Record<string, string> = {};
    
    if (!formData.name.trim()) {
      newErrors.name = 'Publication name is required';
    }
    
    if (formData.name.length > 100) {
      newErrors.name = 'Publication name must be less than 100 characters';
    }
    
    if (formData.description.length > 1000) {
      newErrors.description = 'Description must be less than 1000 characters';
    }

    if (formData.website_url && !isValidUrl(formData.website_url)) {
      newErrors.website_url = 'Please enter a valid URL';
    }

    // Validate social links
    const socialLinks = formData.social_links || {};
    Object.entries(socialLinks).forEach(([platform, url]) => {
      if (url && !isValidUrl(url)) {
        newErrors[`social_${platform}`] = `Please enter a valid ${platform} URL`;
      }
    });
    
    setErrors(newErrors);
    
    if (Object.keys(newErrors).length === 0) {
      onSubmit(formData);
    }
  };

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
    
    // Clear error when user starts typing
    if (errors[name]) {
      setErrors(prev => ({ ...prev, [name]: '' }));
    }
  };

  const handleLogoUpload = (url: string) => {
    setFormData(prev => ({ ...prev, logo_url: url }));
  };

  const handleLogoRemove = () => {
    setFormData(prev => ({ ...prev, logo_url: '' }));
  };

  const handleSocialLinkChange = (platform: string, value: string) => {
    setFormData(prev => ({
      ...prev,
      social_links: {
        ...prev.social_links,
        [platform]: value
      }
    }));
  };

  const isValidUrl = (url: string): boolean => {
    try {
      new URL(url);
      return true;
    } catch {
      return false;
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      <div>
        <label htmlFor="name" className="block text-sm font-medium text-gray-700 mb-2">
          Publication Name *
        </label>
        <input
          type="text"
          id="name"
          name="name"
          value={formData.name}
          onChange={handleInputChange}
          className={`w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 ${
            errors.name ? 'border-red-500' : 'border-gray-300'
          }`}
          placeholder="Enter publication name"
          maxLength={100}
        />
        {errors.name && (
          <p className="mt-1 text-sm text-red-600">{errors.name}</p>
        )}
      </div>

      <div>
        <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-2">
          Description
        </label>
        <textarea
          id="description"
          name="description"
          value={formData.description}
          onChange={handleInputChange}
          rows={4}
          className={`w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 ${
            errors.description ? 'border-red-500' : 'border-gray-300'
          }`}
          placeholder="Describe your publication..."
          maxLength={1000}
        />
        {errors.description && (
          <p className="mt-1 text-sm text-red-600">{errors.description}</p>
        )}
        <p className="mt-1 text-sm text-gray-500">
          {formData.description.length}/1000 characters
        </p>
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Publication Logo
        </label>
        <ImageUpload
          onImageUploaded={handleLogoUpload}
          accept="image/jpeg,image/png,image/gif"
          maxSize={2 * 1024 * 1024} // 2MB
          className="w-32 h-32"
        />
        {formData.logo_url && (
          <div className="mt-2">
            <img src={formData.logo_url} alt="Current logo" className="w-16 h-16 rounded object-cover" />
            <button
              type="button"
              onClick={handleLogoRemove}
              className="ml-2 text-sm text-red-600 hover:text-red-800"
            >
              Remove
            </button>
          </div>
        )}
        <p className="mt-1 text-sm text-gray-500">
          Recommended size: 200x200px. Max file size: 2MB.
        </p>
      </div>

      <div>
        <label htmlFor="website_url" className="block text-sm font-medium text-gray-700 mb-2">
          Website URL
        </label>
        <input
          type="url"
          id="website_url"
          name="website_url"
          value={formData.website_url}
          onChange={handleInputChange}
          className={`w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 ${
            errors.website_url ? 'border-red-500' : 'border-gray-300'
          }`}
          placeholder="https://yourwebsite.com"
        />
        {errors.website_url && (
          <p className="mt-1 text-sm text-red-600">{errors.website_url}</p>
        )}
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-2">
          Social Media Links
        </label>
        <div className="space-y-3">
          <div>
            <label htmlFor="twitter" className="block text-xs font-medium text-gray-600 mb-1">
              Twitter
            </label>
            <input
              type="url"
              id="twitter"
              value={formData.social_links?.twitter || ''}
              onChange={(e) => handleSocialLinkChange('twitter', e.target.value)}
              className={`w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                errors.social_twitter ? 'border-red-500' : 'border-gray-300'
              }`}
              placeholder="https://twitter.com/yourhandle"
            />
            {errors.social_twitter && (
              <p className="mt-1 text-sm text-red-600">{errors.social_twitter}</p>
            )}
          </div>
          <div>
            <label htmlFor="facebook" className="block text-xs font-medium text-gray-600 mb-1">
              Facebook
            </label>
            <input
              type="url"
              id="facebook"
              value={formData.social_links?.facebook || ''}
              onChange={(e) => handleSocialLinkChange('facebook', e.target.value)}
              className={`w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                errors.social_facebook ? 'border-red-500' : 'border-gray-300'
              }`}
              placeholder="https://facebook.com/yourpage"
            />
            {errors.social_facebook && (
              <p className="mt-1 text-sm text-red-600">{errors.social_facebook}</p>
            )}
          </div>
          <div>
            <label htmlFor="linkedin" className="block text-xs font-medium text-gray-600 mb-1">
              LinkedIn
            </label>
            <input
              type="url"
              id="linkedin"
              value={formData.social_links?.linkedin || ''}
              onChange={(e) => handleSocialLinkChange('linkedin', e.target.value)}
              className={`w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                errors.social_linkedin ? 'border-red-500' : 'border-gray-300'
              }`}
              placeholder="https://linkedin.com/company/yourcompany"
            />
            {errors.social_linkedin && (
              <p className="mt-1 text-sm text-red-600">{errors.social_linkedin}</p>
            )}
          </div>
          <div>
            <label htmlFor="instagram" className="block text-xs font-medium text-gray-600 mb-1">
              Instagram
            </label>
            <input
              type="url"
              id="instagram"
              value={formData.social_links?.instagram || ''}
              onChange={(e) => handleSocialLinkChange('instagram', e.target.value)}
              className={`w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                errors.social_instagram ? 'border-red-500' : 'border-gray-300'
              }`}
              placeholder="https://instagram.com/yourhandle"
            />
            {errors.social_instagram && (
              <p className="mt-1 text-sm text-red-600">{errors.social_instagram}</p>
            )}
          </div>
        </div>
      </div>

      <div>
        <label htmlFor="theme_color" className="block text-sm font-medium text-gray-700 mb-2">
          Theme Color
        </label>
        <div className="flex items-center space-x-3">
          <input
            type="color"
            id="theme_color"
            name="theme_color"
            value={formData.theme_color}
            onChange={handleInputChange}
            className="w-12 h-10 border border-gray-300 rounded-md cursor-pointer"
          />
          <input
            type="text"
            value={formData.theme_color}
            onChange={handleInputChange}
            name="theme_color"
            className="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="#3B82F6"
          />
        </div>
        <p className="mt-1 text-sm text-gray-500">
          This color will be used for your publication's branding elements.
        </p>
      </div>

      <div>
        <label htmlFor="custom_css" className="block text-sm font-medium text-gray-700 mb-2">
          Custom CSS (Advanced)
        </label>
        <textarea
          id="custom_css"
          name="custom_css"
          value={formData.custom_css}
          onChange={handleInputChange}
          rows={6}
          className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"
          placeholder="/* Add custom CSS for your publication */
.publication-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}"
        />
        <p className="mt-1 text-sm text-gray-500">
          Add custom CSS to style your publication pages. Use with caution.
        </p>
      </div>

      <div className="flex justify-end space-x-3 pt-4 border-t">
        <button
          type="button"
          onClick={onCancel}
          className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          disabled={isLoading}
        >
          Cancel
        </button>
        <button
          type="submit"
          className="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          disabled={isLoading}
        >
          {isLoading ? 'Saving...' : publication ? 'Update Publication' : 'Create Publication'}
        </button>
      </div>
    </form>
  );
};

export default PublicationForm;