import React, { useEffect } from 'react';
import { Routes, Route } from 'react-router-dom';
import Layout from './components/Layout';
import {
  measureWebVitals,
  registerServiceWorker,
  setupLazyLoading,
  preloadCriticalResources
} from './utils/performance';
import HomePage from './pages/HomePage';
import ArticlePage from './pages/ArticlePage';
import EditorPage from './pages/EditorPage';
import DraftsPage from './pages/DraftsPage';
import DashboardPage from './pages/DashboardPage';
import LoginPage from './pages/LoginPage';
import RegisterPage from './pages/RegisterPage';
import ForgotPasswordPage from './pages/ForgotPasswordPage';
import ResetPasswordPage from './pages/ResetPasswordPage';
import EmailVerificationPage from './pages/EmailVerificationPage';
import UserProfilePage from './pages/UserProfilePage';
import UserSettingsPage from './pages/UserSettingsPage';
import SearchPage from './pages/SearchPage';
import TagPage from './pages/TagPage';
import TagsPage from './pages/TagsPage';
import TrendingPage from './pages/TrendingPage';
import AnalyticsPage from './pages/AnalyticsPage';
import AdvancedAnalyticsPage from './pages/AdvancedAnalyticsPage';
import PublicationsPage from './pages/PublicationsPage';
import PublicationPage from './pages/PublicationPage';
import CreatePublicationPage from './pages/CreatePublicationPage';
import PublicationManagePage from './pages/PublicationManagePage';
import NotFoundPage from './pages/NotFoundPage';
import ProtectedRoute from './components/ProtectedRoute';
// Toast provider removed - using individual toast components instead

function App() {
  useEffect(() => {
    // Initialize performance monitoring
    measureWebVitals();

    // Register service worker for caching
    registerServiceWorker();

    // Setup lazy loading for images
    setupLazyLoading();

    // Preload critical resources
    // Preload critical resources when available
    // preloadCriticalResources([]);

    // Performance monitoring in development
    if (process.env.NODE_ENV === 'development') {
      import('./utils/performance').then(({ analyzeBundleSize, monitorMemoryUsage }) => {
        setTimeout(() => {
          analyzeBundleSize();
          console.log('Memory usage:', monitorMemoryUsage());
        }, 2000);
      });
    }
  }, []);

  return (
    <div className="App">
        <Routes>
          {/* Public routes */}
          <Route path="/" element={<Layout />}>
            <Route index element={<HomePage />} />
            <Route path="article/:id" element={<ArticlePage />} />
            <Route path="user/:username" element={<UserProfilePage />} />
            <Route path="search" element={<SearchPage />} />
            <Route path="tag/:slug" element={<TagPage />} />
            <Route path="tags" element={<TagsPage />} />
            <Route path="trending" element={<TrendingPage />} />
            <Route path="publications" element={<PublicationsPage />} />
            <Route path="publication/:id" element={<PublicationPage />} />
            <Route path="login" element={<LoginPage />} />
            <Route path="register" element={<RegisterPage />} />
            <Route path="forgot-password" element={<ForgotPasswordPage />} />
            <Route path="reset-password" element={<ResetPasswordPage />} />
            <Route path="verify-email" element={<EmailVerificationPage />} />


            <Route path="drafts" element={
              <ProtectedRoute>
                <DraftsPage />
              </ProtectedRoute>
            } />
            <Route path="dashboard" element={
              <ProtectedRoute>
                <DashboardPage />
              </ProtectedRoute>
            } />
            <Route path="dashboard/analytics" element={
              <ProtectedRoute>
                <AnalyticsPage />
              </ProtectedRoute>
            } />
            <Route path="dashboard/advanced-analytics" element={
              <ProtectedRoute>
                <AdvancedAnalyticsPage />
              </ProtectedRoute>
            } />
            <Route path="publications/create" element={
              <ProtectedRoute>
                <CreatePublicationPage />
              </ProtectedRoute>
            } />
            <Route path="publication/:id/manage" element={
              <ProtectedRoute>
                <PublicationManagePage />
              </ProtectedRoute>
            } />
            <Route path="settings" element={
              <ProtectedRoute>
                <UserSettingsPage />
              </ProtectedRoute>
            } />

            {/* 404 page */}
            <Route path="*" element={<NotFoundPage />} />
          </Route>

          {/* Editor routes - without Layout for clean writing experience */}
          <Route path="write" element={
            <ProtectedRoute>
              <EditorPage />
            </ProtectedRoute>
          } />
          <Route path="editor" element={
            <ProtectedRoute>
              <EditorPage />
            </ProtectedRoute>
          } />
          <Route path="editor/:id" element={
            <ProtectedRoute>
              <EditorPage />
            </ProtectedRoute>
          } />
        </Routes>
      </div>
  );
}

export default App;