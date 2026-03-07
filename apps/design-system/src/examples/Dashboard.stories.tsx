import type { Meta, StoryObj } from '@storybook/react';
import { AtomyQKPICard } from '../components/data/AtomyQKPICard';
import { AtomyQTable, type AtomyQColumnDef } from '../components/data/AtomyQTable';
import { AtomyQBadge } from '../components/basic/AtomyQBadge';
import { AtomyQAvatar } from '../components/basic/AtomyQAvatar';
import { AtomyQButton } from '../components/basic/AtomyQButton';
import { AtomyQSidebar } from '../components/navigation/AtomyQSidebar';
import { AtomyQBreadcrumb } from '../components/navigation/AtomyQBreadcrumb';
import {
  kpiMetrics, tasks, riskAlerts, recentComparisons, activityTimeline, navigationItems,
  type Task, type RiskAlert,
} from '@/data/mockData';
import {
  FileText, TrendingUp, Clock, Sparkles, Plus, Bell,
  Search, AlertTriangle, CheckCircle, AlertCircle, History,
} from 'lucide-react';

const meta: Meta = {
  title: 'Examples/Dashboard',
  parameters: { layout: 'fullscreen' },
};

export default meta;

const taskColumns: AtomyQColumnDef<Task>[] = [
  { key: 'title', header: 'Task', accessor: (t) => <span className="font-medium text-[var(--aq-text-primary)]">{t.title}</span> },
  { key: 'type', header: 'Type', accessor: (t) => <AtomyQBadge variant="outline">{t.type}</AtomyQBadge>, width: '100px' },
  { key: 'rfq', header: 'RFQ', accessor: (t) => t.rfq ? <span className="font-mono text-xs">{t.rfq}</span> : <span className="text-[var(--aq-text-subtle)]">—</span>, width: '130px' },
  {
    key: 'priority', header: 'Priority', width: '100px',
    accessor: (t) => {
      const v: Record<string, 'neutral' | 'info' | 'warning' | 'danger'> = { Low: 'neutral', Medium: 'info', High: 'warning', Critical: 'danger' };
      return <AtomyQBadge variant={v[t.priority] || 'neutral'}>{t.priority}</AtomyQBadge>;
    },
  },
  {
    key: 'due', header: 'Due', width: '100px',
    accessor: (t) => (
      <span className={t.due === 'Overdue' ? 'text-[var(--aq-danger-600)] font-medium text-xs' : 'text-xs'}>
        {t.due}
      </span>
    ),
  },
];

const severityIcon = {
  Critical: <AlertCircle className="size-4 text-[var(--aq-danger-600)]" />,
  High: <AlertTriangle className="size-4 text-[var(--aq-warning-600)]" />,
  Medium: <AlertTriangle className="size-4 text-[var(--aq-warning-500)]" />,
  Low: <AlertCircle className="size-4 text-[var(--aq-info-500)]" />,
};

