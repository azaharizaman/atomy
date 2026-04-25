'use client';

import React from 'react';
import { X } from 'lucide-react';

import { SearchInput, SelectInput, FilterChip } from './Input';

interface FilterOption {
  value: string;
  label: string;
}

interface ActiveFilter {
  key: string;
  label: string;
  value: string;
}

interface FilterBarProps {
  searchValue?: string;
  onSearchChange?: (value: string) => void;
  searchPlaceholder?: string;
  filters?: {
    key: string;
    label: string;
    options: FilterOption[];
    value?: string;
    onChange?: (value: string) => void;
  }[];
  activeFilters?: ActiveFilter[];
  onRemoveFilter?: (key: string) => void;
  onClearAll?: () => void;
  extraActions?: React.ReactNode;
  className?: string;
}

export function FilterBar({
  searchValue = '',
  onSearchChange,
  searchPlaceholder = 'Search…',
  filters = [],
  activeFilters = [],
  onRemoveFilter,
  onClearAll,
  extraActions,
  className = '',
}: FilterBarProps) {
  return (
    <div className={['flex flex-col gap-2', className].join(' ')}>
      <div className="flex items-center gap-2 flex-wrap">
        <SearchInput
          value={searchValue}
          onChange={(e) => onSearchChange?.(e.target.value)}
          placeholder={searchPlaceholder}
          onClear={() => onSearchChange?.('')}
          containerClassName="w-64"
        />

        {filters.map((f) => (
          <SelectInput
            key={f.key}
            options={f.options}
            value={f.value ?? ''}
            onChange={(e) => f.onChange?.(e.target.value)}
            placeholder={f.label}
            compact
            containerClassName="min-w-28"
          />
        ))}

        {extraActions && <div className="ml-auto">{extraActions}</div>}
      </div>

      {activeFilters.length > 0 && (
        <div className="flex items-center gap-1.5 flex-wrap">
          <span className="text-xs text-slate-400">Active filters:</span>
          {activeFilters.map((f) => (
            <FilterChip key={f.key} label={`${f.label}: ${f.value}`} onRemove={() => onRemoveFilter?.(f.key)} />
          ))}
          {onClearAll && (
            <button
              type="button"
              onClick={onClearAll}
              className="text-xs text-slate-400 hover:text-slate-600 flex items-center gap-0.5 transition-colors"
            >
              <X size={10} /> Clear all
            </button>
          )}
        </div>
      )}
    </div>
  );
}

interface PageHeaderProps {
  title: string;
  subtitle?: string;
  subtitleTestId?: string;
  actions?: React.ReactNode;
  breadcrumb?: React.ReactNode;
  className?: string;
}

export function PageHeader({ title, subtitle, subtitleTestId, actions, breadcrumb, className = '' }: PageHeaderProps) {
  return (
    <div className={['flex items-start justify-between gap-4 mb-5', className].join(' ')}>
      <div>
        {breadcrumb && <div className="mb-1">{breadcrumb}</div>}
        <h1 className="text-lg font-semibold text-slate-900">{title}</h1>
        {subtitle && <p data-testid={subtitleTestId} className="text-sm text-slate-500 mt-0.5">{subtitle}</p>}
      </div>
      {actions && <div className="flex shrink-0 items-center gap-2 flex-nowrap">{actions}</div>}
    </div>
  );
}
