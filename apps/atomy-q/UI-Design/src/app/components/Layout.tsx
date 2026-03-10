import { useState, useEffect } from 'react';
import { Outlet, NavLink, useLocation, Link, useNavigate } from 'react-router';
import {
  LayoutDashboard, FileText, Inbox, BarChart3,
  Star, TrendingUp, MessageSquare, ShieldAlert,
  ChevronDown, Settings, Bell, Search, HelpCircle,
  Menu, ClipboardCheck, List, Award, Send, ScrollText,
  FolderArchive, PieChart, Users, Plug, Activity,
  FileStack, Scale, Cog, Bot, Plus, Play, Hand, Lightbulb,
  ExternalLink, Lock
} from 'lucide-react';
import { notifications, currentUser } from '../data/mockData';

const navGroups = [
  {
    label: 'Overview',
    items: [
      { path: '/', label: 'Dashboard', icon: LayoutDashboard, exact: true },
    ],
  },
  {
    label: 'Sourcing',
    items: [
      { path: '/rfqs', label: 'RFQ List', icon: List },
      { path: '/rfq/create', label: 'Create RFQ', icon: FileText },
      { path: '/quote-intake', label: 'Quote Intake', icon: Inbox },
      { path: '/comparison', label: 'Comparison Matrix', icon: BarChart3 },
    ],
  },
  {
    label: 'Intelligence',
    items: [
      { path: '/scoring', label: 'Scoring Models', icon: Star },
      { path: '/scenarios', label: 'Scenario Simulator', icon: TrendingUp },
      { path: '/recommendation', label: 'Recommendation', icon: Lightbulb },
    ],
  },
  {
    label: 'Governance',
    items: [
      { path: '/risk', label: 'Risk & Compliance', icon: ShieldAlert },
      { path: '/approvals', label: 'Approval Queue', icon: ClipboardCheck },
      { path: '/decision-trail', label: 'Decision Trail', icon: ScrollText },
      { path: '/evidence-vault', label: 'Evidence Vault', icon: FolderArchive },
    ],
  },
  {
    label: 'Negotiation & Award',
    items: [
      { path: '/negotiations', label: 'Negotiations', icon: MessageSquare },
      { path: '/award', label: 'Award Decision', icon: Award },
      { path: '/handoff', label: 'PO/Contract Handoff', icon: Send },
    ],
  },
  {
    label: 'Reports',
    items: [
      { path: '/reports', label: 'Reports & Analytics', icon: PieChart },
    ],
  },
  {
    label: 'Administration',
    items: [
      { path: '/admin/users', label: 'Users & Access', icon: Users },
      { path: '/admin/settings', label: 'Admin Settings', icon: Settings },
      { path: '/admin/templates', label: 'RFQ Templates', icon: FileStack },
      { path: '/admin/scoring-policies', label: 'Scoring Policies', icon: Scale },
      { path: '/admin/integrations', label: 'Integrations', icon: Plug },
      { path: '/integrations/monitor', label: 'API Monitor', icon: Activity },
    ],
  },
];

const breadcrumbMap: Record<string, string> = {
  '/': 'Dashboard',
  '/rfqs': 'RFQ List',
  '/rfq/create': 'Create RFQ',
  '/quote-intake': 'Quote Intake Inbox',
  '/normalization': 'Normalization Workspace',
  '/comparison': 'Comparison Matrix',
  '/scoring': 'Scoring Model Builder',
  '/scenarios': 'Scenario Simulator',
  '/recommendation': 'Recommendation',
  '/risk': 'Risk & Compliance',
  '/approvals': 'Approval Queue',
  '/decision-trail': 'Decision Trail',
  '/evidence-vault': 'Evidence Vault',
  '/negotiations': 'Negotiation Workspace',
  '/award': 'Award Decision',
  '/handoff': 'PO/Contract Handoff',
  '/reports': 'Reports & Analytics',
  '/admin/users': 'Users & Access',
  '/admin/settings': 'Admin Settings',
  '/admin/templates': 'RFQ Templates',
  '/admin/scoring-policies': 'Scoring Policies',
  '/admin/integrations': 'Integrations',
  '/integrations/monitor': 'API Monitor',
  '/notifications': 'Notification Center',
};

