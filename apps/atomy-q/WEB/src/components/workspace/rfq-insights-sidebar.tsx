'use client';

import React from 'react';
import { ChevronRight, BarChart2, AlertTriangle, Lightbulb } from 'lucide-react';
import { Card } from '@/components/ds/Card';
import { StatusBadge } from '@/components/ds/Badge';
import { useRfq } from '@/hooks/use-rfq';
import { useRfqOverview } from '@/hooks/use-rfq-overview';
import Link from 'next/link';

interface RfqInsightsSidebarProps {
  rfqId: string;
  isNewRfq?: boolean;
}

interface RfqRiskSummary {
  high: number;
  medium: number;
  low: number;
}

function PlaceholderCard({ icon, title, message }: { icon: React.ReactNode; title: string; message: string }) {
  return (
    <Card padding="sm" className="bg-slate-50 border-dashed">
      <div className="flex items-start gap-3">
        <div className="text-slate-400 shrink-0">{icon}</div>
        <div>
          <h4 className="text-xs font-semibold text-slate-700">{title}</h4>
          <p className="text-xs text-slate-500 mt-0.5">{message}</p>
        </div>
      </div>
    </Card>
  );
}

export function RfqInsightsSidebar({ rfqId, isNewRfq: explicitIsNewRfq }: RfqInsightsSidebarProps) {
  const [expanded, setExpanded] = React.useState(true);
  const { data: rfq, isLoading: rfqLoading, isError: rfqIsError, error: rfqError } = useRfq(rfqId);
  const { data: overview, isLoading: overviewLoading, isError: overviewIsError, error: overviewError } = useRfqOverview(rfqId);

  const derivedIsNewRfq =
    !rfqLoading &&
    rfq &&
    (rfq.status === 'draft' && (Number.isFinite(rfq.quotesCount) ? rfq.quotesCount === 0 : false));
  const isNewRfq = explicitIsNewRfq !== undefined ? explicitIsNewRfq : derivedIsNewRfq;

  const comparison = overview?.comparison;
  const placeholderRiskSummary: RfqRiskSummary = { high: 0, medium: 0, low: 0 };
  const riskSummary = isNewRfq ? undefined : placeholderRiskSummary;

  if (overviewLoading || rfqLoading) {
    return (
      <div className="w-72 border-l border-slate-200 bg-slate-50 p-4 space-y-3 animate-pulse">
        <div className="h-4 w-24 bg-slate-200 rounded" />
        <div className="h-20 bg-slate-200 rounded" />
        <div className="h-20 bg-slate-200 rounded" />
        <div className="h-20 bg-slate-200 rounded" />
      </div>
    );
  }

  const sidebarError = rfqIsError ? rfqError : overviewIsError ? overviewError : null;

  return (
    <div
      className={[
        'flex flex-col border-l border-slate-200 bg-slate-50 transition-all duration-200',
        expanded ? 'w-72' : 'w-12',
      ].join(' ')}
    >
      {/* Header with toggle */}
      <div className="flex items-center justify-between px-3 py-2 border-b border-slate-200">
        {expanded && <span className="text-xs font-semibold text-slate-700">Insights</span>}
        <button
          onClick={() => setExpanded(!expanded)}
          className="p-1 rounded hover:bg-slate-200 transition-colors"
          aria-label={expanded ? 'Collapse sidebar' : 'Expand sidebar'}
        >
          <ChevronRight
            size={16}
            className={['text-slate-500 transition-transform', !expanded && 'rotate-180'].join(' ')}
          />
        </button>
      </div>

      {expanded && (
        <div className="flex-1 overflow-y-auto p-3 space-y-3 custom-scrollbar">
          {sidebarError ? (
            <PlaceholderCard
              icon={<AlertTriangle size={16} />}
              title="Insights unavailable"
              message={sidebarError instanceof Error ? sidebarError.message : 'The insights sidebar could not load live RFQ context.'}
            />
          ) : null}

          {/* AI Insights Card */}
          {!sidebarError && isNewRfq ? (
            <PlaceholderCard
              icon={<Lightbulb size={16} />}
              title="AI Insights"
              message="No insights available yet. Insights will appear once you have quotes and comparison data."
            />
          ) : (
            <Card padding="sm">
              <h4 className="text-xs font-semibold text-slate-800 mb-2 flex items-center gap-1.5">
                <Lightbulb size={14} className="text-amber-500" />
                AI Insights
              </h4>
              <p className="text-xs text-slate-600">
                Analysis based on {overview?.normalization?.total_quotes ?? 0} quotes from{' '}
                {overview?.rfq?.vendors_count ?? 0} vendors.
              </p>
              {overview?.comparison && (
                <p className="text-xs text-slate-500 mt-2">
                  Latest comparison: <span className="font-medium">{overview.comparison.name}</span>
                </p>
              )}
            </Card>
          )}

          {/* Comparison Runs Card */}
          {!sidebarError && isNewRfq ? (
            <PlaceholderCard
              icon={<BarChart2 size={16} />}
              title="Comparison Runs"
              message="No comparison runs yet. Run a comparison to see vendor quotes analyzed."
            />
          ) : (
            <Card padding="sm" hover>
              <Link href={`/rfqs/${encodeURIComponent(rfqId)}/comparison-runs`} className="block">
                <h4 className="text-xs font-semibold text-slate-800 mb-2 flex items-center gap-1.5">
                  <BarChart2 size={14} className="text-indigo-500" />
                  Comparison Runs
                </h4>
                {comparison ? (
                  <div className="flex items-center justify-between">
                    <span className="text-xs text-slate-600">{comparison.name}</span>
                    <StatusBadge
                      status={
                        comparison.status === 'locked'
                          ? 'approved'
                          : comparison.is_preview
                            ? 'preview'
                            : comparison.status === 'draft'
                              ? 'draft'
                              : 'draft'
                      }
                      label={comparison.status ?? 'unknown'}
                    />
                  </div>
                ) : (
                  <p className="text-xs text-slate-500">No runs yet</p>
                )}
              </Link>
            </Card>
          )}

          {/* Risk Items Card */}
          {!sidebarError && isNewRfq ? (
            <PlaceholderCard
              icon={<AlertTriangle size={16} />}
              title="Risk Items"
              message="No risk items detected. Risk analysis will appear after vendor screening."
            />
          ) : (
            <Card padding="sm" hover>
              <Link href={`/rfqs/${encodeURIComponent(rfqId)}/risk`} className="block">
                <h4 className="text-xs font-semibold text-slate-800 mb-2 flex items-center gap-1.5">
                  <AlertTriangle size={14} className="text-red-500" />
                  Risk Items
                </h4>
                <div className="flex items-center gap-2">
                  {riskSummary?.high ? (
                    <span className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-red-100 text-red-700">
                      {riskSummary.high} High
                    </span>
                  ) : null}
                  {riskSummary?.medium ? (
                    <span className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-100 text-amber-700">
                      {riskSummary.medium} Med
                    </span>
                  ) : null}
                  {riskSummary?.low ? (
                    <span className="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-100 text-blue-700">
                      {riskSummary.low} Low
                    </span>
                  ) : null}
                  {riskSummary &&
                    riskSummary.high === 0 &&
                    riskSummary.medium === 0 &&
                    riskSummary.low === 0 && (
                      <span className="text-xs text-green-600 flex items-center gap-1">
                        <StatusBadge status="approved" label="No issues" />
                      </span>
                    )}
                  {riskSummary === undefined && (
                    <span className="text-xs text-slate-400">Loading...</span>
                  )}
                </div>
              </Link>
            </Card>
          )}
        </div>
      )}
    </div>
  );
}
