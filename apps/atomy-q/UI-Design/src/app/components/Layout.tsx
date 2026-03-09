import { useState } from 'react';
import { Outlet, NavLink, useLocation } from 'react-router';
import {
  LayoutDashboard, FileText, Inbox, ArrowLeftRight, BarChart3,
  Star, TrendingUp, MessageSquare, PieChart, Users, ShieldAlert,
  ChevronDown, ChevronRight, Settings, Bell, Search, HelpCircle,
  LogOut, Zap, Menu, X, ClipboardCheck
} from 'lucide-react';

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
      { path: '/rfq/create', label: 'Create RFQ', icon: FileText },
      { path: '/quote-intake', label: 'Quote Intake Inbox', icon: Inbox },
      { path: '/normalization', label: 'Quote Normalization', icon: ArrowLeftRight },
      { path: '/comparison', label: 'Quote Comparison', icon: BarChart3 },
    ],
  },
  {
    label: 'Intelligence',
    items: [
      { path: '/scoring', label: 'Scoring Models', icon: Star },
      { path: '/scenarios', label: 'Scenario Simulator', icon: TrendingUp },
      { path: '/negotiations', label: 'Negotiations', icon: MessageSquare },
    ],
  },
  {
    label: 'Governance',
    items: [
      { path: '/risk', label: 'Risk & Compliance', icon: ShieldAlert },
      { path: '/approvals', label: 'Approvals', icon: ClipboardCheck },
      { path: '/reports', label: 'Reports & Analytics', icon: PieChart },
    ],
  },
  {
    label: 'Administration',
    items: [
      { path: '/users', label: 'User & Access', icon: Users },
    ],
  },
];

export function Layout() {
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const location = useLocation();

  return (
    <div className="flex h-screen bg-slate-100 font-[Inter,sans-serif] overflow-hidden">
      {/* Sidebar */}
      <aside
        className={`${sidebarOpen ? 'w-64' : 'w-16'} flex-shrink-0 bg-[#0B1629] flex flex-col transition-all duration-200 overflow-hidden`}
      >
        {/* Logo */}
        <div className="flex items-center gap-3 px-4 py-4 border-b border-white/10 h-14 flex-shrink-0">
          <div className="w-8 h-8 rounded-lg bg-indigo-500 flex items-center justify-center flex-shrink-0">
            <Zap size={16} className="text-white" />
          </div>
          {sidebarOpen && (
            <div className="min-w-0">
              <div className="text-white text-sm" style={{ fontWeight: 700, letterSpacing: '-0.02em' }}>Atomy-Q</div>
              <div className="text-slate-400 text-xs" style={{ fontWeight: 400 }}>Procurement Intelligence</div>
            </div>
          )}
        </div>

        {/* Search */}
        {sidebarOpen && (
          <div className="px-3 py-3">
            <div className="flex items-center gap-2 bg-white/5 rounded-lg px-3 py-2 border border-white/10">
              <Search size={13} className="text-slate-500 flex-shrink-0" />
              <span className="text-slate-500 text-xs">Search...</span>
              <span className="ml-auto text-slate-600 text-xs bg-white/5 rounded px-1">⌘K</span>
            </div>
          </div>
        )}

        {/* Nav */}
        <nav className="flex-1 overflow-y-auto py-2 px-2">
          {navGroups.map((group) => (
            <div key={group.label} className="mb-4">
              {sidebarOpen && (
                <div className="text-slate-500 uppercase px-2 mb-1" style={{ fontSize: '10px', fontWeight: 600, letterSpacing: '0.08em' }}>
                  {group.label}
                </div>
              )}
              {group.items.map((item) => {
                const Icon = item.icon;
                const isActive = item.exact
                  ? location.pathname === item.path
                  : location.pathname.startsWith(item.path);
                return (
                  <NavLink
                    key={item.path}
                    to={item.path}
                    className={`flex items-center gap-3 px-2 py-2 rounded-lg mb-0.5 transition-all text-sm ${
                      isActive
                        ? 'bg-indigo-600 text-white'
                        : 'text-slate-400 hover:text-white hover:bg-white/5'
                    }`}
                  >
                    <Icon size={16} className="flex-shrink-0" />
                    {sidebarOpen && <span style={{ fontWeight: isActive ? 500 : 400 }}>{item.label}</span>}
                  </NavLink>
                );
              })}
            </div>
          ))}
        </nav>

        {/* Bottom */}
        <div className="border-t border-white/10 p-3 flex-shrink-0">
          {sidebarOpen ? (
            <div className="flex items-center gap-2">
              <div className="w-7 h-7 rounded-full bg-indigo-500 flex items-center justify-center flex-shrink-0">
                <span className="text-white text-xs" style={{ fontWeight: 600 }}>AK</span>
              </div>
              <div className="flex-1 min-w-0">
                <div className="text-white text-xs" style={{ fontWeight: 500 }}>Alex Kumar</div>
                <div className="text-slate-500 text-xs truncate">Procurement Lead</div>
              </div>
              <button className="text-slate-500 hover:text-slate-300">
                <Settings size={14} />
              </button>
            </div>
          ) : (
            <div className="flex justify-center">
              <div className="w-7 h-7 rounded-full bg-indigo-500 flex items-center justify-center">
                <span className="text-white text-xs" style={{ fontWeight: 600 }}>AK</span>
              </div>
            </div>
          )}
        </div>
      </aside>

      {/* Main */}
      <div className="flex-1 flex flex-col overflow-hidden">
        {/* Top bar */}
        <header className="bg-white border-b border-slate-200 h-14 flex items-center px-4 gap-3 flex-shrink-0">
          <button
            onClick={() => setSidebarOpen(!sidebarOpen)}
            className="text-slate-500 hover:text-slate-800 p-1 rounded"
          >
            <Menu size={18} />
          </button>
          <div className="flex-1" />
          <div className="flex items-center gap-1">
            <button className="p-2 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-800 relative">
              <Bell size={16} />
              <span className="absolute top-1.5 right-1.5 w-1.5 h-1.5 bg-red-500 rounded-full" />
            </button>
            <button className="p-2 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-800">
              <HelpCircle size={16} />
            </button>
            <div className="w-px h-5 bg-slate-200 mx-1" />
            <div className="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-100 cursor-pointer">
              <div className="w-6 h-6 rounded-full bg-indigo-500 flex items-center justify-center">
                <span className="text-white text-xs" style={{ fontWeight: 600 }}>AK</span>
              </div>
              <span className="text-sm text-slate-700" style={{ fontWeight: 500 }}>Alex Kumar</span>
              <ChevronDown size={13} className="text-slate-400" />
            </div>
          </div>
        </header>

        {/* Content */}
        <main className="flex-1 overflow-auto bg-slate-50">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
