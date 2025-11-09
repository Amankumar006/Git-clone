import React from 'react';
import { useNavigate, useParams, useSearchParams } from 'react-router-dom';
import ArticleEditor from '../components/ArticleEditor';
import { Draft } from '../hooks/useDraftManager';

const EditorPage: React.FC = () => {
  const navigate = useNavigate();
  const { id } = useParams<{ id: string }>();
  const [searchParams] = useSearchParams();
  
  // Get article ID from either path params (/editor/123) or query params (/editor?id=123)
  const articleId = id ? parseInt(id, 10) : 
    searchParams.get('id') ? parseInt(searchParams.get('id')!, 10) : 
    undefined;
    
  console.log('EditorPage - Path param id:', id);
  console.log('EditorPage - Query param id:', searchParams.get('id'));
  console.log('EditorPage - Final articleId:', articleId);

  const handleSave = (article: Draft) => {
    // Show success message or update UI
    console.log('Article saved:', article);
  };

  const handlePublish = (article: Draft) => {
    // Redirect to the published article
    if (article.id) {
      navigate(`/article/${article.id}`);
    }
  };

  const handleCancel = () => {
    // Go back to previous page or dashboard
    navigate(-1);
  };

  return (
    <ArticleEditor
      articleId={articleId}
      onSave={handleSave}
      onPublish={handlePublish}
      onCancel={handleCancel}
    />
  );
};

export default EditorPage;