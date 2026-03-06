"use client";

import Link from "next/link";

import { type NavSection } from "@/types/navigation";
import { cn } from "@/lib/cn";

interface AppSidebarProps {
  readonly sections: readonly NavSection[];
  readonly activeLabel?: string;
}

export function AppSidebar({ sections, activeLabel }: AppSidebarProps): JSX.Element {
  return (
    <aside className="hidden h-screen w-72 flex-col border-r border-slate-800 bg-slate-950 text-slate-200 lg:flex">
      <div className="border-b border-slate-800 px-4 py-4">
        <p className="text-xs uppercase tracking-wide text-slate-400">ERP Intelligence</p>
        <h1 className="mt-1 text-lg font-semibold text-white">Atomy-Q</h1>
      </div>
      <nav className="flex-1 space-y-6 overflow-auto px-3 py-4">
        {sections.map((section) => (
          <section key={section.title}>
            <h2 className="px-2 text-xs uppercase tracking-wide text-slate-500">{section.title}</h2>
            <ul className="mt-2 space-y-1">
              {section.items.map((item) => {
                const isActive = activeLabel === item.label;
                const Icon = item.icon;

                return (
                  <li key={item.label}>
                    <Link
                      className={cn(
                        "flex items-center gap-2 rounded-md px-2 py-2 text-sm transition-colors",
                        isActive ? "bg-slate-800 text-white" : "text-slate-300 hover:bg-slate-900 hover:text-white"
                      )}
                      href={item.href}
                    >
                      <Icon className="h-4 w-4" />
                      <span>{item.label}</span>
                    </Link>
                  </li>
                );
              })}
            </ul>
          </section>
        ))}
      </nav>
    </aside>
  );
}
