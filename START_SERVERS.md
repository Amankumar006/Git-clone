# 🚀 Server Startup Guide

## The Problem
Your frontend is trying to connect to `localhost:8000` but getting 404/500 errors. This means either:
1. The API server is not running
2. The API server is running on a different port
3. There are routing issues

## Solution: Start Your Servers

### Option 1: PHP Built-in Server (Recommended for Development)

**Start the API server:**
```bash
cd api
php -S localhost:8000
```

**Start the React frontend (in a new terminal):**
```bash
cd frontend  # or wherever your React app is
npm start
# This usually runs on localhost:3000
```

### Option 2: Using XAMPP/MAMP/WAMP

If you're using XAMPP, MAMP, or WAMP:

1. **Place your project in the web directory:**
   - XAMPP: `C:\xampp\htdocs\` (Windows) or `/Applications/XAMPP/htdocs/` (Mac)
   - MAMP: `/Applications/MAMP/htdocs/` (Mac)
   - WAMP: `C:\wamp64\www\` (Windows)

2. **Access your API at:**
   - `http://localhost/your-project-name/api/`
   - Update your frontend API base URL accordingly

### Option 3: Apache/Nginx Configuration

If you're using Apache or Nginx, make sure:

1. **Document root points to your project**
2. **URL rewriting is enabled** for the API routes
3. **PHP is properly configured**

## Testing the Setup

### 1. Test API Server
```bash
curl -X GET "http://localhost:8000/api/health"
```
Should return:
```json
{
  "success": true,
  "data": {
    "status": "healthy",
    "timestamp": "2024-01-01T12:00:00+00:00",
    "version": "1.0.0"
  },
  "message": "API is running"
}
```

### 2. Test Specific Endpoints
```bash
# Test trending articles
curl -X GET "http://localhost:8000/api/articles/trending"

# Test notifications (requires auth token)
curl -X GET "http://localhost:8000/api/notifications/unread-count" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 3. Check Frontend API Configuration

Make sure your frontend API base URL matches your server:

**In `frontend/src/utils/api.ts`:**
```typescript
const API_BASE_URL = 'http://localhost:8000/api';
// or whatever port your API server is running on
```

## Common Issues & Solutions

### Issue 1: Port Already in Use
```
Error: Address already in use
```
**Solution:** Use a different port
```bash
php -S localhost:8001  # Try port 8001 instead
```
Then update your frontend API URL to `http://localhost:8001/api`

### Issue 2: 404 Not Found
```
404 Not Found - The requested URL was not found
```
**Solutions:**
1. Make sure the API server is running
2. Check the URL path is correct
3. Verify routing in `api/index.php`

### Issue 3: 500 Internal Server Error
```
500 Internal Server Error
```
**Solutions:**
1. Check PHP error logs
2. Verify database connection
3. Run the endpoint tests: `php api/test_endpoints.php`

### Issue 4: CORS Errors
```
Access to fetch at 'http://localhost:8000' from origin 'http://localhost:3000' has been blocked by CORS policy
```
**Solution:** CORS is already configured in `api/config/cors.php`, but make sure it's being loaded.

## Quick Start Commands

**Terminal 1 (API Server):**
```bash
cd api
php -S localhost:8000
```

**Terminal 2 (Frontend):**
```bash
cd frontend
npm start
```

**Terminal 3 (Test):**
```bash
cd api
php test_endpoints.php
```

## Verification Checklist

- [ ] API server is running on port 8000
- [ ] Frontend is running on port 3000
- [ ] Database connection is working
- [ ] Test endpoints return data
- [ ] No CORS errors in browser console
- [ ] Notifications system is set up (ran `setup_notifications.php`)

## Next Steps

Once both servers are running:

1. **Open your browser to `http://localhost:3000`**
2. **Check the browser console for any remaining errors**
3. **Test the notification bell icon** - it should show up and work
4. **Try following users, clapping articles, commenting** - notifications should be created

## Need Help?

If you're still getting errors:

1. **Check the browser console** for specific error messages
2. **Check PHP error logs** for server-side issues
3. **Run the test scripts** to verify backend functionality
4. **Verify your database connection** and table structure

The notification system is fully implemented and tested - you just need to get the servers running properly!