import type { Meta, StoryObj } from '@storybook/react-vite';
import { AtomyQAvatar } from './AtomyQAvatar';
import { users } from '@/data/mockData';

const meta: Meta<typeof AtomyQAvatar> = {
  title: 'Components/Basic/AtomyQAvatar',
  component: AtomyQAvatar,
  parameters: { layout: 'centered' },
  tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof AtomyQAvatar>;

export const Default: Story = {
  args: { fallback: 'SC', size: 'md' },
};

export const AllSizes: Story = {
  render: () => (
    <div className="flex items-end gap-3">
      <AtomyQAvatar fallback="SC" size="sm" />
      <AtomyQAvatar fallback="SC" size="md" />
      <AtomyQAvatar fallback="SC" size="lg" />
      <AtomyQAvatar fallback="SC" size="xl" />
    </div>
  ),
};

export const WithStatus: Story = {
  render: () => (
    <div className="flex items-center gap-4">
      <AtomyQAvatar fallback="SC" size="lg" status="online" />
      <AtomyQAvatar fallback="MJ" size="lg" status="away" />
      <AtomyQAvatar fallback="DL" size="lg" status="busy" />
      <AtomyQAvatar fallback="TW" size="lg" status="offline" />
    </div>
  ),
};

export const UserAvatarGroup: Story = {
  name: 'User Avatar Stack',
  render: () => (
    <div className="flex -space-x-2">
      {users.slice(0, 4).map((user) => (
        <AtomyQAvatar key={user.id} fallback={user.avatar} size="md" status="online" />
      ))}
      <div className="flex size-8 items-center justify-center rounded-full bg-[var(--aq-bg-elevated)] text-xs font-medium text-[var(--aq-text-muted)] border-2 border-white">
        +3
      </div>
    </div>
  ),
};
