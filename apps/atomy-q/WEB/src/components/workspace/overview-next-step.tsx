'use client';

import React from 'react';
import Link from 'next/link';
import { ChevronRight } from 'lucide-react';
import { Button } from '@/components/ds/Button';
import type { RfqStatus } from '@/hooks/use-rfqs';
import { RFQ_STATUSES } from '@/hooks/use-rfqs';

export interface OverviewNextStepProps {
  rfqId: string;
  status: RfqStatus;
  approvalOverall: 'none' | 'pending' | 'approved' | 'rejected';
  hasComparisonRun: boolean;
  comparisonIsPreview?: boolean;
  submissionDeadline?: string | null;
}

export function OverviewNextStep({
  rfqId,
  status,
  approvalOverall,
  hasComparisonRun,
  comparisonIsPreview,
  submissionDeadline,
}: OverviewNextStepProps) {
  const base = `/rfqs/${encodeURIComponent(rfqId)}`;
  const [now, setNow] = React.useState<number>(() => Date.now());

  React.useEffect(() => {
    const interval = setInterval(() => setNow(Date.now()), 60 * 1000);
    return () => clearInterval(interval);
  }, []);

  const deadlineDate = submissionDeadline ? new Date(submissionDeadline) : null;
  const isOverdue = deadlineDate ? deadlineDate.getTime() < now : false;
  const daysLeft = deadlineDate
    ? Math.ceil((deadlineDate.getTime() - now) / (1000 * 60 * 60 * 24))
    : null;

  let stepLabel: string;
  let stepHref: string;
  let stepVariant: 'primary' | 'outline' = 'primary';

  if (status === RFQ_STATUSES.DRAFT) {
    stepLabel = 'Complete details and publish';
    stepHref = `${base}/details`;
  } else if (status === RFQ_STATUSES.PENDING || status === RFQ_STATUSES.ACTIVE) {
    stepLabel = 'Close for submissions';
    stepHref = `${base}/details`;
    stepVariant = 'outline';
  } else if (status === RFQ_STATUSES.CLOSED && !hasComparisonRun) {
    stepLabel = 'Run comparison';
    stepHref = `${base}/comparison-runs`;
  } else if (status === RFQ_STATUSES.CLOSED && hasComparisonRun && comparisonIsPreview) {
    stepLabel = 'Finalize comparison run';
    stepHref = `${base}/comparison-runs`;
  } else if (approvalOverall === 'pending') {
    stepLabel = 'Review pending approvals';
    stepHref = `${base}/approvals`;
  } else if (status === RFQ_STATUSES.CLOSED && approvalOverall !== 'pending') {
    stepLabel = 'Proceed to award';
    stepHref = `${base}/award`;
  } else if (status === RFQ_STATUSES.AWARDED) {
    stepLabel = 'View award';
    stepHref = `${base}/award`;
    stepVariant = 'outline';
  } else {
    stepLabel = 'View details';
    stepHref = `${base}/details`;
    stepVariant = 'outline';
  }

  return (
    <div className="rounded-lg border border-slate-200 bg-white p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div className="flex items-start gap-3 min-w-0">
        <div className="shrink-0 w-9 h-9 rounded-full bg-indigo-50 flex items-center justify-center">
          <ChevronRight className="text-indigo-600" size={18} />
        </div>
        <div className="min-w-0">
          <p className="text-sm font-medium text-slate-900">Next step</p>
          <p className="text-sm text-slate-600 mt-0.5">{stepLabel}</p>
          {daysLeft !== null && (
            <p className={['text-xs mt-1', isOverdue ? 'text-red-600 font-medium' : 'text-slate-500'].join(' ')}>
              {isOverdue ? 'Submission deadline passed' : `Submission deadline in ${daysLeft} day${daysLeft !== 1 ? 's' : ''}`}
            </p>
          )}
        </div>
      </div>
      <Link href={stepHref} className="shrink-0">
        <Button size="sm" variant={stepVariant}>
          {stepLabel}
        </Button>
      </Link>
    </div>
  );
}