export const FullDashboard: StoryObj = {
  render: () => (
    <div className="flex h-screen bg-[var(--aq-bg-canvas)]">
      {/* Sidebar */}
      <AtomyQSidebar sections={navigationItems} activeHref="/" />

      {/* Main */}
      <div className="flex-1 flex flex-col overflow-hidden">
        {/* Header */}
        <header className="flex items-center justify-between h-14 px-6 bg-[var(--aq-header-bg)] border-b border-[var(--aq-header-border)]">
          <div className="flex items-center gap-4">
            <AtomyQBreadcrumb items={[{ label: 'Dashboard' }]} />
          </div>
          <div className="flex items-center gap-3">
            <div className="flex items-center gap-2 h-8 px-3 rounded-md bg-white/50 border border-[var(--aq-border-default)] text-sm text-[var(--aq-text-muted)]">
              <Search className="size-3.5" /> Search... <kbd className="text-xs bg-[var(--aq-bg-elevated)] px-1 rounded">/</kbd>
            </div>
            <AtomyQButton variant="primary" size="sm"><Plus className="size-3.5" /> New RFQ</AtomyQButton>
            <AtomyQButton variant="ghost" size="icon-sm"><Sparkles className="size-4" /></AtomyQButton>
            <div className="relative">
              <AtomyQButton variant="ghost" size="icon-sm"><Bell className="size-4" /></AtomyQButton>
              <span className="absolute -top-0.5 -right-0.5 size-4 bg-[var(--aq-danger-500)] text-white text-[10px] font-bold rounded-full flex items-center justify-center">3</span>
            </div>
            <AtomyQAvatar fallback="SC" size="sm" status="online" />
          </div>
        </header>

        {/* Content */}
        <main className="flex-1 overflow-y-auto p-6">
          <div className="max-w-[1400px] mx-auto space-y-6">
            {/* Page Title */}
            <div>
              <h1 className="text-xl font-semibold text-[var(--aq-text-primary)]">Dashboard</h1>
              <p className="text-sm text-[var(--aq-text-muted)]">Overview of your procurement workspace</p>
            </div>

            {/* KPI Cards */}
            <div className="grid grid-cols-4 gap-4">
              {kpiMetrics.map((metric, i) => {
                const icons = [FileText, TrendingUp, Clock, Sparkles];
                const Icon = icons[i];
                return (
                  <AtomyQKPICard
                    key={metric.label}
                    label={metric.label}
                    value={metric.value}
                    change={metric.change}
                    trend={metric.trend}
                    period={metric.period}
                    icon={<Icon className="size-4" />}
                  />
                );
              })}
            </div>

            {/* Two column layout */}
            <div className="grid grid-cols-3 gap-6">
              {/* Tasks - 2 columns */}
              <div className="col-span-2">
                <div className="bg-white rounded-lg border border-[var(--aq-border-default)] shadow-sm">
                  <div className="flex items-center justify-between px-5 py-3 border-b border-[var(--aq-border-default)]">
                    <h3 className="text-sm font-semibold text-[var(--aq-text-primary)]">My Tasks</h3>
                    <AtomyQBadge variant="default">{tasks.length}</AtomyQBadge>
                  </div>
                  <AtomyQTable columns={taskColumns} data={tasks} rowKey={(t) => t.id} compact />
                </div>
              </div>

              {/* Risk Alerts - 1 column */}
              <div className="space-y-4">
                <div className="bg-white rounded-lg border border-[var(--aq-border-default)] shadow-sm">
                  <div className="flex items-center justify-between px-5 py-3 border-b border-[var(--aq-border-default)]">
                    <h3 className="text-sm font-semibold text-[var(--aq-text-primary)]">Risk Alerts</h3>
                    <AtomyQBadge variant="danger">{riskAlerts.length}</AtomyQBadge>
                  </div>
                  <div className="divide-y divide-[var(--aq-border-subtle)]">
                    {riskAlerts.map((alert) => (
                      <div key={alert.id} className="px-5 py-3 flex gap-3">
                        {severityIcon[alert.severity as keyof typeof severityIcon]}
                        <div className="min-w-0">
                          <p className="text-sm font-medium text-[var(--aq-text-primary)] truncate">{alert.title}</p>
                          <p className="text-xs text-[var(--aq-text-muted)] mt-0.5 line-clamp-2">{alert.description}</p>
                          <div className="flex items-center gap-2 mt-1">
                            <span className="font-mono text-[10px] text-[var(--aq-text-subtle)]">{alert.source}</span>
                            <span className="text-[10px] text-[var(--aq-text-subtle)]">{alert.time}</span>
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>

                {/* Recent Activity */}
                <div className="bg-white rounded-lg border border-[var(--aq-border-default)] shadow-sm">
                  <div className="px-5 py-3 border-b border-[var(--aq-border-default)]">
                    <h3 className="text-sm font-semibold text-[var(--aq-text-primary)]">Recent Activity</h3>
                  </div>
                  <div className="divide-y divide-[var(--aq-border-subtle)]">
                    {activityTimeline.slice(0, 5).map((a) => (
                      <div key={a.id} className="px-5 py-2.5">
                        <p className="text-xs text-[var(--aq-text-secondary)]">{a.action}</p>
                        <div className="flex items-center gap-2 mt-0.5">
                          <span className="text-[10px] text-[var(--aq-text-muted)]">{a.actor}</span>
                          <span className="text-[10px] text-[var(--aq-text-subtle)]">{a.time}</span>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            </div>

            {/* Recent Comparisons */}
            <div className="bg-white rounded-lg border border-[var(--aq-border-default)] shadow-sm">
              <div className="flex items-center justify-between px-5 py-3 border-b border-[var(--aq-border-default)]">
                <h3 className="text-sm font-semibold text-[var(--aq-text-primary)]">Recent AI Comparisons</h3>
                <AtomyQButton variant="ghost" size="sm">View All</AtomyQButton>
              </div>
              <div className="grid grid-cols-3 gap-4 p-5">
                {recentComparisons.map((cmp) => (
                  <div key={cmp.id} className="p-4 rounded-lg border border-[var(--aq-border-default)] hover:border-[var(--aq-brand-300)] transition-colors cursor-pointer">
                    <div className="flex items-center justify-between mb-2">
                      <span className="font-mono text-xs text-[var(--aq-text-muted)]">{cmp.rfq}</span>
                      <AtomyQBadge variant={cmp.status === 'Complete' ? 'success' : 'warning'} dot>{cmp.status}</AtomyQBadge>
                    </div>
                    <p className="text-sm font-medium text-[var(--aq-text-primary)]">{cmp.title}</p>
                    <div className="flex items-center justify-between mt-3">
                      <span className="text-xs text-[var(--aq-text-muted)]">{cmp.vendorCount} vendors</span>
                      <span className="text-lg font-semibold font-mono text-[var(--aq-brand-600)]">{cmp.score}</span>
                    </div>
                    <p className="text-xs text-[var(--aq-text-subtle)] mt-1">Recommended: {cmp.recommended}</p>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </main>

        {/* Footer */}
        <footer className="flex items-center justify-between h-8 px-6 bg-[var(--aq-bg-elevated)] border-t border-[var(--aq-border-default)] text-xs text-[var(--aq-text-subtle)]">
          <span>AtomyQ v1.0.0</span>
          <div className="flex items-center gap-3">
            <AtomyQBadge variant="success" size="sm">Production</AtomyQBadge>
            <span>API</span>
            <span>Docs</span>
          </div>
        </footer>
      </div>
    </div>
  ),
};
