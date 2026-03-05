"use client";

import { Bell, Search } from "lucide-react";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { type QuickAction } from "@/types/navigation";

interface AppHeaderProps {
  readonly title: string;
  readonly subtitle?: string;
  readonly quickActions: readonly QuickAction[];
}

export function AppHeader({ title, subtitle, quickActions }: AppHeaderProps): JSX.Element {
  return (
    <header className="border-b border-slate-200 bg-white px-6 py-4">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-xl font-semibold text-slate-900">{title}</h1>
          {subtitle ? <p className="mt-1 text-sm text-slate-600">{subtitle}</p> : null}
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <label className="relative block w-64 max-w-full">
            <Search className="pointer-events-none absolute left-2 top-2.5 h-4 w-4 text-slate-400" />
            <Input className="pl-8" placeholder="Search RFQ, run, vendor..." />
          </label>
          <button className="inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-300 text-slate-600 hover:bg-slate-100">
            <Bell className="h-4 w-4" />
          </button>
          {quickActions.map((action) => (
            <Button key={action.label} onClick={action.onClick} variant="secondary">
              {action.label}
            </Button>
          ))}
        </div>
      </div>
    </header>
  );
}
