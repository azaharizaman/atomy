import React from 'react';
import { CountBadge } from './Badge';

// ─── Tab Item Type ─────────────────────────────────────────────────────────────

export interface TabItem {
  id: string;
  label: string;
  count?: number;
  icon?: React.ReactNode;
  disabled?: boolean;
}

// ─── Primary Tabs (underline, large — section-level navigation) ───────────────

interface PrimaryTabsProps {
  tabs: TabItem[];
  activeTab: string;
  onChange: (id: string) => void;
  className?: string;
}

export function PrimaryTabs({ tabs, activeTab, onChange, className = '' }: PrimaryTabsProps) {
  return (
    <div className={['flex border-b border-slate-200', className].join(' ')}>
      {tabs.map(tab => {
        const isActive = tab.id === activeTab;
        return (
          <button
            key={tab.id}
            disabled={tab.disabled}
            onClick={() => !tab.disabled && onChange(tab.id)}
            className={[
              'relative inline-flex items-center gap-1.5 px-3 py-2.5 text-sm font-medium transition-colors duration-150',
              'focus:outline-none',
              'border-b-2 -mb-px',
              isActive
                ? 'text-indigo-600 border-indigo-600'
                : 'text-slate-500 border-transparent hover:text-slate-700 hover:border-slate-300',
              tab.disabled ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer',
            ].join(' ')}
          >
            {tab.icon && <span className="shrink-0">{tab.icon}</span>}
            {tab.label}
            {tab.count !== undefined && (
              <CountBadge count={tab.count} variant={isActive ? 'indigo' : 'default'} />
            )}
          </button>
        );
      })}
    </div>
  );
}

// ─── Secondary Tabs (pill/chip style — sub-record navigation) ─────────────────

interface SecondaryTabsProps {
  tabs: TabItem[];
  activeTab: string;
  onChange: (id: string) => void;
  className?: string;
}

export function SecondaryTabs({ tabs, activeTab, onChange, className = '' }: SecondaryTabsProps) {
  return (
    <div className={['flex items-center gap-1 flex-wrap', className].join(' ')}>
      {tabs.map(tab => {
        const isActive = tab.id === activeTab;
        return (
          <button
            key={tab.id}
            disabled={tab.disabled}
            onClick={() => !tab.disabled && onChange(tab.id)}
            className={[
              'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium transition-colors duration-150 focus:outline-none',
              isActive
                ? 'bg-indigo-50 text-indigo-700 border border-indigo-200'
                : 'text-slate-500 hover:text-slate-700 hover:bg-slate-100',
              tab.disabled ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer',
            ].join(' ')}
          >
            {tab.icon && <span className="shrink-0">{tab.icon}</span>}
            {tab.label}
            {tab.count !== undefined && (
              <CountBadge count={tab.count} variant={isActive ? 'indigo' : 'default'} />
            )}
          </button>
        );
      })}
    </div>
  );
}

// ─── Vertical Tabs ────────────────────────────────────────────────────────────

interface VerticalTabsProps {
  tabs: TabItem[];
  activeTab: string;
  onChange: (id: string) => void;
  className?: string;
}

export function VerticalTabs({ tabs, activeTab, onChange, className = '' }: VerticalTabsProps) {
  return (
    <div className={['flex flex-col gap-0.5', className].join(' ')}>
      {tabs.map(tab => {
        const isActive = tab.id === activeTab;
        return (
          <button
            key={tab.id}
            disabled={tab.disabled}
            onClick={() => !tab.disabled && onChange(tab.id)}
            className={[
              'flex items-center justify-between gap-2 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-150 focus:outline-none text-left',
              isActive
                ? 'bg-indigo-50 text-indigo-700 border border-indigo-200'
                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-800',
              tab.disabled ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer',
            ].join(' ')}
          >
            <span className="flex items-center gap-2">
              {tab.icon && <span className="shrink-0">{tab.icon}</span>}
              {tab.label}
            </span>
            {tab.count !== undefined && (
              <CountBadge count={tab.count} variant={isActive ? 'indigo' : 'default'} />
            )}
          </button>
        );
      })}
    </div>
  );
}

// ─── Tab Panel ────────────────────────────────────────────────────────────────

interface TabPanelProps {
  id: string;
  activeTab: string;
  children: React.ReactNode;
}

export function TabPanel({ id, activeTab, children }: TabPanelProps) {
  if (id !== activeTab) return null;
  return <>{children}</>;
}
