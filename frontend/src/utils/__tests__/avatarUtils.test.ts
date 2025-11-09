import { getAvatarUrl, handleAvatarError, getAvatarProps } from '../avatarUtils';

describe('avatarUtils', () => {
  describe('getAvatarUrl', () => {
    it('should return the provided URL when valid', () => {
      const url = 'https://example.com/avatar.jpg';
      expect(getAvatarUrl(url)).toBe(url);
    });

    it('should return default avatar for null', () => {
      expect(getAvatarUrl(null)).toBe('/default-avatar.svg');
    });

    it('should return default avatar for undefined', () => {
      expect(getAvatarUrl(undefined)).toBe('/default-avatar.svg');
    });

    it('should return default avatar for empty string', () => {
      expect(getAvatarUrl('')).toBe('/default-avatar.svg');
    });
  });

  describe('handleAvatarError', () => {
    it('should set src to default avatar when current src is different', () => {
      const mockTarget = {
        src: 'https://example.com/broken-avatar.jpg'
      } as HTMLImageElement;

      const mockEvent = {
        target: mockTarget
      } as React.SyntheticEvent<HTMLImageElement, Event>;

      // Mock window.location.origin
      Object.defineProperty(window, 'location', {
        value: {
          origin: 'http://localhost:3000'
        },
        writable: true
      });

      handleAvatarError(mockEvent);

      expect(mockTarget.src).toBe('/default-avatar.svg');
    });

    it('should not change src when already default avatar', () => {
      const mockTarget = {
        src: 'http://localhost:3000/default-avatar.svg'
      } as HTMLImageElement;

      const mockEvent = {
        target: mockTarget
      } as React.SyntheticEvent<HTMLImageElement, Event>;

      // Mock window.location.origin
      Object.defineProperty(window, 'location', {
        value: {
          origin: 'http://localhost:3000'
        },
        writable: true
      });

      handleAvatarError(mockEvent);

      // Should remain unchanged to prevent infinite loop
      expect(mockTarget.src).toBe('http://localhost:3000/default-avatar.svg');
    });
  });

  describe('getAvatarProps', () => {
    it('should return correct props with valid src', () => {
      const src = 'https://example.com/avatar.jpg';
      const alt = 'User Avatar';
      const className = 'w-8 h-8 rounded-full';

      const props = getAvatarProps(src, alt, className);

      expect(props.src).toBe(src);
      expect(props.alt).toBe(alt);
      expect(props.className).toBe(className);
      expect(props.onError).toBe(handleAvatarError);
    });

    it('should return default avatar for null src', () => {
      const props = getAvatarProps(null, 'User Avatar');

      expect(props.src).toBe('/default-avatar.svg');
      expect(props.alt).toBe('User Avatar');
      expect(props.onError).toBe(handleAvatarError);
    });
  });
});