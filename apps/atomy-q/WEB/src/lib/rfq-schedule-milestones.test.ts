import { describe, expect, it } from 'vitest';

import { buildRfqScheduleLayout, parseScheduleInstant, positionOnScheduleRail } from './rfq-schedule-milestones';

describe('parseScheduleInstant', () => {
  it('returns null for empty', () => {
    expect(parseScheduleInstant(null)).toBeNull();
    expect(parseScheduleInstant('')).toBeNull();
    expect(parseScheduleInstant('   ')).toBeNull();
  });

  it('parses valid ISO', () => {
    const ms = parseScheduleInstant('2026-03-20T12:00:00Z');
    expect(ms).toBe(Date.parse('2026-03-20T12:00:00Z'));
  });
});

describe('buildRfqScheduleLayout', () => {
  const now = Date.parse('2026-06-15T12:00:00Z');

  it('returns null when no milestone dates', () => {
    expect(buildRfqScheduleLayout({ status: 'published' }, now)).toBeNull();
  });

  it('includes today in range and pads narrow spans', () => {
    const layout = buildRfqScheduleLayout(
      {
        status: 'published',
        submission_deadline: '2026-06-10T00:00:00Z',
      },
      now,
    );
    expect(layout).not.toBeNull();
    expect(layout!.todayMs).toBe(now);
    expect(layout!.minMs).toBeLessThanOrEqual(now);
    expect(layout!.maxMs).toBeGreaterThanOrEqual(now);
    expect(layout!.milestones).toHaveLength(1);
  });

  it('marks submission deadline late when still open', () => {
    const layout = buildRfqScheduleLayout(
      {
        status: 'published',
        submission_deadline: '2026-06-01T00:00:00Z',
      },
      now,
    );
    expect(layout?.milestones[0]?.late).toBe(true);
  });

  it('does not mark submission late when RFQ is closed', () => {
    const layout = buildRfqScheduleLayout(
      {
        status: 'closed',
        submission_deadline: '2026-06-01T00:00:00Z',
      },
      now,
    );
    expect(layout?.milestones[0]?.late).toBe(false);
  });

  it('marks technical review late when preview comparison remains', () => {
    const layout = buildRfqScheduleLayout(
      {
        status: 'published',
        technical_review_due_at: '2026-06-01T00:00:00Z',
        comparison_is_preview: true,
      },
      now,
    );
    const m = layout?.milestones.find((x) => x.id === 'technical_review_due_at');
    expect(m?.late).toBe(true);
  });

  it('marks expected award late when not awarded', () => {
    const layout = buildRfqScheduleLayout(
      {
        status: 'published',
        expected_award_at: '2026-06-01T00:00:00Z',
      },
      now,
    );
    const m = layout?.milestones.find((x) => x.id === 'expected_award_at');
    expect(m?.late).toBe(true);
  });
});

describe('positionOnScheduleRail', () => {
  it('maps endpoints', () => {
    const layout = {
      minMs: 0,
      maxMs: 100,
      milestones: [],
      todayMs: 50,
    };
    expect(positionOnScheduleRail(0, layout)).toBe(0);
    expect(positionOnScheduleRail(100, layout)).toBe(100);
    expect(positionOnScheduleRail(50, layout)).toBe(50);
  });
});
