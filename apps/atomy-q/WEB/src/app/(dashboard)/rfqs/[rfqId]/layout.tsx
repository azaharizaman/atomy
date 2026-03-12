'use client';

import React from 'react';
import { usePathname } from 'next/navigation';
import { LayoutPanelTop, FileText, FolderArchive, BarChart2, Settings } from 'lucide-react';

import { NavGroup, NavItem } from '@/components/layout/sidebar';
import { Header } from '@/components/layout/header';
import { ActiveRecordMenu, type ActiveRfqRecord } from '@/components/workspace/active-record-menu';

export default function RfqWorkspaceLayout({ children, params }: { children: React.ReactNode; params: { rfqId: string } }) {
  const pathname = usePathname();
  const [railExpanded, setRailExpanded] = React.useState(false);

  const rfqId = decodeURIComponent(params.rfqId);

  const record: ActiveRfqRecord = {
    id: rfqId,
    title: rfqId === 'RFQ-2401' ? 'Server Infrastructure Refresh' : 'Requisition Workspace',
    status: 'active',
    vendorsCount: 5,
    quotesCount: 8,
    estValue: '$1.2M',
    savings: '12%',
    primaryActionLabel: 'Close for Submissions',
  };

  return (
    <div className="flex h-screen bg-slate-100 overflow-hidden font-sans">
      {/* Collapsed rail that expands on hover */}
      <div
        className={['relative flex-shrink-0 z-20 transition-all duration-200', railExpanded ? 'w-[200px]' : 'w-12'].join(' ')}
        onMouseEnter={() => setRailExpanded(true)}
        onMouseLeave={() => setRailExpanded(false)}
      >
        <div className={['absolute inset-y-0 left-0 flex flex-col bg-white border-r border-slate-200 overflow-hidden transition-all duration-200', railExpanded ? 'w-[200px] shadow-lg' : 'w-12'].join(' ')}>
          <div className="flex items-center justify-center h-14 shrink-0 border-b border-slate-200">
            <div className="w-7 h-7 bg-indigo-600 rounded-md flex items-center justify-center shrink-0">
              <span className="text-white text-xs font-bold">AQ</span>
            </div>
            {railExpanded && <span className="ml-2.5 text-sm font-semibold text-slate-800 whitespace-nowrap overflow-hidden">Atomy-Q</span>}
          </div>

          <nav className="flex-1 overflow-y-auto py-3 px-1.5 space-y-0.5 custom-scrollbar">
            <NavItem label="Dashboard" icon={<LayoutPanelTop size={18} />} active={pathname === '/'} href="/" collapsed={!railExpanded} />

            <NavGroup label="Requisition" icon={<FileText size={18} />} active={pathname.startsWith('/rfqs')} defaultOpen={pathname.startsWith('/rfqs')} collapsed={!railExpanded}>
              {/* keep minimal children for now; workspace nav is in ActiveRecordMenu */}
            </NavGroup>

            <NavItem label="Documents" icon={<FolderArchive size={18} />} active={pathname.startsWith('/documents')} href="/documents" collapsed={!railExpanded} />
            <NavItem label="Reporting" icon={<BarChart2 size={18} />} active={pathname.startsWith('/reporting')} href="/reporting" collapsed={!railExpanded} />
            <NavGroup label="Settings" icon={<Settings size={18} />} active={pathname.startsWith('/settings')} defaultOpen={pathname.startsWith('/settings')} collapsed={!railExpanded}>
              {/* stubs */}
            </NavGroup>
          </nav>
        </div>
      </div>

      {/* Main column */}
      <div className="flex-1 flex flex-col min-w-0">
        <Header />
        <div className="flex flex-1 min-h-0">
          <ActiveRecordMenu record={record} />
          <div className="flex-1 min-w-0 overflow-y-auto">
            <div className="p-6">
              <div className="max-w-7xl mx-auto space-y-6">{children}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

