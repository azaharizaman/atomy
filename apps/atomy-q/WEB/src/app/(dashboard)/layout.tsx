'use client';

import React from 'react';
import { usePathname, useRouter } from 'next/navigation';
import {
  LayoutDashboard, FileText, ShoppingCart, Inbox, CheckSquare,
  BarChart2, Settings, Users, Scale, AlertTriangle
} from 'lucide-react';
import { NavGroup, NavItem, NavLabel, SubNavItem } from '@/components/layout/sidebar';
import { Header } from '@/components/layout/header';

export default function DashboardLayout({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const pathname = usePathname();

  return (
    <div className="flex h-screen bg-slate-50 overflow-hidden font-sans">
      {/* Sidebar */}
      <aside className="w-64 bg-white border-r border-slate-200 flex flex-col shrink-0 z-20">
        <div className="h-14 flex items-center px-4 border-b border-slate-100">
          <div className="font-bold text-lg text-indigo-600 tracking-tight flex items-center gap-2">
            <div className="w-6 h-6 rounded bg-indigo-600" />
            Atomy-Q
          </div>
        </div>

        <nav className="flex-1 overflow-y-auto p-3 space-y-0.5 custom-scrollbar">
          <NavItem
            label="Dashboard"
            icon={<LayoutDashboard size={18} />}
            active={pathname === '/'}
            onClick={() => router.push('/')}
          />

          <NavLabel label="Procurement" />

          <NavGroup
            label="RFQ Management"
            icon={<FileText size={18} />}
            active={pathname.startsWith('/rfqs')}
            defaultOpen={pathname.startsWith('/rfqs')}
          >
            <SubNavItem
              label="All RFQs"
              active={pathname === '/rfqs'}
              onClick={() => router.push('/rfqs')}
            />
            <SubNavItem
              label="Templates"
              active={pathname === '/rfqs/templates'}
              onClick={() => router.push('/rfqs/templates')}
            />
          </NavGroup>

          <NavGroup
            label="Quote Intake"
            icon={<Inbox size={18} />}
            active={pathname.startsWith('/quote-intake')}
            badge={3}
          >
            <SubNavItem
              label="Submissions"
              active={pathname === '/quote-intake'}
              onClick={() => router.push('/quote-intake')}
              badge={3}
            />
            <SubNavItem
              label="Normalization"
              active={pathname === '/quote-intake/normalization'}
              onClick={() => router.push('/quote-intake/normalization')}
            />
          </NavGroup>

          <NavItem
            label="Comparison Matrix"
            icon={<Scale size={18} />}
            active={pathname.startsWith('/comparison')}
            onClick={() => router.push('/comparison')}
          />

          <NavLabel label="Network" />

          <NavItem
            label="Vendors"
            icon={<ShoppingCart size={18} />}
            active={pathname.startsWith('/vendors')}
            onClick={() => router.push('/vendors')}
          />

          <NavLabel label="Governance" />

          <NavItem
            label="Approvals"
            icon={<CheckSquare size={18} />}
            active={pathname.startsWith('/approvals')}
            badge={5}
            onClick={() => router.push('/approvals')}
          />

          <NavItem
            label="Risk & Compliance"
            icon={<AlertTriangle size={18} />}
            active={pathname.startsWith('/risk')}
            onClick={() => router.push('/risk')}
          />

          <NavItem
            label="Reports"
            icon={<BarChart2 size={18} />}
            active={pathname.startsWith('/reports')}
            onClick={() => router.push('/reports')}
          />

          <NavLabel label="System" />

          <NavItem
            label="Settings"
            icon={<Settings size={18} />}
            active={pathname.startsWith('/settings')}
            onClick={() => router.push('/settings')}
          />

          <NavItem
            label="Users & Access"
            icon={<Users size={18} />}
            active={pathname.startsWith('/users')}
            onClick={() => router.push('/users')}
          />
        </nav>

        <div className="p-4 border-t border-slate-100">
          <div className="flex items-center gap-3">
            <div className="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-medium text-xs">
              JD
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-slate-900 truncate">John Doe</p>
              <p className="text-xs text-slate-500 truncate">Acme Corp</p>
            </div>
          </div>
        </div>
      </aside>

      {/* Main Content */}
      <div className="flex-1 flex flex-col min-w-0">
        <Header />
        <main className="flex-1 overflow-y-auto p-6 scroll-smooth">
          <div className="max-w-7xl mx-auto space-y-6">
            {children}
          </div>
        </main>
      </div>
    </div>
  );
}
