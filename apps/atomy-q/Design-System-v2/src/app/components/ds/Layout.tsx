import React from 'react';
import {
  LayoutGrid, FileText, FolderArchive, BarChart2,
  Settings, HelpCircle, User,
} from 'lucide-react';
import { NavItem } from './Sidebar';
import { TopBar } from './TopBar';
import { MainNav } from './MainNav';

// ─── Default Layout ───────────────────────────────────────────────────────────
// Sidebar (200px) + TopBar + Content + Footer

interface DefaultLayoutProps {
  children: React.ReactNode;
  activeNav?: string;
  onNavChange?: (id: string) => void;
}

export function DefaultLayout({ children, activeNav = 'dashboard', onNavChange }: DefaultLayoutProps) {
  const [active, setActive] = React.useState(activeNav);

  function navigate(id: string) {
    setActive(id);
    onNavChange?.(id);
  }

  const mainNavItems = [
    { id: 'dashboard', label: 'Dashboard', icon: <LayoutGrid size={15} /> },
    {
      id: 'rfqs',
      label: 'Requisition',
      icon: <FileText size={15} />,
      badge: 22,
      children: [
        { id: 'rfq-active', label: 'Active', badge: 12 },
        { id: 'rfq-closed', label: 'Closed', badge: 5 },
        { id: 'rfq-awarded', label: 'Awarded', badge: 3 },
        { id: 'rfq-archived', label: 'Archived' },
        { id: 'rfq-draft', label: 'Draft', badge: 2 },
      ],
    },
    { id: 'documents', label: 'Documents', icon: <FolderArchive size={15} /> },
    { id: 'reporting', label: 'Reporting', icon: <BarChart2 size={15} /> },
    {
      id: 'settings',
      label: 'Settings',
      icon: <Settings size={15} />,
      children: [
        { id: 'settings-users', label: 'Users & Roles' },
        { id: 'settings-scoring', label: 'Scoring Policies' },
        { id: 'settings-templates', label: 'Templates' },
        { id: 'settings-integrations', label: 'Integrations' },
        { id: 'settings-flags', label: 'Feature Flags' },
      ],
    },
  ];

  return (
    <div className="flex h-screen overflow-hidden bg-slate-100 font-sans">
      {/* ─ Sidebar ─ */}
      <aside className="w-[200px] flex-shrink-0 flex flex-col bg-white border-r border-slate-200 overflow-y-auto">
        {/* Logo */}
        <div className="flex items-center gap-2.5 h-14 px-4 border-b border-slate-200 shrink-0">
          <div className="w-7 h-7 bg-indigo-600 rounded-md flex items-center justify-center">
            <span className="text-white text-xs font-bold">AQ</span>
          </div>
          <span className="text-sm font-semibold text-slate-800">Atomy-Q</span>
        </div>

        {/* Navigation */}
        <nav className="flex-1 flex flex-col py-3 px-2 gap-0.5">
          <MainNav
            items={mainNavItems}
            activeItem={active}
            onNavigate={navigate}
          />
        </nav>

        {/* Bottom items */}
        <div className="border-t border-slate-200 py-2 px-2">
          <NavItem label="Help" icon={<HelpCircle size={15} />} />
          <NavItem label="Profile" icon={<User size={15} />} />
        </div>
      </aside>

      {/* ─ Right column: TopBar + Content + Footer ─ */}
      <div className="flex-1 flex flex-col min-w-0 overflow-hidden">
        <TopBar />
        <main className="flex-1 overflow-y-auto">
          <div className="p-6">
            {children}
          </div>
        </main>
        <AppFooter />
      </div>
    </div>
  );
}

// ─── Workspace Layout ─────────────────────────────────────────────────────────
// Collapsed rail (48px, expandable) + Active Record Menu + Work Surface + TopBar

interface WorkspaceLayoutProps {
  children: React.ReactNode;
  activeRecordMenu?: React.ReactNode;
  activeNav?: string;
  onNavChange?: (id: string) => void;
}

