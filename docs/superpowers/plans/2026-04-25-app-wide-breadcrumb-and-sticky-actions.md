# App-Wide Breadcrumb And Sticky Actions Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the shared header the only breadcrumb surface in Atomy-Q and add a reusable sticky save-action dock for editable screens, starting with RFQ Details.

**Architecture:** Route-aware breadcrumb construction moves into shared layout code so individual pages stop rendering their own breadcrumb rows. A reusable sticky action component observes the inline header actions and mirrors them in a floating dock only when edit-mode actions scroll out of view. The first behavior adoption happens on RFQ Details, then the shared pattern becomes available to other editable screens.

**Tech Stack:** Next.js App Router, React 19, TypeScript, Tailwind CSS, Vitest, Testing Library

---

## File Map

- Modify: `apps/atomy-q/WEB/src/components/layout/header.tsx`
  - Replace raw pathname text with semantic breadcrumb rendering.
- Create: `apps/atomy-q/WEB/src/lib/header-breadcrumbs.ts`
  - Central route-aware breadcrumb builder for dashboard and RFQ workspace routes.
- Create: `apps/atomy-q/WEB/src/lib/header-breadcrumbs.test.ts`
  - Unit coverage for static routes and RFQ workspace route normalization.
- Modify: `apps/atomy-q/WEB/src/config/nav.ts`
  - Expose any route-label helpers needed by the breadcrumb builder without duplicating label maps.
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/overview/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/details/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/line-items/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/[runId]/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/approvals/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/negotiations/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/risk/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/documents/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/decision-trail/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/[section]/page.tsx`
  - Remove page-body `WorkspaceBreadcrumbs` usage from RFQ workspace screens.
- Create: `apps/atomy-q/WEB/src/components/ds/sticky-page-actions.tsx`
  - Shared floating action dock driven by inline action visibility.
- Create: `apps/atomy-q/WEB/src/components/ds/sticky-page-actions.test.tsx`
  - Unit coverage for dock visibility and fallback behavior.
- Modify: `apps/atomy-q/WEB/src/components/ds/Button.tsx`
  - Harden non-wrapping button labels and optional minimum-width support for primary actions.
- Modify: `apps/atomy-q/WEB/src/components/ds/FilterBar.tsx`
  - Keep `PageHeader` actions stable and observable without awkward wrapping.
- Create: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/details/page.test.tsx`
  - Page coverage for RFQ Details header action continuity.
- Modify: `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`
  - Record the shared breadcrumb/sticky-action standard and the first adoption.

## Task 1: Build Shared Header Breadcrumbs

**Files:**
- Create: `apps/atomy-q/WEB/src/lib/header-breadcrumbs.ts`
- Create: `apps/atomy-q/WEB/src/lib/header-breadcrumbs.test.ts`
- Modify: `apps/atomy-q/WEB/src/config/nav.ts`
- Modify: `apps/atomy-q/WEB/src/components/layout/header.tsx`

- [ ] **Step 1: Write the failing breadcrumb builder tests**

```ts
import { describe, expect, it } from 'vitest';

import { buildHeaderBreadcrumbs } from './header-breadcrumbs';

describe('buildHeaderBreadcrumbs', () => {
  it('builds breadcrumb items for a top-level route', () => {
    expect(buildHeaderBreadcrumbs({ pathname: '/vendors' })).toEqual([
      { label: 'Atomy-Q', href: '/' },
      { label: 'Vendors', current: true },
    ]);
  });

  it('normalizes RFQ workspace routes with record context', () => {
    expect(
      buildHeaderBreadcrumbs({
        pathname: '/rfqs/rfq-1/details',
        rfqTitle: 'Desktop Purchase',
      }),
    ).toEqual([
      { label: 'Atomy-Q', href: '/' },
      { label: 'RFQs', href: '/rfqs' },
      { label: 'Desktop Purchase', href: '/rfqs/rfq-1/overview' },
      { label: 'Details', current: true },
    ]);
  });

  it('falls back to a stable placeholder while RFQ context is loading', () => {
    expect(
      buildHeaderBreadcrumbs({
        pathname: '/rfqs/rfq-1/comparison-runs/run-42',
      }),
    ).toEqual([
      { label: 'Atomy-Q', href: '/' },
      { label: 'RFQs', href: '/rfqs' },
      { label: 'RFQ', href: '/rfqs/rfq-1/overview' },
      { label: 'Comparison Runs', href: '/rfqs/rfq-1/comparison-runs' },
      { label: 'Run Details', current: true },
    ]);
  });
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `cd apps/atomy-q/WEB && npm run test:unit -- src/lib/header-breadcrumbs.test.ts`

Expected: FAIL because `src/lib/header-breadcrumbs.ts` does not exist yet.

- [ ] **Step 3: Write the minimal breadcrumb builder and route-label helpers**

```ts
import { getLabelForPath } from '@/config/nav';

