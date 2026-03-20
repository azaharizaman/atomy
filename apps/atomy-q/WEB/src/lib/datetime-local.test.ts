import { describe, expect, it } from 'vitest';

import {
  datetimeLocalToIsoOrNull,
  defaultDatetimeLocalDaysFromNow,
  isoOrNullToDatetimeLocal,
} from './datetime-local';

describe('datetime-local bridge', () => {
  it('returns empty string for null/empty iso', () => {
    expect(isoOrNullToDatetimeLocal(null)).toBe('');
    expect(isoOrNullToDatetimeLocal('')).toBe('');
  });

  it('returns null for empty datetime-local value', () => {
    expect(datetimeLocalToIsoOrNull('')).toBeNull();
    expect(datetimeLocalToIsoOrNull('   ')).toBeNull();
  });

  // The intermediate datetime-local value depends on local timezone and may shift date boundaries.
  // We assert epoch milliseconds after round-trip to validate the same instant regardless of locale.
  it('defaultDatetimeLocalDaysFromNow returns a datetime-local shape roughly N days ahead', () => {
    const local = defaultDatetimeLocalDaysFromNow(14);
    expect(local).toMatch(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/);
    const iso = datetimeLocalToIsoOrNull(local);
    expect(iso).not.toBeNull();
    const deltaMs = new Date(iso!).getTime() - Date.now();
    expect(deltaMs).toBeGreaterThan(13 * 86400000);
    expect(deltaMs).toBeLessThan(15 * 86400000);
  });

  it('round-trips a fixed instant (UTC string → local input → ISO)', () => {
    const iso = '2026-06-15T14:30:00.000Z';
    const local = isoOrNullToDatetimeLocal(iso);
    expect(local).toMatch(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/);
    const back = datetimeLocalToIsoOrNull(local);
    expect(back).not.toBeNull();
    expect(new Date(back!).getTime()).toBe(new Date(iso).getTime());
  });
});
