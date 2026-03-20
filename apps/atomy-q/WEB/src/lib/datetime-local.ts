/**
 * Bridge RFC 3339 / Atom timestamps from the API to <input type="datetime-local"> (local, no TZ suffix).
 */

export function isoOrNullToDatetimeLocal(iso: string | null | undefined): string {
  if (iso == null || String(iso).trim() === '') return '';
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return '';
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

/** Empty input clears the value on the server (null). */
export function datetimeLocalToIsoOrNull(value: string): string | null {
  const v = value.trim();
  if (v === '') return null;
  const d = new Date(v);
  return Number.isFinite(d.getTime()) ? d.toISOString() : null;
}

export function formatScheduleInstant(iso: string | null | undefined): string {
  if (iso == null || String(iso).trim() === '') return '—';
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return '—';
  return d.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}
