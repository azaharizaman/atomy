import React from 'react';
import { ChevronLeft, ChevronRight, MoreHorizontal } from 'lucide-react';

interface PaginationProps {
  page: number;
  totalPages: number;
  onPageChange: (page: number) => void;
  totalItems?: number;
  pageSize?: number;
  showPageNumbers?: boolean;
  className?: string;
  compact?: boolean;
}

function getPageRange(current: number, total: number): (number | '...')[] {
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
  const pages: (number | '...')[] = [1];
  if (current > 3) pages.push('...');
  const start = Math.max(2, current - 1);
  const end = Math.min(total - 1, current + 1);
  for (let i = start; i <= end; i++) pages.push(i);
  if (current < total - 2) pages.push('...');
  pages.push(total);
  return pages;
}

export function Pagination({
  page, totalPages, onPageChange,
  totalItems, pageSize,
  showPageNumbers = true,
  className = '', compact = false,
}: PaginationProps) {

  const pageRange = getPageRange(page, totalPages);

  const btnBase =
    'inline-flex items-center justify-center rounded border text-xs font-medium transition-colors duration-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1';

  const sizeClass = compact ? 'h-6 min-w-6 px-1.5' : 'h-7 min-w-7 px-2';

  return (
    <div className={['flex items-center justify-between', className].join(' ')}>
      {/* Left: item count info */}
      <div className="text-xs text-slate-500">
        {totalItems !== undefined && pageSize !== undefined ? (
          <>
            <span className="font-medium text-slate-700">
              {Math.min((page - 1) * pageSize + 1, totalItems)}–{Math.min(page * pageSize, totalItems)}
            </span>
            {' of '}
            <span className="font-medium text-slate-700">{totalItems}</span>
            {' items'}
          </>
        ) : (
          `Page ${page} of ${totalPages}`
        )}
      </div>

      {/* Right: controls */}
      <div className="flex items-center gap-1">
        {/* Prev */}
        <button
          disabled={page <= 1}
          onClick={() => onPageChange(page - 1)}
          className={[
            btnBase, sizeClass,
            'gap-1',
            page <= 1
              ? 'border-slate-200 text-slate-300 cursor-not-allowed'
              : 'border-slate-200 text-slate-600 hover:bg-slate-50 hover:border-slate-300',
          ].join(' ')}
        >
          <ChevronLeft size={12} />
          {!compact && <span>Previous</span>}
        </button>

        {/* Page numbers */}
        {showPageNumbers && pageRange.map((p, i) =>
          p === '...' ? (
            <span key={`ellipsis-${i}`} className={[sizeClass, 'inline-flex items-center justify-center text-slate-400'].join(' ')}>
              <MoreHorizontal size={12} />
            </span>
          ) : (
            <button
              key={p}
              onClick={() => onPageChange(p as number)}
              className={[
                btnBase, sizeClass,
                p === page
                  ? 'bg-indigo-600 text-white border-indigo-600'
                  : 'border-slate-200 text-slate-600 hover:bg-slate-50 hover:border-slate-300',
              ].join(' ')}
            >
              {p}
            </button>
          )
        )}

        {/* Next */}
        <button
          disabled={page >= totalPages}
          onClick={() => onPageChange(page + 1)}
          className={[
            btnBase, sizeClass,
            'gap-1',
            page >= totalPages
              ? 'border-slate-200 text-slate-300 cursor-not-allowed'
              : 'border-slate-200 text-slate-600 hover:bg-slate-50 hover:border-slate-300',
          ].join(' ')}
        >
          {!compact && <span>Next</span>}
          <ChevronRight size={12} />
        </button>
      </div>
    </div>
  );
}

// ─── Simple Page Footer (table bottom bar) ────────────────────────────────────

interface TableFooterProps {
  page: number;
  totalPages: number;
  onPageChange: (page: number) => void;
  totalItems?: number;
  pageSize?: number;
  className?: string;
}

export function TableFooter({ page, totalPages, onPageChange, totalItems, pageSize, className = '' }: TableFooterProps) {
  return (
    <div className={['px-4 py-3 border-t border-slate-200 bg-white rounded-b-lg', className].join(' ')}>
      <Pagination
        page={page}
        totalPages={totalPages}
        onPageChange={onPageChange}
        totalItems={totalItems}
        pageSize={pageSize}
        showPageNumbers
      />
    </div>
  );
}
