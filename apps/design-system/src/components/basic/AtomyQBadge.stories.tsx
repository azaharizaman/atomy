import type { Meta, StoryObj } from '@storybook/react';
import { AtomyQBadge } from './AtomyQBadge';

const meta: Meta<typeof AtomyQBadge> = {
  title: 'Components/Basic/AtomyQBadge',
  component: AtomyQBadge,
  parameters: { layout: 'centered' },
  tags: ['autodocs'],
  argTypes: {
    variant: {
      control: 'select',
      options: ['default', 'secondary', 'success', 'warning', 'danger', 'info', 'outline', 'neutral'],
    },
    size: { control: 'select', options: ['sm', 'md', 'lg'] },
    dot: { control: 'boolean' },
  },
};

export default meta;
type Story = StoryObj<typeof AtomyQBadge>;

export const Default: Story = {
  args: { children: 'New', variant: 'default' },
};

export const AllVariants: Story = {
  render: () => (
    <div className="flex flex-wrap gap-2">
      <AtomyQBadge variant="default">Brand</AtomyQBadge>
      <AtomyQBadge variant="secondary">Secondary</AtomyQBadge>
      <AtomyQBadge variant="success">Success</AtomyQBadge>
      <AtomyQBadge variant="warning">Warning</AtomyQBadge>
      <AtomyQBadge variant="danger">Danger</AtomyQBadge>
      <AtomyQBadge variant="info">Info</AtomyQBadge>
      <AtomyQBadge variant="outline">Outline</AtomyQBadge>
      <AtomyQBadge variant="neutral">Neutral</AtomyQBadge>
    </div>
  ),
};

export const WithDot: Story = {
  render: () => (
    <div className="flex flex-wrap gap-2">
      <AtomyQBadge variant="success" dot>Active</AtomyQBadge>
      <AtomyQBadge variant="warning" dot>Pending</AtomyQBadge>
      <AtomyQBadge variant="danger" dot>Critical</AtomyQBadge>
      <AtomyQBadge variant="info" dot>Processing</AtomyQBadge>
    </div>
  ),
};

export const StatusBadges: Story = {
  name: 'ERP Status Badges',
  render: () => (
    <div className="space-y-4">
      <div>
        <span className="text-xs font-semibold uppercase tracking-wider text-[var(--aq-text-muted)] mb-2 block">RFQ Statuses</span>
        <div className="flex flex-wrap gap-2">
          <AtomyQBadge variant="neutral" dot>Draft</AtomyQBadge>
          <AtomyQBadge variant="info" dot>Open</AtomyQBadge>
          <AtomyQBadge variant="success" dot>Awarded</AtomyQBadge>
          <AtomyQBadge variant="default" dot>Closed</AtomyQBadge>
          <AtomyQBadge variant="danger" dot>Cancelled</AtomyQBadge>
        </div>
      </div>
      <div>
        <span className="text-xs font-semibold uppercase tracking-wider text-[var(--aq-text-muted)] mb-2 block">Priority Levels</span>
        <div className="flex flex-wrap gap-2">
          <AtomyQBadge variant="neutral">Low</AtomyQBadge>
          <AtomyQBadge variant="info">Medium</AtomyQBadge>
          <AtomyQBadge variant="warning">High</AtomyQBadge>
          <AtomyQBadge variant="danger">Critical</AtomyQBadge>
        </div>
      </div>
      <div>
        <span className="text-xs font-semibold uppercase tracking-wider text-[var(--aq-text-muted)] mb-2 block">Quote Parsing</span>
        <div className="flex flex-wrap gap-2">
          <AtomyQBadge variant="success">Accepted</AtomyQBadge>
          <AtomyQBadge variant="warning">Parsed with Warnings</AtomyQBadge>
          <AtomyQBadge variant="info">Processing</AtomyQBadge>
          <AtomyQBadge variant="danger">Rejected</AtomyQBadge>
        </div>
      </div>
    </div>
  ),
};

export const Sizes: Story = {
  render: () => (
    <div className="flex items-center gap-2">
      <AtomyQBadge size="sm" variant="default">Small</AtomyQBadge>
      <AtomyQBadge size="md" variant="default">Medium</AtomyQBadge>
      <AtomyQBadge size="lg" variant="default">Large</AtomyQBadge>
    </div>
  ),
};
