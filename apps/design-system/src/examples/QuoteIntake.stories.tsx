import type { Meta, StoryObj } from '@storybook/react';
import { AtomyQTable, type AtomyQColumnDef } from '../components/data/AtomyQTable';
import { AtomyQBadge } from '../components/basic/AtomyQBadge';
import { AtomyQButton } from '../components/basic/AtomyQButton';
import { AtomyQProgress } from '../components/feedback/AtomyQProgress';
import { AtomyQSidebar } from '../components/navigation/AtomyQSidebar';
import { AtomyQBreadcrumb } from '../components/navigation/AtomyQBreadcrumb';
import { AtomyQAvatar } from '../components/basic/AtomyQAvatar';
import { AtomyQInput } from '../components/form/AtomyQInput';
import { quoteSubmissions, navigationItems, type QuoteSubmission } from '@/data/mockData';
import { Search, Upload, Bell, Filter, FileText, RefreshCw } from 'lucide-react';

const meta: Meta = {
  title: 'Examples/Quote Intake',
  parameters: { layout: 'fullscreen' },
};

export default meta;

const statusVariant = (s: string) => {
  const m: Record<string, 'success' | 'warning' | 'info' | 'danger'> = {
    Accepted: 'success', 'Parsed with Warnings': 'warning', Processing: 'info', Rejected: 'danger',
  };
  return m[s] || 'neutral' as const;
};

const columns: AtomyQColumnDef<QuoteSubmission>[] = [
  { key: 'id', header: 'ID', accessor: (q) => <span className="font-mono text-xs">{q.id}</span>, width: '90px' },
  { key: 'vendor', header: 'Vendor', accessor: (q) => <span className="font-medium text-[var(--aq-text-primary)]">{q.vendor}</span> },
  { key: 'rfqId', header: 'RFQ', accessor: (q) => <span className="font-mono text-xs">{q.rfqId}</span>, width: '130px' },
  { key: 'fileName', header: 'File', accessor: (q) => (
    <div className="flex items-center gap-2">
      <FileText className="size-3.5 text-[var(--aq-text-muted)]" />
      <div>
        <span className="text-xs">{q.fileName}</span>
        <span className="text-[10px] text-[var(--aq-text-subtle)] ml-2">{q.fileSize}</span>
      </div>
    </div>
  )},
  { key: 'status', header: 'Status', accessor: (q) => <AtomyQBadge variant={statusVariant(q.status)} dot>{q.status}</AtomyQBadge>, width: '160px' },
  { key: 'confidence', header: 'Confidence', accessor: (q) => q.confidence != null ? (
    <div className="flex items-center gap-2 min-w-[100px]">
      <AtomyQProgress value={q.confidence} size="sm" variant={q.confidence > 80 ? 'success' : q.confidence > 60 ? 'warning' : 'danger'} />
      <span className="font-mono text-xs w-8 text-right">{q.confidence}%</span>
    </div>
  ) : <span className="text-[var(--aq-text-subtle)]">—</span>, width: '140px' },
  { key: 'lineItems', header: 'Lines', accessor: (q) => q.lineItems ?? '—', align: 'center', width: '60px' },
  { key: 'warnings', header: 'Issues', accessor: (q) => {
    if (q.warnings === null) return '—';
    const total = (q.warnings || 0) + (q.errors || 0);
    if (total === 0) return <AtomyQBadge variant="success" size="sm">Clean</AtomyQBadge>;
    return <AtomyQBadge variant={q.errors ? 'danger' : 'warning'} size="sm">{q.warnings}W / {q.errors}E</AtomyQBadge>;
  }, width: '100px' },
  { key: 'submittedAt', header: 'Received', accessor: (q) => <span className="font-mono text-xs">{q.submittedAt}</span>, width: '140px' },
];

export const QuoteIntakeInbox: StoryObj = {
  render: () => (
    <div className="flex h-screen bg-[var(--aq-bg-canvas)]">
      <AtomyQSidebar sections={navigationItems} activeHref="/intake" />

      <div className="flex-1 flex flex-col overflow-hidden">
        <header className="flex items-center justify-between h-14 px-6 bg-[var(--aq-header-bg)] border-b border-[var(--aq-header-border)]">
          <AtomyQBreadcrumb items={[{ label: 'Quote Intake' }]} />
          <div className="flex items-center gap-3">
            <AtomyQButton variant="ghost" size="icon-sm"><Bell className="size-4" /></AtomyQButton>
            <AtomyQAvatar fallback="SC" size="sm" />
          </div>
        </header>

        <main className="flex-1 overflow-y-auto p-6">
          <div className="max-w-[1400px] mx-auto space-y-4">
            <div className="flex items-center justify-between">
              <div>
                <h1 className="text-xl font-semibold text-[var(--aq-text-primary)]">Quote Intake Inbox</h1>
                <p className="text-sm text-[var(--aq-text-muted)]">AI-parsed vendor quotations awaiting review</p>
              </div>
              <div className="flex gap-2">
                <AtomyQButton variant="outline"><RefreshCw className="size-4" /> Reprocess</AtomyQButton>
                <AtomyQButton variant="primary"><Upload className="size-4" /> Upload Quote</AtomyQButton>
              </div>
            </div>

            {/* Filter bar */}
            <div className="flex items-center gap-3 p-3 bg-white rounded-lg border border-[var(--aq-border-default)]">
              <AtomyQInput placeholder="Search by vendor or file name..." leftIcon={<Search />} className="w-64" />
              <AtomyQButton variant="outline" size="sm"><Filter className="size-3.5" /> Filters</AtomyQButton>
              <div className="flex-1" />
              <div className="flex gap-1.5">
                <AtomyQBadge variant="success">Accepted: 2</AtomyQBadge>
                <AtomyQBadge variant="warning">Warnings: 2</AtomyQBadge>
                <AtomyQBadge variant="info">Processing: 1</AtomyQBadge>
              </div>
            </div>

            <AtomyQTable
              columns={columns}
              data={quoteSubmissions}
              rowKey={(q) => q.id}
              stickyHeader
            />
          </div>
        </main>
      </div>
    </div>
  ),
};
