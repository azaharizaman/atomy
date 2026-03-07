import * as React from 'react';
import { cn } from '@/lib/utils';
import {
  ChevronLeft,
  ChevronRight,
  ChevronsLeft,
  ChevronsRight,
} from 'lucide-react';

export interface AtomyQPaginationAltProps {
  page: number;
  totalPages: number;
  total?: number;
  limit?: number;
  onPageChange: (page: number) => void;
  onLimitChange?: (limit: number) => void;
  limitOptions?: number[];
  className?: string;
}

function AtomyQPaginationAlt({
  page,
  totalPages,
  total,
  limit,
  onPageChange,
  onLimitChange,
  limitOptions = [10, 25, 50, 100],
  className,
}: AtomyQPaginationAltProps) {
  const getPageNumbers = () => {
    const pages: (number | '...')[] = [];
    const delta = 1;
    for (let i = 1; i <= totalPages; i++) {
      if (
        i === 1 ||
        i === totalPages ||
        (i >= page - delta && i <= page + delta)
      ) {
        pages.push(i);
      } else if (pages[pages.length - 1] !== '...') {
        pages.push('...');
      }
    }
    return pages;
  };

  const btnBase =
    'flex size-8 items-center justify-center rounded-lg border border-[var(--aq-border-strong)] bg-[var(--aq-bg-surface)] text-[12px] font-medium text-[var(--aq-text-secondary)] transition-colors hover:bg-[var(--aq-bg-elevated)] disabled:pointer-events-none disabled:opacity-40';

  return (
    <div
      className={cn('flex items-center justify-between px-3 py-2.5', className)}
    >
      <div className="flex items-center gap-2">
        {total !== undefined && (
          <span className="text-[12px] text-[var(--aq-text-muted)]">
            {total.toLocaleString()} result{total !== 1 ? 's' : ''}
          </span>
        )}
        {onLimitChange && limit && (
          <select
            value={limit}
            onChange={(e) => onLimitChange(Number(e.target.value))}
            className="h-8 rounded-lg border border-[var(--aq-border-strong)] bg-[var(--aq-bg-surface)] px-2 text-[12px] text-[var(--aq-text-secondary)] focus:outline-none"
          >
            {limitOptions.map((opt) => (
              <option key={opt} value={opt}>
                {opt} / page
              </option>
            ))}
          </select>
        )}
      </div>

      <div className="flex items-center gap-1">
        <button
          onClick={() => onPageChange(1)}
          disabled={page <= 1}
          className={btnBase}
          aria-label="First page"
        >
          <ChevronsLeft className="size-3.5" />
        </button>
        <button
          onClick={() => onPageChange(page - 1)}
          disabled={page <= 1}
          className={btnBase}
          aria-label="Previous page"
        >
          <ChevronLeft className="size-3.5" />
        </button>
        {getPageNumbers().map((p, i) =>
          p === '...' ? (
            <span
              key={`ellipsis-${i}`}
              className="flex size-8 items-center justify-center text-[12px] text-[var(--aq-text-subtle)]"
            >
              ...
            </span>
          ) : (
            <button
              key={p}
              onClick={() => onPageChange(p)}
              className={cn(
                btnBase,
                p === page &&
                  'border-[var(--aq-brand-600)] bg-[var(--aq-brand-600)] font-semibold text-white hover:bg-[var(--aq-brand-700)]',
              )}
            >
              {p}
            </button>
          ),
        )}
        <button
          onClick={() => onPageChange(page + 1)}
          disabled={page >= totalPages}
          className={btnBase}
          aria-label="Next page"
        >
          <ChevronRight className="size-3.5" />
        </button>
        <button
          onClick={() => onPageChange(totalPages)}
          disabled={page >= totalPages}
          className={btnBase}
          aria-label="Last page"
        >
          <ChevronsRight className="size-3.5" />
        </button>
      </div>
    </div>
  );
}

export { AtomyQPaginationAlt };
