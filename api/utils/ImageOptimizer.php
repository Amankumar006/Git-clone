<?php

/**
 * Image optimization service for better performance
 */
class ImageOptimizer {
    private $uploadDir;
    private $maxWidth;
    private $maxHeight;
    private $quality;
    
    public function __construct($uploadDir = null, $maxWidth = 1200, $maxHeight = 800, $quality = 85) {
        $this->uploadDir = $uploadDir ?: __DIR__ . '/../uploads';
        $this->maxWidth = $maxWidth;
        $this->maxHeight = $maxHeight;
        $this->quality = $quality;
    }
    
    /**
     * Optimize uploaded image
     */
    public function optimizeImage($sourcePath, $targetPath = null, $options = []) {
        if (!file_exists($sourcePath)) {
            throw new Exception('Source image not found');
        }
        
        $targetPath = $targetPath ?: $sourcePath;
        $maxWidth = $options['max_width'] ?? $this->maxWidth;
        $maxHeight = $options['max_height'] ?? $this->maxHeight;
        $quality = $options['quality'] ?? $this->quality;
        
        // Get image info
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            throw new Exception('Invalid image file');
        }
        
        $originalWidth = $imageInfo[0];
        $originalHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];
        
        // Calculate new dimensions
        $dimensions = $this->calculateDimensions($originalWidth, $originalHeight, $maxWidth, $maxHeight);
        
        // Create image resource from source
        $sourceImage = $this->createImageFromFile($sourcePath, $mimeType);
        if (!$sourceImage) {
            throw new Exception('Failed to create image resource');
        }
        
        // Create new image with calculated dimensions
        $optimizedImage = imagecreatetruecolor($dimensions['width'], $dimensions['height']);
        
        // Preserve transparency for PNG and GIF
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($optimizedImage, false);
            imagesavealpha($optimizedImage, true);
            $transparent = imagecolorallocatealpha($optimizedImage, 255, 255, 255, 127);
            imagefill($optimizedImage, 0, 0, $transparent);
        }
        
        // Resize image
        imagecopyresampled(
            $optimizedImage, $sourceImage,
            0, 0, 0, 0,
            $dimensions['width'], $dimensions['height'],
            $originalWidth, $originalHeight
        );
        
        // Save optimized image
        $success = $this->saveOptimizedImage($optimizedImage, $targetPath, $mimeType, $quality);
        
        // Clean up memory
        imagedestroy($sourceImage);
        imagedestroy($optimizedImage);
        
        if (!$success) {
            throw new Exception('Failed to save optimized image');
        }
        
        return [
            'original_size' => filesize($sourcePath),
            'optimized_size' => filesize($targetPath),
            'original_dimensions' => ['width' => $originalWidth, 'height' => $originalHeight],
            'optimized_dimensions' => $dimensions,
            'compression_ratio' => round((1 - filesize($targetPath) / filesize($sourcePath)) * 100, 2)
        ];
    }
    
    /**
     * Generate multiple image sizes (thumbnails)
     */
    public function generateThumbnails($sourcePath, $sizes = []) {
        $defaultSizes = [
            'thumbnail' => ['width' => 150, 'height' => 150],
            'small' => ['width' => 300, 'height' => 200],
            'medium' => ['width' => 600, 'height' => 400],
            'large' => ['width' => 1200, 'height' => 800]
        ];
        
        $sizes = array_merge($defaultSizes, $sizes);
        $results = [];
        
        $pathInfo = pathinfo($sourcePath);
        $baseName = $pathInfo['filename'];
        $extension = $pathInfo['extension'];
        $directory = $pathInfo['dirname'];
        
        foreach ($sizes as $sizeName => $dimensions) {
            $thumbnailPath = $directory . '/' . $baseName . '_' . $sizeName . '.' . $extension;
            
            try {
                $result = $this->optimizeImage($sourcePath, $thumbnailPath, [
                    'max_width' => $dimensions['width'],
                    'max_height' => $dimensions['height']
                ]);
                
                $results[$sizeName] = [
                    'path' => $thumbnailPath,
                    'url' => $this->getImageUrl($thumbnailPath),
                    'dimensions' => $result['optimized_dimensions'],
                    'size' => $result['optimized_size']
                ];
            } catch (Exception $e) {
                error_log("Failed to generate {$sizeName} thumbnail: " . $e->getMessage());
            }
        }
        
        return $results;
    }
    
    /**
     * Convert image to WebP format for better compression
     */
    public function convertToWebP($sourcePath, $targetPath = null, $quality = 80) {
        if (!function_exists('imagewebp')) {
            throw new Exception('WebP support not available');
        }
        
        $targetPath = $targetPath ?: str_replace(['.jpg', '.jpeg', '.png'], '.webp', $sourcePath);
        
        $imageInfo = getimagesize($sourcePath);
        $mimeType = $imageInfo['mime'];
        
        $sourceImage = $this->createImageFromFile($sourcePath, $mimeType);
        if (!$sourceImage) {
            throw new Exception('Failed to create image resource');
        }
        
        $success = imagewebp($sourceImage, $targetPath, $quality);
        imagedestroy($sourceImage);
        
        if (!$success) {
            throw new Exception('Failed to convert to WebP');
        }
        
        return [
            'original_path' => $sourcePath,
            'webp_path' => $targetPath,
            'original_size' => filesize($sourcePath),
            'webp_size' => filesize($targetPath),
            'compression_ratio' => round((1 - filesize($targetPath) / filesize($sourcePath)) * 100, 2)
        ];
    }
    
    /**
     * Generate responsive image srcset
     */
    public function generateSrcSet($imagePath, $sizes = []) {
        $defaultSizes = [480, 768, 1024, 1200, 1600];
        $sizes = $sizes ?: $defaultSizes;
        
        $pathInfo = pathinfo($imagePath);
        $baseName = $pathInfo['filename'];
        $extension = $pathInfo['extension'];
        $directory = $pathInfo['dirname'];
        
        $srcSet = [];
        
        foreach ($sizes as $width) {
            $resizedPath = $directory . '/' . $baseName . '_' . $width . 'w.' . $extension;
            
            try {
                $this->optimizeImage($imagePath, $resizedPath, [
                    'max_width' => $width,
                    'max_height' => $width * 0.75 // Maintain aspect ratio
                ]);
                
                $srcSet[] = $this->getImageUrl($resizedPath) . ' ' . $width . 'w';
            } catch (Exception $e) {
                error_log("Failed to generate {$width}w image: " . $e->getMessage());
            }
        }
        
        return implode(', ', $srcSet);
    }
    
    /**
     * Calculate optimal dimensions while maintaining aspect ratio
     */
    private function calculateDimensions($originalWidth, $originalHeight, $maxWidth, $maxHeight) {
        $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
        
        // Don't upscale images
        if ($ratio > 1) {
            $ratio = 1;
        }
        
        return [
            'width' => round($originalWidth * $ratio),
            'height' => round($originalHeight * $ratio)
        ];
    }
    
    /**
     * Create image resource from file
     */
    private function createImageFromFile($filePath, $mimeType) {
        switch ($mimeType) {
            case 'image/jpeg':
                return imagecreatefromjpeg($filePath);
            case 'image/png':
                return imagecreatefrompng($filePath);
            case 'image/gif':
                return imagecreatefromgif($filePath);
            case 'image/webp':
                return imagecreatefromwebp($filePath);
            default:
                return false;
        }
    }
    
    /**
     * Save optimized image
     */
    private function saveOptimizedImage($imageResource, $filePath, $mimeType, $quality) {
        switch ($mimeType) {
            case 'image/jpeg':
                return imagejpeg($imageResource, $filePath, $quality);
            case 'image/png':
                // PNG quality is 0-9, convert from 0-100
                $pngQuality = round((100 - $quality) / 10);
                return imagepng($imageResource, $filePath, $pngQuality);
            case 'image/gif':
                return imagegif($imageResource, $filePath);
            case 'image/webp':
                return imagewebp($imageResource, $filePath, $quality);
            default:
                return false;
        }
    }
    
    /**
     * Get image URL from path
     */
    private function getImageUrl($imagePath) {
        $relativePath = str_replace($this->uploadDir, '', $imagePath);
        $baseUrl = $this->getBaseUrl();
        return $baseUrl . '/uploads' . $relativePath;
    }
    
    /**
     * Get base URL
     */
    private function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }
}