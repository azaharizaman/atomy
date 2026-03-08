import type { Meta, StoryObj } from '@storybook/react-vite';
import { fn, expect, within, userEvent } from 'storybook/test';
import { useState } from 'react';
import { AtomyQTable, type AtomyQColumnDef, type SortDirection } from './AtomyQTable';
import { AtomyQPagination } from './AtomyQPagination';
import { AtomyQKPICard } from './AtomyQKPICard';
import { AtomyQBadge } from '../basic/AtomyQBadge';
import { rfqs, type RFQ, kpiMetrics } from '@/data/mockData';
import { FileText, TrendingUp, Clock, Sparkles, DollarSign, Users, BarChart3 } from 'lucide-react';

const meta: Meta = {
  title: 'Components/Data/Data Components',
  parameters: {
    layout: 'padded',
    docs: {
      description: {
        component: 'Data display components for tables, pagination, and KPI cards. Supports sorting, selection, and various display modes.',
      },
    },
  },
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

// ==================== TABLE STORIES ====================

export const DataTable: StoryObj<typeof AtomyQTable> = {
  render: function Render(args) {
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
      args.onSort?.(col);
    };

    return (
      <div className="space-y-0">
        <AtomyQTable
          {...args}
          columns={rfqColumns}
          data={rfqs}
          rowKey={(r) => r.id}
          sortColumn={sortCol}
          sortDirection={sortDir}
          onSort={handleSort}
          selectable={args.selectable}
          selectedRows={selected}
          onSelectRow={(key, sel) => {
            const next = new Set(selected);
            sel ? next.add(key) : next.delete(key);
            setSelected(next);
            args.onSelectRow?.(key, sel);
          }}
          onSelectAll={(sel) => {
            setSelected(sel ? new Set(rfqs.map((r) => r.id)) : new Set());
            args.onSelectAll?.(sel);
          }}
          stickyHeader
        />
        <AtomyQPagination page={1} totalPages={4} total={32} limit={8} onPageChange={fn()} onLimitChange={fn()} />
      </div>
    );
  },
  args: {
    selectable: true,
    striped: false,
    compact: false,
    stickyHeader: true,
    onSort: fn(),
    onSelectRow: fn(),
    onSelectAll: fn(),
    onRowClick: fn(),
  },
  argTypes: {
    selectable: { control: 'boolean', description: 'Enable row selection with checkboxes' },
    striped: { control: 'boolean', description: 'Alternate row background colors' },
    compact: { control: 'boolean', description: 'Reduced padding for compact display' },
    stickyHeader: { control: 'boolean', description: 'Sticky header on scroll' },
    loading: { control: 'boolean', description: 'Show loading skeleton' },
  },
  play: async ({ canvasElement, args }) => {
    const canvas = within(canvasElement);
    const table = canvas.getByRole('table');
    
    await expect(table).toBeInTheDocument();
    
    const headerCheckbox = canvas.getAllByRole('checkbox')[0];
    await userEvent.click(headerCheckbox);
    await expect(args.onSelectAll).toHaveBeenCalledWith(true);
  },
};

export const SortableTable: StoryObj = {
  name: 'Sortable Table',
  render: function Render() {
    const [sortCol, setSortCol] = useState<string>('budget');
    const [sortDir, setSortDir] = useState<SortDirection>('desc');
    const handleSort = fn();

    const handleSortClick = (col: string) => {
      if (sortCol === col) {
        setSortDir(sortDir === 'asc' ? 'desc' : sortDir === 'desc' ? null : 'asc');
        if (sortDir === 'desc') setSortCol('');
      } else {
        setSortCol(col);
        setSortDir('asc');
      }
      handleSort(col);
    };

    return (
      <div>
        <p className="text-sm text-[var(--aq-text-muted)] mb-3">
          Sorted by: <span className="font-medium">{sortCol || 'none'}</span> 
          {sortDir && <span> ({sortDir})</span>}
        </p>
        <AtomyQTable
          columns={rfqColumns}
          data={rfqs}
          rowKey={(r) => r.id}
          sortColumn={sortCol}
          sortDirection={sortDir}
          onSort={handleSortClick}
        />
      </div>
    );
  },
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);
    
    const budgetHeader = canvas.getByText('Budget');
    await userEvent.click(budgetHeader);
  },
};

