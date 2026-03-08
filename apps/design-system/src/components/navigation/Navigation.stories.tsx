import type { Meta, StoryObj } from '@storybook/react-vite';
import { fn, expect, within, userEvent } from 'storybook/test';
import { useState } from 'react';
import { AtomyQSidebar } from './AtomyQSidebar';
import { AtomyQBreadcrumb } from './AtomyQBreadcrumb';
import { AtomyQTabs, AtomyQTabContent } from './AtomyQTabs';
import { AtomyQStepper } from './AtomyQStepper';
import { navigationItems, workflowSteps } from '@/data/mockData';

const meta: Meta = {
  title: 'Components/Navigation/Navigation Components',
  parameters: {
    layout: 'padded',
    docs: {
      description: {
        component: 'Navigation components for wayfinding and workflow management. Includes sidebar, breadcrumbs, tabs, and steppers.',
      },
    },
  },
};

export default meta;

// ==================== SIDEBAR STORIES ====================

export const Sidebar: StoryObj<typeof AtomyQSidebar> = {
  render: function Render(args) {
    const [collapsed, setCollapsed] = useState(args.collapsed ?? false);
    const [active, setActive] = useState(args.activeHref ?? '/');

    return (
      <div className="flex h-[600px] border border-[var(--aq-border-default)] rounded-lg overflow-hidden">
        <AtomyQSidebar
          {...args}
          collapsed={collapsed}
          onToggleCollapse={() => {
            setCollapsed(!collapsed);
            args.onToggleCollapse?.();
          }}
          activeHref={active}
          onNavigate={(href) => {
            setActive(href);
            args.onNavigate?.(href);
          }}
        />
        <div className="flex-1 bg-[var(--aq-bg-canvas)] p-6">
          <p className="text-sm text-[var(--aq-text-muted)]">Active route: <span className="font-mono text-[var(--aq-brand-600)]">{active}</span></p>
          <p className="text-sm text-[var(--aq-text-muted)] mt-2">Sidebar collapsed: <span className="font-mono">{String(collapsed)}</span></p>
        </div>
      </div>
    );
  },
  args: {
    sections: navigationItems,
    collapsed: false,
    activeHref: '/',
    onToggleCollapse: fn(),
    onNavigate: fn(),
  },
  argTypes: {
    collapsed: { control: 'boolean', description: 'Collapsed state showing only icons' },
    activeHref: { control: 'text', description: 'Current active navigation href' },
  },
  parameters: { layout: 'fullscreen' },
  play: async ({ canvasElement, args }) => {
    const canvas = within(canvasElement);
    
    const activeRouteText = canvas.getByText(/Active route:/i);
    await expect(activeRouteText).toBeInTheDocument();
  },
};

export const CollapsedSidebar: StoryObj = {
  render: function Render() {
    const [collapsed, setCollapsed] = useState(true);
    const [active, setActive] = useState('/rfqs');
    const handleNavigate = fn();
    
    return (
      <div className="flex h-[500px] border border-[var(--aq-border-default)] rounded-lg overflow-hidden">
        <AtomyQSidebar
          sections={navigationItems}
          collapsed={collapsed}
          onToggleCollapse={() => setCollapsed(!collapsed)}
          activeHref={active}
          onNavigate={(href) => {
            setActive(href);
            handleNavigate(href);
          }}
        />
        <div className="flex-1 bg-[var(--aq-bg-canvas)] p-6">
          <p className="text-sm text-[var(--aq-text-muted)]">Sidebar in collapsed (icon-only) mode</p>
          <p className="text-sm text-[var(--aq-text-muted)] mt-2">Click the expand button to toggle</p>
        </div>
      </div>
    );
  },
  parameters: { layout: 'fullscreen' },
};

// ==================== BREADCRUMB STORIES ====================

export const Breadcrumbs: StoryObj<typeof AtomyQBreadcrumb> = {
  render: (args) => (
    <AtomyQBreadcrumb {...args} />
  ),
  args: {
    items: [
      { label: 'RFQs', href: '/rfqs' },
      { label: 'RFQ-2026-001' },
    ],
  },
  argTypes: {
    items: { 
      control: 'object', 
      description: 'Array of breadcrumb items with label and optional href' 
    },
  },
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);
    const breadcrumb = canvas.getByRole('navigation');
    
    await expect(breadcrumb).toBeInTheDocument();
    await expect(canvas.getByText('RFQs')).toBeInTheDocument();
    await expect(canvas.getByText('RFQ-2026-001')).toBeInTheDocument();
  },
};

