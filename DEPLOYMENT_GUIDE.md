# Deployment Guide - Persistent File Storage

## Overview

This guide explains how to properly deploy the Medium Clone application with persistent file storage that survives deployments.

## The Problem

By default, user-uploaded files are stored in the `uploads/` directory within the project. During deployment, this directory could be overwritten, causing **permanent loss of user content**.

## The Solution: Persistent Storage

### 1. Directory Structure

**Development (Current):**
```
project-root/
├── api/
├── frontend/
├── uploads/          ← VULNERABLE to deployment overwrites
└── database/
```

**Production (Recommended):**
```
/var/www/
├── medium-clone/     ← Application code (deployment target)
│   ├── api/
│   ├── frontend/
│   └── database/
└── storage/
    └── medium-clone/ ← Persistent storage (NEVER touched by deployment)
        └── uploads/
            ├── articles/
            ├── profiles/
            └── publications/
```

### 2. Production Setup

#### Step 1: Create Persistent Storage Directory
```bash
# Create persistent storage outside web root
sudo mkdir -p /var/storage/medium-clone/uploads/{articles,profiles,publications}

# Set proper ownership and permissions
sudo chown -R www-data:www-data /var/storage/medium-clone/
sudo chmod -R 755 /var/storage/medium-clone/
```

#### Step 2: Update Environment Configuration
```bash
# In production .env file
UPLOAD_PATH=/var/storage/medium-clone/uploads/
```

#### Step 3: Verify Setup
```bash
# Test directory creation and permissions
php -r "
$path = '/var/storage/medium-clone/uploads/test/';
if (mkdir($path, 0755, true)) {
    echo 'Directory creation: SUCCESS\n';
    rmdir($path);
} else {
    echo 'Directory creation: FAILED\n';
}
"
```

### 3. File Serving

Our application uses a **proxy serving approach**:

1. **Files are stored** in persistent storage (outside web root)
2. **Files are served** via `/api/uploads/*` endpoint
3. **Security is maintained** through path validation
4. **Caching is optimized** with proper headers

#### How it works:
```
User requests: /api/uploads/articles/image.jpg
       ↓
API route: api/routes/uploads.php
       ↓
Reads from: /var/storage/medium-clone/uploads/articles/image.jpg
       ↓
Serves to browser with proper headers
```

### 4. Deployment Process

#### Safe Deployment Steps:
1. **Backup current uploads** (if using old structure):
   ```bash
   cp -r /var/www/medium-clone/uploads /var/storage/medium-clone/
   ```

2. **Deploy new application code**:
   ```bash
   # Your deployment process (git pull, rsync, etc.)
   git pull origin main
   ```

3. **Update environment variables**:
   ```bash
   # Update .env with persistent storage path
   UPLOAD_PATH=/var/storage/medium-clone/uploads/
   ```

4. **Verify file serving**:
   ```bash
   curl -I http://yoursite.com/api/uploads/profiles/test-image.jpg
   ```

### 5. Development vs Production

#### Development (.env):
```bash
# Relative path for easy development
UPLOAD_PATH=../uploads/
```

#### Production (.env):
```bash
# Absolute path outside web root
UPLOAD_PATH=/var/storage/medium-clone/uploads/
```

### 6. Backup Strategy

#### Automated Backup Script:
```bash
#!/bin/bash
# backup-uploads.sh

STORAGE_PATH="/var/storage/medium-clone/uploads"
BACKUP_PATH="/var/backups/medium-clone/uploads"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p "$BACKUP_PATH"

# Create compressed backup
tar -czf "$BACKUP_PATH/uploads_$DATE.tar.gz" -C "$STORAGE_PATH" .

# Keep only last 30 days of backups
find "$BACKUP_PATH" -name "uploads_*.tar.gz" -mtime +30 -delete

echo "Backup completed: uploads_$DATE.tar.gz"
```

#### Cron job for daily backups:
```bash
# Add to crontab
0 2 * * * /path/to/backup-uploads.sh
```

### 7. Monitoring

#### Check Storage Usage:
```bash
# Monitor disk usage
df -h /var/storage/medium-clone/

# Count uploaded files
find /var/storage/medium-clone/uploads -type f | wc -l

# Check recent uploads
find /var/storage/medium-clone/uploads -type f -mtime -1
```

### 8. Security Considerations

1. **Directory Permissions**: 755 for directories, 644 for files
2. **Web Server Access**: Only PHP should access storage directory
3. **Path Validation**: Our proxy script prevents directory traversal
4. **File Type Validation**: Only allowed image types are accepted

### 9. Troubleshooting

#### Common Issues:

**Permission Denied:**
```bash
sudo chown -R www-data:www-data /var/storage/medium-clone/
sudo chmod -R 755 /var/storage/medium-clone/
```

**Directory Not Found:**
```bash
# Check if path exists
ls -la /var/storage/medium-clone/uploads/

# Check PHP can access it
php -r "var_dump(is_dir('/var/storage/medium-clone/uploads/'));"
```

**Files Not Serving:**
```bash
# Test the API endpoint
curl -v http://yoursite.com/api/uploads/articles/test-image.jpg

# Check PHP error logs
tail -f /var/log/php/error.log
```

## Summary

✅ **Current Setup**: Already implements proxy serving (good!)
✅ **Development**: Works with relative paths
⚠️ **Production**: Needs absolute path outside web root
✅ **Security**: Path validation prevents attacks
✅ **Performance**: Proper caching headers
✅ **Scalability**: Can easily move to cloud storage later

**Next Steps for Production:**
1. Create `/var/storage/medium-clone/uploads/` directory
2. Update `.env` with `UPLOAD_PATH=/var/storage/medium-clone/uploads/`
3. Set up automated backups
4. Test file upload and serving

This setup ensures your user-generated content is **never lost during deployments**! 🛡️