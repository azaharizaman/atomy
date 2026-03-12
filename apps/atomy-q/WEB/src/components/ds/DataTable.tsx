'use client';

import React from 'react';
import { ChevronDown, ChevronRight, ChevronUp, ChevronsUpDown } from 'lucide-react';

import { Checkbox } from './Input';
import { OverflowMenuTrigger } from './Card';

export type SortDirection = 'asc' | 'desc' | null;

export interface ColumnDef<T = Record<string, unknown>> {
  key: string;
  label: string;
  width?: string;
  minWidth?: string;
  align?: 'left' | 'center' | 'right';
  sortable?: boolean;
  render?: (row: T) => React.ReactNode;
  className?: string;
}

export interface TableSort {
  key: string;
  direction: SortDirection;
}

interface SortHeaderProps {
  label: string;
  sortKey: string;
  currentSort: TableSort | null;
  onSort: (key: string) => void;
  align?: 'left' | 'center' | 'right';
}

function SortHeader({ label, sortKey, currentSort, onSort, align = 'left' }: SortHeaderProps) {
  const isActive = currentSort?.key === sortKey;
  const dir = isActive ? currentSort!.direction : null;
  const Icon = dir === 'asc' ? ChevronUp : dir === 'desc' ? ChevronDown : ChevronsUpDown;
  const alignClass = align === 'right' ? 'justify-end' : align === 'center' ? 'justify-center' : 'justify-start';

  return (
    <button
      type="button"
      onClick={() => onSort(sortKey)}
      className={['flex items-center gap-1 group w-full font-medium text-slate-600 hover:text-slate-900 transition-colors text-xs', alignClass].join(' ')}
    >
      {label}
      <Icon size={12} className={['shrink-0 transition-opacity', isActive ? 'opacity-100 text-indigo-600' : 'opacity-0 group-hover:opacity-50'].join(' ')} />
    </button>
  );
}

interface BulkActionToolbarProps {
  selectedCount: number;
  actions: { label: string; onClick: () => void; variant?: 'default' | 'destructive' }[];
  onClear: () => void;
}

export function BulkActionToolbar({ selectedCount, actions, onClear }: BulkActionToolbarProps) {
  return (
    <div className="flex items-center gap-3 px-4 py-2 bg-indigo-50 border-b border-indigo-200">
      <span className="text-xs font-semibold text-indigo-700 shrink-0">{selectedCount} selected</span>
      <div className="w-px h-4 bg-indigo-200" />
      <details className="relative">
        <summary className="list-none cursor-pointer text-xs font-medium px-2.5 py-1 rounded border text-indigo-700 border-indigo-300 bg-white hover:bg-indigo-100">
          Bulk Actions
        </summary>
        <div className="absolute top-[110%] left-0 min-w-44 bg-white border border-slate-200 rounded-md shadow-lg py-1 z-20">
          {actions.map((a, i) => (
            <button
              type="button"
              key={i}
              onClick={a.onClick}
              className={['w-full text-left px-3 py-1.5 text-xs hover:bg-slate-50', a.variant === 'destructive' ? 'text-red-600' : 'text-slate-700'].join(' ')}
            >
              {a.label}
            </button>
          ))}
        </div>
      </details>
      <div className="ml-auto">
        <button type="button" onClick={onClear} className="text-xs text-slate-500 hover:text-slate-700">
          Clear
        </button>
      </div>
    </div>
  );
}

interface DataTableProps<T extends { id: string | number }> {
  columns: ColumnDef<T>[];
  rows: T[];
  sort?: TableSort | null;
  onSort?: (sort: TableSort) => void;
  selectable?: boolean;
  selectedIds?: (string | number)[];
  onSelectChange?: (ids: (string | number)[]) => void;
  expandable?: boolean;
  expandedId?: string | number | null;
  onExpandChange?: (id: string | number | null) => void;
  renderExpanded?: (row: T) => React.ReactNode;
  expandedIndentColumns?: number;
  bulkActions?: { label: string; onClick: (ids: (string | number)[]) => void; variant?: 'default' | 'destructive' }[];
  showActions?: boolean;
  onRowAction?: (row: T) => void;
  loading?: boolean;
  emptyState?: React.ReactNode;
  stickyHeader?: boolean;
  className?: string;
  rowClassName?: (row: T) => string;
  onRowClick?: (row: T) => void;
}