export function Layout() {
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [searchOpen, setSearchOpen] = useState(false);
  const location = useLocation();
  const navigate = useNavigate();
  const unreadCount = notifications.filter(n => !n.read).length;

  useEffect(() => {
    function handleKeyDown(e: KeyboardEvent) {
      if (e.key === '/' && !searchOpen && document.activeElement?.tagName !== 'INPUT' && document.activeElement?.tagName !== 'TEXTAREA') {
        e.preventDefault();
        setSearchOpen(true);
      }
      if (e.key === 'Escape') setSearchOpen(false);
    }
    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [searchOpen]);

  const currentBreadcrumb = breadcrumbMap[location.pathname]
    || (location.pathname.startsWith('/quote-intake/') && location.pathname !== '/quote-intake' ? 'Quote Detail' : null)
    || location.pathname.split('/').pop()?.replace(/-/g, ' ') || '';

  return (
    <div className="flex h-screen bg-slate-100 font-[Inter,sans-serif] overflow-hidden">
      {/* Sidebar */}
      <aside className={`${sidebarOpen ? 'w-60' : 'w-14'} flex-shrink-0 bg-[#0B1629] flex flex-col transition-all duration-200 overflow-hidden`}>
        {/* Logo */}
        <div className="flex items-center gap-2.5 px-3 py-3 border-b border-white/10 h-13 flex-shrink-0">
          <div className="w-7 h-7 rounded-lg bg-indigo-500 flex items-center justify-center flex-shrink-0">
            <BarChart3 size={14} className="text-white" />
          </div>
          {sidebarOpen && (
            <div className="min-w-0">
              <div className="text-white text-sm" style={{ fontWeight: 700, letterSpacing: '-0.02em' }}>Atomy-Q</div>
              <div className="text-slate-500 text-[10px]" style={{ fontWeight: 400 }}>Procurement Intelligence</div>
            </div>
          )}
        </div>

        {/* Nav */}
        <nav className="flex-1 overflow-y-auto py-1.5 px-1.5 scrollbar-thin">
          {navGroups.map((group) => (
            <div key={group.label} className="mb-2.5">
              {sidebarOpen && (
                <div className="text-slate-500 uppercase px-2 mb-1 mt-1" style={{ fontSize: '9px', fontWeight: 600, letterSpacing: '0.08em' }}>
                  {group.label}
                </div>
              )}
              {group.items.map((item) => {
                const Icon = item.icon;
                const isActive = item.exact
                  ? location.pathname === item.path
                  : location.pathname === item.path || location.pathname.startsWith(item.path + '/');
                return (
                  <NavLink
                    key={item.path}
                    to={item.path}
                    className={`flex items-center gap-2.5 px-2 py-1.5 rounded-md mb-px transition-all text-[13px] ${
                      isActive
                        ? 'bg-indigo-600/90 text-white'
                        : 'text-slate-400 hover:text-slate-200 hover:bg-white/5'
                    }`}
                  >
                    <Icon size={15} className="flex-shrink-0" />
                    {sidebarOpen && <span style={{ fontWeight: isActive ? 500 : 400 }}>{item.label}</span>}
                  </NavLink>
                );
              })}
            </div>
          ))}
        </nav>

        {/* Bottom */}
        <div className="border-t border-white/10 p-2.5 flex-shrink-0">
          {sidebarOpen ? (
            <div className="flex items-center gap-2">
              <div className={`w-7 h-7 rounded-full ${currentUser.avatarColor} flex items-center justify-center flex-shrink-0`}>
                <span className="text-white text-[10px]" style={{ fontWeight: 600 }}>{currentUser.initials}</span>
              </div>
              <div className="flex-1 min-w-0">
                <div className="text-white text-xs" style={{ fontWeight: 500 }}>{currentUser.name}</div>
                <div className="text-slate-500 text-[10px] truncate">{currentUser.role}</div>
              </div>
              <button onClick={() => navigate('/admin/settings')} className="text-slate-500 hover:text-slate-300">
                <Cog size={13} />
              </button>
            </div>
          ) : (
            <div className="flex justify-center">
              <div className={`w-7 h-7 rounded-full ${currentUser.avatarColor} flex items-center justify-center`}>
                <span className="text-white text-[10px]" style={{ fontWeight: 600 }}>{currentUser.initials}</span>
              </div>
            </div>
          )}
        </div>
      </aside>

      {/* Main */}
      <div className="flex-1 flex flex-col overflow-hidden">
        {/* Top bar */}
        <header className="bg-white border-b border-slate-200 h-13 flex items-center px-4 gap-3 flex-shrink-0">
          <button onClick={() => setSidebarOpen(!sidebarOpen)} className="text-slate-400 hover:text-slate-700 p-1 rounded">
            <Menu size={17} />
          </button>

          {/* Breadcrumb */}
          <div className="flex items-center gap-1.5 text-sm">
            <Link to="/" className="text-slate-400 hover:text-slate-600 text-xs">Home</Link>
            {location.pathname !== '/' && (
              <>
                <span className="text-slate-300 text-xs">/</span>
                <span className="text-slate-700 text-xs" style={{ fontWeight: 500 }}>{currentBreadcrumb}</span>
              </>
            )}
          </div>

          <div className="flex-1" />

          {/* Quick Actions */}
          <div className="flex items-center gap-1.5">
            <Link to="/rfq/create" className="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs bg-indigo-600 hover:bg-indigo-700 text-white transition-colors" style={{ fontWeight: 500 }}>
              <Plus size={13} />
              New RFQ
            </Link>
            <Link to="/comparison" className="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors" style={{ fontWeight: 500 }}>
              <Play size={12} />
              Run Comparison
            </Link>
            <Link to="/approvals" className="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors" style={{ fontWeight: 500 }}>
              <Hand size={12} />
              Approvals
            </Link>
          </div>

          <div className="w-px h-5 bg-slate-200 mx-1" />

          {/* Search Trigger */}
          <button onClick={() => setSearchOpen(!searchOpen)} className="flex items-center gap-2 px-2.5 py-1.5 rounded-lg border border-slate-200 text-slate-400 hover:border-slate-300 hover:text-slate-500 transition-colors">
            <Search size={13} />
            <span className="text-xs">Search...</span>
            <kbd className="text-[10px] bg-slate-100 px-1 rounded text-slate-400">/</kbd>
          </button>

          {/* AI Assistant */}
          <button className="p-2 rounded-lg text-slate-400 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
            <Bot size={16} />
          </button>

          {/* Notifications */}
          <Link to="/notifications" className="p-2 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700 relative transition-colors">
            <Bell size={16} />
            {unreadCount > 0 && (
              <span className="absolute top-1 right-1 min-w-[14px] h-[14px] bg-red-500 rounded-full flex items-center justify-center">
                <span className="text-white text-[9px]" style={{ fontWeight: 700 }}>{unreadCount}</span>
              </span>
            )}
          </Link>

          {/* User dropdown */}
          <div className="flex items-center gap-1.5 px-2 py-1.5 rounded-lg hover:bg-slate-100 cursor-pointer transition-colors">
            <div className={`w-6 h-6 rounded-full ${currentUser.avatarColor} flex items-center justify-center`}>
              <span className="text-white text-[10px]" style={{ fontWeight: 600 }}>{currentUser.initials}</span>
            </div>
            <span className="text-sm text-slate-700" style={{ fontWeight: 500 }}>{currentUser.name}</span>
            <ChevronDown size={12} className="text-slate-400" />
          </div>
        </header>

        {/* Search overlay */}
        {searchOpen && (
          <div className="absolute inset-0 z-50 bg-black/30" onClick={() => setSearchOpen(false)}>
            <div className="max-w-xl mx-auto mt-20 bg-white rounded-xl shadow-2xl border border-slate-200 overflow-hidden" onClick={e => e.stopPropagation()}>
              <div className="flex items-center gap-3 px-4 py-3 border-b border-slate-100">
                <Search size={16} className="text-slate-400" />
                <input autoFocus type="text" placeholder="Search RFQs, vendors, quotes, approvals..." className="flex-1 text-sm outline-none text-slate-700 placeholder-slate-400" />
                <kbd className="text-[10px] bg-slate-100 px-1.5 py-0.5 rounded text-slate-400">ESC</kbd>
              </div>
              <div className="p-3">
                <div className="text-[10px] uppercase text-slate-400 px-2 mb-2" style={{ fontWeight: 600, letterSpacing: '0.05em' }}>Quick links</div>
                {[
                  { label: 'RFQ-2401 — Server Infrastructure', path: '/rfq/RFQ-2401' },
                  { label: 'PrimeSource Co. — Vendor Profile', path: '/vendors/vnd-004' },
                  { label: 'Pending Approvals (4)', path: '/approvals' },
                ].map(item => (
                  <button key={item.path} onClick={() => { navigate(item.path); setSearchOpen(false); }} className="flex items-center gap-2 w-full px-2 py-2 text-sm text-slate-600 hover:bg-slate-50 rounded-lg transition-colors text-left">
                    <ExternalLink size={13} className="text-slate-400" />
                    {item.label}
                  </button>
                ))}
              </div>
            </div>
          </div>
        )}

        {/* Content */}
        <main className="flex-1 overflow-auto bg-slate-50">
          <Outlet />
        </main>

        {/* Footer */}
        <footer className="bg-white border-t border-slate-200 h-8 flex items-center px-4 gap-4 flex-shrink-0">
          <span className="text-[10px] text-slate-400">Atomy-Q v0.9.1</span>
          <span className="inline-flex items-center gap-1 text-[10px] px-1.5 py-0.5 rounded bg-amber-50 text-amber-600 border border-amber-200" style={{ fontWeight: 500 }}>Staging</span>
          <div className="flex-1" />
          <span className="flex items-center gap-1 text-[10px] text-slate-400">
            <span className="w-1.5 h-1.5 rounded-full bg-emerald-500" />
            All systems operational
          </span>
          <span className="text-slate-300">|</span>
          <button className="text-[10px] text-slate-400 hover:text-slate-600">API Docs</button>
          <button className="text-[10px] text-slate-400 hover:text-slate-600">Privacy</button>
          <button className="text-[10px] text-slate-400 hover:text-slate-600">Terms</button>
        </footer>
      </div>
    </div>
  );
}
