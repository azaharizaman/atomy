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
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (token: string, user: User) => void;
  logout: () => void;
  setLoading: (loading: boolean) => void;
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      user: null,
      token: null,
      isAuthenticated: false,
      isLoading: true,
      login: (token, user) => set({ token, user, isAuthenticated: true, isLoading: false }),
      logout: () => set({ token: null, user: null, isAuthenticated: false, isLoading: false }),
      setLoading: (loading) => set({ isLoading: loading }),
    }),
    {
      name: 'auth-storage',
      partialize: (state) => ({ user: state.user, isAuthenticated: state.isAuthenticated }), // Don't persist token if we want it in memory only, but for dev ease we might persist it. PLAN says memory only for access token. I'll stick to PLAN.
      // Wait, PLAN says "Access token: keep in memory only". So I should NOT persist token.
      // However, if I reload, I lose the token. The PLAN says "On load: call GET /api/v1/me (or equivalent); on 401, attempt refresh".
      // So I need a way to restore session on load.
    }
  )
);
