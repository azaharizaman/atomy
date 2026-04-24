'use client';

import React from 'react';
import {
  BarChart2,
  FileText,
  FolderArchive,
  FolderKanban,
  LayoutPanelTop,
  ListTodo,
  Settings,
  ShieldCheck,
  Users,
} from 'lucide-react';

import {
  INBOX_MAIN_NAV_IDS,
  type NavItemConfig,
  RECORDS_MAIN_NAV_IDS,
  REQUISITION_NAV_ITEMS,
  UNGROUPED_LEADING_MAIN_NAV_IDS,
  UNGROUPED_TRAILING_MAIN_NAV_IDS,
  getVisibleMainNavItems,
} from '@/config/nav';
import type { TenantFeatureFlags } from '@/hooks/use-feature-flags';
import type { RfqNavCounts } from '@/hooks/use-rfq-counts';
import { RFQ_STATUSES } from '@/hooks/use-rfqs';
import { NavGroup, NavItem, NavLabel, SubNavItem } from '@/components/layout/sidebar';

type MainSidebarNavProps = {
  pathname: string;
  currentStatus?: string | null;
  collapsed?: boolean;
  alphaMode: boolean;
  featureFlags?: TenantFeatureFlags;
  featureFlagsLoading?: boolean;
  rfqCounts?: RfqNavCounts;
};

function NavFeatureSlotSkeleton({ collapsed = false }: { collapsed?: boolean }) {
  return (
    <div
      className={collapsed ? 'mx-auto h-9 w-9 rounded-md bg-slate-100 animate-pulse' : 'h-9 mx-0.5 rounded-md bg-slate-100 animate-pulse'}
      aria-hidden
    />
  );
}

function iconForNavItem(id: string) {
  switch (id) {
    case 'dashboard':
      return <LayoutPanelTop size={18} />;
    case 'projects':
      return <FolderKanban size={18} />;
    case 'requisition':
      return <FileText size={18} />;
    case 'tasks':
      return <ListTodo size={18} />;
    case 'approvals':
      return <ShieldCheck size={18} />;
    case 'vendors':
      return <Users size={18} />;
    case 'documents':
      return <FolderArchive size={18} />;
    case 'reporting':
      return <BarChart2 size={18} />;
    case 'settings':
      return <Settings size={18} />;
    default:
      return null;
  }
}

export function MainSidebarNav({
  pathname,
  currentStatus = null,
  collapsed = false,
  alphaMode,
  featureFlags,
  featureFlagsLoading = false,
  rfqCounts,
}: MainSidebarNavProps) {
  const visibleItems = React.useMemo(
    () => getVisibleMainNavItems(alphaMode, featureFlagsLoading, featureFlags),
    [alphaMode, featureFlagsLoading, featureFlags],
  );
  const visibleItemById = React.useMemo(
    () => new Map(visibleItems.map((item) => [item.id, item])),
    [visibleItems],
  );

  function getItem(id: string): NavItemConfig | undefined {
    return visibleItemById.get(id);
  }

  function isItemEnabled(id: string): boolean {
    const item = getItem(id);
    if (!item) {
      return false;
    }
    if (id === 'projects') {
      return !alphaMode && featureFlagsLoading !== true && featureFlags?.projects === true;
    }
    if (id === 'tasks') {
      return !alphaMode && featureFlagsLoading !== true && featureFlags?.tasks === true;
    }
    return true;
  }

  function renderNavItem(id: string) {
    const item = getItem(id);

    if (!item) {
      return null;
    }

    if ((id === 'projects' || id === 'tasks') && !alphaMode && featureFlagsLoading) {
      return <NavFeatureSlotSkeleton key={`${id}-skeleton`} collapsed={collapsed} />;
    }

    if (!isItemEnabled(id)) {
      return null;
    }

    if (id === 'requisition') {
      return (
        <NavGroup
          key={item.id}
          label={item.label}
          icon={iconForNavItem(item.id)}
          active={pathname.startsWith(item.href)}
          defaultOpen={pathname.startsWith(item.href)}
          collapsed={collapsed}
        >
          {REQUISITION_NAV_ITEMS.map((child) => {
            const isActiveChild =
              pathname === '/rfqs' &&
              ((child.id === 'active' && (!currentStatus || currentStatus === RFQ_STATUSES.ACTIVE)) ||
                (child.id !== 'active' && currentStatus === child.id));
            const count = rfqCounts?.[child.countKey];

            return (
              <SubNavItem
                key={child.id}
                label={child.label}
                active={isActiveChild}
                href={child.href}
                badge={count !== undefined && count > 0 ? count : undefined}
              />
            );
          })}
        </NavGroup>
      );
    }

    return (
      <NavItem
        key={item.id}
        label={item.label}
        icon={iconForNavItem(item.id)}
        active={pathname === item.href || pathname.startsWith(`${item.href}/`)}
        href={item.href}
        collapsed={collapsed}
      />
    );
  }

  const inboxVisible = INBOX_MAIN_NAV_IDS.some((id) => {
    if (id === 'tasks' && !alphaMode && featureFlagsLoading) {
      return true;
    }

    return isItemEnabled(id);
  });
  const recordsVisible = RECORDS_MAIN_NAV_IDS.some((id) => isItemEnabled(id));

  return (
    <>
      {UNGROUPED_LEADING_MAIN_NAV_IDS.map((id) => renderNavItem(id))}

      {inboxVisible ? <NavLabel label="Inbox" collapsed={collapsed} /> : null}
      {INBOX_MAIN_NAV_IDS.map((id) => renderNavItem(id))}

      {recordsVisible ? <NavLabel label="Records" collapsed={collapsed} /> : null}
      {RECORDS_MAIN_NAV_IDS.map((id) => renderNavItem(id))}

      {UNGROUPED_TRAILING_MAIN_NAV_IDS.map((id) => renderNavItem(id))}
    </>
  );
}
