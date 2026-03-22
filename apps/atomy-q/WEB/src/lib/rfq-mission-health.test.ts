import { describe, expect, it } from 'vitest';

import { computeMissionHealth } from './rfq-mission-health';

const base = {
  status: 'published',
  submission_deadline: new Date(Date.now() + 86400000 * 7).toISOString(),
  needs_review_count: 0,
  approvals: { overall: 'none' as const, pending_count: 0 },
  comparison: null as { is_preview: boolean } | null,
};

describe('computeMissionHealth', () => {
  it('returns nominal when nothing requires attention', () => {
    const r = computeMissionHealth({
      ...base,
      needs_review_count: 0,
      comparison: null,
    });
    expect(r.health).toBe('nominal');
    expect(r.reason).toBeNull();
  });

  it('returns blocked when explicit_blocked', () => {
    const r = computeMissionHealth({ ...base, explicit_blocked: true });
    expect(r.health).toBe('blocked');
    expect(r.reason).toContain('blocked');
  });

  it('returns attention when needs_review_count > 0', () => {
    const r = computeMissionHealth({
      ...base,
      needs_review_count: 2,
    });
    expect(r.health).toBe('attention');
    expect(r.reason?.toLowerCase()).toContain('review');
  });

  it('returns attention when approvals pending', () => {
    const r = computeMissionHealth({
      ...base,
      approvals: { overall: 'pending', pending_count: 1 },
    });
    expect(r.health).toBe('attention');
    expect(r.reason).toContain('Approvals');
  });

  it('returns attention when submission deadline passed and RFQ still open', () => {
    const r = computeMissionHealth(
      {
        ...base,
        status: 'published',
        submission_deadline: new Date(Date.now() - 86400000).toISOString(),
      },
      Date.now(),
    );
    expect(r.health).toBe('attention');
    expect(r.reason?.toLowerCase()).toContain('submission');
  });

  it('returns attention when comparison is preview', () => {
    const r = computeMissionHealth({
      ...base,
      comparison: { is_preview: true },
    });
    expect(r.health).toBe('attention');
    expect(r.reason?.toLowerCase()).toContain('preview');
  });

  it('prioritizes needs_review over preview when both true', () => {
    const r = computeMissionHealth({
      ...base,
      needs_review_count: 1,
      comparison: { is_preview: true },
    });
    expect(r.health).toBe('attention');
    expect(r.reason?.toLowerCase()).toContain('review');
  });
});