export const BreadcrumbVariants: StoryObj = {
  name: 'Breadcrumb Examples',
  render: () => (
    <div className="space-y-4">
      <div>
        <p className="text-xs font-semibold uppercase tracking-wider text-[var(--aq-text-muted)] mb-2">Two levels</p>
        <AtomyQBreadcrumb items={[{ label: 'RFQs', href: '/rfqs' }, { label: 'RFQ-2026-001' }]} />
      </div>
      <div>
        <p className="text-xs font-semibold uppercase tracking-wider text-[var(--aq-text-muted)] mb-2">Three levels</p>
        <AtomyQBreadcrumb items={[
          { label: 'RFQs', href: '/rfqs' },
          { label: 'RFQ-2026-001', href: '/rfqs/001' },
          { label: 'Comparison Results' },
        ]} />
      </div>
      <div>
        <p className="text-xs font-semibold uppercase tracking-wider text-[var(--aq-text-muted)] mb-2">Administration path</p>
        <AtomyQBreadcrumb items={[
          { label: 'Administration', href: '/admin' },
          { label: 'Users & Roles', href: '/admin/users' },
          { label: 'Sarah Chen' },
        ]} />
      </div>
    </div>
  ),
};

// ==================== TABS STORIES ====================

export const Tabs: StoryObj<typeof AtomyQTabs> = {
  render: function Render(args) {
    const [activeTab, setActiveTab] = useState(args.defaultValue ?? 'details');
    const handleTabChange = fn();
    
    return (
      <AtomyQTabs
        {...args}
        defaultValue={activeTab}
        onValueChange={(val) => {
          setActiveTab(val);
          handleTabChange(val);
        }}
      >
        <AtomyQTabContent value="details">
          <p className="text-sm text-[var(--aq-text-muted)]">RFQ details content goes here...</p>
        </AtomyQTabContent>
        <AtomyQTabContent value="quotes">
          <p className="text-sm text-[var(--aq-text-muted)]">Quotes list content goes here...</p>
        </AtomyQTabContent>
        <AtomyQTabContent value="comparison">
          <p className="text-sm text-[var(--aq-text-muted)]">Comparison results content goes here...</p>
        </AtomyQTabContent>
        <AtomyQTabContent value="history">
          <p className="text-sm text-[var(--aq-text-muted)]">Activity history content goes here...</p>
        </AtomyQTabContent>
      </AtomyQTabs>
    );
  },
  args: {
    items: [
      { value: 'details', label: 'Details' },
      { value: 'quotes', label: 'Quotes', badge: '4' },
      { value: 'comparison', label: 'Comparison' },
      { value: 'history', label: 'History' },
    ],
    defaultValue: 'details',
    variant: 'default',
  },
  argTypes: {
    variant: {
      control: 'select',
      options: ['default', 'pills', 'underline'],
      description: 'Tab style variant',
    },
    items: { control: 'object', description: 'Tab items with value, label, and optional badge' },
    defaultValue: { control: 'text', description: 'Default active tab value' },
  },
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);
    const tabList = canvas.getByRole('tablist');
    
    await expect(tabList).toBeInTheDocument();
    
    const quotesTab = canvas.getByRole('tab', { name: /Quotes/i });
    await userEvent.click(quotesTab);
    await expect(quotesTab).toHaveAttribute('data-state', 'active');
  },
};

export const TabVariants: StoryObj = {
  name: 'Tab Style Variants',
  render: () => (
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
  ),
};

// ==================== STEPPER STORIES ====================

export const StepperHorizontal: StoryObj<typeof AtomyQStepper> = {
  name: 'Stepper (Horizontal)',
  render: (args) => (
    <div className="max-w-2xl">
      <AtomyQStepper {...args} />
    </div>
  ),
  args: {
    steps: workflowSteps,
    orientation: 'horizontal',
  },
  argTypes: {
    orientation: {
      control: 'radio',
      options: ['horizontal', 'vertical'],
      description: 'Stepper orientation',
    },
    steps: { control: 'object', description: 'Array of step objects with label, description, and status' },
  },
};

