import React, { useState } from 'react';
import RichTextEditor from './RichTextEditor';

const EditorDemo: React.FC = () => {
  const [content, setContent] = useState('<h1>Welcome to your Medium-style editor</h1><p>Start writing your story here. This editor features:</p><ul><li>Medium-inspired typography and spacing</li><li>Drag & drop image uploads</li><li>Auto-save functionality</li><li>Slash commands for quick formatting</li><li>Focus mode for distraction-free writing</li></ul><p>Try selecting text to see the formatting toolbar, or type "/" to see available commands.</p>');

  const handleAutoSave = () => {
    console.log('Auto-saving content:', content);
    // Here you would typically save to your backend
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="max-w-6xl mx-auto">
        <RichTextEditor
          content={content}
          onChange={setContent}
          onAutoSave={handleAutoSave}
          placeholder="Tell your story..."
          autoSaveInterval={30000}
        />
      </div>
    </div>
  );
};

export default EditorDemo;