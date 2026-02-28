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
  LogOut,
  LogIn,
} from "lucide-react";
import { useState } from "react";
import { useAuth } from "@/lib/auth";
import { LoginModal } from "./LoginModal";

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
  const { auth, logout } = useAuth();
  const [collapsed, setCollapsed] = useState(false);
  const [favoritesExpanded, setFavoritesExpanded] = useState(true);
  const [isLoginModalOpen, setIsLoginModalOpen] = useState(false);

  const isActive = (href: string) => {
    if (href === "/") return pathname === "/";
    return pathname.startsWith(href);
  };

  const userInitial = auth?.email?.[0]?.toUpperCase() || "A";

  return (
    <>
      <aside
        className={`flex flex-col bg-[var(--sidebar-bg)] text-white transition-all duration-200 ${
          collapsed ? "w-[72px]" : "w-64"
        }`}
      >
        {/* Logo & title */}
        <div className="flex h-16 items-center gap-3 border-b border-white/10 px-4">
          <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[var(--accent)]/90 text-white">
            <span className="text-lg font-bold">{userInitial}</span>
          </div>
          {!collapsed && (
            <div className="overflow-hidden">
              <h1 className="truncate font-semibold tracking-tight">
                {auth ? auth.tenantId : "Atomy"}
              </h1>
              <p className="truncate text-xs text-white/60">
                {auth ? auth.email : "Nexus ERP"}
              </p>
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
          <div className="mt-6 flex-1 overflow-y-auto px-3 pb-6">
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
          </div>
        )}

        {/* Bottom section */}
        <div className="mt-auto border-t border-white/10 p-3">
          {!collapsed && (
            <div className="mb-3 rounded-lg bg-[var(--accent)]/10 p-3">
              <p className="text-xs font-medium text-white">
                {auth ? "Identity Active" : "Nexus API"}
              </p>
              <p className="mt-0.5 text-[10px] text-white/70 leading-tight">
                {auth 
                  ? "Logged in via IdentityOperations" 
                  : "Sign in to access secure features"}
              </p>
              {!auth && (
                <button
                  onClick={() => setIsLoginModalOpen(true)}
                  className="mt-2 inline-flex items-center gap-1 rounded bg-[var(--accent)] px-2.5 py-1 text-xs font-semibold text-white hover:bg-[var(--accent)]/80 transition-colors"
                >
                  Sign in
                </button>
              )}
            </div>
          )}
          
          <div className="flex items-center gap-2">
            {auth ? (
              <button
                onClick={() => logout()}
                className={`flex items-center justify-center rounded-lg p-2.5 text-white/70 transition-colors hover:bg-red-500/20 hover:text-red-400 ${
                  collapsed ? "w-full" : "w-full justify-start gap-3"
                }`}
                title="Sign out"
              >
                <LogOut className="h-5 w-5 shrink-0" />
                {!collapsed && <span className="text-sm font-medium">Sign out</span>}
              </button>
            ) : (
              <button
                onClick={() => setIsLoginModalOpen(true)}
                className={`flex items-center justify-center rounded-lg p-2.5 text-white/70 transition-colors hover:bg-white/10 hover:text-white ${
                  collapsed ? "w-full" : "w-full justify-start gap-3"
                }`}
                title="Sign in"
              >
                <LogIn className="h-5 w-5 shrink-0" />
                {!collapsed && <span className="text-sm font-medium">Sign in</span>}
              </button>
            )}
          </div>
        </div>
      </aside>

      <LoginModal 
        isOpen={isLoginModalOpen} 
        onClose={() => setIsLoginModalOpen(false)} 
      />
    </>
  );
}