export const StepperVertical: StoryObj<typeof AtomyQStepper> = {
  name: 'Stepper (Vertical)',
  render: (args) => (
    <div className="max-w-sm">
      <AtomyQStepper {...args} />
    </div>
  ),
  args: {
    steps: workflowSteps,
    orientation: 'vertical',
  },
  argTypes: {
    orientation: {
      control: 'radio',
      options: ['horizontal', 'vertical'],
      description: 'Stepper orientation',
    },
  },
};

export const InteractiveStepper: StoryObj = {
  name: 'Interactive Stepper',
  render: function Render() {
    const [currentStep, setCurrentStep] = useState(1);
    
    const steps = [
      { label: 'Create RFQ', description: 'Fill in basic details', status: currentStep > 0 ? 'completed' : currentStep === 0 ? 'current' : 'upcoming' as const },
      { label: 'Add Items', description: 'Specify line items', status: currentStep > 1 ? 'completed' : currentStep === 1 ? 'current' : 'upcoming' as const },
      { label: 'Invite Vendors', description: 'Select participants', status: currentStep > 2 ? 'completed' : currentStep === 2 ? 'current' : 'upcoming' as const },
      { label: 'Review', description: 'Final review', status: currentStep > 3 ? 'completed' : currentStep === 3 ? 'current' : 'upcoming' as const },
      { label: 'Publish', description: 'Go live', status: currentStep >= 4 ? 'completed' : 'upcoming' as const },
    ];

    return (
      <div className="space-y-6 max-w-2xl">
        <AtomyQStepper steps={steps} orientation="horizontal" />
        <div className="flex gap-2 justify-center">
          <button
            onClick={() => setCurrentStep(Math.max(0, currentStep - 1))}
            disabled={currentStep === 0}
            className="px-3 py-1.5 text-sm rounded-md border border-[var(--aq-border-default)] disabled:opacity-50"
          >
            Previous
          </button>
          <button
            onClick={() => setCurrentStep(Math.min(4, currentStep + 1))}
            disabled={currentStep === 4}
            className="px-3 py-1.5 text-sm rounded-md bg-[var(--aq-brand-600)] text-white disabled:opacity-50"
          >
            Next
          </button>
        </div>
      </div>
    );
  },
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);
    const nextButton = canvas.getByRole('button', { name: /Next/i });
    
    await userEvent.click(nextButton);
    await userEvent.click(nextButton);
  },
};

// ==================== COMBINED NAVIGATION DEMO ====================

export const NavigationDemo: StoryObj = {
  name: 'Full Navigation Demo',
  render: function Render() {
    const [collapsed, setCollapsed] = useState(false);
    const [active, setActive] = useState('/rfqs');
    const [activeTab, setActiveTab] = useState('details');

    return (
      <div className="flex h-[700px] border border-[var(--aq-border-default)] rounded-lg overflow-hidden">
        <AtomyQSidebar
          sections={navigationItems}
          collapsed={collapsed}
          onToggleCollapse={() => setCollapsed(!collapsed)}
          activeHref={active}
          onNavigate={setActive}
        />
        <div className="flex-1 flex flex-col bg-[var(--aq-bg-canvas)]">
          <header className="border-b border-[var(--aq-border-default)] bg-white px-6 py-3">
            <AtomyQBreadcrumb items={[
              { label: 'RFQs', href: '/rfqs' },
              { label: 'RFQ-2026-001', href: '/rfqs/001' },
              { label: 'Details' },
            ]} />
            <h1 className="text-lg font-semibold text-[var(--aq-text-primary)] mt-2">
              Industrial Pumping Equipment
            </h1>
          </header>
          <div className="flex-1 p-6 overflow-auto">
            <AtomyQTabs
              items={[
                { value: 'details', label: 'Details' },
                { value: 'quotes', label: 'Quotes', badge: '4' },
                { value: 'comparison', label: 'Comparison' },
                { value: 'history', label: 'History' },
              ]}
              defaultValue={activeTab}
              onValueChange={setActiveTab}
            >
              <AtomyQTabContent value="details">
                <div className="text-sm text-[var(--aq-text-muted)] space-y-4">
                  <p>RFQ details would go here...</p>
                  <AtomyQStepper steps={workflowSteps} orientation="horizontal" />
                </div>
              </AtomyQTabContent>
              <AtomyQTabContent value="quotes">
                <p className="text-sm text-[var(--aq-text-muted)]">Vendor quotes list...</p>
              </AtomyQTabContent>
            </AtomyQTabs>
          </div>
        </div>
      </div>
    );
  },
  parameters: { layout: 'fullscreen' },
};