export const EmptyTable: StoryObj = {
  render: (args) => (
    <AtomyQTable
      columns={rfqColumns}
      data={[]}
      rowKey={(r) => r.id}
      emptyMessage={args.emptyMessage}
    />
  ),
  args: {
    emptyMessage: 'No RFQs found. Create your first RFQ to get started.',
  },
  argTypes: {
    emptyMessage: { control: 'text', description: 'Message to display when table is empty' },
  },
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

// ==================== PAGINATION STORIES ====================

export const Pagination: StoryObj<typeof AtomyQPagination> = {
  render: function Render(args) {
    const [page, setPage] = useState(args.page ?? 1);
    const [limit, setLimit] = useState(args.limit ?? 25);
    
    return (
      <div className="border border-[var(--aq-border-default)] rounded-lg">
        <AtomyQPagination 
          {...args}
          page={page} 
          totalPages={args.totalPages ?? 8} 
          total={args.total ?? 200} 
          limit={limit} 
          onPageChange={(p) => {
            setPage(p);
            args.onPageChange?.(p);
          }} 
          onLimitChange={(l) => {
            setLimit(l);
            args.onLimitChange?.(l);
          }} 
        />
      </div>
    );
  },
  args: {
    page: 1,
    totalPages: 8,
    total: 200,
    limit: 25,
    onPageChange: fn(),
    onLimitChange: fn(),
  },
  argTypes: {
    page: { control: { type: 'number', min: 1 }, description: 'Current page number' },
    totalPages: { control: { type: 'number', min: 1 }, description: 'Total number of pages' },
    total: { control: 'number', description: 'Total number of items' },
    limit: { 
      control: 'select', 
      options: [10, 25, 50, 100], 
      description: 'Items per page' 
    },
  },
  play: async ({ canvasElement, args }) => {
    const canvas = within(canvasElement);
    
    const nextButton = canvas.getByRole('button', { name: /next/i });
    await userEvent.click(nextButton);
    await expect(args.onPageChange).toHaveBeenCalledWith(2);
  },
};

export const PaginationStates: StoryObj = {
  name: 'Pagination States',
  render: () => (
    <div className="space-y-4">
      <div>
        <p className="text-xs font-semibold uppercase tracking-wider text-[var(--aq-text-muted)] mb-2">First Page</p>
        <div className="border border-[var(--aq-border-default)] rounded-lg">
          <AtomyQPagination page={1} totalPages={10} total={250} limit={25} onPageChange={fn()} onLimitChange={fn()} />
        </div>
      </div>
      <div>
        <p className="text-xs font-semibold uppercase tracking-wider text-[var(--aq-text-muted)] mb-2">Middle Page</p>
        <div className="border border-[var(--aq-border-default)] rounded-lg">
          <AtomyQPagination page={5} totalPages={10} total={250} limit={25} onPageChange={fn()} onLimitChange={fn()} />
        </div>
      </div>
      <div>
        <p className="text-xs font-semibold uppercase tracking-wider text-[var(--aq-text-muted)] mb-2">Last Page</p>
        <div className="border border-[var(--aq-border-default)] rounded-lg">
          <AtomyQPagination page={10} totalPages={10} total={250} limit={25} onPageChange={fn()} onLimitChange={fn()} />
        </div>
      </div>
      <div>
        <p className="text-xs font-semibold uppercase tracking-wider text-[var(--aq-text-muted)] mb-2">Single Page</p>
        <div className="border border-[var(--aq-border-default)] rounded-lg">
          <AtomyQPagination page={1} totalPages={1} total={15} limit={25} onPageChange={fn()} onLimitChange={fn()} />
        </div>
      </div>
    </div>
  ),
};

// ==================== KPI CARD STORIES ====================

export const KPICard: StoryObj<typeof AtomyQKPICard> = {
  render: (args) => (
    <div className="w-[280px]">
      <AtomyQKPICard {...args} />
    </div>
  ),
  args: {
    label: 'Active RFQs',
    value: '24',
    change: '+12%',
    trend: 'up',
    period: 'vs last month',
    icon: <FileText className="size-4" />,
  },
  argTypes: {
    label: { control: 'text', description: 'KPI metric label' },
    value: { control: 'text', description: 'Main value to display' },
    change: { control: 'text', description: 'Change percentage or amount' },
    trend: { 
      control: 'select', 
      options: ['up', 'down', 'neutral'], 
      description: 'Trend direction' 
    },
    period: { control: 'text', description: 'Comparison period text' },
  },
};

export const KPICards: StoryObj = {
  name: 'KPI Cards Grid',
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

export const KPICardVariants: StoryObj = {
  name: 'KPI Card Variants',
  render: () => (
    <div className="grid grid-cols-3 gap-4">
      <AtomyQKPICard
        label="Total Revenue"
        value="RM 2.4M"
        change="+15.3%"
        trend="up"
        period="vs last quarter"
        icon={<DollarSign className="size-4" />}
      />
      <AtomyQKPICard
        label="Active Vendors"
        value="156"
        change="-2"
        trend="down"
        period="this month"
        icon={<Users className="size-4" />}
      />
      <AtomyQKPICard
        label="Avg Response Time"
        value="2.3 days"
        change="0%"
        trend="neutral"
        period="unchanged"
        icon={<Clock className="size-4" />}
      />
      <AtomyQKPICard
        label="Completion Rate"
        value="94.2%"
        change="+3.1%"
        trend="up"
        period="vs target"
        icon={<BarChart3 className="size-4" />}
      />
      <AtomyQKPICard
        label="AI Confidence"
        value="92/100"
        change="+5"
        trend="up"
        period="improved"
        icon={<Sparkles className="size-4" />}
      />
      <AtomyQKPICard
        label="Pending Approvals"
        value="7"
        change="+3"
        trend="up"
        period="requires attention"
        icon={<Clock className="size-4" />}
      />
    </div>
  ),
};

// ==================== COMPLETE DATA DISPLAY DEMO ====================

export const DataDisplayDemo: StoryObj = {
  name: 'Complete Data Display',
  render: function Render() {
    const [sortCol, setSortCol] = useState<string>('');
    const [sortDir, setSortDir] = useState<SortDirection>(null);
    const [page, setPage] = useState(1);
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
      <div className="space-y-6">
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

        <div className="bg-white rounded-lg border border-[var(--aq-border-default)] overflow-hidden">
          <div className="px-4 py-3 border-b border-[var(--aq-border-default)] flex items-center justify-between">
            <div>
              <h3 className="font-medium text-[var(--aq-text-primary)]">RFQ List</h3>
              <p className="text-sm text-[var(--aq-text-muted)]">
                {selected.size > 0 ? `${selected.size} selected` : '32 total RFQs'}
              </p>
            </div>
          </div>
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
          />
          <AtomyQPagination 
            page={page} 
            totalPages={4} 
            total={32} 
            limit={8} 
            onPageChange={setPage} 
            onLimitChange={fn()} 
          />
        </div>
      </div>
    );
  },
};
