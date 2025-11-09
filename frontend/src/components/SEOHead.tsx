import React, { useEffect } from 'react';
import { 
  updateDocumentSEO, 
  generateOrganizationStructuredData, 
  generateWebsiteStructuredData,
  addStructuredDataToPage,
  preloadCriticalResources,
  addDNSPrefetch,
  SEOMetadata 
} from '../utils/seo';

interface SEOHeadProps {
  title?: string;
  description?: string;
  keywords?: string[];
  ogImage?: string;
  canonicalUrl?: string;
  structuredData?: any[];
  noIndex?: boolean;
  noFollow?: boolean;
  preloadResources?: Array<{href: string, as: string, type?: string}>;
  dnsPrefetch?: string[];
}

const SEOHead: React.FC<SEOHeadProps> = ({
  title = 'Medium Clone - Share Your Stories',
  description = 'A modern publishing platform where writers and readers come together to share and discover amazing stories.',
  keywords = ['blog', 'writing', 'publishing', 'stories', 'articles'],
  ogImage,
  canonicalUrl,
  structuredData = [],
  noIndex = false,
  noFollow = false,
  preloadResources = [],
  dnsPrefetch = []
}) => {
  useEffect(() => {
    const baseUrl = window.location.origin;
    const currentUrl = canonicalUrl || window.location.href;
    
    // Default Open Graph image
    const defaultOgImage = `${baseUrl}/og-default.png`;
    
    // Create SEO metadata
    const seoMetadata: SEOMetadata = {
      title,
      description,
      keywords,
      ogTitle: title,
      ogDescription: description,
      ogImage: ogImage || defaultOgImage,
      ogUrl: currentUrl,
      twitterTitle: title,
      twitterDescription: description,
      twitterImage: ogImage || defaultOgImage,
      canonicalUrl: currentUrl,
      structuredData: {}
    };
    
    // Update document SEO
    updateDocumentSEO(seoMetadata);
    
    // Add robots meta tag if needed
    if (noIndex || noFollow) {
      const robotsContent = [];
      if (noIndex) robotsContent.push('noindex');
      if (noFollow) robotsContent.push('nofollow');
      
      let robotsTag = document.querySelector('meta[name="robots"]') as HTMLMetaElement;
      if (!robotsTag) {
        robotsTag = document.createElement('meta');
        robotsTag.setAttribute('name', 'robots');
        document.head.appendChild(robotsTag);
      }
      robotsTag.setAttribute('content', robotsContent.join(', '));
    }
    
    // Add structured data
    const allStructuredData = [
      generateOrganizationStructuredData(),
      generateWebsiteStructuredData(),
      ...structuredData
    ];
    
    addStructuredDataToPage(allStructuredData);
    
    // Preload critical resources
    if (preloadResources.length > 0) {
      preloadCriticalResources(preloadResources);
    }
    
    // Add DNS prefetch
    if (dnsPrefetch.length > 0) {
      addDNSPrefetch(dnsPrefetch);
    }
    
    // Add viewport meta tag for mobile optimization
    let viewportTag = document.querySelector('meta[name="viewport"]') as HTMLMetaElement;
    if (!viewportTag) {
      viewportTag = document.createElement('meta');
      viewportTag.setAttribute('name', 'viewport');
      viewportTag.setAttribute('content', 'width=device-width, initial-scale=1.0');
      document.head.appendChild(viewportTag);
    }
    
    // Add charset meta tag
    let charsetTag = document.querySelector('meta[charset]') as HTMLMetaElement;
    if (!charsetTag) {
      charsetTag = document.createElement('meta');
      charsetTag.setAttribute('charset', 'UTF-8');
      document.head.insertBefore(charsetTag, document.head.firstChild);
    }
    
    // Add theme color for mobile browsers
    let themeColorTag = document.querySelector('meta[name="theme-color"]') as HTMLMetaElement;
    if (!themeColorTag) {
      themeColorTag = document.createElement('meta');
      themeColorTag.setAttribute('name', 'theme-color');
      themeColorTag.setAttribute('content', '#ffffff');
      document.head.appendChild(themeColorTag);
    }
    
    // Add apple touch icon
    let appleTouchIcon = document.querySelector('link[rel="apple-touch-icon"]') as HTMLLinkElement;
    if (!appleTouchIcon) {
      appleTouchIcon = document.createElement('link');
      appleTouchIcon.setAttribute('rel', 'apple-touch-icon');
      appleTouchIcon.setAttribute('href', `${baseUrl}/apple-touch-icon.png`);
      document.head.appendChild(appleTouchIcon);
    }
    
    // Add favicon
    let favicon = document.querySelector('link[rel="icon"]') as HTMLLinkElement;
    if (!favicon) {
      favicon = document.createElement('link');
      favicon.setAttribute('rel', 'icon');
      favicon.setAttribute('type', 'image/x-icon');
      favicon.setAttribute('href', `${baseUrl}/favicon.ico`);
      document.head.appendChild(favicon);
    }
    
  }, [title, description, keywords, ogImage, canonicalUrl, structuredData, noIndex, noFollow, preloadResources, dnsPrefetch]);

  return null; // This component doesn't render anything visible
};

export default SEOHead;