/**
 * Pure helpers for HorizontalProcessTrack — segment coloring and today-cursor placement.
 */

export type ProcessStepHealth = 'default' | 'issue' | 'blocked';
export type ProcessStepProgress = 'upcoming' | 'current' | 'complete';

export interface ProcessTrackStepLike {
  progress: ProcessStepProgress;
  health?: ProcessStepHealth;
  date?: string;
}

export type SegmentTone = 'slate' | 'complete' | 'issue' | 'blocked';

/** Segment after step at `prevIndex`, connecting toward step `prevIndex + 1`. */
export function segmentToneAfterStep(prev: ProcessTrackStepLike, next: ProcessTrackStepLike): SegmentTone {
  const nh = next.health ?? 'default';
  if (nh === 'blocked') {
    return 'blocked';
  }
  if (nh === 'issue') {
    return 'issue';
  }
  if (next.progress === 'complete') {
    return 'complete';
  }
  if (prev.progress === 'complete') {
    return 'complete';
  }
  return 'slate';
}

export interface DateAnchor {
  stepIndex: number;
  timeMs: number;
}

export function collectDateAnchors(steps: ProcessTrackStepLike[]): DateAnchor[] {
  const out: DateAnchor[] = [];
  for (let i = 0; i < steps.length; i++) {
    const raw = steps[i].date;
    if (raw === undefined || raw === '') {
      continue;
    }
    const timeMs = Date.parse(raw);
    if (!Number.isFinite(timeMs)) {
      continue;
    }
    out.push({ stepIndex: i, timeMs });
  }
  return out;
}

export interface TodayCursorResult {
  /** 0–100, relative to full track width (node-center to node-center space). */
  leftPercent: number;
  /** ISO date string used for a11y / tooltip. */
  isoDate: string;
}

/**
 * Map "today" onto the rail between step centers using time interpolation between
 * the two bounding anchors in time order. Requires at least two valid step dates.
 */
export function computeTodayCursor(
  steps: ProcessTrackStepLike[],
  now: Date,
  pinToEnds: boolean,
): TodayCursorResult | null {
  const n = steps.length;
  if (n < 2) {
    return null;
  }

  const anchors = collectDateAnchors(steps);
  if (anchors.length < 2) {
    return null;
  }

  const byTime = [...anchors].sort((a, b) => a.timeMs - b.timeMs);
  const nowMs = now.getTime();
  const minT = byTime[0]!.timeMs;
  const maxT = byTime[byTime.length - 1]!.timeMs;

  const centerPct = (stepIndex: number): number => ((stepIndex + 0.5) / n) * 100;

  if (nowMs < minT) {
    if (!pinToEnds) {
      return null;
    }
    const idx = byTime[0]!.stepIndex;
    return { leftPercent: centerPct(idx), isoDate: now.toISOString().slice(0, 10) };
  }
  if (nowMs > maxT) {
    if (!pinToEnds) {
      return null;
    }
    const idx = byTime[byTime.length - 1]!.stepIndex;
    return { leftPercent: centerPct(idx), isoDate: now.toISOString().slice(0, 10) };
  }

  for (let i = 0; i < byTime.length - 1; i++) {
    const a = byTime[i]!;
    const b = byTime[i + 1]!;
    if (nowMs >= a.timeMs && nowMs <= b.timeMs) {
      const span = b.timeMs - a.timeMs;
      const frac = span === 0 ? 0 : (nowMs - a.timeMs) / span;
      const left = centerPct(a.stepIndex) + frac * (centerPct(b.stepIndex) - centerPct(a.stepIndex));
      return {
        leftPercent: Math.min(100, Math.max(0, left)),
        isoDate: now.toISOString().slice(0, 10),
      };
    }
  }

  return null;
}
