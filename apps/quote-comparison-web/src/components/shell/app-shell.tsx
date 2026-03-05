import { type ReactNode } from "react";

import { type NavSection, type QuickAction } from "@/types/navigation";

import { AppFooter } from "./app-footer";
import { AppHeader } from "./app-header";
import { AppSidebar } from "./app-sidebar";

interface AppShellProps {
  readonly title: string;
  readonly subtitle?: string;
  readonly children: ReactNode;
  readonly sections: readonly NavSection[];
  readonly quickActions: readonly QuickAction[];
  readonly activeSidebarLabel?: string;
}

export function AppShell({ title, subtitle, children, sections, quickActions, activeSidebarLabel }: AppShellProps): JSX.Element {
  return (
    <div className="min-h-screen bg-slate-100 text-slate-900 lg:flex">
      <AppSidebar activeLabel={activeSidebarLabel} sections={sections} />
      <div className="flex min-h-screen min-w-0 flex-1 flex-col">
        <AppHeader quickActions={quickActions} subtitle={subtitle} title={title} />
        <main className="flex-1 px-6 py-6">{children}</main>
        <AppFooter />
      </div>
    </div>
  );
}
