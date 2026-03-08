import type { Meta, StoryObj } from '@storybook/react-vite';
import { EvidenceTimeline } from '../components/EvidenceTimeline';
import { evidenceVault } from '../mocks/quotationComparisonMock';

const meta = {
  title: 'Atomy-Q/Composites/EvidenceTimeline',
  component: EvidenceTimeline,
  args: {
    items: evidenceVault,
  },
  argTypes: {
    items: { control: 'object' },
  },
} satisfies Meta<typeof EvidenceTimeline>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};
