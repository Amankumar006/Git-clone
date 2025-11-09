/**
 * Dashboard and Analytics Frontend Test Suite
 * Tests UI responsiveness, data visualization, and user interactions
 */

import React from 'react';
import { render, screen, fireEvent, waitFor, act } from '@testing-library/react';
import '@testing-library/jest-dom';
import { BrowserRouter } from 'react-router-dom';
import WriterDashboard from '../WriterDashboard';
import WriterAnalytics from '../WriterAnalytics';
import AdvancedAnalytics from '../AdvancedAnalytics';
import NotificationCenter from '../NotificationCenter';
import ReaderDashboard from '../ReaderDashboard';
import { AuthContext } from '../../context/AuthContext';
import * as api from '../../utils/api';

// Mock the API module
jest.mock('../../utils/api');
const mockedApi = api as jest.Mocked<typeof api>;

// Mock user context
const mockUser = {
  id: 1,
  username: 'testuser',
  email: 'test@example.com',
  profile_image_url: null
};

const mockAuthContext = {
  user: mockUser,
  login: jest.fn(),
  logout: jest.fn(),
  loading: false
};

// Mock data for testing
const mockWriterStats = {
  article_counts: {
    draft: 5,
    published: 10,
    archived: 2
  },
  total_views: 1500,
  total_claps: 250,
  total_comments: 45,
  follower_count: 30,
  recent_activity: [
    {
      type: 'clap',
      data: {
        clapper_username: 'reader1',
        article_title: 'Test Article'
      },
      timestamp: '2024-01-15T10:00:00Z'
    }
  ],
  top_articles: [
    {
      id: 1,
      title: 'Top Article',
      view_count: 500,
      clap_count: 50,
      comment_count: 10,
      engagement_score: 85
    }
  ]
};

const mockAnalyticsData = {
  views_over_time: [
    { date: '2024-01-01', views: 100 },
    { date: '2024-01-02', views: 150 },
    { date: '2024-01-03', views: 120 }
  ],
  engagement_over_time: [
    { date: '2024-01-01', claps: 10, comments: 5, follows: 2 },
    { date: '2024-01-02', claps: 15, comments: 8, follows: 1 },
    { date: '2024-01-03', claps: 12, comments: 6, follows: 3 }
  ],
  article_performance: [
    {
      id: 1,
      title: 'Test Article 1',
      view_count: 200,
      clap_count: 25,
      comment_count: 8,
      engagement_score: 75,
      published_at: '2024-01-01T00:00:00Z'
    }
  ],
  audience_insights: {
    top_readers: [
      {
        id: 2,
        username: 'reader1',
        profile_image_url: null,
        comment_count: 5,
        clap_count: 15,
        engagement_score: 80
      }
    ],
    engagement_patterns: {
      by_day_of_week: [
        { day_name: 'Monday', day_number: 1, clap_events: 10, total_claps: 25 },
        { day_name: 'Tuesday', day_number: 2, clap_events: 8, total_claps: 20 }
      ],
      by_hour_of_day: [
        { hour: 9, clap_events: 5, total_claps: 12 },
        { hour: 14, clap_events: 8, total_claps: 18 }
      ]
    },
    tag_performance: [
      {
        tag_name: 'Technology',
        article_count: 5,
        avg_views: 150,
        avg_claps: 20,
        avg_comments: 5,
        total_views: 750
      }
    ]
  },
  timeframe_days: 30
};

