'use client';

import React from 'react';

export interface TimelineEvent {
  id: string;
  timestamp: string;
  actor?: string;
  action: string;
  icon?: React.ReactNode;
  iconColor?: 'indigo' | 'green' | 'amber' | 'red' | 'slate';
  meta?: string;
}

interface TimelineProps {
  events: TimelineEvent[];
  compact?: boolean;
  className?: string;
}

const DOT_COLORS: Record<NonNullable<TimelineEvent['iconColor']>, string> = {
  indigo: 'bg-indigo-100 text-indigo-600 border border-indigo-200',
  green: 'bg-green-100 text-green-600 border border-green-200',
  amber: 'bg-amber-100 text-amber-600 border border-amber-200',
  red: 'bg-red-100 text-red-600 border border-red-200',
  slate: 'bg-slate-100 text-slate-500 border border-slate-200',
};

export function Timeline({ events, compact = false, className = '' }: TimelineProps) {
  return (
    <div className={['flex flex-col', className].join(' ')}>
      {events.map((event, i) => {
        const isLast = i === events.length - 1;
        const dotColor = DOT_COLORS[event.iconColor ?? 'slate'];

        return (
          <div key={event.id} className="flex gap-3">
            <div className="flex flex-col items-center shrink-0">
              <div
                className={[
                  'flex items-center justify-center rounded-full shrink-0 z-10',
                  compact ? 'w-6 h-6' : 'w-7 h-7',
                  dotColor,
                ].join(' ')}
              >
                {event.icon != null ? (
                  <span className={compact ? 'text-[11px]' : 'text-xs'}>{event.icon}</span>
                ) : (
                  <span className="w-1.5 h-1.5 rounded-full bg-current opacity-60" aria-hidden />
                )}
              </div>
              {!isLast && <div className="w-px flex-1 bg-slate-200 my-1" />}
            </div>

            <div className={['flex-1 min-w-0', isLast ? 'pb-0' : compact ? 'pb-3' : 'pb-4'].join(' ')}>
              <div className="flex items-start justify-between gap-2">
                <div className="flex-1 min-w-0">
                  {event.actor != null ? (
                    <p className={['text-slate-700', compact ? 'text-xs' : 'text-sm'].join(' ')}>
                      <span className="font-medium">{event.actor}</span> {event.action}
                    </p>
                  ) : (
                    <p className={['text-slate-600', compact ? 'text-xs' : 'text-sm'].join(' ')}>
                      {event.action}
                    </p>
                  )}
                  {event.meta != null && (
                    <p className="text-xs text-slate-400 mt-0.5 truncate">{event.meta}</p>
                  )}
                </div>
                <span className="text-[11px] text-slate-400 whitespace-nowrap shrink-0 mt-0.5">
                  {event.timestamp}
                </span>
              </div>
            </div>
          </div>
        );
      })}
    </div>
  );
}
