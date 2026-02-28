"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { Star, Upload, Plus, Grid3X3, List, Search } from "lucide-react";
import { useState } from "react";

interface Tab {
  id: string;
  label: string;
  href?: string;
}

interface ContentHeaderProps {
  title: string;
  description?: string;
  tabs?: Tab[];
  activeTab?: string;
  onTabChange?: (id: string) => void;
  viewMode?: "grid" | "list";
  onViewModeChange?: (mode: "grid" | "list") => void;
  itemCount?: number;
  showViewToggle?: boolean;
}

export function ContentHeader({
  title,
  description,
  tabs,
  activeTab,
  onTabChange,
  viewMode = "grid",
  onViewModeChange,
  itemCount,
  showViewToggle = true,
}: ContentHeaderProps) {
  const pathname = usePathname();
  const [isStarred, setIsStarred] = useState(false);

  return (
    <div className="space-y-4">
      {/* Top row: title, avatars, actions */}
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div className="flex items-center gap-4">
          <h1 className="text-2xl font-bold tracking-tight text-[var(--foreground)]">{title}</h1>
          <div className="flex items-center gap-2">
            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-[var(--accent)] text-xs font-medium text-white">
              AC
            </div>
            <div className="flex -space-x-2">
              {[1, 2, 3].map((i) => (
                <div
                  key={i}
                  className="flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-[var(--surface)] text-xs text-[var(--text-muted)]"
                >
                  ?
                </div>
              ))}
            </div>
            <button
              type="button"
              className="flex h-8 w-8 items-center justify-center rounded-full border border-[var(--border)] text-[var(--text-muted)] transition-colors hover:bg-[var(--surface)] hover:text-[var(--foreground)]"
              title="Add collaborator"
            >
              <Plus className="h-4 w-4" />
            </button>
            <button
              type="button"
              onClick={() => setIsStarred(!isStarred)}
              className={`flex h-8 w-8 items-center justify-center rounded-full transition-colors ${
                isStarred
                  ? "text-amber-500"
                  : "border border-[var(--border)] text-[var(--text-muted)] hover:bg-[var(--surface)]"
              }`}
              title={isStarred ? "Unstar" : "Star"}
            >
              <Star className="h-4 w-4" fill={isStarred ? "currentColor" : "none"} />
            </button>
          </div>
        </div>
        <div className="flex items-center gap-2">
          <button
            type="button"
            className="flex items-center gap-2 rounded-lg border border-[var(--border)] bg-white px-4 py-2 text-sm font-medium text-[var(--foreground)] transition-colors hover:bg-[var(--surface)]"
          >
            <Upload className="h-4 w-4" />
            Upload
          </button>
          <button
            type="button"
            className="flex items-center gap-2 rounded-lg bg-[var(--accent)] px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-[var(--accent-muted)]"
          >
            <Plus className="h-4 w-4" />
            New Content
          </button>
        </div>
      </div>

      {/* Tabs */}
      {tabs && tabs.length > 0 && (
        <div className="flex border-b border-[var(--border)]">
          {tabs.map((tab) => {
            const href = tab.href ?? (tab.id === "folder" ? "/" : `/${tab.id}`);
            const isActive =
              activeTab === tab.id ||
              (href === "/" && pathname === "/") ||
              (href !== "/" && pathname.startsWith(href));
            const className = `border-b-2 px-4 py-3 text-sm font-medium transition-colors ${
              isActive
                ? "border-[var(--accent)] text-[var(--accent)]"
                : "border-transparent text-[var(--text-muted)] hover:border-[var(--border)] hover:text-[var(--foreground)]"
            }`;
            return href ? (
              <Link key={tab.id} href={href} className={className}>
                {tab.label}
              </Link>
            ) : (
              <button
                key={tab.id}
                type="button"
                onClick={() => onTabChange?.(tab.id)}
                className={className}
              >
                {tab.label}
              </button>
            );
          })}
        </div>
      )}

      {/* Toolbar: filter, search, count, sort, view toggle */}
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div className="flex flex-wrap items-center gap-3">
          <button
            type="button"
            className="flex items-center gap-2 rounded-lg border border-[var(--border)] bg-white px-3 py-2 text-sm text-[var(--text-muted)] transition-colors hover:bg-[var(--surface)]"
          >
            Add Filter
          </button>
          <div className="relative">
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--text-muted-light)]" />
            <input
              type="search"
              placeholder="Search..."
              className="w-64 rounded-lg border border-[var(--border)] bg-white py-2 pl-9 pr-4 text-sm placeholder:text-[var(--text-muted-light)] focus:border-[var(--accent)] focus:outline-none focus:ring-1 focus:ring-[var(--accent)]"
            />
          </div>
          {itemCount != null && (
            <span className="text-sm text-[var(--text-muted)]">{itemCount} content</span>
          )}
          <span className="flex items-center gap-1 text-sm text-[var(--text-muted)]">
            Date Created
            <span className="cursor-pointer">↕</span>
          </span>
        </div>
        {showViewToggle && onViewModeChange && (
          <div className="flex rounded-lg border border-[var(--border)] p-0.5">
            <button
              type="button"
              onClick={() => onViewModeChange("grid")}
              className={`rounded-md p-2 transition-colors ${
                viewMode === "grid" ? "bg-[var(--surface)] text-[var(--foreground)]" : "text-[var(--text-muted)] hover:text-[var(--foreground)]"
              }`}
              title="Grid view"
            >
              <Grid3X3 className="h-4 w-4" />
            </button>
            <button
              type="button"
              onClick={() => onViewModeChange("list")}
              className={`rounded-md p-2 transition-colors ${
                viewMode === "list" ? "bg-[var(--surface)] text-[var(--foreground)]" : "text-[var(--text-muted)] hover:text-[var(--foreground)]"
              }`}
              title="List view"
            >
              <List className="h-4 w-4" />
            </button>
          </div>
        )}
      </div>
    </div>
  );
}