const mockAdvancedAnalytics = {
  performance_metrics: [
    {
      id: 1,
      title: 'Advanced Test Article',
      view_count: 300,
      clap_count: 40,
      comment_count: 12,
      engagement_score: 85,
      read_completion_rate: 75.5,
      avg_time_spent: 180000,
      avg_scroll_depth: 85.2
    }
  ],
  reader_demographics: {
    geographic_distribution: [
      { ip_prefix: '192.168', view_count: 100, unique_readers: 25 }
    ],
    reading_behavior: {
      avg_reading_time: 120000,
      avg_scroll_depth: 78.5,
      full_reads: 150,
      partial_reads: 80,
      quick_scans: 20,
      total_reads: 250
    },
    device_analytics: [
      { device_type: 'Desktop', view_count: 180, unique_users: 45 },
      { device_type: 'Mobile', view_count: 120, unique_users: 30 }
    ],
    retention_metrics: {
      total_readers: 100,
      returning_readers: 65,
      retention_rate: 65.0
    }
  },
  engagement_patterns: {
    engagement_velocity: [
      {
        id: 1,
        title: 'Viral Article',
        published_at: '2024-01-01T00:00:00Z',
        claps_1h: 5,
        claps_24h: 25,
        claps_7d: 50
      }
    ]
  },
  timeframe_days: 30,
  generated_at: '2024-01-15T12:00:00Z'
};

const mockNotifications = {
  notifications: [
    {
      id: 1,
      type: 'clap' as const,
      content: 'User liked your article',
      related_id: 1,
      is_read: false,
      created_at: '2024-01-15T10:00:00Z'
    },
    {
      id: 2,
      type: 'follow' as const,
      content: 'New follower',
      related_id: 2,
      is_read: true,
      created_at: '2024-01-14T15:30:00Z'
    }
  ],
  unread_count: 1
};

// Helper function to render components with context
const renderWithContext = (component: React.ReactElement) => {
  return render(
    <BrowserRouter>
      <AuthContext.Provider value={mockAuthContext}>
        {component}
      </AuthContext.Provider>
    </BrowserRouter>
  );
};

