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

