import type { Meta, StoryObj } from '@storybook/react-vite';
import { fn, expect, within, userEvent } from 'storybook/test';
import { AtomyQButton } from './AtomyQButton';
import { Plus, Download, Trash2, Check, Sparkles, ArrowRight } from 'lucide-react';

const meta: Meta<typeof AtomyQButton> = {
  title: 'Components/Basic/AtomyQButton',
  component: AtomyQButton,
  parameters: {
    layout: 'centered',
    docs: {
      description: {
        component: 'A versatile button component for AtomyQ with multiple variants, sizes, and states. Supports icons, loading states, and keyboard navigation.',
      },
    },
  },
  tags: ['autodocs'],
  argTypes: {
    variant: {
      control: 'select',
      options: ['primary', 'secondary', 'outline', 'ghost', 'destructive', 'success', 'link'],
      description: 'Visual style variant of the button',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: 'primary' },
      },
    },
    size: {
      control: 'select',
      options: ['sm', 'md', 'lg', 'icon', 'icon-sm'],
      description: 'Size of the button',
      table: {
        type: { summary: 'string' },
        defaultValue: { summary: 'md' },
      },
    },
    loading: {
      control: 'boolean',
      description: 'Shows loading spinner and disables the button',
      table: {
        type: { summary: 'boolean' },
        defaultValue: { summary: 'false' },
      },
    },
    disabled: {
      control: 'boolean',
      description: 'Disables the button',
      table: {
        type: { summary: 'boolean' },
        defaultValue: { summary: 'false' },
      },
    },
    asChild: {
      control: 'boolean',
      description: 'Render as child component (Radix Slot pattern)',
      table: {
        type: { summary: 'boolean' },
        defaultValue: { summary: 'false' },
      },
    },
    children: {
      control: 'text',
      description: 'Button content',
    },
    onClick: {
      action: 'clicked',
      description: 'Click handler function',
    },
  },
  args: {
    onClick: fn(),
  },
};

export default meta;
type Story = StoryObj<typeof AtomyQButton>;

export const Primary: Story = {
  args: {
    children: 'Create RFQ',
    variant: 'primary',
  },
  play: async ({ canvasElement, args }) => {
    const canvas = within(canvasElement);
    const button = canvas.getByRole('button', { name: /Create RFQ/i });
    
    await expect(button).toBeInTheDocument();
    await expect(button).not.toBeDisabled();
    await userEvent.click(button);
    await expect(args.onClick).toHaveBeenCalledTimes(1);
  },
};

export const Secondary: Story = {
  args: {
    children: 'Cancel',
    variant: 'secondary',
  },
};

export const Outline: Story = {
  args: {
    children: 'Export',
    variant: 'outline',
  },
};

export const Ghost: Story = {
  args: {
    children: 'View Details',
    variant: 'ghost',
  },
};

export const Destructive: Story = {
  args: {
    children: 'Delete RFQ',
    variant: 'destructive',
  },
  play: async ({ canvasElement, args }) => {
    const canvas = within(canvasElement);
    const button = canvas.getByRole('button', { name: /Delete RFQ/i });
    
    await userEvent.click(button);
    await expect(args.onClick).toHaveBeenCalled();
  },
};

export const Success: Story = {
  args: {
    children: 'Approve',
    variant: 'success',
  },
};

export const Link: Story = {
  args: {
    children: 'View vendor profile',
    variant: 'link',
  },
};

export const WithIcon: Story = {
  name: 'With Icons',
  render: (args) => (
    <div className="flex gap-3 flex-wrap">
      <AtomyQButton variant="primary" onClick={args.onClick}>
        <Plus className="size-4" /> New RFQ
      </AtomyQButton>
      <AtomyQButton variant="outline" onClick={args.onClick}>
        <Download className="size-4" /> Export
      </AtomyQButton>
      <AtomyQButton variant="destructive" onClick={args.onClick}>
        <Trash2 className="size-4" /> Delete
      </AtomyQButton>
      <AtomyQButton variant="success" onClick={args.onClick}>
        <Check className="size-4" /> Approve
      </AtomyQButton>
      <AtomyQButton variant="ghost" onClick={args.onClick}>
        <Sparkles className="size-4" /> Run AI
      </AtomyQButton>
    </div>
  ),
  args: {
    onClick: fn(),
  },
  play: async ({ canvasElement, args }) => {
    const canvas = within(canvasElement);
    const buttons = canvas.getAllByRole('button');
    
    await expect(buttons).toHaveLength(5);
    await userEvent.click(buttons[0]);
    await expect(args.onClick).toHaveBeenCalled();
  },
};

