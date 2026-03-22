import React from 'react';
import { AlertCircle, AlertTriangle, Calendar, CheckCircle2, ChevronRight } from 'lucide-react';
import { AvatarLabel } from './Avatar';

/** Composite project health — aligns with RFQ mission-control semantics (nominal / attention / blocked). */
export type MissionHealth = 'nominal' | 'attention' | 'blocked';

export interface ProjectVitalMetric {
  label: string;
  value: React.ReactNode;
}

export interface ProjectVitalsPanelProps {
  /** Display name; truncated with ellipsis when long. */
  projectName: string;
  /** Optional short code (e.g. PRJ-2044). */
  projectCode?: string;
  missionHealth: MissionHealth;
  /** First matching reason shown under the headline (accessibility + clarity). */
  healthReason?: string;
  /** Compact KPI rows (keep to a handful for ~200px density). */
  metrics: ProjectVitalMetric[];
  ownerName: string;
  ownerSubtitle?: string;
  ownerAvatarSrc?: string;
  milestoneLabel?: string;
  milestoneDate?: string;
  viewProjectLabel?: string;
  onViewProject?: () => void;
  className?: string;
}

const HEALTH_UI: Record<
  MissionHealth,
  { label: string; icon: React.ReactNode; wrap: string; text: string }
> = {
  nominal: {
    label: 'All clear',
    icon: <CheckCircle2 size={14} className="shrink-0 mt-0.5" aria-hidden />,
    wrap: 'bg-green-50 border border-green-100',
    text: 'text-green-800',
  },
  attention: {
    label: 'Needs attention',
    icon: <AlertTriangle size={14} className="shrink-0 mt-0.5" aria-hidden />,
    wrap: 'bg-amber-50 border border-amber-100',
    text: 'text-amber-900',
  },
  blocked: {
    label: 'Blocked',
    icon: <AlertCircle size={14} className="shrink-0 mt-0.5" aria-hidden />,
    wrap: 'bg-red-50 border border-red-100',
    text: 'text-red-800',
  },
};

/**
 * Dense **project vitals** strip for a ~200px sidebar: identity, single composite health,
 * a few KPI rows, owner, next milestone, and one exit action.
 */
export function ProjectVitalsPanel({
  projectName,
  projectCode,
  missionHealth,
  healthReason,
  metrics,
  ownerName,
  ownerSubtitle,
  ownerAvatarSrc,
  milestoneLabel,
  milestoneDate,
  viewProjectLabel = 'View project',
  onViewProject,
  className = '',
}: ProjectVitalsPanelProps) {
  const health = HEALTH_UI[missionHealth];

  return (
    <aside
      className={[
        'w-[200px] shrink-0 flex flex-col gap-3 rounded-lg border border-slate-200 bg-white p-3 shadow-sm',
        className,
      ].join(' ')}
      aria-label="Project summary"
    >
      <header className="min-w-0">
        {projectCode && (
          <p className="text-[11px] font-mono text-slate-400 truncate">{projectCode}</p>
        )}
        <h2 className="text-sm font-semibold text-slate-900 truncate" title={projectName}>
          {projectName}
        </h2>
      </header>

      <div
        className={['rounded-md px-2 py-1.5', health.wrap].join(' ')}
        role="status"
        aria-live="polite"
      >
        <div className={['flex gap-2 text-xs font-semibold leading-snug', health.text].join(' ')}>
          {health.icon}
          <span className="min-w-0">{health.label}</span>
        </div>
        {healthReason && (
          <p className="mt-1 text-[11px] font-normal text-slate-600 leading-snug">{healthReason}</p>
        )}
      </div>

      {metrics.length > 0 && (
        <ul className="flex flex-col gap-1.5 border-t border-slate-100 pt-3">
          {metrics.map((m, i) => (
            <li key={`${m.label}-${i}`} className="flex items-start justify-between gap-2 min-w-0">
              <span className="text-[11px] font-medium text-slate-500 shrink-0">{m.label}</span>
              <span className="text-xs font-semibold text-slate-800 text-right truncate min-w-0">
                {m.value}
              </span>
            </li>
          ))}
        </ul>
      )}

      <div className="border-t border-slate-100 pt-3">
        <p className="text-[10px] font-semibold uppercase tracking-wide text-slate-400 mb-1.5">
          Owner
        </p>
        <AvatarLabel src={ownerAvatarSrc} name={ownerName} subtitle={ownerSubtitle} size="sm" />
      </div>

      {(milestoneLabel ?? milestoneDate) && (
        <div className="flex items-start gap-2 rounded-md bg-slate-50 px-2 py-1.5 border border-slate-100">
          <Calendar size={13} className="text-slate-400 shrink-0 mt-0.5" aria-hidden />
          <div className="min-w-0">
            {milestoneLabel && (
              <p className="text-[11px] font-medium text-slate-600 leading-tight">{milestoneLabel}</p>
            )}
            {milestoneDate && (
              <p className="text-xs text-slate-800 font-semibold mt-0.5">{milestoneDate}</p>
            )}
          </div>
        </div>
      )}

      {onViewProject && (
        <button
          type="button"
          onClick={onViewProject}
          className="mt-auto inline-flex w-full items-center justify-between rounded-md px-2 py-1.5 text-xs font-medium text-indigo-600 transition-colors hover:bg-slate-50 hover:text-indigo-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-1"
        >
          {viewProjectLabel}
          <ChevronRight size={14} className="shrink-0 text-indigo-500" aria-hidden />
        </button>
      )}
    </aside>
  );
}
