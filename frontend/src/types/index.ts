// Common types used throughout the application

export interface User {
  id: number;
  username: string;
  email: string;
  bio?: string;
  profile_image_url?: string;
  social_links?: Record<string, string>;
  email_verified: boolean;
  created_at: string;
  updated_at: string;
}

export interface Article {
  id: number;
  author_id: number;
  publication_id?: number;
  title: string;
  subtitle?: string;
  content: any; // Rich text content as JSON
  featured_image_url?: string;
  status: 'draft' | 'published' | 'archived';
  published_at?: string;
  reading_time: number; // Backend uses snake_case
  readingTime?: number; // Frontend alias for compatibility
  view_count: number;
  clap_count: number;
  comment_count: number;
  created_at: string;
  updated_at: string;
  slug?: string;
  // Author information from backend join
  username?: string;
  author_username?: string;
  author_avatar?: string;
  // Publication information
  publication_name?: string;
  publication_logo?: string;
  // Legacy support
  author?: User;
  tags?: (Tag | string)[] | string;
}

export interface Tag {
  id: number;
  name: string;
  slug: string;
  description?: string;
  created_at: string;
}

export interface Comment {
  id: number;
  article_id: number;
  user_id: number;
  parent_comment_id?: number;
  content: string;
  created_at: string;
  updated_at: string;
  user?: User;
  replies?: Comment[];
}

export interface Publication {
  id: number;
  name: string;
  description?: string;
  logo_url?: string;
  website_url?: string;
  social_links?: {
    twitter?: string;
    facebook?: string;
    linkedin?: string;
    instagram?: string;
  };
  theme_color?: string;
  custom_css?: string;
  owner_id: number;
  created_at: string;
  updated_at: string;
  owner?: User;
  owner_username?: string;
  owner_avatar?: string;
  members?: PublicationMember[];
  stats?: PublicationStats;
  user_role?: string;
  can_manage?: boolean;
  is_following?: boolean;
  followers_count?: number;
  submission_stats?: SubmissionStats;
  recent_activity?: RecentActivity[];
}

export interface PublicationStats {
  member_count: number;
  published_articles: number;
  draft_articles: number;
  total_views: number;
  total_claps: number;
  total_comments: number;
}

export interface SubmissionStats {
  pending_submissions: number;
  published_articles: number;
  archived_articles: number;
  unique_contributors: number;
}

export interface RecentActivity {
  activity_type: 'article_submitted' | 'article_published' | 'member_joined';
  article_id?: number;
  article_title: string;
  author_username: string;
  activity_date: string;
}

export interface PublicationMember {
  publication_id: number;
  user_id: number;
  role: 'admin' | 'editor' | 'writer';
  created_at: string;
  joined_at?: string;
  user?: User;
}

export interface Notification {
  id: number;
  user_id: number;
  type: 'follow' | 'clap' | 'comment' | 'publication_invite';
  content: string;
  related_id?: number;
  is_read: boolean;
  created_at: string;
}

export interface ApiError {
  code: string;
  message: string;
  details?: Record<string, string[]>;
}

export interface PaginationInfo {
  current_page: number;
  total_pages: number;
  total_items: number;
  per_page: number;
}