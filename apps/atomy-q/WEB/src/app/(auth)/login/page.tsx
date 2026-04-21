'use client';

import React from 'react';
import Link from 'next/link';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useRouter } from 'next/navigation';
import { Mail, Lock } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ds/Button';
import { PasswordInput, TextInput } from '@/components/ds/Input';
import { useAuthStore } from '@/store/use-auth-store';

const schema = z.object({
  email: z.string().email('Enter a valid email'),
  password: z.string().min(1, 'Password is required'),
});

type FormData = z.infer<typeof schema>;

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api/v1';

function getSafeRedirectTarget(): string {
  if (typeof window === 'undefined') return '/';

  const redirect = new URLSearchParams(window.location.search).get('redirect');
  if (!redirect || !redirect.startsWith('/') || redirect.startsWith('//')) return '/';

  try {
    const parsed = new URL(redirect, window.location.origin);
    return parsed.origin === window.location.origin ? `${parsed.pathname}${parsed.search}${parsed.hash}` : '/';
  } catch {
    return '/';
  }
}

async function readJsonResponse(response: Response): Promise<unknown> {
  const text = await response.text();
  if (!text.trim()) return null;
  try {
    return JSON.parse(text) as unknown;
  } catch {
    return { message: text };
  }
}

function LoginPageContent() {
  const router = useRouter();
  const login = useAuthStore((state) => state.login);
  const [authError, setAuthError] = React.useState<string | null>(null);
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  const { register, handleSubmit, formState: { errors, isSubmitting } } = useForm<FormData>({
    resolver: zodResolver(schema),
    defaultValues: {
      email: '',
      password: '',
    },
  });

  const persistAuthSession = React.useCallback(
    (accessToken: string, refreshToken: string | null, user: unknown) => {
      login(accessToken, refreshToken, user as Parameters<typeof login>[2]);
      router.push(getSafeRedirectTarget());
    },
    [login, router],
  );

  const onSubmit = async (payload: FormData) => {
    setAuthError(null);
    try {
      const response = await fetch(`${API_URL}/auth/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          email: payload.email.trim().toLowerCase(),
          password: payload.password,
        }),
      });
      const responseData = await readJsonResponse(response);
      if (!response.ok) {
        const payloadData = responseData && typeof responseData === 'object' ? (responseData as Record<string, unknown>) : null;
        const messageRaw = payloadData?.message ?? payloadData?.error;
        const message =
          typeof messageRaw === 'string' && messageRaw.trim() !== ''
            ? messageRaw
            : response.status === 422
              ? 'Please check email and password.'
              : response.status === 401
                ? 'Invalid credentials'
                : response.status === 404
                  ? 'API route not found. Ensure NEXT_PUBLIC_API_URL ends with /api/v1 (e.g. http://localhost:8000/api/v1).'
                  : 'Sign-in failed. Check that the API is running and NEXT_PUBLIC_API_URL is correct.';
        throw new Error(message);
      }
      const { access_token, refresh_token, user } =
        (responseData && typeof responseData === 'object' ? (responseData as Record<string, unknown>) : {}) as {
          access_token?: string;
          refresh_token?: string | null;
          user?: unknown;
        };
      let userData = user;
      if (!userData && access_token) {
        const meResponse = await fetch(`${API_URL}/me`, {
          headers: {
            Authorization: `Bearer ${access_token}`,
            Accept: 'application/json',
          },
          credentials: 'include',
        });
        if (!meResponse.ok) {
          throw new Error('Login succeeded but user data could not be loaded.');
        }
        const meData = await readJsonResponse(meResponse);
        userData = (meData && typeof meData === 'object' && 'data' in meData
          ? (meData as { data?: unknown }).data
          : meData) as unknown;
      }
      if (!userData || !access_token) {
        setAuthError('Login succeeded but user data was missing. Please try again.');
        toast.error('Login response incomplete');
        return;
      }
      persistAuthSession(access_token, refresh_token ?? null, userData);
      toast.success('Signed in successfully');
    } catch (error: unknown) {
      const message = error instanceof Error ? error.message : 'Sign-in failed. Check that the API is running and NEXT_PUBLIC_API_URL is correct.';
      setAuthError(message);
      toast.error(message);
    }
  };

  return (
    <div className="space-y-6">
      <div className="space-y-1 text-center sm:text-left">
        <h1 className="text-xl font-bold text-slate-900">Log in to your account</h1>
        <p className="text-sm text-slate-500">
          Welcome back! Sign in with your workspace credentials.
        </p>
      </div>

      <form className="space-y-4" onSubmit={handleSubmit(onSubmit)}>
        <TextInput
          label="Email"
          type="email"
          autoComplete="email"
          {...register('email')}
          placeholder="Email"
          error={errors.email?.message}
          prefixIcon={<Mail size={16} className="shrink-0" />}
        />
        <PasswordInput
          label="Password"
          autoComplete="current-password"
          {...register('password')}
          placeholder="Password"
          error={errors.password?.message}
          prefixIcon={<Lock size={16} className="shrink-0" />}
        />

        <div className="flex items-center justify-end gap-3">
          <button
            type="button"
            className="text-sm font-medium text-indigo-600 hover:text-indigo-700 whitespace-nowrap"
            onClick={() => router.push('/forgot-password')}
          >
            Forgot password?
          </button>
        </div>

        {authError && (
          <div className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
            {authError}
          </div>
        )}

        <Button type="submit" fullWidth size="lg" loading={isSubmitting}>
          Log in
        </Button>

        {useMocks && (
          <Button
            type="button"
            variant="outline"
            fullWidth
            size="md"
            onClick={() => {
              const tenantId =
                process.env.NEXT_PUBLIC_TENANT_ID || '01KKH77M4R0V8QZ1M8NB3XWWWQ';
              persistAuthSession('mock-access-token', null, {
                id: 'mock-user-1',
                name: 'Alex Kumar',
                email: 'user1@example.com',
                role: 'admin',
                tenantId,
              });
              toast.success('Signed in with mock account');
            }}
          >
            Use mock account
          </Button>
        )}

        {!useMocks && (
          <Button
            type="button"
            variant="outline"
            fullWidth
            size="md"
            onClick={async () => {
              try {
                const response = await fetch(`${API_URL}/auth/sso`, {
                  method: 'POST',
                  headers: {
                    Accept: 'application/json',
                  },
                  credentials: 'include',
                });
                if (!response.ok) {
                  throw new Error('SSO is not enabled yet');
                }
                toast.success('SSO flow started');
              } catch {
                toast.error('SSO is not enabled yet');
              }
            }}
          >
            Continue with SSO
          </Button>
        )}
      </form>

      <p className="text-xs text-slate-500 text-center sm:text-left">
        Need access? Contact your workspace administrator to provision an account, or{' '}
        <Link href="/register-company" className="font-medium text-indigo-600 hover:text-indigo-700">
          register a new company
        </Link>
        .
      </p>
    </div>
  );
}

export default function LoginPage() {
  return <LoginPageContent />;
}
