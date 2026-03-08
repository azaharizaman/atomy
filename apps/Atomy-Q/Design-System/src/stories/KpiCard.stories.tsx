import type { Meta, StoryObj } from '@storybook/react-vite';
import { KpiCard } from '../components/KpiCard';

const meta = {
  title: 'Atomy-Q/Primitives/KpiCard',
  component: KpiCard,
  args: {
    label: 'Expected Savings',
    value: '$1.46M',
    delta: '+11.2% vs target',
    tone: 'success',
  },
  argTypes: {
    label: { control: 'text' },
    value: { control: 'text' },
    delta: { control: 'text' },
    tone: {
      options: ['neutral', 'info', 'success', 'warning', 'danger'],
      control: { type: 'inline-radio' },
    },
  },
} satisfies Meta<typeof KpiCard>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Playground: Story = {};
