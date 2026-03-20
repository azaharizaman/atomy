'use client';

import { useEffect } from 'react';
import { useAuthStore } from '../store/use-auth-store';
import { api } from '../lib/api';

export default function AuthProvider({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, token, refreshToken, user, login, logout, setLoading, setTokens } =
    useAuthStore();

  useEffect(() => {
    const initAuth = async () => {
      if (isAuthenticated && !token) {
        if (!refreshToken) {
          setLoading(false);
          // Mock / dev login persists user + isAuthenticated but not access token; allow session after full page load.
          if (process.env.NEXT_PUBLIC_USE_MOCKS === 'true' && user) {
            return;
          }
          logout();
          return;
        }
        try {
          const { data } = await api.post('/auth/refresh', { refresh_token: refreshToken });
          const accessToken = data.access_token as string;
          const newRefreshToken = (data.refresh_token as string) ?? refreshToken;
          setTokens(accessToken, newRefreshToken);
          const me = await api.get('/me', {
            headers: { Authorization: `Bearer ${accessToken}` },
          });
          const payload = (me.data?.data ?? me.data) as Parameters<typeof login>[2];
          login(accessToken, newRefreshToken, payload);
        } catch {
          logout();
        }
      }
      setLoading(false);
    };

    initAuth();
  }, [isAuthenticated, token, refreshToken, user, login, logout, setLoading, setTokens]);

  return <>{children}</>;
}
