'use client';

import React from 'react';
import Link from 'next/link';
import { PageHeader } from '@/components/ds/FilterBar';
import { SectionCard, Card } from '@/components/ds/Card';
import { Button } from '@/components/ds/Button';
import { StatusBadge } from '@/components/ds/Badge';
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';
import { useRfq } from '@/hooks/use-rfq';
import { Lock, Unlock } from 'lucide-react';

const VENDORS = ['Dell Technologies', 'HP Enterprise', 'Lenovo', 'Cisco', 'IBM'];
const CATEGORIES = [
  { name: 'Servers', lines: ['PowerEdge R750', 'PowerEdge R650', 'Rack chassis'] },
  { name: 'Storage', lines: ['SAN Array 50TB'] },
  { name: 'Networking', lines: ['Switch 48p', 'Cables'] },
];

export default function ComparisonMatrixPage({
  params,
}: {
  params: Promise<{ rfqId: string; runId: string }>;
}) {
  const { rfqId } = React.use(params);
  const { data: rfq } = useRfq(rfqId);
  const [locked, setLocked] = React.useState(true);

  const breadcrumbItems = [
    { label: 'RFQs', href: '/rfqs' },
    { label: rfq?.title ?? 'Requisition', href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
    { label: 'Comparison Runs', href: `/rfqs/${encodeURIComponent(rfqId)}/comparison-runs` },
    { label: 'Run #005' },
  ];

  return (
    <div className="space-y-5">
      <WorkspaceBreadcrumbs items={breadcrumbItems} />
      <div className="flex items-center gap-3 flex-wrap">
        <StatusBadge status="final" label="Final" />
        <span className="text-xs font-medium text-green-800 bg-green-100 border border-green-200 rounded px-2 py-0.5">
          Snapshot frozen
        </span>
        {locked ? <Lock size={14} className="text-slate-500" /> : <Unlock size={14} className="text-slate-500" />}
        <Button size="sm" variant="ghost" onClick={() => setLocked(!locked)}>
          {locked ? 'Unlock' : 'Lock'}
        </Button>
        <span className="text-xs text-slate-500">Scoring model: v2.1</span>
        <label className="flex items-center gap-2 text-xs text-slate-600">
          <input type="checkbox" className="rounded border-slate-300" />
          View original values
        </label>
        <Button size="sm" variant="outline">View Recommendation</Button>
        <Link
          href={`/rfqs/${encodeURIComponent(rfqId)}/decision-trail`}
          className="text-sm font-medium text-indigo-600 hover:underline"
        >
          Decision trail
        </Link>
      </div>
      <div className="rounded-lg bg-green-50 border border-green-200 px-4 py-3 flex items-center justify-between">
        <span className="text-sm text-slate-800">
          Auto-approved. Proceed to Award or Negotiation.
        </span>
        <Link href={`/rfqs/${encodeURIComponent(rfqId)}/award`} className="text-sm font-medium text-indigo-600 hover:underline">
          Go to Award
        </Link>
      </div>
      <SectionCard title="Comparison matrix" subtitle="Normalized unit price by vendor (best per row highlighted)">
        <div className="overflow-x-auto">
          <table className="w-full text-xs border-collapse">
            <thead>
              <tr className="border-b border-slate-200">
                <th className="text-left py-2 px-3 font-medium text-slate-600 sticky left-0 bg-white">Line Item</th>
                {VENDORS.map((v, i) => (
                  <th key={v} className="text-right py-2 px-3 font-medium text-slate-600 whitespace-nowrap">
                    {v} <span className="text-slate-400">#{i + 1}</span>
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {CATEGORIES.flatMap((cat) => [
                <tr key={`h-${cat.name}`} className="bg-slate-50">
                  <td colSpan={VENDORS.length + 1} className="py-1.5 px-3 font-medium text-slate-700">
                    {cat.name}
                  </td>
                </tr>,
                ...cat.lines.map((line) => (
                  <tr key={line} className="border-b border-slate-100">
                    <td className="py-2 px-3 text-slate-800 sticky left-0 bg-white">{line}</td>
                    {VENDORS.map((v, i) => (
                      <td key={v} className="text-right py-2 px-3 tabular-nums">
                        <span className={i === 0 ? 'font-medium text-green-700' : 'text-slate-600'}>
                          ${(4000 + i * 200).toLocaleString()}
                        </span>
                        <span className="block text-[10px] text-slate-400">${(4200 + i * 100).toLocaleString()}</span>
                      </td>
                    ))}
                  </tr>
                )),
              ])}
            </tbody>
          </table>
        </div>
      </SectionCard>
      <SectionCard title="Recommendation">
        <p className="text-sm font-semibold text-slate-900">Recommended: Dell Technologies</p>
        <p className="text-xs text-slate-500 mt-0.5">Confidence: 82% — High</p>
        <ul className="mt-2 text-xs text-slate-600 list-disc list-inside space-y-0.5">
          <li>Best total cost</li>
          <li>Strong compliance score</li>
          <li>Lead time within target</li>
        </ul>
        <div className="mt-3 flex gap-2">
          <Button size="sm" variant="ghost">View Full Breakdown</Button>
          <Button size="sm" variant="outline">Override</Button>
        </div>
      </SectionCard>
    </div>
  );
}
