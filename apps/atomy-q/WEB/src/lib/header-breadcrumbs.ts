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

function isRfqWorkspacePath(pathname: string): boolean {
  if (!pathname.startsWith('/rfqs/') || pathname === '/rfqs' || pathname === '/rfqs/') {
    return false;
  }

  const segments = pathname.split('/').filter(Boolean);
  return segments.length >= 2 && segments[0] === 'rfqs' && segments[1] !== 'new';
}

export function buildHeaderBreadcrumbs({
  pathname,
  rfqTitle,
}: BuildHeaderBreadcrumbsInput): HeaderBreadcrumbItem[] {
  if (!isRfqWorkspacePath(pathname)) {
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