describe('Dashboard and Analytics Test Suite', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    // Mock successful API responses by default
    mockedApi.default.get.mockResolvedValue({ data: mockWriterStats });
  });

  describe('WriterDashboard Component', () => {
    test('renders dashboard with loading state', async () => {
      renderWithContext(<WriterDashboard />);
      
      // Should show loading spinner initially
      expect(screen.getByRole('status', { name: /loading/i })).toBeInTheDocument();
    });

    test('displays writer statistics correctly', async () => {
      mockedApi.default.get.mockResolvedValueOnce({ data: mockWriterStats });
      
      renderWithContext(<WriterDashboard />);
      
      await waitFor(() => {
        expect(screen.getByText('17')).toBeInTheDocument(); // Total articles
        expect(screen.getByText('1,500')).toBeInTheDocument(); // Total views
        expect(screen.getByText('250')).toBeInTheDocument(); // Total claps
        expect(screen.getByText('30')).toBeInTheDocument(); // Followers
      });
    });

    test('handles article filtering and sorting', async () => {
      const mockArticles = {
        articles: [
          {
            id: 1,
            title: 'Test Article',
            status: 'published' as const,
            view_count: 100,
            clap_count: 10,
            comment_count: 5,
            created_at: '2024-01-01T00:00:00Z',
            updated_at: '2024-01-02T00:00:00Z',
            tags: ['tech'],
            preview: 'Article preview...',
            engagement_score: 75
          }
        ]
      };

      mockedApi.default.get
        .mockResolvedValueOnce({ data: mockWriterStats })
        .mockResolvedValueOnce({ data: mockArticles });

      renderWithContext(<WriterDashboard />);

      await waitFor(() => {
        expect(screen.getByText('Published')).toBeInTheDocument();
      });

      // Test filtering
      const publishedTab = screen.getByText('Published');
      fireEvent.click(publishedTab);

      await waitFor(() => {
        expect(mockedApi.default.get).toHaveBeenCalledWith(
          expect.stringContaining('status=published')
        );
      });
    });

    test('handles bulk operations on articles', async () => {
      const mockArticles = {
        articles: [
          {
            id: 1,
            title: 'Test Article 1',
            status: 'draft' as const,
            view_count: 0,
            clap_count: 0,
            comment_count: 0,
            created_at: '2024-01-01T00:00:00Z',
            updated_at: '2024-01-01T00:00:00Z',
            tags: [],
            preview: 'Preview 1',
            engagement_score: 0
          }
        ]
      };

      mockedApi.default.get
        .mockResolvedValueOnce({ data: mockWriterStats })
        .mockResolvedValueOnce({ data: mockArticles });

      mockedApi.default.post.mockResolvedValueOnce({
        data: {
          success: true,
          summary: { success: 1, errors: 0 }
        }
      });

      renderWithContext(<WriterDashboard />);

      await waitFor(() => {
        expect(screen.getByText('Test Article 1')).toBeInTheDocument();
      });

      // Select article
      const checkbox = screen.getAllByRole('checkbox')[1]; // First is select all
      fireEvent.click(checkbox);

      // Click publish button
      const publishButton = screen.getByText(/Publish \(1\)/);
      fireEvent.click(publishButton);

      await waitFor(() => {
        expect(mockedApi.default.post).toHaveBeenCalledWith(
          '/dashboard/bulk-operations',
          {
            article_ids: [1],
            operation: 'publish'
          }
        );
      });
    });

    test('displays recent activity correctly', async () => {
      mockedApi.default.get.mockResolvedValueOnce({ data: mockWriterStats });

      renderWithContext(<WriterDashboard />);

      await waitFor(() => {
        expect(screen.getByText('Recent Activity')).toBeInTheDocument();
        expect(screen.getByText(/reader1.*clapped for.*Test Article/)).toBeInTheDocument();
      });
    });

    test('shows top performing articles', async () => {
      mockedApi.default.get.mockResolvedValueOnce({ data: mockWriterStats });

      renderWithContext(<WriterDashboard />);

      await waitFor(() => {
        expect(screen.getByText('Top Performing Articles')).toBeInTheDocument();
        expect(screen.getByText('Top Article')).toBeInTheDocument();
        expect(screen.getByText('500')).toBeInTheDocument(); // View count
      });
    });
  });

  describe('WriterAnalytics Component', () => {
    test('renders analytics with timeframe selector', async () => {
      mockedApi.default.get.mockResolvedValueOnce({ data: mockAnalyticsData });

      renderWithContext(<WriterAnalytics />);

      await waitFor(() => {
        expect(screen.getByText('Analytics')).toBeInTheDocument();
        expect(screen.getByDisplayValue('30')).toBeInTheDocument(); // Default timeframe
      });
    });

    test('displays views over time chart', async () => {
      mockedApi.default.get.mockResolvedValueOnce({ data: mockAnalyticsData });

      renderWithContext(<WriterAnalytics />);

      await waitFor(() => {
        expect(screen.getByText('Views Over Time')).toBeInTheDocument();
        expect(screen.getByText(/Total views in the last 30 days: 370/)).toBeInTheDocument();
      });
    });

    test('shows engagement trends', async () => {
      mockedApi.default.get.mockResolvedValueOnce({ data: mockAnalyticsData });

      renderWithContext(<WriterAnalytics />);

      await waitFor(() => {
        expect(screen.getByText('Engagement Trends')).toBeInTheDocument();
        expect(screen.getByText('37')).toBeInTheDocument(); // Total claps
        expect(screen.getByText('19')).toBeInTheDocument(); // Total comments
      });
    });

    test('displays article performance list', async () => {
      mockedApi.default.get.mockResolvedValueOnce({ data: mockAnalyticsData });

      renderWithContext(<WriterAnalytics />);

      await waitFor(() => {
        expect(screen.getByText('Article Performance')).toBeInTheDocument();
        expect(screen.getByText('Test Article 1')).toBeInTheDocument();
      });
    });

    test('shows top readers', async () => {
      mockedApi.default.get.mockResolvedValueOnce({ data: mockAnalyticsData });

      renderWithContext(<WriterAnalytics />);

      await waitFor(() => {
        expect(screen.getByText('Top Readers')).toBeInTheDocument();
        expect(screen.getByText('reader1')).toBeInTheDocument();
      });
    });

    test('handles timeframe changes', async () => {
      mockedApi.default.get.mockResolvedValue({ data: mockAnalyticsData });

      renderWithContext(<WriterAnalytics />);

      await waitFor(() => {
        expect(screen.getByDisplayValue('30')).toBeInTheDocument();
      });

      // Change timeframe
      const timeframeSelect = screen.getByDisplayValue('30');
      fireEvent.change(timeframeSelect, { target: { value: '7' } });

      await waitFor(() => {
        expect(mockedApi.default.get).toHaveBeenCalledWith(
          '/dashboard/writer-analytics?timeframe=7'
        );
      });
    });
  });

  describe('AdvancedAnalytics Component', () => {
    test('renders advanced analytics dashboard', async () => {
      mockedApi.default.get.mockResolvedValueOnce({ data: mockAdvancedAnalytics });

      renderWithContext(<AdvancedAnalytics />);

      await waitFor(() => {
        expect(screen.getByText('Advanced Analytics')).toBeInTheDocument();
        expect(screen.getByText('Deep insights into your content performance')).toBeInTheDocument();
      });
    });

    test('displays performance metrics overview', async () => {
      mockedApi.default.get.mockResolvedValueOnce({ data: mockAdvancedAnalytics });

      renderWithContext(<AdvancedAnalytics />);

      await waitFor(() => {
        expect(screen.getByText('Avg Engagement Score')).toBeInTheDocument();
        expect(screen.getByText('85')).toBeInTheDocument(); // Engagement score
        expect(screen.getByText('2m 0s')).toBeInTheDocument(); // Reading time
        expect(screen.getByText('65.0%')).toBeInTheDocument(); // Retention rate
      });
    });

    test('shows reading behavior analysis', async () => {
      mockedApi.default.get.mockResolvedValueOnce({ data: mockAdvancedAnalytics });

      renderWithContext(<AdvancedAnalytics />);

      await waitFor(() => {
        expect(screen.getByText('Reading Behavior')).toBeInTheDocument();
        expect(screen.getByText('Full Reads (90%+)')).toBeInTheDocument();
        expect(screen.getByText('150')).toBeInTheDocument(); // Full reads count
      });
    });

    test('displays device distribution', async () => {
      mockedApi.default.get.mockResolvedValueOnce({ data: mockAdvancedAnalytics });

      renderWithContext(<AdvancedAnalytics />);

      await waitFor(() => {
        expect(screen.getByText('Device Distribution')).toBeInTheDocument();
        expect(screen.getByText('Desktop')).toBeInTheDocument();
        expect(screen.getByText('Mobile')).toBeInTheDocument();
      });
    });

    test('shows article performance comparison table', async () => {
      mockedApi.default.get.mockResolvedValueOnce({ data: mockAdvancedAnalytics });

      renderWithContext(<AdvancedAnalytics />);

      await waitFor(() => {
        expect(screen.getByText('Article Performance Comparison')).toBeInTheDocument();
        expect(screen.getByText('Advanced Test Article')).toBeInTheDocument();
        expect(screen.getByText('75.5%')).toBeInTheDocument(); // Completion rate
      });
    });

    test('handles export functionality', async () => {
      mockedApi.default.get
        .mockResolvedValueOnce({ data: mockAdvancedAnalytics })
        .mockResolvedValueOnce({ data: new Blob(['test data']) });

      // Mock URL.createObjectURL
      global.URL.createObjectURL = jest.fn(() => 'mock-url');
      global.URL.revokeObjectURL = jest.fn();

      renderWithContext(<AdvancedAnalytics />);

      await waitFor(() => {
        expect(screen.getByText('Export')).toBeInTheDocument();
      });

      // Click export button
      const exportButton = screen.getByText('Export');
      fireEvent.click(exportButton);

      await waitFor(() => {
        expect(mockedApi.default.get).toHaveBeenCalledWith(
          expect.stringContaining('/dashboard/export-analytics'),
          expect.objectContaining({ responseType: 'blob' })
        );
      });
    });
  });

  describe('NotificationCenter Component', () => {
    test('renders notification bell with unread count', async () => {
      mockedApi.default.get.mockResolvedValueOnce({ 
        data: { unread_count: 5 },
        success: true 
      });

      renderWithContext(<NotificationCenter />);

      await waitFor(() => {
        expect(screen.getByText('5')).toBeInTheDocument(); // Unread badge
      });
    });

    test('opens notification dropdown on click', async () => {
      mockedApi.default.get
        .mockResolvedValueOnce({ data: { unread_count: 1 }, success: true })
        .mockResolvedValueOnce({ data: mockNotifications, success: true });

      renderWithContext(<NotificationCenter />);

      // Click notification bell
      const notificationBell = screen.getByRole('button');
      fireEvent.click(notificationBell);

      await waitFor(() => {
        expect(screen.getByText('Notifications')).toBeInTheDocument();
        expect(screen.getByText('User liked your article')).toBeInTheDocument();
      });
    });

    test('filters unread notifications', async () => {
      mockedApi.default.get
        .mockResolvedValueOnce({ data: { unread_count: 1 }, success: true })
        .mockResolvedValueOnce({ data: mockNotifications, success: true })
        .mockResolvedValueOnce({ 
          data: { 
            notifications: [mockNotifications.notifications[0]], 
            unread_count: 1 
          }, 
          success: true 
        });

      renderWithContext(<NotificationCenter />);

      // Open dropdown
      const notificationBell = screen.getByRole('button');
      fireEvent.click(notificationBell);

      await waitFor(() => {
        expect(screen.getByText('Unread only')).toBeInTheDocument();
      });

      // Toggle unread filter
      const unreadCheckbox = screen.getByLabelText('Unread only');
      fireEvent.click(unreadCheckbox);

      await waitFor(() => {
        expect(mockedApi.default.get).toHaveBeenCalledWith(
          expect.stringContaining('unread_only=true')
        );
      });
    });

    test('marks notification as read', async () => {
      mockedApi.default.get
        .mockResolvedValueOnce({ data: { unread_count: 1 }, success: true })
        .mockResolvedValueOnce({ data: mockNotifications, success: true });

      mockedApi.default.put.mockResolvedValueOnce({
        data: { unread_count: 0 },
        success: true
      });

      renderWithContext(<NotificationCenter />);

      // Open dropdown
      const notificationBell = screen.getByRole('button');
      fireEvent.click(notificationBell);

      await waitFor(() => {
        expect(screen.getByTitle('Mark as read')).toBeInTheDocument();
      });

      // Mark as read
      const markReadButton = screen.getByTitle('Mark as read');
      fireEvent.click(markReadButton);

      await waitFor(() => {
        expect(mockedApi.default.put).toHaveBeenCalledWith('/notifications/read/1');
      });
    });

    test('marks all notifications as read', async () => {
      mockedApi.default.get
        .mockResolvedValueOnce({ data: { unread_count: 2 }, success: true })
        .mockResolvedValueOnce({ data: mockNotifications, success: true });

      mockedApi.default.put.mockResolvedValueOnce({
        data: { unread_count: 0 },
        success: true
      });

      renderWithContext(<NotificationCenter />);

      // Open dropdown
      const notificationBell = screen.getByRole('button');
      fireEvent.click(notificationBell);

      await waitFor(() => {
        expect(screen.getByText('Mark all read')).toBeInTheDocument();
      });

      // Mark all as read
      const markAllReadButton = screen.getByText('Mark all read');
      fireEvent.click(markAllReadButton);

      await waitFor(() => {
        expect(mockedApi.default.put).toHaveBeenCalledWith('/notifications/read-all');
      });
    });

    test('deletes notification', async () => {
      mockedApi.default.get
        .mockResolvedValueOnce({ data: { unread_count: 1 }, success: true })
        .mockResolvedValueOnce({ data: mockNotifications, success: true });

      mockedApi.default.delete.mockResolvedValueOnce({
        data: { unread_count: 0 },
        success: true
      });

      renderWithContext(<NotificationCenter />);

      // Open dropdown
      const notificationBell = screen.getByRole('button');
      fireEvent.click(notificationBell);

      await waitFor(() => {
        expect(screen.getByTitle('Delete')).toBeInTheDocument();
      });

      // Delete notification
      const deleteButton = screen.getByTitle('Delete');
      fireEvent.click(deleteButton);

      await waitFor(() => {
        expect(mockedApi.default.delete).toHaveBeenCalledWith('/notifications/1');
      });
    });
  });

  describe('Performance and Responsiveness Tests', () => {
    test('components render within acceptable time', async () => {
      const startTime = performance.now();
      
      mockedApi.default.get.mockResolvedValue({ data: mockWriterStats });
      
      renderWithContext(<WriterDashboard />);
      
      await waitFor(() => {
        expect(screen.getByText('Writer Dashboard')).toBeInTheDocument();
      });
      
      const endTime = performance.now();
      const renderTime = endTime - startTime;
      
      // Should render within 1 second
      expect(renderTime).toBeLessThan(1000);
    });

    test('handles large datasets efficiently', async () => {
      // Create large mock dataset
      const largeDataset = {
        ...mockAnalyticsData,
        views_over_time: Array.from({ length: 365 }, (_, i) => ({
          date: `2024-01-${String(i + 1).padStart(2, '0')}`,
          views: Math.floor(Math.random() * 1000)
        })),
        article_performance: Array.from({ length: 100 }, (_, i) => ({
          id: i + 1,
          title: `Article ${i + 1}`,
          view_count: Math.floor(Math.random() * 1000),
          clap_count: Math.floor(Math.random() * 100),
          comment_count: Math.floor(Math.random() * 50),
          engagement_score: Math.floor(Math.random() * 100),
          published_at: '2024-01-01T00:00:00Z'
        }))
      };

      mockedApi.default.get.mockResolvedValue({ data: largeDataset });

      const startTime = performance.now();
      
      renderWithContext(<WriterAnalytics />);
      
      await waitFor(() => {
        expect(screen.getByText('Analytics')).toBeInTheDocument();
      });
      
      const endTime = performance.now();
      const renderTime = endTime - startTime;
      
      // Should handle large datasets within 2 seconds
      expect(renderTime).toBeLessThan(2000);
    });

    test('error handling works correctly', async () => {
      mockedApi.default.get.mockRejectedValue(new Error('API Error'));

      renderWithContext(<WriterDashboard />);

      // Should not crash and should handle error gracefully
      await waitFor(() => {
        expect(screen.getByText('Writer Dashboard')).toBeInTheDocument();
      });
    });

    test('loading states are displayed correctly', () => {
      // Mock slow API response
      mockedApi.default.get.mockImplementation(() => 
        new Promise(resolve => setTimeout(() => resolve({ data: mockWriterStats }), 1000))
      );

      renderWithContext(<WriterDashboard />);

      // Should show loading spinner
      expect(screen.getByRole('status', { name: /loading/i })).toBeInTheDocument();
    });
  });

  describe('Data Visualization Tests', () => {
    test('chart data is processed correctly', async () => {
      mockedApi.default.get.mockResolvedValue({ data: mockAnalyticsData });

      renderWithContext(<WriterAnalytics />);

      await waitFor(() => {
        // Check that chart elements are rendered
        expect(screen.getByText('Views Over Time')).toBeInTheDocument();
        
        // Check that data totals are calculated correctly
        expect(screen.getByText(/Total views in the last 30 days: 370/)).toBeInTheDocument();
      });
    });

    test('engagement patterns are visualized correctly', async () => {
      mockedApi.default.get.mockResolvedValue({ data: mockAnalyticsData });

      renderWithContext(<WriterAnalytics />);

      await waitFor(() => {
        expect(screen.getByText('Best Days')).toBeInTheDocument();
        expect(screen.getByText('Monday')).toBeInTheDocument();
        expect(screen.getByText('Tuesday')).toBeInTheDocument();
      });
    });

    test('responsive design works on different screen sizes', async () => {
      mockedApi.default.get.mockResolvedValue({ data: mockWriterStats });

      // Mock mobile viewport
      Object.defineProperty(window, 'innerWidth', {
        writable: true,
        configurable: true,
        value: 375,
      });

      renderWithContext(<WriterDashboard />);

      await waitFor(() => {
        expect(screen.getByText('Writer Dashboard')).toBeInTheDocument();
      });

      // Component should render without layout issues
      expect(screen.getByText('Total Articles')).toBeInTheDocument();
    });
  });
});