import type { Meta, StoryObj } from '@storybook/react-vite';
import { fn } from 'storybook/test';
import { StatusBadge } from '../components/StatusBadge';

const meta = {
  title: 'Atomy-Q/Primitives/StatusBadge',
  component: StatusBadge,
  args: {
    label: 'Conditional pass',
    tone: 'warning',
    onClick: fn(),
  },
  argTypes: {
    label: { control: 'text' },
    tone: {
      options: ['neutral', 'info', 'success', 'warning', 'danger'],
      control: { type: 'radio' },
    },
    onClick: { action: 'badge.clicked' },
  },
} satisfies Meta<typeof StatusBadge>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Playground: Story = {};

export const RiskCritical: Story = {
  args: {
    label: 'Fraud severity: critical',
    tone: 'danger',
  },
};
