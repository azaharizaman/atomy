import type { Meta, StoryObj } from '@storybook/react';
import { spacingScale, breakpointDescriptions, elevationDescriptions, radii, shadows } from './spacing';

const meta: Meta = {
  title: 'Tokens/Spacing',
  parameters: { layout: 'padded' },
};

export default meta;

export const SpacingScale: StoryObj = {
  render: () => (
    <div className="space-y-8 max-w-3xl">
      <div>
        <h2 className="text-xl font-semibold mb-1">Spacing System</h2>
        <p className="text-sm text-[var(--aq-text-muted)] mb-6">
          All spacing in AtomyQ uses a 4px base unit. Never use arbitrary pixel values — pick the closest token.
        </p>
      </div>

      <div className="space-y-2">
        {spacingScale.map((s) => (
          <div key={s.token} className="flex items-center gap-4 py-1">
            <span className="text-xs font-mono text-[var(--aq-text-muted)] w-20">{s.token}</span>
            <div
              className="bg-[var(--aq-brand-200)] rounded-sm h-6"
              style={{ width: s.value }}
            />
            <span className="text-xs font-mono text-[var(--aq-text-subtle)] w-16">{s.value}</span>
            <span className="text-xs text-[var(--aq-text-muted)] flex-1">{s.usage}</span>
          </div>
        ))}
      </div>
    </div>
  ),
};

export const Breakpoints: StoryObj = {
  render: () => (
    <div className="space-y-6 max-w-3xl">
      <div>
        <h2 className="text-xl font-semibold mb-1">Responsive Breakpoints</h2>
        <p className="text-sm text-[var(--aq-text-muted)] mb-6">
          AtomyQ is desktop-first, targeting 1440px as the primary viewport. Tablet support is secondary.
        </p>
      </div>
      <div className="border border-[var(--aq-border-default)] rounded-lg overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <tr className="bg-[var(--aq-bg-elevated)] border-b border-[var(--aq-border-default)]">
              <th className="px-4 py-2 text-left font-semibold text-xs uppercase text-[var(--aq-text-muted)]">Name</th>
              <th className="px-4 py-2 text-left font-semibold text-xs uppercase text-[var(--aq-text-muted)]">Width</th>
              <th className="px-4 py-2 text-left font-semibold text-xs uppercase text-[var(--aq-text-muted)]">Usage</th>
            </tr>
          </thead>
          <tbody>
            {breakpointDescriptions.map((bp) => (
              <tr key={bp.name} className="border-b border-[var(--aq-border-subtle)]">
                <td className="px-4 py-2 font-mono text-xs">{bp.name}</td>
                <td className="px-4 py-2 font-mono text-xs">{bp.value}</td>
                <td className="px-4 py-2 text-[var(--aq-text-muted)]">{bp.usage}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  ),
};

export const Elevation: StoryObj = {
  render: () => (
    <div className="space-y-6 max-w-3xl">
      <div>
        <h2 className="text-xl font-semibold mb-1">Elevation & Shadows</h2>
        <p className="text-sm text-[var(--aq-text-muted)] mb-6">
          Shadows communicate hierarchy. Use sparingly — most elements are flat with borders.
        </p>
      </div>
      <div className="grid grid-cols-2 gap-6">
        {elevationDescriptions.map((e) => (
          <div
            key={e.level}
            className="p-6 rounded-lg bg-white border border-[var(--aq-border-default)]"
            style={{ boxShadow: e.shadow }}
          >
            <div className="text-sm font-semibold">Shadow {e.level.toUpperCase()}</div>
            <div className="text-xs text-[var(--aq-text-muted)] mt-1">{e.usage}</div>
            <div className="text-xs font-mono text-[var(--aq-text-subtle)] mt-2 break-all">{e.shadow}</div>
          </div>
        ))}
      </div>

      <div>
        <h3 className="text-sm font-semibold uppercase tracking-wider text-[var(--aq-text-muted)] mb-4">Border Radii</h3>
        <div className="flex gap-4 flex-wrap">
          {Object.entries(radii).map(([name, value]) => (
            <div key={name} className="flex flex-col items-center gap-2">
              <div
                className="size-14 bg-[var(--aq-brand-100)] border-2 border-[var(--aq-brand-400)]"
                style={{ borderRadius: value }}
              />
              <span className="text-xs font-mono text-[var(--aq-text-muted)]">{name}</span>
              <span className="text-xs font-mono text-[var(--aq-text-subtle)]">{value}</span>
            </div>
          ))}
        </div>
      </div>
    </div>
  ),
};
