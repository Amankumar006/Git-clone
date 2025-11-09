# Medium Clone Frontend

A React-based frontend for the Medium-style publishing platform.

## Features

- **Modern React Setup**: Built with React 18, TypeScript, and Tailwind CSS
- **Responsive Design**: Mobile-first design that works on all devices
- **Authentication**: Complete user authentication flow with JWT tokens
- **Rich Text Editing**: Will support rich text editing for article creation
- **Real-time Features**: Prepared for real-time notifications and updates
- **SEO Optimized**: Meta tags and structured data for better search visibility

## Tech Stack

- **React 18** with TypeScript for type safety
- **React Router v6** for client-side routing
- **Tailwind CSS** for styling and responsive design
- **Axios** for HTTP requests with interceptors
- **Context API** for state management

## Getting Started

### Prerequisites

- Node.js 16+ and npm
- Backend API running (see `/api` directory)

### Installation

1. **Install dependencies**
   ```bash
   cd frontend
   npm install
   ```

2. **Environment Setup**
   ```bash
   cp .env.example .env
   # Update .env with your API URL
   ```

3. **Start Development Server**
   ```bash
   npm start
   ```

   The app will open at [http://localhost:3000](http://localhost:3000)

### Available Scripts

- `npm start` - Start development server
- `npm run build` - Build for production
- `npm test` - Run tests
- `npm run eject` - Eject from Create React App (not recommended)

## Project Structure

```
src/
├── components/          # Reusable UI components
│   ├── Layout.tsx      # Main layout wrapper
│   ├── Header.tsx      # Navigation header
│   ├── Footer.tsx      # Site footer
│   └── ProtectedRoute.tsx # Route protection
├── pages/              # Page components
│   ├── HomePage.tsx    # Landing/feed page
│   ├── LoginPage.tsx   # User login
│   ├── RegisterPage.tsx # User registration
│   ├── ArticlePage.tsx # Article display
│   ├── EditorPage.tsx  # Article editor
│   ├── ProfilePage.tsx # User profiles
│   └── DashboardPage.tsx # User dashboard
├── context/            # React Context providers
│   └── AuthContext.tsx # Authentication state
├── utils/              # Utility functions
│   ├── api.ts         # API service layer
│   └── helpers.ts     # Helper functions
├── types/              # TypeScript type definitions
│   └── index.ts       # Common types
├── App.tsx            # Main app component
├── index.tsx          # App entry point
└── index.css          # Global styles
```

## Key Features

### Authentication System
- JWT-based authentication with automatic token refresh
- Protected routes that redirect to login
- Persistent login state across browser sessions
- User profile management

### Responsive Design
- Mobile-first approach with Tailwind CSS
- Optimized for reading experience across devices
- Clean, distraction-free article display
- Accessible UI components

### API Integration
- Axios-based HTTP client with interceptors
- Automatic error handling and retry logic
- Type-safe API calls with TypeScript
- Loading states and error boundaries

## Development Guidelines

### Component Structure
- Use functional components with hooks
- Implement proper TypeScript typing
- Follow React best practices for performance
- Use Tailwind CSS classes for styling

### State Management
- Use Context API for global state (auth, theme)
- Local state with useState for component-specific data
- Custom hooks for reusable logic

### Error Handling
- Implement error boundaries for component errors
- Use try-catch blocks for async operations
- Display user-friendly error messages
- Log errors for debugging

## Deployment

### Build for Production
```bash
npm run build
```

This creates an optimized build in the `build/` directory ready for deployment.

### Environment Variables
Set these environment variables for production:
- `REACT_APP_API_URL` - Backend API URL
- `GENERATE_SOURCEMAP` - Set to false for production

## Next Steps

The frontend is set up with a complete foundation. The following features will be implemented in subsequent tasks:

1. **Rich Text Editor** (Task 3.1) - TipTap integration for article creation
2. **Article Display** (Task 4.1) - Full article reading experience
3. **Social Features** (Task 5.1) - Claps, comments, bookmarks
4. **Search & Discovery** (Task 6.1) - Content discovery features
5. **User Dashboard** (Task 7.1) - Analytics and content management
6. **Publications** (Task 8.1) - Collaborative writing features

## Contributing

1. Follow the existing code style and patterns
2. Add TypeScript types for new features
3. Test components thoroughly
4. Update documentation for new features