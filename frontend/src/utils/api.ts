import axios, { AxiosInstance, AxiosResponse, InternalAxiosRequestConfig } from 'axios';

// API configuration - Force correct base URL for development
const API_BASE_URL = 'http://localhost:8000/api';

// Debug: Log the API base URL
console.log('API Base URL:', API_BASE_URL);

// Create axios instance
const api: AxiosInstance = axios.create({
  baseURL: API_BASE_URL,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Request interceptor to add auth token
api.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const tokensData = localStorage.getItem('authTokens');
    if (tokensData && config.headers) {
      try {
        const tokens = JSON.parse(tokensData);
        config.headers.Authorization = `Bearer ${tokens.access_token}`;
      } catch (error) {
        console.error('Error parsing tokens:', error);
        localStorage.removeItem('authTokens');
      }
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor for error handling
api.interceptors.response.use(
  (response: AxiosResponse) => {
    return response;
  },
  async (error) => {
    const originalRequest = error.config;
    
    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;
      
      // Try to refresh token
      const tokensData = localStorage.getItem('authTokens');
      if (tokensData) {
        try {
          const tokens = JSON.parse(tokensData);
          const refreshResponse = await api.post('/auth/refresh', {
            refresh_token: tokens.refresh_token
          });
          
          if (refreshResponse.data.success) {
            const newTokens = {
              ...tokens,
              access_token: refreshResponse.data.data.access_token,
              expires_in: refreshResponse.data.data.expires_in
            };
            
            localStorage.setItem('authTokens', JSON.stringify(newTokens));
            originalRequest.headers.Authorization = `Bearer ${newTokens.access_token}`;
            
            return api(originalRequest);
          }
        } catch (refreshError) {
          console.error('Token refresh failed:', refreshError);
        }
      }
      
      // Refresh failed or no tokens, logout user
      localStorage.removeItem('authTokens');
      localStorage.removeItem('user');
      // Don't redirect automatically, let the app handle it
      // window.location.href = '/login';
    }
    
    // Handle network errors
    if (!error.response) {
      console.error('Network error:', error.message);
      return Promise.reject({
        message: 'Network error. Please check your connection.',
        code: 'NETWORK_ERROR'
      });
    }
    
    // Return the error response for handling in components
    return Promise.reject(error.response.data);
  }
);

// API response types
export interface ApiResponse<T = any> {
  success: boolean;
  data?: T;
  message: string;
  pagination?: {
    current_page: number;
    total_pages: number;
    total_items: number;
    per_page: number;
  };
}

export interface ApiError {
  success: false;
  error: {
    code: string;
    message: string;
    details?: Record<string, string[]>;
  };
}

// API service methods
export const apiService = {
  // Generic methods
  get: <T>(url: string, config?: any): Promise<ApiResponse<T>> =>
    api.get(url, config).then(res => res.data),
    
  post: <T>(url: string, data?: any, config?: any): Promise<ApiResponse<T>> =>
    api.post(url, data, config).then(res => res.data),
    
  put: <T>(url: string, data?: any, config?: any): Promise<ApiResponse<T>> =>
    api.put(url, data, config).then(res => res.data),
    
  delete: <T>(url: string, config?: any): Promise<ApiResponse<T>> =>
    api.delete(url, config).then(res => res.data),

  // Authentication endpoints
  auth: {
    register: (userData: any) => apiService.post('/auth/register', userData),
    login: (credentials: any) => apiService.post('/auth/login', credentials),
    logout: () => apiService.post('/auth/logout'),
    refreshToken: (data: { refresh_token: string }) => apiService.post('/auth/refresh', data),
    forgotPassword: (data: { email: string }) => apiService.post('/auth/forgot-password', data),
    resetPassword: (data: { token: string; password: string }) => 
      apiService.post('/auth/reset-password', data),
    verifyEmail: (data: { token: string }) => apiService.post('/auth/verify-email', data),
    resendVerification: (data: { email: string }) => apiService.post('/auth/resend-verification', data),
    me: () => apiService.get('/auth/me'),
  },

  // User endpoints
  users: {
    getProfile: (identifier?: string, byUsername = false) => {
      if (!identifier) return apiService.get('/users/profile');
      const param = byUsername ? `username=${identifier}` : `id=${identifier}`;
      return apiService.get(`/users/profile?${param}`);
    },
    updateProfile: (data: any) => apiService.put('/users/profile', data),
    updatePassword: (data: { current_password: string; new_password: string }) => 
      apiService.put('/users/password', data),
    uploadAvatar: (file: File) => {
      const formData = new FormData();
      formData.append('avatar', file);
      return apiService.post('/users/upload-avatar', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      });
    },
    getUserArticles: (userId: string) => apiService.get(`/users/articles?id=${userId}`),
    getFollowers: (userId: string) => apiService.get(`/users/followers?id=${userId}`),
    getFollowing: (userId: string) => apiService.get(`/users/following?id=${userId}`),
    follow: (userId: number) => apiService.post('/users/follow', { user_id: userId }),
    unfollow: (userId: number) => apiService.delete(`/users/follow?user_id=${userId}`),
    getNotificationPreferences: () => apiService.get('/users/notification-preferences'),
    updateNotificationPreferences: (preferences: any) => 
      apiService.put('/users/notification-preferences', { preferences }),
  },

  // Article endpoints
  articles: {
    getAll: (params?: any) => apiService.get('/articles', { params }),
    getById: (id: string): Promise<AxiosResponse> => api.get(`/articles/${id}`),
    create: (data: any) => apiService.post('/articles', data),
    update: (id: string, data: any) => apiService.put(`/articles/${id}`, data),
    delete: (id: string) => apiService.delete(`/articles/${id}`),
    getDrafts: (): Promise<AxiosResponse> => api.get('/articles/drafts'),
    clap: (id: string, count: number = 1) => 
      apiService.post('/articles/clap', { article_id: id, count }),
    getComments: (id: string) => apiService.get(`/articles/comments?id=${id}`),
    addComment: (articleId: string, content: string, parentId?: string) =>
      apiService.post('/articles/comment', { article_id: articleId, content, parent_id: parentId }),
    getRelated: (id: string, limit?: number) => 
      apiService.get(`/articles/related?id=${id}${limit ? `&limit=${limit}` : ''}`),
    getMoreFromAuthor: (authorId: string, excludeId?: string, limit?: number) => {
      let url = `/articles/more-from-author?author_id=${authorId}`;
      if (excludeId) url += `&exclude_id=${excludeId}`;
      if (limit) url += `&limit=${limit}`;
      return apiService.get(url);
    },
    getRecommended: (limit?: number) => 
      apiService.get(`/articles/recommended${limit ? `?limit=${limit}` : ''}`),
  },

  // Search endpoints
  search: {
    articles: (query: string, filters?: any): Promise<AxiosResponse> => {
      const params = new URLSearchParams({ q: query, ...filters });
      return api.get(`/search?${params.toString()}`);
    },
    getSuggestions: (query: string): Promise<AxiosResponse> => 
      api.get(`/search/suggestions?q=${encodeURIComponent(query)}&limit=5`),
    trending: () => apiService.get('/articles/trending'),
    recommended: () => apiService.get('/articles/recommended'),
  },

  // Publication endpoints
  publications: {
    getAll: () => apiService.get('/publications'),
    getById: (id: string): Promise<AxiosResponse> => api.get(`/publications/show?id=${id}`),
    getMy: (): Promise<AxiosResponse> => api.get('/publications/my'),
    getFollowed: (): Promise<AxiosResponse> => api.get('/publications/followed'),
    getFollowedArticles: (): Promise<AxiosResponse> => api.get('/publications/followed-articles'),
    create: (data: any): Promise<AxiosResponse> => api.post('/publications/create', data),
    update: (id: string, data: any): Promise<AxiosResponse> => api.put(`/publications/update?id=${id}`, data),
    getFilteredArticles: (id: string, filters: any): Promise<AxiosResponse> => {
      const params = new URLSearchParams({ id, ...filters });
      return api.get(`/publications/filtered-articles?${params.toString()}`);
    },
    follow: (id: string) => apiService.post('/publications/follow', { publication_id: id }),
    unfollow: (publicationId: string): Promise<AxiosResponse> => 
      api.post('/publications/unfollow', { publication_id: publicationId }),
    getWorkflowStats: (id: number): Promise<AxiosResponse> => api.get(`/publications/${id}/workflow-stats`),
    invite: (publicationId: string, email: string, role: string): Promise<AxiosResponse> =>
      api.post('/publications/invite', { publication_id: publicationId, email, role }),
    updateRole: (publicationId: string, userId: number, role: string): Promise<AxiosResponse> =>
      api.post('/publications/update-role', { publication_id: publicationId, user_id: userId, role }),
    removeMember: (publicationId: string, userId: number): Promise<AxiosResponse> =>
      api.post('/publications/remove-member', { publication_id: publicationId, user_id: userId }),
    delete: (publicationId: string): Promise<AxiosResponse> =>
      api.delete('/publications/delete', { data: { id: publicationId } }),
    search: (query: string): Promise<AxiosResponse> =>
      api.get(`/publications/search?q=${encodeURIComponent(query)}`),
    getMembers: (id: string) => apiService.get(`/publications/members?id=${id}`),
  },

  // Clap endpoints
  claps: {
    add: (articleId: number, count: number = 1) => 
      apiService.post('/claps/add', { article_id: articleId, count }),
    remove: (articleId: number) => 
      apiService.delete('/claps/remove', { data: { article_id: articleId } }),
    getArticleClaps: (articleId: number, page?: number, limit?: number) => {
      let url = `/claps/article/${articleId}`;
      const params = new URLSearchParams();
      if (page) params.append('page', page.toString());
      if (limit) params.append('limit', limit.toString());
      if (params.toString()) url += `?${params.toString()}`;
      return apiService.get(url);
    },
    getStatus: (articleId: number) => apiService.get(`/claps/status/${articleId}`),
  },

  // Comment endpoints
  comments: {
    create: (articleId: number, content: string, parentCommentId?: number) =>
      apiService.post('/comments/create', { 
        article_id: articleId, 
        content, 
        parent_comment_id: parentCommentId 
      }),
    getArticleComments: (articleId: number, page?: number, limit?: number) => {
      let url = `/comments/article/${articleId}`;
      const params = new URLSearchParams();
      if (page) params.append('page', page.toString());
      if (limit) params.append('limit', limit.toString());
      if (params.toString()) url += `?${params.toString()}`;
      return apiService.get(url);
    },
    update: (commentId: number, content: string) =>
      apiService.put(`/comments/update/${commentId}`, { content }),
    delete: (commentId: number) =>
      apiService.delete(`/comments/delete/${commentId}`),
    getById: (commentId: number) =>
      apiService.get(`/comments/show/${commentId}`),
    getUserComments: (userId: number, page?: number, limit?: number) => {
      let url = `/comments/user/${userId}`;
      const params = new URLSearchParams();
      if (page) params.append('page', page.toString());
      if (limit) params.append('limit', limit.toString());
      if (params.toString()) url += `?${params.toString()}`;
      return apiService.get(url);
    },
  },

  // Bookmark endpoints
  bookmarks: {
    add: (articleId: number) =>
      apiService.post('/bookmarks/add', { article_id: articleId }),
    remove: (articleId: number) =>
      apiService.delete('/bookmarks/remove', { data: { article_id: articleId } }),
    getUserBookmarks: (userId?: number, page?: number, limit?: number) => {
      let url = userId ? `/bookmarks/user/${userId}` : '/bookmarks/user';
      const params = new URLSearchParams();
      if (page) params.append('page', page.toString());
      if (limit) params.append('limit', limit.toString());
      if (params.toString()) url += `?${params.toString()}`;
      return apiService.get(url);
    },
    getStatus: (articleId: number) =>
      apiService.get(`/bookmarks/status/${articleId}`),
    getPopular: (limit?: number) => {
      let url = '/bookmarks/popular';
      if (limit) url += `?limit=${limit}`;
      return apiService.get(url);
    },
  },

  // Follow endpoints
  follows: {
    follow: (userId: number) =>
      apiService.post('/follows/follow', { user_id: userId }),
    unfollow: (userId: number) =>
      apiService.delete('/follows/unfollow', { data: { user_id: userId } }),
    getFollowers: (userId: number, page?: number, limit?: number) => {
      let url = `/follows/followers/${userId}`;
      const params = new URLSearchParams();
      if (page) params.append('page', page.toString());
      if (limit) params.append('limit', limit.toString());
      if (params.toString()) url += `?${params.toString()}`;
      return apiService.get(url);
    },
    getFollowing: (userId: number, page?: number, limit?: number) => {
      let url = `/follows/following/${userId}`;
      const params = new URLSearchParams();
      if (page) params.append('page', page.toString());
      if (limit) params.append('limit', limit.toString());
      if (params.toString()) url += `?${params.toString()}`;
      return apiService.get(url);
    },
    getStatus: (userId: number) =>
      apiService.get(`/follows/status/${userId}`),
    getFeed: (page?: number, limit?: number) => {
      let url = '/follows/feed';
      const params = new URLSearchParams();
      if (page) params.append('page', page.toString());
      if (limit) params.append('limit', limit.toString());
      if (params.toString()) url += `?${params.toString()}`;
      return apiService.get(url);
    },
    getSuggestions: (limit?: number) => {
      let url = '/follows/suggestions';
      if (limit) url += `?limit=${limit}`;
      return apiService.get(url);
    },
  },

  // Notification endpoints
  notifications: {
    getAll: (unreadOnly?: boolean, page?: number, limit?: number) => {
      let url = '/notifications';
      const params = new URLSearchParams();
      if (unreadOnly) params.append('unread_only', 'true');
      if (page) params.append('page', page.toString());
      if (limit) params.append('limit', limit.toString());
      if (params.toString()) url += `?${params.toString()}`;
      return apiService.get(url);
    },
    markAsRead: (notificationId: number) =>
      apiService.put(`/notifications/read/${notificationId}`),
    markAllAsRead: () =>
      apiService.put('/notifications/read-all'),
    delete: (notificationId: number) =>
      apiService.delete(`/notifications/${notificationId}`),
    getUnreadCount: () =>
      apiService.get('/notifications/unread-count'),
    getStats: () =>
      apiService.get('/notifications/stats'),
  },

  // Dashboard endpoints
  dashboard: {
    // Writer dashboard
    getWriterStats: () => apiService.get('/dashboard/writer-stats'),
    getWriterAnalytics: (timeframe?: number): Promise<AxiosResponse> => {
      let url = '/dashboard/writer-analytics';
      if (timeframe) url += `?timeframe=${timeframe}`;
      return api.get(url);
    },
    getAdvancedAnalytics: (timeframe?: number, compareWith?: string, articleIds?: string): Promise<AxiosResponse> => {
      let url = '/dashboard/advanced-analytics';
      const params = new URLSearchParams();
      if (timeframe) params.append('timeframe', timeframe.toString());
      if (compareWith) params.append('compare_with', compareWith);
      if (articleIds) params.append('article_ids', articleIds);
      if (params.toString()) url += `?${params.toString()}`;
      return api.get(url);
    },
    getUserArticles: (params?: {
      page?: number;
      limit?: number;
      status?: string;
      sort_by?: string;
      sort_order?: string;
    }) => {
      let url = '/dashboard/user-articles';
      if (params) {
        const searchParams = new URLSearchParams();
        Object.entries(params).forEach(([key, value]) => {
          if (value !== undefined) searchParams.append(key, value.toString());
        });
        if (searchParams.toString()) url += `?${searchParams.toString()}`;
      }
      return apiService.get(url);
    },
    bulkOperations: (data: { article_ids: number[]; operation: string }) =>
      apiService.post('/dashboard/bulk-operations', data),
    exportAnalytics: (format?: string, timeframe?: number, dataType?: string): Promise<AxiosResponse> => {
      let url = '/dashboard/export-analytics';
      const params = new URLSearchParams();
      if (format) params.append('format', format);
      if (timeframe) params.append('timeframe', timeframe.toString());
      if (dataType) params.append('data_type', dataType);
      if (params.toString()) url += `?${params.toString()}`;
      return api.get(url);
    },

    // Reader dashboard
    getReaderStats: () => apiService.get('/dashboard/reader-stats'),
    getBookmarks: (page?: number, limit?: number) => {
      let url = '/dashboard/bookmarks';
      const params = new URLSearchParams();
      if (page) params.append('page', page.toString());
      if (limit) params.append('limit', limit.toString());
      if (params.toString()) url += `?${params.toString()}`;
      return apiService.get(url);
    },
    getFollowingFeed: (page?: number, limit?: number) => {
      let url = '/dashboard/following-feed';
      const params = new URLSearchParams();
      if (page) params.append('page', page.toString());
      if (limit) params.append('limit', limit.toString());
      if (params.toString()) url += `?${params.toString()}`;
      return apiService.get(url);
    },
    getReadingHistory: (page?: number, limit?: number) => {
      let url = '/dashboard/reading-history';
      const params = new URLSearchParams();
      if (page) params.append('page', page.toString());
      if (limit) params.append('limit', limit.toString());
      if (params.toString()) url += `?${params.toString()}`;
      return apiService.get(url);
    },
  },

  // Tags endpoints
  tags: {
    getBySlug: (slug: string, params?: { page?: number; limit?: number }): Promise<AxiosResponse> => {
      const queryParams = new URLSearchParams({ slug });
      if (params?.page) queryParams.append('page', params.page.toString());
      if (params?.limit) queryParams.append('limit', params.limit.toString());
      return api.get(`/tags/show?${queryParams.toString()}`);
    },
    getStats: (tagId: number): Promise<AxiosResponse> => api.get(`/tags/stats?tag_id=${tagId}`),
    checkFollowing: (tagId: number): Promise<AxiosResponse> => api.get(`/tags/check-following?tag_id=${tagId}`),
    follow: (tagId: number): Promise<AxiosResponse> => api.post('/tags/follow', { tag_id: tagId }),
    unfollow: (tagId: number): Promise<AxiosResponse> => api.post('/tags/unfollow', { tag_id: tagId }),
    getSuggestions: (query: string): Promise<AxiosResponse> => 
      api.get(`/tags/suggestions?q=${encodeURIComponent(query)}`),
    getCloud: (limit?: number): Promise<AxiosResponse> => 
      api.get(`/tags/cloud?limit=${limit || 100}`),
    getCategories: (): Promise<AxiosResponse> => 
      api.get('/tags/categories'),
    getAll: (limit?: number): Promise<AxiosResponse> => 
      api.get(`/tags?limit=${limit || 200}`),
    getTrending: (limit?: number): Promise<AxiosResponse> => 
      api.get(`/tags/trending?limit=${limit || 20}`),
  },

  // Workflow endpoints
  workflow: {
    getPendingSubmissions: (publicationId: number): Promise<AxiosResponse> => 
      api.get(`/workflow/pending-submissions?publication_id=${publicationId}`),
    getMySubmissions: (): Promise<AxiosResponse> => api.get('/workflow/my-submissions'),
    getGuidelines: (publicationId: number): Promise<AxiosResponse> => 
      api.get(`/workflow/guidelines?publication_id=${publicationId}`),
    getTemplates: (publicationId: number): Promise<AxiosResponse> => 
      api.get(`/workflow/templates?publication_id=${publicationId}`),
    createTemplate: (data: any): Promise<AxiosResponse> => 
      api.post('/workflow/create-template', data),
    updateTemplate: (templateId: number, data: any): Promise<AxiosResponse> => 
      api.put(`/workflow/templates/${templateId}`, data),
    deleteTemplate: (templateId: number): Promise<AxiosResponse> => 
      api.delete(`/workflow/templates/${templateId}`),
    setDefaultTemplate: (templateId: number): Promise<AxiosResponse> => 
      api.post(`/workflow/templates/${templateId}/set-default`),
    checkCompliance: (data: any): Promise<AxiosResponse> => 
      api.post('/workflow/check-compliance', data),
    createRevision: (data: any): Promise<AxiosResponse> => 
      api.post('/workflow/create-revision', data),
    approveSubmission: (data: { submission_id: number; review_notes?: string }): Promise<AxiosResponse> => 
      api.post('/workflow/approve-submission', data),
    rejectSubmission: (data: { submission_id: number; review_notes?: string }): Promise<AxiosResponse> => 
      api.post('/workflow/reject-submission', data),
    requestRevision: (data: { submission_id: number; revision_notes: string }): Promise<AxiosResponse> => 
      api.post('/workflow/request-revision', data),
    resubmit: (submissionId: number) => 
      apiService.post('/workflow/resubmit', { submission_id: submissionId }),
  },

  // Upload endpoints
  upload: {
    image: (formData: FormData) => 
      apiService.post('/upload/image', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      }),
  },
};

// Export both the axios instance and apiService for backward compatibility
export { api };
export default api;