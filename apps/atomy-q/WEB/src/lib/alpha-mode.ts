export const ALPHA_DEFERRED_COPY = 'This feature will be available in future releases';

const ALPHA_TOP_LEVEL_NAV_IDS = new Set(['dashboard', 'requisition']);
const ALPHA_RFQ_SECTION_IDS = new Set([
  'overview',
  'details',
  'line-items',
  'vendors',
  'award',
  'quote-intake',
  'comparison-runs',
  'approvals',
  'decision-trail',
]);

const DEFERRED_PATH_PATTERN = /^\/rfqs\/[^/]+\/(negotiations|documents|risk)(?:\/.*)?$/;

export function isAlphaMode(): boolean {
  return process.env.NEXT_PUBLIC_ALPHA_MODE === 'true';
}

export function isTopLevelNavVisibleInAlpha(navId: string): boolean {
  return ALPHA_TOP_LEVEL_NAV_IDS.has(navId);
}

export function isRfqSectionVisibleInAlpha(sectionId: string): boolean {
  return ALPHA_RFQ_SECTION_IDS.has(sectionId);
}

export function isDeferredAlphaPath(pathname: string): boolean {
  return (
    pathname === '/documents' ||
    pathname === '/reporting' ||
    pathname === '/settings' ||
    pathname.startsWith('/settings/') ||
    DEFERRED_PATH_PATTERN.test(pathname)
  );
}