export type HeaderBreadcrumbItem = {
  label: string;
  href?: string;
  current?: boolean;
};

const RFQ_SECTION_LABELS: Record<string, string> = {
  overview: 'Overview',
  details: 'Details',
  'line-items': 'Line Items',
  vendors: 'Vendors',
  'quote-intake': 'Quote Intake',
  'comparison-runs': 'Comparison Runs',
  approvals: 'Approvals',
  negotiations: 'Negotiations',
  award: 'Award',
  risk: 'Risk & Compliance',
  documents: 'Documents',
  'decision-trail': 'Decision Trail',
};

type BuildHeaderBreadcrumbsInput = {
  pathname: string;
  rfqTitle?: string | null;
};

export function buildHeaderBreadcrumbs({
  pathname,
  rfqTitle,
}: BuildHeaderBreadcrumbsInput): HeaderBreadcrumbItem[] {
  if (!pathname.startsWith('/rfqs/') || pathname === '/rfqs' || pathname === '/rfqs/') {
    return [
      { label: 'Atomy-Q', href: '/' },
      { label: getLabelForPath(pathname), current: true },
    ];
  }

  const segments = pathname.split('/').filter(Boolean);
  const rfqId = segments[1];
  const section = segments[2] ?? 'overview';
  const nestedId = segments[3];
  const rfqLabel = rfqTitle?.trim() ? rfqTitle.trim() : 'RFQ';
  const sectionLabel = RFQ_SECTION_LABELS[section] ?? getLabelForPath(`/${section}`);

  const items: HeaderBreadcrumbItem[] = [
    { label: 'Atomy-Q', href: '/' },
    { label: 'RFQs', href: '/rfqs' },
    { label: rfqLabel, href: `/rfqs/${encodeURIComponent(rfqId)}/overview` },
  ];

  if (section === 'comparison-runs' && nestedId) {
    items.push({ label: sectionLabel, href: `/rfqs/${encodeURIComponent(rfqId)}/comparison-runs` });
    items.push({ label: 'Run Details', current: true });
    return items;
  }

  if (section === 'quote-intake' && nestedId && segments[4] === 'normalize') {
    items.push({ label: sectionLabel, href: `/rfqs/${encodeURIComponent(rfqId)}/quote-intake` });
    items.push({ label: 'Normalize Quote', current: true });
    return items;
  }

  if (section === 'quote-intake' && nestedId) {
    items.push({ label: sectionLabel, href: `/rfqs/${encodeURIComponent(rfqId)}/quote-intake` });
    items.push({ label: 'Quote Details', current: true });
    return items;
  }

  items.push({ label: sectionLabel, current: true });
  return items;
}
```

```ts
export function getLabelForPath(pathname: string): string {
  if (pathToLabel[pathname]) return pathToLabel[pathname];
  const normalized = pathname.split('?')[0];
  if (pathToLabel[normalized]) return pathToLabel[normalized];
  const last = normalized.split('/').filter(Boolean).pop();
  return last ? last.replace(/-/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase()) : 'Dashboard';
}
```

- [ ] **Step 4: Run the breadcrumb tests to verify they pass**

Run: `cd apps/atomy-q/WEB && npm run test:unit -- src/lib/header-breadcrumbs.test.ts`

Expected: PASS with all breadcrumb-builder cases green.

- [ ] **Step 5: Add a failing header render test**

```ts
import React from 'react';
import { describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import { renderWithProviders } from '@/test/utils';

vi.mock('next/navigation', () => ({
  usePathname: () => '/rfqs/rfq-1/details',
  useRouter: () => ({ push: vi.fn() }),
}));

vi.mock('@/store/use-auth-store', () => ({
  useAuthStore: (selector: (state: { user: { name: string; email: string; tenantId: string }; logout: () => void }) => unknown) =>
    selector({
      user: { name: 'Aisyah Lim', email: 'aisyah@example.test', tenantId: 'tenant-1' },
      logout: vi.fn(),
    }),
}));

vi.mock('@/hooks/use-rfq', () => ({
  useRfq: () => ({
    data: { title: 'Desktop Purchase' },
    isLoading: false,
    isError: false,
  }),
}));

import { Header } from './header';

describe('Header', () => {
  it('renders semantic breadcrumb items for RFQ workspace routes', () => {
    renderWithProviders(<Header />);

    expect(screen.getByRole('navigation', { name: /breadcrumb/i })).toBeInTheDocument();
    expect(screen.getByRole('link', { name: 'RFQs' })).toHaveAttribute('href', '/rfqs');
    expect(screen.getByText('Desktop Purchase')).toBeInTheDocument();
    expect(screen.getByText('Details')).toHaveAttribute('aria-current', 'page');
  });
});
```

- [ ] **Step 6: Run the header test to verify it fails**

Run: `cd apps/atomy-q/WEB && npm run test:unit -- src/components/layout/header.test.tsx`

Expected: FAIL because the current `Header` still renders a raw text string and has no semantic breadcrumb nav.

- [ ] **Step 7: Update the shared header to render breadcrumb items instead of a raw pathname**

```tsx
import Link from 'next/link';
import { ChevronRight } from 'lucide-react';
import { buildHeaderBreadcrumbs } from '@/lib/header-breadcrumbs';
import { useRfq } from '@/hooks/use-rfq';

function getWorkspaceRfqId(pathname: string): string | null {
  if (!pathname.startsWith('/rfqs/')) return null;
  const segments = pathname.split('/').filter(Boolean);
  return segments[0] === 'rfqs' && segments[1] ? segments[1] : null;
}

export function Header() {
  const pathname = usePathname();
  const rfqId = getWorkspaceRfqId(pathname);
  const { data: rfq } = useRfq(rfqId ?? '', { enabled: rfqId !== null });
  const breadcrumbItems = buildHeaderBreadcrumbs({
    pathname,
    rfqTitle: rfq?.title,
  });

  return (
    <header className="h-14 border-b border-slate-200 bg-white flex items-center justify-between px-4 sticky top-0 z-10">
      <nav aria-label="Breadcrumb" className="min-w-0 flex items-center gap-1 text-sm">
        {breadcrumbItems.map((item, index) => {
          const last = index === breadcrumbItems.length - 1;
          return (
            <React.Fragment key={`${item.label}-${index}`}>
              {item.href && !last ? (
                <Link href={item.href} className="truncate text-slate-500 hover:text-indigo-600">
                  {item.label}
                </Link>
              ) : (
                <span
                  className={last ? 'truncate text-slate-900 font-medium' : 'truncate text-slate-500'}
                  aria-current={last ? 'page' : undefined}
                >
                  {item.label}
                </span>
              )}
              {!last && <ChevronRight size={12} className="shrink-0 text-slate-300" />}
            </React.Fragment>
          );
        })}
      </nav>
      {/* existing search/actions/user menu stay unchanged */}
    </header>
  );
}
```

- [ ] **Step 8: Run the breadcrumb test set to verify it passes**

Run: `cd apps/atomy-q/WEB && npm run test:unit -- src/lib/header-breadcrumbs.test.ts src/components/layout/header.test.tsx`

Expected: PASS with semantic breadcrumb coverage green.

- [ ] **Step 9: Commit**

```bash
git add \
  apps/atomy-q/WEB/src/lib/header-breadcrumbs.ts \
  apps/atomy-q/WEB/src/lib/header-breadcrumbs.test.ts \
  apps/atomy-q/WEB/src/config/nav.ts \
  apps/atomy-q/WEB/src/components/layout/header.tsx \
  apps/atomy-q/WEB/src/components/layout/header.test.tsx
git commit -m "feat: standardize app header breadcrumbs"
```

## Task 2: Remove Duplicate RFQ Page-Body Breadcrumbs

**Files:**
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/overview/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/details/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/line-items/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/vendors/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/quote-intake/[quoteId]/normalize/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/comparison-runs/[runId]/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/approvals/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/negotiations/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/award/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/risk/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/documents/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/decision-trail/page.tsx`
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/[section]/page.tsx`
- Modify tests that currently rely on in-page breadcrumb text where needed

- [ ] **Step 1: Write a failing RFQ page regression test that asserts the page-body breadcrumb row is gone**

```ts
import React from 'react';
import { describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import { renderWithProviders } from '@/test/utils';

vi.mock('@/hooks/use-rfq', () => ({
  useRfq: () => ({
    data: {
      id: 'rfq-1',
      title: 'Desktop Purchase',
      status: 'draft',
      category: 'Fixed Assets',
      submission_deadline: '2026-04-30T10:00:00Z',
    },
    isLoading: false,
    isError: false,
    error: null,
  }),
  useUpdateRfq: () => ({
    mutateAsync: vi.fn(),
    isPending: false,
  }),
}));

import RfqDetailsPage from './page';

describe('RfqDetailsPage breadcrumb placement', () => {
  it('does not render a page-body breadcrumb row', async () => {
    renderWithProviders(<RfqDetailsPage params={Promise.resolve({ rfqId: 'rfq-1' })} />);

    expect(await screen.findByRole('heading', { name: 'RFQ details' })).toBeInTheDocument();
    expect(screen.queryByRole('navigation', { name: /breadcrumb/i })).not.toBeInTheDocument();
  });
});
```

- [ ] **Step 2: Run the RFQ Details test to verify it fails**

Run: `cd apps/atomy-q/WEB && npm run test:unit -- src/app/'(dashboard)'/rfqs/'[rfqId]'/details/page.test.tsx`

Expected: FAIL because the page still renders `WorkspaceBreadcrumbs`.

- [ ] **Step 3: Remove `WorkspaceBreadcrumbs` imports and markup from RFQ workspace pages**

```tsx
// Before
import { WorkspaceBreadcrumbs } from '@/components/workspace/workspace-breadcrumbs';

return (
  <div className="space-y-5">
    <WorkspaceBreadcrumbs items={breadcrumbItems} />
    <PageHeader ... />
  </div>
);

// After
return (
  <div className="space-y-5">
    <PageHeader ... />
  </div>
);
```

```tsx
// Also remove breadcrumbItems arrays that only fed WorkspaceBreadcrumbs.
// Keep any data still required by page-local links or titles.
```

- [ ] **Step 4: Run the targeted RFQ workspace page tests to verify they pass without page-body breadcrumbs**

Run: `cd apps/atomy-q/WEB && npm run test:unit -- src/app/'(dashboard)'/rfqs/'[rfqId]'/overview/page.test.tsx src/app/'(dashboard)'/rfqs/'[rfqId]'/details/page.test.tsx src/app/'(dashboard)'/rfqs/'[rfqId]'/line-items/page.test.tsx src/app/'(dashboard)'/rfqs/'[rfqId]'/vendors/page.test.tsx src/app/'(dashboard)'/rfqs/'[rfqId]'/comparison-runs/page.test.tsx src/app/'(dashboard)'/rfqs/'[rfqId]'/comparison-runs/'[runId]'/page.test.tsx src/app/'(dashboard)'/rfqs/'[rfqId]'/quote-intake/page.test.tsx src/app/'(dashboard)'/rfqs/'[rfqId]'/quote-intake/'[quoteId]'/normalize/page.test.tsx src/app/'(dashboard)'/rfqs/'[rfqId]'/award/page.test.tsx src/app/'(dashboard)'/rfqs/'[rfqId]'/approvals/page.test.tsx src/app/'(dashboard)'/rfqs/'[rfqId]'/risk/page.test.tsx`

Expected: PASS with no page regressions from breadcrumb removal.

- [ ] **Step 5: Commit**

```bash
git add apps/atomy-q/WEB/src/app/(dashboard)/rfqs
git commit -m "refactor: remove duplicate rfq workspace breadcrumbs"
```

## Task 3: Add A Shared Sticky Page Actions Primitive

**Files:**
- Create: `apps/atomy-q/WEB/src/components/ds/sticky-page-actions.tsx`
- Create: `apps/atomy-q/WEB/src/components/ds/sticky-page-actions.test.tsx`
- Modify: `apps/atomy-q/WEB/src/components/ds/Button.tsx`
- Modify: `apps/atomy-q/WEB/src/components/ds/FilterBar.tsx`

- [ ] **Step 1: Write the failing sticky action tests**

```tsx
import React from 'react';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import { screen } from '@testing-library/react';
import { render } from '@testing-library/react';

import { StickyPageActions } from './sticky-page-actions';

class MockIntersectionObserver {
  callback: IntersectionObserverCallback;

  constructor(callback: IntersectionObserverCallback) {
    this.callback = callback;
  }

  observe() {
    this.callback([{ isIntersecting: false } as IntersectionObserverEntry], this as unknown as IntersectionObserver);
  }

  disconnect() {}
  unobserve() {}
  takeRecords() {
    return [];
  }
}

describe('StickyPageActions', () => {
  beforeEach(() => {
    vi.stubGlobal('IntersectionObserver', MockIntersectionObserver);
  });

  it('renders the floating dock when the inline actions are not visible', () => {
    const targetRef = { current: document.createElement('div') };

    render(
      <StickyPageActions active targetRef={targetRef}>
        <button type="button">Save changes</button>
      </StickyPageActions>,
    );

    expect(screen.getByTestId('sticky-page-actions')).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /save changes/i })).toBeInTheDocument();
  });

  it('renders nothing when inactive', () => {
    const targetRef = { current: document.createElement('div') };

    const { container } = render(
      <StickyPageActions active={false} targetRef={targetRef}>
        <button type="button">Save changes</button>
      </StickyPageActions>,
    );

    expect(container).toBeEmptyDOMElement();
  });
});
```

- [ ] **Step 2: Run the sticky action tests to verify they fail**

Run: `cd apps/atomy-q/WEB && npm run test:unit -- src/components/ds/sticky-page-actions.test.tsx`

Expected: FAIL because the component does not exist yet.

- [ ] **Step 3: Implement the sticky action dock and shared action-row hardening**

```tsx
'use client';

