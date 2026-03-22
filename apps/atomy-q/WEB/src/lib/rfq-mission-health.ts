/**
 * Composite RFQ workspace health for mission-control overview (`nominal` | `attention` | `blocked`).
 * @see docs/superpowers/specs/2026-03-21-rfq-workspace-mission-control-design.md
 */

import { isSubmissionDeadlineLateForMission } from '@/lib/rfq-schedule-milestones';

export type MissionHealth = 'nominal' | 'attention' | 'blocked';

export interface MissionHealthApprovalsSlice {
  overall: 'none' | 'pending' | 'approved' | 'rejected';
  pending_count: number;
}

export interface MissionHealthComparisonSlice {
  is_preview: boolean;
}

export interface MissionHealthInput {
  status: string;
  submission_deadline?: string | null;
  needs_review_count?: number;
  approvals: MissionHealthApprovalsSlice;
  comparison: MissionHealthComparisonSlice | null;
  /**
   * When true, primary path cannot proceed (e.g. future API flag). v1 usually unset.
   */
  explicit_blocked?: boolean;
}

export interface MissionHealthResult {
  health: MissionHealth;
  /** First matching rule; null when nominal. */
  reason: string | null;
}

/**
 * Precedence: `blocked` → `attention` → `nominal`.
 */
export function computeMissionHealth(input: MissionHealthInput, nowMs: number = Date.now()): MissionHealthResult {
  if (input.explicit_blocked === true) {
    return {
      health: 'blocked',
      reason: 'Workflow is blocked — resolve blocking issues before continuing.',
    };
  }

  const needsReview =
    input.needs_review_count !== undefined &&
    Number.isFinite(Number(input.needs_review_count)) &&
    Number(input.needs_review_count) > 0;
  if (needsReview) {
    return {
      health: 'attention',
      reason: 'One or more quotes need review before normalization can proceed.',
    };
  }

  if (input.approvals.overall === 'pending' && input.approvals.pending_count > 0) {
    return {
      health: 'attention',
      reason: `Approvals pending (${input.approvals.pending_count}).`,
    };
  }

  if (isSubmissionDeadlineLateForMission(input.status, input.submission_deadline, nowMs)) {
    return {
      health: 'attention',
      reason: 'Submission deadline has passed while the RFQ is still open.',
    };
  }

  if (input.comparison !== null && input.comparison.is_preview) {
    return {
      health: 'attention',
      reason: 'Latest comparison run is still a preview — finalize when ready.',
    };
  }

  return { health: 'nominal', reason: null };
}
