'use client';

import React from 'react';
import { AlertTriangle, ChevronRight, Clock, TrendingUp } from 'lucide-react';
import { Card } from '@/components/ds/Card';
import { Button } from '@/components/ds/Button';

// ─── Pipeline Stat Card ───────────────────────────────────────────────────────

interface PipelineStatCardProps {
  label: string;
  count: number;
  icon?: React.ReactNode;
  onClick?: () => void;
  className?: string;
}

export function PipelineStatCard({ label, count, icon, onClick, className = '' }: PipelineStatCardProps) {
  return (
    <div
      onClick={onClick}
      className={[
        'flex items-center justify-between p-4 rounded-lg border border-slate-200 bg-white shadow-[0_1px_3px_0_rgba(0,0,0,0.06)]',
        onClick ? 'cursor-pointer hover:border-indigo-300 hover:shadow-sm transition-all' : '',
        className,
      ].join(' ')}
    >
      <div className="flex items-center gap-3">
        {icon && <div className="text-slate-400">{icon}</div>}
        <div>
          <p className="text-2xl font-semibold text-slate-900">{count}</p>
          <p className="text-xs font-medium text-slate-500 uppercase tracking-wide">{label}</p>
        </div>
      </div>
      {onClick && <ChevronRight size={16} className="text-slate-400" />}
    </div>
  );
}

// ─── Savings Highlight Card ────────────────────────────────────────────────────

interface SavingsHighlightCardProps {
  title: string;
  value: string;
  subtitle?: string;
  trend?: { value: number; label: string };
  onClick?: () => void;
  className?: string;
}

export function SavingsHighlightCard({ title, value, subtitle, trend, onClick, className = '' }: SavingsHighlightCardProps) {
  const trendUp = trend && trend.value > 0;
  return (
    <Card padding="lg" onClick={onClick} className={className}>
      <p className="text-xs font-medium text-slate-500 uppercase tracking-wide mb-1">{title}</p>
      <p className="text-3xl font-bold text-slate-900">{value}</p>
      {subtitle && <p className="text-sm text-slate-500 mt-1">{subtitle}</p>}
      {trend && (
        <div className={['inline-flex items-center gap-1 mt-2 px-2 py-0.5 rounded text-xs font-medium', trendUp ? 'text-green-700 bg-green-50' : 'text-slate-600 bg-slate-100'].join(' ')}>
          <TrendingUp size={12} className={trendUp ? '' : 'opacity-50'} />
          {trend.label}
        </div>
      )}
    </Card>
  );
}

// ─── SLA Alert Card ────────────────────────────────────────────────────────────

type SLAUrgency = 'high' | 'medium' | 'low';

interface SLAAlertCardProps {
  title: string;
  rfqId: string;
  timeRemaining: string;
  urgency?: SLAUrgency;
  assignee?: string;
  onClick?: () => void;
  className?: string;
}

const SLA_URGENCY_STYLES: Record<SLAUrgency, { bg: string; border: string; icon: string }> = {
  high: { bg: 'bg-red-50', border: 'border-red-200', icon: 'text-red-600' },
  medium: { bg: 'bg-amber-50', border: 'border-amber-200', icon: 'text-amber-600' },
  low: { bg: 'bg-slate-50', border: 'border-slate-200', icon: 'text-slate-500' },
};

export function SLAAlertCard({ title, rfqId, timeRemaining, urgency = 'medium', assignee, onClick, className = '' }: SLAAlertCardProps) {
  const s = SLA_URGENCY_STYLES[urgency];
  return (
    <div
      onClick={onClick}
      className={[
        'flex items-start gap-3 p-3 rounded-lg border',
        s.bg,
        s.border,
        onClick ? 'cursor-pointer hover:shadow-sm transition-shadow' : '',
        className,
      ].join(' ')}
    >
      <AlertTriangle size={18} className={['shrink-0 mt-0.5', s.icon].join(' ')} />
      <div className="min-w-0 flex-1">
        <p className="text-sm font-medium text-slate-900">{title}</p>
        <p className="text-xs text-slate-500 mt-0.5 font-mono">{rfqId}</p>
        <div className="flex items-center gap-2 mt-1.5 text-xs text-slate-600">
          <Clock size={12} />
          <span>{timeRemaining}</span>
          {assignee && <span className="text-slate-400">· {assignee}</span>}
        </div>
      </div>
      {onClick && <ChevronRight size={14} className="text-slate-400 shrink-0" />}
    </div>
  );
}

// ─── Activity Summary Card ─────────────────────────────────────────────────────

export interface ActivitySummaryItem {
  id: string;
  timestamp: string;
  actor: string;
  action: string;
  rfqId?: string;
}

interface ActivitySummaryCardProps {
  items: ActivitySummaryItem[];
  title?: string;
  maxItems?: number;
  onViewAll?: () => void;
  className?: string;
}

