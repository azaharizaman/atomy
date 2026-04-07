'use client';

import React, { Suspense } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { z } from 'zod';
import { zodResolver } from '@hookform/resolvers/zod';
import { Building2, Clock3, Globe, Lock, Mail, Sparkles, UserRound } from 'lucide-react';
import { useForm } from 'react-hook-form';
import { toast } from 'sonner';
import { Button } from '@/components/ds/Button';
import { PasswordInput, SelectInput, TextInput } from '@/components/ds/Input';
import { api } from '@/lib/api';
import { parseApiError } from '@/lib/api-error';
import { useAuthStore } from '@/store/use-auth-store';

const schema = z.object({
  company_name: z.string().min(2, 'Company name is required').max(255, 'Company name is too long'),
  tenant_code: z
    .string()
    .min(2, 'Company code is required')
    .max(64, 'Company code is too long')
    .regex(/^[A-Za-z0-9][A-Za-z0-9_-]*$/, 'Use letters, numbers, hyphen, or underscore'),
  owner_name: z.string().min(2, 'Owner name is required').max(255, 'Owner name is too long'),
  owner_email: z.string().email('Enter a valid email'),
  owner_password: z.string().min(8, 'Use at least 8 characters'),
  timezone: z.string().max(64, 'Timezone is too long').optional().or(z.literal('')),
  locale: z.string().max(16, 'Locale is too long').optional().or(z.literal('')),
  currency: z.string().length(3, 'Currency must be a 3-letter code').optional().or(z.literal('')),
});

type FormData = z.infer<typeof schema>;

const currencyOptions = [
  { value: 'USD', label: 'USD - US Dollar' },
  { value: 'EUR', label: 'EUR - Euro' },
  { value: 'GBP', label: 'GBP - British Pound' },
  { value: 'MYR', label: 'MYR - Malaysian Ringgit' },
  { value: 'SGD', label: 'SGD - Singapore Dollar' },
];

function getBrowserTimezone(): string {
  try {
    return Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
  } catch {
    return 'UTC';
  }
}

function getBrowserLocale(): string {
  if (typeof navigator !== 'undefined' && typeof navigator.language === 'string' && navigator.language.trim() !== '') {
    return navigator.language;
  }

  return 'en-US';
}

function normalizeOptionalValue(value?: string | null): string | null {
  const trimmed = typeof value === 'string' ? value.trim() : '';
  return trimmed === '' ? null : trimmed;
}

