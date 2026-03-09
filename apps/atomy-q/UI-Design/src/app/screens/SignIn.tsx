import { useState, useEffect } from 'react';
import { useNavigate, useSearchParams } from 'react-router';
import {
  Eye, EyeOff, Lock, Mail, ShieldCheck, Zap, BarChart3,
  FileSearch, AlertTriangle, Loader2
} from 'lucide-react';

const brandFeatures = [
  { icon: FileSearch, text: 'Intelligent Quote Comparison' },
  { icon: BarChart3, text: 'AI-Powered Recommendations' },
  { icon: ShieldCheck, text: 'Compliance & Risk Management' },
  { icon: Zap, text: 'Automated Approval Workflows' },
];

export function SignIn() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();

  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [rememberDevice, setRememberDevice] = useState(false);
  const [error, setError] = useState('');
  const [showMfa, setShowMfa] = useState(false);
  const [mfaCode, setMfaCode] = useState('');
  const [loading, setLoading] = useState(false);
  const [sessionExpired, setSessionExpired] = useState(false);

  useEffect(() => {
    if (searchParams.get('session_expired') === '1') {
      setSessionExpired(true);
    }
  }, [searchParams]);

  function handleSignIn(e: React.FormEvent) {
    e.preventDefault();
    setError('');

    if (!email.trim() || !password.trim()) {
      setError('Please enter your email and password.');
      return;
    }

    setLoading(true);

    setTimeout(() => {
      if (password === 'mfa') {
        setLoading(false);
        setShowMfa(true);
        return;
      }

      if (password === 'wrong') {
        setLoading(false);
        setError('Invalid email or password. Please try again.');
        return;
      }

      setLoading(false);
      navigate('/');
    }, 800);
  }

  function handleMfaSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError('');

    if (mfaCode.length !== 6) {
      setError('Please enter a valid 6-digit code.');
      return;
    }

    setLoading(true);
    setTimeout(() => {
      setLoading(false);
      navigate('/');
    }, 600);
  }

  return (
    <div className="min-h-screen flex">
      {/* Brand Panel */}
      <div
        className="hidden lg:flex lg:w-[40%] relative flex-col justify-between p-10"
        style={{ background: 'linear-gradient(135deg, #0B1629 0%, #132042 50%, #0B1629 100%)' }}
      >
        <div
          className="absolute inset-0 opacity-[0.04]"
          style={{
            backgroundImage:
              'radial-gradient(circle at 25% 25%, #6366f1 1px, transparent 1px), radial-gradient(circle at 75% 75%, #6366f1 1px, transparent 1px)',
            backgroundSize: '40px 40px',
          }}
        />

        <div className="relative z-10">
          <div className="flex items-center gap-3 mb-2">
            <div className="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center">
              <span className="text-white text-lg" style={{ fontWeight: 700 }}>Q</span>
            </div>
            <span className="text-white text-2xl tracking-tight" style={{ fontWeight: 700 }}>
              Atomy-Q
            </span>
          </div>
          <p className="text-indigo-300 text-lg mt-1" style={{ fontWeight: 400 }}>
            Procurement Intelligence Platform
          </p>
        </div>

        <div className="relative z-10 space-y-5">
          {brandFeatures.map((feat, i) => (
            <div key={i} className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-lg bg-white/[0.06] border border-white/[0.08] flex items-center justify-center">
                <feat.icon className="w-5 h-5 text-indigo-400" />
              </div>
              <span className="text-slate-300 text-sm" style={{ fontWeight: 500 }}>
                {feat.text}
              </span>
            </div>
          ))}
        </div>

        <div className="relative z-10">
          <p className="text-slate-500 text-xs">
            &copy; {new Date().getFullYear()} Atomy-Q. All rights reserved.
          </p>
        </div>
      </div>

      {/* Sign-in Panel */}
      <div className="flex-1 flex flex-col">
        {/* Session expired banner */}
        {sessionExpired && (
          <div className="flex items-center gap-2 px-4 py-3 bg-amber-50 border-b border-amber-200 text-amber-800 text-sm">
            <AlertTriangle className="w-4 h-4 flex-shrink-0" />
            Your session has expired. Please sign in again.
          </div>
        )}

        <div className="flex-1 flex items-center justify-center px-6">
          <div className="w-full max-w-md">
            {/* Mobile logo */}
            <div className="lg:hidden flex items-center gap-2 mb-8 justify-center">
              <div className="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center">
                <span className="text-white text-base" style={{ fontWeight: 700 }}>Q</span>
              </div>
              <span className="text-slate-900 text-xl" style={{ fontWeight: 700 }}>Atomy-Q</span>
            </div>

            <div className="mb-8">
              <h1 className="text-2xl text-slate-900" style={{ fontWeight: 600 }}>
                {showMfa ? 'Two-factor authentication' : 'Welcome back'}
              </h1>
              <p className="text-slate-500 mt-1.5 text-sm">
                {showMfa
                  ? 'Enter the 6-digit code from your authenticator app.'
                  : 'Sign in to your account to continue.'}
              </p>
            </div>

            {error && (
              <div className="mb-4 flex items-center gap-2 px-3.5 py-2.5 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                <AlertTriangle className="w-4 h-4 flex-shrink-0" />
                {error}
              </div>
            )}

            {showMfa ? (
              <form onSubmit={handleMfaSubmit} className="space-y-5">
                <div>
                  <label className="block text-sm text-slate-700 mb-1.5" style={{ fontWeight: 500 }}>
                    Verification code
                  </label>
                  <div className="flex gap-2 justify-between">
                    {Array.from({ length: 6 }).map((_, i) => (
                      <input
                        key={i}
                        type="text"
                        maxLength={1}
                        inputMode="numeric"
                        className="w-12 h-12 text-center text-lg border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        style={{ fontWeight: 600 }}
                        value={mfaCode[i] || ''}
                        onChange={e => {
                          const val = e.target.value.replace(/\D/g, '');
                          const updated = mfaCode.split('');
                          updated[i] = val;
                          setMfaCode(updated.join('').slice(0, 6));
                          if (val && e.target.nextElementSibling instanceof HTMLInputElement) {
                            e.target.nextElementSibling.focus();
                          }
                        }}
                        onKeyDown={e => {
                          if (e.key === 'Backspace' && !mfaCode[i] && e.target instanceof HTMLInputElement) {
                            const prev = e.target.previousElementSibling;
                            if (prev instanceof HTMLInputElement) prev.focus();
                          }
                        }}
                      />
                    ))}
                  </div>
                </div>

                <button
                  type="submit"
                  disabled={loading}
                  className="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-60"
                  style={{ fontWeight: 500 }}
                >
                  {loading && <Loader2 className="w-4 h-4 animate-spin" />}
                  Verify &amp; Sign In
                </button>

                <button
                  type="button"
                  onClick={() => { setShowMfa(false); setMfaCode(''); setError(''); }}
                  className="w-full text-sm text-slate-500 hover:text-indigo-600 transition-colors"
                >
                  Back to sign in
                </button>
              </form>
            ) : (
              <form onSubmit={handleSignIn} className="space-y-5">
                <div>
                  <label className="block text-sm text-slate-700 mb-1.5" style={{ fontWeight: 500 }}>
                    Email address
                  </label>
                  <div className="relative">
                    <Mail className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                    <input
                      type="email"
                      placeholder="you@company.com"
                      value={email}
                      onChange={e => setEmail(e.target.value)}
                      className="w-full pl-10 pr-4 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    />
                  </div>
                </div>

                <div>
                  <div className="flex items-center justify-between mb-1.5">
                    <label className="block text-sm text-slate-700" style={{ fontWeight: 500 }}>
                      Password
                    </label>
                    <button
                      type="button"
                      className="text-xs text-indigo-600 hover:text-indigo-700"
                      style={{ fontWeight: 500 }}
                    >
                      Forgot password?
                    </button>
                  </div>
                  <div className="relative">
                    <Lock className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                    <input
                      type={showPassword ? 'text' : 'password'}
                      placeholder="Enter your password"
                      value={password}
                      onChange={e => setPassword(e.target.value)}
                      className="w-full pl-10 pr-10 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    />
                    <button
                      type="button"
                      onClick={() => setShowPassword(!showPassword)}
                      className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                    >
                      {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                    </button>
                  </div>
                </div>

                <div className="flex items-center gap-2">
                  <input
                    type="checkbox"
                    id="remember"
                    checked={rememberDevice}
                    onChange={e => setRememberDevice(e.target.checked)}
                    className="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                  />
                  <label htmlFor="remember" className="text-sm text-slate-600">
                    Remember this device
                  </label>
                </div>

                <button
                  type="submit"
                  disabled={loading}
                  className="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-60"
                  style={{ fontWeight: 500 }}
                >
                  {loading && <Loader2 className="w-4 h-4 animate-spin" />}
                  Sign In
                </button>

                <div className="relative flex items-center gap-3 my-1">
                  <div className="flex-1 h-px bg-slate-200" />
                  <span className="text-xs text-slate-400">or</span>
                  <div className="flex-1 h-px bg-slate-200" />
                </div>

                <button
                  type="button"
                  className="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-white border border-slate-200 text-slate-700 text-sm rounded-lg hover:bg-slate-50 transition-colors"
                  style={{ fontWeight: 500 }}
                >
                  <ShieldCheck className="w-4 h-4" />
                  Sign in with SSO
                </button>
              </form>
            )}

            <p className="mt-8 text-center text-xs text-slate-400 flex items-center justify-center gap-1.5">
              <Lock className="w-3 h-3" />
              Protected by enterprise-grade security. All sessions encrypted.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
