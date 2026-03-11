import React from 'react';
import { ShieldAlert, ShieldCheck } from 'lucide-react';
import { Card } from './Card';
import { TextInput, PasswordInput, Checkbox } from './Input';
import { Button } from './Button';
import { Banner } from './Alert';

interface InlineAuthErrorProps {
  message: string;
  className?: string;
}

export function InlineAuthError({ message, className = '' }: InlineAuthErrorProps) {
  return (
    <div className={['flex items-start gap-2 rounded-md border border-red-200 bg-red-50 px-3 py-2', className].join(' ')}>
      <ShieldAlert size={14} className="text-red-600 mt-0.5 shrink-0" />
      <p className="text-xs text-red-700">{message}</p>
    </div>
  );
}

interface SessionExpiryBannerProps {
  visible?: boolean;
  message?: string;
}

export function SessionExpiryBanner({
  visible = false,
  message = 'Your session has expired. Please sign in again.',
}: SessionExpiryBannerProps) {
  if (!visible) return null;
  return <Banner variant="warning">{message}</Banner>;
}

interface MfaPromptPanelProps {
  methodLabel?: string;
  onVerify?: () => void;
  onResend?: () => void;
}

export function MfaPromptPanel({
  methodLabel = 'Authenticator App',
  onVerify,
  onResend,
}: MfaPromptPanelProps) {
  return (
    <Card padding="md">
      <div className="flex items-center gap-2 mb-2">
        <ShieldCheck size={14} className="text-indigo-600" />
        <p className="text-sm font-semibold text-slate-800">Verify MFA</p>
      </div>
      <p className="text-xs text-slate-500 mb-3">
        Enter the 6-digit code from {methodLabel}.
      </p>
      <TextInput placeholder="123456" maxLength={6} />
      <div className="flex items-center justify-between mt-3">
        <button className="text-xs text-indigo-600 hover:text-indigo-700" onClick={onResend}>
          Resend code
        </button>
        <Button size="sm" onClick={onVerify}>Verify</Button>
      </div>
    </Card>
  );
}

interface SignInCardProps {
  title?: string;
  subtitle?: string;
  loading?: boolean;
  error?: string;
  sessionExpired?: boolean;
  onSubmit?: (payload: { email: string; password: string; rememberDevice: boolean }) => void;
  onSso?: () => void;
  onForgotPassword?: () => void;
}

export function SignInCard({
  title = 'Sign in to Atomy-Q',
  subtitle = 'Use your workspace credentials to continue.',
  loading = false,
  error,
  sessionExpired = false,
  onSubmit,
  onSso,
  onForgotPassword,
}: SignInCardProps) {
  const [email, setEmail] = React.useState('');
  const [password, setPassword] = React.useState('');
  const [rememberDevice, setRememberDevice] = React.useState(false);

  return (
    <Card padding="lg" className="w-full max-w-md">
      <SessionExpiryBanner visible={sessionExpired} />
      <h2 className="text-lg font-semibold text-slate-900 mt-2">{title}</h2>
      <p className="text-sm text-slate-500 mt-1">{subtitle}</p>

      <div className="mt-4 space-y-3">
        <TextInput
          label="Email or Username"
          value={email}
          onChange={e => setEmail(e.target.value)}
          placeholder="name@company.com"
          required
        />
        <PasswordInput
          label="Password"
          value={password}
          onChange={e => setPassword(e.target.value)}
          placeholder="••••••••"
          required
        />
        <div className="flex items-center justify-between">
          <Checkbox
            label="Remember this device"
            checked={rememberDevice}
            onChange={e => setRememberDevice(e.currentTarget.checked)}
          />
          <button className="text-xs text-indigo-600 hover:text-indigo-700" onClick={onForgotPassword}>
            Forgot password?
          </button>
        </div>
        {error && <InlineAuthError message={error} />}
      </div>

      <div className="mt-4 flex flex-col gap-2">
        <Button
          loading={loading}
          onClick={() => onSubmit?.({ email, password, rememberDevice })}
          fullWidth
        >
          Sign In
        </Button>
        <Button variant="outline" onClick={onSso} fullWidth>
          Continue with SSO
        </Button>
      </div>
    </Card>
  );
}
