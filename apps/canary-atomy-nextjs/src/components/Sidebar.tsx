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
  Folder,
  LogOut,
  LogIn,
} from "lucide-react";
import { useState } from "react";
import { useAuth } from "@/lib/auth";
import { LoginModal } from "./LoginModal";
import { Button } from "@/components/ui/button";
import { Separator } from "@/components/ui/separator";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";

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
        className={`flex flex-col bg-[hsl(var(--sidebar-background))] text-[hsl(var(--sidebar-foreground))] transition-all duration-200 border-r border-[hsl(var(--sidebar-border))] ${
          collapsed ? "w-[72px]" : "w-64"
        }`}
      >
        {/* Logo & title */}
        <div className="flex h-16 items-center gap-3 border-b border-[hsl(var(--sidebar-border))] px-4">
          <Avatar className="h-10 w-10 shrink-0 border border-[hsl(var(--sidebar-primary))]">
            <AvatarFallback className="bg-[hsl(var(--sidebar-primary))] text-[hsl(var(--sidebar-primary-foreground))] text-lg font-bold">
              {userInitial}
            </AvatarFallback>
          </Avatar>
          {!collapsed && (
            <div className="overflow-hidden">
              <h1 className="truncate font-semibold tracking-tight">
                {auth ? auth.tenantId : "Atomy"}
              </h1>
              <p className="truncate text-xs text-[hsl(var(--sidebar-foreground))]/60">
                {auth ? auth.email : "Nexus ERP"}
              </p>
            </div>
          )}
        </div>

        {/* Collapse toggle */}
        <Button
          variant="ghost"
          size="sm"
          onClick={() => setCollapsed(!collapsed)}
          className="mt-3 mx-2 text-[hsl(var(--sidebar-foreground))]/70 hover:bg-[hsl(var(--sidebar-accent))] hover:text-[hsl(var(--sidebar-accent-foreground))]"
        >
          {collapsed ? (
            <ChevronRight className="h-4 w-4" />
          ) : (
            <>
              <ChevronLeft className="h-4 w-4 mr-2" />
              <span className="text-xs">Collapse</span>
            </>
          )}
        </Button>

        {/* Primary nav icons */}
        <nav className="mt-4 flex flex-col gap-1 px-3">
          {mainNavItems.map(({ href, label, icon: Icon }) => {
            const active = isActive(href);
            return (
              <Link
                key={href}
                href={href}
                title={label}
                className={`flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition-colors ${
                  active
                    ? "bg-[hsl(var(--sidebar-accent))] text-[hsl(var(--sidebar-accent-foreground))]"
                    : "text-[hsl(var(--sidebar-foreground))]/80 hover:bg-[hsl(var(--sidebar-accent))]/50 hover:text-[hsl(var(--sidebar-accent-foreground))]"
                }`}
              >
                <Icon className={`h-5 w-5 shrink-0 ${active ? "text-[hsl(var(--sidebar-primary))]" : ""}`} />
                {!collapsed && <span>{label}</span>}
              </Link>
            );
          })}
        </nav>

        {/* Recents */}
        {!collapsed && (
          <div className="mt-6 flex-1 overflow-y-auto px-3 pb-6">
            <p className="mb-2 px-3 text-xs font-medium uppercase tracking-wider text-[hsl(var(--sidebar-foreground))]/40">
              Recents
            </p>
            <ul className="space-y-1">
              {recentsItems.map(({ href, label, icon: Icon }) => (
                <li key={href}>
                  <Link
                    href={href}
                    className="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-[hsl(var(--sidebar-foreground))]/70 transition-colors hover:bg-[hsl(var(--sidebar-accent))]/30 hover:text-[hsl(var(--sidebar-accent-foreground))]"
                  >
                    <Icon className="h-4 w-4 shrink-0 opacity-60" />
                    <span>{label}</span>
                  </Link>
                </li>
              ))}
            </ul>

            <Separator className="my-4 bg-[hsl(var(--sidebar-border))]" />

            <p className="mb-2 px-3 text-xs font-medium uppercase tracking-wider text-[hsl(var(--sidebar-foreground))]/40">
              Favorites
            </p>
            <Button
              variant="ghost"
              size="sm"
              onClick={() => setFavoritesExpanded(!favoritesExpanded)}
              className="w-full justify-start gap-2 px-3 text-[hsl(var(--sidebar-foreground))]/70 hover:bg-[hsl(var(--sidebar-accent))]/30"
            >
              <span className="flex-1 text-left">Collections</span>
              <ChevronRight
                className={`h-4 w-4 transition-transform ${favoritesExpanded ? "rotate-90" : ""}`}
              />
            </Button>
            {favoritesExpanded && (
              <ul className="mt-1 space-y-1">
                <li>
                  <Link
                    href="/modules"
                    className="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-[hsl(var(--sidebar-foreground))]/70 hover:bg-[hsl(var(--sidebar-accent))]/30"
                  >
                    <Folder className="h-4 w-4 shrink-0 opacity-60" />
                    <span>Modules</span>
                  </Link>
                </li>
                <li>
                  <Link
                    href="/users"
                    className="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-[hsl(var(--sidebar-foreground))]/70 hover:bg-[hsl(var(--sidebar-accent))]/30"
                  >
                    <Users className="h-4 w-4 shrink-0 opacity-60" />
                    <span>Users</span>
                  </Link>
                </li>
              </ul>
            )}
          </div>
        )}

        {/* Bottom section */}
        <div className="mt-auto border-t border-[hsl(var(--sidebar-border))] p-3">
          {!collapsed && (
            <div className="mb-3 rounded-lg bg-[hsl(var(--sidebar-primary))]/10 p-3">
              <p className="text-xs font-medium text-[hsl(var(--sidebar-foreground))]">
                {auth ? "Identity Active" : "Nexus API"}
              </p>
              <p className="mt-1 text-[10px] text-[hsl(var(--sidebar-foreground))]/60 leading-tight">
                {auth 
                  ? "Logged in via IdentityOperations" 
                  : "Sign in to access secure features"}
              </p>
              {!auth && (
                <Button
                  size="sm"
                  variant="default"
                  onClick={() => setIsLoginModalOpen(true)}
                  className="mt-2 h-7 px-3 text-[10px] bg-[hsl(var(--sidebar-primary))] hover:bg-[hsl(var(--sidebar-primary))]/90"
                >
                  Sign in
                </Button>
              )}
            </div>
          )}
          
          <div className="flex flex-col gap-1">
            {auth ? (
              <Button
                variant="ghost"
                onClick={() => logout()}
                className={`justify-start gap-3 text-[hsl(var(--sidebar-foreground))]/70 hover:bg-destructive/10 hover:text-destructive ${
                  collapsed ? "px-0 justify-center" : ""
                }`}
                title="Sign out"
              >
                <LogOut className="h-5 w-5 shrink-0" />
                {!collapsed && <span className="text-sm font-medium">Sign out</span>}
              </Button>
            ) : (
              <Button
                variant="ghost"
                onClick={() => setIsLoginModalOpen(true)}
                className={`justify-start gap-3 text-[hsl(var(--sidebar-foreground))]/70 hover:bg-[hsl(var(--sidebar-accent))]/50 hover:text-[hsl(var(--sidebar-accent-foreground))] ${
                  collapsed ? "px-0 justify-center" : ""
                }`}
                title="Sign in"
              >
                <LogIn className="h-5 w-5 shrink-0" />
                {!collapsed && <span className="text-sm font-medium">Sign in</span>}
              </Button>
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

