import { create } from 'zustand';
import { persist } from 'zustand/middleware';

export interface User {
  id: string;
  name: string;
  email: string;
  role: string;
  tenantId: string;
  avatarUrl?: string;
}

interface AuthState {
  user: User | null;
  token: string | null;
  refreshToken: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (token: string, refreshToken: string | null, user: User) => void;
  logout: () => void;
  setLoading: (loading: boolean) => void;
  setTokens: (token: string, refreshToken: string | null) => void;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      user: null,
      token: null,
      refreshToken: null,
      isAuthenticated: false,
      isLoading: true,
      login: (token, refreshToken, user) =>
        set({ token, refreshToken, user, isAuthenticated: true, isLoading: false }),
      logout: () =>
        set({
          token: null,
          refreshToken: null,
          user: null,
          isAuthenticated: false,
          isLoading: false,
        }),
      setLoading: (loading) => set({ isLoading: loading }),
      setTokens: (token, refreshToken) => set({ token, refreshToken }),
    }),
    {
      name: 'auth-storage',
      // Persist user + isAuthenticated for UI; persist refreshToken for session restore. Access token not persisted (short-lived, restored via refresh).
      partialize: (state) => ({
        user: state.user,
        isAuthenticated: state.isAuthenticated,
        refreshToken: state.refreshToken,
      }),
    }
  )
);
