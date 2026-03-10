import React from 'react';
import { FileText, List, Users, Award, Eye, BarChart2, ShieldCheck, GitBranch, FileCheck, AlertTriangle } from 'lucide-react';
import { StatusBadge } from './Badge';
import { MetricChip } from './KPIScorecard';
import { NavigationLink } from './Sidebar';
import { Button } from './Button';
import type { StatusVariant } from './tokens';

// ─── Zone 1: Active Record Snippet ────────────────────────────────────────────

interface ActiveRecordSnippetProps {
  rfqId: string;
  rfqTitle: string;
  status: StatusVariant;
  metrics: {
    vendors: number | string;
    quotes: number | string;
    estValue: string;
    savings: string;
  };
  primaryAction?: string;
  onPrimaryAction?: () => void;
  className?: string;
}

export function ActiveRecordSnippet({
  rfqId, rfqTitle, status, metrics, primaryAction = 'Close for Submissions', onPrimaryAction, className = '',
}: ActiveRecordSnippetProps) {
  return (
    <div className={['px-4 py-4 border-b border-slate-200', className].join(' ')}>
      {/* ID + Title */}
      <div className="mb-2">
        <span className="text-[11px] font-mono font-medium text-slate-400">{rfqId}</span>
        <div className="flex items-start gap-2 mt-1">
          <h2 className="text-sm font-semibold text-slate-900 leading-tight flex-1">{rfqTitle}</h2>
          <StatusBadge status={status} size="xs" />
        </div>
      </div>

      {/* Metric chips */}
      <div className="grid grid-cols-4 gap-1.5 mb-3">
        <MetricChip label="Vendors" value={metrics.vendors} />
        <MetricChip label="Quotes" value={metrics.quotes} />
        <MetricChip label="Est. Val" value={metrics.estValue} />
        <MetricChip label="Savings" value={metrics.savings} />
      </div>

      {/* Primary lifecycle action */}
      <Button
        variant="primary"
        size="sm"
        fullWidth
        onClick={onPrimaryAction}
      >
        {primaryAction}
      </Button>
    </div>
  );
}

// ─── Zone 2: Navigation Links ─────────────────────────────────────────────────

export interface ActiveNavLink {
  id: string;
  label: string;
  icon?: React.ReactNode;
  badge?: number | string;
  statusDot?: 'green' | 'amber' | 'red' | 'slate';
}

interface ActiveRecordNavProps {
  rfqLinks: ActiveNavLink[];
  childLinks: ActiveNavLink[];
  activeId: string;
  onChange: (id: string) => void;
  className?: string;
}

export function ActiveRecordNav({ rfqLinks, childLinks, activeId, onChange, className = '' }: ActiveRecordNavProps) {
  return (
    <div className={['px-3 py-3', className].join(' ')}>
      {/* RFQ group */}
      <div className="mb-3">
        <span className="block px-3 mb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400">RFQ</span>
        <div className="flex flex-col gap-0.5">
          {rfqLinks.map(link => (
            <NavigationLink
              key={link.id}
              label={link.label}
              icon={link.icon}
              badge={link.badge}
              statusDot={link.statusDot}
              active={activeId === link.id}
              onClick={() => onChange(link.id)}
            />
          ))}
        </div>
      </div>

      {/* Divider */}
      <div className="border-t border-slate-200 mb-3" />

      {/* Child Records group */}
      <div>
        <span className="block px-3 mb-1 text-[10px] font-bold uppercase tracking-widest text-slate-400">Child Records</span>
        <div className="flex flex-col gap-0.5">
          {childLinks.map(link => (
            <NavigationLink
              key={link.id}
              label={link.label}
              icon={link.icon}
              badge={link.badge}
              statusDot={link.statusDot}
              active={activeId === link.id}
              onClick={() => onChange(link.id)}
            />
          ))}
        </div>
      </div>
    </div>
  );
}

// ─── Complete Active Record Menu (Zone 1 + Zone 2) ────────────────────────────

interface ActiveRecordMenuProps {
  rfqId?: string;
  rfqTitle?: string;
  status?: StatusVariant;
  metrics?: {
    vendors: number | string;
    quotes: number | string;
    estValue: string;
    savings: string;
  };
  primaryAction?: string;
  onPrimaryAction?: () => void;
  activeNavId?: string;
  onNavChange?: (id: string) => void;
  className?: string;
}

const DEFAULT_RFQ_LINKS: ActiveNavLink[] = [
  { id: 'overview',   label: 'Overview',    icon: <Eye size={13} /> },
  { id: 'details',    label: 'Details',     icon: <FileText size={13} /> },
  { id: 'line-items', label: 'Line Items',  icon: <List size={13} /> },
  { id: 'vendors',    label: 'Vendors',     icon: <Users size={13} /> },
  { id: 'award',      label: 'Award',       icon: <Award size={13} /> },
];

const DEFAULT_CHILD_LINKS: ActiveNavLink[] = [
  { id: 'quote-intake',    label: 'Quote Intake',       icon: <FileCheck size={13} />,  badge: 8 },
  { id: 'comparison-runs', label: 'Comparison Runs',    icon: <BarChart2 size={13} />,  badge: 3 },
  { id: 'approvals',       label: 'Approvals',          icon: <ShieldCheck size={13} />, badge: 2 },
  { id: 'negotiations',    label: 'Negotiations',        icon: <GitBranch size={13} />,  badge: 1 },
  { id: 'documents',       label: 'Documents',           icon: <FileText size={13} />,   badge: 12 },
  { id: 'risk',            label: 'Risk & Compliance',  icon: <AlertTriangle size={13} />, statusDot: 'amber' },
  { id: 'decision-trail',  label: 'Decision Trail',     icon: <List size={13} /> },
];

export function ActiveRecordMenu({
  rfqId = 'RFQ-2401',
  rfqTitle = 'Server Infrastructure Refresh',
  status = 'active',
  metrics = { vendors: 5, quotes: 8, estValue: '$1.2M', savings: '12%' },
  primaryAction = 'Close for Submissions',
  onPrimaryAction,
  activeNavId = 'overview',
  onNavChange,
  className = '',
}: ActiveRecordMenuProps) {
  const [activeId, setActiveId] = React.useState(activeNavId);

  function handleChange(id: string) {
    setActiveId(id);
    onNavChange?.(id);
  }

  return (
    <div
      className={[
        'flex flex-col bg-white border-r border-slate-200 overflow-y-auto',
        'w-[360px] min-w-[280px] shrink-0',
        className,
      ].join(' ')}
    >
      <ActiveRecordSnippet
        rfqId={rfqId}
        rfqTitle={rfqTitle}
        status={status}
        metrics={metrics}
        primaryAction={primaryAction}
        onPrimaryAction={onPrimaryAction}
      />
      <ActiveRecordNav
        rfqLinks={DEFAULT_RFQ_LINKS}
        childLinks={DEFAULT_CHILD_LINKS}
        activeId={activeId}
        onChange={handleChange}
      />
    </div>
  );
}
