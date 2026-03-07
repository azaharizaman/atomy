import type { Meta, StoryObj } from '@storybook/react';
import { useState } from 'react';
import { AtomyQSidebar } from './AtomyQSidebar';
import { AtomyQBreadcrumb } from './AtomyQBreadcrumb';
import { AtomyQTabs, AtomyQTabContent } from './AtomyQTabs';
import { AtomyQStepper } from './AtomyQStepper';
import { navigationItems, workflowSteps } from '@/data/mockData';

const meta: Meta = {
  title: 'Components/Navigation/Navigation Components',
  parameters: { layout: 'padded' },
};

export default meta;

export const Sidebar: StoryObj = {
  render: function Render() {
    const [collapsed, setCollapsed] = useState(false);
    const [active, setActive] = useState('/');

    return (
      <div className="flex h-[600px] border border-[var(--aq-border-default)] rounded-lg overflow-hidden">
        <AtomyQSidebar
          sections={navigationItems}
          collapsed={collapsed}
          onToggleCollapse={() => setCollapsed(!collapsed)}
          activeHref={active}
          onNavigate={setActive}
        />
        <div className="flex-1 bg-[var(--aq-bg-canvas)] p-6">
          <p className="text-sm text-[var(--aq-text-muted)]">Active route: <span className="font-mono">{active}</span></p>
          <p className="text-sm text-[var(--aq-text-muted)] mt-2">Sidebar collapsed: <span className="font-mono">{String(collapsed)}</span></p>
        </div>
      </div>
    );
  },
  parameters: { layout: 'fullscreen' },
};

export const CollapsedSidebar: StoryObj = {
  render: function Render() {
    return (
      <div className="flex h-[400px] border border-[var(--aq-border-default)] rounded-lg overflow-hidden">
        <AtomyQSidebar
          sections={navigationItems}
          collapsed={true}
          activeHref="/rfqs"
        />
        <div className="flex-1 bg-[var(--aq-bg-canvas)] p-6">
          <p className="text-sm text-[var(--aq-text-muted)]">Sidebar in collapsed (icon-only) mode</p>
        </div>
      </div>
    );
  },
  parameters: { layout: 'fullscreen' },
};

export const Breadcrumbs: StoryObj = {
  render: () => (
    <div className="space-y-4">
      <AtomyQBreadcrumb items={[{ label: 'RFQs', href: '/rfqs' }, { label: 'RFQ-2026-001' }]} />
      <AtomyQBreadcrumb items={[
        { label: 'RFQs', href: '/rfqs' },
        { label: 'RFQ-2026-001', href: '/rfqs/001' },
        { label: 'Comparison Results' },
      ]} />
      <AtomyQBreadcrumb items={[
        { label: 'Administration', href: '/admin' },
        { label: 'Users & Roles', href: '/admin/users' },
        { label: 'Sarah Chen' },
      ]} />
    </div>
  ),
};

export const Tabs: StoryObj = {
  render: function Render() {
    return (
      <div className="space-y-8">
        <div>
          <p className="text-xs font-semibold uppercase tracking-wider text-[var(--aq-text-muted)] mb-3">Default Tabs</p>
          <AtomyQTabs
            items={[
              { value: 'details', label: 'Details' },
              { value: 'quotes', label: 'Quotes', badge: '4' },
              { value: 'comparison', label: 'Comparison' },
              { value: 'history', label: 'History' },
            ]}
            defaultValue="details"
          >
            <AtomyQTabContent value="details">
              <p className="text-sm text-[var(--aq-text-muted)]">RFQ details content...</p>
            </AtomyQTabContent>
            <AtomyQTabContent value="quotes">
              <p className="text-sm text-[var(--aq-text-muted)]">Quotes list content...</p>
            </AtomyQTabContent>
          </AtomyQTabs>
        </div>

        <div>
          <p className="text-xs font-semibold uppercase tracking-wider text-[var(--aq-text-muted)] mb-3">Pill Tabs</p>
          <AtomyQTabs
            items={[
              { value: 'all', label: 'All' },
              { value: 'open', label: 'Open', badge: '5' },
              { value: 'draft', label: 'Draft', badge: '3' },
              { value: 'closed', label: 'Closed' },
            ]}
            defaultValue="all"
            variant="pills"
          />
        </div>

        <div>
          <p className="text-xs font-semibold uppercase tracking-wider text-[var(--aq-text-muted)] mb-3">Underline Tabs</p>
          <AtomyQTabs
            items={[
              { value: 'overview', label: 'Overview' },
              { value: 'vendors', label: 'Vendors' },
              { value: 'scoring', label: 'Scoring' },
              { value: 'evidence', label: 'Evidence' },
            ]}
            defaultValue="overview"
            variant="underline"
          />
        </div>
      </div>
    );
  },
};

export const StepperHorizontal: StoryObj = {
  name: 'Stepper (Horizontal)',
  render: () => (
    <div className="max-w-2xl">
      <AtomyQStepper steps={workflowSteps} orientation="horizontal" />
    </div>
  ),
};

export const StepperVertical: StoryObj = {
  name: 'Stepper (Vertical)',
  render: () => (
    <div className="max-w-sm">
      <AtomyQStepper steps={workflowSteps} orientation="vertical" />
    </div>
  ),
};
