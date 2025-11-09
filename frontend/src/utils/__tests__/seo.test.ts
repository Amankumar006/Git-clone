import {
  generateSlug,
  extractPlainText,
  generateMetaDescription,
  generateArticleSEO,
  updateDocumentSEO,
  generateBreadcrumbStructuredData,
  generateFAQStructuredData,
  generateOrganizationStructuredData,
  generateWebsiteStructuredData
} from '../seo';

// Mock window.location
Object.defineProperty(window, 'location', {
  value: {
    origin: 'https://example.com',
    href: 'https://example.com/article/test-article'
  },
  writable: true
});

describe('SEO Utilities', () => {
  beforeEach(() => {
    // Clear document head
    document.head.innerHTML = '';
    document.title = '';
  });

  describe('generateSlug', () => {
    it('should generate URL-friendly slug from title', () => {
      expect(generateSlug('Hello World')).toBe('hello-world');
      expect(generateSlug('This is a Test Article!')).toBe('this-is-a-test-article');
      expect(generateSlug('Special Characters: @#$%^&*()')).toBe('special-characters');
      expect(generateSlug('Multiple   Spaces')).toBe('multiple-spaces');
      expect(generateSlug('  Leading and Trailing Spaces  ')).toBe('leading-and-trailing-spaces');
    });

    it('should handle empty strings', () => {
      expect(generateSlug('')).toBe('');
      expect(generateSlug('   ')).toBe('');
    });

    it('should handle unicode characters', () => {
      expect(generateSlug('Café & Restaurant')).toBe('caf-restaurant');
      expect(generateSlug('Naïve Résumé')).toBe('nave-rsum');
    });
  });

  describe('extractPlainText', () => {
    it('should remove HTML tags and return plain text', () => {
      const html = '<p>This is <strong>bold</strong> text with <a href="#">links</a>.</p>';
      expect(extractPlainText(html)).toBe('This is bold text with links.');
    });

    it('should handle nested HTML tags', () => {
      const html = '<div><p>Nested <span><em>content</em></span> here</p></div>';
      expect(extractPlainText(html)).toBe('Nested content here');
    });

    it('should handle empty HTML', () => {
      expect(extractPlainText('')).toBe('');
      expect(extractPlainText('<div></div>')).toBe('');
    });
  });

  describe('generateMetaDescription', () => {
    it('should generate meta description within character limit', () => {
      const content = 'This is a test article with some content that should be truncated properly.';
      const description = generateMetaDescription(content, 50);
      expect(description.length).toBeLessThanOrEqual(53); // Allow for ellipsis
    });

    it('should preserve complete sentences when possible', () => {
      const content = 'First sentence. Second sentence that is longer. Third sentence.';
      const description = generateMetaDescription(content, 30);
      expect(description).toBe('First sentence.');
    });

    it('should add ellipsis when truncating at word boundary', () => {
      const content = 'This is a very long sentence without proper punctuation that needs truncation';
      const description = generateMetaDescription(content, 30);
      expect(description).toContain('...');
    });

    it('should handle HTML content', () => {
      const content = '<p>This is <strong>HTML</strong> content.</p>';
      const description = generateMetaDescription(content);
      expect(description).toBe('This is HTML content.');
    });
  });

  describe('generateArticleSEO', () => {
    const mockArticle = {
      title: 'Test Article',
      content: '<p>Article content goes here.</p>',
      tags: ['test', 'article'],
      featuredImage: 'https://example.com/image.jpg',
      author: {
        name: 'John Doe',
        username: 'johndoe'
      },
      publishedAt: '2023-01-01T00:00:00Z',
      slug: 'test-article',
      readingTime: 5
    };

    it('should generate complete SEO metadata', () => {
      const seo = generateArticleSEO(mockArticle);

      expect(seo.title).toBe('Test Article | Medium Clone');
      expect(seo.description).toBe('This is a test article');
      expect(seo.keywords).toEqual(['test', 'article']);
      expect(seo.ogTitle).toBe('Test Article');
      expect(seo.ogDescription).toBe('This is a test article');
      expect(seo.ogImage).toBe('https://example.com/image.jpg');
      expect(seo.canonicalUrl).toBe('https://example.com/article/test-article');
    });

    it('should generate structured data', () => {
      const seo = generateArticleSEO(mockArticle);

      expect(seo.structuredData['@context']).toBe('https://schema.org');
      expect(seo.structuredData['@type']).toBe('Article');
      expect(seo.structuredData.headline).toBe('Test Article');
      expect(seo.structuredData.author.name).toBe('John Doe');
      expect(seo.structuredData.timeRequired).toBe('PT5M');
    });

    it('should handle missing optional fields', () => {
      const minimalArticle = {
        title: 'Minimal Article',
        content: 'Content',
        tags: [],
        author: { name: 'Author', username: 'author' },
        publishedAt: '2023-01-01T00:00:00Z'
      };

      const seo = generateArticleSEO(minimalArticle);
      expect(seo.title).toBe('Minimal Article | Medium Clone');
      expect(seo.description).toBe('Content');
    });
  });

  describe('updateDocumentSEO', () => {
    it('should update document title', () => {
      const metadata = {
        title: 'Test Title',
        description: 'Test description',
        keywords: ['test'],
        ogTitle: 'Test Title',
        ogDescription: 'Test description',
        ogUrl: 'https://example.com',
        twitterTitle: 'Test Title',
        twitterDescription: 'Test description',
        canonicalUrl: 'https://example.com',
        structuredData: {}
      };

      updateDocumentSEO(metadata);
      expect(document.title).toBe('Test Title');
    });

    it('should create meta tags', () => {
      const metadata = {
        title: 'Test Title',
        description: 'Test description',
        keywords: ['test', 'seo'],
        ogTitle: 'Test Title',
        ogDescription: 'Test description',
        ogUrl: 'https://example.com',
        twitterTitle: 'Test Title',
        twitterDescription: 'Test description',
        canonicalUrl: 'https://example.com',
        structuredData: { '@type': 'Article' }
      };

      updateDocumentSEO(metadata);

      const descriptionTag = document.querySelector('meta[name="description"]') as HTMLMetaElement;
      expect(descriptionTag?.content).toBe('Test description');

      const keywordsTag = document.querySelector('meta[name="keywords"]') as HTMLMetaElement;
      expect(keywordsTag?.content).toBe('test, seo');

      const ogTitleTag = document.querySelector('meta[property="og:title"]') as HTMLMetaElement;
      expect(ogTitleTag?.content).toBe('Test Title');
    });

    it('should create canonical link', () => {
      const metadata = {
        title: 'Test Title',
        description: 'Test description',
        keywords: [],
        ogTitle: 'Test Title',
        ogDescription: 'Test description',
        ogUrl: 'https://example.com',
        twitterTitle: 'Test Title',
        twitterDescription: 'Test description',
        canonicalUrl: 'https://example.com/canonical',
        structuredData: {}
      };

      updateDocumentSEO(metadata);

      const canonicalLink = document.querySelector('link[rel="canonical"]') as HTMLLinkElement;
      expect(canonicalLink?.href).toBe('https://example.com/canonical');
    });

    it('should create structured data script', () => {
      const structuredData = {
        '@context': 'https://schema.org',
        '@type': 'Article',
        headline: 'Test Article'
      };

      const metadata = {
        title: 'Test Title',
        description: 'Test description',
        keywords: [],
        ogTitle: 'Test Title',
        ogDescription: 'Test description',
        ogUrl: 'https://example.com',
        twitterTitle: 'Test Title',
        twitterDescription: 'Test description',
        canonicalUrl: 'https://example.com',
        structuredData
      };

      updateDocumentSEO(metadata);

      const script = document.querySelector('script[type="application/ld+json"]');
      expect(script?.textContent).toBe(JSON.stringify(structuredData));
    });
  });

  describe('generateBreadcrumbStructuredData', () => {
    it('should generate valid breadcrumb structured data', () => {
      const breadcrumbs = [
        { name: 'Home', url: 'https://example.com' },
        { name: 'Articles', url: 'https://example.com/articles' },
        { name: 'Test Article', url: 'https://example.com/article/test' }
      ];

      const structuredData = generateBreadcrumbStructuredData(breadcrumbs);

      expect(structuredData['@context']).toBe('https://schema.org');
      expect(structuredData['@type']).toBe('BreadcrumbList');
      expect(structuredData.itemListElement).toHaveLength(3);
      expect(structuredData.itemListElement[0].position).toBe(1);
      expect(structuredData.itemListElement[0].name).toBe('Home');
    });
  });

  describe('generateFAQStructuredData', () => {
    it('should generate valid FAQ structured data', () => {
      const faqs = [
        { question: 'What is this?', answer: 'This is a test.' },
        { question: 'How does it work?', answer: 'It works well.' }
      ];

      const structuredData = generateFAQStructuredData(faqs);

      expect(structuredData['@context']).toBe('https://schema.org');
      expect(structuredData['@type']).toBe('FAQPage');
      expect(structuredData.mainEntity).toHaveLength(2);
      expect(structuredData.mainEntity[0]['@type']).toBe('Question');
      expect(structuredData.mainEntity[0].name).toBe('What is this?');
    });
  });

  describe('generateOrganizationStructuredData', () => {
    it('should generate valid organization structured data', () => {
      const structuredData = generateOrganizationStructuredData();

      expect(structuredData['@context']).toBe('https://schema.org');
      expect(structuredData['@type']).toBe('Organization');
      expect(structuredData.name).toBe('Medium Clone');
      expect(structuredData.url).toBe('https://example.com');
    });
  });

  describe('generateWebsiteStructuredData', () => {
    it('should generate valid website structured data', () => {
      const structuredData = generateWebsiteStructuredData();

      expect(structuredData['@context']).toBe('https://schema.org');
      expect(structuredData['@type']).toBe('WebSite');
      expect(structuredData.name).toBe('Medium Clone');
      expect(structuredData.potentialAction['@type']).toBe('SearchAction');
    });
  });
});