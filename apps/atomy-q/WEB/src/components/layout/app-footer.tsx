'use client';

import React from 'react';
import { useAuthStore } from '@/store/use-auth-store';

/**
 * App-wide footer for Default and Workspace layouts (not shown on login/auth).
 * Matches Screen-Blueprint: version, environment/status, legal links, plus current user tenant ID.
 */
export function AppFooter({ className = '' }: { className?: string }) {
  const user = useAuthStore((s) => s.user);
  const tenantId = user?.tenantId ?? '—';

  return (
    <footer
      className={[
        'flex items-center justify-between px-5 h-9 bg-white border-t border-slate-200 shrink-0 text-[11px] text-slate-400',
        className,
      ].join(' ')}
    >
      <div className="flex items-center gap-3 flex-wrap min-w-0">
        <span>Atomy-Q</span>
        <span className="inline-flex items-center gap-1 bg-slate-100 text-slate-500 border border-slate-200 rounded px-1.5 py-0.5 text-[10px] font-mono">
          v1.0.0
        </span>
        <span className="inline-flex items-center gap-1">
          <span className="w-1.5 h-1.5 rounded-full bg-green-500 shrink-0" aria-hidden />
          All systems operational
        </span>
        <span className="text-slate-400" title="Current workspace tenant">
          Tenant: <span className="font-mono text-slate-600">{tenantId}</span>
        </span>
      </div>
      <div className="flex items-center gap-3 shrink-0">
        <a href="#" className="hover:text-slate-600">
          Privacy
        </a>
        <a href="#" className="hover:text-slate-600">
          Terms
        </a>
        <a href="#" className="hover:text-slate-600">
          Support
        </a>
      </div>
    </footer>
  );
}
