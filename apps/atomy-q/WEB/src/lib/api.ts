import axios, { AxiosRequestConfig } from 'axios';
import { useAuthStore } from '../store/use-auth-store';
import type { User } from '../store/use-auth-store';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8001/api/v1';

export const api = axios.create({
  baseURL: API_URL,
  withCredentials: true, // sending cookies
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

type RetryableRequest = AxiosRequestConfig & { _retry?: boolean };

let refreshPromise: Promise<{ accessToken: string; user: User }> | null = null;

api.interceptors.request.use(
  (config) => {
    const token = useAuthStore.getState().token;
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config as RetryableRequest;
    const isAuthRequest = typeof originalRequest.url === 'string' && originalRequest.url.includes('/auth/');

    if (error.response?.status === 401 && !originalRequest._retry && !isAuthRequest) {
      originalRequest._retry = true;
      const refreshToken = useAuthStore.getState().refreshToken;
      if (!refreshToken) {
        useAuthStore.getState().logout();
        return Promise.reject(error);
      }
      try {
        if (!refreshPromise) {
          refreshPromise = axios
            .post(
              `${API_URL}/auth/refresh`,
              { refresh_token: refreshToken },
              { withCredentials: true }
            )
            .then(async ({ data }) => {
              const newToken = data.access_token as string;
              const newRefreshToken = (data.refresh_token as string) ?? refreshToken;
              useAuthStore.getState().setTokens(newToken, newRefreshToken);
              const currentUser = useAuthStore.getState().user;

              if (currentUser) {
                return { accessToken: newToken, refreshToken: newRefreshToken, user: currentUser };
              }

              const meResponse = await axios.get(`${API_URL}/me`, {
                withCredentials: true,
                headers: { Authorization: `Bearer ${newToken}` },
              });
              const userData = (meResponse.data?.data ?? meResponse.data) as User;
              return { accessToken: newToken, refreshToken: newRefreshToken, user: userData };
            })
            .finally(() => {
              refreshPromise = null;
            });
        }

        const { accessToken, refreshToken: newRefreshToken, user } = await refreshPromise;
        if (user) {
          useAuthStore.getState().login(accessToken, newRefreshToken, user);
        } else {
          useAuthStore.getState().setTokens(accessToken, newRefreshToken);
        }
        originalRequest.headers = originalRequest.headers ?? {};
        originalRequest.headers.Authorization = `Bearer ${accessToken}`;
        return api(originalRequest);
      } catch (refreshError) {
        useAuthStore.getState().logout();
        return Promise.reject(refreshError);
      }
    }
    return Promise.reject(error);
  }
);
