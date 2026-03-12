import React from 'react';
import { Bell, Search } from 'lucide-react';

export function Header() {
  return (
    <header className="h-14 border-b border-slate-200 bg-white flex items-center justify-between px-4 sticky top-0 z-10">
      <div className="flex items-center gap-4">
        {/* Breadcrumbs Placeholder */}
        <span className="text-sm text-slate-500 font-medium">Atomy-Q / Dashboard</span>
      </div>

      <div className="flex items-center gap-4">
        <div className="relative">
          <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400" size={16} aria-hidden="true" />
          <input
            type="text"
            aria-label="Search"
            placeholder="Search..."
            className="h-9 w-64 pl-9 pr-4 rounded-md border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
          />
        </div>

        <button className="relative text-slate-500 hover:text-slate-700" aria-label="Notifications">
          <Bell size={20} />
          <span className="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full border border-white" />
        </button>

        <div className="w-8 h-8 rounded-full bg-slate-200" />
      </div>
    </header>
  );
}
