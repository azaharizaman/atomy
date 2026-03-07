import * as React from 'react';
import { cn } from '@/lib/utils';
import { ChevronRight } from 'lucide-react';

export interface SplitFeature {
  text: string;
}

export interface SplitStat {
  value: string;
  label: string;
}

export interface AtomyQSplitLayoutAltProps {
  brandName?: string;
  brandLogo?: React.ReactNode;
  tagline?: string;
  headline?: string;
  description?: string;
  features?: SplitFeature[];
  stats?: SplitStat[];
  children: React.ReactNode;
  className?: string;
}

function AtomyQSplitLayoutAlt({
  brandName = 'AtomyQ',
  brandLogo,
  tagline,
  headline,
  description,
  features = [],
  stats = [],
  children,
  className,
}: AtomyQSplitLayoutAltProps) {
  return (
    <div className={cn('flex min-h-screen', className)}>
      {/* Left branded panel */}
      <div
        className="relative hidden w-[480px] shrink-0 flex-col justify-between overflow-hidden bg-[var(--aq-nav-bg)] p-10 lg:flex"
      >
        {/* Grid overlay */}
        <div
          className="pointer-events-none absolute inset-0 opacity-[0.04]"
          style={{
            backgroundImage: 'linear-gradient(var(--aq-text-inverse) 1px, transparent 1px), linear-gradient(90deg, var(--aq-text-inverse) 1px, transparent 1px)',
            backgroundSize: '32px 32px',
          }}
        />
        {/* Glow */}
        <div
          className="pointer-events-none absolute -left-[100px] -top-[100px] size-[400px] rounded-full"
          style={{ background: 'radial-gradient(circle, var(--aq-brand-tint-8) 0%, transparent 70%)' }}
        />

        <div className="relative z-10 space-y-8">
          <div className="flex items-center gap-3">
            {brandLogo ?? (
              <div
                className="flex size-10 items-center justify-center rounded-xl text-sm font-bold text-white"
                style={{ background: 'linear-gradient(135deg, var(--aq-brand-500), var(--aq-brand-700))' }}
              >
                {brandName.slice(0, 2).toUpperCase()}
              </div>
            )}
            <span className="text-lg font-semibold text-[var(--aq-text-inverse)]">{brandName}</span>
          </div>

          {tagline && (
            <p className="text-[10px] font-semibold uppercase tracking-[0.12em] text-[var(--aq-brand-400)]">
              {tagline}
            </p>
          )}
          {headline && (
            <h1 className="text-[28px] font-bold leading-tight text-[var(--aq-text-inverse)]" style={{ letterSpacing: '-0.02em' }}>
              {headline}
            </h1>
          )}
          {description && (
            <p className="max-w-sm text-sm leading-relaxed text-[var(--aq-text-subtle)]">{description}</p>
          )}

          {features.length > 0 && (
            <ul className="space-y-3">
              {features.map((f, i) => (
                <li key={i} className="flex items-center gap-3 text-sm text-[var(--aq-nav-text)]">
                  <span className="flex size-[18px] items-center justify-center rounded bg-[var(--aq-nav-hover)]">
                    <ChevronRight className="size-3 text-[var(--aq-brand-400)]" />
                  </span>
                  {f.text}
                </li>
              ))}
            </ul>
          )}
        </div>

        {stats.length > 0 && (
          <div className="relative z-10 flex gap-8">
            {stats.map((s, i) => (
              <div key={i}>
                <div className="text-lg font-bold text-[var(--aq-brand-400)]">{s.value}</div>
                <div className="text-[11px] text-[var(--aq-text-subtle)]">{s.label}</div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Right content */}
      <div className="flex flex-1 items-center justify-center bg-[var(--aq-bg-canvas)] px-8">
        <div className="w-full max-w-[380px]">{children}</div>
      </div>
    </div>
  );
}

export { AtomyQSplitLayoutAlt };
