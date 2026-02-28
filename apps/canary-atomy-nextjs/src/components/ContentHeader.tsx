"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { Star, Upload, Plus, Grid3X3, List, Search } from "lucide-react";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";

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
          <h1 className="text-2xl font-bold tracking-tight text-foreground">{title}</h1>
          <div className="flex items-center gap-2">
            <Avatar className="h-8 w-8">
              <AvatarFallback className="bg-primary text-primary-foreground text-xs">AC</AvatarFallback>
            </Avatar>
            <div className="flex -space-x-2">
              {[1, 2, 3].map((i) => (
                <Avatar key={i} className="h-8 w-8 border-2 border-background">
                  <AvatarFallback className="text-xs">?</AvatarFallback>
                </Avatar>
              ))}
            </div>
            <Button variant="outline" size="icon" className="h-8 w-8 rounded-full">
              <Plus className="h-4 w-4" />
            </Button>
            <Button
              variant="ghost"
              size="icon"
              onClick={() => setIsStarred(!isStarred)}
              className={`h-8 w-8 rounded-full ${isStarred ? "text-amber-500" : "text-muted-foreground"}`}
            >
              <Star className="h-4 w-4" fill={isStarred ? "currentColor" : "none"} />
            </Button>
          </div>
        </div>
        <div className="flex items-center gap-2">
          <Button variant="outline" className="gap-2">
            <Upload className="h-4 w-4" />
            Upload
          </Button>
          <Button className="gap-2">
            <Plus className="h-4 w-4" />
            New Content
          </Button>
        </div>
      </div>

      {/* Tabs */}
      {tabs && tabs.length > 0 && (
        <div className="border-b">
          <nav className="-mb-px flex space-x-8" aria-label="Tabs">
            {tabs.map((tab) => {
              const href = tab.href ?? (tab.id === "folder" ? "/" : `/${tab.id}`);
              const isActive =
                activeTab === tab.id ||
                (href === "/" && pathname === "/") ||
                (href !== "/" && pathname.startsWith(href));
              
              if (href) {
                return (
                  <Link
                    key={tab.id}
                    href={href}
                    className={`
                      whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium
                      ${isActive 
                        ? "border-primary text-primary" 
                        : "border-transparent text-muted-foreground hover:border-muted-foreground/30 hover:text-foreground"}
                    `}
                  >
                    {tab.label}
                  </Link>
                );
              }
              
              return (
                <button
                  key={tab.id}
                  type="button"
                  onClick={() => onTabChange?.(tab.id)}
                  className={`
                    whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium
                    ${isActive 
                      ? "border-primary text-primary" 
                      : "border-transparent text-muted-foreground hover:border-muted-foreground/30 hover:text-foreground"}
                  `}
                >
                  {tab.label}
                </button>
              );
            })}
          </nav>
        </div>
      )}

      {/* Toolbar: filter, search, count, sort, view toggle */}
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div className="flex flex-wrap items-center gap-3">
          <Button variant="outline" size="sm">
            Add Filter
          </Button>
          <div className="relative">
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              type="search"
              placeholder="Search..."
              className="w-64 pl-9"
            />
          </div>
          {itemCount != null && (
            <span className="text-sm text-muted-foreground">{itemCount} content</span>
          )}
          <span className="flex items-center gap-1 text-sm text-muted-foreground">
            Date Created
            <span className="cursor-pointer">↕</span>
          </span>
        </div>
        {showViewToggle && onViewModeChange && (
          <div className="flex rounded-lg border p-0.5">
            <Button
              variant={viewMode === "grid" ? "secondary" : "ghost"}
              size="icon"
              onClick={() => onViewModeChange("grid")}
              className="h-8 w-8"
              title="Grid view"
            >
              <Grid3X3 className="h-4 w-4" />
            </Button>
            <Button
              variant={viewMode === "list" ? "secondary" : "ghost"}
              size="icon"
              onClick={() => onViewModeChange("list")}
              className="h-8 w-8"
              title="List view"
            >
              <List className="h-4 w-4" />
            </Button>
          </div>
        )}
      </div>
    </div>
  );
}

