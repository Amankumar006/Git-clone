/**
 * Utility functions for handling avatar images consistently across the application
 */

/**
 * Gets the appropriate avatar URL, falling back to default if needed
 */
export const getAvatarUrl = (avatarUrl?: string | null): string => {
  return avatarUrl || '/default-avatar.svg';
};

/**
 * Handles avatar image loading errors to prevent infinite loops
 */
export const handleAvatarError = (event: React.SyntheticEvent<HTMLImageElement, Event>): void => {
  const target = event.target as HTMLImageElement;
  const defaultAvatarUrl = window.location.origin + '/default-avatar.svg';
  
  // Only set to default if it's not already the default to prevent infinite loops
  if (target.src !== defaultAvatarUrl) {
    target.src = '/default-avatar.svg';
  }
};

/**
 * Props for avatar images with consistent error handling
 */
export interface AvatarImageProps {
  src?: string | null;
  alt: string;
  className?: string;
  onError?: (event: React.SyntheticEvent<HTMLImageElement, Event>) => void;
}

/**
 * Get standardized props for avatar images
 */
export const getAvatarProps = (
  src: string | null | undefined,
  alt: string,
  className?: string
): AvatarImageProps => ({
  src: getAvatarUrl(src),
  alt,
  className,
  onError: handleAvatarError
});