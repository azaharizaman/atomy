import * as React from 'react';
import { cn } from '@/lib/utils';
import { ArrowUpDown, ArrowUp, ArrowDown } from 'lucide-react';

export type SortDirection = 'asc' | 'desc' | null;

export interface AtomyQColumnDef<T> {
  key: string;
  header: string;
  accessor: (row: T) => React.ReactNode;
  sortable?: boolean;
  align?: 'left' | 'center' | 'right';
  width?: string;
  sticky?: boolean;
}

export interface AtomyQTableProps<T> {
  columns: AtomyQColumnDef<T>[];
  data: T[];
  rowKey: (row: T) => string;
  sortColumn?: string;
  sortDirection?: SortDirection;
  onSort?: (column: string) => void;
  onRowClick?: (row: T) => void;
  selectedRows?: Set<string>;
  onSelectRow?: (key: string, selected: boolean) => void;
  onSelectAll?: (selected: boolean) => void;
  selectable?: boolean;
  stickyHeader?: boolean;
  striped?: boolean;
  compact?: boolean;
  loading?: boolean;
  emptyMessage?: string;
  className?: string;
}

function AtomyQTable<T>({
  columns,
  data,
  rowKey,
  sortColumn,
  sortDirection,
  onSort,
  onRowClick,
  selectedRows,
  onSelectRow,
  onSelectAll,
  selectable = false,
  stickyHeader = true,
  striped = false,
  compact = false,
  loading = false,
  emptyMessage = 'No data available',
  className,
}: AtomyQTableProps<T>) {
  const allSelected = data.length > 0 && selectedRows?.size === data.length;
  const someSelected = (selectedRows?.size ?? 0) > 0 && !allSelected;

  const getSortIcon = (column: string) => {
    if (sortColumn !== column) return <ArrowUpDown className="size-3.5 text-[var(--aq-text-subtle)]" />;
    if (sortDirection === 'asc') return <ArrowUp className="size-3.5 text-[var(--aq-brand-600)]" />;
    return <ArrowDown className="size-3.5 text-[var(--aq-brand-600)]" />;
  };

  const cellPadding = compact ? 'px-3 py-1.5' : 'px-4 py-2.5';
  const headerPadding = compact ? 'px-3 py-2' : 'px-4 py-3';

  return (
    <div className={cn('w-full overflow-auto rounded-lg border border-[var(--aq-border-default)]', className)}>
      <table className="w-full caption-bottom text-sm" role="grid">
        <thead className={cn(stickyHeader && 'sticky top-0 z-10')}>
          <tr className="bg-[var(--aq-bg-elevated)] border-b border-[var(--aq-border-default)]">
            {selectable && (
              <th className={cn(headerPadding, 'w-10')}>
                <input
                  type="checkbox"
                  checked={allSelected}
                  ref={(el) => { if (el) el.indeterminate = someSelected; }}
                  onChange={(e) => onSelectAll?.(e.target.checked)}
                  className="size-4 rounded border-[var(--aq-border-strong)] accent-[var(--aq-brand-600)]"
                  aria-label="Select all rows"
                />
              </th>
            )}
            {columns.map((col) => (
              <th
                key={col.key}
                className={cn(
                  headerPadding,
                  'text-xs font-semibold text-[var(--aq-text-muted)] uppercase tracking-wider whitespace-nowrap',
                  col.align === 'center' && 'text-center',
                  col.align === 'right' && 'text-right',
                  col.sortable && 'cursor-pointer select-none hover:text-[var(--aq-text-primary)]'
                )}
                style={{ width: col.width }}
                onClick={() => col.sortable && onSort?.(col.key)}
                aria-sort={
                  sortColumn === col.key
                    ? sortDirection === 'asc' ? 'ascending' : 'descending'
                    : undefined
                }
              >
                <span className="inline-flex items-center gap-1">
                  {col.header}
                  {col.sortable && getSortIcon(col.key)}
                </span>
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {loading ? (
            <tr>
              <td colSpan={columns.length + (selectable ? 1 : 0)} className="py-12 text-center">
                <div className="flex flex-col items-center gap-2">
                  <svg className="animate-spin size-6 text-[var(--aq-brand-500)]" viewBox="0 0 24 24" fill="none">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                  <span className="text-sm text-[var(--aq-text-muted)]">Loading...</span>
                </div>
              </td>
            </tr>
          ) : data.length === 0 ? (
            <tr>
              <td colSpan={columns.length + (selectable ? 1 : 0)} className="py-12 text-center text-sm text-[var(--aq-text-muted)]">
                {emptyMessage}
              </td>
            </tr>
          ) : (
            data.map((row, idx) => {
              const key = rowKey(row);
              const isSelected = selectedRows?.has(key);

              return (
                <tr
                  key={key}
                  onClick={() => onRowClick?.(row)}
                  className={cn(
                    'border-b border-[var(--aq-border-subtle)] last:border-0 transition-colors',
                    onRowClick && 'cursor-pointer',
                    'hover:bg-[var(--aq-bg-elevated)]/50',
                    striped && idx % 2 === 1 && 'bg-[var(--aq-bg-elevated)]/30',
                    isSelected && 'bg-[var(--aq-brand-50)]'
                  )}
                >
                  {selectable && (
                    <td className={cn(cellPadding, 'w-10')} onClick={(e) => e.stopPropagation()}>
                      <input
                        type="checkbox"
                        checked={isSelected || false}
                        onChange={(e) => onSelectRow?.(key, e.target.checked)}
                        className="size-4 rounded border-[var(--aq-border-strong)] accent-[var(--aq-brand-600)]"
                        aria-label={`Select row ${key}`}
                      />
                    </td>
                  )}
                  {columns.map((col) => (
                    <td
                      key={col.key}
                      className={cn(
                        cellPadding,
                        'text-[var(--aq-text-secondary)]',
                        col.align === 'center' && 'text-center',
                        col.align === 'right' && 'text-right'
                      )}
                    >
                      {col.accessor(row)}
                    </td>
                  ))}
                </tr>
              );
            })
          )}
        </tbody>
      </table>
    </div>
  );
}

export { AtomyQTable };