export function ActivitySummaryCard({ items, title = 'Recent Activity', maxItems = 5, onViewAll, className = '' }: ActivitySummaryCardProps) {
  const shown = items.slice(0, maxItems);
  return (
    <Card padding="none" className={className}>
      <div className="flex items-center justify-between px-4 py-3 border-b border-slate-100">
        <h3 className="text-sm font-semibold text-slate-800">{title}</h3>
        {onViewAll && (
          <button onClick={onViewAll} className="text-xs font-medium text-indigo-600 hover:text-indigo-700">
            View all
          </button>
        )}
      </div>
      <ul className="divide-y divide-slate-100">
        {shown.map(item => (
          <li key={item.id} className="px-4 py-2.5">
            <p className="text-sm text-slate-700">{item.actor} {item.action}</p>
            <p className="text-[11px] text-slate-400 mt-0.5">
              {item.timestamp}
              {item.rfqId && <span className="font-mono ml-1">{item.rfqId}</span>}
            </p>
          </li>
        ))}
      </ul>
    </Card>
  );
}

// ─── Pending Approvals Card ────────────────────────────────────────────────────

export interface PendingApprovalItem {
  id: string;
  rfqId: string;
  rfqTitle: string;
  type: string;
  assignee: string;
  submittedAt: string;
}

interface PendingApprovalsCardProps {
  items: PendingApprovalItem[];
  onItemClick?: (id: string) => void;
  onViewAll?: () => void;
  className?: string;
}

export function PendingApprovalsCard({ items, onItemClick, onViewAll, className = '' }: PendingApprovalsCardProps) {
  return (
    <Card padding="none" className={className}>
      <div className="flex items-center justify-between px-4 py-3 border-b border-slate-100">
        <h3 className="text-sm font-semibold text-slate-800">Pending Approvals</h3>
        {onViewAll && (
          <button onClick={onViewAll} className="text-xs font-medium text-indigo-600 hover:text-indigo-700">
            View queue
          </button>
        )}
      </div>
      <ul className="divide-y divide-slate-100">
        {items.map(item => (
          <li
            key={item.id}
            onClick={() => onItemClick?.(item.id)}
            className={['px-4 py-2.5', onItemClick ? 'cursor-pointer hover:bg-slate-50 transition-colors' : ''].join(' ')}
          >
            <div className="flex items-start justify-between gap-2">
              <div>
                <p className="text-sm font-medium text-slate-900">{item.rfqTitle}</p>
                <p className="text-xs text-slate-500 mt-0.5">
                  <span className="font-mono">{item.rfqId}</span> · {item.type} · {item.assignee}
                </p>
              </div>
              <span className="text-[11px] text-slate-400 shrink-0">{item.submittedAt}</span>
            </div>
          </li>
        ))}
      </ul>
    </Card>
  );
}

// ─── Category Breakdown Card ───────────────────────────────────────────────────

export interface CategoryMetricItem {
  category: string;
  count: number;
  estValue: string;
  pct: number;
}

interface CategoryBreakdownCardProps {
  items: CategoryMetricItem[];
  title?: string;
  maxItems?: number;
  onViewAll?: () => void;
  className?: string;
}

export function CategoryBreakdownCard({ items, title = 'By Category', maxItems = 6, onViewAll, className = '' }: CategoryBreakdownCardProps) {
  const shown = items.slice(0, maxItems);
  const maxPct = Math.max(...shown.map(i => i.pct), 1);
  return (
    <Card padding="none" className={className}>
      <div className="flex items-center justify-between px-4 py-3 border-b border-slate-100">
        <h3 className="text-sm font-semibold text-slate-800">{title}</h3>
        {onViewAll && (
          <button onClick={onViewAll} className="text-xs font-medium text-indigo-600 hover:text-indigo-700">
            View all
          </button>
        )}
      </div>
      <div className="p-4 space-y-3">
        {shown.map(item => (
          <div key={item.category}>
            <div className="flex items-center justify-between text-xs mb-1">
              <span className="font-medium text-slate-700">{item.category}</span>
              <span className="text-slate-500">{item.estValue} · {item.count} RFQs</span>
            </div>
            <div className="h-1.5 rounded-full bg-slate-100 overflow-hidden">
              <div
                className="h-full rounded-full bg-indigo-500"
                style={{ width: `${(item.pct / maxPct) * 100}%` }}
              />
            </div>
          </div>
        ))}
      </div>
    </Card>
  );
}

// ─── Quick Action Card ─────────────────────────────────────────────────────────

interface QuickActionCardProps {
  icon: React.ReactNode;
  title: string;
  description?: string;
  actionLabel?: string;
  onAction?: () => void;
  className?: string;
}

export function QuickActionCard({ icon, title, description, actionLabel, onAction, className = '' }: QuickActionCardProps) {
  return (
    <Card padding="md" onClick={onAction} className={className}>
      <div className="flex items-start gap-3">
        <div className="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 shrink-0">
          {icon}
        </div>
        <div className="min-w-0 flex-1">
          <h4 className="text-sm font-semibold text-slate-900">{title}</h4>
          {description && <p className="text-xs text-slate-500 mt-0.5">{description}</p>}
          {actionLabel && onAction && (
            <Button variant="ghost" size="sm" className="mt-2 -ml-1" onClick={e => { e.stopPropagation(); onAction(); }}>
              {actionLabel}
            </Button>
          )}
        </div>
      </div>
    </Card>
  );
}
