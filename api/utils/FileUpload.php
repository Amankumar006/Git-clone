<?php
/**
 * File Upload Handler Class
 */

class FileUpload {
    
    private $allowedTypes;
    private $maxSize;
    private $uploadPath;
    
    public function __construct($allowedTypes = null, $maxSize = null, $uploadPath = null) {
        $this->allowedTypes = $allowedTypes ?? ALLOWED_IMAGE_TYPES;
        $this->maxSize = $maxSize ?? MAX_UPLOAD_SIZE;
        $this->uploadPath = $uploadPath ?? UPLOAD_PATH;
        
        // Ensure upload path is absolute for better security and deployment
        if (!$this->isAbsolutePath($this->uploadPath)) {
            $this->uploadPath = realpath(__DIR__ . '/../' . $this->uploadPath) . '/';
        }
        
        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadPath)) {
            if (!mkdir($this->uploadPath, 0755, true)) {
                throw new Exception('Failed to create upload directory: ' . $this->uploadPath);
            }
        }
        
        // Verify directory is writable
        if (!is_writable($this->uploadPath)) {
            throw new Exception('Upload directory is not writable: ' . $this->uploadPath);
        }
    }
    
    /**
     * Check if path is absolute
     */
    private function isAbsolutePath($path) {
        return (strpos($path, '/') === 0) || (strpos($path, ':\\') === 1);
    }
    
    /**
     * Validate image file and return result
     */
    public function validateImage($file) {
        try {
            if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
                return ['valid' => false, 'errors' => ['No file uploaded']];
            }
            
            // Check for upload errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                return ['valid' => false, 'errors' => [$this->getUploadErrorMessage($file['error'])]];
            }
            
            // Check file size
            if ($file['size'] > $this->maxSize) {
                $maxSizeMB = round($this->maxSize / 1024 / 1024, 2);
                return ['valid' => false, 'errors' => ["File size exceeds maximum allowed size of {$maxSizeMB}MB"]];
            }
            
            // Check MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $this->allowedTypes)) {
                return ['valid' => false, 'errors' => ['Invalid file type. Allowed types: ' . implode(', ', $this->allowedTypes)]];
            }
            
            // Additional security check - verify it's actually an image
            if (strpos($mimeType, 'image/') === 0) {
                $imageInfo = getimagesize($file['tmp_name']);
                if ($imageInfo === false) {
                    return ['valid' => false, 'errors' => ['Invalid image file']];
                }
            }
            
            return ['valid' => true, 'errors' => []];
        } catch (Exception $e) {
            return ['valid' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Upload image file
     */
    public function uploadImage($file, $subdirectory = '') {
        try {
            if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
                return ['success' => false, 'error' => 'No file uploaded'];
            }
            
            // Validate file
            $validation = $this->validateImage($file);
            if (!$validation['valid']) {
                return ['success' => false, 'error' => implode(', ', $validation['errors'])];
            }
            
            // Generate unique filename
            $extension = $this->getFileExtension($file['name']);
            $filename = $this->generateUniqueFilename($extension);
            
            // Create subdirectory if specified
            $targetDir = $this->uploadPath;
            if (!empty($subdirectory)) {
                $targetDir .= rtrim($subdirectory, '/') . '/';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
            }
            
            $targetPath = $targetDir . $filename;
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                return ['success' => false, 'error' => 'Failed to move uploaded file'];
            }
            
            // Return API URL for serving the file
            $relativePath = str_replace($this->uploadPath, '', $targetPath);
            $url = '/api/uploads/' . ltrim($relativePath, '/');
            
            return [
                'success' => true, 
                'url' => $url, 
                'path' => $targetPath,
                'filename' => $filename,
                'size' => filesize($targetPath)
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Validate uploaded file
     */
    private function validateFile($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception($this->getUploadErrorMessage($file['error']));
        }
        
        // Check file size
        if ($file['size'] > $this->maxSize) {
            $maxSizeMB = round($this->maxSize / 1024 / 1024, 2);
            throw new Exception("File size exceeds maximum allowed size of {$maxSizeMB}MB");
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $this->allowedTypes)) {
            throw new Exception('Invalid file type. Allowed types: ' . implode(', ', $this->allowedTypes));
        }
        
        // Additional security check - verify it's actually an image
        if (strpos($mimeType, 'image/') === 0) {
            $imageInfo = getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                throw new Exception('Invalid image file');
            }
        }
    }
    
    /**
     * Generate unique filename
     */
    private function generateUniqueFilename($extension) {
        return uniqid('img_', true) . '.' . $extension;
    }
    
    /**
     * Get file extension
     */
    private function getFileExtension($filename) {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }
    
    /**
     * Get upload error message
     */
    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'File size exceeds maximum allowed size';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }
    
    /**
     * Delete uploaded file
     */
    public function deleteFile($filePath) {
        $fullPath = $this->uploadPath . ltrim($filePath, '/');
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }
    
    /**
     * Resize image (requires GD extension)
     */
    public function resizeImage($sourcePath, $targetPath, $maxWidth, $maxHeight) {
        if (!extension_loaded('gd')) {
            throw new Exception('GD extension is required for image resizing');
        }
        
        $imageInfo = getimagesize($sourcePath);
        if ($imageInfo === false) {
            throw new Exception('Invalid image file');
        }
        
        list($originalWidth, $originalHeight, $imageType) = $imageInfo;
        
        // Calculate new dimensions
        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        $newWidth = intval($originalWidth * $ratio);
        $newHeight = intval($originalHeight * $ratio);
        
        // Create image resources
        $sourceImage = $this->createImageFromType($sourcePath, $imageType);
        $targetImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preserve transparency for PNG and GIF
        if ($imageType === IMAGETYPE_PNG || $imageType === IMAGETYPE_GIF) {
            imagealphablending($targetImage, false);
            imagesavealpha($targetImage, true);
        }
        
        // Resize image
        imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
        
        // Save resized image
        $this->saveImageByType($targetImage, $targetPath, $imageType);
        
        // Clean up
        imagedestroy($sourceImage);
        imagedestroy($targetImage);
        
        return true;
    }
    
    /**
     * Create image resource from file type
     */
    private function createImageFromType($path, $type) {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($path);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($path);
            default:
                throw new Exception('Unsupported image type');
        }
    }
    
    /**
     * Save image by type
     */
    private function saveImageByType($image, $path, $type) {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagejpeg($image, $path, 90);
            case IMAGETYPE_PNG:
                return imagepng($image, $path);
            case IMAGETYPE_GIF:
                return imagegif($image, $path);
            default:
                throw new Exception('Unsupported image type');
        }
    }
}