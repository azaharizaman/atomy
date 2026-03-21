'use client';

import React from 'react';

import { HorizontalProcessTrack, type HorizontalProcessTrackStep } from '@/components/ds/HorizontalProcessTrack';
import type { ProcessStepHealth, ProcessStepProgress } from '@/components/ds/horizontalProcessTrackLogic';
import {
  buildRfqScheduleLayout,
  type RfqScheduleContext,
  type ScheduleMilestone,
} from '@/lib/rfq-schedule-milestones';

export type RfqScheduleTimelineProps = RfqScheduleContext;

function formatDateTitle(ms: number): string {
  return new Date(ms).toLocaleDateString(undefined, { dateStyle: 'medium' });
}

/**
 * Map schedule milestones to process-track steps: **title = date**, **subtitle = event name**.
 */
function milestonesToTrackSteps(milestones: ScheduleMilestone[], nowMs: number): HorizontalProcessTrackStep[] {
  /** `buildRfqScheduleLayout` returns milestones sorted by date. */
  const firstFutureOrNowIdx = milestones.findIndex((m) => m.ms >= nowMs);
  const currentIdx = firstFutureOrNowIdx === -1 ? milestones.length - 1 : firstFutureOrNowIdx;

  return milestones.map((m, i) => {
    const iso = new Date(m.ms).toISOString();
    const title = formatDateTitle(m.ms);
    const description = m.label;

    let health: ProcessStepHealth | undefined;
    let progress: ProcessStepProgress;

    if (m.late) {
      health = 'issue';
      progress = 'upcoming';
    } else if (m.ms <= nowMs) {
      progress = 'complete';
    } else if (i === currentIdx) {
      progress = 'current';
    } else {
      progress = 'upcoming';
    }

    return {
      id: m.id,
      label: title,
      description,
      progress,
      health,
      date: iso,
    };
  });
}

export function RfqScheduleTimeline(props: RfqScheduleTimelineProps) {
  const nowMs = Date.now();
  const layout = buildRfqScheduleLayout(props, nowMs);

  if (layout === null) {
    return (
      <p className="text-sm text-slate-500 px-4 pb-4 pt-0">
        No schedule dates yet. Add submission or closing dates, or planning milestones (reviews, expected award) on RFQ
        details when available.
      </p>
    );
  }

  const steps = milestonesToTrackSteps(layout.milestones, nowMs);

  return (
    <div className="px-4 pb-4 pt-0 space-y-3">
      <p className="text-xs text-slate-500">
        Milestones use your planning dates. A stop is marked{' '}
        <span className="text-amber-600 font-medium">attention</span> when its date has passed and related work is still
        incomplete—not from the calendar alone.
      </p>

      <HorizontalProcessTrack
        steps={steps}
        variant="compact"
        completeAppearance="success"
        showTodayCursor={steps.length >= 2}
        today={new Date(nowMs)}
        pinTodayCursorToEnds={false}
      />
    </div>
  );
}
