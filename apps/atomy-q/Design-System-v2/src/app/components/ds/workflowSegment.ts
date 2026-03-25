/**
 * Shared workflow / task status segments for dashboard widgets.
 * Colors map to DS semantic families only (see task-dashboard spec).
 */

export type WorkflowSemanticTone = 'neutral' | 'warning' | 'accent' | 'info' | 'success';

export interface WorkflowSegment {
  id: string;
  label: string;
  /** Share 0–100; widths normalize if segments do not sum to 100. */
  pct: number;
  count?: number;
  tone: WorkflowSemanticTone;
}

/** Stacked bar and spark column fills */
export const WORKFLOW_TONE_BAR: Record<WorkflowSemanticTone, string> = {
  neutral: 'bg-slate-400',
  warning: 'bg-amber-500',
  accent: 'bg-indigo-600',
  info: 'bg-blue-500',
  success: 'bg-green-600',
};

/** Legend / swatch (same chroma as bar) */
export const WORKFLOW_TONE_SWATCH: Record<WorkflowSemanticTone, string> = WORKFLOW_TONE_BAR;

/** Mini tile surfaces for StatusBreakdownGrid */
export const WORKFLOW_TONE_TILE: Record<WorkflowSemanticTone, string> = {
  neutral: 'bg-slate-50 border-slate-200',
  warning: 'bg-amber-50 border-amber-200',
  accent: 'bg-indigo-50 border-indigo-200',
  info: 'bg-blue-50 border-blue-200',
  success: 'bg-green-50 border-green-200',
};

/** Pill text + border for segment label chips */
export const WORKFLOW_TONE_PILL: Record<WorkflowSemanticTone, string> = {
  neutral: 'bg-slate-100 text-slate-700 border-slate-200',
  warning: 'bg-amber-100 text-amber-800 border-amber-200',
  accent: 'bg-indigo-100 text-indigo-800 border-indigo-200',
  info: 'bg-blue-100 text-blue-800 border-blue-200',
  success: 'bg-green-100 text-green-800 border-green-200',
};

export function clampPct(value: number): number {
  if (!Number.isFinite(value)) {
    return 0;
  }
  return Math.max(0, Math.min(100, value));
}

/** Normalize segment weights to cumulative 0–100 strip widths. */
export function normalizeSegmentWidths(segments: WorkflowSegment[]): number[] {
  const raw = segments.map(s => Math.max(0, s.pct));
  const sum = raw.reduce((a, b) => a + b, 0);
  if (sum <= 0) {
    return segments.map(() => 0);
  }
  return raw.map(p => (p / sum) * 100);
}
