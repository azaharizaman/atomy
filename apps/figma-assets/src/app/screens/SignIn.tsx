import { useState } from "react";
import { useNavigate } from "react-router";
import { Zap, Eye, EyeOff, Shield, AlertCircle, Lock, ChevronRight, ArrowRight } from "lucide-react";

const features = [
  { title: "AI-Powered Quote Parsing", desc: "Ingest any vendor format and normalize automatically" },
  { title: "Policy-Aware Scoring", desc: "Weighted criteria with compliance guardrails built-in" },
  { title: "Governed Approvals", desc: "Full audit trail with immutable decision lineage" },
  { title: "Risk Intelligence", desc: "Real-time sanctions, insurance, and SLA monitoring" },
];

export function SignIn() {
  const navigate = useNavigate();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const [step, setStep] = useState<"credentials" | "mfa">("credentials");
  const [mfaCode, setMfaCode] = useState("");

  const handleSignIn = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setLoading(true);
    await new Promise((r) => setTimeout(r, 900));
    if (email === "locked@atomy.io") {
      setError("Account locked. Too many failed attempts. Contact your administrator.");
      setLoading(false);
      return;
    }
    if (email && password.length >= 6) {
      setStep("mfa");
      setLoading(false);
    } else {
      setError("Invalid credentials. Please check your email and password.");
      setLoading(false);
    }
  };

  const handleMFA = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setLoading(true);
    await new Promise((r) => setTimeout(r, 700));
    if (mfaCode === "000000" || mfaCode.length === 6) {
      navigate("/dashboard");
    } else {
      setError("Invalid verification code. Please try again.");
      setLoading(false);
    }
  };

  return (
    <div className="flex h-screen overflow-hidden" style={{ background: "var(--app-bg-canvas)", fontFamily: "'Inter', system-ui, sans-serif" }}>
      {/* Left panel */}
      <div
        className="hidden lg:flex flex-col justify-between p-10 relative overflow-hidden"
        style={{ width: 480, flexShrink: 0, background: "var(--app-nav-bg)" }}
      >
        {/* Grid overlay */}
        <div className="absolute inset-0 pointer-events-none" style={{ backgroundImage: "linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px)", backgroundSize: "32px 32px" }} />
        {/* Glow */}
        <div className="absolute rounded-full pointer-events-none" style={{ width: 400, height: 400, background: "radial-gradient(circle, var(--app-brand-tint-6) 0%, transparent 70%)", top: -100, left: -100 }} />

        {/* Logo */}
        <div className="relative z-10">
          <div className="flex items-center gap-3 mb-16">
            <div className="flex items-center justify-center rounded-xl" style={{ width: 40, height: 40, background: "linear-gradient(135deg, var(--app-brand-500), var(--app-brand-700))" }}>
              <Zap size={20} color="white" />
            </div>
            <div>
              <div style={{ fontSize: 20, fontWeight: 700, color: "var(--app-nav-text-strong)", letterSpacing: "-0.02em" }}>Atomy-Q</div>
              <div style={{ fontSize: 10, color: "var(--app-brand-400)", letterSpacing: "0.12em", fontWeight: 600, textTransform: "uppercase" }}>Procurement Intelligence</div>
            </div>
          </div>

          <div className="mb-10">
            <h1 style={{ fontSize: 28, fontWeight: 700, color: "var(--app-nav-text-strong)", letterSpacing: "-0.02em", lineHeight: 1.2, marginBottom: 12 }}>
              Quote comparison,<br />
              <span style={{ color: "var(--app-brand-400)" }}>governed end-to-end.</span>
            </h1>
            <p style={{ fontSize: 15, color: "var(--app-nav-text-main)", lineHeight: 1.6 }}>
              From vendor invite to award decision — every action traced, every outcome defensible.
            </p>
          </div>

          <div className="space-y-4">
            {features.map((f) => (
              <div key={f.title} className="flex items-start gap-3">
                <div className="flex-shrink-0 mt-0.5 rounded" style={{ width: 18, height: 18, background: "var(--app-nav-hover)", display: "flex", alignItems: "center", justifyContent: "center" }}>
                  <ChevronRight size={11} style={{ color: "var(--app-brand-400)" }} />
                </div>
                <div>
                  <div style={{ fontSize: 13, fontWeight: 600, color: "var(--app-nav-text-strong)", marginBottom: 1 }}>{f.title}</div>
                  <div style={{ fontSize: 12, color: "var(--app-nav-text-main)", lineHeight: 1.5 }}>{f.desc}</div>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Bottom stats */}
        <div className="relative z-10">
          <div className="flex gap-6 pb-2">
            {[
              { value: "$47M+", label: "Savings Tracked" },
              { value: "98.2%", label: "Audit Pass Rate" },
              { value: "2,400+", label: "RFQs Processed" },
            ].map((stat) => (
              <div key={stat.label}>
                <div style={{ fontSize: 18, fontWeight: 700, color: "var(--app-brand-400)", letterSpacing: "-0.02em" }}>{stat.value}</div>
                <div style={{ fontSize: 11, color: "var(--app-nav-text-muted)", marginTop: 1 }}>{stat.label}</div>
              </div>
            ))}
          </div>
          <div style={{ fontSize: 11, color: "var(--app-nav-text-muted)", marginTop: 12 }}>
            Enterprise-grade security · SOC 2 Type II · ISO 27001
          </div>
        </div>
      </div>

      {/* Right panel */}
      <div className="flex-1 flex flex-col items-center justify-center px-8 relative" style={{ background: "var(--app-bg-canvas)" }}>
        {/* Mobile logo */}
        <div className="lg:hidden absolute top-8 left-8 flex items-center gap-2">
          <div className="flex items-center justify-center rounded-lg" style={{ width: 32, height: 32, background: "linear-gradient(135deg, var(--app-brand-500), var(--app-brand-700))" }}>
            <Zap size={16} color="white" />
          </div>
          <span style={{ fontSize: 16, fontWeight: 700, color: "var(--app-text-strong)" }}>Atomy-Q</span>
        </div>

        <div style={{ width: "100%", maxWidth: 380 }}>
          {step === "credentials" ? (
            <>
              <div className="mb-8">
                <h2 style={{ fontSize: 22, fontWeight: 700, color: "var(--app-text-strong)", letterSpacing: "-0.02em", marginBottom: 6 }}>Sign in to your workspace</h2>
                <p style={{ fontSize: 14, color: "var(--app-text-subtle)" }}>Enter your credentials to continue</p>
              </div>

              {error && (
                <div className="flex items-start gap-3 rounded-lg px-4 py-3 mb-6" style={{ background: "var(--app-danger-tint-8)", border: "1px solid var(--app-danger-tint-20)" }}>
                  <AlertCircle size={15} style={{ color: "var(--app-danger)", flexShrink: 0, marginTop: 1 }} />
                  <span style={{ fontSize: 13, color: "var(--app-danger-faint)" }}>{error}</span>
                </div>
              )}

              <form onSubmit={handleSignIn} className="space-y-4">
                <div>
                  <label style={{ fontSize: 12, fontWeight: 500, color: "var(--app-text-muted)", letterSpacing: "0.02em", display: "block", marginBottom: 6 }}>EMAIL ADDRESS</label>
                  <input
                    type="email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    placeholder="you@company.com"
                    required
                    style={{ width: "100%", height: 42, background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", borderRadius: 8, padding: "0 14px", fontSize: 14, color: "var(--app-text-main)", outline: "none", boxSizing: "border-box" }}
                    onFocus={(e) => { e.currentTarget.style.borderColor = "var(--app-brand-500)"; }}
                    onBlur={(e) => { e.currentTarget.style.borderColor = "var(--app-border-strong)"; }}
                  />
                </div>

                <div>
                  <div className="flex justify-between items-center mb-1.5">
                    <label style={{ fontSize: 12, fontWeight: 500, color: "var(--app-text-muted)", letterSpacing: "0.02em" }}>PASSWORD</label>
                    <button type="button" style={{ fontSize: 12, color: "var(--app-brand-500)" }} className="hover:opacity-80 transition-opacity">
                      Forgot password?
                    </button>
                  </div>
                  <div className="relative">
                    <input
                      type={showPassword ? "text" : "password"}
                      value={password}
                      onChange={(e) => setPassword(e.target.value)}
                      placeholder="Enter your password"
                      required
                      style={{ width: "100%", height: 42, background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", borderRadius: 8, padding: "0 40px 0 14px", fontSize: 14, color: "var(--app-text-main)", outline: "none", boxSizing: "border-box" }}
                      onFocus={(e) => { e.currentTarget.style.borderColor = "var(--app-brand-500)"; }}
                      onBlur={(e) => { e.currentTarget.style.borderColor = "var(--app-border-strong)"; }}
                    />
                    <button
                      type="button"
                      onClick={() => setShowPassword(!showPassword)}
                      className="absolute right-3 top-1/2 -translate-y-1/2"
                      style={{ color: "var(--app-text-subtle)" }}
                    >
                      {showPassword ? <EyeOff size={15} /> : <Eye size={15} />}
                    </button>
                  </div>
                </div>

                <button
                  type="submit"
                  disabled={loading}
                  className="w-full flex items-center justify-center gap-2 rounded-lg transition-opacity"
                  style={{ height: 42, background: loading ? "var(--app-brand-700)" : "var(--app-brand-600)", color: "white", fontSize: 14, fontWeight: 500, opacity: loading ? 0.7 : 1 }}
                >
                  {loading ? (
                    <div className="w-4 h-4 rounded-full border-2 border-white/30 border-t-white animate-spin" />
                  ) : (
                    <>Sign In <ArrowRight size={15} /></>
                  )}
                </button>

                <div className="flex items-center gap-3 my-2">
                  <div className="flex-1 h-px" style={{ background: "var(--app-border-strong)" }} />
                  <span style={{ fontSize: 12, color: "var(--app-text-faint)" }}>or continue with</span>
                  <div className="flex-1 h-px" style={{ background: "var(--app-border-strong)" }} />
                </div>

                <button
                  type="button"
                  className="w-full flex items-center justify-center gap-2.5 rounded-lg transition-colors"
                  style={{ height: 42, background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", color: "var(--app-text-subtle)", fontSize: 14 }}
                  onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-text-faint)"; (e.currentTarget as HTMLButtonElement).style.color = "var(--app-text-main)"; }}
                  onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-border-strong)"; (e.currentTarget as HTMLButtonElement).style.color = "var(--app-text-subtle)"; }}
                >
                  <svg width="16" height="16" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                  Sign in with Google SSO
                </button>
              </form>
            </>
          ) : (
            <>
              <div className="mb-8">
                <div className="flex items-center justify-center w-12 h-12 rounded-xl mb-5" style={{ background: "var(--app-brand-tint-10)", border: "1px solid var(--app-brand-tint-20)" }}>
                  <Lock size={20} style={{ color: "var(--app-brand-500)" }} />
                </div>
                <h2 style={{ fontSize: 22, fontWeight: 700, color: "var(--app-text-strong)", letterSpacing: "-0.02em", marginBottom: 6 }}>Two-factor authentication</h2>
                <p style={{ fontSize: 14, color: "var(--app-text-subtle)" }}>Enter the 6-digit code from your authenticator app or enter any 6-digit code to proceed.</p>
              </div>

              {error && (
                <div className="flex items-start gap-3 rounded-lg px-4 py-3 mb-6" style={{ background: "var(--app-danger-tint-8)", border: "1px solid var(--app-danger-tint-20)" }}>
                  <AlertCircle size={15} style={{ color: "var(--app-danger)", flexShrink: 0, marginTop: 1 }} />
                  <span style={{ fontSize: 13, color: "var(--app-danger-faint)" }}>{error}</span>
                </div>
              )}

              <form onSubmit={handleMFA} className="space-y-4">
                <div>
                  <label style={{ fontSize: 12, fontWeight: 500, color: "var(--app-text-muted)", letterSpacing: "0.02em", display: "block", marginBottom: 6 }}>VERIFICATION CODE</label>
                  <input
                    type="text"
                    value={mfaCode}
                    onChange={(e) => setMfaCode(e.target.value.replace(/\D/g, "").slice(0, 6))}
                    placeholder="000000"
                    maxLength={6}
                    style={{ width: "100%", height: 48, background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", borderRadius: 8, padding: "0 14px", fontSize: 24, color: "var(--app-text-main)", outline: "none", boxSizing: "border-box", letterSpacing: "0.3em", textAlign: "center", fontFamily: "'JetBrains Mono', monospace" }}
                    onFocus={(e) => { e.currentTarget.style.borderColor = "var(--app-brand-500)"; }}
                    onBlur={(e) => { e.currentTarget.style.borderColor = "var(--app-border-strong)"; }}
                  />
                </div>

                <button
                  type="submit"
                  disabled={loading || mfaCode.length < 6}
                  className="w-full flex items-center justify-center gap-2 rounded-lg transition-opacity"
                  style={{ height: 42, background: "var(--app-brand-600)", color: "white", fontSize: 14, fontWeight: 500, opacity: (loading || mfaCode.length < 6) ? 0.5 : 1 }}
                >
                  {loading ? <div className="w-4 h-4 rounded-full border-2 border-white/30 border-t-white animate-spin" /> : "Verify & Continue"}
                </button>
                <button type="button" onClick={() => setStep("credentials")} style={{ fontSize: 13, color: "var(--app-text-subtle)", width: "100%", textAlign: "center", display: "block" }} className="hover:text-slate-400 transition-colors">
                  ← Back to sign in
                </button>
              </form>
            </>
          )}

          {/* Security notice */}
          <div className="mt-8 flex items-center gap-2 rounded-lg px-3 py-2.5" style={{ background: "var(--app-surface-glass)", border: "1px solid var(--app-border-strong)" }}>
            <Shield size={13} style={{ color: "var(--app-text-faint)", flexShrink: 0 }} />
            <span style={{ fontSize: 11, color: "var(--app-text-faint)", lineHeight: 1.5 }}>
              Access restricted to authorized personnel. All sessions are logged and monitored in compliance with your organization's security policy.
            </span>
          </div>
        </div>
      </div>
    </div>
  );
}