import React from 'react';

type StickyPageActionsProps = {
  active: boolean;
  targetRef: React.RefObject<HTMLElement | null>;
  children: React.ReactNode;
  insetClassName?: string;
};

export function StickyPageActions({
  active,
  targetRef,
  children,
  insetClassName = 'max-w-7xl',
}: StickyPageActionsProps) {
  const [showDock, setShowDock] = React.useState(false);

  React.useEffect(() => {
    if (!active || !targetRef.current || typeof IntersectionObserver === 'undefined') {
      setShowDock(false);
      return;
    }

    const observer = new IntersectionObserver(
      ([entry]) => setShowDock(!entry.isIntersecting),
      { threshold: 0.95 },
    );

    observer.observe(targetRef.current);
    return () => observer.disconnect();
  }, [active, targetRef]);

  if (!active || !showDock) {
    return null;
  }

  return (
    <div className="fixed inset-x-0 bottom-4 z-30 pointer-events-none px-4">
      <div className={`mx-auto flex justify-end ${insetClassName}`}>
        <div
          data-testid="sticky-page-actions"
          className="pointer-events-auto flex items-center gap-2 rounded-xl border border-slate-200 bg-white/95 px-3 py-3 shadow-lg backdrop-blur"
        >
          {children}
        </div>
      </div>
    </div>
  );
}
```

```tsx
// Button.tsx
const base =
  'inline-flex min-w-fit items-center justify-center font-medium transition-colors duration-150 select-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-1 whitespace-nowrap';
