# 📝 Medium Clone - Advanced Publishing Platform

> A feature-rich, modern publishing platform inspired by Medium, built with React, TypeScript, PHP, and MySQL.

[![React](https://img.shields.io/badge/React-18.2-blue.svg)](https://reactjs.org/)
[![TypeScript](https://img.shields.io/badge/TypeScript-4.9-blue.svg)](https://www.typescriptlang.org/)
[![PHP](https://img.shields.io/badge/PHP-8.0+-purple.svg)](https://www.php.net/)
[![TailwindCSS](https://img.shields.io/badge/TailwindCSS-3.2-38bdf8.svg)](https://tailwindcss.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

## 🌟 Features

### Core Publishing
- 📄 **Rich Text Editor** - TipTap-based WYSIWYG editor with markdown support
- 🖼️ **Image Management** - Drag-and-drop image upload with optimization
- 📑 **Draft System** - Auto-save drafts with version history
- 🏷️ **Tagging System** - Multi-tag support with autocomplete
- 📱 **Responsive Design** - Mobile-first UI with Tailwind CSS

### Social & Engagement
- 👏 **Clapping System** - Medium-style article appreciation (up to 50 claps)
- 💬 **Threaded Comments** - Nested comments up to 3 levels deep
- 🔖 **Bookmarks** - Save articles for later reading
- 👥 **Follow System** - Follow authors and publications
- 🔔 **Real-time Notifications** - Activity updates and engagement alerts

### Publications
- 📰 **Publication Management** - Create and manage multi-author publications
- 🎨 **Custom Branding** - Logo, colors, and custom CSS support
- 👥 **Role-based Access** - Owner, Admin, Editor, and Writer roles
- 📊 **Publication Analytics** - Track performance and engagement
- 🌐 **Social Integration** - Link Twitter, Facebook, LinkedIn, Instagram

### Advanced Features
- 🔍 **Full-text Search** - Search articles, users, and tags with filters
- 📈 **Analytics Dashboard** - Views, engagement metrics, and trends
- 🛡️ **Content Moderation** - Automated content filtering and reporting
- ✉️ **Email Verification** - Secure user registration system
- 🔐 **JWT Authentication** - Secure token-based auth with refresh tokens
- 🚨 **Security Monitoring** - Track suspicious activities and failed logins
- 🎯 **SEO Optimization** - Dynamic sitemap and meta tags
- 📊 **Advanced Analytics** - User behavior tracking and insights

## � Screenshots

### Home Feed
![Home Feed](./uploads/Images/Screenshot%202025-11-09%20at%209.22.24%20PM.png)
*Browse trending articles and personalized content feed*

### Article Reader
![Article Reader](./uploads/Images/Screenshot%202025-11-09%20at%209.22.55%20PM.png)
*Clean, distraction-free reading experience*

### Rich Text Editor
![Rich Text Editor](./uploads/Images/Screenshot%202025-11-09%20at%209.23.05%20PM.png)
*Powerful TipTap editor with formatting options and image upload*

### User Profile
![User Profile](./uploads/Images/Screenshot%202025-11-09%20at%209.23.12%20PM.png)
*Comprehensive user profiles with articles and stats*

### Publication Dashboard
![Publication Dashboard](./uploads/Images/Screenshot%202025-11-09%20at%209.23.27%20PM.png)
*Manage publications with analytics and team members*

### Notification Center
![Notification Center](./uploads/Images/Screenshot%202025-11-09%20at%209.23.33%20PM.png)
*Real-time notifications for engagement and activity*

## �🚀 Quick Start

### Prerequisites
- **PHP** >= 8.0
- **MySQL** >= 5.7
- **Node.js** >= 16.x
- **npm** or **yarn**
- **Composer** (optional, for dependency management)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/Amankumar006/Git-clone.git
   cd Git-clone
   ```

2. **Set up the database**
   ```bash
   # Create database
   mysql -u root -p -e "CREATE DATABASE medium_clone CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   
   # Import schema
   mysql -u root -p medium_clone < database/setup.sql
   
   # Import additional features (optional)
   mysql -u root -p medium_clone < database/analytics_tables.sql
   mysql -u root -p medium_clone < database/content_moderation.sql
   mysql -u root -p medium_clone < database/collaborative_workflow.sql
   ```

3. **Configure API**
   ```bash
   cd api
   cp .env.example .env
   # Edit .env with your database credentials
   ```

4. **Configure Frontend**
   ```bash
   cd frontend
   npm install
   cp .env.example .env
   # Edit .env with your API URL
   ```

5. **Start the servers**
   
   **Terminal 1 - API Server:**
   ```bash
   cd api
   php -S localhost:8000
   ```
   
   **Terminal 2 - Frontend:**
   ```bash
   cd frontend
   npm start
   ```

6. **Access the application**
   - Frontend: http://localhost:3000
   - API: http://localhost:8000

## 📁 Project Structure

```
├── api/                          # Backend API (PHP)
│   ├── config/                   # Configuration files
│   ├── controllers/              # Request handlers
│   ├── middleware/               # Auth, validation, CORS
│   ├── models/                   # Database models
│   ├── routes/                   # API route definitions
│   ├── utils/                    # Helper utilities
│   └── index.php                 # API entry point
│
├── frontend/                     # Frontend Application (React + TypeScript)
│   ├── public/                   # Static assets
│   └── src/
│       ├── components/           # React components
│       ├── context/              # React context providers
│       ├── hooks/                # Custom React hooks
│       ├── pages/                # Page components
│       ├── types/                # TypeScript definitions
│       └── utils/                # Utility functions
│
├── database/                     # SQL schemas and migrations
│   ├── setup.sql                 # Core database schema
│   ├── analytics_tables.sql     # Analytics tracking
│   ├── content_moderation.sql   # Moderation system
│   └── ...                       # Additional migrations
│
└── uploads/                      # User-generated content
    ├── articles/                 # Article images
    ├── profiles/                 # User avatars
    └── publications/             # Publication logos
```

## 🔧 Configuration

### Environment Variables

**API (.env)**
```env
# Database Configuration
DB_HOST=localhost
DB_NAME=medium_clone
DB_USER=root
DB_PASS=your_password

# JWT Configuration
JWT_SECRET=your_secret_key_here
JWT_EXPIRY=3600

# File Upload
UPLOAD_PATH=../uploads/
MAX_FILE_SIZE=5242880

# Email Configuration
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your_email@gmail.com
SMTP_PASS=your_password
```

**Frontend (.env)**
```env
REACT_APP_API_URL=http://localhost:8000/api
REACT_APP_UPLOADS_URL=http://localhost:8000/api/uploads
```

## 📚 API Documentation

### Authentication Endpoints
```
POST   /api/auth/register          # Register new user
POST   /api/auth/login             # Login user
POST   /api/auth/refresh           # Refresh JWT token
POST   /api/auth/logout            # Logout user
POST   /api/auth/verify-email      # Verify email address
```

### Article Endpoints
```
GET    /api/articles               # List articles
GET    /api/articles/trending      # Get trending articles
GET    /api/articles/:slug         # Get article by slug
POST   /api/articles/create        # Create article
PUT    /api/articles/update/:id    # Update article
DELETE /api/articles/delete/:id    # Delete article
```

### User Endpoints
```
GET    /api/users/profile/:id      # Get user profile
PUT    /api/users/profile          # Update profile
POST   /api/users/upload-avatar    # Upload avatar
GET    /api/users/following        # Get following list
POST   /api/users/follow/:id       # Follow user
```

### Comment Endpoints
```
GET    /api/comments/article/:id   # Get article comments
POST   /api/comments/create        # Create comment
PUT    /api/comments/update/:id    # Update comment
DELETE /api/comments/delete/:id    # Delete comment
```

[View full API documentation →](api/README.md)

## 🎨 Frontend Components

### Key Components
- **Editor** - Rich text editor with TipTap
- **ArticleCard** - Reusable article preview card
- **CommentSection** - Threaded comment system
- **NotificationCenter** - Real-time notification bell
- **PublicationDashboard** - Publication management interface
- **AnalyticsDashboard** - Comprehensive analytics view

### Hooks
- `useAuth` - Authentication state management
- `useNotifications` - Notification fetching and updates
- `useArticles` - Article CRUD operations
- `useInfiniteScroll` - Pagination helper

## 🧪 Testing

### Backend Tests
```bash
cd api
php test_endpoints.php              # Test all endpoints
php test_db_connection.php          # Test database connection
php test_notifications.php          # Test notification system
php test_moderation_api.php         # Test content moderation
```

### Frontend Tests
```bash
cd frontend
npm test                            # Run all tests
npm test -- --coverage              # Run with coverage
```

## 🚀 Deployment

### Production Setup

1. **Set up persistent storage**
   ```bash
   sudo mkdir -p /var/storage/medium-clone/uploads/{articles,profiles,publications}
   sudo chown -R www-data:www-data /var/storage/medium-clone/
   ```

2. **Update environment variables**
   ```env
   UPLOAD_PATH=/var/storage/medium-clone/uploads/
   ```

3. **Build frontend**
   ```bash
   cd frontend
   npm run build
   ```

4. **Configure web server** (Apache/Nginx)
   - Set document root to `/path/to/project`
   - Enable URL rewriting
   - Configure PHP handler

[View full deployment guide →](DEPLOYMENT_GUIDE.md)

## 🛡️ Security Features

- ✅ JWT token authentication with refresh tokens
- ✅ Password hashing with bcrypt
- ✅ SQL injection prevention with prepared statements
- ✅ XSS protection with input sanitization
- ✅ CSRF token validation
- ✅ Rate limiting on sensitive endpoints
- ✅ File upload validation and sanitization
- ✅ Security headers (CSP, HSTS, X-Frame-Options)
- ✅ Activity logging and monitoring

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Guidelines
- Follow existing code style and conventions
- Write tests for new features
- Update documentation as needed
- Keep commits atomic and descriptive

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Author

**Aman Kumar**
- GitHub: [@Amankumar006](https://github.com/Amankumar006)

## 🙏 Acknowledgments

- Inspired by [Medium](https://medium.com/)
- Built with [TipTap Editor](https://tiptap.dev/)
- UI components styled with [Tailwind CSS](https://tailwindcss.com/)

## 📞 Support

If you encounter any issues or have questions:
- 📫 Open an issue on GitHub
- 📖 Check the [documentation](api/README.md)
- 💬 Review existing issues and discussions

---

<p align="center">Made with ❤️ by Aman Kumar</p>

