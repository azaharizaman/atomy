import type { Meta, StoryObj } from '@storybook/react';
import { typeScale, fontFamilies } from './typography';

const meta: Meta = {
  title: 'Tokens/Typography',
  parameters: { layout: 'padded' },
};

export default meta;

export const TypeScale: StoryObj = {
  render: () => (
    <div className="space-y-8 max-w-3xl">
      <div>
        <h2 className="text-xl font-semibold mb-1">Typography Scale</h2>
        <p className="text-sm text-[var(--aq-text-muted)] mb-6">
          AtomyQ uses Inter for UI text and JetBrains Mono for data/code. The scale is optimised for data-dense ERP screens.
        </p>
      </div>

      <div>
        <h3 className="text-sm font-semibold uppercase tracking-wider text-[var(--aq-text-muted)] mb-4">Font Families</h3>
        <div className="space-y-3">
          <div className="p-4 rounded-lg border border-[var(--aq-border-default)]">
            <p className="text-lg" style={{ fontFamily: fontFamilies.sans }}>Inter — The quick brown fox jumps over the lazy dog</p>
            <p className="text-xs text-[var(--aq-text-muted)] mt-1 font-mono">{fontFamilies.sans}</p>
          </div>
          <div className="p-4 rounded-lg border border-[var(--aq-border-default)]">
            <p className="text-lg" style={{ fontFamily: fontFamilies.mono }}>JetBrains Mono — 0123456789 RM 1,250.00</p>
            <p className="text-xs text-[var(--aq-text-muted)] mt-1 font-mono">{fontFamilies.mono}</p>
          </div>
        </div>
      </div>

      <div>
        <h3 className="text-sm font-semibold uppercase tracking-wider text-[var(--aq-text-muted)] mb-4">Heading Scale</h3>
        <div className="space-y-4">
          {Object.entries(typeScale).map(([name, spec]) => (
            <div key={name} className="flex items-baseline gap-6 py-2 border-b border-[var(--aq-border-subtle)]">
              <div className="w-28 shrink-0">
                <span className="text-xs font-mono text-[var(--aq-text-muted)]">{name}</span>
              </div>
              <div className="flex-1">
                <p
                  className={spec.family === 'mono' ? 'font-mono' : ''}
                  style={{
                    fontSize: { xs: '0.75rem', sm: '0.875rem', base: '1rem', lg: '1.125rem', xl: '1.25rem', '2xl': '1.5rem', '3xl': '1.875rem', '4xl': '2.25rem', '5xl': '2.625rem' }[spec.size],
                    fontWeight: { normal: 400, medium: 500, semibold: 600, bold: 700 }[spec.weight],
                  }}
                >
                  {spec.family === 'mono' ? 'RM 428,500.00' : 'The quick brown fox'}
                </p>
                <p className="text-xs text-[var(--aq-text-subtle)] mt-1">{spec.usage}</p>
              </div>
              <div className="text-xs font-mono text-[var(--aq-text-subtle)] w-32 text-right">
                {spec.size} / {spec.weight}
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  ),
};
