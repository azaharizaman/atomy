'use client';

import React from 'react';
import { AlertTriangle } from 'lucide-react';

import {
  buildRfqScheduleLayout,
  positionOnScheduleRail,
  type RfqScheduleContext,
} from '@/lib/rfq-schedule-milestones';

export type RfqScheduleTimelineProps = RfqScheduleContext;

function formatMediumDate(ms: number): string {
  return new Date(ms).toLocaleDateString(undefined, { dateStyle: 'medium' });
}

export function RfqScheduleTimeline(props: RfqScheduleTimelineProps) {
  const layout = buildRfqScheduleLayout(props);

  if (layout === null) {
    return (
      <p className="text-sm text-slate-500 px-4 pb-4 pt-0">
        No schedule dates yet. Add submission or closing dates, or planning milestones (reviews, expected award) on RFQ
        details when available.
      </p>
    );
  }

  const todayPct = positionOnScheduleRail(layout.todayMs, layout);

  return (
    <div className="px-4 pb-4 pt-0 space-y-4">
      <p className="text-xs text-slate-500">
        Milestones on the axis are positioned by date relative to today. A milestone is marked{' '}
        <span className="text-red-600 font-medium">late</span> when its date has passed and related work (status, quotes,
        comparison, or approvals) is still incomplete—not from the calendar alone.
      </p>

      <div className="overflow-x-auto -mx-1 px-1" role="region" aria-label="RFQ schedule timeline">
        <div className="relative min-w-[520px] h-24 pt-2" aria-hidden="true">
          <div className="absolute left-0 right-0 top-[3.25rem] h-1 bg-slate-200 rounded-full" />

          <div
            className="absolute top-0 flex flex-col items-center z-20 pointer-events-none"
            style={{ left: `${todayPct}%`, transform: 'translateX(-50%)' }}
          >
            <span className="text-[10px] font-semibold uppercase tracking-wide text-amber-800">Today</span>
            <div className="w-px h-11 bg-amber-500 rounded-full" />
          </div>

          {layout.milestones.map((m) => {
            const pct = positionOnScheduleRail(m.ms, layout);
            return (
              <div
                key={m.id}
                className="absolute top-[3rem] z-10 flex flex-col items-center"
                style={{ left: `${pct}%`, transform: 'translateX(-50%)' }}
              >
                <div
                  className={[
                    'w-3 h-3 rounded-full border-2 shrink-0 bg-white shadow-sm',
                    m.late ? 'border-red-500 bg-red-50' : 'border-indigo-500 bg-indigo-50',
                  ].join(' ')}
                />
              </div>
            );
          })}
        </div>
      </div>

      <ul className="list-none m-0 p-0 space-y-2 border-t border-slate-100 pt-3">
        {layout.milestones.map((m) => (
          <li key={m.id} className="flex flex-wrap items-baseline justify-between gap-2 text-sm">
            <span className="text-slate-700">
              <span className="font-medium">{m.label}</span>
              <span className="text-slate-500"> — {formatMediumDate(m.ms)}</span>
            </span>
            {m.late ? (
              <span className="inline-flex items-center gap-1 text-xs font-medium text-red-600">
                <AlertTriangle size={12} aria-hidden />
                <span>{m.lateNote ?? 'Overdue'}</span>
              </span>
            ) : (
              <span className="text-xs text-slate-400">On track</span>
            )}
          </li>
        ))}
      </ul>
    </div>
  );
}
