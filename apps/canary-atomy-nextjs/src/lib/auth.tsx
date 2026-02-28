"use client";

import React, { createContext, useContext, useEffect, useState } from "react";
import { AuthContextType, login as apiLogin, logout as apiLogout } from "./api";

interface AuthState {
  auth: AuthContextType | null;
  isLoading: boolean;
  error: string | null;
}

interface AuthContextValue extends AuthState {
  login: (email: string, password: string, tenantId: string) => Promise<void>;
  logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [state, setState] = useState<AuthState>({
    auth: null,
    isLoading: true,
    error: null,
  });

  useEffect(() => {
    const storedAuth = localStorage.getItem("auth");
    if (storedAuth) {
      try {
        setState({ auth: JSON.parse(storedAuth), isLoading: false, error: null });
      } catch {
        localStorage.removeItem("auth");
        setState({ auth: null, isLoading: false, error: null });
      }
    } else {
      setState((s) => ({ ...s, isLoading: false }));
    }
  }, []);

  const login = async (email: string, password: string, tenantId: string) => {
    setState((s) => ({ ...s, isLoading: true, error: null }));
    try {
      const auth = await apiLogin(email, password, tenantId);
      localStorage.setItem("auth", JSON.stringify(auth));
      setState({ auth, isLoading: false, error: null });
    } catch (err: any) {
      setState({ auth: null, isLoading: false, error: err.message || "Login failed" });
      throw err;
    }
  };

  const logout = async () => {
    if (state.auth) {
      try {
        await apiLogout(state.auth.userId, state.auth.tenantId, state.auth.sessionId);
      } catch {
        // ignore logout errors
      }
    }
    localStorage.removeItem("auth");
    setState({ auth: null, isLoading: false, error: null });
  };

  return (
    <AuthContext.Provider value={{ ...state, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error("useAuth must be used within an AuthProvider");
  }
  return context;
}