export const IconTrailing: Story = {
  name: 'Icon Trailing',
  args: {
    children: (
      <>
        Continue <ArrowRight className="size-4" />
      </>
    ),
    variant: 'primary',
  },
};

export const Sizes: Story = {
  render: (args) => (
    <div className="flex items-center gap-3">
      <AtomyQButton size="sm" onClick={args.onClick}>Small</AtomyQButton>
      <AtomyQButton size="md" onClick={args.onClick}>Medium</AtomyQButton>
      <AtomyQButton size="lg" onClick={args.onClick}>Large</AtomyQButton>
    </div>
  ),
  args: {
    onClick: fn(),
  },
};

export const Loading: Story = {
  args: {
    children: 'Submitting...',
    loading: true,
    variant: 'primary',
  },
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);
    const button = canvas.getByRole('button');
    
    await expect(button).toBeDisabled();
    await expect(button).toHaveAttribute('aria-busy', 'true');
  },
};

export const Disabled: Story = {
  args: {
    children: 'Cannot submit',
    disabled: true,
    variant: 'primary',
  },
  play: async ({ canvasElement }) => {
    const canvas = within(canvasElement);
    const button = canvas.getByRole('button');
    
    await expect(button).toBeDisabled();
  },
};

export const ButtonGroup: Story = {
  name: 'Button Group (Action Bar)',
  render: (args) => (
    <div className="flex items-center gap-2 p-4 bg-white rounded-lg border border-[var(--aq-border-default)]">
      <AtomyQButton variant="primary" onClick={args.onClick}>
        <Plus className="size-4" /> Create RFQ
      </AtomyQButton>
      <AtomyQButton variant="outline" onClick={args.onClick}>
        <Sparkles className="size-4" /> Run Comparison
      </AtomyQButton>
      <AtomyQButton variant="outline" onClick={args.onClick}>
        <Download className="size-4" /> Export
      </AtomyQButton>
      <div className="flex-1" />
      <AtomyQButton variant="ghost" size="sm" onClick={args.onClick}>
        Help
      </AtomyQButton>
    </div>
  ),
  args: {
    onClick: fn(),
  },
  parameters: {
    layout: 'padded',
  },
};

export const AllVariants: Story = {
  name: 'All Variants Grid',
  render: (args) => (
    <div className="grid grid-cols-2 gap-4 p-4">
      {(['primary', 'secondary', 'outline', 'ghost', 'destructive', 'success', 'link'] as const).map(
        (variant) => (
          <div key={variant} className="flex flex-col gap-2">
            <span className="text-xs font-medium text-[var(--aq-text-muted)] uppercase">{variant}</span>
            <AtomyQButton variant={variant} onClick={args.onClick}>
              {variant.charAt(0).toUpperCase() + variant.slice(1)} Button
            </AtomyQButton>
          </div>
        )
      )}
    </div>
  ),
  args: {
    onClick: fn(),
  },
  parameters: {
    layout: 'padded',
  },
};

export const InteractivePlayground: Story = {
  name: 'Interactive Playground',
  args: {
    children: 'Click me!',
    variant: 'primary',
    size: 'md',
    loading: false,
    disabled: false,
  },
  play: async ({ canvasElement, args, step }) => {
    const canvas = within(canvasElement);
    const button = canvas.getByRole('button');

    await step('Button is visible and enabled', async () => {
      await expect(button).toBeVisible();
      await expect(button).not.toBeDisabled();
    });

    await step('Button responds to click', async () => {
      await userEvent.click(button);
      await expect(args.onClick).toHaveBeenCalledTimes(1);
    });

    await step('Button can be focused with keyboard', async () => {
      button.blur();
      await userEvent.tab();
      await expect(button).toHaveFocus();
    });

    await step('Button can be activated with Enter key', async () => {
      await userEvent.keyboard('{Enter}');
      await expect(args.onClick).toHaveBeenCalledTimes(2);
    });

    await step('Button can be activated with Space key', async () => {
      await userEvent.keyboard(' ');
      await expect(args.onClick).toHaveBeenCalledTimes(3);
    });
  },
};
