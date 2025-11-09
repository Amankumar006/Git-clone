# User Profile Management Implementation

## Overview

This document describes the implementation of the user profile management system for the Medium-style publishing platform. The implementation includes profile viewing, editing, file upload functionality, and follow/unfollow features.

## Features Implemented

### 1. User Profile Viewing
- **Public Profile Pages**: Users can view other users' profiles at `/user/:username`
- **Profile Information Display**: Shows username, bio, profile picture, social links, and statistics
- **Statistics**: Displays follower count, following count, and published articles count
- **Articles Listing**: Shows all published articles by the user
- **Follow Status**: Displays whether the current user is following the profile owner

### 2. Profile Editing (Settings Page)
- **Profile Information**: Editable bio and social media links
- **Profile Picture Upload**: File upload with validation (JPEG, PNG, GIF, WebP up to 5MB)
- **Profile Preview**: Real-time preview of how the profile will appear to others
- **Account Information**: Display of username, email, and verification status
- **Password Management**: Secure password change functionality

### 3. Follow/Unfollow System
- **Follow Users**: Authenticated users can follow other users
- **Unfollow Users**: Users can unfollow previously followed users
- **Follow Status Tracking**: Real-time display of follow relationships
- **Notifications**: System creates notifications when users are followed

### 4. File Upload System
- **Image Validation**: Validates file type, size, and format
- **Secure Upload**: Generates unique filenames and validates MIME types
- **Image Processing**: Optional image resizing and optimization
- **Error Handling**: Comprehensive error messages for upload failures

## Backend Implementation

### API Endpoints

#### User Profile Management
```
GET /api/users/profile                    # Get own profile (authenticated)
GET /api/users/profile?id=:id            # Get profile by user ID
GET /api/users/profile?username=:username # Get profile by username
PUT /api/users/profile                    # Update profile (authenticated)
PUT /api/users/password                   # Update password (authenticated)
POST /api/users/upload-avatar             # Upload profile picture (authenticated)
```

#### Follow System
```
POST /api/users/follow                    # Follow a user (authenticated)
DELETE /api/users/follow?user_id=:id     # Unfollow a user (authenticated)
GET /api/users/followers?id=:id          # Get user's followers
GET /api/users/following?id=:id          # Get users that user is following
```

#### User Articles
```
GET /api/users/articles?id=:id           # Get user's published articles
```

### Database Schema

The implementation uses the following database tables:

#### Users Table
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    bio TEXT,
    profile_image_url VARCHAR(500),
    social_links JSON,
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### Follows Table
```sql
CREATE TABLE follows (
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (follower_id, following_id),
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
    CHECK (follower_id != following_id)
);
```

### PHP Classes

#### UserController
- Handles all user-related HTTP requests
- Implements authentication middleware integration
- Manages file uploads and profile updates
- Handles follow/unfollow operations

#### User Model
- Extends BaseRepository for database operations
- Implements profile validation and updates
- Manages password hashing and verification
- Provides follower/following count methods

#### FileUpload Utility
- Validates uploaded files (type, size, format)
- Generates unique filenames for security
- Handles image processing and resizing
- Provides comprehensive error handling

## Frontend Implementation

### Components

#### UserProfilePage
- Displays user profile information and articles
- Handles follow/unfollow interactions
- Shows profile statistics and social links
- Responsive design for mobile and desktop

#### UserSettingsPage
- Tabbed interface for different settings sections
- Profile editing with real-time preview
- File upload with drag-and-drop support
- Password change functionality
- Form validation and error handling

### State Management
- Uses React Context for authentication state
- Local state for form data and loading states
- Error handling with user-friendly messages
- Optimistic updates for better UX

### API Integration
- TypeScript interfaces for type safety
- Axios interceptors for authentication
- Error handling and retry logic
- File upload with progress tracking

## Security Features

### Authentication & Authorization
- JWT token-based authentication
- Protected routes for sensitive operations
- User can only edit their own profile
- Follow operations require authentication

