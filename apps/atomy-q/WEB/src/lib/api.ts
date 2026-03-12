import axios, { AxiosRequestConfig } from 'axios';
import { useAuthStore } from '../store/use-auth-store';
import type { User } from '../store/use-auth-store';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';

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
      try {
        if (!refreshPromise) {
          refreshPromise = axios
            .post(`${API_URL}/auth/refresh`, {}, { withCredentials: true })
            .then(async ({ data }) => {
              const newToken = data.access_token as string;
              const currentUser = useAuthStore.getState().user;

              if (currentUser) {
                return { accessToken: newToken, user: currentUser };
              }

              const meResponse = await axios.get(`${API_URL}/me`, {
                withCredentials: true,
                headers: { Authorization: `Bearer ${newToken}` },
              });

              return { accessToken: newToken, user: meResponse.data as User };
            })
            .finally(() => {
              refreshPromise = null;
            });
        }

        const { accessToken, user } = await refreshPromise;
        useAuthStore.getState().login(accessToken, user);
        originalRequest.headers = originalRequest.headers ?? {};
        originalRequest.headers.Authorization = `Bearer ${accessToken}`;
        return api(originalRequest);
      } catch (refreshError) {
        useAuthStore.getState().logout();
        // Redirect to login handled by protected routes or layout
        return Promise.reject(refreshError);
      }
    }
    return Promise.reject(error);
  }
);