```

```tsx
// FilterBar.tsx
{actions && <div className="flex items-center gap-2 shrink-0 flex-nowrap">{actions}</div>}
```

- [ ] **Step 4: Run the component tests to verify they pass**

Run: `cd apps/atomy-q/WEB && npm run test:unit -- src/components/ds/sticky-page-actions.test.tsx`

Expected: PASS with dock visibility logic and inactive-state coverage green.

- [ ] **Step 5: Commit**

```bash
git add \
  apps/atomy-q/WEB/src/components/ds/sticky-page-actions.tsx \
  apps/atomy-q/WEB/src/components/ds/sticky-page-actions.test.tsx \
  apps/atomy-q/WEB/src/components/ds/Button.tsx \
  apps/atomy-q/WEB/src/components/ds/FilterBar.tsx
git commit -m "feat: add shared sticky page actions"
```

## Task 4: Adopt Sticky Actions On RFQ Details

**Files:**
- Modify: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/details/page.tsx`
- Create: `apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/details/page.test.tsx`

- [ ] **Step 1: Extend the RFQ Details test with sticky-action behavior**

```tsx
it('shows a floating save dock when edit actions leave the viewport', async () => {
  class MockIntersectionObserver {
    constructor(private callback: IntersectionObserverCallback) {}
    observe() {
      this.callback([{ isIntersecting: false } as IntersectionObserverEntry], this as unknown as IntersectionObserver);
    }
    disconnect() {}
    unobserve() {}
    takeRecords() {
      return [];
    }
  }

  vi.stubGlobal('IntersectionObserver', MockIntersectionObserver);

  renderWithProviders(<RfqDetailsPage params={Promise.resolve({ rfqId: 'rfq-1' })} />);

  const editButton = await screen.findByRole('button', { name: /edit/i });
  editButton.click();

  expect(await screen.findAllByRole('button', { name: /save changes/i })).toHaveLength(2);
  expect(screen.getByTestId('sticky-page-actions')).toBeInTheDocument();
});
```

