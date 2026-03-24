/**
 * Pure helpers for ScheduleStrip layout (date math only; no React).
 */

/** Minimum span width as % of range when `end` is omitted (visibility). */
export const SCHEDULE_MIN_WIDTH_PCT = 8;

export function positionPercentInRange(instant: Date, rangeStart: Date, rangeEnd: Date): number {
  const rs = rangeStart.getTime();
  const re = rangeEnd.getTime();
  const span = re - rs;
  if (span <= 0 || !Number.isFinite(span)) {
    return 0;
  }
  const t = instant.getTime() - rs;
  const pct = (t / span) * 100;
  return Math.max(0, Math.min(100, pct));
}

export function widthPercentSpan(
  spanStart: Date,
  spanEnd: Date,
  rangeStart: Date,
  rangeEnd: Date,
): number {
  const rs = rangeStart.getTime();
  const re = rangeEnd.getTime();
  const rspan = re - rs;
  if (rspan <= 0 || !Number.isFinite(rspan)) {
    return 0;
  }
  const w = spanEnd.getTime() - spanStart.getTime();
  const pct = (w / rspan) * 100;
  return Math.max(0, Math.min(100, pct));
}

/**
 * Effective end for an item: use `end` when after `start`; otherwise extend by
 * a fraction of the visible range so the pill stays readable (never negative width).
 */
export function effectiveSpanEnd(
  start: Date,
  end: Date | undefined,
  rangeStart: Date,
  rangeEnd: Date,
  minWidthPct: number = SCHEDULE_MIN_WIDTH_PCT,
): Date {
  if (end !== undefined && end.getTime() > start.getTime()) {
    return end;
  }
  const rs = rangeStart.getTime();
  const re = rangeEnd.getTime();
  const rspan = Math.max(0, re - rs);
  const minMs = Math.max(60 * 60 * 1000, (rspan * minWidthPct) / 100);
  return new Date(start.getTime() + minMs);
}

export interface ScheduleStripItemInput {
  id: string;
  label: string;
  start: Date;
  end?: Date;
  lane?: number;
  emphasis?: 'default' | 'today';
}

export type PlacedScheduleItem = ScheduleStripItemInput & { lane: number; spanEnd: Date };

/**
 * Assigns lanes (greedy, non-overlapping) when `lane` is omitted; respects explicit `lane` when set.
 */
export function placeScheduleItems(
  items: ScheduleStripItemInput[],
  rangeStart: Date,
  rangeEnd: Date,
): PlacedScheduleItem[] {
  const sorted = [...items].sort((a, b) => a.start.getTime() - b.start.getTime());
  const laneEnds: number[] = [];
  const out: PlacedScheduleItem[] = [];

  for (const it of sorted) {
    const spanEnd = effectiveSpanEnd(it.start, it.end, rangeStart, rangeEnd);
    let lane: number;

    if (it.lane !== undefined) {
      lane = it.lane;
      while (laneEnds.length <= lane) {
        laneEnds.push(0);
      }
      laneEnds[lane] = Math.max(laneEnds[lane], spanEnd.getTime());
    } else {
      lane = 0;
      while (lane < laneEnds.length && laneEnds[lane] > it.start.getTime()) {
        lane++;
      }
      if (lane === laneEnds.length) {
        laneEnds.push(spanEnd.getTime());
      } else {
        laneEnds[lane] = spanEnd.getTime();
      }
    }

    out.push({ ...it, lane, spanEnd });
  }

  return out;
}

export function maxLaneIndex(placed: PlacedScheduleItem[]): number {
  if (placed.length === 0) {
    return 0;
  }
  return Math.max(...placed.map(p => p.lane));
}
