import { useState } from "react";
import { Outlet, useLocation, useNavigate } from "react-router";
import {
  LayoutDashboard, FileText, Inbox, GitCompareArrows, CheckCircle2, BarChart2,
  Bell, ClipboardList, AtSign,
  Shield, ScrollText, Archive,
  Users, SlidersHorizontal, LayoutTemplate, Plug, Flag,
  User, Settings, HelpCircle,
  Search, Plus, Play, SendHorizonal, Sparkles, ChevronDown,
  LogOut, ChevronRight, Zap, Menu, X, GitBranch
} from "lucide-react";

const navSections = [
  {
    label: "WORKSPACE",
    items: [
      { label: "Dashboard", path: "/dashboard", icon: LayoutDashboard },
      { label: "RFQs", path: "/rfqs", icon: FileText },
      { label: "Quote Intake", path: "/quote-intake", icon: Inbox },
      { label: "Comparison Matrix", path: "/comparison", icon: GitCompareArrows },
      { label: "Approvals", path: "/approvals", icon: CheckCircle2 },
      { label: "Reports", path: "/reports", icon: BarChart2 },
    ],
  },
  {
    label: "COLLABORATION",
    items: [
      { label: "Notifications", path: "/notifications", icon: Bell, badge: 5 },
      { label: "Tasks", path: "/tasks", icon: ClipboardList, badge: 3 },
      { label: "Mentions", path: "/mentions", icon: AtSign },
    ],
  },
  {
    label: "GOVERNANCE",
    items: [
      { label: "Risk & Compliance", path: "/risk", icon: Shield },
      { label: "Decision Trail", path: "/decision-trail", icon: GitBranch },
      { label: "Evidence Vault", path: "/evidence", icon: Archive },
    ],
  },
  {
    label: "ADMINISTRATION",
    items: [
      { label: "Users & Access", path: "/users", icon: Users },
      { label: "Scoring Policies", path: "/scoring", icon: SlidersHorizontal },
      { label: "Templates", path: "/templates", icon: LayoutTemplate },
      { label: "Integrations", path: "/integrations", icon: Plug },
      { label: "Feature Flags", path: "/flags", icon: Flag },
    ],
  },
];

const accountItems = [
  { label: "Profile", path: "/profile", icon: User },
  { label: "Preferences", path: "/preferences", icon: Settings },
  { label: "Help & Docs", path: "/help", icon: HelpCircle },
];

function getBreadcrumbs(pathname: string) {
  const crumbs: { label: string; path: string }[] = [{ label: "Atomy-Q", path: "/dashboard" }];
  const parts = pathname.split("/").filter(Boolean);
  if (!parts.length) return crumbs;
  if (parts[0] === "dashboard") crumbs.push({ label: "Dashboard", path: "/dashboard" });
  else if (parts[0] === "rfqs") {
    crumbs.push({ label: "RFQs", path: "/rfqs" });
    if (parts[1] === "create") crumbs.push({ label: "New RFQ", path: "/rfqs/create" });
    else if (parts[1]) {
      crumbs.push({ label: parts[1].toUpperCase(), path: `/rfqs/${parts[1]}` });
      if (parts[2] === "vendors") crumbs.push({ label: "Vendor Invitations", path: `/rfqs/${parts[1]}/vendors` });
    }
  } else if (parts[0] === "quote-intake") crumbs.push({ label: "Quote Intake", path: "/quote-intake" });
  else if (parts[0] === "approvals") crumbs.push({ label: "Approvals", path: "/approvals" });
  else if (parts[0] === "reports") crumbs.push({ label: "Reports", path: "/reports" });
  else if (parts[0] === "risk") crumbs.push({ label: "Risk & Compliance", path: "/risk" });
  return crumbs;
}

