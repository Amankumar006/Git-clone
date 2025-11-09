/**
 * SEO and metadata utilities
 */

export interface SEOMetadata {
  title: string;
  description: string;
  keywords: string[];
  ogTitle: string;
  ogDescription: string;
  ogImage?: string;
  ogUrl: string;
  twitterTitle: string;
  twitterDescription: string;
  twitterImage?: string;
  canonicalUrl: string;
  structuredData: any;
}

/**
 * Generate URL-friendly slug from title
 */
export const generateSlug = (title: string): string => {
  return title
    .toLowerCase()
    .trim()
    .replace(/[^\w\s-]/g, '') // Remove special characters
    .replace(/[\s_-]+/g, '-') // Replace spaces and underscores with hyphens
    .replace(/^-+|-+$/g, ''); // Remove leading/trailing hyphens
};

/**
 * Ensure slug is unique by checking against existing articles
 */
export const ensureUniqueSlug = async (baseSlug: string, articleId?: number): Promise<string> => {
  // This would typically make an API call to check for existing slugs
  // For now, we'll just return the base slug with a timestamp if needed
  let slug = baseSlug;
  let counter = 1;
  
  // In a real implementation, you'd check against the database
  // For now, we'll assume the slug is unique
  return slug;
};

/**
 * Extract plain text from HTML content
 */
export const extractPlainText = (html: string): string => {
  return html.replace(/<[^>]*>/g, '').trim();
};

/**
 * Generate meta description from content
 */
export const generateMetaDescription = (content: string, maxLength: number = 160): string => {
  const plainText = extractPlainText(content);
  if (plainText.length <= maxLength) {
    return plainText;
  }
  
  // Find the last complete sentence within the limit
  const truncated = plainText.substring(0, maxLength);
  const lastSentence = truncated.lastIndexOf('.');
  
  if (lastSentence > maxLength * 0.7) {
    return plainText.substring(0, lastSentence + 1);
  }
  
  // If no good sentence break, truncate at word boundary
  const lastSpace = truncated.lastIndexOf(' ');
  return plainText.substring(0, lastSpace) + '...';
};

/**
 * Generate complete SEO metadata for an article
 */
export const generateArticleSEO = (article: {
  title: string;
  content: string;
  tags: string[];
  featuredImage?: string;
  author: {
    name: string;
    username: string;
  };
  publishedAt: string;
  slug?: string;
  readingTime?: number;
}): SEOMetadata => {
  const slug = article.slug || generateSlug(article.title);
  const baseUrl = window.location.origin;
  const articleUrl = `${baseUrl}/article/${slug}`;
  
  const description = generateMetaDescription(article.content);
  const keywords = article.tags;
  
  // Enhanced structured data for search engines
  const structuredData = {
    "@context": "https://schema.org",
    "@type": "Article",
    "headline": article.title,
    "description": description,
    "image": article.featuredImage ? [article.featuredImage] : [],
    "author": {
      "@type": "Person",
      "name": article.author.name,
      "url": `${baseUrl}/user/${article.author.username}`
    },
    "publisher": {
      "@type": "Organization",
      "name": "Medium Clone",
      "logo": {
        "@type": "ImageObject",
        "url": `${baseUrl}/logo.png`
      }
    },
    "datePublished": article.publishedAt,
    "dateModified": article.publishedAt,
    "mainEntityOfPage": {
      "@type": "WebPage",
      "@id": articleUrl
    },
    "keywords": keywords.join(", "),
    "wordCount": extractPlainText(article.content).split(' ').length,
    "timeRequired": article.readingTime ? `PT${article.readingTime}M` : undefined,
    "articleSection": "Blog",
    "inLanguage": "en-US"
  };

  return {
    title: `${article.title} | Medium Clone`,
    description,
    keywords,
    ogTitle: article.title,
    ogDescription: description,
    ogImage: article.featuredImage,
    ogUrl: articleUrl,
    twitterTitle: article.title,
    twitterDescription: description,
    twitterImage: article.featuredImage,
    canonicalUrl: articleUrl,
    structuredData
  };
};

/**
 * Update document head with SEO metadata
 */
export const updateDocumentSEO = (metadata: SEOMetadata): void => {
  // Update title
  document.title = metadata.title;
  
  // Update or create meta tags
  const updateMetaTag = (name: string, content: string, property?: boolean) => {
    const selector = property ? `meta[property="${name}"]` : `meta[name="${name}"]`;
    let tag = document.querySelector(selector) as HTMLMetaElement;
    
    if (!tag) {
      tag = document.createElement('meta');
      if (property) {
        tag.setAttribute('property', name);
      } else {
        tag.setAttribute('name', name);
      }
      document.head.appendChild(tag);
    }
    
    tag.setAttribute('content', content);
  };
  
  // Basic meta tags
  updateMetaTag('description', metadata.description);
  updateMetaTag('keywords', metadata.keywords.join(', '));
  
  // Open Graph tags
  updateMetaTag('og:title', metadata.ogTitle, true);
  updateMetaTag('og:description', metadata.ogDescription, true);
  updateMetaTag('og:url', metadata.ogUrl, true);
  updateMetaTag('og:type', 'article', true);
  
  if (metadata.ogImage) {
    updateMetaTag('og:image', metadata.ogImage, true);
  }
  
  // Twitter Card tags
  updateMetaTag('twitter:card', 'summary_large_image');
  updateMetaTag('twitter:title', metadata.twitterTitle);
  updateMetaTag('twitter:description', metadata.twitterDescription);
  
  if (metadata.twitterImage) {
    updateMetaTag('twitter:image', metadata.twitterImage);
  }
  
  // Canonical URL
  let canonicalLink = document.querySelector('link[rel="canonical"]') as HTMLLinkElement;
  if (!canonicalLink) {
    canonicalLink = document.createElement('link');
    canonicalLink.setAttribute('rel', 'canonical');
    document.head.appendChild(canonicalLink);
  }
  canonicalLink.setAttribute('href', metadata.canonicalUrl);
  
  // Structured data
  let structuredDataScript = document.querySelector('script[type="application/ld+json"]');
  if (!structuredDataScript) {
    structuredDataScript = document.createElement('script');
    structuredDataScript.setAttribute('type', 'application/ld+json');
    document.head.appendChild(structuredDataScript);
  }
  structuredDataScript.textContent = JSON.stringify(metadata.structuredData);
};

