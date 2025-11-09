import { useState, useCallback } from 'react';

interface UseAuthModalReturn {
  isOpen: boolean;
  activeTab: 'login' | 'register';
  openLogin: () => void;
  openRegister: () => void;
  close: () => void;
  setActiveTab: (tab: 'login' | 'register') => void;
}

export const useAuthModal = (): UseAuthModalReturn => {
  const [isOpen, setIsOpen] = useState(false);
  const [activeTab, setActiveTab] = useState<'login' | 'register'>('login');

  const openLogin = useCallback(() => {
    setActiveTab('login');
    setIsOpen(true);
  }, []);

  const openRegister = useCallback(() => {
    setActiveTab('register');
    setIsOpen(true);
  }, []);

  const close = useCallback(() => {
    setIsOpen(false);
  }, []);

  return {
    isOpen,
    activeTab,
    openLogin,
    openRegister,
    close,
    setActiveTab,
  };
};