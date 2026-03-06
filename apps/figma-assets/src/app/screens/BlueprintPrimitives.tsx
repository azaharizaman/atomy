import type { ReactNode } from "react";
import { X } from "lucide-react";

type Tone = "brand" | "success" | "warning" | "danger" | "neutral";

const toneStyles: Record<Tone, { bg: string; color: string; border: string }> = {
  brand: {
    bg: "var(--app-brand-tint-10)",
    color: "var(--app-brand-500)",
    border: "var(--app-brand-tint-20)",
  },
  success: {
    bg: "var(--app-success-tint-10)",
    color: "var(--app-success)",
    border: "var(--app-success-tint-20)",
  },
  warning: {
    bg: "var(--app-warning-tint-10)",
    color: "var(--app-warning)",
    border: "var(--app-warning-tint-20)",
  },
  danger: {
    bg: "var(--app-danger-tint-10)",
    color: "var(--app-danger)",
    border: "var(--app-danger-tint-20)",
  },
  neutral: {
    bg: "var(--app-slate-tint-10)",
    color: "var(--app-text-muted)",
    border: "var(--app-border-strong)",
  },
};

export function Page({
  title,
  subtitle,
  actions,
  children,
}: {
  title: string;
  subtitle: string;
  actions?: ReactNode;
  children: ReactNode;
}) {
  return (
    <div style={{ padding: 24, minHeight: "100%", background: "var(--app-bg-canvas)" }}>
      <div className="flex items-start justify-between mb-6 gap-4">
        <div>
          <h1 style={{ fontSize: 20, fontWeight: 700, color: "var(--app-text-strong)", letterSpacing: "-0.01em", marginBottom: 4 }}>{title}</h1>
          <p style={{ fontSize: 13, color: "var(--app-text-muted)", maxWidth: 880 }}>{subtitle}</p>
        </div>
        {actions && <div className="flex items-center gap-2">{actions}</div>}
      </div>
      {children}
    </div>
  );
}

export function Panel({
  title,
  subtitle,
  right,
  children,
}: {
  title: string;
  subtitle?: string;
  right?: ReactNode;
  children: ReactNode;
}) {
  return (
    <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
      <div className="flex items-start justify-between gap-3 mb-3">
        <div>
          <h3 style={{ fontSize: 13, fontWeight: 700, color: "var(--app-text-strong)" }}>{title}</h3>
          {subtitle && <p style={{ fontSize: 11, color: "var(--app-text-muted)", marginTop: 2 }}>{subtitle}</p>}
        </div>
        {right}
      </div>
      {children}
    </div>
  );
}

export function Stat({
  label,
  value,
  tone = "neutral",
}: {
  label: string;
  value: string;
  tone?: Tone;
}) {
  const style = toneStyles[tone];
  return (
    <div className="rounded-lg border p-3" style={{ background: style.bg, borderColor: style.border }}>
      <div style={{ fontSize: 11, color: "var(--app-text-muted)", marginBottom: 4 }}>{label}</div>
      <div style={{ fontSize: 20, fontWeight: 800, color: style.color, letterSpacing: "-0.02em" }}>{value}</div>
    </div>
  );
}

export function Badge({
  label,
  tone = "neutral",
}: {
  label: string;
  tone?: Tone;
}) {
  const style = toneStyles[tone];
  return (
    <span
      className="rounded-full px-2 py-1"
      style={{
        fontSize: 10,
        fontWeight: 700,
        background: style.bg,
        color: style.color,
        border: `1px solid ${style.border}`,
        textTransform: "uppercase",
        letterSpacing: "0.05em",
      }}
    >
      {label}
    </span>
  );
}

export function GhostButton({ label }: { label: string }) {
  return (
    <button
      className="rounded-lg px-3 py-2 transition-colors"
      style={{
        fontSize: 13,
        color: "var(--app-text-subtle)",
        background: "var(--app-bg-surface)",
        border: "1px solid var(--app-border-strong)",
      }}
      onMouseEnter={(e) => {
        (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-text-faint)";
      }}
      onMouseLeave={(e) => {
        (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-border-strong)";
      }}
    >
      {label}
    </button>
  );
}

export function PrimaryButton({ label }: { label: string }) {
  return (
    <button
      className="rounded-lg px-3 py-2 transition-opacity hover:opacity-90"
      style={{ fontSize: 13, fontWeight: 600, color: "white", background: "var(--app-brand-600)" }}
    >
      {label}
    </button>
  );
}

export function SlideOver({
  open,
  title,
  subtitle,
  onClose,
  children,
}: {
  open: boolean;
  title: string;
  subtitle: string;
  onClose: () => void;
  children: ReactNode;
}) {
  if (!open) return null;

  return (
    <>
      <button
        onClick={onClose}
        style={{ position: "fixed", inset: 0, background: "var(--app-overlay)", zIndex: 60, border: "none" }}
        aria-label="Close overlay"
      />
      <aside
        style={{
          position: "fixed",
          top: 0,
          right: 0,
          width: "34vw",
          minWidth: 420,
          maxWidth: 620,
          height: "100vh",
          zIndex: 70,
          background: "var(--app-bg-surface)",
          borderLeft: "1px solid var(--app-border-strong)",
          boxShadow: "-16px 0 40px rgba(2, 6, 23, 0.2)",
          display: "flex",
          flexDirection: "column",
        }}
      >
        <div className="px-5 py-4 border-b flex items-start justify-between gap-3" style={{ borderColor: "var(--app-border-strong)" }}>
          <div>
            <h2 style={{ fontSize: 16, fontWeight: 700, color: "var(--app-text-strong)" }}>{title}</h2>
            <p style={{ fontSize: 12, color: "var(--app-text-muted)", marginTop: 2 }}>{subtitle}</p>
          </div>
          <button
            onClick={onClose}
            className="rounded p-1.5 transition-colors"
            style={{ color: "var(--app-text-muted)", border: "1px solid var(--app-border-strong)" }}
          >
            <X size={14} />
          </button>
        </div>
        <div style={{ padding: 20, overflow: "auto", flex: 1 }}>{children}</div>
      </aside>
    </>
  );
}
