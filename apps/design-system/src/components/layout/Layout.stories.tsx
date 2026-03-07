import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import { AtomyQPageLayoutAlt } from './AtomyQPageLayoutAlt';
import { AtomyQPanelAlt } from './AtomyQPanelAlt';
import { AtomyQSlideOverAlt } from './AtomyQSlideOverAlt';
import { AtomyQSplitLayoutAlt } from './AtomyQSplitLayoutAlt';
import { Plus, Settings } from 'lucide-react';

const meta: Meta = {
  title: 'Components/Layout/Layout Alt',
  parameters: { layout: 'fullscreen' },
};

export default meta;

export const PageLayout: StoryObj = {
  name: 'Page Layout Alt',
  render: () => (
    <AtomyQPageLayoutAlt
      title="Request for Quotations"
      subtitle="Manage and track all your procurement requests in one place."
      actions={
        <>
          <button className="flex h-9 items-center gap-1.5 rounded-lg border border-[var(--aq-border-strong)] bg-[var(--aq-bg-surface)] px-3 text-[13px] font-medium text-[var(--aq-text-secondary)]">
            <Settings className="size-3.5" />
            Settings
          </button>
          <button className="flex h-9 items-center gap-1.5 rounded-lg bg-[var(--aq-brand-600)] px-4 text-[13px] font-medium text-white">
            <Plus className="size-3.5" />
            New RFQ
          </button>
        </>
      }
    >
      <div className="grid grid-cols-3 gap-4">
        {Array.from({ length: 3 }).map((_, i) => (
          <div
            key={i}
            className="h-40 rounded-xl border border-[var(--aq-border-strong)] bg-[var(--aq-bg-surface)] p-4 text-[13px] text-[var(--aq-text-muted)]"
          >
            Content area {i + 1}
          </div>
        ))}
      </div>
    </AtomyQPageLayoutAlt>
  ),
};

export const Panel: StoryObj = {
  name: 'Panel Alt',
  render: () => (
    <div className="space-y-4 p-6">
      <AtomyQPanelAlt
        title="Active Policies"
        subtitle="Configure threshold and scoring rules"
        right={
          <button className="rounded-lg border border-[var(--aq-border-strong)] bg-[var(--aq-bg-surface)] px-3 py-1.5 text-[12px] font-medium text-[var(--aq-text-secondary)]">
            Edit
          </button>
        }
      >
        <div className="space-y-2">
          {['Price weight: 40%', 'Quality weight: 30%', 'Delivery weight: 30%'].map((text) => (
            <div
              key={text}
              className="rounded-lg border border-[var(--aq-border-default)] bg-[var(--aq-bg-elevated)] p-3 text-[13px] text-[var(--aq-text-secondary)]"
            >
              {text}
            </div>
          ))}
        </div>
      </AtomyQPanelAlt>

      <AtomyQPanelAlt title="No-padding panel" noPadding>
        <div className="border-t border-[var(--aq-border-default)] p-4 text-[13px] text-[var(--aq-text-muted)]">
          Content without panel padding — useful for tables.
        </div>
      </AtomyQPanelAlt>
    </div>
  ),
};

export const SlideOver: StoryObj = {
  name: 'Slide Over Alt',
  render: function Render() {
    const [open, setOpen] = useState(false);
    return (
      <div className="p-6">
        <button
          onClick={() => setOpen(true)}
          className="rounded-lg bg-[var(--aq-brand-600)] px-4 py-2 text-[13px] font-medium text-white"
        >
          Open Slide Over
        </button>
        <AtomyQSlideOverAlt
          open={open}
          onClose={() => setOpen(false)}
          title="Edit Policy"
          subtitle="Modify scoring weights and thresholds"
        >
          <div className="space-y-4">
            <div className="rounded-lg border border-[var(--aq-border-default)] bg-[var(--aq-bg-elevated)] p-4 text-[13px] text-[var(--aq-text-secondary)]">
              Slide-over content goes here. Supports Escape key and backdrop click to close.
            </div>
            <div className="rounded-lg border border-[var(--aq-border-default)] bg-[var(--aq-bg-elevated)] p-4 text-[13px] text-[var(--aq-text-secondary)]">
              Scrollable content area with auto overflow handling.
            </div>
          </div>
        </AtomyQSlideOverAlt>
      </div>
    );
  },
};

export const SplitLayout: StoryObj = {
  name: 'Split Layout Alt',
  render: () => (
    <AtomyQSplitLayoutAlt
      brandName="AtomyQ"
      tagline="Intelligent Procurement Platform"
      headline="Smarter sourcing decisions, powered by AI"
      description="Streamline your procurement workflow with automated quote analysis, vendor scoring, and approval routing."
      features={[
        { text: 'AI-powered quote comparison' },
        { text: 'Automated vendor scoring' },
        { text: 'Smart approval workflows' },
        { text: 'Real-time compliance monitoring' },
      ]}
      stats={[
        { value: '40%', label: 'Cost Savings' },
        { value: '3x', label: 'Faster Processing' },
        { value: '99.9%', label: 'Uptime' },
      ]}
    >
      <div className="space-y-4">
        <h2
          className="text-[22px] font-bold text-[var(--aq-text-primary)]"
          style={{ letterSpacing: '-0.02em' }}
        >
          Sign in to your account
        </h2>
        <div className="space-y-3">
          <div>
            <label className="mb-1 block text-[12px] font-medium tracking-wide text-[var(--aq-text-muted)]">
              Email
            </label>
            <input
              type="email"
              placeholder="you@company.com"
              className="h-[42px] w-full rounded-lg border border-[var(--aq-border-strong)] bg-[var(--aq-bg-surface)] px-3.5 text-sm text-[var(--aq-text-primary)] focus:border-[var(--aq-brand-500)] focus:outline-none"
            />
          </div>
          <div>
            <label className="mb-1 block text-[12px] font-medium tracking-wide text-[var(--aq-text-muted)]">
              Password
            </label>
            <input
              type="password"
              placeholder="Enter your password"
              className="h-[42px] w-full rounded-lg border border-[var(--aq-border-strong)] bg-[var(--aq-bg-surface)] px-3.5 text-sm text-[var(--aq-text-primary)] focus:border-[var(--aq-brand-500)] focus:outline-none"
            />
          </div>
          <button className="h-[42px] w-full rounded-lg bg-[var(--aq-brand-600)] text-sm font-medium text-white">
            Sign In
          </button>
        </div>
      </div>
    </AtomyQSplitLayoutAlt>
  ),
};
