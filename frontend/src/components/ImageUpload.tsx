import React, { useCallback, useState } from 'react';
import { apiService } from '../utils/api';

interface ImageUploadProps {
  onImageUploaded: (imageUrl: string) => void;
  className?: string;
  accept?: string;
  maxSize?: number; // in bytes
}

const ImageUpload: React.FC<ImageUploadProps> = ({
  onImageUploaded,
  className = '',
  accept = 'image/*',
  maxSize = 5 * 1024 * 1024, // 5MB default
}) => {
  const [isDragging, setIsDragging] = useState(false);
  const [isUploading, setIsUploading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const validateFile = (file: File): string | null => {
    if (!file.type.startsWith('image/')) {
      return 'Please select an image file';
    }
    
    if (file.size > maxSize) {
      return `File size must be less than ${Math.round(maxSize / (1024 * 1024))}MB`;
    }
    
    return null;
  };

  const uploadFile = async (file: File) => {
    const validationError = validateFile(file);
    if (validationError) {
      setError(validationError);
      return;
    }

    setIsUploading(true);
    setError(null);

    try {
      const formData = new FormData();
      formData.append('image', file);

      const response = await apiService.upload.image(formData);

      if (response.success) {
        onImageUploaded((response.data as any).url);
      } else {
        setError(response.message || 'Upload failed');
      }
    } catch (error: any) {
      console.error('Upload error:', error);
      setError(error.response?.data?.message || 'Upload failed');
    } finally {
      setIsUploading(false);
    }
  };

  const handleDrop = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(false);

    const files = Array.from(e.dataTransfer.files);
    if (files.length > 0) {
      uploadFile(files[0]);
    }
  }, []);

  const handleDragOver = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(true);
  }, []);

  const handleDragLeave = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(false);
  }, []);

  const handleFileSelect = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
    const files = e.target.files;
    if (files && files.length > 0) {
      uploadFile(files[0]);
    }
  }, []);

  return (
    <div className={className}>
      <div
        onDrop={handleDrop}
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
        className={`
          border-2 border-dashed rounded-lg p-6 text-center transition-colors
          ${isDragging 
            ? 'border-blue-500 bg-blue-50' 
            : 'border-gray-300 hover:border-gray-400'
          }
          ${isUploading ? 'opacity-50 pointer-events-none' : ''}
        `}
      >
        {isUploading ? (
          <div className="flex flex-col items-center">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mb-2"></div>
            <p className="text-gray-600">Uploading...</p>
          </div>
        ) : (
          <div className="flex flex-col items-center">
            <svg
              className="w-12 h-12 text-gray-400 mb-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
              />
            </svg>
            <p className="text-gray-600 mb-2">
              Drag and drop an image here, or{' '}
              <label className="text-blue-500 hover:text-blue-600 cursor-pointer underline">
                browse
                <input
                  type="file"
                  accept={accept}
                  onChange={handleFileSelect}
                  className="hidden"
                />
              </label>
            </p>
            <p className="text-sm text-gray-400">
              Maximum file size: {Math.round(maxSize / (1024 * 1024))}MB
            </p>
          </div>
        )}
      </div>
      
      {error && (
        <p className="text-red-500 text-sm mt-2">{error}</p>
      )}
    </div>
  );
};

export default ImageUpload;