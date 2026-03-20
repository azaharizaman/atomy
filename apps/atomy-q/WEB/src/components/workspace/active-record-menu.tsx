'use client';

import React from 'react';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { Eye, FileText, List, Users, Inbox, GitCompareArrows, ShieldCheck, HandCoins, FolderArchive, ShieldAlert, History, Award } from 'lucide-react';

import { Button } from '@/components/ds/Button';
import { CountBadge, StatusBadge, StatusDot } from '@/components/ds/Badge';
import { NavigationLink } from '@/components/layout/sidebar';
import { type RfqStatus } from '@/hooks/use-rfqs';
import { MetricChip } from './metric-chip';

export interface ActiveRfqRecord {
  id: string;
  title: string;
  status: RfqStatus;
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
    { id: 'overview', label: 'Overview', href: `${rfqBase}/overview`, icon: <Eye size={14} /> },
    { id: 'details', label: 'Details', href: `${rfqBase}/details`, icon: <FileText size={14} /> },
    { id: 'line-items', label: 'Line Items', href: `${rfqBase}/line-items`, icon: <List size={14} /> },
    { id: 'vendors', label: 'Vendors', href: `${rfqBase}/vendors`, icon: <Users size={14} /> },
    { id: 'award', label: 'Award', href: `${rfqBase}/award`, icon: <Award size={14} /> },
  ];

  const childLinks = [
    { id: 'quote-intake', label: 'Quote Intake', href: `${rfqBase}/quote-intake`, icon: <Inbox size={14} />, badge: record.quotesCount },
    { id: 'comparison-runs', label: 'Comparison Runs', href: `${rfqBase}/comparison-runs`, icon: <GitCompareArrows size={14} />, badge: 3 },
    { id: 'approvals', label: 'Approvals', href: `${rfqBase}/approvals`, icon: <ShieldCheck size={14} />, badge: 2 },
    { id: 'negotiations', label: 'Negotiations', href: `${rfqBase}/negotiations`, icon: <HandCoins size={14} />, badge: 1 },
    { id: 'documents', label: 'Documents', href: `${rfqBase}/documents`, icon: <FolderArchive size={14} />, badge: 12 },
    { id: 'risk', label: 'Risk & Compliance', href: `${rfqBase}/risk`, icon: <ShieldAlert size={14} />, statusDot: 'green' as const },
    { id: 'decision-trail', label: 'Decision Trail', href: `${rfqBase}/decision-trail`, icon: <List size={14} />, badge: 5 },
  ];

  return (
    <div
      className="w-[360px] shrink-0 bg-white border-r border-slate-200 overflow-y-auto"
      data-testid="active-record-menu"
    >
      {/* Zone 1: Record Snippet — per Screen-Blueprint */}
      <div className="px-4 pt-4 pb-4 border-b border-slate-200">
        <div className="flex items-start justify-between gap-3">
          <div className="min-w-0">
            <div className="text-xs font-mono text-slate-400">{record.id}</div>
            <div className="text-sm font-semibold text-slate-900 truncate">{record.title}</div>
          </div>
          <StatusBadge status={record.status} />
        </div>

        <div className="mt-3 flex flex-wrap gap-2">
          <MetricChip label="Vendors" value={record.vendorsCount} />
          <MetricChip label="Quotes" value={record.quotesCount} />
          <MetricChip label="Est.Value" value={record.estValue} />
          <MetricChip label="Savings" value={record.savings} />
        </div>

        <div className="mt-3">
          <Button fullWidth size="sm" variant="primary" disabled>
            {record.primaryActionLabel}
          </Button>
        </div>
      </div>

      {/* Zone 2: Navigation — per Screen-Blueprint (RFQ + CHILD RECORDS) */}
      <div className="px-3 py-3">
        <div className="text-[10px] font-bold uppercase tracking-widest text-slate-400 px-3 mb-1">RFQ</div>
        <div className="flex flex-col gap-0.5">
          {rfqLinks.map((l) => (
            <NavigationLink key={l.id} label={l.label} icon={l.icon} active={pathname === l.href} href={l.href} />
          ))}
        </div>

        <div className="border-t border-slate-200 mb-3" />
        <div className="text-[10px] font-bold uppercase tracking-widest text-slate-400 px-3 mb-1">Child Records</div>
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

