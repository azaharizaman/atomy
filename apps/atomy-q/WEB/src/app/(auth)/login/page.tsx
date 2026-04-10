'use client';

import React, { Suspense } from 'react';
import Link from 'next/link';
import { useForm, useWatch } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useRouter, useSearchParams } from 'next/navigation';
import { Mail, Lock } from 'lucide-react';
import { toast } from 'sonner';
import { api } from '@/lib/api';
import { parseApiError } from '@/lib/api-error';
import { useAuthStore } from '@/store/use-auth-store';
import { Button } from '@/components/ds/Button';
import { Checkbox, PasswordInput, TextInput } from '@/components/ds/Input';

const schema = z.object({
  email: z.string().email('Enter a valid email'),
  password: z.string().min(1, 'Password is required'),
  remember_device: z.boolean().optional(),
});

type FormData = z.infer<typeof schema>;

function LoginPageContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const sessionExpired = searchParams.get('session_expired') === '1';
  const [authError, setAuthError] = React.useState<string | null>(null);
  const { login } = useAuthStore();
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  const { register, handleSubmit, control, formState: { errors, isSubmitting }, setValue } = useForm<FormData>({
    resolver: zodResolver(schema),
    defaultValues: {
      email: '',
      password: '',
      remember_device: true,
    },
  });

  const rememberDevice = useWatch({ control, name: 'remember_device' }) ?? true;

  const onSubmit = async (payload: FormData) => {
    setAuthError(null);
    try {
      const response = await api.post('/auth/login', {
        email: payload.email.trim().toLowerCase(),
        password: payload.password,
      });
      const { access_token, refresh_token, user } = response.data ?? {};
      let userData = user;
      if (!userData && access_token) {
        const meResponse = await api.get('/me', { headers: { Authorization: `Bearer ${access_token}` } });
        userData = meResponse?.data?.data ?? meResponse?.data;
      }
      if (!userData || !access_token) {
        setAuthError('Login succeeded but user data was missing. Please try again.');
        toast.error('Login response incomplete');
        return;
      }
      login(access_token, refresh_token ?? null, userData);
      toast.success('Signed in successfully');
      const redirect = searchParams.get('redirect');
      router.push(redirect && redirect.startsWith('/') ? redirect : '/');
    } catch (error: unknown) {
      const parsed = parseApiError(error);
      const message =
        parsed.message ??
        (parsed.status === 422
          ? 'Please check email and password.'
          : parsed.status === 401
            ? 'Invalid credentials'
            : parsed.status === 404
              ? 'API route not found. Ensure NEXT_PUBLIC_API_URL ends with /api/v1 (e.g. http://localhost:8000/api/v1).'
              : 'Sign-in failed. Check that the API is running and NEXT_PUBLIC_API_URL is correct.');
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

      {sessionExpired && (
        <div className="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700">
          Your session expired. Please sign in again.
        </div>
      )}

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

        <div className="flex items-center justify-between gap-3">
          <Checkbox
            label="Remember this device"
            checked={rememberDevice}
            onChange={(e) => setValue('remember_device', e.currentTarget.checked)}
          />
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
              login('mock-access-token', null, {
                id: 'mock-user-1',
                name: 'Alex Kumar',
                email: 'user1@example.com',
                role: 'admin',
                tenantId,
              });
              toast.success('Signed in with mock account');
              router.push('/');
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
                await api.post('/auth/sso');
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
  return (
    <Suspense
      fallback={
        <div className="space-y-6 animate-pulse">
          <div className="h-6 w-48 rounded bg-slate-200" />
          <div className="h-4 w-full max-w-md rounded bg-slate-100" />
          <div className="h-10 w-full rounded bg-slate-100" />
          <div className="h-10 w-full rounded bg-slate-100" />
        </div>
      }
    >
      <LoginPageContent />
    </Suspense>
  );
}
