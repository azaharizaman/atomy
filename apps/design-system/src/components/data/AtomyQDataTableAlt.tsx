import * as React from 'react';
import { cn } from '@/lib/utils';
import { ArrowUp, ArrowDown, ArrowUpDown, Check } from 'lucide-react';

export type SortDirectionAlt = 'asc' | 'desc' | null;

export interface ColumnDefAlt<T> {
  key: string;
  header: string;
  accessor: (row: T) => React.ReactNode;
  sortable?: boolean;
  align?: 'left' | 'center' | 'right';
  width?: string;
}

export interface AtomyQDataTableAltProps<T> {
  columns: ColumnDefAlt<T>[];
  data: T[];
  rowKey: (row: T) => string;
  sortColumn?: string;
  sortDirection?: SortDirectionAlt;
  onSort?: (column: string) => void;
  selectable?: boolean;
  selectedRows?: Set<string>;
  onSelectRow?: (key: string, selected: boolean) => void;
  onSelectAll?: (selected: boolean) => void;
  onRowClick?: (row: T) => void;
  stickyHeader?: boolean;
  loading?: boolean;
  emptyMessage?: string;
  className?: string;
}

function AtomyQDataTableAlt<T>({
  columns,
  data,
  rowKey,
  sortColumn,
  sortDirection,
  onSort,
  selectable = false,
  selectedRows,
  onSelectRow,
  onSelectAll,
  onRowClick,
  stickyHeader = false,
  loading = false,
  emptyMessage = 'No data found.',
  className,
}: AtomyQDataTableAltProps<T>) {
  const allSelected = data.length > 0 && selectedRows?.size === data.length;
  const someSelected = (selectedRows?.size ?? 0) > 0 && !allSelected;

  const CheckboxButton = ({
    checked,
    onClick,
    label,
  }: {
    checked: boolean;
    onClick: () => void;
    label: string;
  }) => (
    <button
      onClick={onClick}
      className={cn(
        'flex size-4 items-center justify-center rounded border transition-colors',
        checked
          ? 'border-[var(--aq-brand-600)] bg-[var(--aq-brand-600)]'
          : 'border-[var(--aq-border-strong)] bg-[var(--aq-bg-surface)]',
      )}
      aria-label={label}
    >
      {checked && <Check className="size-2.5 text-white" />}
    </button>
  );

  return (
    <div
      className={cn(
        'overflow-hidden rounded-xl border border-[var(--aq-border-strong)] bg-[var(--aq-bg-surface)]',
        className,
      )}
    >
      <div className="overflow-x-auto">
        <table className="w-full border-collapse">
          <thead>
            <tr className={cn(stickyHeader && 'sticky top-0 z-10')}>
              {selectable && (
                <th className="w-10 bg-[var(--aq-bg-elevated)] px-3 py-2.5">
                  <CheckboxButton
                    checked={allSelected || someSelected}
                    onClick={() => onSelectAll?.(!allSelected)}
                    label="Select all"
                  />
                </th>
              )}
              {columns.map((col) => (
                <th
                  key={col.key}
                  className={cn(
                    'bg-[var(--aq-bg-elevated)] px-3 py-2.5 text-left',
                    col.align === 'center' && 'text-center',
                    col.align === 'right' && 'text-right',
                  )}
                  style={{ width: col.width }}
                >
                  {col.sortable ? (
                    <button
                      onClick={() => onSort?.(col.key)}
                      className="flex items-center gap-1 text-[11px] font-semibold uppercase tracking-[0.06em] text-[var(--aq-text-subtle)] transition-colors hover:text-[var(--aq-text-primary)]"
                    >
                      {col.header}
                      {sortColumn === col.key ? (
                        sortDirection === 'asc' ? (
                          <ArrowUp className="size-3" />
                        ) : (
                          <ArrowDown className="size-3" />
                        )
                      ) : (
                        <ArrowUpDown className="size-3 opacity-40" />
                      )}
                    </button>
                  ) : (
                    <span className="text-[11px] font-semibold uppercase tracking-[0.06em] text-[var(--aq-text-subtle)]">
                      {col.header}
                    </span>
                  )}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {loading
              ? Array.from({ length: 5 }).map((_, i) => (
                  <tr key={i}>
                    {selectable && (
                      <td className="px-3 py-2.5">
                        <div className="h-4 w-4 animate-pulse rounded bg-[var(--aq-bg-elevated)]" />
                      </td>
                    )}
                    {columns.map((col) => (
                      <td key={col.key} className="px-3 py-2.5">
                        <div className="h-4 animate-pulse rounded bg-[var(--aq-bg-elevated)]" />
                      </td>
                    ))}
                  </tr>
                ))
              : data.length === 0
                ? (
                    <tr>
                      <td
                        colSpan={columns.length + (selectable ? 1 : 0)}
                        className="px-6 py-12 text-center text-[13px] text-[var(--aq-text-muted)]"
                      >
                        {emptyMessage}
                      </td>
                    </tr>
                  )
                : data.map((row) => {
                    const key = rowKey(row);
                    const isSelected = selectedRows?.has(key);
                    return (
                      <tr
                        key={key}
                        onClick={() => onRowClick?.(row)}
                        className={cn(
                          'border-b border-[var(--aq-bg-elevated)] transition-colors',
                          isSelected
                            ? 'bg-[var(--aq-brand-tint-4)]'
                            : 'hover:bg-[var(--aq-hover-subtle)]',
                          onRowClick && 'cursor-pointer',
                        )}
                      >
                        {selectable && (
                          <td
                            className="px-3 py-2.5"
                            onClick={(e) => e.stopPropagation()}
                          >
                            <CheckboxButton
                              checked={!!isSelected}
                              onClick={() => onSelectRow?.(key, !isSelected)}
                              label={`Select row ${key}`}
                            />
                          </td>
                        )}
                        {columns.map((col) => (
                          <td
                            key={col.key}
                            className={cn(
                              'px-3 py-2.5 text-[13px] text-[var(--aq-text-secondary)]',
                              col.align === 'center' && 'text-center',
                              col.align === 'right' && 'text-right',
                            )}
                            style={{ width: col.width }}
                          >
                            {col.accessor(row)}
                          </td>
                        ))}
                      </tr>
                    );
                  })}
          </tbody>
        </table>
      </div>
    </div>
  );
}

export { AtomyQDataTableAlt };
