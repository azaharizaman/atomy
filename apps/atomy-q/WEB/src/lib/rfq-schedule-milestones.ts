/**
 * Pure helpers for RFQ overview schedule rail: milestone dates, overdue rules, axis range.
 */

export interface RfqScheduleContext {
  status: string;
  submission_deadline?: string | null;
  closing_date?: string | null;
  expected_award_at?: string | null;
  technical_review_due_at?: string | null;
  financial_review_due_at?: string | null;
  needs_review_count?: number;
  /** null = no comparison run yet */
  comparison_is_preview?: boolean | null;
  approval_overall?: string;
}

export interface ScheduleMilestone {
  id: string;
  label: string;
  ms: number;
  late: boolean;
  /** Short text for screen readers when late */
  lateNote: string | null;
}

export interface ScheduleLayout {
  minMs: number;
  maxMs: number;
  milestones: ScheduleMilestone[];
  todayMs: number;
}

export function parseScheduleInstant(iso: string | null | undefined): number | null {
  if (iso == null) return null;
  const s = String(iso).trim();
  if (s === '') return null;
  const t = Date.parse(s);
  return Number.isFinite(t) ? t : null;
}

function normStatus(status: string): string {
  return String(status || '')
    .trim()
    .toLowerCase();
}

function isPastDue(ms: number, nowMs: number): boolean {
  return nowMs > ms;
}

function parseOptionalCount(value: unknown): number | undefined {
  if (value === null || value === undefined) return undefined;
  if (typeof value === 'string' && value.trim() === '') return undefined;
  const n = Number(value);
  if (!Number.isFinite(n)) return undefined;
  if (n <= 0) return undefined;
  return n;
}

function normalizeApprovalOverall(value: unknown): string | undefined {
  if (value === null || value === undefined) return undefined;
  const s = String(value).trim().toLowerCase();
  return s === '' ? undefined : s;
}

function submissionDeadlineLate(statusNorm: string, nowMs: number, deadlineMs: number): boolean {
  if (!isPastDue(deadlineMs, nowMs)) return false;
  return ['draft', 'published', 'active', 'pending'].includes(statusNorm);
}

function closingDateLate(statusNorm: string, nowMs: number, closingMs: number): boolean {
  if (!isPastDue(closingMs, nowMs)) return false;
  return !['closed', 'awarded', 'cancelled', 'archived'].includes(statusNorm);
}

function technicalReviewLate(nowMs: number, dueMs: number, needsReview: number | undefined, comparisonPreview: boolean | null): boolean {
  if (!isPastDue(dueMs, nowMs)) return false;
  if (needsReview !== undefined && needsReview > 0) return true;
  if (comparisonPreview === true) return true;
  return false;
}

function financialReviewLate(statusNorm: string, nowMs: number, dueMs: number, approvalOverall: string | undefined): boolean {
  if (!isPastDue(dueMs, nowMs)) return false;
  if (['awarded', 'cancelled', 'archived'].includes(statusNorm)) return false;
  return (approvalOverall ?? 'none') !== 'approved';
}

function expectedAwardLate(statusNorm: string, nowMs: number, awardMs: number): boolean {
  if (!isPastDue(awardMs, nowMs)) return false;
  return !['awarded', 'cancelled', 'archived'].includes(statusNorm);
}

const DAY_MS = 86400000;

function padRange(minMs: number, maxMs: number): { minMs: number; maxMs: number } {
  if (maxMs - minMs < DAY_MS) {
    const mid = (minMs + maxMs) / 2;
    return { minMs: mid - DAY_MS / 2, maxMs: mid + DAY_MS / 2 };
  }
  return { minMs, maxMs };
}

/**
 * Collect dated milestones with overdue flags. Returns null when there is nothing to plot (no milestone dates).
 */
export function buildRfqScheduleLayout(ctx: RfqScheduleContext, nowMs: number = Date.now()): ScheduleLayout | null {
  const statusNorm = normStatus(ctx.status);
  const needsReview = parseOptionalCount(ctx.needs_review_count);
  const approvalOverall = normalizeApprovalOverall(ctx.approval_overall);
  const comparisonPreview = ctx.comparison_is_preview ?? null;

  const milestones: ScheduleMilestone[] = [];

  const push = (
    id: string,
    label: string,
    iso: string | null | undefined,
    late: boolean,
    lateNote: string | null,
    parsedMs?: number | null,
  ): void => {
    const ms = parsedMs ?? parseScheduleInstant(iso);
    if (ms === null) return;
    milestones.push({ id, label, ms, late, lateNote: late ? lateNote : null });
  };

  const sd = parseScheduleInstant(ctx.submission_deadline);
  if (sd !== null) {
    const late = submissionDeadlineLate(statusNorm, nowMs, sd);
    push(
      'submission_deadline',
      'Submission deadline',
      ctx.submission_deadline,
      late,
      late ? 'Submission deadline has passed while the RFQ is still open.' : null,
      sd,
    );
  }

  const cd = parseScheduleInstant(ctx.closing_date);
  if (cd !== null) {
    const late = closingDateLate(statusNorm, nowMs, cd);
    push(
      'closing_date',
      'Closing date',
      ctx.closing_date,
      late,
      late ? 'Closing date has passed before the RFQ reached a closed state.' : null,
      cd,
    );
  }

  const tr = parseScheduleInstant(ctx.technical_review_due_at);
  if (tr !== null) {
    const late = technicalReviewLate(nowMs, tr, needsReview, comparisonPreview);
    push(
      'technical_review_due_at',
      'Technical review due',
      ctx.technical_review_due_at,
      late,
      late ? 'Technical review due date has passed with review work still outstanding.' : null,
      tr,
    );
  }

  const fr = parseScheduleInstant(ctx.financial_review_due_at);
  if (fr !== null) {
    const late = financialReviewLate(statusNorm, nowMs, fr, approvalOverall);
    push(
      'financial_review_due_at',
      'Financial review due',
      ctx.financial_review_due_at,
      late,
      late ? 'Financial review due date has passed before approvals completed.' : null,
      fr,
    );
  }

  const ea = parseScheduleInstant(ctx.expected_award_at);
  if (ea !== null) {
    const late = expectedAwardLate(statusNorm, nowMs, ea);
    push(
      'expected_award_at',
      'Expected award',
      ctx.expected_award_at,
      late,
      late ? 'Expected award date has passed and the RFQ is not yet awarded.' : null,
      ea,
    );
  }

  if (milestones.length === 0) {
    return null;
  }

  const milestoneMs = milestones.map((m) => m.ms);
  let minMs = Math.min(...milestoneMs, nowMs);
  let maxMs = Math.max(...milestoneMs, nowMs);
  ({ minMs, maxMs } = padRange(minMs, maxMs));

  milestones.sort((a, b) => a.ms - b.ms);

  return {
    minMs,
    maxMs,
    milestones,
    todayMs: nowMs,
  };
}

export function positionOnScheduleRail(ms: number, layout: ScheduleLayout): number {
  const span = layout.maxMs - layout.minMs;
  if (span <= 0) return 50;
  const t = ((ms - layout.minMs) / span) * 100;
  return Math.min(100, Math.max(0, t));
}
