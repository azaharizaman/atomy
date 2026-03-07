import * as React from 'react';
import { ChevronRight, Home } from 'lucide-react';
import { cn } from '@/lib/utils';

export interface BreadcrumbItem {
  label: string;
  href?: string;
}

export interface AtomyQBreadcrumbProps {
  items: BreadcrumbItem[];
  showHome?: boolean;
  className?: string;
}

function AtomyQBreadcrumb({ items, showHome = true, className }: AtomyQBreadcrumbProps) {
  return (
    <nav aria-label="Breadcrumb" className={className}>
      <ol className="flex items-center gap-1 text-sm">
        {showHome && (
          <>
            <li>
              <a
                href="/"
                className="text-[var(--aq-text-muted)] hover:text-[var(--aq-text-primary)] transition-colors"
                aria-label="Home"
              >
                <Home className="size-4" />
              </a>
            </li>
            {items.length > 0 && (
              <li aria-hidden="true">
                <ChevronRight className="size-3.5 text-[var(--aq-text-subtle)]" />
              </li>
            )}
          </>
        )}
        {items.map((item, idx) => {
          const isLast = idx === items.length - 1;
          return (
            <React.Fragment key={idx}>
              <li>
                {isLast || !item.href ? (
                  <span
                    className={cn(
                      isLast
                        ? 'font-medium text-[var(--aq-text-primary)]'
                        : 'text-[var(--aq-text-muted)]'
                    )}
                    aria-current={isLast ? 'page' : undefined}
                  >
                    {item.label}
                  </span>
                ) : (
                  <a
                    href={item.href}
                    className="text-[var(--aq-text-muted)] hover:text-[var(--aq-text-primary)] transition-colors"
                  >
                    {item.label}
                  </a>
                )}
              </li>
              {!isLast && (
                <li aria-hidden="true">
                  <ChevronRight className="size-3.5 text-[var(--aq-text-subtle)]" />
                </li>
              )}
            </React.Fragment>
          );
        })}
      </ol>
    </nav>
  );
}

export { AtomyQBreadcrumb };
