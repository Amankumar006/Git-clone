import React from 'react';

interface ArticleContentProps {
  content: any;
}

const ArticleContent: React.FC<ArticleContentProps> = ({ content }) => {
  const renderContent = (content: any) => {
    if (typeof content === 'string') {
      return <div dangerouslySetInnerHTML={{ __html: content }} />;
    }
    
    // Handle rich text content (JSON format)
    if (Array.isArray(content)) {
      return content.map((block, index) => renderContentBlock(block, index));
    }
    
    return <div>{JSON.stringify(content)}</div>;
  };

  const renderContentBlock = (block: any, index: number) => {
    if (!block || !block.type) return null;
    
    const key = `block-${index}`;
    
    switch (block.type) {
      case 'paragraph':
        return (
          <p key={key} className="mb-6 text-gray-700 leading-relaxed text-base sm:text-lg">
            {block.content && renderInlineContent(block.content)}
          </p>
        );
      case 'heading':
        const level = block.attrs?.level || 1;
        const HeadingTag = `h${Math.min(level, 6)}` as keyof JSX.IntrinsicElements;
        const headingClasses = {
          1: 'text-2xl sm:text-3xl font-bold mb-6 sm:mb-8 mt-10 sm:mt-12 text-gray-900 scroll-mt-24 leading-snug',
          2: 'text-xl sm:text-2xl font-semibold mb-5 sm:mb-6 mt-8 sm:mt-10 text-gray-800 scroll-mt-24 leading-snug',
          3: 'text-lg sm:text-xl font-semibold mb-4 sm:mb-5 mt-6 sm:mt-8 text-gray-800 scroll-mt-24 leading-snug',
          4: 'text-base sm:text-lg font-semibold mb-3 sm:mb-4 mt-5 sm:mt-6 text-gray-800 scroll-mt-24 leading-snug',
          5: 'text-base sm:text-base font-semibold mb-3 mt-4 sm:mt-5 text-gray-800 scroll-mt-24 leading-snug',
          6: 'text-sm sm:text-base font-semibold mb-2 mt-3 sm:mt-4 text-gray-800 scroll-mt-24 leading-snug'
        };
        return (
          <HeadingTag 
            key={key} 
            id={`heading-${index}`}
            className={headingClasses[level as keyof typeof headingClasses] || headingClasses[1]}
          >
            {block.content && renderInlineContent(block.content)}
          </HeadingTag>
        );
      case 'blockquote':
        return (
          <blockquote key={key} className="border-l-3 border-gray-400 pl-5 sm:pl-6 my-6 sm:my-8 italic text-gray-600 text-lg sm:text-xl leading-relaxed bg-gray-50 py-3 rounded-r-md">
            {block.content && renderInlineContent(block.content)}
          </blockquote>
        );
      case 'bulletList':
        return (
          <ul key={key} className="list-disc list-inside mb-6 space-y-2 ml-4">
            {block.content?.map((item: any, itemIndex: number) => (
              <li key={`${key}-item-${itemIndex}`} className="text-gray-700 text-base sm:text-lg leading-relaxed">
                {item.content && renderInlineContent(item.content)}
              </li>
            ))}
          </ul>
        );
      case 'orderedList':
        return (
          <ol key={key} className="list-decimal list-inside mb-6 space-y-2 ml-4">
            {block.content?.map((item: any, itemIndex: number) => (
              <li key={`${key}-item-${itemIndex}`} className="text-gray-700 text-base sm:text-lg leading-relaxed">
                {item.content && renderInlineContent(item.content)}
              </li>
            ))}
          </ol>
        );
      case 'codeBlock':
        // Extract plain text from code block content
        const getCodeText = (content: any): string => {
          if (typeof content === 'string') return content;
          if (Array.isArray(content)) {
            return content.map(item => {
              if (typeof item === 'string') return item;
              if (item?.type === 'text') return item.text || '';
              return '';
            }).join('');
          }
          return '';
        };
        
        return (
          <pre key={key} className="bg-gray-50 rounded-lg p-4 sm:p-6 mb-6 overflow-x-auto shadow-sm border border-gray-200">
            <code className="text-sm sm:text-base font-mono text-gray-800 leading-relaxed whitespace-pre-wrap block">
              {getCodeText(block.content)}
            </code>
          </pre>
        );
      case 'image':
        return (
          <figure key={key} className="my-10 sm:my-12">
            <img 
              src={block.attrs?.src} 
              alt={block.attrs?.alt || ''} 
              className="w-full h-auto rounded-lg shadow-lg"
              loading="lazy"
            />
            {block.attrs?.caption && (
              <figcaption className="text-sm sm:text-base text-gray-600 text-center mt-4 italic">
                {block.attrs.caption}
              </figcaption>
            )}
          </figure>
        );
      default:
        return (
          <div key={key} className="mb-6 text-base sm:text-lg leading-relaxed">
            {block.content && renderInlineContent(block.content)}
          </div>
        );
    }
  };

  const renderInlineContent = (content: any[]): React.ReactNode => {
    if (!Array.isArray(content)) return content;
    
    return content.map((inline, index) => {
      if (typeof inline === 'string') return inline;
      if (!inline.type) return null;
      
      const key = `inline-${index}`;
      
      if (inline.type === 'text') {
        let text = inline.text || '';
        let element: React.ReactNode = text;
        
        if (inline.marks) {
          inline.marks.forEach((mark: any) => {
            switch (mark.type) {
              case 'bold':
                element = <strong key={key}>{element}</strong>;
                break;
              case 'italic':
                element = <em key={key}>{element}</em>;
                break;
              case 'code':
                element = <code key={key} className="bg-gray-100 px-2 py-1 rounded text-base font-mono text-red-600">{element}</code>;
                break;
              case 'link':
                element = (
                  <a 
                    key={key} 
                    href={mark.attrs?.href} 
                    className="text-blue-600 hover:text-blue-800 underline"
                    target="_blank" 
                    rel="noopener noreferrer"
                  >
                    {element}
                  </a>
                );
                break;
            }
          });
        }
        
        return element;
      }
      
      return null;
    });
  };

  return (
    <article className="prose prose-lg prose-gray max-w-none">
      <div className="text-base sm:text-lg leading-relaxed text-gray-700 font-serif selection:bg-blue-100 space-y-1">
        {renderContent(content)}
      </div>
    </article>
  );
};

export default ArticleContent;