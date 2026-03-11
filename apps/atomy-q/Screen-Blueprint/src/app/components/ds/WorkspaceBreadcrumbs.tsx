import React from 'react';
import { BreadcrumbBar, type BreadcrumbItem } from './BreadcrumbBar';

export interface WorkspaceBreadcrumbSegment {
  label: string;
  path: string;
}

interface WorkspaceBreadcrumbsProps {
  segments: WorkspaceBreadcrumbSegment[];
  onNavigate?: (path: string) => void;
  className?: string;
}

export function WorkspaceBreadcrumbs({
  segments,
  onNavigate,
  className = '',
}: WorkspaceBreadcrumbsProps) {
  const resolved: WorkspaceBreadcrumbSegment[] =
    segments[0]?.label.toLowerCase() === 'rfqs'
      ? segments
      : [{ label: 'RFQs', path: '/rfqs' }, ...segments];

  const items: BreadcrumbItem[] = resolved.map((segment, idx) => ({
    label: segment.label,
    href: segment.path,
    onClick: idx < resolved.length - 1 ? () => onNavigate?.(segment.path) : undefined,
  }));

  return (
    <div className={['px-5 py-2.5 border-b border-slate-200 bg-white', className].join(' ')}>
      <BreadcrumbBar items={items} />
    </div>
  );
}
