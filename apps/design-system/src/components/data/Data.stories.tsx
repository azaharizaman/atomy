import type { Meta, StoryObj } from '@storybook/react';
import { useState } from 'react';
import { AtomyQTable, type AtomyQColumnDef, type SortDirection } from './AtomyQTable';
import { AtomyQPagination } from './AtomyQPagination';
import { AtomyQKPICard } from './AtomyQKPICard';
import { AtomyQBadge } from '../basic/AtomyQBadge';
import { rfqs, type RFQ, kpiMetrics } from '@/data/mockData';
import { FileText, TrendingUp, Clock, Sparkles } from 'lucide-react';

const meta: Meta = {
  title: 'Components/Data/Data Components',
  parameters: { layout: 'padded' },
};

export default meta;

const statusVariant = (status: string) => {
  const map: Record<string, 'neutral' | 'info' | 'success' | 'default' | 'danger'> = {
    Draft: 'neutral', Open: 'info', Awarded: 'success', Closed: 'default', Cancelled: 'danger',
  };
  return map[status] || 'neutral';
};

const priorityVariant = (priority: string) => {
  const map: Record<string, 'neutral' | 'info' | 'warning' | 'danger'> = {
    Low: 'neutral', Medium: 'info', High: 'warning', Critical: 'danger',
  };
  return map[priority] || 'neutral';
};

const rfqColumns: AtomyQColumnDef<RFQ>[] = [
  { key: 'id', header: 'ID', accessor: (r) => <span className="font-mono text-xs">{r.id}</span>, sortable: true, width: '140px' },
  { key: 'title', header: 'Title', accessor: (r) => <span className="font-medium text-[var(--aq-text-primary)]">{r.title}</span>, sortable: true },
  { key: 'category', header: 'Category', accessor: (r) => r.category, sortable: true, width: '120px' },
  { key: 'status', header: 'Status', accessor: (r) => <AtomyQBadge variant={statusVariant(r.status)} dot>{r.status}</AtomyQBadge>, sortable: true, width: '120px' },
  { key: 'priority', header: 'Priority', accessor: (r) => <AtomyQBadge variant={priorityVariant(r.priority)}>{r.priority}</AtomyQBadge>, width: '100px' },
  { key: 'owner', header: 'Owner', accessor: (r) => r.owner, width: '130px' },
  { key: 'vendorCount', header: 'Vendors', accessor: (r) => r.vendorCount, align: 'center', width: '80px' },
  { key: 'budget', header: 'Budget', accessor: (r) => <span className="font-mono text-xs">RM {r.budget.toLocaleString()}</span>, align: 'right', sortable: true, width: '130px' },
  { key: 'deadline', header: 'Deadline', accessor: (r) => <span className="font-mono text-xs">{r.deadline}</span>, sortable: true, width: '120px' },
];

export const DataTable: StoryObj = {
  render: function Render() {
    const [sortCol, setSortCol] = useState<string>('');
    const [sortDir, setSortDir] = useState<SortDirection>(null);
    const [selected, setSelected] = useState<Set<string>>(new Set());

    const handleSort = (col: string) => {
      if (sortCol === col) {
        setSortDir(sortDir === 'asc' ? 'desc' : sortDir === 'desc' ? null : 'asc');
        if (sortDir === 'desc') setSortCol('');
      } else {
        setSortCol(col);
        setSortDir('asc');
      }
    };

    return (
      <div className="space-y-0">
        <AtomyQTable
          columns={rfqColumns}
          data={rfqs}
          rowKey={(r) => r.id}
          sortColumn={sortCol}
          sortDirection={sortDir}
          onSort={handleSort}
          selectable
          selectedRows={selected}
          onSelectRow={(key, sel) => {
            const next = new Set(selected);
            sel ? next.add(key) : next.delete(key);
            setSelected(next);
          }}
          onSelectAll={(sel) => setSelected(sel ? new Set(rfqs.map((r) => r.id)) : new Set())}
          stickyHeader
        />
        <AtomyQPagination page={1} totalPages={4} total={32} limit={8} onPageChange={() => {}} onLimitChange={() => {}} />
      </div>
    );
  },
};

export const EmptyTable: StoryObj = {
  render: () => (
    <AtomyQTable
      columns={rfqColumns}
      data={[]}
      rowKey={(r) => r.id}
      emptyMessage="No RFQs found. Create your first RFQ to get started."
    />
  ),
};

export const LoadingTable: StoryObj = {
  render: () => (
    <AtomyQTable columns={rfqColumns} data={[]} rowKey={(r) => r.id} loading />
  ),
};

export const CompactTable: StoryObj = {
  render: () => (
    <AtomyQTable
      columns={rfqColumns}
      data={rfqs.slice(0, 4)}
      rowKey={(r) => r.id}
      compact
      striped
    />
  ),
};

export const Pagination: StoryObj = {
  render: function Render() {
    const [page, setPage] = useState(1);
    const [limit, setLimit] = useState(25);
    return (
      <div className="border border-[var(--aq-border-default)] rounded-lg">
        <AtomyQPagination page={page} totalPages={8} total={200} limit={limit} onPageChange={setPage} onLimitChange={setLimit} />
      </div>
    );
  },
};

export const KPICards: StoryObj = {
  name: 'KPI Cards',
  render: () => {
    const icons = [FileText, TrendingUp, Clock, Sparkles];
    return (
      <div className="grid grid-cols-4 gap-4">
        {kpiMetrics.map((metric, i) => {
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
    );
  },
};
