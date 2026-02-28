"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import {
  Home,
  FolderOpen,
  Bookmark,
  Users,
  ChevronLeft,
  ChevronRight,
  Plus,
  Folder,
  FileText,
} from "lucide-react";
import { useState } from "react";

const mainNavItems = [
  { href: "/", label: "Dashboard", icon: Home },
  { href: "/modules", label: "Modules", icon: FolderOpen },
  { href: "/users", label: "Users", icon: Users },
  { href: "/feature-flags", label: "Feature Flags", icon: Bookmark },
];

const recentsItems = [
  { href: "/modules", label: "Modules", icon: FolderOpen },
  { href: "/users", label: "Shared Users", icon: Users },
  { href: "/feature-flags", label: "Feature Flags", icon: Bookmark },
];

export function Sidebar() {
  const pathname = usePathname();
  const [collapsed, setCollapsed] = useState(false);
  const [favoritesExpanded, setFavoritesExpanded] = useState(true);
  const [projectsExpanded, setProjectsExpanded] = useState(true);

  const isActive = (href: string) => {
    if (href === "/") return pathname === "/";
    return pathname.startsWith(href);
  };

  return (
    <aside
      className={`flex flex-col bg-[var(--sidebar-bg)] text-white transition-all duration-200 ${
        collapsed ? "w-[72px]" : "w-64"
      }`}
    >
      {/* Logo & title */}
      <div className="flex h-16 items-center gap-3 border-b border-white/10 px-4">
        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[var(--accent)]/90 text-white">
          <span className="text-lg font-bold">A</span>
        </div>
        {!collapsed && (
          <div>
            <h1 className="font-semibold tracking-tight">Atomy</h1>
            <p className="text-xs text-white/60">Nexus ERP</p>
          </div>
        )}
      </div>

      {/* Collapse toggle */}
      <button
        type="button"
        onClick={() => setCollapsed(!collapsed)}
        className="mt-3 flex items-center justify-center gap-2 self-center rounded-lg px-2 py-1.5 text-white/70 transition-colors hover:bg-white/10 hover:text-white"
        aria-label={collapsed ? "Expand sidebar" : "Collapse sidebar"}
      >
        {collapsed ? (
          <ChevronRight className="h-4 w-4" />
        ) : (
          <>
            <ChevronLeft className="h-4 w-4" />
            <span className="text-xs">Collapse</span>
          </>
        )}
      </button>

      {/* Primary nav icons */}
      <nav className="mt-4 flex flex-col gap-0.5 px-3">
        {mainNavItems.map(({ href, label, icon: Icon }) => {
          const active = isActive(href);
          return (
            <Link
              key={href}
              href={href}
              title={label}
              className={`flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-colors ${
                active
                  ? "bg-[var(--sidebar-active)] text-white"
                  : "text-white/80 hover:bg-white/10 hover:text-white"
              }`}
            >
              <Icon className="h-5 w-5 shrink-0" />
              {!collapsed && <span>{label}</span>}
            </Link>
          );
        })}
      </nav>

      {/* Recents */}
      {!collapsed && (
        <div className="mt-6 flex-1 overflow-y-auto px-3">
          <p className="mb-2 px-3 text-xs font-medium uppercase tracking-wider text-white/50">
            Recents
          </p>
          <ul className="space-y-0.5">
            {recentsItems.map(({ href, label, icon: Icon }) => (
              <li key={href}>
                <Link
                  href={href}
                  className="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-white/80 transition-colors hover:bg-white/10 hover:text-white"
                >
                  <Icon className="h-4 w-4 shrink-0 text-white/60" />
                  <span>{label}</span>
                </Link>
              </li>
            ))}
          </ul>

          <p className="mb-2 mt-4 px-3 text-xs font-medium uppercase tracking-wider text-white/50">
            Favorites
          </p>
          <button
            type="button"
            onClick={() => setFavoritesExpanded(!favoritesExpanded)}
            className="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-white/80 hover:bg-white/10"
          >
            <span className="flex-1 text-left">Favorites</span>
            <ChevronRight
              className={`h-4 w-4 transition-transform ${favoritesExpanded ? "rotate-90" : ""}`}
            />
          </button>
          {favoritesExpanded && (
            <ul className="mt-0.5 space-y-0.5">
              <li>
                <Link
                  href="/modules"
                  className="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-white/80 hover:bg-white/10"
                >
                  <Folder className="h-4 w-4 shrink-0 text-white/60" />
                  <span>Modules</span>
                </Link>
              </li>
              <li>
                <Link
                  href="/users"
                  className="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-white/80 hover:bg-white/10"
                >
                  <Users className="h-4 w-4 shrink-0 text-white/60" />
                  <span>Users</span>
                </Link>
              </li>
            </ul>
          )}

          <p className="mb-2 mt-4 px-3 text-xs font-medium uppercase tracking-wider text-white/50">
            Projects
          </p>
          <button
            type="button"
            onClick={() => setProjectsExpanded(!projectsExpanded)}
            className="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-white/80 hover:bg-white/10"
          >
            <span className="flex-1 text-left">Nexus</span>
            <Plus className="h-4 w-4 shrink-0" />
            <ChevronRight
              className={`h-4 w-4 transition-transform ${projectsExpanded ? "rotate-90" : ""}`}
            />
          </button>
          {projectsExpanded && (
            <ul className="mt-0.5 space-y-0.5">
              <li>
                <Link
                  href="/"
                  className={`flex items-center gap-3 rounded-lg px-3 py-2 text-sm ${
                    pathname === "/" ? "bg-white/10 text-white" : "text-white/80 hover:bg-white/10"
                  }`}
                >
                  <div className="h-2 w-2 shrink-0 rounded-full bg-[var(--accent)]" />
                  <span>Dashboard</span>
                </Link>
              </li>
              <li>
                <Link
                  href="/modules"
                  className={`flex items-center gap-3 rounded-lg px-3 py-2 text-sm ${
                    pathname.startsWith("/modules")
                      ? "bg-white/10 text-white"
                      : "text-white/80 hover:bg-white/10"
                  }`}
                >
                  <FileText className="h-4 w-4 shrink-0 text-white/60" />
                  <span>Modules</span>
                </Link>
              </li>
            </ul>
          )}
        </div>
      )}

      {/* Bottom section */}
      <div className="border-t border-white/10 p-3">
        {!collapsed && (
          <div className="mb-3 rounded-lg bg-[var(--accent)]/20 p-3">
            <p className="text-xs font-medium text-white">Nexus API</p>
            <p className="mt-0.5 text-[10px] text-white/70">
              Connect to the API for full integration
            </p>
            <Link
              href={process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api/docs"}
              target="_blank"
              rel="noopener noreferrer"
              className="mt-2 inline-flex items-center gap-1 rounded bg-[var(--accent)] px-2 py-1 text-xs font-medium text-white hover:bg-[var(--accent-muted)]"
            >
              Open docs
              <span className="inline-block">↗</span>
            </Link>
          </div>
        )}
        <div className="flex items-center gap-3">
          <button
            type="button"
            className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[var(--accent)] text-white transition-colors hover:bg-[var(--accent-muted)]"
            title="New content"
          >
            <Plus className="h-5 w-5" />
          </button>
          <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/20 text-sm font-medium">
            AC
          </div>
        </div>
      </div>
    </aside>
  );
}
