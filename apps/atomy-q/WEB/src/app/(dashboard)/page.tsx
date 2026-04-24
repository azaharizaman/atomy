'use client';

import React from 'react';
import { useQuery } from '@tanstack/react-query';
import {
  BarChart2,
  CheckCircle2,
  FileText,
  GitCompareArrows,
  HandCoins,
  Inbox,
  LayoutGrid,
  Sparkles,
  Users,
} from 'lucide-react';
import { useRouter } from 'next/navigation';
import { AiNarrativePanel } from '@/components/ai/ai-narrative-panel';
import { useDashboardAiSummary } from '@/hooks/use-dashboard-ai-summary';
import { fetchLiveOrFail } from '@/lib/api-live';
import {
  ActivitySummaryCard,
  type ActivitySummaryItem,
  CategoryBreakdownCard,
  type CategoryMetricItem,
  PendingApprovalsCard,
  type PendingApprovalItem,
  PipelineStatCard,
  QuickActionCard,
  SavingsHighlightCard,
  SLAAlertCard,
} from '@/components/dashboard/DashboardCards';

type DashboardKpis = {
  active_rfqs?: number;
  pending_approvals?: number;
  total_savings?: string | number;
  avg_cycle_time_days?: string | number;
};

type DashboardActivityRaw = {
  id?: string | number;
  timestamp?: string;
  time?: string;
  actor?: string;
  user?: string;
  action?: string;
  title?: string;
  rfqId?: string;
};

