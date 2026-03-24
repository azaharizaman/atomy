import React from 'react';
import { eachDayOfInterval, endOfDay, format, isSameDay, isWithinInterval, startOfDay } from 'date-fns';
import {
  maxLaneIndex,
  placeScheduleItems,
  positionPercentInRange,
  type PlacedScheduleItem,
  type ScheduleStripItemInput,
  widthPercentSpan,
} from './scheduleStripLogic';

export type { ScheduleStripItemInput };

const LANE_ROW_PX = 36;
const PILL_H = 28;

export interface ScheduleStripProps {
  title?: string;
  range: { start: Date; end: Date };
  /** Defaults to `new Date()` when omitted. */
  today?: Date;
  items: ScheduleStripItemInput[];
  /** Header actions (e.g. overflow menu). */
  actions?: React.ReactNode;
  className?: string;
}

export function ScheduleStrip({
  title = 'Schedule',
  range,
  today = new Date(),
  items,
  actions,
  className = '',
}: ScheduleStripProps) {
  const rangeStart = startOfDay(range.start);
  const rangeEnd = endOfDay(range.end);
  const days = React.useMemo(
    () =>
      eachDayOfInterval({
        start: rangeStart,
        end: startOfDay(range.end),
      }),
    [rangeStart, range.end],
  );

  const placed = React.useMemo(
    () => placeScheduleItems(items, rangeStart, rangeEnd),
    [items, rangeStart, rangeEnd],
  );

  const lanes = maxLaneIndex(placed) + 1;
  const bodyHeight = Math.max(LANE_ROW_PX, lanes * LANE_ROW_PX + 8);

  const todayInRange = isWithinInterval(startOfDay(today), {
    start: rangeStart,
    end: rangeEnd,
  });
  const todayLinePct = todayInRange
    ? positionPercentInRange(startOfDay(today), rangeStart, rangeEnd)
    : null;

  const summary = React.useMemo(
    () =>
      `Schedule from ${format(rangeStart, 'MMM d')} to ${format(rangeEnd, 'MMM d')}, ${items.length} task${
        items.length === 1 ? '' : 's'
      }`,
    [rangeStart, rangeEnd, items.length],
  );

  return (
    <div className={['rounded-lg border border-slate-200 bg-white shadow-[0_1px_3px_0_rgba(0,0,0,0.06)]', className].join(' ')}>
      <div className="flex items-center justify-between px-4 py-3 border-b border-slate-100">
        <h3 className="text-sm font-semibold text-slate-800">{title}</h3>
        {actions && <div className="flex items-center gap-1">{actions}</div>}
      </div>
      <div className="p-4 space-y-3">
        <div className="flex border-b border-slate-200 pb-2">
          {days.map(day => {
            const isToday = isSameDay(day, today);
            return (
              <div
                key={day.toISOString()}
                className={['flex-1 text-center text-[11px] font-medium min-w-0', isToday ? 'text-indigo-700' : 'text-slate-500'].join(
                  ' ',
                )}
              >
                {isToday ? (
                  <span className="inline-flex rounded-full bg-indigo-600 text-white px-2 py-0.5 text-[10px]">
                    {format(day, 'd MMM')}
                  </span>
                ) : (
                  <span className="tabular-nums">{format(day, 'd MMM')}</span>
                )}
              </div>
            );
          })}
        </div>

        <div
          className="relative rounded-md border border-slate-100 bg-slate-50/50"
          role="region"
          aria-label={summary}
        >
          {todayLinePct !== null && (
            <div
              className="absolute top-0 bottom-0 w-px bg-indigo-500/50 z-10 pointer-events-none"
              style={{ left: `${todayLinePct}%` }}
              aria-hidden
            />
          )}
          <div className="relative" style={{ minHeight: bodyHeight }}>
            {placed.map(item => {
              const left = positionPercentInRange(item.start, rangeStart, rangeEnd);
              const w = widthPercentSpan(item.start, item.spanEnd, rangeStart, rangeEnd);
              const top = item.lane * LANE_ROW_PX + 4;
              const highlight = item.emphasis === 'today' || isSameDay(item.start, today);

              return (
                <SchedulePill
                  key={item.id}
                  item={item}
                  leftPct={left}
                  widthPct={w}
                  top={top}
                  highlight={highlight}
                />
              );
            })}
          </div>
        </div>
      </div>
    </div>
  );
}

function SchedulePill({
  item,
  leftPct,
  widthPct,
  top,
  highlight,
}: {
  item: PlacedScheduleItem;
  leftPct: number;
  widthPct: number;
  top: number;
  highlight: boolean;
}) {
  return (
    <div
      className="absolute flex items-center px-1"
      style={{
        left: `${leftPct}%`,
        width: `${widthPct}%`,
        top,
        minWidth: '3rem',
        height: PILL_H,
      }}
    >
      <div
        className={[
          'flex w-full min-w-0 items-center rounded-md border px-2 py-1 text-[11px] font-medium shadow-sm',
          highlight
            ? 'border-indigo-700 bg-indigo-600 text-white'
            : 'border-slate-200 bg-white text-slate-800',
        ].join(' ')}
        title={item.label}
      >
        <span className="truncate">{item.label}</span>
      </div>
    </div>
  );
}