export function DataTable<T extends { id: string | number }>({
  columns,
  rows,
  sort,
  onSort,
  selectable,
  selectedIds = [],
  onSelectChange,
  expandable,
  expandedId,
  onExpandChange,
  renderExpanded,
  expandedIndentColumns = 0,
  bulkActions,
  showActions,
  onRowAction,
  loading,
  emptyState,
  stickyHeader,
  className = '',
  rowClassName,
  onRowClick,
}: DataTableProps<T>) {
  const [internalSort, setInternalSort] = React.useState<TableSort | null>(null);
  const activeSort = sort !== undefined ? sort : internalSort;

  function handleSort(key: string) {
    const newDir: SortDirection =
      activeSort?.key === key ? (activeSort.direction === 'asc' ? 'desc' : activeSort.direction === 'desc' ? null : 'asc') : 'asc';
    const newSort: TableSort = { key, direction: newDir };
    if (onSort) onSort(newSort);
    else setInternalSort(newSort.direction === null ? null : newSort);
  }

  const allIds = rows.map((r) => r.id);
  const allSelected = allIds.length > 0 && allIds.every((id) => selectedIds.includes(id));
  const someSelected = selectedIds.length > 0 && !allSelected;

  function toggleAll() {
    if (!onSelectChange) return;
    onSelectChange(allSelected ? [] : allIds);
  }

  function toggleRow(id: string | number) {
    if (!onSelectChange) return;
    onSelectChange(selectedIds.includes(id) ? selectedIds.filter((x) => x !== id) : [...selectedIds, id]);
  }

  const alignClass = { left: 'text-left', center: 'text-center', right: 'text-right' };
  const colSpanCount = columns.length + (selectable ? 1 : 0) + (expandable ? 1 : 0) + (showActions ? 1 : 0);

  function pxFromWidth(width?: string, fallback = 120): number {
    if (!width) return fallback;
    if (width.endsWith('px')) {
      const parsed = Number(width.replace('px', ''));
      return Number.isFinite(parsed) ? parsed : fallback;
    }
    return fallback;
  }

  function expandedOffsetPx(): number {
    let offset = 0;
    if (selectable) offset += 40;
    if (expandable) offset += 32;
    for (let i = 0; i < expandedIndentColumns; i += 1) {
      const col = columns[i];
      offset += pxFromWidth(col?.width, i === 0 ? 96 : 128);
    }
    return offset;
  }

  function renderDataRow(row: T): React.ReactNode {
    const isSelected = selectedIds.includes(row.id);
    const isExpanded = expandedId === row.id;

    return (
      <React.Fragment key={row.id}>
        <tr
          onClick={() => onRowClick?.(row)}
          className={[
            'border-b border-slate-100 transition-colors duration-100',
            isSelected ? 'bg-indigo-50/60' : 'hover:bg-slate-50',
            onRowClick ? 'cursor-pointer' : '',
            rowClassName ? rowClassName(row) : '',
          ].join(' ')}
        >
          {selectable && (
            <td className="w-10 pl-4 pr-0 py-0" onClick={(e) => e.stopPropagation()}>
              <Checkbox checked={isSelected} onChange={() => toggleRow(row.id)} />
            </td>
          )}
          {expandable && (
            <td
              className="w-8 py-0 pl-2"
              onClick={(e) => {
                e.stopPropagation();
                onExpandChange?.(isExpanded ? null : row.id);
              }}
            >
              <button type="button" className="p-1 rounded hover:bg-slate-200 text-slate-400 hover:text-slate-700 transition-colors">
                <ChevronRight size={13} className={['transition-transform duration-200', isExpanded ? 'rotate-90' : ''].join(' ')} />
              </button>
            </td>
          )}
          {columns.map((col) => (
            <td key={col.key} className={['px-3 py-0 h-11', alignClass[col.align ?? 'left'], col.className ?? ''].join(' ')}>
              {col.render ? col.render(row) : <span className="text-sm text-slate-700">{String((row as Record<string, unknown>)[col.key] ?? '')}</span>}
            </td>
          ))}
          {showActions && (
            <td className="w-10 py-0 pr-2" onClick={(e) => e.stopPropagation()}>
              <OverflowMenuTrigger onClick={() => onRowAction?.(row)} />
            </td>
          )}
        </tr>

        {expandable && isExpanded && renderExpanded && (
          <tr className="border-b border-slate-200">
            <td colSpan={colSpanCount} className="p-0">
              <div style={{ paddingLeft: expandedOffsetPx() }}>{renderExpanded(row)}</div>
            </td>
          </tr>
        )}
      </React.Fragment>
    );
  }

  return (
    <div className={['bg-white border border-slate-200 rounded-lg overflow-hidden', className].join(' ')}>
      {selectable && selectedIds.length > 0 && bulkActions && (
        <BulkActionToolbar
          selectedCount={selectedIds.length}
          actions={bulkActions.map((a) => ({ label: a.label, onClick: () => a.onClick(selectedIds), variant: a.variant }))}
          onClear={() => onSelectChange?.([])}
        />
      )}

      <div className="overflow-x-auto">
        <table className="w-full border-collapse">
          <thead className={stickyHeader ? 'sticky top-0 z-10' : ''}>
            <tr className="bg-slate-50 border-b border-slate-200">
              {selectable && (
                <th className="w-10 pl-4 pr-0 py-2.5">
                  <Checkbox checked={allSelected} indeterminate={someSelected} onChange={toggleAll} />
                </th>
              )}
              {expandable && <th className="w-8 py-2.5" />}
              {columns.map((col) => (
                <th
                  key={col.key}
                  style={{ width: col.width, minWidth: col.minWidth }}
                  className={['px-3 py-2.5', alignClass[col.align ?? 'left'], col.className ?? ''].join(' ')}
                >
                  {col.sortable ? (
                    <SortHeader label={col.label} sortKey={col.key} currentSort={activeSort ?? null} onSort={handleSort} align={col.align} />
                  ) : (
                    <span className="text-xs font-medium text-slate-600">{col.label}</span>
                  )}
                </th>
              ))}
              {showActions && <th className="w-10 py-2.5" />}
            </tr>
          </thead>

          <tbody>
            {loading ? (
              <tr>
                <td colSpan={colSpanCount}>
                  <div className="flex items-center justify-center py-12">
                    <div className="w-5 h-5 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin" />
                  </div>
                </td>
              </tr>
            ) : rows.length === 0 ? (
              <tr>
                <td colSpan={colSpanCount}>{emptyState ?? <div className="py-12 text-center text-sm text-slate-400">No records found.</div>}</td>
              </tr>
            ) : (
              rows.map((row) => renderDataRow(row))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}

