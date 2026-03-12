'use client';

import { useEffect } from 'react';
import { useAuthStore } from '../store/use-auth-store';
import { api } from '../lib/api';

export default function AuthProvider({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, token, login, logout, setLoading } = useAuthStore();

  useEffect(() => {
    const initAuth = async () => {
      if (isAuthenticated && !token) {
        try {
          const { data } = await api.post('/auth/refresh');
          // Fetch user profile as well to be sure
          const me = await api.get('/me');
          login(data.access_token, me.data);
        } catch {
          logout();
        }
      }
      setLoading(false);
    };

    initAuth();
  }, [isAuthenticated, token, login, logout, setLoading]);

  return <>{children}</>;
}
