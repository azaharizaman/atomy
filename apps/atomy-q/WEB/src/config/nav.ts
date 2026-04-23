import { isAlphaMode, isTopLevelNavVisibleInAlpha } from '@/lib/alpha-mode';

/**
 * Central navigation config — single source of truth for main app nav.
 * Consume from dashboard layout, workspace rail, and (optionally) settings page / breadcrumbs.
 * See docs/NAV_STRUCTURE_ANALYSIS.md.
 */

export interface NavItemConfig {
  id: string;
  label: string;
  href: string;
  /** For breadcrumb/label lookup. Omit for groups that are not a route. */
  path?: string;
}

export interface NavGroupConfig {
  id: string;
  label: string;
  /** First child href used for "active" when pathname starts with it. */
  pathPrefix: string;
  children: NavItemConfig[];
}

export interface RequisitionNavItemConfig extends NavItemConfig {
  countKey: 'draft' | 'pending' | 'active' | 'awarded' | 'closed' | 'archived';
}

/** Top-level nav items shared by the dashboard shell and alpha policy tests. */
export const MAIN_NAV_ITEMS: NavItemConfig[] = [
  { id: 'dashboard', label: 'Dashboard', href: '/', path: '/' },
  { id: 'projects', label: 'Projects', href: '/projects', path: '/projects' },
  { id: 'requisition', label: 'Requisitions', href: '/rfqs', path: '/rfqs' },
  { id: 'tasks', label: 'Task', href: '/tasks', path: '/tasks' },
  { id: 'approvals', label: 'Approvals', href: '/approvals', path: '/approvals' },
  { id: 'vendors', label: 'Vendors', href: '/vendors', path: '/vendors' },
  { id: 'documents', label: 'Documents', href: '/documents', path: '/documents' },
  { id: 'reporting', label: 'Reports', href: '/reporting', path: '/reporting' },
  { id: 'settings', label: 'Settings', href: '/settings', path: '/settings' },
];

export const UNGROUPED_LEADING_MAIN_NAV_IDS = ['dashboard', 'projects', 'requisition'] as const;
export const INBOX_MAIN_NAV_IDS = ['tasks', 'approvals'] as const;
export const RECORDS_MAIN_NAV_IDS = ['vendors', 'documents', 'reporting'] as const;
export const UNGROUPED_TRAILING_MAIN_NAV_IDS = ['settings'] as const;

export const REQUISITION_NAV_ITEMS: RequisitionNavItemConfig[] = [
  { id: 'draft', label: 'Draft', href: `/rfqs?status=draft`, path: '/rfqs', countKey: 'draft' },
  { id: 'pending', label: 'Pending Approval', href: `/rfqs?status=pending`, path: '/rfqs', countKey: 'pending' },
  { id: 'active', label: 'Active (Live RFQ)', href: '/rfqs', path: '/rfqs', countKey: 'active' },
  { id: 'awarded', label: 'Awarded', href: `/rfqs?status=awarded`, path: '/rfqs', countKey: 'awarded' },
  { id: 'closed', label: 'Closed', href: `/rfqs?status=closed`, path: '/rfqs', countKey: 'closed' },
  { id: 'archived', label: 'Archived', href: `/rfqs?status=archived`, path: '/rfqs', countKey: 'archived' },
];

/** Settings group — same list used by sidebar and settings landing page. */
export const SETTINGS_NAV_ITEMS: NavItemConfig[] = [
  { id: 'users', label: 'Users & Roles', href: '/settings/users', path: '/settings/users' },
  { id: 'scoring-policies', label: 'Scoring Policies', href: '/settings/scoring-policies', path: '/settings/scoring-policies' },
  { id: 'templates', label: 'Templates', href: '/settings/templates', path: '/settings/templates' },
  { id: 'integrations', label: 'Integrations', href: '/settings/integrations', path: '/settings/integrations' },
  { id: 'feature-flags', label: 'Feature Flags', href: '/settings/feature-flags', path: '/settings/feature-flags' },
];

export const SETTINGS_NAV_GROUP: NavGroupConfig = {
  id: 'settings',
  label: 'Settings',
  pathPrefix: '/settings',
  children: SETTINGS_NAV_ITEMS,
};

export function getVisibleMainNavItems(): NavItemConfig[] {
  if (!isAlphaMode()) {
    return MAIN_NAV_ITEMS;
  }

  return MAIN_NAV_ITEMS.filter((item) => isTopLevelNavVisibleInAlpha(item.id));
}

export function isSettingsNavVisible(): boolean {
  return !isAlphaMode();
}

/** Path → label for breadcrumbs. Includes main items and settings children. */
const pathToLabel: Record<string, string> = {
  '/': 'Dashboard',
  '/projects': 'Projects',
  '/rfqs': 'Requisitions',
  '/tasks': 'Task',
  '/vendors': 'Vendors',
  '/documents': 'Documents',
  '/reporting': 'Reports',
  '/approvals': 'Approvals',
  '/settings': 'Settings',
  ...Object.fromEntries(SETTINGS_NAV_ITEMS.map((item) => [item.path ?? item.href, item.label])),
};

/**
 * Get a human-readable label for a pathname (for breadcrumbs).
 * Falls back to the last path segment or the pathname.
 */
export function getLabelForPath(pathname: string): string {
  if (pathToLabel[pathname]) return pathToLabel[pathname];
  // Match longest prefix (e.g. /settings/users → "Users & Roles")
  const sorted = Object.keys(pathToLabel).filter((p) => pathname.startsWith(p)).sort((a, b) => b.length - a.length);
  const prefix = sorted[0];
  if (prefix) {
    const rest = pathname.slice(prefix.length).replace(/^\//, '');
    if (!rest) return pathToLabel[prefix];
    // Optional: map RFQ section suffixes (e.g. overview, line-items) here
    const segment = rest.split('/')[0];
    return `${pathToLabel[prefix]} / ${segment}`;
  }
  const last = pathname.split('/').filter(Boolean).pop();
  return last ? last.replace(/-/g, ' ') : pathname;
}
