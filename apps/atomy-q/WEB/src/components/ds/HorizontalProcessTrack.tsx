'use client';

import React, { useMemo } from 'react';
import { AlertTriangle, Check, X } from 'lucide-react';
import {
  computeTodayCursor,
  segmentToneAfterStep,
  type ProcessStepHealth,
  type ProcessStepProgress,
  type SegmentTone,
} from '@/components/ds/horizontalProcessTrackLogic';
import {
  TIMELINE_DOT_PALETTE,
  timelineIconTextClass,
  timelineNodeSizeClass,
} from '@/components/ds/Timeline';

export type { ProcessStepHealth, ProcessStepProgress };

export interface HorizontalProcessTrackStep {
  id: string;
  label: string;
  description?: string;
  progress: ProcessStepProgress;
  health?: ProcessStepHealth;
  /** ISO-8601 date string; used when `showTodayCursor` is true (caller supplies consistent calendar semantics). */
  date?: string;
}

export interface HorizontalProcessTrackProps {
  steps: HorizontalProcessTrackStep[];
  variant?: 'compact' | 'detailed';
  completeAppearance?: 'success' | 'accent';
  showTodayCursor?: boolean;
  /** Defaults to `new Date()` when `showTodayCursor` is true. */
  today?: Date;
  pinTodayCursorToEnds?: boolean;
  className?: string;
}

/** Horizontal connector: same 1px weight as vertical timeline (`w-px bg-slate-200`). */
const SEGMENT_BG: Record<SegmentTone, Record<'success' | 'accent', string>> = {
  slate: { success: 'bg-slate-200', accent: 'bg-slate-200' },
  complete: { success: 'bg-green-600', accent: 'bg-indigo-600' },
  issue: { success: 'bg-amber-500', accent: 'bg-amber-500' },
  blocked: { success: 'bg-red-500', accent: 'bg-red-500' },
};

function nodeContent(
  step: HorizontalProcessTrackStep,
  index: number,
  compact: boolean,
  completeAppearance: 'success' | 'accent',
): { paletteClass: string; inner: React.ReactNode } {
  const h = step.health ?? 'default';
  const numCls = [timelineIconTextClass(compact), 'font-semibold tabular-nums leading-none'].join(' ');
  const iconDim = compact ? 'h-3 w-3 shrink-0' : 'h-3.5 w-3.5 shrink-0';

  if (h === 'blocked') {
    return {
      paletteClass: TIMELINE_DOT_PALETTE.red,
      inner: <X className={iconDim} strokeWidth={2} aria-hidden />,
    };
  }
  if (h === 'issue') {
    return {
      paletteClass: TIMELINE_DOT_PALETTE.amber,
      inner: <AlertTriangle className={iconDim} strokeWidth={2} aria-hidden />,
    };
  }

  if (step.progress === 'complete') {
    const key = completeAppearance === 'accent' ? 'indigo' : 'green';
    return {
      paletteClass: TIMELINE_DOT_PALETTE[key],
      inner: <Check className={iconDim} strokeWidth={2} aria-hidden />,
    };
  }

  if (step.progress === 'current') {
    return {
      paletteClass: TIMELINE_DOT_PALETTE.indigo,
      inner: <span className={numCls}>{index + 1}</span>,
    };
  }

  return {
    paletteClass: TIMELINE_DOT_PALETTE.slate,
    inner: <span className={numCls}>{index + 1}</span>,
  };
}

/**
 * Horizontal milestone rail: equal-width columns (flex) so all stops fit the container without horizontal scroll.
 */
