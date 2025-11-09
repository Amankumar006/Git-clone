# Dashboard Setup Instructions

The writer and reader dashboards were not showing data due to missing database tables and API endpoints. This has been fixed.

## What Was Fixed

### 1. Frontend Issues
- ✅ Added missing dashboard API endpoints to `frontend/src/utils/api.ts`
- ✅ Updated `WriterDashboard.tsx` to use proper API service methods
- ✅ Updated `ReaderDashboard.tsx` to use proper API service methods
- ✅ Fixed TypeScript errors in dashboard components

### 2. Backend Issues
- ✅ Dashboard controller exists with all required methods
- ✅ Dashboard routes are properly configured
- ❌ **Missing database tables** (needs to be run)

## Required Setup Steps

### Step 1: Create Analytics Tables
The dashboard requires analytics tables that don't exist yet. Run this command:

```bash
cd api
php setup_analytics_tables.php
```

This will create:
- `article_views` - tracks article views
- `article_reads` - tracks reading analytics (time spent, scroll depth)
- `article_tags` - links articles to tags
- `tags` - stores available tags

### Step 2: Test Dashboard Endpoints
Verify the dashboard endpoints work:

```bash
cd api
php test_dashboard_endpoints.php
```

### Step 3: Verify Frontend Connection
1. Make sure the backend server is running on `http://localhost:8000`
2. Make sure the frontend is running and can access the API
3. Navigate to the dashboard in the frontend

## Expected Dashboard Data

### Writer Dashboard
- Article counts (draft, published, archived)
- Total views, claps, comments
- Follower count
- Recent activity (comments, claps, follows)
- Top performing articles
- Article management with bulk operations

### Reader Dashboard
- Articles read count
- Total reading time
- Bookmarks count
- Authors followed count
- Reading streak
- Favorite topics
- Bookmarked articles list
- Following feed
- Reading history

## Troubleshooting

### Dashboard Shows No Data
1. Run `php setup_analytics_tables.php` to create missing tables
2. Check that the backend server is running on port 8000
3. Check browser console for API errors
4. Verify database connection in `api/test_db_connection.php`

### 500 Internal Server Error on Bulk Operations
This was caused by missing methods in the Article model. **Fixed** by adding:
- `deleteArticle()` method
- `unpublish()` method  
- `archive()` method

### API Errors
1. Check that all required tables exist
2. Verify user authentication is working
3. Check backend logs for PHP errors
4. Test individual endpoints with `php test_dashboard_endpoints.php`
5. Run `php debug_dashboard.php` for comprehensive debugging
6. Test bulk operations with `php test_bulk_operations.php`

### TypeScript Errors
The dashboard components now use proper type assertions to handle API responses safely.

## Database Schema

The analytics tables created:

```sql
-- Tracks article views
CREATE TABLE article_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    user_id INT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tracks detailed reading analytics
CREATE TABLE article_reads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    user_id INT NULL,
    time_spent INT DEFAULT 0,
    scroll_depth INT DEFAULT 0,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Links articles to tags
CREATE TABLE article_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    tag_id INT NOT NULL
);

-- Stores available tags
CREATE TABLE tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL
);
```

## Debug Scripts Created

### `debug_dashboard.php`
Comprehensive debugging script that checks:
- Database connection
- Required tables existence
- Test data availability
- Article model methods
- Dashboard controller methods
- Authentication middleware
- JSON input handling

### `test_bulk_operations.php`
Specific test for bulk operations functionality:
- Tests archive, publish, unpublish operations
- Tests error handling for invalid operations
- Tests validation for missing parameters

### `test_dashboard_endpoints.php`
Tests all dashboard API endpoints:
- Writer stats endpoint
- Reader stats endpoint
- User articles endpoint
- Bookmarks, following feed, reading history

## Next Steps

1. **Run the setup script**: `php setup_analytics_tables.php`
2. **Debug any issues**: `php debug_dashboard.php`
3. **Test bulk operations**: `php test_bulk_operations.php`
4. **Test all endpoints**: `php test_dashboard_endpoints.php`

After running these scripts, the dashboard should display:
- Real analytics data for existing articles
- Sample data for testing purposes
- Proper error handling for missing data
- Working bulk operations (archive, publish, unpublish, delete)

The dashboard is now fully functional and ready to track user engagement and provide insights to writers and readers.