function RegisterCompanyPageContent() {
  const router = useRouter();
  const login = useAuthStore((state) => state.login);
  const [submitError, setSubmitError] = React.useState<string | null>(null);

  const { register, handleSubmit, formState: { errors, isSubmitting }, setError, setValue } = useForm<FormData>({
    resolver: zodResolver(schema),
    defaultValues: {
      company_name: '',
      tenant_code: '',
      owner_name: '',
      owner_email: '',
      owner_password: '',
      timezone: '',
      locale: '',
      currency: 'USD',
    },
  });

  React.useEffect(() => {
    setValue('timezone', getBrowserTimezone(), { shouldDirty: false, shouldValidate: false });
    setValue('locale', getBrowserLocale(), { shouldDirty: false, shouldValidate: false });
  }, [setValue]);

  const applyServerErrors = (fieldErrors: Record<string, string[]> | undefined) => {
    if (!fieldErrors) {
      return;
    }

    const fieldMap: Record<string, keyof FormData> = {
      tenant_code: 'tenant_code',
      company_name: 'company_name',
      owner_name: 'owner_name',
      owner_email: 'owner_email',
      owner_password: 'owner_password',
      timezone: 'timezone',
      locale: 'locale',
      currency: 'currency',
    };

    for (const [field, messages] of Object.entries(fieldErrors)) {
      const target = fieldMap[field];
      const message = messages.find((value) => value.trim() !== '') ?? 'Invalid value';

      if (target) {
        setError(target, { type: 'server', message });
      }
    }
  };

  const onSubmit = async (payload: FormData) => {
    setSubmitError(null);

    try {
      const response = await api.post('/auth/register-company', {
        tenant_code: payload.tenant_code.trim(),
        company_name: payload.company_name.trim(),
        owner_name: payload.owner_name.trim(),
        owner_email: payload.owner_email.trim().toLowerCase(),
        owner_password: payload.owner_password,
        timezone: normalizeOptionalValue(payload.timezone),
        locale: normalizeOptionalValue(payload.locale),
        currency: normalizeOptionalValue(payload.currency)?.toUpperCase() ?? null,
      });

      const { access_token, refresh_token, user } = response.data ?? {};
      let userData = user;

      if (!userData && access_token) {
        const meResponse = await api.get('/me', { headers: { Authorization: `Bearer ${access_token}` } });
        userData = meResponse?.data?.data ?? meResponse?.data;
      }

      if (!access_token || !userData) {
        setSubmitError('Company onboarding succeeded, but the session bootstrap was incomplete.');
        toast.error('Company onboarding response incomplete');
        return;
      }

      login(access_token, refresh_token ?? null, userData);
      toast.success('Company created successfully');
      router.push('/');
    } catch (error: unknown) {
      const parsed = parseApiError(error);
      applyServerErrors(parsed.fieldErrors);
      const message =
        parsed.message ??
        (parsed.fieldErrors && Object.keys(parsed.fieldErrors).length > 0
          ? 'Review the highlighted fields.'
          : 'Company onboarding failed');

      setSubmitError(message);
      toast.error(message);
    }
  };

  return (
    <div className="space-y-6">
      <div className="space-y-3">
        <div className="inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-indigo-700">
          <Sparkles size={12} />
          Alpha onboarding
        </div>
        <div className="space-y-1">
          <h1 className="text-2xl font-bold tracking-tight text-slate-900">Register your company</h1>
          <p className="text-sm leading-relaxed text-slate-500">
            Create the first tenant record, provision the owner account, and enter Atomy-Q with a workspace that is ready for the alpha release.
          </p>
        </div>
      </div>

      <div className="rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
        <p className="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">What happens next</p>
        <div className="mt-3 grid gap-3 sm:grid-cols-3">
          {[
            { title: 'Create tenant', desc: 'Persist the company code, name, and tenant defaults.' },
            { title: 'Create owner', desc: 'Provision the first admin user in the same transaction.' },
            { title: 'Bootstrap access', desc: 'Issue the session tokens and open the dashboard.' },
          ].map((item) => (
            <div key={item.title} className="rounded-xl border border-slate-200 bg-white px-3 py-3">
              <p className="text-sm font-semibold text-slate-900">{item.title}</p>
              <p className="mt-1 text-xs leading-relaxed text-slate-500">{item.desc}</p>
            </div>
          ))}
        </div>
      </div>

      <form className="space-y-5" onSubmit={handleSubmit(onSubmit)}>
        <div className="space-y-4">
          <div className="flex items-center gap-2 text-sm font-semibold text-slate-900">
            <Building2 size={16} className="text-indigo-600" />
            Company details
          </div>
          <TextInput
            label="Company name"
            autoComplete="organization"
            {...register('company_name')}
            placeholder="Acme Procurement Ltd"
            error={errors.company_name?.message}
            prefixIcon={<Building2 size={16} className="shrink-0" />}
          />
          <TextInput
            label="Company code"
            autoComplete="off"
            {...register('tenant_code')}
            placeholder="acme-procurement"
            error={errors.tenant_code?.message}
            hint="Use letters, numbers, hyphen, or underscore. This becomes the tenant handle."
          />
        </div>

        <div className="space-y-4">
          <div className="flex items-center gap-2 text-sm font-semibold text-slate-900">
            <UserRound size={16} className="text-indigo-600" />
            Owner account
          </div>
          <TextInput
            label="Owner full name"
            autoComplete="name"
            {...register('owner_name')}
            placeholder="Ada Lovelace"
            error={errors.owner_name?.message}
            prefixIcon={<UserRound size={16} className="shrink-0" />}
          />
          <TextInput
            label="Owner email"
            type="email"
            autoComplete="email"
            {...register('owner_email')}
            placeholder="ada@acme.com"
            error={errors.owner_email?.message}
            prefixIcon={<Mail size={16} className="shrink-0" />}
          />
          <PasswordInput
            label="Owner password"
            autoComplete="new-password"
            {...register('owner_password')}
            placeholder="Create a secure password"
            error={errors.owner_password?.message}
            prefixIcon={<Lock size={16} className="shrink-0" />}
          />
        </div>

        <div className="space-y-4">
          <div className="flex items-center gap-2 text-sm font-semibold text-slate-900">
            <Globe size={16} className="text-indigo-600" />
            Workspace defaults
          </div>
          <div className="rounded-xl border border-slate-200 bg-white p-4">
            <p className="text-xs leading-relaxed text-slate-500">
              Optional settings for the workspace. The alpha flow uses these to seed the tenant profile and session bootstrap.
            </p>
            <div className="mt-4 grid gap-4 sm:grid-cols-2">
              <TextInput
                label="Timezone"
                {...register('timezone')}
                placeholder="America/New_York"
                error={errors.timezone?.message}
                prefixIcon={<Clock3 size={16} className="shrink-0" />}
              />
              <TextInput
                label="Locale"
                {...register('locale')}
                placeholder="en-US"
                error={errors.locale?.message}
                prefixIcon={<Globe size={16} className="shrink-0" />}
              />
              <SelectInput
                label="Currency"
                {...register('currency')}
                placeholder="Select currency"
                error={errors.currency?.message}
                options={currencyOptions}
                containerClassName="sm:col-span-2"
              />
            </div>
          </div>
        </div>

        {submitError && (
          <div className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs leading-relaxed text-red-700">
            {submitError}
          </div>
        )}

        <Button type="submit" fullWidth size="lg" loading={isSubmitting}>
          Create company and sign in
        </Button>
      </form>

      <p className="text-xs leading-relaxed text-slate-500">
        Already have an account?{' '}
        <Link href="/login" className="font-medium text-indigo-600 hover:text-indigo-700">
          Back to sign in
        </Link>
        .
      </p>
    </div>
  );
}

export default function RegisterCompanyPage() {
  return (
    <Suspense
      fallback={
        <div className="space-y-6 animate-pulse">
          <div className="h-6 w-52 rounded bg-slate-200" />
          <div className="h-4 w-full max-w-md rounded bg-slate-100" />
          <div className="h-10 w-full rounded bg-slate-100" />
          <div className="h-10 w-full rounded bg-slate-100" />
          <div className="h-10 w-full rounded bg-slate-100" />
        </div>
      }
    >
      <RegisterCompanyPageContent />
    </Suspense>
  );
}
