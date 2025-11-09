import { useState, useCallback } from 'react';

interface ToastState {
  id: string;
  message: string;
  type: 'success' | 'error' | 'warning' | 'info';
  isVisible: boolean;
}

export const useToast = () => {
  const [toasts, setToasts] = useState<ToastState[]>([]);

  const showToast = useCallback((message: string, type: 'success' | 'error' | 'warning' | 'info' = 'info') => {
    const id = Date.now().toString();
    const newToast: ToastState = {
      id,
      message,
      type,
      isVisible: true
    };

    setToasts(prev => [...prev, newToast]);

    // Auto remove after 5 seconds
    setTimeout(() => {
      setToasts(prev => prev.filter(toast => toast.id !== id));
    }, 5000);

    return id;
  }, []);

  const hideToast = useCallback((id: string) => {
    setToasts(prev => prev.map(toast => 
      toast.id === id ? { ...toast, isVisible: false } : toast
    ));

    // Remove from array after animation
    setTimeout(() => {
      setToasts(prev => prev.filter(toast => toast.id !== id));
    }, 300);
  }, []);

  const showSuccess = useCallback((title: string, message?: string) => {
    const fullMessage = message ? `${title}: ${message}` : title;
    return showToast(fullMessage, 'success');
  }, [showToast]);
  
  const showError = useCallback((title: string, message?: string) => {
    const fullMessage = message ? `${title}: ${message}` : title;
    return showToast(fullMessage, 'error');
  }, [showToast]);
  
  const showWarning = useCallback((title: string, message?: string) => {
    const fullMessage = message ? `${title}: ${message}` : title;
    return showToast(fullMessage, 'warning');
  }, [showToast]);
  
  const showInfo = useCallback((title: string, message?: string) => {
    const fullMessage = message ? `${title}: ${message}` : title;
    return showToast(fullMessage, 'info');
  }, [showToast]);

  return {
    toasts,
    showToast,
    hideToast,
    showSuccess,
    showError,
    showWarning,
    showInfo
  };
};