### File Upload Security
- MIME type validation
- File size limits (5MB default)
- Unique filename generation
- Path traversal prevention
- Image format verification

### Input Validation
- Server-side validation for all inputs
- Bio length limits (500 characters)
- URL validation for social links
- Username format validation
- Password strength requirements

### Data Protection
- Password hashing with bcrypt
- SQL injection prevention with prepared statements
- XSS protection through input sanitization
- CSRF protection for state-changing operations

## Error Handling

### Backend Error Handling
- Comprehensive exception handling
- Structured error responses
- Logging for debugging
- User-friendly error messages

### Frontend Error Handling
- Form validation with real-time feedback
- Network error handling
- Loading states and user feedback
- Graceful degradation for failed operations

## Testing

### Backend Tests
- Unit tests for validation functions
- API endpoint testing
- File upload validation tests
- Follow/unfollow functionality tests

### Frontend Tests
- Component rendering tests
- User interaction tests
- API integration tests
- Form validation tests

## Performance Optimizations

### Database Optimizations
- Proper indexing on frequently queried columns
- Efficient queries for follower/following counts
- Pagination for large result sets

### Frontend Optimizations
- Image lazy loading
- Component memoization
- Efficient re-rendering
- Code splitting for better load times

### File Upload Optimizations
- Client-side file validation
- Image compression before upload
- Progress tracking for large files
- Chunked upload for very large files

## Usage Examples

### Getting a User Profile
```javascript
// Get profile by username
const profile = await apiService.users.getProfile('johndoe', true);

// Get own profile
const myProfile = await apiService.users.getProfile();
```

### Updating Profile
```javascript
const profileData = {
  bio: 'Software developer and writer',
  social_links: {
    twitter: 'https://twitter.com/johndoe',
    linkedin: 'https://linkedin.com/in/johndoe'
  }
};

const result = await apiService.users.updateProfile(profileData);
```

### Following a User
```javascript
// Follow user
await apiService.users.follow(userId);

// Unfollow user
await apiService.users.unfollow(userId);
```

### Uploading Profile Picture
```javascript
const file = event.target.files[0];
const result = await apiService.users.uploadAvatar(file);
```

## Configuration

### Environment Variables
```
UPLOAD_PATH=../uploads/
MAX_UPLOAD_SIZE=5242880
ALLOWED_IMAGE_TYPES=image/jpeg,image/png,image/gif,image/webp
```

### File Upload Configuration
- Maximum file size: 5MB
- Allowed formats: JPEG, PNG, GIF, WebP
- Upload directory: `/uploads/profiles/`
- Filename format: `img_[unique_id].[extension]`

## Future Enhancements

### Planned Features
1. **Profile Verification**: Blue checkmark for verified accounts
2. **Profile Themes**: Customizable profile appearance
3. **Profile Analytics**: Detailed profile view statistics
4. **Bulk Operations**: Bulk follow/unfollow management
5. **Profile Export**: Export profile data and articles
6. **Advanced Privacy**: Privacy settings for profile visibility

### Technical Improvements
1. **Image CDN**: Integration with cloud storage and CDN
2. **Real-time Updates**: WebSocket integration for live updates
3. **Advanced Search**: Search users by bio, location, interests
4. **Profile Recommendations**: Suggest users to follow
5. **Profile Backup**: Automated profile data backup

## Troubleshooting

### Common Issues

#### File Upload Failures
- Check file size limits
- Verify MIME type support
- Ensure upload directory permissions
- Check available disk space

#### Profile Update Errors
- Validate input data format
- Check authentication status
- Verify database connectivity
- Review error logs

#### Follow/Unfollow Issues
- Ensure user exists
- Check authentication
- Verify not following self
- Review database constraints

### Debug Information
- Enable debug mode in configuration
- Check browser network tab for API errors
- Review server error logs
- Use database query logging

## Conclusion

The user profile management system provides a comprehensive solution for user identity and social features in the Medium-style publishing platform. It includes secure file uploads, profile editing, social following, and proper authentication/authorization. The implementation follows best practices for security, performance, and user experience.