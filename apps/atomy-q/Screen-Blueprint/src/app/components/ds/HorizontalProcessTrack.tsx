import React, { useMemo } from 'react';
import { AlertTriangle, Check, X } from 'lucide-react';
import {
  computeTodayCursor,
  segmentToneAfterStep,
  type ProcessStepHealth,
  type ProcessStepProgress,
  type SegmentTone,
} from './horizontalProcessTrackLogic';

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

const SEGMENT_BG: Record<SegmentTone, Record<'success' | 'accent', string>> = {
  slate: { success: 'bg-slate-200', accent: 'bg-slate-200' },
  complete: { success: 'bg-green-500', accent: 'bg-indigo-500' },
  issue: { success: 'bg-amber-500', accent: 'bg-amber-500' },
  blocked: { success: 'bg-red-500', accent: 'bg-red-500' },
};

function nodeContent(
  step: HorizontalProcessTrackStep,
  index: number,
  compact: boolean,
  completeAppearance: 'success' | 'accent',
): { className: string; inner: React.ReactNode } {
  const h = step.health ?? 'default';
  const iconSm = compact ? 'h-3 w-3' : 'h-3.5 w-3.5';
  const numCls = compact ? 'text-[10px] font-semibold' : 'text-xs font-semibold';

  if (h === 'blocked') {
    return {
      className: 'border-2 border-red-600 bg-red-500 text-white shadow-sm',
      inner: <X className={iconSm} strokeWidth={2.5} aria-hidden />,
    };
  }
  if (h === 'issue') {
    return {
      className: 'border-2 border-amber-600 bg-amber-500 text-white shadow-sm',
      inner: <AlertTriangle className={iconSm} aria-hidden />,
    };
  }

  if (step.progress === 'complete') {
    const fill =
      completeAppearance === 'accent'
        ? 'border-2 border-indigo-700 bg-indigo-600 text-white'
        : 'border-2 border-green-700 bg-green-600 text-white';
    return {
      className: fill,
      inner: <Check className={iconSm} strokeWidth={2.5} aria-hidden />,
    };
  }

  if (step.progress === 'current') {
    return {
      className:
        'border-2 border-indigo-600 bg-white text-indigo-600 shadow-sm',
      inner: <span className={numCls}>{index + 1}</span>,
    };
  }

  return {
    className: 'border border-slate-300 bg-slate-100 text-slate-500',
    inner: <span className={numCls}>{index + 1}</span>,
  };
}

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

  const nodeSize = compact ? 'h-7 w-7' : 'h-9 w-9';
  const titleCls = compact
    ? 'text-xs font-medium text-slate-800 truncate text-center w-full'
    : 'text-sm font-medium text-slate-800 text-center w-full';
  const descCls = 'text-xs text-slate-500 text-center w-full line-clamp-2 mt-0.5';

  const ariaToday =
    cursor &&
    `Today marker on ${cursor.isoDate}, about ${Math.round(cursor.leftPercent)} percent along the schedule between dated milestones.`;

  return (
    <div className={['w-full min-w-0', className].join(' ')}>
      <div className="overflow-x-auto overflow-y-visible pb-1 snap-x snap-mandatory">
        <div
          className={[
            'relative min-w-[min(100%,520px)] px-1',
            cursor ? (compact ? 'pt-7' : 'pt-8') : '',
          ].join(' ')}
        >
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
                <div className="min-h-0 w-px flex-1 bg-red-500 shadow-[0_0_0_1px_rgba(255,255,255,0.5)]" />
              </div>
            </div>
          )}

          {/* Node + connector row */}
          <div className="relative z-10 flex w-full items-center">
            {steps.map((step, idx) => {
              const { className: nodeRing, inner } = nodeContent(step, idx, compact, completeAppearance);
              const showLeftSeg = idx > 0;
              const prev = steps[idx - 1]!;
              const tone = showLeftSeg
                ? segmentToneAfterStep(prev, step)
                : null;
              const segKey = completeAppearance;
              const segBg = tone ? SEGMENT_BG[tone][segKey] : '';

              return (
                <React.Fragment key={step.id}>
                  {showLeftSeg && (
                    <div
                      className={['h-0.5 min-w-[8px] flex-1 shrink', segBg].join(' ')}
                      aria-hidden
                    />
                  )}
                  <div
                    className={[
                      'flex shrink-0 flex-col items-center snap-center',
                      compact ? 'w-[4.5rem]' : 'w-[7.5rem]',
                    ].join(' ')}
                  >
                    <div
                      className={[
                        'flex items-center justify-center rounded-full',
                        nodeSize,
                        nodeRing,
                      ].join(' ')}
                      aria-current={step.progress === 'current' ? 'step' : undefined}
                    >
                      {inner}
                    </div>
                  </div>
                </React.Fragment>
              );
            })}
          </div>

          {/* Labels */}
          <div className="relative z-10 mt-2 flex w-full items-start">
            {steps.map((step, idx) => (
              <React.Fragment key={`lbl-${step.id}`}>
                {idx > 0 && <div className={compact ? 'min-w-[8px] flex-1' : 'min-w-[8px] flex-1'} />}
                <div
                  className={[
                    'flex shrink-0 flex-col items-center px-0.5',
                    compact ? 'w-[4.5rem]' : 'w-[7.5rem]',
                  ].join(' ')}
                >
                  <p className={titleCls}>{step.label}</p>
                  {variant === 'detailed' && step.description && (
                    <p className={descCls}>{step.description}</p>
                  )}
                </div>
              </React.Fragment>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
