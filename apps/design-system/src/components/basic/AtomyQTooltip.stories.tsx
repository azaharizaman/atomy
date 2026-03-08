import type { Meta, StoryObj } from '@storybook/react-vite';
import { AtomyQTooltip } from './AtomyQTooltip';
import { AtomyQButton } from './AtomyQButton';
import { AtomyQIconButton } from './AtomyQIconButton';
import { HelpCircle, Settings, Bell, Download } from 'lucide-react';

const meta: Meta = {
  title: 'Components/Basic/AtomyQTooltip',
  parameters: { layout: 'centered' },
  tags: ['autodocs'],
};

export default meta;

export const Default: StoryObj = {
  render: () => (
    <AtomyQTooltip content="Create a new Request for Quotation">
      <AtomyQButton variant="primary">New RFQ</AtomyQButton>
    </AtomyQTooltip>
  ),
};

export const Positions: StoryObj = {
  render: () => (
    <div className="flex items-center gap-6 py-12">
      <AtomyQTooltip content="Top tooltip" side="top">
        <AtomyQButton variant="outline">Top</AtomyQButton>
      </AtomyQTooltip>
      <AtomyQTooltip content="Right tooltip" side="right">
        <AtomyQButton variant="outline">Right</AtomyQButton>
      </AtomyQTooltip>
      <AtomyQTooltip content="Bottom tooltip" side="bottom">
        <AtomyQButton variant="outline">Bottom</AtomyQButton>
      </AtomyQTooltip>
      <AtomyQTooltip content="Left tooltip" side="left">
        <AtomyQButton variant="outline">Left</AtomyQButton>
      </AtomyQTooltip>
    </div>
  ),
};

export const IconButtonTooltips: StoryObj = {
  name: 'Icon Buttons with Tooltips',
  render: () => (
    <div className="flex items-center gap-2">
      <AtomyQIconButton tooltip="Settings" aria-label="Settings"><Settings className="size-4" /></AtomyQIconButton>
      <AtomyQIconButton tooltip="Notifications" aria-label="Notifications"><Bell className="size-4" /></AtomyQIconButton>
      <AtomyQIconButton tooltip="Download report" aria-label="Download report"><Download className="size-4" /></AtomyQIconButton>
      <AtomyQIconButton tooltip="Help & documentation" aria-label="Help"><HelpCircle className="size-4" /></AtomyQIconButton>
    </div>
  ),
};