export default function DashboardPage() {
  const router = useRouter();
  const dashboardAiSummary = useDashboardAiSummary();

  const { data: kpis, isLoading: isLoadingKpis } = useQuery({
    queryKey: ['dashboard', 'kpis'],
    queryFn: async () => {
      const data = await fetchLiveOrFail<DashboardKpis>('/dashboard/kpis');
      if (!data) {
        return {
          active_rfqs: 12,
          pending_approvals: 5,
          total_savings: '$1.2M',
          avg_cycle_time_days: '14 days',
        } satisfies DashboardKpis;
      }
      return data;
    },
  });

  const { data: activity, isLoading: isLoadingActivity } = useQuery({
    queryKey: ['dashboard', 'activity'],
    queryFn: async () => {
      const data = await fetchLiveOrFail<{ data?: DashboardActivityRaw[] }>('/dashboard/recent-activity');
      if (!data) {
        return [
          { id: 'act-1', timestamp: '2 hours ago', actor: 'Alex Kumar', action: 'published RFQ', rfqId: 'RFQ-2407' },
          { id: 'act-2', timestamp: '4 hours ago', actor: 'Priya Nair', action: 'requested approval', rfqId: 'RFQ-2404' },
          { id: 'act-3', timestamp: 'Yesterday', actor: 'System', action: 'normalized quotes (18/24)', rfqId: 'RFQ-2401' },
          { id: 'act-4', timestamp: 'Yesterday', actor: 'Marcus Webb', action: 'invited vendors', rfqId: 'RFQ-2408' },
        ] as ActivitySummaryItem[];
      }
      const items = Array.isArray(data) ? data : (data?.data ?? []);
      return items.map((item: DashboardActivityRaw, index: number) => ({
        id: String(item.id ?? index),
        timestamp: String(item.timestamp ?? item.time ?? 'Just now'),
        actor: String(item.actor ?? item.user ?? 'System'),
        action: String(item.action ?? item.title ?? 'updated an RFQ'),
        rfqId: item.rfqId ? String(item.rfqId) : undefined,
      })) as ActivitySummaryItem[];
    },
  });

  const pipeline = {
    active: Number(kpis?.active_rfqs ?? 0),
    pending: Number(kpis?.pending_approvals ?? 0),
    intake: 0,
    awards: 0,
  };

  const savingsValue =
    typeof kpis?.total_savings === 'number'
      ? `$${kpis.total_savings.toLocaleString('en-US')}`
      : kpis?.total_savings ?? '$0';

  const approvals: PendingApprovalItem[] = [];

  const categories: CategoryMetricItem[] = [];

  const alerts: { id: string; title: string; rfqId: string; timeRemaining: string; urgency: 'high' | 'medium' | 'low'; assignee: string }[] = [];

  const quickActions = [
    { id: 'qa-1', icon: <FileText size={16} />, title: 'Create RFQ', description: 'Launch a new requisition with guided intake.', actionLabel: 'Start', onAction: () => router.push('/rfqs/new') },
    { id: 'qa-2', icon: <Inbox size={16} />, title: 'Quote Intake', description: 'Review new vendor uploads and extraction status.', actionLabel: 'View intake', onAction: () => router.push('/rfqs') },
    { id: 'qa-3', icon: <GitCompareArrows size={16} />, title: 'Comparison Runs', description: 'Generate or review latest scoring outputs.', actionLabel: 'Open runs', onAction: () => router.push('/rfqs') },
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <div className="flex items-center gap-2 text-xs font-medium text-slate-500">
            <LayoutGrid size={12} className="text-slate-400" />
            Overview
          </div>
          <h1 className="text-2xl font-semibold text-slate-900">Dashboard</h1>
          <p className="text-sm text-slate-500">Snapshot of requisitions, approvals, and savings performance.</p>
        </div>
        {dashboardAiSummary.summary?.available ? (
          <div className="inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700">
            <Sparkles size={12} />
            AI insights active
          </div>
        ) : null}
      </div>

      <AiNarrativePanel
        featureKey="dashboard_ai_summary"
        title="AI Summary"
        subtitle="Assistive interpretation of the deterministic dashboard signals."
        summary={dashboardAiSummary.summary}
        isLoading={dashboardAiSummary.isLoading}
        isError={dashboardAiSummary.isError}
        error={dashboardAiSummary.error}
        fallbackCopy="Dashboard insights are unavailable. Deterministic dashboard metrics remain usable."
      />

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        {[
          { label: 'Active RFQs', count: pipeline.active, icon: <FileText size={16} /> },
          { label: 'Pending Approvals', count: pipeline.pending, icon: <CheckCircle2 size={16} /> },
          { label: 'Quotes In Intake', count: pipeline.intake, icon: <Inbox size={16} /> },
          { label: 'Awards In Flight', count: pipeline.awards, icon: <HandCoins size={16} /> },
        ].map((item, index) => (
          <div
            key={item.label}
            className="animate-fade-up"
            style={{ animationDelay: `${index * 80}ms` }}
          >
            <PipelineStatCard
              label={item.label}
              count={item.count}
              icon={item.icon}
              onClick={() => router.push('/rfqs')}
            />
          </div>
        ))}
      </div>

      <div className="grid gap-4 lg:grid-cols-[1.3fr,1fr]">
        <div className="animate-fade-up" style={{ animationDelay: '120ms' }}>
          <SavingsHighlightCard
            title="YTD Savings"
            value={isLoadingKpis ? '...' : String(savingsValue)}
            subtitle="Savings impact across all RFQs"
            trend={undefined}
          />
        </div>
        <div className="rounded-lg border border-slate-200 bg-white p-5 shadow-[0_1px_3px_0_rgba(0,0,0,0.06)] animate-fade-up" style={{ animationDelay: '200ms' }}>
          <div className="flex items-center justify-between">
            <h3 className="text-sm font-semibold text-slate-800">Spend Trend</h3>
            <div className="text-xs text-slate-400">Last 6 months</div>
          </div>
          <div className="mt-4 h-36 rounded-lg bg-gradient-to-b from-indigo-50 to-white border border-slate-100 flex items-end justify-between px-4 pb-4">
            {[38, 52, 46, 68, 58, 72].map((value, index) => (
              <div key={index} className="flex flex-col items-center gap-1">
                <div className="w-6 rounded-md bg-indigo-500/70" style={{ height: `${value}px` }} />
                <span className="text-[10px] text-slate-400">{['Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'][index]}</span>
              </div>
            ))}
          </div>
          <div className="mt-3 flex items-center gap-2 text-xs text-slate-500">
            <BarChart2 size={12} className="text-indigo-500" />
            Trending upward with improved cycle times.
          </div>
        </div>
      </div>

      <div className="grid gap-4 xl:grid-cols-[1.1fr,1fr,1fr]">
        <div className="animate-fade-up" style={{ animationDelay: '260ms' }}>
          <PendingApprovalsCard
            items={approvals}
            onItemClick={(id) => router.push(`/rfqs?approval=${encodeURIComponent(id)}`)}
            onViewAll={() => router.push('/rfqs')}
          />
        </div>
        <div className="animate-fade-up" style={{ animationDelay: '320ms' }}>
          <ActivitySummaryCard
            items={activity ?? []}
            onViewAll={() => router.push('/rfqs')}
            className={isLoadingActivity ? 'opacity-60' : ''}
          />
        </div>
        <div className="animate-fade-up" style={{ animationDelay: '380ms' }}>
          <CategoryBreakdownCard items={categories} />
        </div>
      </div>

      <div className="grid gap-4 lg:grid-cols-[1.1fr,1.3fr]">
        <div className="space-y-3">
          <h3 className="text-sm font-semibold text-slate-800">SLA Alerts</h3>
          {alerts.length === 0 ? (
            <div className="rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-500">
              No SLA alerts for the current period.
            </div>
          ) : (
            alerts.map(alert => (
              <SLAAlertCard
                key={alert.id}
                title={alert.title}
                rfqId={alert.rfqId}
                timeRemaining={alert.timeRemaining}
                urgency={alert.urgency as 'high' | 'medium' | 'low'}
                assignee={alert.assignee}
                onClick={() => router.push(`/rfqs/${encodeURIComponent(alert.rfqId)}/overview`)}
              />
            ))
          )}
        </div>

        <div className="grid gap-3 sm:grid-cols-2">
          {quickActions.map(action => (
            <QuickActionCard
              key={action.id}
              icon={action.icon}
              title={action.title}
              description={action.description}
              actionLabel={action.actionLabel}
              onAction={action.onAction}
            />
          ))}
          <QuickActionCard
            icon={<Users size={16} />}
            title="Vendor Scores"
            description="Review performance and compliance signals."
            actionLabel="Open vendors"
            onAction={() => router.push('/rfqs')}
          />
        </div>
      </div>

      {!isLoadingKpis && (!activity || activity.length === 0) && (
        <div className="rounded-lg border border-dashed border-slate-300 bg-white p-5 text-sm text-slate-500">
          Dashboard data is still syncing. Once activity is available, you will see KPI snapshots and SLA alerts here.
        </div>
      )}
    </div>
  );
}
