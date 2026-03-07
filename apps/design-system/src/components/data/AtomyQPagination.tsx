import * as React from 'react';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-react';
import { cn } from '@/lib/utils';

export interface AtomyQPaginationProps {
  page: number;
  totalPages: number;
  total: number;
  limit: number;
  onPageChange: (page: number) => void;
  onLimitChange?: (limit: number) => void;
  limitOptions?: number[];
  className?: string;
}

function AtomyQPagination({
  page,
  totalPages,
  total,
  limit,
  onPageChange,
  onLimitChange,
  limitOptions = [10, 25, 50, 100],
  className,
}: AtomyQPaginationProps) {
  const start = (page - 1) * limit + 1;
  const end = Math.min(page * limit, total);

  const getPageNumbers = () => {
    const pages: (number | '...')[] = [];
    if (totalPages <= 7) {
      for (let i = 1; i <= totalPages; i++) pages.push(i);
    } else {
      pages.push(1);
      if (page > 3) pages.push('...');
      for (let i = Math.max(2, page - 1); i <= Math.min(totalPages - 1, page + 1); i++) {
        pages.push(i);
      }
      if (page < totalPages - 2) pages.push('...');
      pages.push(totalPages);
    }
    return pages;
  };

  return (
    <div className={cn('flex items-center justify-between gap-4 px-2 py-3', className)}>
      <div className="flex items-center gap-2 text-sm text-[var(--aq-text-muted)]">
        <span>
          Showing {start}–{end} of {total}
        </span>
        {onLimitChange && (
          <select
            value={limit}
            onChange={(e) => onLimitChange(Number(e.target.value))}
            className="h-8 rounded-md border border-[var(--aq-border-default)] bg-white px-2 text-xs outline-none"
            aria-label="Rows per page"
          >
            {limitOptions.map((opt) => (
              <option key={opt} value={opt}>{opt} / page</option>
            ))}
          </select>
        )}
      </div>

      <nav className="flex items-center gap-1" aria-label="Pagination">
        <button
          onClick={() => onPageChange(1)}
          disabled={page === 1}
          className="inline-flex size-8 items-center justify-center rounded-md text-[var(--aq-text-muted)] hover:bg-[var(--aq-bg-elevated)] disabled:opacity-50 disabled:pointer-events-none"
          aria-label="First page"
        >
          <ChevronsLeft className="size-4" />
        </button>
        <button
          onClick={() => onPageChange(page - 1)}
          disabled={page === 1}
          className="inline-flex size-8 items-center justify-center rounded-md text-[var(--aq-text-muted)] hover:bg-[var(--aq-bg-elevated)] disabled:opacity-50 disabled:pointer-events-none"
          aria-label="Previous page"
        >
          <ChevronLeft className="size-4" />
        </button>

        {getPageNumbers().map((p, i) =>
          p === '...' ? (
            <span key={`ellipsis-${i}`} className="size-8 flex items-center justify-center text-sm text-[var(--aq-text-subtle)]">
              ...
            </span>
          ) : (
            <button
              key={p}
              onClick={() => onPageChange(p)}
              className={cn(
                'inline-flex size-8 items-center justify-center rounded-md text-sm transition-colors',
                p === page
                  ? 'bg-[var(--aq-brand-600)] text-white font-medium'
                  : 'text-[var(--aq-text-secondary)] hover:bg-[var(--aq-bg-elevated)]'
              )}
              aria-current={p === page ? 'page' : undefined}
            >
              {p}
            </button>
          )
        )}

        <button
          onClick={() => onPageChange(page + 1)}
          disabled={page === totalPages}
          className="inline-flex size-8 items-center justify-center rounded-md text-[var(--aq-text-muted)] hover:bg-[var(--aq-bg-elevated)] disabled:opacity-50 disabled:pointer-events-none"
          aria-label="Next page"
        >
          <ChevronRight className="size-4" />
        </button>
        <button
          onClick={() => onPageChange(totalPages)}
          disabled={page === totalPages}
          className="inline-flex size-8 items-center justify-center rounded-md text-[var(--aq-text-muted)] hover:bg-[var(--aq-bg-elevated)] disabled:opacity-50 disabled:pointer-events-none"
          aria-label="Last page"
        >
          <ChevronsRight className="size-4" />
        </button>
      </nav>
    </div>
  );
}

export { AtomyQPagination };
