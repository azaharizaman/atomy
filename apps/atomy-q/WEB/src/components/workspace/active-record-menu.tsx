'use client';

import React from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { FileText, Inbox, GitCompareArrows, CheckSquare, HandCoins, FolderArchive, ShieldAlert, History, Award } from 'lucide-react';

import { Button } from '@/components/ds/Button';
import { CountBadge, StatusBadge, StatusDot } from '@/components/ds/Badge';
import { NavigationLink } from '@/components/layout/sidebar';
import { MetricChip } from './metric-chip';

export interface ActiveRfqRecord {
  id: string;
  title: string;
  status: 'active' | 'closed' | 'awarded' | 'draft' | 'pending' | 'archived';
  vendorsCount: number;
  quotesCount: number;
  estValue: string;
  savings: string;
  primaryActionLabel: string;
}

export function ActiveRecordMenu({ record }: { record: ActiveRfqRecord }) {
  const pathname = usePathname();

  const rfqBase = `/rfqs/${encodeURIComponent(record.id)}`;

  const rfqLinks = [
    { id: 'overview', label: 'Overview', href: `${rfqBase}/overview`, icon: <FileText size={16} /> },
    { id: 'details', label: 'Details', href: `${rfqBase}/details`, icon: <FileText size={16} /> },
    { id: 'line-items', label: 'Line Items', href: `${rfqBase}/line-items`, icon: <FileText size={16} /> },
    { id: 'vendors', label: 'Vendors', href: `${rfqBase}/vendors`, icon: <FileText size={16} /> },
    { id: 'award', label: 'Award', href: `${rfqBase}/award`, icon: <Award size={16} /> },
  ];

  const childLinks = [
    { id: 'quote-intake', label: 'Quote Intake', href: `${rfqBase}/quote-intake`, icon: <Inbox size={16} />, badge: record.quotesCount },
    { id: 'comparison-runs', label: 'Comparison Runs', href: `${rfqBase}/comparison-runs`, icon: <GitCompareArrows size={16} />, badge: 3 },
    { id: 'approvals', label: 'Approvals', href: `${rfqBase}/approvals`, icon: <CheckSquare size={16} />, badge: 2 },
    { id: 'negotiations', label: 'Negotiations', href: `${rfqBase}/negotiations`, icon: <HandCoins size={16} />, badge: 1 },
    { id: 'documents', label: 'Documents', href: `${rfqBase}/documents`, icon: <FolderArchive size={16} />, badge: 12 },
    { id: 'risk', label: 'Risk & Compliance', href: `${rfqBase}/risk`, icon: <ShieldAlert size={16} />, statusDot: 'green' as const },
    { id: 'decision-trail', label: 'Decision Trail', href: `${rfqBase}/decision-trail`, icon: <History size={16} />, badge: 5 },
  ];

  return (
    <div className="w-[360px] shrink-0 bg-white border-r border-slate-200 overflow-y-auto">
      {/* Zone 1 */}
      <div className="px-4 pt-4 pb-4 border-b border-slate-100">
        <div className="flex items-start justify-between gap-3">
          <div className="min-w-0">
            <div className="text-xs font-mono text-slate-400">{record.id}</div>
            <div className="text-sm font-semibold text-slate-900 truncate">{record.title}</div>
          </div>
          <StatusBadge status={record.status as any} />
        </div>

        <div className="mt-3 flex flex-wrap gap-2">
          <MetricChip label="Vendors" value={record.vendorsCount} />
          <MetricChip label="Quotes" value={record.quotesCount} />
          <MetricChip label="Est.Value" value={record.estValue} />
          <MetricChip label="Savings" value={record.savings} />
        </div>

        <div className="mt-3">
          <Button fullWidth size="sm" variant="primary">
            {record.primaryActionLabel}
          </Button>
        </div>
      </div>

      {/* Zone 2 */}
      <div className="px-3 py-3">
        <div className="text-[10px] font-semibold tracking-widest uppercase text-slate-400 px-1.5 mb-2">RFQ</div>
        <div className="flex flex-col gap-0.5">
          {rfqLinks.map((l) => (
            <NavigationLink key={l.id} label={l.label} icon={l.icon} active={pathname === l.href} href={l.href} />
          ))}
        </div>

        <div className="h-px bg-slate-100 my-3" />
        <div className="text-[10px] font-semibold tracking-widest uppercase text-slate-400 px-1.5 mb-2">CHILD RECORDS</div>
        <div className="flex flex-col gap-0.5">
          {childLinks.map((l) => (
            <NavigationLink
              key={l.id}
              label={l.label}
              icon={l.icon}
              active={pathname === l.href}
              href={l.href}
              badge={l.badge}
              statusDot={l.statusDot}
            />
          ))}
        </div>
      </div>

      <div className="px-4 py-3 border-t border-slate-100 text-[11px] text-slate-400 flex items-center justify-between">
        <span className="font-mono">v1.0.0</span>
        <Link href="/status" className="hover:text-slate-600">
          Status
        </Link>
      </div>
    </div>
  );
}

