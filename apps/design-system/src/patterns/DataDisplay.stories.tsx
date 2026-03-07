import type { Meta, StoryObj } from '@storybook/react';
import { AtomyQBadge } from '../components/basic/AtomyQBadge';
import { formatCurrency, formatDate, formatPercentage, formatNumber } from '@/lib/utils';

const meta: Meta = {
  title: 'Patterns/Data Display',
  parameters: { layout: 'padded' },
};

export default meta;

export const DataDisplayStandards: StoryObj = {
  render: () => (
    <div className="max-w-3xl space-y-8">
      <div>
        <h2 className="text-xl font-semibold text-[var(--aq-text-primary)] mb-1">Data Display Standards</h2>
        <p className="text-sm text-[var(--aq-text-muted)] mb-6">
          ERP software is data-heavy. Consistent formatting ensures users can scan and compare data quickly.
        </p>
      </div>

      {/* Currency */}
      <div>
        <h3 className="text-sm font-semibold uppercase tracking-wider text-[var(--aq-text-muted)] mb-3">Currency Format</h3>
        <div className="border border-[var(--aq-border-default)] rounded-lg overflow-hidden">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-[var(--aq-bg-elevated)]">
                <th className="px-4 py-2 text-left text-xs font-semibold uppercase text-[var(--aq-text-muted)]">Value</th>
                <th className="px-4 py-2 text-left text-xs font-semibold uppercase text-[var(--aq-text-muted)]">Formatted</th>
                <th className="px-4 py-2 text-left text-xs font-semibold uppercase text-[var(--aq-text-muted)]">Rules</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-[var(--aq-border-subtle)]">
              <tr>
                <td className="px-4 py-2 font-mono">1250</td>
                <td className="px-4 py-2 font-mono font-medium">{formatCurrency(1250)}</td>
                <td className="px-4 py-2 text-[var(--aq-text-muted)]">Always 2 decimal places</td>
              </tr>
              <tr>
                <td className="px-4 py-2 font-mono">428500</td>
                <td className="px-4 py-2 font-mono font-medium">{formatCurrency(428500)}</td>
                <td className="px-4 py-2 text-[var(--aq-text-muted)]">Use thousand separators</td>
              </tr>
              <tr>
                <td className="px-4 py-2 font-mono">1240000</td>
                <td className="px-4 py-2 font-mono font-medium">{formatCurrency(1240000)}</td>
                <td className="px-4 py-2 text-[var(--aq-text-muted)]">MYR is default currency</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p className="text-xs text-[var(--aq-text-subtle)] mt-2">Use monospace font for all financial data. Right-align in tables for easy scanning.</p>
      </div>

      {/* Dates */}
      <div>
        <h3 className="text-sm font-semibold uppercase tracking-wider text-[var(--aq-text-muted)] mb-3">Date/Time Format</h3>
        <div className="border border-[var(--aq-border-default)] rounded-lg overflow-hidden">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-[var(--aq-bg-elevated)]">
                <th className="px-4 py-2 text-left text-xs font-semibold uppercase text-[var(--aq-text-muted)]">Context</th>
                <th className="px-4 py-2 text-left text-xs font-semibold uppercase text-[var(--aq-text-muted)]">Format</th>
                <th className="px-4 py-2 text-left text-xs font-semibold uppercase text-[var(--aq-text-muted)]">Example</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-[var(--aq-border-subtle)]">
              <tr>
                <td className="px-4 py-2">Table columns</td>
                <td className="px-4 py-2 font-mono text-xs">YYYY-MM-DD</td>
                <td className="px-4 py-2 font-mono">{formatDate('2026-03-07', 'iso')}</td>
              </tr>
              <tr>
                <td className="px-4 py-2">Detail views</td>
                <td className="px-4 py-2 font-mono text-xs">DD MMM YYYY</td>
                <td className="px-4 py-2 font-mono">{formatDate('2026-03-07', 'short')}</td>
              </tr>
              <tr>
                <td className="px-4 py-2">Activity logs</td>
                <td className="px-4 py-2 font-mono text-xs">Relative</td>
                <td className="px-4 py-2">2h ago, Just now, Yesterday</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      {/* Numbers */}
      <div>
        <h3 className="text-sm font-semibold uppercase tracking-wider text-[var(--aq-text-muted)] mb-3">Numbers & Percentages</h3>
        <div className="grid grid-cols-3 gap-4">
          <div className="p-4 rounded-lg border border-[var(--aq-border-default)]">
            <span className="text-xs text-[var(--aq-text-muted)] block mb-1">Percentage</span>
            <span className="text-lg font-semibold font-mono">{formatPercentage(91.2)}</span>
            <p className="text-xs text-[var(--aq-text-subtle)] mt-1">1 decimal for precision</p>
          </div>
          <div className="p-4 rounded-lg border border-[var(--aq-border-default)]">
            <span className="text-xs text-[var(--aq-text-muted)] block mb-1">Count</span>
            <span className="text-lg font-semibold font-mono">{formatNumber(1250)}</span>
            <p className="text-xs text-[var(--aq-text-subtle)] mt-1">Thousand separators</p>
          </div>
          <div className="p-4 rounded-lg border border-[var(--aq-border-default)]">
            <span className="text-xs text-[var(--aq-text-muted)] block mb-1">ID</span>
            <span className="text-lg font-semibold font-mono">RFQ-2026-001</span>
            <p className="text-xs text-[var(--aq-text-subtle)] mt-1">Monospace, left-aligned</p>
          </div>
        </div>
      </div>

      {/* Status */}
      <div>
        <h3 className="text-sm font-semibold uppercase tracking-wider text-[var(--aq-text-muted)] mb-3">Status Indicators</h3>
        <p className="text-sm text-[var(--aq-text-muted)] mb-3">
          Always use both colour AND text. Never communicate status with colour alone (accessibility rule).
        </p>
        <div className="grid grid-cols-2 gap-4">
          <div className="p-4 rounded-lg border border-[var(--aq-border-default)] space-y-2">
            <span className="text-xs font-semibold text-[var(--aq-text-muted)]">With dot indicator:</span>
            <div className="flex flex-wrap gap-2">
              <AtomyQBadge variant="success" dot>Active</AtomyQBadge>
              <AtomyQBadge variant="warning" dot>Pending</AtomyQBadge>
              <AtomyQBadge variant="danger" dot>Critical</AtomyQBadge>
              <AtomyQBadge variant="neutral" dot>Inactive</AtomyQBadge>
            </div>
          </div>
          <div className="p-4 rounded-lg border border-[var(--aq-border-default)] space-y-2">
            <span className="text-xs font-semibold text-[var(--aq-text-muted)]">Text only:</span>
            <div className="flex flex-wrap gap-2">
              <AtomyQBadge variant="success">Approved</AtomyQBadge>
              <AtomyQBadge variant="danger">Rejected</AtomyQBadge>
              <AtomyQBadge variant="info">Processing</AtomyQBadge>
              <AtomyQBadge variant="neutral">Draft</AtomyQBadge>
            </div>
          </div>
        </div>
      </div>

      {/* Units */}
      <div>
        <h3 className="text-sm font-semibold uppercase tracking-wider text-[var(--aq-text-muted)] mb-3">Units</h3>
        <div className="border border-[var(--aq-border-default)] rounded-lg overflow-hidden">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-[var(--aq-bg-elevated)]">
                <th className="px-4 py-2 text-left text-xs font-semibold uppercase text-[var(--aq-text-muted)]">Type</th>
                <th className="px-4 py-2 text-left text-xs font-semibold uppercase text-[var(--aq-text-muted)]">Format</th>
                <th className="px-4 py-2 text-left text-xs font-semibold uppercase text-[var(--aq-text-muted)]">Example</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-[var(--aq-border-subtle)]">
              <tr><td className="px-4 py-2">File size</td><td className="px-4 py-2 font-mono text-xs">N.N MB</td><td className="px-4 py-2 font-mono">2.4 MB</td></tr>
              <tr><td className="px-4 py-2">Duration</td><td className="px-4 py-2 font-mono text-xs">N weeks / N days</td><td className="px-4 py-2 font-mono">6 weeks</td></tr>
              <tr><td className="px-4 py-2">Quantity</td><td className="px-4 py-2 font-mono text-xs">N unit</td><td className="px-4 py-2 font-mono">4 unit</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  ),
};
