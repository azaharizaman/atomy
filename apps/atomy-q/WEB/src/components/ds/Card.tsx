'use client';

import React from 'react';
import { MoreHorizontal } from 'lucide-react';

import { IconButton } from './Button';

interface CardProps {
  children: React.ReactNode;
  className?: string;
  padding?: 'none' | 'sm' | 'md' | 'lg';
  hover?: boolean;
  bordered?: boolean;
  onClick?: () => void;
}

const PAD_MAP = { none: '', sm: 'p-3', md: 'p-4', lg: 'p-5' };

export function Card({ children, className = '', padding = 'md', hover = false, bordered = true, onClick }: CardProps) {
  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (onClick && (e.key === 'Enter' || e.key === ' ')) {
      e.preventDefault();
      onClick();
    }
  };

  return (
    <div
      onClick={onClick}
      onKeyDown={handleKeyDown}
      role={onClick ? 'button' : undefined}
      tabIndex={onClick ? 0 : undefined}
      className={[
        'bg-white rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500',
        bordered ? 'border border-slate-200' : '',
        'shadow-[0_1px_3px_0_rgba(0,0,0,0.06)]',
        PAD_MAP[padding],
        hover ? 'cursor-pointer hover:border-slate-300 hover:shadow-[0_2px_8px_0_rgba(0,0,0,0.08)] transition-all duration-150' : '',
        onClick ? 'cursor-pointer' : '',
        className,
      ].join(' ')}
    >
      {children}
    </div>
  );
}

// ─── Section Card (header + body) — per Screen-Blueprint ────────────────────────

interface SectionCardProps {
  title: string;
  subtitle?: string;
  actions?: React.ReactNode;
  children: React.ReactNode;
  className?: string;
  headerClassName?: string;
  bodyClassName?: string;
  noPadBody?: boolean;
}

export function SectionCard({
  title,
  subtitle,
  actions,
  children,
  className = '',
  headerClassName = '',
  bodyClassName = '',
  noPadBody = false,
}: SectionCardProps) {
  return (
    <Card padding="none" className={className}>
      <div
        className={['flex items-center justify-between px-4 py-3 border-b border-slate-100', headerClassName].join(' ')}
      >
        <div>
          <h3 className="text-sm font-semibold text-slate-800">{title}</h3>
          {subtitle && <p className="text-xs text-slate-500 mt-0.5">{subtitle}</p>}
        </div>
        {actions && <div className="flex items-center gap-1.5">{actions}</div>}
      </div>
      <div className={[noPadBody ? '' : 'p-4', bodyClassName].join(' ')}>{children}</div>
    </Card>
  );
}

interface InlineDetailPanelProps {
  items: { label: string; value: React.ReactNode }[];
  className?: string;
}

export function InlineDetailPanel({ items, className = '' }: InlineDetailPanelProps) {
  return (
    <div className={['grid grid-cols-6 gap-4 px-4 py-3 bg-slate-50 border-b border-slate-200', className].join(' ')}>
      {items.map((item, i) => (
        <div key={i}>
          <span className="block text-[10px] font-medium text-slate-400 uppercase tracking-wider mb-0.5">{item.label}</span>
          <span className="block text-xs font-medium text-slate-700">{item.value}</span>
        </div>
      ))}
    </div>
  );
}

// ─── Info Grid — per Screen-Blueprint ─────────────────────────────────────────

interface InfoGridProps {
  items: { label: string; value: React.ReactNode }[];
  cols?: 2 | 3 | 4;
  className?: string;
}

export function InfoGrid({ items, cols = 2, className = '' }: InfoGridProps) {
  const colClass = { 2: 'grid-cols-2', 3: 'grid-cols-3', 4: 'grid-cols-4' }[cols];
  return (
    <div className={['grid gap-3', colClass, className].join(' ')}>
      {items.map((item, i) => (
        <div key={i}>
          <span className="block text-[11px] font-medium text-slate-400 uppercase tracking-wider mb-0.5">
            {item.label}
          </span>
          <span className="block text-sm font-medium text-slate-800">{item.value}</span>
        </div>
      ))}
    </div>
  );
}

