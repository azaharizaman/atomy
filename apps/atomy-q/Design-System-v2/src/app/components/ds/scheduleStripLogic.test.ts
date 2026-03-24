import { describe, expect, it } from 'vitest';
import {
  effectiveSpanEnd,
  maxLaneIndex,
  placeScheduleItems,
  positionPercentInRange,
  widthPercentSpan,
} from './scheduleStripLogic';

describe('scheduleStripLogic', () => {
  const rangeStart = new Date('2026-03-11T00:00:00');
  const rangeEnd = new Date('2026-03-18T23:59:59');

  it('positionPercentInRange is within 0–100', () => {
    const mid = new Date('2026-03-15T12:00:00');
    const p = positionPercentInRange(mid, rangeStart, rangeEnd);
    expect(p).toBeGreaterThan(0);
    expect(p).toBeLessThan(100);
  });

  it('positionPercentInRange clamps before range to 0', () => {
    expect(positionPercentInRange(new Date('2026-03-01'), rangeStart, rangeEnd)).toBe(0);
  });

  it('widthPercentSpan matches span over range', () => {
    const a = new Date('2026-03-12T00:00:00');
    const b = new Date('2026-03-14T00:00:00');
    const w = widthPercentSpan(a, b, rangeStart, rangeEnd);
    expect(w).toBeGreaterThan(0);
    expect(w).toBeLessThanOrEqual(100);
  });

  it('effectiveSpanEnd extends when end missing', () => {
    const start = new Date('2026-03-15T10:00:00');
    const end = effectiveSpanEnd(start, undefined, rangeStart, rangeEnd);
    expect(end.getTime()).toBeGreaterThan(start.getTime());
  });

  it('placeScheduleItems assigns non-overlapping lanes', () => {
    const placed = placeScheduleItems(
      [
        { id: 'a', label: 'A', start: new Date('2026-03-12T10:00:00') },
        { id: 'b', label: 'B', start: new Date('2026-03-12T11:00:00') },
      ],
      rangeStart,
      rangeEnd,
    );
    expect(placed).toHaveLength(2);
    expect(maxLaneIndex(placed)).toBeGreaterThanOrEqual(1);
  });
});