/**
 * Generate social sharing URLs
 */
export const generateSharingUrls = (article: {
  title: string;
  url: string;
  description?: string;
}) => {
  const encodedUrl = encodeURIComponent(article.url);
  const encodedTitle = encodeURIComponent(article.title);
  const encodedDescription = encodeURIComponent(article.description || '');
  
  return {
    twitter: `https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedTitle}`,
    facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`,
    linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`,
    reddit: `https://reddit.com/submit?url=${encodedUrl}&title=${encodedTitle}`,
    email: `mailto:?subject=${encodedTitle}&body=${encodedDescription}%0A%0A${encodedUrl}`,
    copy: article.url
  };
};

/**
 * Fetch SEO metadata from backend
 */
export const fetchArticleSEO = async (articleId: string): Promise<any> => {
  try {
    const response = await fetch(`/api/seo/article-seo?id=${articleId}`);
    const data = await response.json();
    
    if (data.success) {
      return data.data;
    }
    
    throw new Error(data.error || 'Failed to fetch SEO data');
  } catch (error) {
    console.error('Error fetching SEO data:', error);
    return null;
  }
};

/**
 * Update article slug
 */
export const updateArticleSlug = async (articleId: string, newSlug: string): Promise<boolean> => {
  try {
    const response = await fetch('/api/seo/update-slug', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        article_id: articleId,
        slug: newSlug
      })
    });
    
    const data = await response.json();
    return data.success;
  } catch (error) {
    console.error('Error updating slug:', error);
    return false;
  }
};

/**
 * Generate breadcrumb structured data
 */
export const generateBreadcrumbStructuredData = (breadcrumbs: Array<{name: string, url: string}>) => {
  return {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": breadcrumbs.map((crumb, index) => ({
      "@type": "ListItem",
      "position": index + 1,
      "name": crumb.name,
      "item": crumb.url
    }))
  };
};

/**
 * Generate FAQ structured data
 */
export const generateFAQStructuredData = (faqs: Array<{question: string, answer: string}>) => {
  return {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": faqs.map(faq => ({
      "@type": "Question",
      "name": faq.question,
      "acceptedAnswer": {
        "@type": "Answer",
        "text": faq.answer
      }
    }))
  };
};

/**
 * Generate organization structured data
 */
export const generateOrganizationStructuredData = () => {
  const baseUrl = window.location.origin;
  
  return {
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "Medium Clone",
    "url": baseUrl,
    "logo": `${baseUrl}/logo.png`,
    "sameAs": [
      "https://twitter.com/mediumclone",
      "https://facebook.com/mediumclone",
      "https://linkedin.com/company/mediumclone"
    ],
    "contactPoint": {
      "@type": "ContactPoint",
      "contactType": "customer service",
      "email": "support@mediumclone.com"
    }
  };
};

/**
 * Generate website structured data
 */
export const generateWebsiteStructuredData = () => {
  const baseUrl = window.location.origin;
  
  return {
    "@context": "https://schema.org",
    "@type": "WebSite",
    "name": "Medium Clone",
    "url": baseUrl,
    "potentialAction": {
      "@type": "SearchAction",
      "target": {
        "@type": "EntryPoint",
        "urlTemplate": `${baseUrl}/search?q={search_term_string}`
      },
      "query-input": "required name=search_term_string"
    }
  };
};

/**
 * Add multiple structured data scripts to page
 */
export const addStructuredDataToPage = (structuredDataArray: any[]) => {
  // Remove existing structured data scripts
  const existingScripts = document.querySelectorAll('script[type="application/ld+json"]');
  existingScripts.forEach(script => script.remove());
  
  // Add new structured data scripts
  structuredDataArray.forEach(data => {
    const script = document.createElement('script');
    script.type = 'application/ld+json';
    script.textContent = JSON.stringify(data);
    document.head.appendChild(script);
  });
};

/**
 * Preload critical resources
 */
export const preloadCriticalResources = (resources: Array<{href: string, as: string, type?: string}>) => {
  resources.forEach(resource => {
    const link = document.createElement('link');
    link.rel = 'preload';
    link.href = resource.href;
    link.as = resource.as;
    if (resource.type) {
      link.type = resource.type;
    }
    document.head.appendChild(link);
  });
};

/**
 * Add DNS prefetch for external domains
 */
export const addDNSPrefetch = (domains: string[]) => {
  domains.forEach(domain => {
    const link = document.createElement('link');
    link.rel = 'dns-prefetch';
    link.href = domain;
    document.head.appendChild(link);
  });
};