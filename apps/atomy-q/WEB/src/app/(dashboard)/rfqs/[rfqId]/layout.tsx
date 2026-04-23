'use client';

import React from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { AlertTriangle } from 'lucide-react';

import { Header } from '@/components/layout/header';
import { AppFooter } from '@/components/layout/app-footer';
import { MainSidebarNav } from '@/components/layout/main-sidebar-nav';
import { ActiveRecordMenu } from '@/components/workspace/active-record-menu';
import { RfqInsightsSidebar } from '@/components/workspace/rfq-insights-sidebar';
import { EmptyState, SectionCard } from '@/components/ds/Card';
import { isAlphaMode } from '@/lib/alpha-mode';
import { useRfq } from '@/hooks/use-rfq';
import { useFeatureFlags } from '@/hooks/use-feature-flags';
import { type RfqStatus, RFQ_STATUSES } from '@/hooks/use-rfqs';

function getPrimaryActionLabel(status: RfqStatus): string {
  switch (status) {
    case RFQ_STATUSES.ACTIVE:
      return 'Close for Submissions';
    case RFQ_STATUSES.CLOSED:
      return 'Award RFQ';
    case RFQ_STATUSES.AWARDED:
      return 'Archive';
    case RFQ_STATUSES.PENDING:
    case RFQ_STATUSES.DRAFT:
    case RFQ_STATUSES.ARCHIVED:
    default:
      return 'View Details';
  }
}

export default function RfqWorkspaceLayout({ children, params }: { children: React.ReactNode; params: Promise<{ rfqId: string }> }) {
  const pathname = usePathname();
  const [railExpanded, setRailExpanded] = React.useState(false);
  const alphaMode = isAlphaMode();

  const { rfqId } = React.use(params);
  const { data: rfq, isLoading, isError, error } = useRfq(rfqId);
  const { data: flags, isLoading: flagsLoading } = useFeatureFlags();
  const projectsLinkEnabled = flags?.projects === true;

  const record = rfq
    ? {
        id: rfq.id,
        title: rfq.title,
        status: rfq.status,
        vendorsCount: rfq.vendorsCount ?? 0,
        quotesCount: rfq.quotesCount ?? 0,
        estValue: rfq.estValue ?? '—',
        savings: rfq.savings ?? '—',
        primaryActionLabel: getPrimaryActionLabel(rfq.status),
      }
    : null;

  return (
    <div className="flex h-screen bg-slate-100 overflow-hidden font-sans">
      {/* Collapsed rail that expands on hover */}
      <div
        className={['relative flex-shrink-0 z-20 transition-all duration-200', railExpanded ? 'w-[200px]' : 'w-12'].join(' ')}
        onMouseEnter={() => setRailExpanded(true)}
        onMouseLeave={() => setRailExpanded(false)}
      >
        <div className={['absolute inset-y-0 left-0 flex flex-col bg-white border-r border-slate-200 overflow-hidden transition-all duration-200', railExpanded ? 'w-[200px] shadow-lg' : 'w-12'].join(' ')}>
          <div className="flex items-center justify-center h-14 shrink-0 border-b border-slate-200">
            <div className="w-7 h-7 bg-indigo-600 rounded-md flex items-center justify-center shrink-0">
              <span className="text-white text-xs font-bold">AQ</span>
            </div>
            {railExpanded && <span className="ml-2.5 text-sm font-semibold text-slate-800 whitespace-nowrap overflow-hidden">Atomy-Q</span>}
          </div>

          <nav className="flex-1 overflow-y-auto py-3 px-1.5 space-y-0.5 custom-scrollbar">
            <MainSidebarNav
              pathname={pathname}
              alphaMode={alphaMode}
              collapsed={!railExpanded}
              featureFlags={flags}
              featureFlagsLoading={flagsLoading}
            />
          </nav>
        </div>
      </div>

      {/* Right side: TopBar + (Active Record Menu + Work surface) + Footer — per Screen-Blueprint Workspace layout */}
      <div className="flex-1 flex flex-col min-w-0 min-h-0">
        <Header />
        <div className="flex flex-1 min-h-0">
          {!isLoading && record && <ActiveRecordMenu record={record} />}
          {!isLoading && !record && isError && (
            <div className="w-[360px] shrink-0 bg-white border-r border-slate-200 overflow-y-auto">
              <div className="px-4 pt-4 pb-4 border-b border-slate-200">
                <div className="text-xs font-mono text-slate-400">{rfqId}</div>
                <div className="text-sm font-semibold text-slate-900">RFQ context unavailable</div>
              </div>
              <div className="p-4">
                <SectionCard title="Workspace context unavailable" subtitle="Mandatory RFQ shell data could not be loaded.">
                  <EmptyState
                    icon={<AlertTriangle size={20} />}
                    title="Could not load RFQ record"
                    description={error instanceof Error ? error.message : 'The RFQ workspace shell could not load this record.'}
                  />
                </SectionCard>
              </div>
            </div>
          )}
          <div className="flex-1 min-w-0 overflow-y-auto flex flex-col">
            <div className="flex flex-1">
              <div className="flex-1 min-w-0">
                <div className="p-6 flex-1">
                  <div className="max-w-7xl mx-auto space-y-6">
                    {!isLoading && !record && isError && (
                      <SectionCard title="RFQ workspace unavailable" subtitle="Mandatory RFQ context failed to load.">
                        <EmptyState
                          icon={<AlertTriangle size={20} />}
                          title="Could not load RFQ workspace context"
                          description={error instanceof Error ? error.message : 'The workspace shell could not load the RFQ record.'}
                        />
                      </SectionCard>
                    )}
                    {!isLoading && rfq && (
                      <div className="text-sm text-slate-600">
                        Project:{' '}
                        {flagsLoading ? (
                          <span className="inline-block h-4 w-32 rounded bg-slate-200 animate-pulse align-middle" aria-hidden />
                        ) : rfq.projectId ? (
                          projectsLinkEnabled ? (
                            <Link
                              href={`/projects/${encodeURIComponent(rfq.projectId)}`}
                              className="text-indigo-600 hover:underline font-medium"
                            >
                              {rfq.projectName ?? rfq.projectId}
                            </Link>
                          ) : (
                            <span className="text-slate-700 font-medium">{rfq.projectName ?? rfq.projectId}</span>
                          )
                        ) : (
                          <span className="text-slate-700 font-medium">Unassigned</span>
                        )}
                      </div>
                    )}
                    {children}
                  </div>
                </div>
              </div>
              <RfqInsightsSidebar rfqId={rfqId} />
            </div>
          </div>
        </div>
        <AppFooter />
      </div>
    </div>
  );
}
