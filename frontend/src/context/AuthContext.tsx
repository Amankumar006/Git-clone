import React, { createContext, useContext, useReducer, useEffect, ReactNode } from 'react';
import { apiService } from '../utils/api';

// Types
export interface User {
  id: number;
  username: string;
  email: string;
  bio?: string;
  profile_image_url?: string;
  social_links?: Record<string, string>;
  email_verified: boolean;
  created_at: string;
  role?: string;
}

interface Tokens {
  access_token: string;
  refresh_token: string;
  token_type: string;
  expires_in: number;
}

interface AuthState {
  user: User | null;
  tokens: Tokens | null;
  isLoading: boolean;
  isAuthenticated: boolean;
}

type AuthAction =
  | { type: 'SET_LOADING'; payload: boolean }
  | { type: 'LOGIN_SUCCESS'; payload: { user: User; tokens: Tokens } }
  | { type: 'TOKEN_REFRESHED'; payload: { access_token: string; expires_in: number } }
  | { type: 'LOGOUT' }
  | { type: 'UPDATE_USER'; payload: User };

interface AuthContextType extends AuthState {
  login: (email: string, password: string) => Promise<void>;
  register: (userData: any) => Promise<void>;
  logout: () => void;
  updateUser: (userData: Partial<User>) => void;
  forgotPassword: (email: string) => Promise<void>;
  resetPassword: (token: string, password: string) => Promise<void>;
  verifyEmail: (token: string) => Promise<void>;
  resendVerification: (email: string) => Promise<void>;
  refreshToken: () => Promise<void>;
}

// Initial state
const initialState: AuthState = {
  user: null,
  tokens: null,
  isLoading: true,
  isAuthenticated: false,
};

// Reducer
const authReducer = (state: AuthState, action: AuthAction): AuthState => {
  switch (action.type) {
    case 'SET_LOADING':
      return { ...state, isLoading: action.payload };
    
    case 'LOGIN_SUCCESS':
      return {
        ...state,
        user: action.payload.user,
        tokens: action.payload.tokens,
        isAuthenticated: true,
        isLoading: false,
      };
    
    case 'TOKEN_REFRESHED':
      return {
        ...state,
        tokens: state.tokens ? {
          ...state.tokens,
          access_token: action.payload.access_token,
          expires_in: action.payload.expires_in
        } : null,
      };
    
    case 'LOGOUT':
      return {
        ...state,
        user: null,
        tokens: null,
        isAuthenticated: false,
        isLoading: false,
      };
    
    case 'UPDATE_USER':
      return {
        ...state,
        user: action.payload,
      };
    
    default:
      return state;
  }
};

// Create context
const AuthContext = createContext<AuthContextType | undefined>(undefined);

// Export the context for direct use if needed
export { AuthContext };

// Provider component
interface AuthProviderProps {
  children: ReactNode;
}