export function HorizontalProcessTrack({
  steps,
  variant = 'detailed',
  completeAppearance = 'success',
  showTodayCursor = false,
  today: todayProp,
  pinTodayCursorToEnds = false,
  className = '',
}: HorizontalProcessTrackProps) {
  const compact = variant === 'compact';
  const today = useMemo(
    () => (showTodayCursor ? (todayProp ?? new Date()) : null),
    [showTodayCursor, todayProp],
  );

  const cursor = useMemo(() => {
    if (!showTodayCursor || !today || steps.length < 2) {
      return null;
    }
    return computeTodayCursor(steps, today, pinTodayCursorToEnds);
  }, [showTodayCursor, today, steps, pinTodayCursorToEnds]);

  const nodeSize = timelineNodeSizeClass(compact);
  const titleCls = compact
    ? 'text-[11px] font-medium text-slate-800 text-center w-full min-w-0 leading-tight line-clamp-2'
    : 'text-xs font-medium text-slate-800 text-center w-full min-w-0 leading-tight line-clamp-2';
  const descCls =
    'text-[10px] sm:text-[11px] text-slate-500 text-center w-full min-w-0 line-clamp-2 mt-0.5';

  const ariaToday =
    cursor &&
    `Today marker on ${cursor.isoDate}, about ${Math.round(cursor.leftPercent)} percent along the schedule between dated milestones.`;

  const n = steps.length;

  return (
    <div className={['w-full min-w-0', className].join(' ')}>
      <div className="pb-1">
        <div className={['relative w-full', cursor ? (compact ? 'pt-7' : 'pt-8') : ''].join(' ')}>
          {cursor && (
            <div className="pointer-events-none absolute inset-x-0 top-0 bottom-0 z-20">
              <div
                className="absolute bottom-1 flex w-0 flex-col items-center"
                style={{
                  left: `${cursor.leftPercent}%`,
                  top: compact ? '2px' : '4px',
                  transform: 'translateX(-50%)',
                }}
                role="img"
                aria-label={ariaToday}
              >
                <span className="mb-1 shrink-0 rounded bg-red-600 px-1 py-0.5 text-[10px] font-medium text-white shadow-sm">
                  Today
                </span>
                <div className="min-h-0 w-px flex-1 bg-red-600" />
              </div>
            </div>
          )}

          <div className="relative z-10 flex w-full min-w-0">
            {steps.map((step, idx) => {
              const { paletteClass, inner } = nodeContent(step, idx, compact, completeAppearance);
              const segKey = completeAppearance;

              const leftTone = idx > 0 ? segmentToneAfterStep(steps[idx - 1]!, step) : null;
              const rightTone = idx < n - 1 ? segmentToneAfterStep(step, steps[idx + 1]!) : null;

              const leftBg = leftTone ? SEGMENT_BG[leftTone][segKey] : '';
              const rightBg = rightTone ? SEGMENT_BG[rightTone][segKey] : '';

              return (
                <div key={step.id} className="flex min-w-0 flex-1 flex-col items-stretch">
                  <div className="flex w-full min-w-0 items-center">
                    {idx === 0 ? (
                      <div className="min-h-px min-w-0 flex-1" aria-hidden />
                    ) : (
                      <div
                        className={['h-px min-h-px min-w-0 flex-1 self-center', leftBg].join(' ')}
                        aria-hidden
                      />
                    )}
                    <div
                      className={[
                        'z-10 flex shrink-0 items-center justify-center rounded-full',
                        nodeSize,
                        paletteClass,
                      ].join(' ')}
                      aria-current={step.progress === 'current' ? 'step' : undefined}
                      aria-label={`${step.description ?? step.label} ${step.label}`}
                    >
                      {inner}
                    </div>
                    {idx === n - 1 ? (
                      <div className="min-h-px min-w-0 flex-1" aria-hidden />
                    ) : (
                      <div
                        className={['h-px min-h-px min-w-0 flex-1 self-center', rightBg].join(' ')}
                        aria-hidden
                      />
                    )}
                  </div>

                  <div className="mt-2 flex flex-col items-center px-0.5 min-w-0">
                    <p className={titleCls}>{step.label}</p>
                    {variant === 'detailed' && step.description && (
                      <p className={descCls}>{step.description}</p>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      </div>
    </div>
  );
}
