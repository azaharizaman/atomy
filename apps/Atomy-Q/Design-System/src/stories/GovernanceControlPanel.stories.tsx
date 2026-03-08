import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, fn, userEvent, within } from 'storybook/test';
import { GovernanceControlPanel } from '../components/GovernanceControlPanel';
import { governanceControlSnapshot } from '../mocks/quotationComparisonMock';

const meta = {
  title: 'Atomy-Q/Composites/GovernanceControlPanel',
  component: GovernanceControlPanel,
  args: {
    snapshot: governanceControlSnapshot,
    onApprove: fn(),
    onRequestWaiver: fn(),
  },
  argTypes: {
    onApprove: { action: 'approve.clicked' },
    onRequestWaiver: { action: 'waiver.clicked' },
  },
} satisfies Meta<typeof GovernanceControlPanel>;

export default meta;
type Story = StoryObj<typeof meta>;

export const ControlsAndActions: Story = {};

export const InteractionFlow: Story = {
  play: async ({ canvasElement, args }) => {
    const canvas = within(canvasElement);
    const waiverButton = canvas.getByRole('button', { name: /request waiver/i });
    const approveButton = canvas.getByRole('button', { name: /approve with controls/i });

    await userEvent.click(waiverButton);
    await userEvent.click(approveButton);

    await expect(args.onRequestWaiver).toHaveBeenCalledTimes(1);
    await expect(args.onApprove).toHaveBeenCalledTimes(1);
  },
};
