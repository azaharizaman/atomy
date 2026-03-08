import type { Meta, StoryObj } from '@storybook/react-vite';
import { colors } from './colors';

const meta: Meta = {
  title: 'Tokens/Colors',
  parameters: { layout: 'padded' },
};

export default meta;

function ColorSwatch({ name, hex, token }: { name: string; hex: string; token?: string }) {
  return (
    <div className="flex items-center gap-3">
      <div
        className="size-10 rounded-lg border border-[var(--aq-border-default)] shrink-0 shadow-sm"
        style={{ backgroundColor: hex }}
      />
      <div className="min-w-0">
        <div className="text-sm font-medium text-[var(--aq-text-primary)]">{name}</div>
        <div className="text-xs font-mono text-[var(--aq-text-muted)]">{hex}</div>
        {token && <div className="text-xs font-mono text-[var(--aq-text-subtle)]">{token}</div>}
      </div>
    </div>
  );
}

function ColorGroup({ title, colors: colorSet }: { title: string; colors: Record<string, string> }) {
  return (
    <div>
      <h3 className="text-sm font-semibold text-[var(--aq-text-primary)] mb-3 uppercase tracking-wider">{title}</h3>
      <div className="grid grid-cols-2 gap-3">
        {Object.entries(colorSet).map(([key, value]) => (
          <ColorSwatch key={key} name={`${title} ${key}`} hex={value} token={`--aq-${title.toLowerCase()}-${key}`} />
        ))}
      </div>
    </div>
  );
}

export const AllColors: StoryObj = {
  render: () => (
    <div className="space-y-8 max-w-3xl">
      <div>
        <h2 className="text-xl font-semibold text-[var(--aq-text-primary)] mb-1">Colour Palette</h2>
        <p className="text-sm text-[var(--aq-text-muted)] mb-6">
          All colours in AtomyQ are defined as design tokens. Never use raw hex values in components — always reference tokens.
        </p>
      </div>

      <ColorGroup title="Brand" colors={colors.brand} />

      <div className="grid grid-cols-3 gap-8">
        <ColorGroup title="Success" colors={colors.success} />
        <ColorGroup title="Warning" colors={colors.warning} />
        <ColorGroup title="Danger" colors={colors.danger} />
      </div>

      <ColorGroup title="Neutral" colors={colors.neutral} />

      <div>
        <h3 className="text-sm font-semibold text-[var(--aq-text-primary)] mb-3 uppercase tracking-wider">Backgrounds</h3>
        <div className="grid grid-cols-3 gap-3">
          <ColorSwatch name="Canvas" hex={colors.canvas} token="--aq-bg-canvas" />
          <ColorSwatch name="Surface" hex={colors.surface} token="--aq-bg-surface" />
          <ColorSwatch name="Elevated" hex={colors.elevated} token="--aq-bg-elevated" />
        </div>
      </div>
    </div>
  ),
};
