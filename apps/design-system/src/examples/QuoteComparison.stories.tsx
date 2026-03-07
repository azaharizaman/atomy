import type { Meta, StoryObj } from '@storybook/react-vite';
import { AtomyQBadge } from '../components/basic/AtomyQBadge';
import { AtomyQButton } from '../components/basic/AtomyQButton';
import { AtomyQSidebar } from '../components/navigation/AtomyQSidebar';
import { AtomyQBreadcrumb } from '../components/navigation/AtomyQBreadcrumb';
import { AtomyQTabs, AtomyQTabContent } from '../components/navigation/AtomyQTabs';
import { AtomyQProgress } from '../components/feedback/AtomyQProgress';
import { AtomyQAvatar } from '../components/basic/AtomyQAvatar';
import { comparisonMatrix, navigationItems } from '@/data/mockData';
import { Search, Bell, Sparkles, Download, Check, Star } from 'lucide-react';

const meta: Meta = {
  title: 'Examples/Quote Comparison',
  parameters: { layout: 'fullscreen' },
};

export default meta;

export const ComparisonMatrix: StoryObj = {
  render: () => {
    const vendors = ['Apex Industrial', 'GlobalPump Corp'];
    const totals = vendors.map((v) =>
      comparisonMatrix.reduce((sum, item) => sum + (item.vendors[v]?.total ?? 0), 0)
    );

    return (
      <div className="flex h-screen bg-[var(--aq-bg-canvas)]">
        <AtomyQSidebar sections={navigationItems} activeHref="/comparisons" />

        <div className="flex-1 flex flex-col overflow-hidden">
          {/* Header */}
          <header className="flex items-center justify-between h-14 px-6 bg-[var(--aq-header-bg)] border-b border-[var(--aq-header-border)]">
            <AtomyQBreadcrumb items={[
              { label: 'RFQs', href: '/rfqs' },
              { label: 'RFQ-2026-001', href: '/rfqs/001' },
              { label: 'Comparison' },
            ]} />
            <div className="flex items-center gap-3">
              <AtomyQButton variant="ghost" size="icon-sm"><Search className="size-4" /></AtomyQButton>
              <AtomyQButton variant="ghost" size="icon-sm"><Bell className="size-4" /></AtomyQButton>
              <AtomyQAvatar fallback="SC" size="sm" status="online" />
            </div>
          </header>

          <main className="flex-1 overflow-y-auto p-6">
            <div className="max-w-[1400px] mx-auto space-y-6">
              {/* Page Header */}
              <div className="flex items-center justify-between">
                <div>
                  <div className="flex items-center gap-3 mb-1">
                    <h1 className="text-xl font-semibold text-[var(--aq-text-primary)]">Quote Comparison</h1>
                    <AtomyQBadge variant="success" dot>Complete</AtomyQBadge>
                  </div>
                  <p className="text-sm text-[var(--aq-text-muted)]">
                    RFQ-2026-001 · Industrial Pumping Equipment · 2 vendors
                  </p>
                </div>
                <div className="flex gap-2">
                  <AtomyQButton variant="outline"><Download className="size-4" /> Export</AtomyQButton>
                  <AtomyQButton variant="success"><Check className="size-4" /> Approve</AtomyQButton>
                </div>
              </div>

              {/* AI Score Summary */}
              <div className="grid grid-cols-3 gap-4">
                <div className="p-5 rounded-lg border border-[var(--aq-border-default)] bg-white">
                  <span className="text-xs font-semibold uppercase tracking-wider text-[var(--aq-text-muted)]">AI Confidence Score</span>
                  <div className="flex items-end gap-2 mt-2">
                    <span className="text-3xl font-semibold font-mono text-[var(--aq-brand-600)]">92</span>
                    <span className="text-sm text-[var(--aq-text-muted)] mb-1">/ 100</span>
                  </div>
                  <AtomyQProgress value={92} variant="default" size="sm" className="mt-3" />
                </div>
                <div className="p-5 rounded-lg border border-[var(--aq-border-default)] bg-white">
                  <span className="text-xs font-semibold uppercase tracking-wider text-[var(--aq-text-muted)]">Recommended Vendor</span>
                  <div className="flex items-center gap-2 mt-2">
                    <Star className="size-5 text-[var(--aq-warning-500)] fill-[var(--aq-warning-500)]" />
                    <span className="text-lg font-semibold text-[var(--aq-text-primary)]">Apex Industrial Solutions</span>
                  </div>
                  <p className="text-xs text-[var(--aq-text-muted)] mt-1">Best overall score across price, delivery, and warranty</p>
                </div>
                <div className="p-5 rounded-lg border border-[var(--aq-border-default)] bg-white">
                  <span className="text-xs font-semibold uppercase tracking-wider text-[var(--aq-text-muted)]">Potential Savings</span>
                  <div className="flex items-end gap-2 mt-2">
                    <span className="text-3xl font-semibold font-mono text-[var(--aq-success-600)]">RM 21,800</span>
                  </div>
                  <p className="text-xs text-[var(--aq-text-muted)] mt-1">5.2% below allocated budget</p>
                </div>
              </div>

              {/* Comparison Tabs */}
              <AtomyQTabs
                items={[
                  { value: 'matrix', label: 'Comparison Matrix' },
                  { value: 'scoring', label: 'Scoring Breakdown' },
                  { value: 'risk', label: 'Risk Analysis' },
                  { value: 'evidence', label: 'Evidence' },
                ]}
                defaultValue="matrix"
                variant="underline"
              >
                <AtomyQTabContent value="matrix">
                  {/* Comparison Table */}
                  <div className="border border-[var(--aq-border-default)] rounded-lg overflow-hidden mt-4">
                    <table className="w-full text-sm">
                      <thead>
                        <tr className="bg-[var(--aq-bg-elevated)] border-b border-[var(--aq-border-default)]">
                          <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-[var(--aq-text-muted)]">Line Item</th>
                          <th className="px-4 py-3 text-center text-xs font-semibold uppercase text-[var(--aq-text-muted)]">Qty</th>
                          {vendors.map((v) => (
                            <th key={v} className="px-4 py-3 text-right text-xs font-semibold uppercase text-[var(--aq-text-muted)]">{v}</th>
                          ))}
                          <th className="px-4 py-3 text-center text-xs font-semibold uppercase text-[var(--aq-text-muted)]">Winner</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-[var(--aq-border-subtle)]">
                        {comparisonMatrix.map((item) => (
                          <tr key={item.lineItem} className="hover:bg-[var(--aq-bg-elevated)]/50">
                            <td className="px-4 py-3">
                              <span className="font-medium text-[var(--aq-text-primary)]">{item.lineItem}</span>
                            </td>
                            <td className="px-4 py-3 text-center font-mono text-xs">{item.quantity} {item.unit}</td>
                            {vendors.map((v) => {
                              const data = item.vendors[v];
                              const isWinner = item.recommended === v;
                              return (
                                <td key={v} className={`px-4 py-3 text-right ${isWinner ? 'bg-[var(--aq-success-50)]' : ''}`}>
                                  <div className="font-mono text-sm font-medium">RM {data.total.toLocaleString()}</div>
                                  <div className="text-[10px] text-[var(--aq-text-subtle)] mt-0.5">
                                    RM {data.unitPrice.toLocaleString()}/unit · {data.leadTime} · {data.warranty}
                                  </div>
                                </td>
                              );
                            })}
                            <td className="px-4 py-3 text-center">
                              <AtomyQBadge variant="success" size="sm">{item.recommended.split(' ')[0]}</AtomyQBadge>
                            </td>
                          </tr>
                        ))}
                        {/* Totals row */}
                        <tr className="bg-[var(--aq-bg-elevated)] font-semibold">
                          <td className="px-4 py-3">Total</td>
                          <td className="px-4 py-3"></td>
                          {totals.map((total, i) => (
                            <td key={i} className="px-4 py-3 text-right font-mono">RM {total.toLocaleString()}</td>
                          ))}
                          <td className="px-4 py-3 text-center">
                            <AtomyQBadge variant="success">
                              <Star className="size-3" /> Best
                            </AtomyQBadge>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </AtomyQTabContent>
              </AtomyQTabs>
            </div>
          </main>
        </div>
      </div>
    );
  },
};
