import type { Meta, StoryObj } from '@storybook/react-vite';
import { fn } from 'storybook/test';
import { AtomyQKPICardAlt, type AtomyQKPICardAltProps } from './AtomyQKPICardAlt';
import {
  FileText,
  DollarSign,
  Clock,
  Sparkles,
  TrendingUp,
  Users,
  ShieldCheck,
  Zap,
} from 'lucide-react';

const iconMap: Record<string, React.ReactNode> = {
  none: undefined,
  FileText: <FileText className="size-4" />,
  DollarSign: <DollarSign className="size-4" />,
  Clock: <Clock className="size-4" />,
  Sparkles: <Sparkles className="size-4" />,
  TrendingUp: <TrendingUp className="size-4" />,
  Users: <Users className="size-4" />,
  ShieldCheck: <ShieldCheck className="size-4" />,
  Zap: <Zap className="size-4" />,
};

const meta: Meta<AtomyQKPICardAltProps & { iconName: string }> = {
  title: 'Components/Data/KPI Card Alt',
  component: AtomyQKPICardAlt,
  parameters: {
    layout: 'centered',
    docs: {
      description: {
        component:
          'A modern KPI card with tinted icon background, large value display, and trend delta indicator. Supports six semantic tones and optional click interaction.',
      },
    },
  },
  tags: ['autodocs'],
  argTypes: {
    label: {
      control: 'text',
      description: 'The metric label displayed above the value',
      table: { category: 'Content' },
    },
    value: {
      control: 'text',
      description: 'The primary metric value (large display)',
      table: { category: 'Content' },
    },
    delta: {
      control: 'text',
      description: 'Change indicator text (e.g. "+12%", "-3.5%")',
      table: { category: 'Content' },
    },
    trend: {
      control: 'inline-radio',
      options: ['up', 'down', 'flat'],
      description: 'Trend direction — controls the arrow icon and color',
      table: { category: 'Appearance' },
    },
    tone: {
      control: 'select',
      options: ['brand', 'success', 'warning', 'danger', 'purple', 'neutral'],
      description: 'Semantic tone — controls the icon background tint',
      table: { category: 'Appearance' },
    },
    iconName: {
      control: 'select',
      options: Object.keys(iconMap),
      description: 'Icon displayed in the tinted background',
      table: { category: 'Appearance' },
      mapping: iconMap,
    },
    icon: { table: { disable: true } },
    onClick: {
      action: 'clicked',
      description: 'Callback fired when the card is clicked',
      table: { category: 'Events' },
    },
    className: { table: { disable: true } },
  },
  args: {
    label: 'Active RFQs',
    value: '24',
    delta: '+12%',
    trend: 'up',
    tone: 'brand',
    iconName: 'FileText',
    onClick: fn(),
  },
  render: ({ iconName, ...args }) => (
    <AtomyQKPICardAlt
      {...args}
      icon={iconMap[iconName as string]}
      className="w-[260px]"
    />
  ),
};

export default meta;
type Story = StoryObj<typeof meta>;

// ─── Interactive Playground ──────────────────────────────────────────────────
export const Playground: Story = {
  name: 'Playground',
};

// ─── All Tones ───────────────────────────────────────────────────────────────
export const AllTones: Story = {
  name: 'All Tones',
  parameters: { controls: { disable: true } },
  render: () => (
    <div className="grid grid-cols-3 gap-4">
      <AtomyQKPICardAlt
        label="Active RFQs"
        value="24"
        delta="+12%"
        trend="up"
        tone="brand"
        icon={<FileText className="size-4" />}
      />
      <AtomyQKPICardAlt
        label="Total Savings"
        value="RM 2.4M"
        delta="+8.3%"
        trend="up"
        tone="success"
        icon={<DollarSign className="size-4" />}
      />
      <AtomyQKPICardAlt
        label="Avg. Cycle Time"
        value="4.2d"
        delta="-15%"
        trend="down"
        tone="warning"
        icon={<Clock className="size-4" />}
      />
      <AtomyQKPICardAlt
        label="Overdue Items"
        value="3"
        delta="+2"
        trend="up"
        tone="danger"
        icon={<ShieldCheck className="size-4" />}
      />
      <AtomyQKPICardAlt
        label="AI Confidence"
        value="94%"
        delta="+2.1%"
        trend="up"
        tone="purple"
        icon={<Sparkles className="size-4" />}
      />
      <AtomyQKPICardAlt
        label="Pending Reviews"
        value="7"
        trend="flat"
        tone="neutral"
        icon={<Users className="size-4" />}
      />
    </div>
  ),
};

