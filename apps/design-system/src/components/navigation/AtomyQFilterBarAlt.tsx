import * as React from 'react';
import { cn } from '@/lib/utils';
import { Search, ChevronDown } from 'lucide-react';

export interface FilterOptionAlt {
  value: string;
  label: string;
  count?: number;
}

export interface FilterDropdownAlt {
  label: string;
  value: string;
  options: FilterOptionAlt[];
  onChange: (value: string) => void;
}

export interface AtomyQFilterBarAltProps {
  searchValue?: string;
  onSearchChange?: (value: string) => void;
  searchPlaceholder?: string;
  statusFilters?: FilterOptionAlt[];
  activeStatus?: string;
  onStatusChange?: (value: string) => void;
  dropdowns?: FilterDropdownAlt[];
  actions?: React.ReactNode;
  className?: string;
}

function AtomyQFilterBarAlt({
  searchValue = '',
  onSearchChange,
  searchPlaceholder = 'Search...',
  statusFilters = [],
  activeStatus,
  onStatusChange,
  dropdowns = [],
  actions,
  className,
}: AtomyQFilterBarAltProps) {
  return (
    <div className={cn('flex flex-wrap items-center gap-3', className)}>
      {onSearchChange && (
        <div className="relative max-w-sm flex-1">
          <Search className="absolute left-3 top-1/2 size-3.5 -translate-y-1/2 text-[var(--aq-text-subtle)]" />
          <input
            type="text"
            value={searchValue}
            onChange={(e) => onSearchChange(e.target.value)}
            placeholder={searchPlaceholder}
            className="h-9 w-full rounded-lg border border-[var(--aq-border-strong)] bg-[var(--aq-bg-surface)] pl-9 pr-3 text-[13px] text-[var(--aq-text-primary)] placeholder:text-[var(--aq-text-subtle)] transition-colors focus:border-[var(--aq-brand-500)] focus:outline-none"
          />
        </div>
      )}

      {statusFilters.length > 0 && (
        <div className="flex items-center rounded-lg bg-[var(--aq-bg-elevated)] p-1">
          {statusFilters.map((filter) => (
            <button
              key={filter.value}
              onClick={() => onStatusChange?.(filter.value)}
              className={cn(
                'rounded-md px-2.5 py-1 text-[12px] font-medium transition-colors',
                activeStatus === filter.value
                  ? 'bg-[var(--aq-bg-surface)] text-[var(--aq-text-primary)] shadow-sm'
                  : 'text-[var(--aq-text-muted)] hover:text-[var(--aq-text-secondary)]',
              )}
            >
              {filter.label}
              {filter.count !== undefined && (
                <span className="ml-1.5 text-[10px] text-[var(--aq-text-subtle)]">
                  {filter.count}
                </span>
              )}
            </button>
          ))}
        </div>
      )}

      {dropdowns.map((dropdown) => (
        <div key={dropdown.label} className="relative">
          <select
            value={dropdown.value}
            onChange={(e) => dropdown.onChange(e.target.value)}
            className="h-9 appearance-none rounded-lg border border-[var(--aq-border-strong)] bg-[var(--aq-bg-surface)] pl-3 pr-8 text-[12px] text-[var(--aq-text-secondary)] focus:border-[var(--aq-brand-500)] focus:outline-none"
          >
            {dropdown.options.map((opt) => (
              <option key={opt.value} value={opt.value}>
                {opt.label}
              </option>
            ))}
          </select>
          <ChevronDown className="pointer-events-none absolute right-2.5 top-1/2 size-3.5 -translate-y-1/2 text-[var(--aq-text-subtle)]" />
        </div>
      ))}

      {actions && (
        <>
          <div className="flex-1" />
          {actions}
        </>
      )}
    </div>
  );
}

export { AtomyQFilterBarAlt };
