import type { Meta, StoryObj } from '@storybook/react-vite';
import { AtomyQButton } from './AtomyQButton';
import { Plus, Download, Trash2, Check, Sparkles } from 'lucide-react';

const meta: Meta<typeof AtomyQButton> = {
  title: 'Components/Basic/AtomyQButton',
  component: AtomyQButton,
  parameters: { layout: 'centered' },
  tags: ['autodocs'],
  argTypes: {
    variant: {
      control: 'select',
      options: ['primary', 'secondary', 'outline', 'ghost', 'destructive', 'success', 'link'],
    },
    size: { control: 'select', options: ['sm', 'md', 'lg', 'icon', 'icon-sm'] },
    loading: { control: 'boolean' },
    disabled: { control: 'boolean' },
  },
};

export default meta;
type Story = StoryObj<typeof AtomyQButton>;

export const Primary: Story = {
  args: { children: 'Create RFQ', variant: 'primary' },
};

export const Secondary: Story = {
  args: { children: 'Cancel', variant: 'secondary' },
};

export const Outline: Story = {
  args: { children: 'Export', variant: 'outline' },
};

export const Ghost: Story = {
  args: { children: 'View Details', variant: 'ghost' },
};

export const Destructive: Story = {
  args: { children: 'Delete RFQ', variant: 'destructive' },
};

export const Success: Story = {
  args: { children: 'Approve', variant: 'success' },
};

export const Link: Story = {
  args: { children: 'View vendor profile', variant: 'link' },
};

export const WithIcon: Story = {
  render: () => (
    <div className="flex gap-3 flex-wrap">
      <AtomyQButton variant="primary"><Plus className="size-4" /> New RFQ</AtomyQButton>
      <AtomyQButton variant="outline"><Download className="size-4" /> Export</AtomyQButton>
      <AtomyQButton variant="destructive"><Trash2 className="size-4" /> Delete</AtomyQButton>
      <AtomyQButton variant="success"><Check className="size-4" /> Approve</AtomyQButton>
      <AtomyQButton variant="ghost"><Sparkles className="size-4" /> Run AI</AtomyQButton>
    </div>
  ),
};

export const Sizes: Story = {
  render: () => (
    <div className="flex items-center gap-3">
      <AtomyQButton size="sm">Small</AtomyQButton>
      <AtomyQButton size="md">Medium</AtomyQButton>
      <AtomyQButton size="lg">Large</AtomyQButton>
    </div>
  ),
};

export const Loading: Story = {
  args: { children: 'Submitting...', loading: true, variant: 'primary' },
};

export const Disabled: Story = {
  args: { children: 'Cannot submit', disabled: true, variant: 'primary' },
};

export const ButtonGroup: Story = {
  name: 'Button Group (Action Bar)',
  render: () => (
    <div className="flex items-center gap-2 p-4 bg-white rounded-lg border border-[var(--aq-border-default)]">
      <AtomyQButton variant="primary"><Plus className="size-4" /> Create RFQ</AtomyQButton>
      <AtomyQButton variant="outline"><Sparkles className="size-4" /> Run Comparison</AtomyQButton>
      <AtomyQButton variant="outline"><Download className="size-4" /> Export</AtomyQButton>
      <div className="flex-1" />
      <AtomyQButton variant="ghost" size="sm">Help</AtomyQButton>
    </div>
  ),
};