// ─── All Trends ──────────────────────────────────────────────────────────────
export const AllTrends: Story = {
  name: 'All Trends',
  parameters: { controls: { disable: true } },
  render: () => (
    <div className="flex gap-4">
      <AtomyQKPICardAlt
        label="Revenue"
        value="RM 1.8M"
        delta="+18%"
        trend="up"
        tone="success"
        icon={<TrendingUp className="size-4" />}
        className="w-[220px]"
      />
      <AtomyQKPICardAlt
        label="Costs"
        value="RM 420K"
        delta="-5.2%"
        trend="down"
        tone="danger"
        icon={<DollarSign className="size-4" />}
        className="w-[220px]"
      />
      <AtomyQKPICardAlt
        label="Headcount"
        value="142"
        trend="flat"
        tone="neutral"
        icon={<Users className="size-4" />}
        className="w-[220px]"
      />
    </div>
  ),
};

// ─── Without Icon ────────────────────────────────────────────────────────────
export const WithoutIcon: Story = {
  name: 'Without Icon',
  args: {
    label: 'Open Quotes',
    value: '18',
    delta: '+3',
    trend: 'up',
    tone: 'brand',
    iconName: 'none',
  },
};

// ─── Without Delta ───────────────────────────────────────────────────────────
export const WithoutDelta: Story = {
  name: 'Without Delta',
  args: {
    label: 'Total Vendors',
    value: '56',
    delta: undefined,
    trend: 'flat',
    tone: 'neutral',
    iconName: 'Users',
  },
};

// ─── Clickable Card ──────────────────────────────────────────────────────────
export const Clickable: Story = {
  name: 'Clickable',
  args: {
    label: 'Pending Approvals',
    value: '5',
    delta: '+2',
    trend: 'up',
    tone: 'warning',
    iconName: 'Zap',
    onClick: fn(),
  },
};

// ─── Dashboard Row (realistic) ───────────────────────────────────────────────
export const DashboardRow: Story = {
  name: 'Dashboard Row (4-col)',
  parameters: { layout: 'padded', controls: { disable: true } },
  render: () => {
    const handleClick = (metric: string) => () => {
      console.log(`Navigate to ${metric} detail`);
    };
    return (
      <div className="grid grid-cols-4 gap-4">
        <AtomyQKPICardAlt
          label="Active RFQs"
          value="24"
          delta="+12%"
          trend="up"
          tone="brand"
          icon={<FileText className="size-4" />}
          onClick={handleClick('rfqs')}
        />
        <AtomyQKPICardAlt
          label="Total Savings"
          value="RM 2.4M"
          delta="+8.3%"
          trend="up"
          tone="success"
          icon={<DollarSign className="size-4" />}
          onClick={handleClick('savings')}
        />
        <AtomyQKPICardAlt
          label="Avg. Cycle Time"
          value="4.2 days"
          delta="-15%"
          trend="down"
          tone="warning"
          icon={<Clock className="size-4" />}
          onClick={handleClick('cycle-time')}
        />
        <AtomyQKPICardAlt
          label="AI Confidence"
          value="94%"
          delta="+2.1%"
          trend="up"
          tone="purple"
          icon={<Sparkles className="size-4" />}
          onClick={handleClick('ai')}
        />
      </div>
    );
  },
};
