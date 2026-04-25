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