export function AppShell() {
  const location = useLocation();
  const navigate = useNavigate();
  const [userMenuOpen, setUserMenuOpen] = useState(false);
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
  const [searchFocused, setSearchFocused] = useState(false);

  const breadcrumbs = getBreadcrumbs(location.pathname);

  const isActive = (path: string) => {
    if (path === "/rfqs") return location.pathname === "/rfqs" || (location.pathname.startsWith("/rfqs/") && location.pathname !== "/rfqs/create");
    return location.pathname === path || location.pathname.startsWith(path + "/");
  };

  return (
    <div className="flex h-screen overflow-hidden" style={{ background: "var(--app-bg-canvas)", color: "var(--app-text-main)", fontFamily: "'Inter', system-ui, sans-serif" }}>
      {/* Sidebar */}
      <aside
        className="flex-shrink-0 flex flex-col border-r transition-all duration-200"
        style={{
          width: sidebarCollapsed ? 56 : 220,
          background: "var(--app-bg-surface)",
          borderColor: "var(--app-border-strong)",
        }}
      >
        {/* Logo */}
        <div className="flex items-center gap-2.5 px-3 h-14 border-b flex-shrink-0" style={{ borderColor: "var(--app-border-strong)" }}>
          <div className="flex items-center justify-center rounded-lg flex-shrink-0" style={{ width: 30, height: 30, background: "linear-gradient(135deg, var(--app-brand-500), var(--app-brand-700))" }}>
            <Zap size={15} color="white" />
          </div>
          {!sidebarCollapsed && (
            <div>
              <div style={{ fontSize: 14, fontWeight: 700, color: "var(--app-text-strong)", letterSpacing: "-0.01em", lineHeight: 1 }}>Atomy-Q</div>
              <div style={{ fontSize: 9, color: "var(--app-text-subtle)", letterSpacing: "0.08em", fontWeight: 500, textTransform: "uppercase", lineHeight: 1.4 }}>Procurement Intelligence</div>
            </div>
          )}
          <button
            onClick={() => setSidebarCollapsed(!sidebarCollapsed)}
            className="ml-auto rounded flex items-center justify-center hover:bg-slate-700 transition-colors"
            style={{ width: 22, height: 22, color: "var(--app-text-muted)" }}
          >
            {sidebarCollapsed ? <ChevronRight size={13} /> : <Menu size={13} />}
          </button>
        </div>

        {/* Nav */}
        <div className="flex-1 overflow-y-auto overflow-x-hidden py-3" style={{ scrollbarWidth: "none" }}>
          {navSections.map((section) => (
            <div key={section.label} className="mb-4">
              {!sidebarCollapsed && (
                <div className="px-3 mb-1" style={{ fontSize: 9, fontWeight: 600, color: "var(--app-text-faint)", letterSpacing: "0.1em" }}>
                  {section.label}
                </div>
              )}
              {section.items.map((item) => {
                const active = isActive(item.path);
                const Icon = item.icon;
                return (
                  <button
                    key={item.path}
                    onClick={() => navigate(item.path)}
                    className="w-full flex items-center gap-2.5 transition-all duration-100 rounded mx-1 relative"
                    style={{
                      padding: sidebarCollapsed ? "7px 14px" : "7px 10px",
                      width: sidebarCollapsed ? "calc(100% - 8px)" : "calc(100% - 8px)",
                      background: active ? "var(--app-brand-tint-12)" : "transparent",
                      color: active ? "var(--app-brand-400)" : "var(--app-text-subtle)",
                      borderLeft: active ? "2px solid var(--app-brand-500)" : "2px solid transparent",
                    }}
                    onMouseEnter={(e) => {
                      if (!active) (e.currentTarget as HTMLButtonElement).style.background = "var(--app-hover-soft)";
                    }}
                    onMouseLeave={(e) => {
                      if (!active) (e.currentTarget as HTMLButtonElement).style.background = "transparent";
                    }}
                  >
                    <Icon size={15} style={{ flexShrink: 0 }} />
                    {!sidebarCollapsed && (
                      <>
                        <span style={{ fontSize: 13, fontWeight: active ? 500 : 400, flex: 1, textAlign: "left" }}>{item.label}</span>
                        {"badge" in item && item.badge && (
                          <span style={{ fontSize: 10, fontWeight: 600, background: "var(--app-brand-500)", color: "white", borderRadius: 10, padding: "1px 5px", lineHeight: 1.6 }}>
                            {item.badge}
                          </span>
                        )}
                      </>
                    )}
                  </button>
                );
              })}
            </div>
          ))}
        </div>

        {/* Account */}
        <div className="border-t py-2 flex-shrink-0" style={{ borderColor: "var(--app-border-strong)" }}>
          {accountItems.map((item) => {
            const Icon = item.icon;
            return (
              <button
                key={item.path}
                onClick={() => navigate(item.path)}
                className="w-full flex items-center gap-2.5 transition-colors rounded mx-1"
                style={{
                  padding: sidebarCollapsed ? "6px 14px" : "6px 10px",
                  width: "calc(100% - 8px)",
                  color: "var(--app-text-muted)",
                }}
                onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.color = "var(--app-text-subtle)"; (e.currentTarget as HTMLButtonElement).style.background = "var(--app-hover-soft)"; }}
                onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.color = "var(--app-text-muted)"; (e.currentTarget as HTMLButtonElement).style.background = "transparent"; }}
              >
                <Icon size={14} style={{ flexShrink: 0 }} />
                {!sidebarCollapsed && <span style={{ fontSize: 13 }}>{item.label}</span>}
              </button>
            );
          })}
        </div>
      </aside>

      {/* Main */}
      <div className="flex flex-col flex-1 overflow-hidden">
        {/* Header */}
        <header className="flex-shrink-0 flex items-center gap-3 px-4 border-b" style={{ height: 56, background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
          {/* Breadcrumb */}
          <div className="flex items-center gap-1 flex-shrink-0" style={{ fontSize: 13 }}>
            {breadcrumbs.map((crumb, i) => (
              <span key={crumb.path} className="flex items-center gap-1">
                {i > 0 && <ChevronRight size={12} style={{ color: "var(--app-text-faint)" }} />}
                <button
                  onClick={() => navigate(crumb.path)}
                  style={{ color: i === breadcrumbs.length - 1 ? "var(--app-text-main)" : "var(--app-text-subtle)", fontWeight: i === breadcrumbs.length - 1 ? 500 : 400 }}
                  className="hover:opacity-80 transition-opacity"
                >
                  {crumb.label}
                </button>
              </span>
            ))}
          </div>

          {/* Search */}
          <div
            className="flex items-center gap-2 rounded-lg px-3 flex-1 max-w-xs transition-all duration-150 ml-4"
            style={{ background: searchFocused ? "var(--app-border-strong)" : "var(--app-bg-elevated)", border: `1px solid ${searchFocused ? "var(--app-brand-500)" : "var(--app-border-strong)"}`, height: 34 }}
          >
            <Search size={13} style={{ color: "var(--app-text-subtle)", flexShrink: 0 }} />
            <input
              onFocus={() => setSearchFocused(true)}
              onBlur={() => setSearchFocused(false)}
              placeholder="Search RFQs, vendors, quotes…"
              style={{ background: "transparent", border: "none", outline: "none", fontSize: 13, color: "var(--app-text-subtle)", width: "100%" }}
            />
            <kbd style={{ fontSize: 10, color: "var(--app-text-faint)", background: "var(--app-border-strong)", border: "1px solid var(--app-text-faint)", borderRadius: 4, padding: "1px 5px", fontFamily: "monospace" }}>/</kbd>
          </div>

          <div className="flex-1" />

          {/* Quick Actions */}
          <button
            onClick={() => navigate("/rfqs/create")}
            className="flex items-center gap-1.5 rounded-lg px-3 transition-colors hover:opacity-90 flex-shrink-0"
            style={{ height: 32, background: "var(--app-brand-600)", color: "white", fontSize: 13, fontWeight: 500 }}
          >
            <Plus size={13} />
            New RFQ
          </button>
          <button
            className="flex items-center gap-1.5 rounded-lg px-3 transition-colors flex-shrink-0"
            style={{ height: 32, background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", color: "var(--app-text-subtle)", fontSize: 13 }}
            onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-text-faint)"; }}
            onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-border-strong)"; }}
          >
            <Play size={12} />
            Run Comparison
          </button>
          <button
            className="flex items-center gap-1.5 rounded-lg px-3 transition-colors flex-shrink-0"
            style={{ height: 32, background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", color: "var(--app-text-subtle)", fontSize: 13 }}
            onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-text-faint)"; }}
            onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-border-strong)"; }}
          >
            <SendHorizonal size={12} />
            Request Approval
          </button>

          {/* AI Agent */}
          <button
            className="flex items-center justify-center rounded-lg transition-colors flex-shrink-0"
            style={{ width: 34, height: 34, background: "var(--app-purple-tint-12)", border: "1px solid var(--app-purple-tint-20)", color: "var(--app-accent-purple)" }}
            title="AI Agent Assistant"
            onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.background = "var(--app-purple-tint-20)"; }}
            onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.background = "var(--app-purple-tint-12)"; }}
          >
            <Sparkles size={14} />
          </button>

          {/* Notification Bell */}
          <button className="relative flex items-center justify-center rounded-lg transition-colors flex-shrink-0" style={{ width: 34, height: 34, background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", color: "var(--app-text-subtle)" }}>
            <Bell size={14} />
            <span className="absolute top-1 right-1 rounded-full" style={{ width: 8, height: 8, background: "var(--app-danger)", fontSize: 8, lineHeight: 1 }} />
          </button>

          {/* User Menu */}
          <div className="relative flex-shrink-0">
            <button
              onClick={() => setUserMenuOpen(!userMenuOpen)}
              className="flex items-center gap-2 rounded-lg px-2 transition-colors"
              style={{ height: 34, background: userMenuOpen ? "var(--app-border-strong)" : "transparent", border: "1px solid transparent" }}
              onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.background = "var(--app-border-strong)"; }}
              onMouseLeave={(e) => { if (!userMenuOpen) (e.currentTarget as HTMLButtonElement).style.background = "transparent"; }}
            >
              <div className="rounded-full flex items-center justify-center flex-shrink-0" style={{ width: 26, height: 26, background: "linear-gradient(135deg, var(--app-brand-500), var(--app-accent-purple))", fontSize: 11, fontWeight: 700, color: "white" }}>
                SC
              </div>
              <div className="text-left">
                <div style={{ fontSize: 12, fontWeight: 500, color: "var(--app-text-main)", lineHeight: 1.2 }}>Sarah Chen</div>
                <div style={{ fontSize: 10, color: "var(--app-text-subtle)", lineHeight: 1.2 }}>Buyer</div>
              </div>
              <ChevronDown size={12} style={{ color: "var(--app-text-subtle)" }} />
            </button>
            {userMenuOpen && (
              <div className="absolute right-0 top-full mt-1 rounded-lg border shadow-xl z-50 py-1" style={{ background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", minWidth: 180 }}>
                {[
                  { label: "Profile", icon: User },
                  { label: "Preferences", icon: Settings },
                  { label: "Help & Docs", icon: HelpCircle },
                ].map((item) => (
                  <button key={item.label} className="w-full flex items-center gap-2.5 px-3 py-2 transition-colors text-left" style={{ fontSize: 13, color: "var(--app-text-subtle)" }}
                    onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.background = "var(--app-hover-strong)"; (e.currentTarget as HTMLButtonElement).style.color = "var(--app-text-main)"; }}
                    onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.background = "transparent"; (e.currentTarget as HTMLButtonElement).style.color = "var(--app-text-subtle)"; }}>
                    <item.icon size={13} />
                    {item.label}
                  </button>
                ))}
                <div className="my-1" style={{ borderTop: "1px solid var(--app-border-strong)" }} />
                <button
                  onClick={() => navigate("/")}
                  className="w-full flex items-center gap-2.5 px-3 py-2 transition-colors text-left"
                  style={{ fontSize: 13, color: "var(--app-danger)" }}
                  onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.background = "var(--app-danger-tint-8)"; }}
                  onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.background = "transparent"; }}
                >
                  <LogOut size={13} />
                  Sign Out
                </button>
              </div>
            )}
          </div>
        </header>

        {/* Content */}
        <main className="flex-1 overflow-auto" style={{ background: "var(--app-bg-canvas)" }}>
          <Outlet />
        </main>

        {/* Footer */}
        <footer className="flex-shrink-0 flex items-center justify-between px-4 border-t" style={{ height: 32, background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
          <div className="flex items-center gap-3" style={{ fontSize: 11, color: "var(--app-text-faint)" }}>
            <span>v2.4.1-prod</span>
            <span className="px-1.5 py-0.5 rounded text-xs font-mono" style={{ background: "var(--app-success-tint-10)", color: "var(--app-success)", border: "1px solid var(--app-success-tint-20)", fontSize: 10 }}>PRODUCTION</span>
            <a href="#" style={{ color: "var(--app-text-subtle)" }} className="hover:text-slate-300 transition-colors">System Status ↗</a>
          </div>
          <div className="flex items-center gap-3" style={{ fontSize: 11, color: "var(--app-text-faint)" }}>
            {["API", "Docs", "Privacy", "Terms", "Security"].map((link) => (
              <a key={link} href="#" className="hover:text-slate-400 transition-colors" style={{ color: "var(--app-text-faint)" }}>{link}</a>
            ))}
          </div>
        </footer>
      </div>

      {/* Click outside to close user menu */}
      {userMenuOpen && <div className="fixed inset-0 z-40" onClick={() => setUserMenuOpen(false)} />}
    </div>
  );
}
