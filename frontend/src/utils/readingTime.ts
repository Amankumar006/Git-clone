/**
 * Calculate estimated reading time based on word count
 * Average reading speed is approximately 200-250 words per minute
 * We'll use 225 words per minute as the baseline
 */
export const calculateReadingTime = (content: string): number => {
  // Remove HTML tags and get plain text
  const plainText = content.replace(/<[^>]*>/g, '');
  
  // Count words (split by whitespace and filter out empty strings)
  const words = plainText.trim().split(/\s+/).filter(word => word.length > 0);
  const wordCount = words.length;
  
  // Calculate reading time in minutes (minimum 1 minute)
  const readingTime = Math.max(1, Math.ceil(wordCount / 225));
  
  return readingTime;
};

/**
 * Format reading time for display
 */
export const formatReadingTime = (minutes: number): string => {
  if (minutes === 1) {
    return '1 min read';
  }
  return `${minutes} min read`;
};

/**
 * Get word count from content
 */
export const getWordCount = (content: string): number => {
  const plainText = content.replace(/<[^>]*>/g, '');
  const words = plainText.trim().split(/\s+/).filter(word => word.length > 0);
  return words.length;
};