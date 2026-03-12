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
          const accessToken = data.access_token as string;
          const me = await api.get('/me', {
            headers: { Authorization: `Bearer ${accessToken}` },
          });
          login(accessToken, me.data);
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