- [ ] **Step 2: Run the RFQ Details test to verify it fails**

Run: `cd apps/atomy-q/WEB && npm run test:unit -- src/app/'(dashboard)'/rfqs/'[rfqId]'/details/page.test.tsx`

Expected: FAIL because RFQ Details does not yet render the shared sticky dock.

- [ ] **Step 3: Refactor RFQ Details to share one action set between the header and sticky dock**

```tsx
import { StickyPageActions } from '@/components/ds/sticky-page-actions';

const headerActionsRef = React.useRef<HTMLDivElement | null>(null);

const editActions = !isEditing ? (
  <Button variant="outline" size="sm" type="button" onClick={openEdit}>
    <SquarePen size={14} className="mr-1.5" />
    Edit
  </Button>
) : (
  <>
    <Button variant="ghost" size="sm" type="button" onClick={cancelEdit} disabled={updateRfq.isPending}>
      <X size={14} className="mr-1.5" />
      Cancel
    </Button>
    <Button
      variant="primary"
      size="sm"
      type="submit"
      form="rfq-details-form"
      loading={updateRfq.isPending}
      className="min-w-[8.5rem]"
    >
      Save changes
    </Button>
  </>
);

<PageHeader
  title="RFQ details"
  subtitle="Metadata, commercial fields, and schedule milestones"
  actions={<div ref={headerActionsRef} className="flex items-center gap-2 flex-nowrap">{editActions}</div>}
/>

<StickyPageActions active={isEditing} targetRef={headerActionsRef}>
  {editActions}
</StickyPageActions>

<form id="rfq-details-form" onSubmit={onSubmit} className="space-y-5 pb-24">
  {/* existing form content */}
</form>
```

