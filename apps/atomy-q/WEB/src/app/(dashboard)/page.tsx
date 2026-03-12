'use client';

import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { TrendingUp, AlertCircle, Clock, CheckCircle2 } from 'lucide-react';
import { api } from '@/lib/api';

interface KPICardProps {
  title: string;
  value: string | number | undefined;
  subtext?: string;
  icon: React.ElementType;
}

function KPICard({ title, value, subtext, icon: Icon }: KPICardProps) {
  return (
    <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between h-32">
      <div className="flex justify-between items-start">
        <span className="text-slate-500 text-sm font-medium">{title}</span>
        <div className="p-2 bg-indigo-50 rounded-lg text-indigo-600">
          <Icon size={18} />
        </div>
      </div>
      <div>
        <div className="text-2xl font-bold text-slate-900">{value}</div>
        {subtext && <div className="text-xs text-slate-500 mt-1">{subtext}</div>}
      </div>
    </div>
  );
}

interface ActivityItem {
  id: number;
  type: string;
  title: string;
  user?: string;
  vendor?: string;
  time: string;
}

export default function DashboardPage() {
  const useMocks = process.env.NEXT_PUBLIC_USE_MOCKS === 'true';

  const { data: kpis, isLoading: isLoadingKPIs } = useQuery({
    queryKey: ['dashboard', 'kpis'],
    queryFn: async () => {
      if (useMocks) {
        return {
          active_rfqs: 12,
          pending_approvals: 5,
          total_savings: '$1.2M',
          avg_cycle_time_days: '14 days',
        };
      }

      return (await api.get('/dashboard/kpis')).data;
    }
  });

  const { data: activity, isLoading: isLoadingActivity } = useQuery({
    queryKey: ['dashboard', 'activity'],
    queryFn: async () => {
      if (useMocks) {
        return [
          { id: 1, type: 'rfq_created', title: 'IT Hardware Refresh 2026', user: 'John Doe', time: '2h ago' },
          { id: 2, type: 'approval_request', title: 'Office Supplies Q2', user: 'Jane Smith', time: '4h ago' },
          { id: 3, type: 'quote_received', title: 'Server Maintenance', vendor: 'TechCorp', time: '5h ago' },
        ] as ActivityItem[];
      }

      const { data } = await api.get('/dashboard/recent-activity');
      return Array.isArray(data) ? data : (data?.data ?? []);
    }
  });

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold text-slate-900">Dashboard</h1>
        <div className="text-sm text-slate-500">Last updated: Just now</div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <KPICard
          title="Active RFQs"
          value={isLoadingKPIs ? '...' : kpis?.active_rfqs}
          subtext="+2 from last week"
          icon={TrendingUp}
        />
        <KPICard
          title="Pending Approvals"
          value={isLoadingKPIs ? '...' : kpis?.pending_approvals}
          subtext="Requires attention"
          icon={AlertCircle}
        />
        <KPICard
          title="YTD Savings"
          value={isLoadingKPIs ? '...' : kpis?.total_savings}
          subtext="12% above target"
          icon={CheckCircle2}
        />
        <KPICard
          title="Avg Cycle Time"
          value={isLoadingKPIs ? '...' : kpis?.avg_cycle_time_days}
          subtext="-2 days improvement"
          icon={Clock}
        />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm p-6">
          <h2 className="text-lg font-bold text-slate-900 mb-4">Spend Trend</h2>
          <div className="h-64 bg-slate-50 rounded-lg flex items-center justify-center text-slate-400">
            Chart Placeholder (Recharts)
          </div>
        </div>

        <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
          <h2 className="text-lg font-bold text-slate-900 mb-4">Recent Activity</h2>
          <div className="space-y-4">
            {isLoadingActivity ? (
              <div className="text-center py-4 text-slate-500">Loading...</div>
            ) : (
              activity?.map((item: ActivityItem) => (
                <div key={item.id} className="flex gap-3 items-start pb-4 border-b border-slate-100 last:border-0 last:pb-0">
                  <div className="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center shrink-0">
                    <span className="text-xs font-bold text-slate-600">
                      {(item.user || item.vendor || 'S').charAt(0)}
                    </span>
                  </div>
                  <div>
                    <p className="text-sm font-medium text-slate-900">{item.title}</p>
                    <p className="text-xs text-slate-500">{item.type.replace('_', ' ')} • {item.time}</p>
                  </div>
                </div>
              ))
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