export const AuthProvider: React.FC<AuthProviderProps> = ({ children }) => {
  const [state, dispatch] = useReducer(authReducer, initialState);

  // Initialize auth state from localStorage
  useEffect(() => {
    const initializeAuth = () => {
      const tokensData = localStorage.getItem('authTokens');
      const userData = localStorage.getItem('user');

      if (tokensData && userData) {
        try {
          const tokens = JSON.parse(tokensData);
          const user = JSON.parse(userData);
          dispatch({ type: 'LOGIN_SUCCESS', payload: { user, tokens } });
        } catch (error) {
          console.error('Error parsing auth data:', error);
          localStorage.removeItem('authTokens');
          localStorage.removeItem('user');
        }
      }
      
      dispatch({ type: 'SET_LOADING', payload: false });
    };

    initializeAuth();
  }, []);

  // Login function
  const login = async (email: string, password: string): Promise<void> => {
    try {
      dispatch({ type: 'SET_LOADING', payload: true });
      
      const response = await apiService.auth.login({ email, password });
      
      if (response.success && response.data) {
        const { user, tokens } = response.data as { user: User; tokens: Tokens };
        
        // Store in localStorage
        localStorage.setItem('authTokens', JSON.stringify(tokens));
        localStorage.setItem('user', JSON.stringify(user));
        
        dispatch({ type: 'LOGIN_SUCCESS', payload: { user, tokens } });
      } else {
        throw new Error('Login failed');
      }
    } catch (error: any) {
      dispatch({ type: 'SET_LOADING', payload: false });
      throw error;
    }
  };

  // Register function
  const register = async (userData: any): Promise<void> => {
    try {
      dispatch({ type: 'SET_LOADING', payload: true });
      
      const response = await apiService.auth.register(userData);
      
      if (response.success && response.data) {
        const { user, tokens } = response.data as { user: User; tokens: Tokens };
        
        // Store in localStorage
        localStorage.setItem('authTokens', JSON.stringify(tokens));
        localStorage.setItem('user', JSON.stringify(user));
        
        dispatch({ type: 'LOGIN_SUCCESS', payload: { user, tokens } });
      } else {
        throw new Error('Registration failed');
      }
    } catch (error: any) {
      dispatch({ type: 'SET_LOADING', payload: false });
      throw error;
    }
  };

  // Logout function
  const logout = (): void => {
    try {
      // Call logout API (fire and forget)
      apiService.auth.logout().catch(console.error);
    } catch (error) {
      console.error('Logout API error:', error);
    } finally {
      // Clear localStorage and state regardless of API call result
      localStorage.removeItem('authTokens');
      localStorage.removeItem('user');
      dispatch({ type: 'LOGOUT' });
    }
  };

  // Update user function
  const updateUser = (userData: Partial<User>): void => {
    if (state.user) {
      const updatedUser = { ...state.user, ...userData };
      localStorage.setItem('user', JSON.stringify(updatedUser));
      dispatch({ type: 'UPDATE_USER', payload: updatedUser });
    }
  };

  // Forgot password function
  const forgotPassword = async (email: string): Promise<void> => {
    const response = await apiService.auth.forgotPassword({ email });
    if (!response.success) {
      throw new Error(response.message || 'Failed to send password reset email');
    }
  };

  // Reset password function
  const resetPassword = async (token: string, password: string): Promise<void> => {
    const response = await apiService.auth.resetPassword({ token, password });
    if (!response.success) {
      throw new Error(response.message || 'Failed to reset password');
    }
  };

  // Verify email function
  const verifyEmail = async (token: string): Promise<void> => {
    const response = await apiService.auth.verifyEmail({ token });
    if (!response.success) {
      throw new Error(response.message || 'Failed to verify email');
    }
    
    // Update user's email verification status
    if (state.user) {
      const updatedUser = { ...state.user, email_verified: true };
      localStorage.setItem('user', JSON.stringify(updatedUser));
      dispatch({ type: 'UPDATE_USER', payload: updatedUser });
    }
  };

  // Resend verification function
  const resendVerification = async (email: string): Promise<void> => {
    const response = await apiService.auth.resendVerification({ email });
    if (!response.success) {
      throw new Error(response.message || 'Failed to resend verification email');
    }
  };

  // Refresh token function
  const refreshToken = async (): Promise<void> => {
    if (!state.tokens?.refresh_token) {
      throw new Error('No refresh token available');
    }

    const response = await apiService.auth.refreshToken({ 
      refresh_token: state.tokens.refresh_token 
    });
    
    if (response.success && response.data) {
      const { access_token, expires_in } = response.data as { 
        access_token: string; 
        expires_in: number; 
      };
      
      const updatedTokens = {
        ...state.tokens,
        access_token,
        expires_in
      };
      
      localStorage.setItem('authTokens', JSON.stringify(updatedTokens));
      dispatch({ type: 'TOKEN_REFRESHED', payload: { access_token, expires_in } });
    } else {
      // Refresh failed, logout user
      logout();
      throw new Error('Session expired. Please login again.');
    }
  };

  const value: AuthContextType = {
    ...state,
    login,
    register,
    logout,
    updateUser,
    forgotPassword,
    resetPassword,
    verifyEmail,
    resendVerification,
    refreshToken,
  };

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
};

// Custom hook to use auth context
export const useAuth = (): AuthContextType => {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};