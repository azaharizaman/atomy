import type { Page } from '@playwright/test';

export interface AuthBootstrapUser {
  id: string;
  name: string;
  email: string;
  role: string;
  tenantId: string;
}

export async function seedAuthSession(
  page: Page,
  user: AuthBootstrapUser,
  options?: { token?: string; refreshToken?: string | null },
): Promise<void> {
  const token = options?.token ?? 'test-token';
  const refreshToken = options?.refreshToken ?? 'test-refresh';

  await page.addInitScript(
    ({ authStorageKey, authState }) => {
      window.localStorage.setItem(
        authStorageKey,
        JSON.stringify({
          state: authState,
          version: 0,
        }),
      );
    },
    {
      authStorageKey: 'auth-storage',
      authState: {
        user,
        token,
        refreshToken,
        isAuthenticated: true,
        isLoading: false,
      },
    },
  );
}
