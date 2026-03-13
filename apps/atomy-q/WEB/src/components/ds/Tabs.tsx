'use client';

import React from 'react';
import { CountBadge } from './Badge';

export interface TabItem {
  id: string;
  label: string;
  count?: number;
  icon?: React.ReactNode;
  disabled?: boolean;
}

interface SecondaryTabsProps {
  tabs: TabItem[];
  activeTab: string;
  onChange: (id: string) => void;
  className?: string;
}

/** Pill/chip style tabs — per Screen-Blueprint SecondaryTabs */
export function SecondaryTabs({ tabs, activeTab, onChange, className = '' }: SecondaryTabsProps) {
  return (
    <div className={['flex items-center gap-1 flex-wrap', className].join(' ')}>
      {tabs.map((tab) => {
        const isActive = tab.id === activeTab;
        return (
          <button
            key={tab.id}
            type="button"
            disabled={tab.disabled}
            onClick={() => !tab.disabled && onChange(tab.id)}
            className={[
              'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium transition-colors duration-150 focus:outline-none',
              isActive ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-100',
              tab.disabled ? 'opacity-40 cursor-not-allowed' : 'cursor-pointer',
            ].join(' ')}
          >
            {tab.icon != null && <span className="shrink-0">{tab.icon}</span>}
            {tab.label}
            {tab.count != null && (
              <CountBadge count={tab.count} variant={isActive ? 'indigo' : 'default'} />
            )}
          </button>
        );
      })}
    </div>
  );
}
