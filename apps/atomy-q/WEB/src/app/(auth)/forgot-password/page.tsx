'use client';

import React from 'react';
import { useForm } from 'react-hook-form';
import { z } from 'zod';
import { zodResolver } from '@hookform/resolvers/zod';
import { useRouter } from 'next/navigation';
import { Mail } from 'lucide-react';
import { toast } from 'sonner';
import { api } from '@/lib/api';
import { Button } from '@/components/ds/Button';
import { TextInput } from '@/components/ds/Input';

const schema = z.object({
  email: z.string().email('Enter a valid email'),
});

type FormData = z.infer<typeof schema>;

export default function ForgotPasswordPage() {
  const router = useRouter();
  const [status, setStatus] = React.useState<'idle' | 'sent'>('idle');
  const [error, setError] = React.useState<string | null>(null);

  const { register, handleSubmit, formState: { errors, isSubmitting }, getValues } = useForm<FormData>({
    resolver: zodResolver(schema),
    defaultValues: {
      email: '',
    },
  });

  const onSubmit = async (payload: FormData) => {
    setError(null);
    try {
      await api.post('/auth/forgot-password', {
        email: payload.email.trim().toLowerCase(),
      });
      setStatus('sent');
      toast.success('Reset link sent. Check your inbox.');
    } catch (err: unknown) {
      const axiosish = err as { response?: { status?: number; data?: Record<string, unknown> } };
      if (axiosish?.response?.status === 501) {
        setStatus('sent');
        toast.success('Reset link simulated for development.');
        return;
      }
      const messageRaw = axiosish?.response?.data?.message;
      const message = typeof messageRaw === 'string' && messageRaw.trim() !== '' ? messageRaw : 'Unable to send reset link';
      setError(message);
      toast.error(message);
    }
  };

  return (
    <div className="space-y-6">
      <div className="space-y-1 text-center sm:text-left">
        <h1 className="text-xl font-bold text-slate-900">Forgot password?</h1>
        <p className="text-sm text-slate-500">
          Enter your email. We’ll send a secure reset link to your inbox if an account exists.
        </p>
      </div>

      {status === 'sent' ? (
        <div className="space-y-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
          <p className="text-sm text-emerald-800">
            If an account exists, a reset link has been sent to <strong>{getValues('email')}</strong>.
          </p>
          <div className="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
            <Button size="md" onClick={() => setStatus('idle')}>
              Send another link
            </Button>
            <Button size="md" variant="outline" onClick={() => router.push('/login')}>
              Back to sign in
            </Button>
          </div>
        </div>
      ) : (
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

          {error && (
            <div className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
              {error}
            </div>
          )}

          <Button type="submit" fullWidth size="lg" loading={isSubmitting}>
            Send reset link
          </Button>
          <button
            type="button"
            className="w-full text-sm font-medium text-indigo-600 hover:text-indigo-700 py-2"
            onClick={() => router.push('/login')}
          >
            Back to sign in
          </button>
        </form>
      )}
    </div>
  );
}
