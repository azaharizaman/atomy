import React from 'react';
import { Info } from 'lucide-react';
import { Card } from './Card';
import {
  type WorkflowSegment,
  WORKFLOW_TONE_BAR,
  WORKFLOW_TONE_PILL,
  WORKFLOW_TONE_SWATCH,
  WORKFLOW_TONE_TILE,
  normalizeSegmentWidths,
} from './workflowSegment';

// ─── Status distribution (stacked or spark columns) ───────────────────────────

export type StatusDistributionVariant = 'stacked' | 'spark';

export interface StatusDistributionCardProps {
  title: string;
  segments: WorkflowSegment[];
  variant?: StatusDistributionVariant;
  /** Header actions (e.g. `DropdownMenu` trigger). */
  actions?: React.ReactNode;
  className?: string;
}

export function StatusDistributionCard({
  title,
  segments,
  variant = 'stacked',
  actions,
  className = '',
}: StatusDistributionCardProps) {
  const widths = normalizeSegmentWidths(segments);
  const maxPct =
    segments.length === 0 ? 1 : Math.max(...segments.map(s => Math.max(0, s.pct)), 1);

  const chartLabel = React.useMemo(() => {
    const n = segments.length;
    return `Status distribution, ${n} segment${n === 1 ? '' : 's'}`;
  }, [segments.length]);

  return (
    <Card padding="none" className={className}>
      <div className="flex items-center justify-between px-4 py-3 border-b border-slate-100">
        <h3 className="text-sm font-semibold text-slate-800">{title}</h3>
        {actions && <div className="flex items-center gap-1">{actions}</div>}
      </div>
      <div className="p-4 space-y-4">
        {variant === 'stacked' ? (
          <div
            className="h-3 w-full rounded-full overflow-hidden flex bg-slate-100"
            role="img"
            aria-label={chartLabel}
          >
            {segments.map((seg, i) => (
              <div
                key={seg.id}
                className={['h-full min-w-0', WORKFLOW_TONE_BAR[seg.tone]].join(' ')}
                style={{ width: `${widths[i]}%` }}
                title={`${seg.label}: ${seg.pct.toFixed(0)}%` + (seg.count !== undefined ? ` (${seg.count})` : '')}
              />
            ))}
          </div>
        ) : (
          <div
            className="flex items-end justify-between gap-1 h-24"
            role="img"
            aria-label={chartLabel}
          >
            {segments.map(seg => {
              const h = (Math.max(0, seg.pct) / maxPct) * 100;
              return (
                <div
                  key={seg.id}
                  className="flex-1 flex flex-col items-center justify-end gap-1 min-w-0 h-full"
                  title={`${seg.label}: ${seg.pct.toFixed(0)}%`}
                >
                  <div
                    className={['w-full max-w-[14px] rounded-full min-h-[4px]', WORKFLOW_TONE_BAR[seg.tone]].join(' ')}
                    style={{ height: `${Math.max(8, h)}%` }}
                  />
                </div>
              );
            })}
          </div>
        )}

        <div className="flex flex-wrap gap-x-4 gap-y-2">
          {segments.map((seg, i) => (
            <div key={seg.id} className="flex items-center gap-2 text-xs">
              <span className={['inline-block size-2.5 rounded-sm shrink-0', WORKFLOW_TONE_SWATCH[seg.tone]].join(' ')} />
              <span className="text-slate-500">{seg.label}</span>
              <span className="font-medium text-slate-800 tabular-nums">{seg.pct.toFixed(0)}%</span>
              {seg.count !== undefined && (
                <span className="text-slate-400">({seg.count})</span>
              )}
            </div>
          ))}
        </div>
      </div>
    </Card>
  );
}

// ─── Status breakdown grid (mini tiles) ───────────────────────────────────────

export interface StatusBreakdownGridProps {
  segments: WorkflowSegment[];
  /** Responsive: 2 columns on narrow, 4 on `lg` when `4`. */
  columns?: 2 | 4;
  onInfoClick?: (segmentId: string) => void;
  className?: string;
}

export function StatusBreakdownGrid({
  segments,
  columns = 4,
  onInfoClick,
  className = '',
}: StatusBreakdownGridProps) {
  const grid =
    columns === 4
      ? 'grid grid-cols-2 lg:grid-cols-4 gap-3'
      : 'grid grid-cols-2 gap-3';

  return (
    <div className={[grid, className].join(' ')}>
      {segments.map(seg => (
        <div
          key={seg.id}
          className={['rounded-lg border p-3 relative', WORKFLOW_TONE_TILE[seg.tone]].join(' ')}
        >
          {onInfoClick && (
            <button
              type="button"
              onClick={() => {
                onInfoClick(seg.id);
              }}
              className="absolute top-2 right-2 p-0.5 rounded-md text-slate-400 hover:text-slate-600 hover:bg-white/80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
              aria-label={`${seg.label} details`}
            >
              <Info size={14} strokeWidth={2} />
            </button>
          )}
          <span
            className={[
              'inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium',
              WORKFLOW_TONE_PILL[seg.tone],
            ].join(' ')}
          >
            {seg.label}
          </span>
          <div className="mt-3 flex items-baseline justify-between gap-2">
            <span className="text-2xl font-semibold text-slate-900 tabular-nums">
              {Math.round(seg.pct)}
              <span className="text-lg font-semibold">%</span>
            </span>
            <span className="text-sm text-slate-600">{seg.count ?? 0} tasks</span>
          </div>
        </div>
      ))}
    </div>
  );
}

export type { WorkflowSegment, WorkflowSemanticTone } from './workflowSegment';
