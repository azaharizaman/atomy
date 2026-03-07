import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { AtomyQStatAlt } from './AtomyQStatAlt';
import {
  AtomyQDataTableAlt,
  type ColumnDefAlt,
  type SortDirectionAlt,
} from './AtomyQDataTableAlt';
import { AtomyQSummaryCardAlt } from './AtomyQSummaryCardAlt';
import { AtomyQMetricBadgeAlt } from '../basic/AtomyQMetricBadgeAlt';
import { AtomyQStatusBadgeAlt } from '../basic/AtomyQStatusBadgeAlt';
import {
  TrendingUp,
  Sparkles,
  DollarSign,
  Users,
  Target,
} from 'lucide-react';

const meta: Meta = {
  title: 'Components/Data/Data Alt',
  parameters: { layout: 'padded' },
};

export default meta;

export const StatCards: StoryObj = {
  name: 'Stat Cards Alt',
  render: () => (
    <div className="grid grid-cols-5 gap-3">
      <AtomyQStatAlt label="Policies Active" value="12" tone="brand" />
      <AtomyQStatAlt label="Compliant" value="98%" tone="success" />
      <AtomyQStatAlt label="Pending Review" value="3" tone="warning" />
      <AtomyQStatAlt label="Violations" value="1" tone="danger" />
      <AtomyQStatAlt label="Templates" value="8" tone="neutral" />
    </div>
  ),
};

interface SampleRow {
  id: string;
  title: string;
  status: string;
  statusTone: 'success' | 'warning' | 'danger' | 'info' | 'neutral';
  owner: string;
  budget: number;
  vendors: number;
  deadline: string;
}

const sampleData: SampleRow[] = [
  { id: 'RFQ-2024-0042', title: 'Office Supplies Q4', status: 'Open', statusTone: 'info', owner: 'Sarah Chen', budget: 125000, vendors: 5, deadline: '2024-04-15' },
  { id: 'RFQ-2024-0041', title: 'IT Equipment Refresh', status: 'Awarded', statusTone: 'success', owner: 'James Lee', budget: 450000, vendors: 3, deadline: '2024-03-28' },
  { id: 'RFQ-2024-0040', title: 'Cleaning Services', status: 'Draft', statusTone: 'neutral', owner: 'Aisha Patel', budget: 85000, vendors: 7, deadline: '2024-05-01' },
  { id: 'RFQ-2024-0039', title: 'Raw Materials - Steel', status: 'Open', statusTone: 'info', owner: 'Sarah Chen', budget: 1200000, vendors: 4, deadline: '2024-04-10' },
  { id: 'RFQ-2024-0038', title: 'Catering Contract', status: 'Cancelled', statusTone: 'danger', owner: 'Mike Tan', budget: 95000, vendors: 6, deadline: '2024-03-20' },
];

const columns: ColumnDefAlt<SampleRow>[] = [
  {
    key: 'id',
    header: 'RFQ ID',
    accessor: (r) => <span className="font-mono text-xs">{r.id}</span>,
    sortable: true,
    width: '150px',
  },
  {
    key: 'title',
    header: 'Title',
    accessor: (r) => (
      <span className="font-medium text-[var(--aq-text-primary)]">{r.title}</span>
    ),
    sortable: true,
  },
  {
    key: 'status',
    header: 'Status',
    accessor: (r) => (
      <AtomyQStatusBadgeAlt tone={r.statusTone} dot>
        {r.status}
      </AtomyQStatusBadgeAlt>
    ),
    width: '120px',
  },
  {
    key: 'owner',
    header: 'Owner',
    accessor: (r) => r.owner,
    width: '130px',
  },
  {
    key: 'vendors',
    header: 'Vendors',
    accessor: (r) => r.vendors,
    align: 'center',
    width: '80px',
  },
  {
    key: 'budget',
    header: 'Budget',
    accessor: (r) => (
      <span className="font-mono text-xs">
        RM {r.budget.toLocaleString()}
      </span>
    ),
    align: 'right',
    sortable: true,
    width: '140px',
  },
  {
    key: 'deadline',
    header: 'Deadline',
    accessor: (r) => <span className="font-mono text-xs">{r.deadline}</span>,
    sortable: true,
    width: '120px',
  },
];

export const DataTable: StoryObj = {
  name: 'Data Table Alt',
  render: function Render() {
    const [sortCol, setSortCol] = useState('');
    const [sortDir, setSortDir] = useState<SortDirectionAlt>(null);
    const [selected, setSelected] = useState<Set<string>>(new Set());

    const handleSort = (col: string) => {
      if (sortCol === col) {
        setSortDir(
          sortDir === 'asc' ? 'desc' : sortDir === 'desc' ? null : 'asc',
        );
        if (sortDir === 'desc') setSortCol('');
      } else {
        setSortCol(col);
        setSortDir('asc');
      }
    };

    return (
      <AtomyQDataTableAlt
        columns={columns}
        data={sampleData}
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
        onSelectAll={(sel) =>
          setSelected(sel ? new Set(sampleData.map((r) => r.id)) : new Set())
        }
        stickyHeader
      />
    );
  },
};

export const LoadingTable: StoryObj = {
  name: 'Loading Table Alt',
  render: () => (
    <AtomyQDataTableAlt
      columns={columns}
      data={[]}
      rowKey={(r) => r.id}
      loading
    />
  ),
};

export const SummaryCards: StoryObj = {
  name: 'Summary Cards Alt',
  render: () => (
    <div className="grid grid-cols-6 gap-3">
      <AtomyQSummaryCardAlt
        label="AI Recommendation"
        value="Vendor B"
        description="Best overall score across price, quality, and delivery"
        icon={<Sparkles className="size-4" />}
        hero
        className="col-span-2"
      />
      <AtomyQSummaryCardAlt
        label="Total Spend"
        value="RM 1.2M"
        description="Across all line items"
        icon={<DollarSign className="size-3.5" />}
      />
      <AtomyQSummaryCardAlt
        label="Vendors"
        value="5"
        description="3 qualified"
        icon={<Users className="size-3.5" />}
      />
      <AtomyQSummaryCardAlt
        label="Savings"
        value="18.4%"
        description="vs. last contract"
        icon={<TrendingUp className="size-3.5" />}
      />
      <AtomyQSummaryCardAlt
        label="Risk Score"
        value="Low"
        description="All vendors compliant"
        icon={<Target className="size-3.5" />}
      />
    </div>
  ),
};

export const MetricBadges: StoryObj = {
  name: 'Metric Badges Alt',
  render: () => (
    <div className="flex flex-wrap items-center gap-3 p-4">
      <AtomyQMetricBadgeAlt value="RM 45,200" tone="best" />
      <AtomyQMetricBadgeAlt value="RM 52,100" tone="neutral" />
      <AtomyQMetricBadgeAlt value="RM 68,900" tone="worst" />
      <AtomyQMetricBadgeAlt value="RM 51,400" tone="warning" />
    </div>
  ),
};