export function WorkspaceLayout({ children, activeRecordMenu, activeNav = 'rfq-active', onNavChange }: WorkspaceLayoutProps) {
  const [railExpanded, setRailExpanded] = React.useState(false);
  const [active, setActive] = React.useState(activeNav);

  function navigate(id: string) {
    setActive(id);
    onNavChange?.(id);
  }

  const mainNavItems = [
    { id: 'dashboard', label: 'Dashboard', icon: <LayoutGrid size={15} /> },
    {
      id: 'rfqs',
      label: 'Requisition',
      icon: <FileText size={15} />,
      badge: 22,
      children: [
        { id: 'rfq-active', label: 'Active', badge: 12 },
        { id: 'rfq-closed', label: 'Closed', badge: 5 },
        { id: 'rfq-awarded', label: 'Awarded', badge: 3 },
        { id: 'rfq-archived', label: 'Archived' },
        { id: 'rfq-draft', label: 'Draft', badge: 2 },
      ],
    },
    { id: 'documents', label: 'Documents', icon: <FolderArchive size={15} /> },
    { id: 'reporting', label: 'Reporting', icon: <BarChart2 size={15} /> },
    {
      id: 'settings',
      label: 'Settings',
      icon: <Settings size={15} />,
      children: [
        { id: 'settings-users', label: 'Users & Roles' },
        { id: 'settings-scoring', label: 'Scoring Policies' },
        { id: 'settings-templates', label: 'Templates' },
        { id: 'settings-integrations', label: 'Integrations' },
        { id: 'settings-flags', label: 'Feature Flags' },
      ],
    },
  ];

  return (
    <div className="flex h-screen overflow-hidden bg-slate-100 font-sans">
      {/* ─ Rail / Expanded sidebar overlay ─ */}
      <div
        className={[
          'relative flex-shrink-0 z-20 transition-all duration-200',
          railExpanded ? 'w-[200px]' : 'w-12',
        ].join(' ')}
        onMouseEnter={() => setRailExpanded(true)}
        onMouseLeave={() => setRailExpanded(false)}
      >
        <div
          className={[
            'absolute inset-y-0 left-0 flex flex-col bg-white border-r border-slate-200 overflow-hidden transition-all duration-200',
            railExpanded ? 'w-[200px] shadow-lg' : 'w-12',
          ].join(' ')}
        >
          {/* Logo */}
          <div className="flex items-center justify-center h-14 shrink-0 border-b border-slate-200">
            <div className="w-7 h-7 bg-indigo-600 rounded-md flex items-center justify-center shrink-0">
              <span className="text-white text-xs font-bold">AQ</span>
            </div>
            {railExpanded && (
              <span className="ml-2.5 text-sm font-semibold text-slate-800 whitespace-nowrap overflow-hidden">
                Atomy-Q
              </span>
            )}
          </div>

          {/* Navigation (collapsed = icon only) */}
          <nav className="flex-1 flex flex-col py-3 px-1.5 gap-0.5 overflow-hidden">
            <MainNav
              items={mainNavItems}
              activeItem={active}
              onNavigate={navigate}
              collapsed={!railExpanded}
            />
          </nav>
        </div>
      </div>

      {/* ─ Right side: TopBar + content row ─ */}
      <div className="flex-1 flex flex-col min-w-0 overflow-hidden">
        <TopBar />

        {/* Content row: Active Record Menu + Work Surface */}
        <div className="flex flex-1 min-h-0">
          {/* Active Record Menu */}
          {activeRecordMenu ?? (
            <div className="w-[360px] shrink-0 bg-white border-r border-slate-200 overflow-y-auto">
              <div className="px-4 py-8 text-center text-sm text-slate-400">
                Active Record Menu
              </div>
            </div>
          )}

          {/* Work Surface */}
          <div className="flex-1 flex flex-col min-w-0 overflow-hidden">
            {children}
          </div>
        </div>

        <AppFooter />
      </div>
    </div>
  );
}

// ─── App Footer ───────────────────────────────────────────────────────────────

export function AppFooter({ className = '' }: { className?: string }) {
  return (
    <footer className={['flex items-center justify-between px-5 h-8 bg-white border-t border-slate-200 shrink-0', className].join(' ')}>
      <div className="flex items-center gap-3 text-[11px] text-slate-400">
        <span>Atomy-Q</span>
        <span className="inline-flex items-center gap-1 bg-slate-100 text-slate-500 border border-slate-200 rounded px-1.5 py-0 text-[10px] font-mono">
          v1.0.0
        </span>
        <span className="inline-flex items-center gap-1">
          <span className="w-1.5 h-1.5 rounded-full bg-green-500" />
          All systems operational
        </span>
      </div>
      <div className="flex items-center gap-3 text-[11px] text-slate-400">
        <a href="#" className="hover:text-slate-600">Privacy</a>
        <a href="#" className="hover:text-slate-600">Terms</a>
        <a href="#" className="hover:text-slate-600">Support</a>
      </div>
    </footer>
  );
}