- [ ] **Step 4: Run the RFQ Details tests to verify they pass**

Run: `cd apps/atomy-q/WEB && npm run test:unit -- src/app/'(dashboard)'/rfqs/'[rfqId]'/details/page.test.tsx`

Expected: PASS with duplicate-breadcrumb removal and sticky-save continuity covered.

- [ ] **Step 5: Commit**

```bash
git add \
  apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/details/page.tsx \
  apps/atomy-q/WEB/src/app/(dashboard)/rfqs/[rfqId]/details/page.test.tsx
git commit -m "feat: keep rfq detail save actions reachable"
```

## Task 5: Verify, Document, And Close Out

**Files:**
- Modify: `apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md`

- [ ] **Step 1: Update the WEB implementation summary**

```md
## 2026-04-25 - App-wide breadcrumb and sticky form actions

- Standardized breadcrumbs into the shared top header and removed duplicate RFQ workspace breadcrumb rows.
- Added `buildHeaderBreadcrumbs` for route-aware breadcrumb rendering across dashboard and RFQ workspace routes.
- Added `StickyPageActions` as the shared floating action dock for editable screens when inline save actions scroll out of view.
- Adopted the sticky action pattern on RFQ Details and tightened button/header action wrapping rules for single-line save labels.
```

- [ ] **Step 2: Run the focused verification suite**

