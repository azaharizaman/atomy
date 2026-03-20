import React from 'react';
import { Avatar } from './Avatar';

// ─── Timeline Item ────────────────────────────────────────────────────────────

export interface TimelineEvent {
  id: string;
  timestamp: string;      // relative label e.g. "2 hours ago"
  actor?: string;         // person name
  action: string;         // description
  icon?: React.ReactNode; // custom icon for the dot
  iconColor?: 'indigo' | 'green' | 'amber' | 'red' | 'slate';
  meta?: string;          // extra line
}

interface TimelineProps {
  events: TimelineEvent[];
  compact?: boolean;
  className?: string;
}

/** Shared with `HorizontalProcessTrack` — keep node chrome aligned across vertical + horizontal timelines. */
export const TIMELINE_DOT_PALETTE = {
  indigo: 'bg-indigo-100 text-indigo-600 border border-indigo-200',
  green:  'bg-green-100 text-green-600 border border-green-200',
  amber:  'bg-amber-100 text-amber-600 border border-amber-200',
  red:    'bg-red-100 text-red-600 border border-red-200',
  slate:  'bg-slate-100 text-slate-500 border border-slate-200',
} as const;

export type TimelineDotPaletteKey = keyof typeof TIMELINE_DOT_PALETTE;

export function timelineNodeSizeClass(compact: boolean): string {
  return compact ? 'w-6 h-6' : 'w-7 h-7';
}

/** Icon / glyph size inside a timeline node (matches vertical `Timeline` spans). */
export function timelineIconTextClass(compact: boolean): string {
  return compact ? 'text-[11px]' : 'text-xs';
}

const DOT_COLORS = TIMELINE_DOT_PALETTE;

export function Timeline({ events, compact = false, className = '' }: TimelineProps) {
  return (
    <div className={['flex flex-col', className].join(' ')}>
      {events.map((event, i) => {
        const isLast = i === events.length - 1;
        const dotColor = DOT_COLORS[event.iconColor ?? 'slate'];

        return (
          <div key={event.id} className="flex gap-3">
            {/* Left: icon + connector */}
            <div className="flex flex-col items-center shrink-0">
              <div
                className={[
                  'flex items-center justify-center rounded-full shrink-0 z-10',
                  compact ? 'w-6 h-6' : 'w-7 h-7',
                  dotColor,
                ].join(' ')}
              >
                {event.icon ? (
                  <span className={compact ? 'text-[11px]' : 'text-xs'}>{event.icon}</span>
                ) : (
                  <span className="w-1.5 h-1.5 rounded-full bg-current opacity-60" />
                )}
              </div>
              {!isLast && (
                <div className="w-px flex-1 bg-slate-200 my-1" />
              )}
            </div>

            {/* Right: content */}
            <div className={['flex-1 min-w-0', isLast ? 'pb-0' : compact ? 'pb-3' : 'pb-4'].join(' ')}>
              <div className="flex items-start justify-between gap-2">
                <div className="flex-1 min-w-0">
                  {event.actor ? (
                    <p className={['text-slate-700', compact ? 'text-xs' : 'text-sm'].join(' ')}>
                      <span className="font-medium">{event.actor}</span>{' '}
                      <span className="text-slate-500">{event.action}</span>
                    </p>
                  ) : (
                    <p className={['text-slate-600', compact ? 'text-xs' : 'text-sm'].join(' ')}>
                      {event.action}
                    </p>
                  )}
                  {event.meta && (
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

// ─── Activity Feed (with actor avatars) ──────────────────────────────────────

interface ActivityFeedProps {
  events: TimelineEvent[];
  className?: string;
}

export function ActivityFeed({ events, className = '' }: ActivityFeedProps) {
  return (
    <div className={['flex flex-col divide-y divide-slate-100', className].join(' ')}>
      {events.map(event => (
        <div key={event.id} className="flex items-start gap-3 py-3 px-4">
          {/* Avatar or icon */}
          <div className="shrink-0 mt-0.5">
            {event.actor ? (
              <Avatar name={event.actor} size="sm" />
            ) : (
              <div className={['w-6 h-6 rounded-full flex items-center justify-center', DOT_COLORS[event.iconColor ?? 'slate']].join(' ')}>
                {event.icon ?? <span className="w-1.5 h-1.5 rounded-full bg-current opacity-60" />}
              </div>
            )}
          </div>

          {/* Content */}
          <div className="flex-1 min-w-0">
            <p className="text-xs text-slate-700">
              {event.actor && <span className="font-medium">{event.actor} </span>}
              <span className="text-slate-500">{event.action}</span>
            </p>
            {event.meta && <p className="text-[11px] text-slate-400 mt-0.5">{event.meta}</p>}
          </div>

          {/* Timestamp */}
          <span className="text-[11px] text-slate-400 whitespace-nowrap shrink-0">{event.timestamp}</span>
        </div>
      ))}
    </div>
  );
}
