<?php

/**
 * Simple file-based caching service for improved performance
 */
class CacheService {
    private $cacheDir;
    private $defaultTTL;
    
    public function __construct($cacheDir = null, $defaultTTL = 3600) {
        $this->cacheDir = $cacheDir ?: __DIR__ . '/../cache';
        $this->defaultTTL = $defaultTTL;
        
        // Create cache directory if it doesn't exist
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Get cached data
     */
    public function get($key) {
        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $data = file_get_contents($filename);
        $cached = json_decode($data, true);
        
        if (!$cached || !isset($cached['expires']) || !isset($cached['data'])) {
            return null;
        }
        
        // Check if cache has expired
        if (time() > $cached['expires']) {
            $this->delete($key);
            return null;
        }
        
        return $cached['data'];
    }
    
    /**
     * Set cached data
     */
    public function set($key, $data, $ttl = null) {
        $ttl = $ttl ?: $this->defaultTTL;
        $filename = $this->getCacheFilename($key);
        
        $cached = [
            'data' => $data,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        return file_put_contents($filename, json_encode($cached)) !== false;
    }
    
    /**
     * Delete cached data
     */
    public function delete($key) {
        $filename = $this->getCacheFilename($key);
        
        if (file_exists($filename)) {
            return unlink($filename);
        }
        
        return true;
    }
    
    /**
     * Check if key exists in cache
     */
    public function exists($key) {
        return $this->get($key) !== null;
    }
    
    /**
     * Clear all cache
     */
    public function clear() {
        $files = glob($this->cacheDir . '/*.cache');
        
        foreach ($files as $file) {
            unlink($file);
        }
        
        return true;
    }
    
    /**
     * Clear expired cache entries
     */
    public function clearExpired() {
        $files = glob($this->cacheDir . '/*.cache');
        $cleared = 0;
        
        foreach ($files as $file) {
            $data = file_get_contents($file);
            $cached = json_decode($data, true);
            
            if (!$cached || !isset($cached['expires'])) {
                unlink($file);
                $cleared++;
                continue;
            }
            
            if (time() > $cached['expires']) {
                unlink($file);
                $cleared++;
            }
        }
        
        return $cleared;
    }
    
    /**
     * Get cache statistics
     */
    public function getStats() {
        $files = glob($this->cacheDir . '/*.cache');
        $totalSize = 0;
        $totalFiles = count($files);
        $expiredFiles = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
            
            $data = file_get_contents($file);
            $cached = json_decode($data, true);
            
            if ($cached && isset($cached['expires']) && time() > $cached['expires']) {
                $expiredFiles++;
            }
        }
        
        return [
            'total_files' => $totalFiles,
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'expired_files' => $expiredFiles,
            'cache_dir' => $this->cacheDir
        ];
    }
    
    /**
     * Cache with callback - get from cache or execute callback and cache result
     */
    public function remember($key, $callback, $ttl = null) {
        $cached = $this->get($key);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $data = $callback();
        $this->set($key, $data, $ttl);
        
        return $data;
    }
    
    /**
     * Generate cache filename from key
     */
    private function getCacheFilename($key) {
        $hash = md5($key);
        return $this->cacheDir . '/' . $hash . '.cache';
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

/**
 * Global cache instance
 */
class Cache {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new CacheService();
        }
        
        return self::$instance;
    }
    
    public static function get($key) {
        return self::getInstance()->get($key);
    }
    
    public static function set($key, $data, $ttl = null) {
        return self::getInstance()->set($key, $data, $ttl);
    }
    
    public static function delete($key) {
        return self::getInstance()->delete($key);
    }
    
    public static function remember($key, $callback, $ttl = null) {
        return self::getInstance()->remember($key, $callback, $ttl);
    }
    
    public static function clear() {
        return self::getInstance()->clear();
    }
}