Run:

```bash
cd apps/atomy-q/WEB && npm run test:unit -- \
  src/lib/header-breadcrumbs.test.ts \
  src/components/layout/header.test.tsx \
  src/components/ds/sticky-page-actions.test.tsx \
  src/app/'(dashboard)'/rfqs/'[rfqId]'/details/page.test.tsx \
  src/app/'(dashboard)'/rfqs/'[rfqId]'/overview/page.test.tsx \
  src/app/'(dashboard)'/rfqs/'[rfqId]'/line-items/page.test.tsx \
  src/app/'(dashboard)'/rfqs/'[rfqId]'/vendors/page.test.tsx \
  src/app/'(dashboard)'/rfqs/'[rfqId]'/comparison-runs/page.test.tsx \
  src/app/'(dashboard)'/rfqs/'[rfqId]'/comparison-runs/'[runId]'/page.test.tsx \
  src/app/'(dashboard)'/rfqs/'[rfqId]'/quote-intake/page.test.tsx \
  src/app/'(dashboard)'/rfqs/'[rfqId]'/quote-intake/'[quoteId]'/normalize/page.test.tsx \
  src/app/'(dashboard)'/rfqs/'[rfqId]'/award/page.test.tsx \
  src/app/'(dashboard)'/rfqs/'[rfqId]'/approvals/page.test.tsx \
  src/app/'(dashboard)'/rfqs/'[rfqId]'/risk/page.test.tsx
```

Expected: PASS with no RFQ workspace regressions and the new shared layout behavior covered.

- [ ] **Step 3: Run lint on touched shared files**

Run:

```bash
cd apps/atomy-q/WEB && npm run lint -- \
  src/components/layout/header.tsx \
  src/lib/header-breadcrumbs.ts \
  src/components/ds/sticky-page-actions.tsx \
  src/components/ds/Button.tsx \
  src/components/ds/FilterBar.tsx \
  src/app/'(dashboard)'/rfqs/'[rfqId]'/details/page.tsx
```

Expected: PASS with no lint errors on shared layout and RFQ Details files.

- [ ] **Step 4: Commit documentation and final verification updates**

```bash
git add apps/atomy-q/WEB/IMPLEMENTATION_SUMMARY.md
git commit -m "docs: record breadcrumb and sticky action rollout"
```

## Self-Review

- Spec coverage: covered header-only breadcrumb placement, RFQ duplicate-breadcrumb removal, sticky save continuity, non-wrapping shared button behavior, shared primitive creation, RFQ Details adoption, testing, and documentation update.
- Placeholder scan: no `TODO`, `TBD`, or unlabeled “write tests” steps remain.
- Type consistency: the plan consistently uses `buildHeaderBreadcrumbs`, `HeaderBreadcrumbItem`, and `StickyPageActions` across tasks.

