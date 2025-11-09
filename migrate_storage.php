<?php
/**
 * Storage Migration Script
 * Migrates uploads from development structure to production persistent storage
 */

require_once __DIR__ . '/api/config/config.php';

class StorageMigrator {
    private $oldPath;
    private $newPath;
    
    public function __construct($oldPath = null, $newPath = null) {
        $this->oldPath = $oldPath ?: __DIR__ . '/uploads/';
        $this->newPath = $newPath ?: '/var/storage/medium-clone/uploads/';
    }
    
    public function migrate() {
        echo "🚀 Starting storage migration...\n\n";
        
        // Check if old directory exists
        if (!is_dir($this->oldPath)) {
            echo "❌ Source directory not found: {$this->oldPath}\n";
            return false;
        }
        
        // Create new directory structure
        if (!$this->createDirectoryStructure()) {
            return false;
        }
        
        // Copy files
        if (!$this->copyFiles()) {
            return false;
        }
        
        // Verify migration
        if (!$this->verifyMigration()) {
            return false;
        }
        
        echo "\n✅ Migration completed successfully!\n";
        echo "📝 Next steps:\n";
        echo "   1. Update your .env file: UPLOAD_PATH={$this->newPath}\n";
        echo "   2. Restart your web server\n";
        echo "   3. Test file upload and serving\n";
        echo "   4. Remove old uploads directory: rm -rf {$this->oldPath}\n\n";
        
        return true;
    }
    
    private function createDirectoryStructure() {
        echo "📁 Creating directory structure...\n";
        
        $directories = [
            $this->newPath,
            $this->newPath . 'articles/',
            $this->newPath . 'profiles/',
            $this->newPath . 'publications/'
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    echo "❌ Failed to create directory: $dir\n";
                    return false;
                }
                echo "   ✓ Created: $dir\n";
            } else {
                echo "   ✓ Exists: $dir\n";
            }
        }
        
        return true;
    }
    
    private function copyFiles() {
        echo "\n📋 Copying files...\n";
        
        $totalFiles = 0;
        $copiedFiles = 0;
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->oldPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $totalFiles++;
                
                $relativePath = str_replace($this->oldPath, '', $file->getPathname());
                $newFilePath = $this->newPath . $relativePath;
                
                // Create subdirectory if needed
                $newDir = dirname($newFilePath);
                if (!is_dir($newDir)) {
                    mkdir($newDir, 0755, true);
                }
                
                // Copy file
                if (copy($file->getPathname(), $newFilePath)) {
                    $copiedFiles++;
                    echo "   ✓ Copied: $relativePath\n";
                } else {
                    echo "   ❌ Failed to copy: $relativePath\n";
                }
            }
        }
        
        echo "\n📊 Summary: $copiedFiles/$totalFiles files copied\n";
        return $copiedFiles === $totalFiles;
    }
    
    private function verifyMigration() {
        echo "\n🔍 Verifying migration...\n";
        
        // Check if new directory is writable
        if (!is_writable($this->newPath)) {
            echo "❌ New directory is not writable: {$this->newPath}\n";
            return false;
        }
        echo "   ✓ Directory is writable\n";
        
        // Test file creation
        $testFile = $this->newPath . 'test_write.txt';
        if (file_put_contents($testFile, 'test') === false) {
            echo "❌ Cannot write test file\n";
            return false;
        }
        unlink($testFile);
        echo "   ✓ Write test successful\n";
        
        // Compare file counts
        $oldCount = $this->countFiles($this->oldPath);
        $newCount = $this->countFiles($this->newPath);
        
        if ($oldCount !== $newCount) {
            echo "❌ File count mismatch: old=$oldCount, new=$newCount\n";
            return false;
        }
        echo "   ✓ File count matches: $newCount files\n";
        
        return true;
    }
    
    private function countFiles($directory) {
        $count = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $count++;
            }
        }
        
        return $count;
    }
}

// CLI usage
if (php_sapi_name() === 'cli') {
    $oldPath = $argv[1] ?? null;
    $newPath = $argv[2] ?? null;
    
    echo "📦 Medium Clone Storage Migration Tool\n";
    echo str_repeat("=", 50) . "\n\n";
    
    $migrator = new StorageMigrator($oldPath, $newPath);
    $success = $migrator->migrate();
    
    exit($success ? 0 : 1);
} else {
    echo "This script must be run from the command line.\n";
    echo "Usage: php migrate_storage.php [old_path] [new_path]\n";
}
?>