// ─── Empty State — per Screen-Blueprint ───────────────────────────────────────

interface EmptyStateProps {
  icon?: React.ReactNode;
  title: string;
  description?: string;
  action?: React.ReactNode;
  className?: string;
}

export function EmptyState({ icon, title, description, action, className = '' }: EmptyStateProps) {
  return (
    <div
      className={['flex flex-col items-center justify-center py-12 px-4 text-center', className].join(' ')}
    >
      {icon != null && <div className="text-slate-300 mb-3">{icon}</div>}
      <p className="text-sm font-medium text-slate-600">{title}</p>
      {description != null && (
        <p className="text-xs text-slate-400 mt-1 max-w-sm">{description}</p>
      )}
      {action != null && <div className="mt-4">{action}</div>}
    </div>
  );
}

// ─── Document Preview Placeholder — per Screen-Blueprint ───────────────────────

interface DocPreviewProps {
  fileName: string;
  pageInfo?: string;
  className?: string;
}

export function DocPreview({ fileName, pageInfo, className = '' }: DocPreviewProps) {
  return (
    <div
      className={[
        'flex flex-col items-center justify-center bg-slate-100 border border-dashed border-slate-300 rounded-lg',
        className,
      ].join(' ')}
    >
      <div className="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mb-3">
        <span className="text-[10px] font-bold text-red-600 uppercase">PDF</span>
      </div>
      <p className="text-sm font-medium text-slate-700">{fileName}</p>
      {pageInfo != null && <p className="text-xs text-slate-400 mt-1">{pageInfo}</p>}
    </div>
  );
}

// ─── Upload Zone — per Screen-Blueprint ──────────────────────────────────────

interface UploadZoneProps {
  onBrowse?: () => void;
  className?: string;
  compact?: boolean;
}

export function UploadZone({ onBrowse, className = '', compact = false }: UploadZoneProps) {
  const [hover, setHover] = React.useState(false);
  return (
    <div
      onDragOver={(e) => {
        e.preventDefault();
        setHover(true);
      }}
      onDragLeave={() => setHover(false)}
      onDrop={(e) => {
        e.preventDefault();
        setHover(false);
      }}
      onClick={onBrowse}
      role="button"
      tabIndex={0}
      onKeyDown={(e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          onBrowse?.();
        }
      }}
      className={[
        'flex items-center justify-center gap-3 border-2 border-dashed rounded-lg cursor-pointer transition-colors duration-150',
        compact ? 'py-3 px-4' : 'py-5 px-6',
        hover
          ? 'border-indigo-400 bg-indigo-50'
          : 'border-slate-200 bg-slate-50 hover:border-slate-300 hover:bg-white',
        className,
      ].join(' ')}
    >
      <div className={['flex flex-col items-center text-center gap-1', compact ? '' : 'gap-1.5'].join(' ')}>
        <svg
          className={['text-slate-400', compact ? 'w-5 h-5' : 'w-7 h-7'].join(' ')}
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          aria-hidden
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={1.5}
            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
          />
        </svg>
        <p className={['font-medium text-slate-600', compact ? 'text-xs' : 'text-sm'].join(' ')}>
          Drop files here or <span className="text-indigo-600 hover:text-indigo-700">click to browse</span>
        </p>
        {!compact && <p className="text-xs text-slate-400">PDF, XLSX, CSV · Max 50 MB</p>}
      </div>
    </div>
  );
}

interface OverflowMenuProps {
  onClick?: (e: React.MouseEvent) => void;
  className?: string;
}

export function OverflowMenuTrigger({ onClick, className = '' }: OverflowMenuProps) {
  return (
    <IconButton label="More options" variant="ghost" size="sm" onClick={onClick} className={className}>
      <MoreHorizontal size={14} />
    </IconButton>
  );
}

