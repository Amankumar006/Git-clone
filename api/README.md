# Medium Clone API

## Overview

This is the backend API for the Medium Clone application, built with PHP and MySQL.

## Directory Structure

```
api/
├── config/          # Configuration files
│   ├── config.php   # Application configuration
│   ├── cors.php     # CORS handling
│   └── database.php # Database connection
├── controllers/     # Controller classes
│   └── BaseController.php
├── middleware/      # Middleware classes
│   ├── AuthMiddleware.php
│   └── ValidationMiddleware.php
├── models/          # Data models and repositories
│   └── BaseRepository.php
├── routes/          # API route definitions
│   ├── auth.php
│   ├── users.php
│   ├── articles.php
│   ├── search.php
│   └── publications.php
├── utils/           # Utility classes
│   ├── ErrorHandler.php
│   ├── JWTHelper.php
│   ├── Validator.php
│   └── FileUpload.php
├── .env.example     # Environment variables template
├── .htaccess        # Apache configuration
└── index.php        # API entry point
```

## Features

- **Database Connection**: Singleton pattern with PDO
- **Authentication**: JWT-based authentication system
- **Validation**: Input validation and sanitization
- **File Upload**: Image upload with validation and resizing
- **Error Handling**: Centralized error handling and logging
- **CORS**: Cross-origin resource sharing configuration
- **Rate Limiting**: Basic rate limiting implementation
- **Security**: CSRF protection and security headers

## Configuration

1. Copy `.env.example` to `.env`
2. Update database credentials and other settings
3. Ensure `uploads/` directory has write permissions
4. Configure Apache/Nginx to route requests to `index.php`

## API Endpoints

### Health Check
- `GET /` - API health check

### Authentication (Placeholder)
- `POST /auth/register` - User registration
- `POST /auth/login` - User login
- `POST /auth/refresh` - Refresh JWT token
- `POST /auth/logout` - User logout

### Users (Placeholder)
- `GET /users/profile` - Get user profile
- `PUT /users/profile` - Update user profile
- `POST /users/upload-avatar` - Upload profile picture

### Articles (Placeholder)
- `GET /articles` - Get articles list
- `POST /articles/create` - Create new article

### Search (Placeholder)
- `GET /search` - Search articles and users

### Publications (Placeholder)
- `GET /publications` - Get publications list
- `POST /publications/create` - Create new publication

## Response Format

### Success Response
```json
{
  "success": true,
  "data": {},
  "message": "Success message",
  "pagination": {
    "current_page": 1,
    "total_pages": 10,
    "total_items": 100,
    "per_page": 10
  }
}
```

### Error Response
```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Error message",
    "details": {}
  }
}
```

## Security Features

- JWT token authentication
- Input validation and sanitization
- SQL injection prevention with prepared statements
- File upload validation
- Rate limiting
- CORS configuration
- Security headers
- CSRF protection

## Development

The API is designed to be modular and extensible. Each component follows PHP best practices and includes proper error handling and validation.

To add new endpoints:
1. Create controller in `controllers/`
2. Add routes in appropriate `routes/` file
3. Implement validation and authentication as needed
4. Use `BaseController` and `BaseRepository` for common functionality