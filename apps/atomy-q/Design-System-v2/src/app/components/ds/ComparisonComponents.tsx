import React from 'react';
import { Banner } from './Alert';
import { StatusBadge, ConfidenceBadge } from './Badge';
import { Card } from './Card';
import { Button } from './Button';

interface DeltaBadgeProps {
  value: string;
  best?: boolean;
}

export function DeltaBadge({ value, best = false }: DeltaBadgeProps) {
  return (
    <span
      className={[
        'inline-flex h-5 items-center rounded-full px-2 text-xs font-medium border',
        best
          ? 'bg-green-50 border-green-200 text-green-700'
          : 'bg-red-50 border-red-200 text-red-700',
      ].join(' ')}
    >
      {best ? 'Best' : value}
    </span>
  );
}

export function MissingValuePlaceholder() {
  return <span className="text-slate-400 text-xs">—</span>;
}

interface VendorSummaryHeaderProps {
  vendor: string;
  total: string;
  rank: number;
}

export function VendorSummaryHeader({ vendor, total, rank }: VendorSummaryHeaderProps) {
  return (
    <div className="rounded-md border border-slate-200 bg-white px-2 py-1.5">
      <p className="text-xs font-medium text-slate-700 truncate">{vendor}</p>
      <div className="flex items-center justify-between mt-1">
        <span className="text-xs font-semibold text-slate-900">{total}</span>
        <span className="text-[10px] px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-600">#{rank}</span>
      </div>
    </div>
  );
}

interface ApprovalGateBannerProps {
  mode: 'approved' | 'pending' | 'rejected';
}

export function ApprovalGateBanner({ mode }: ApprovalGateBannerProps) {
  if (mode === 'approved') {
    return (
      <Banner variant="success">
        Auto-approved. Proceed to Award or Negotiation.
      </Banner>
    );
  }

  if (mode === 'rejected') {
    return (
      <Banner variant="danger">
        Rejected by approval gate. Review policy exceptions and rerun comparison.
      </Banner>
    );
  }

  return (
    <Banner variant="warning">
      Pending approval. Human decision required before award workflow can proceed.
    </Banner>
  );
}

export function ReadinessBanner({ issues }: { issues: string[] }) {
  if (issues.length === 0) return null;
  return (
    <Banner variant="warning">
      Readiness issues: {issues.join(' · ')}
    </Banner>
  );
}

interface RecommendationCardProps {
  vendor: string;
  confidence: number;
  factors: string[];
  onOverride?: () => void;
}

export function RecommendationCard({
  vendor,
  confidence,
  factors,
  onOverride,
}: RecommendationCardProps) {
  return (
    <Card padding="md">
      <div className="flex items-start justify-between gap-3">
        <div>
          <p className="text-xs text-slate-500 uppercase tracking-wide">Recommended</p>
          <h3 className="text-base font-semibold text-slate-900">{vendor}</h3>
        </div>
        <StatusBadge status="approved" label="Recommended" />
      </div>
      <div className="mt-3">
        <ConfidenceBadge
          variant={confidence >= 75 ? 'high' : confidence >= 50 ? 'medium' : 'low'}
          showBar
          percentage={confidence}
        />
      </div>
      <ul className="mt-3 space-y-1">
        {factors.map(factor => (
          <li key={factor} className="text-xs text-slate-600">• {factor}</li>
        ))}
      </ul>
      <div className="mt-3 flex items-center justify-between">
        <button className="text-xs text-indigo-600 hover:text-indigo-700">View Full Breakdown</button>
        <Button variant="ghost" size="sm" onClick={onOverride}>Override</Button>
      </div>
    </Card>
  );
}

export interface ComparisonMatrixRow {
  id: string;
  lineItem: string;
  values: (string | null)[];
  bestVendorIndex: number;
}

interface ComparisonMatrixGridProps {
  vendors: { name: string; total: string; rank: number }[];
  rows: ComparisonMatrixRow[];
}

export function ComparisonMatrixGrid({ vendors, rows }: ComparisonMatrixGridProps) {
  return (
    <div className="rounded-lg border border-slate-200 bg-white overflow-auto">
      <table className="w-full text-xs border-collapse min-w-[760px]">
        <thead className="bg-slate-50 border-b border-slate-200">
          <tr>
            <th className="sticky left-0 z-10 bg-slate-50 px-3 py-2 text-left text-slate-600 font-medium min-w-56">
              Line Item
            </th>
            {vendors.map(vendor => (
              <th key={vendor.name} className="px-2 py-2 min-w-44">
                <VendorSummaryHeader vendor={vendor.name} total={vendor.total} rank={vendor.rank} />
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {rows.map(row => (
            <tr key={row.id} className="border-b border-slate-100">
              <td className="sticky left-0 bg-white px-3 py-2 text-slate-700 font-medium">{row.lineItem}</td>
              {row.values.map((value, idx) => (
                <td
                  key={`${row.id}-${idx}`}
                  className={[
                    'px-2 py-2 text-right',
                    idx === row.bestVendorIndex ? 'bg-green-50/60' : '',
                  ].join(' ')}
                >
                  {value ? <span className="text-slate-800 font-medium">{value}</span> : <MissingValuePlaceholder />}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
