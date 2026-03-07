import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { AtomyQHeaderAlt } from './AtomyQHeaderAlt';
import { AtomyQSidebarAlt, type NavSectionAlt } from './AtomyQSidebarAlt';
import { AtomyQFilterBarAlt, type FilterOptionAlt } from './AtomyQFilterBarAlt';
import { AtomyQPaginationAlt } from './AtomyQPaginationAlt';
import { AtomyQSectionHeaderAlt } from './AtomyQSectionHeaderAlt';
import { Plus, Download, ListFilter } from 'lucide-react';

const meta: Meta = {
  title: 'Components/Navigation/Navigation Alt',
  parameters: { layout: 'fullscreen' },
};

export default meta;

export const Header: StoryObj = {
  name: 'Header Alt',
  render: function Render() {
    const [search, setSearch] = useState('');
    return (
      <AtomyQHeaderAlt
        breadcrumbs={[
          { label: 'Dashboard', href: '/' },
          { label: 'RFQs', href: '/rfqs' },
          { label: 'RFQ-2024-0042' },
        ]}
        onBreadcrumbClick={() => {}}
        searchValue={search}
        onSearchChange={setSearch}
        searchPlaceholder="Search RFQs..."
        quickActions={[
          {
            label: 'Export',
            onClick: () => {},
            variant: 'secondary',
            icon: <Download className="size-3.5" />,
          },
          {
            label: 'New RFQ',
            onClick: () => {},
            variant: 'primary',
            icon: <Plus className="size-3.5" />,
          },
        ]}
        showAIButton
        onAIClick={() => {}}
        notificationCount={3}
        onNotificationClick={() => {}}
        userName="Sarah Chen"
        userInitials="SC"
      />
    );
  },
};

const sampleSections: NavSectionAlt[] = [
  {
    section: 'Workspace',
    items: [
      { label: 'Dashboard', icon: 'LayoutDashboard', href: '/dashboard' },
      { label: 'RFQs', icon: 'FileText', href: '/rfqs', badge: '12' },
      { label: 'Quote Intake', icon: 'Inbox', href: '/quote-intake', badge: '5' },
      { label: 'Comparisons', icon: 'GitCompareArrows', href: '/comparisons' },
    ],
  },
  {
    section: 'Workflow',
    items: [
      { label: 'Approvals', icon: 'CheckSquare', href: '/approvals', badge: '3' },
      { label: 'Negotiations', icon: 'MessageSquare', href: '/negotiations' },
      { label: 'Awards', icon: 'Trophy', href: '/awards' },
    ],
  },
  {
    section: 'Analytics',
    items: [
      { label: 'Reports', icon: 'BarChart3', href: '/reports' },
      { label: 'Risk & Compliance', icon: 'Shield', href: '/risk' },
      { label: 'Decision Trail', icon: 'History', href: '/decision-trail' },
    ],
  },
];

export const Sidebar: StoryObj = {
  name: 'Sidebar Alt',
  render: function Render() {
    const [collapsed, setCollapsed] = useState(false);
    const [active, setActive] = useState('/rfqs');
    return (
      <div className="flex h-[600px]">
        <AtomyQSidebarAlt
          sections={sampleSections}
          collapsed={collapsed}
          onToggleCollapse={() => setCollapsed(!collapsed)}
          activeHref={active}
          onNavigate={setActive}
          brandName="AtomyQ"
        />
        <div className="flex-1 bg-[var(--aq-bg-canvas)] p-6 text-[13px] text-[var(--aq-text-muted)]">
          Main content area — sidebar is {collapsed ? 'collapsed' : 'expanded'}
        </div>
      </div>
    );
  },
};

const statusFilters: FilterOptionAlt[] = [
  { value: 'all', label: 'All', count: 32 },
  { value: 'open', label: 'Open', count: 12 },
  { value: 'draft', label: 'Draft', count: 8 },
  { value: 'awarded', label: 'Awarded', count: 7 },
  { value: 'closed', label: 'Closed', count: 5 },
];

export const FilterBar: StoryObj = {
  name: 'Filter Bar Alt',
  render: function Render() {
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('all');
    const [owner, setOwner] = useState('all');
    return (
      <div className="p-6">
        <AtomyQFilterBarAlt
          searchValue={search}
          onSearchChange={setSearch}
          searchPlaceholder="Search by RFQ ID, title, or vendor..."
          statusFilters={statusFilters}
          activeStatus={status}
          onStatusChange={setStatus}
          dropdowns={[
            {
              label: 'Owner',
              value: owner,
              options: [
                { value: 'all', label: 'All Owners' },
                { value: 'me', label: 'Assigned to Me' },
                { value: 'team', label: 'My Team' },
              ],
              onChange: setOwner,
            },
          ]}
          actions={
            <button className="flex h-9 items-center gap-1.5 rounded-lg border border-[var(--aq-border-strong)] bg-[var(--aq-bg-surface)] px-3 text-[12px] font-medium text-[var(--aq-text-secondary)]">
              <ListFilter className="size-3.5" />
              More Filters
            </button>
          }
        />
      </div>
    );
  },
};

export const Pagination: StoryObj = {
  name: 'Pagination Alt',
  render: function Render() {
    const [page, setPage] = useState(1);
    const [limit, setLimit] = useState(25);
    return (
      <div className="border border-[var(--aq-border-default)] rounded-xl m-6">
        <AtomyQPaginationAlt
          page={page}
          totalPages={8}
          total={200}
          limit={limit}
          onPageChange={setPage}
          onLimitChange={setLimit}
        />
      </div>
    );
  },
};

export const SectionHeaders: StoryObj = {
  name: 'Section Header Alt',
  render: () => (
    <div className="space-y-6 p-6">
      <AtomyQSectionHeaderAlt
        title="Active Tasks"
        action={{ label: 'View All', onClick: () => {} }}
      />
      <AtomyQSectionHeaderAlt
        title="Recent Comparisons"
        action={{ label: 'See More', onClick: () => {} }}
        icon={<ListFilter className="size-3" />}
      />
      <AtomyQSectionHeaderAlt title="Risk Alerts" />
    </div>
  ),
};
