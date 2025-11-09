# 🔔 Notification System Setup Guide

This guide will help you set up the complete notification system for your Medium clone application.

## 📋 Prerequisites

- PHP 7.4+ with PDO MySQL extension
- MySQL/MariaDB database
- Existing user authentication system
- Articles, follows, claps, and comments functionality

## 🚀 Setup Steps

### Step 1: Create the Database Table

Run the setup script to create the notifications table:

```bash
cd api
php setup_notifications.php
```

This will:
- Create the `notifications` table
- Add `notification_preferences` column to users table
- Optionally create sample notifications for testing

### Step 2: Verify the Setup

Test that everything is working:

```bash
php test_notifications.php
```

This will verify:
- Database table exists
- Notification creation works
- Notification retrieval works
- All methods function properly

### Step 3: Frontend Integration

The NotificationCenter component is already set up in your React frontend. Make sure it's imported and used in your main layout:

```tsx
import NotificationCenter from './components/NotificationCenter';

// In your header/navbar component:
<NotificationCenter />
```

## 🔧 API Endpoints

The following endpoints are available:

### Get Notifications
```
GET /api/notifications
GET /api/notifications?unread_only=true
GET /api/notifications?page=1&limit=20
```

### Get Unread Count
```
GET /api/notifications/unread-count
```

### Mark as Read
```
PUT /api/notifications/read/{id}
PUT /api/notifications/read-all
```

### Delete Notification
```
DELETE /api/notifications/{id}
```

### Get Statistics
```
GET /api/notifications/stats
```

## 🎯 Notification Types

The system supports these notification types:

1. **Follow** - When someone follows you
2. **Clap** - When someone claps your article
3. **Comment** - When someone comments on your article
4. **Publication Invite** - When invited to join a publication

## 🔄 How Notifications Are Created

Notifications are automatically created when:

### Follow Notifications
- Created in `FollowController::followUser()`
- Triggered when user A follows user B
- User B receives notification

### Clap Notifications
- Created in `ClapController::addClap()`
- Triggered when user A claps user B's article
- User B (article author) receives notification
- No notification for self-claps

### Comment Notifications
- Created in `CommentController::createComment()`
- Triggered when user A comments on user B's article
- User B (article author) receives notification
- No notification for self-comments

## ⚙️ User Preferences

Users can control their notification preferences through the `notification_preferences` JSON column in the users table:

```json
{
  "push_notifications": {
    "follows": true,
    "claps": true,
    "comments": true,
    "publication_invites": true
  },
  "email_notifications": {
    "follows": false,
    "claps": true,
    "comments": true,
    "publication_invites": true
  }
}
```

## 🧪 Testing

### Manual Testing

1. **Create a follow notification:**
   ```bash
   curl -X POST http://localhost/api/follows/follow \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"user_id": 2}'
   ```

2. **Check notifications:**
   ```bash
   curl -X GET http://localhost/api/notifications \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```

3. **Check unread count:**
   ```bash
   curl -X GET http://localhost/api/notifications/unread-count \
     -H "Authorization: Bearer YOUR_TOKEN"
   ```

### Frontend Testing

1. Open your application in the browser
2. Log in as a user
3. Look for the bell icon (🔔) in the header
4. Click it to open the notification dropdown
5. Test different actions (follow, clap, comment) to generate notifications

## 🐛 Troubleshooting

### Common Issues

1. **"notifications.map is not a function" error**
   - Fixed in the updated NotificationCenter component
   - Ensures notifications is always an array

2. **No notifications appearing**
   - Check if the database table exists
   - Verify API endpoints are working
   - Check browser console for errors
   - Ensure user is authenticated

3. **Database connection errors**
   - Verify database credentials in `.env`
   - Check if database exists
   - Ensure user has proper permissions

### Debug Steps

1. **Check database:**
   ```sql
   SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10;
   ```

2. **Check API response:**
   ```bash
   curl -X GET http://localhost/api/notifications/unread-count \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -v
   ```

3. **Check browser console:**
   - Open Developer Tools
   - Look for network errors
   - Check for JavaScript errors

## 🔒 Security Considerations

- All notification endpoints require authentication
- Users can only access their own notifications
- SQL injection protection through prepared statements
- XSS protection through proper data sanitization

## 📈 Performance Optimization

- Database indexes on `user_id`, `is_read`, and `created_at`
- Pagination support for large notification lists
- Automatic cleanup of old notifications (30+ days)
- Efficient queries with proper JOINs

## 🎨 Customization

### Adding New Notification Types

1. Add new type to the ENUM in the database:
   ```sql
   ALTER TABLE notifications MODIFY COLUMN type 
   ENUM('follow', 'clap', 'comment', 'publication_invite', 'your_new_type');
   ```

2. Add creation method in `Notification.php`:
   ```php
   public function createYourNewTypeNotification($userId, $content, $relatedId = null) {
       return $this->createNotification($userId, 'your_new_type', $content, $relatedId);
   }
   ```

3. Add icon in `NotificationCenter.tsx`:
   ```tsx
   case 'your_new_type':
     return <YourCustomIcon />;
   ```

### Styling the Notification Center

The NotificationCenter component uses Tailwind CSS classes. You can customize:
- Colors and themes
- Icon styles
- Animation effects
- Layout and spacing

## 📚 Additional Resources

- [PHP PDO Documentation](https://www.php.net/manual/en/book.pdo.php)
- [React Hooks Documentation](https://reactjs.org/docs/hooks-intro.html)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)

## 🤝 Support

If you encounter issues:
1. Check this guide first
2. Run the test script
3. Check the browser console
4. Verify database structure
5. Test API endpoints manually

---

🎉 **Congratulations!** Your notification system is now ready to keep your users engaged and informed about all the important activities in your Medium clone application.