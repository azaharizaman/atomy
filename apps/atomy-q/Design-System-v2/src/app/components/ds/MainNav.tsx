import React from 'react';
import { ChevronRight } from 'lucide-react';
import { CountBadge } from './Badge';
import { NavItem, SubNavItem } from './Sidebar';

export interface MainNavChildItem {
  id: string;
  label: string;
  badge?: number | string;
}

export interface MainNavItem {
  id: string;
  label: string;
  icon?: React.ReactNode;
  badge?: number | string;
  children?: MainNavChildItem[];
}

interface MainNavProps {
  items: MainNavItem[];
  activeItem?: string;
  onNavigate?: (id: string) => void;
  collapsed?: boolean;
  className?: string;
}

function findParentGroup(items: MainNavItem[], id?: string): string | null {
  if (!id) return null;
  for (const item of items) {
    if (!item.children?.length) continue;
    if (item.children.some(child => child.id === id)) return item.id;
  }
  return null;
}

export function MainNav({
  items,
  activeItem,
  onNavigate,
  collapsed = false,
  className = '',
}: MainNavProps) {
  const [openGroupId, setOpenGroupId] = React.useState<string | null>(() => findParentGroup(items, activeItem));

  React.useEffect(() => {
    const parent = findParentGroup(items, activeItem);
    if (parent) setOpenGroupId(parent);
  }, [items, activeItem]);

  function handleGroupToggle(groupId: string) {
    setOpenGroupId(prev => (prev === groupId ? null : groupId));
  }

  return (
    <div className={['flex flex-col gap-0.5', className].join(' ')}>
      {items.map(item => {
        const isDirectActive = activeItem === item.id;
        const hasChildren = (item.children?.length ?? 0) > 0;
        const hasActiveChild = item.children?.some(child => child.id === activeItem) ?? false;
        const isGroupOpen = openGroupId === item.id;
        const isGroupActive = hasActiveChild || isGroupOpen;

        if (!hasChildren) {
          return (
            <NavItem
              key={item.id}
              label={item.label}
              icon={item.icon}
              badge={item.badge}
              active={isDirectActive}
              collapsed={collapsed}
              onClick={() => onNavigate?.(item.id)}
            />
          );
        }

        if (collapsed) {
          return (
            <NavItem
              key={item.id}
              label={item.label}
              icon={item.icon}
              badge={item.badge}
              active={isGroupActive}
              collapsed
              onClick={() => onNavigate?.(item.id)}
            />
          );
        }

        return (
          <div key={item.id}>
            <button
              onClick={() => handleGroupToggle(item.id)}
              className={[
                'flex items-center gap-2.5 px-2.5 py-1.5 rounded-md w-full text-left transition-colors duration-100',
                isGroupActive ? 'text-slate-900 font-medium' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
              ].join(' ')}
            >
              {item.icon && (
                <span className={['shrink-0', isGroupActive ? 'text-indigo-600' : 'text-slate-400'].join(' ')}>
                  {item.icon}
                </span>
              )}
              <span className="flex-1 text-sm truncate">{item.label}</span>
              {item.badge !== undefined && <CountBadge count={item.badge} variant={isGroupActive ? 'indigo' : 'default'} />}
              <ChevronRight
                size={13}
                className={['text-slate-400 transition-transform duration-200 shrink-0', isGroupOpen ? 'rotate-90' : ''].join(' ')}
              />
            </button>

            {isGroupOpen && (
              <div className="ml-4 pl-2 border-l border-slate-200 mt-0.5 mb-1 flex flex-col gap-0.5">
                {item.children!.map(child => (
                  <SubNavItem
                    key={child.id}
                    label={child.label}
                    badge={child.badge}
                    active={activeItem === child.id}
                    onClick={() => onNavigate?.(child.id)}
                  />
                ))}
              </div>
            )}
          </div>
        );
      })}
    </div>
  );
}
