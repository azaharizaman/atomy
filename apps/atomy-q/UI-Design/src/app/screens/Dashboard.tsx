import { Link } from 'react-router';
import {
  FileText, Inbox, BarChart3, TrendingUp, ShieldAlert, ArrowUpRight,
  Clock, CheckCircle, AlertTriangle, DollarSign, Users, Zap, ClipboardCheck
} from 'lucide-react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { RichTooltip, TooltipBadge, StatusDot } from '../components/RichTooltip';
import {
  kpiData, rfqs, vendors, approvalItems, notifications, riskItems,
  formatCurrency, getTimeAgo, statusColors,
} from '../data/mockData';

const statusIconMap: Record<string, { icon: typeof FileText; color: string }> = {
  draft: { icon: FileText, color: 'text-slate-500 bg-slate-50' },
  open: { icon: Inbox, color: 'text-blue-600 bg-blue-50' },
  closed: { icon: CheckCircle, color: 'text-emerald-600 bg-emerald-50' },
  archived: { icon: Clock, color: 'text-slate-400 bg-slate-50' },
  reopened: { icon: ShieldAlert, color: 'text-purple-600 bg-purple-50' },
};

const pendingApprovals = approvalItems.filter(a => a.status === 'pending');

const kpis = [
  { label: 'Active RFQs', value: String(kpiData.activeRfqs), delta: `${rfqs.filter(r => r.status === 'open').length} open`, icon: FileText, color: 'bg-indigo-50 text-indigo-600', trend: 'up' as const },
  { label: 'Pending Approvals', value: String(pendingApprovals.length), delta: `${pendingApprovals.filter(a => a.priority === 'urgent').length} urgent`, icon: ClipboardCheck, color: 'bg-amber-50 text-amber-600', trend: 'up' as const },
  { label: 'Total Savings', value: formatCurrency(kpiData.totalSavings), delta: `+${kpiData.savingsTarget}% vs target`, icon: DollarSign, color: 'bg-purple-50 text-purple-600', trend: 'up' as const },
  { label: 'Avg. Cycle Time', value: `${kpiData.avgCycleTimeDays}d`, delta: `${kpiData.complianceRate}% compliance`, icon: Clock, color: 'bg-emerald-50 text-emerald-600', trend: 'down' as const },
];

const spendData = kpiData.spendTrend.map(s => ({ month: s.month, value: Math.round(s.value / 1000) }));

const vendorPerf = vendors.map(v => ({ name: v.name.length > 12 ? v.name.slice(0, 11) + '.' : v.name, fullName: v.name, score: v.overallScore, id: v.id }));

const recentActivity = rfqs.slice(0, 5).map(r => {
  const mapped = statusIconMap[r.status] ?? statusIconMap.open;
  return { id: r.id, title: r.title, status: r.status, time: getTimeAgo(r.createdAt), icon: mapped.icon, color: mapped.color, category: r.category, estimatedValue: r.estimatedValue, vendorCount: r.vendorCount, quoteCount: r.quoteCount };
});

const riskCounts = {
  critical: riskItems.filter(r => r.level === 'critical').length,
  high: riskItems.filter(r => r.level === 'high').length,
  medium: riskItems.filter(r => r.level === 'medium').length,
};
const riskAlerts = [
  { label: 'Critical Risks', count: riskCounts.critical, level: 'critical' as const, path: '/risk' },
  { label: 'High Risks', count: riskCounts.high, level: 'high' as const, path: '/risk' },
  { label: 'Medium Risks', count: riskCounts.medium, level: 'medium' as const, path: '/risk' },
  { label: 'Unread Notifications', count: notifications.filter(n => !n.read).length, level: 'high' as const, path: '/notifications' },
];

const quickActions = [
  { label: 'Create New RFQ', path: '/rfq/create', icon: FileText, color: 'bg-indigo-600 hover:bg-indigo-700 text-white' },
  { label: 'Review Intake Queue', path: '/quote-intake', icon: Inbox, color: 'bg-white hover:bg-slate-50 text-slate-700 border border-slate-200' },
  { label: 'Compare Quotes', path: '/comparison', icon: BarChart3, color: 'bg-white hover:bg-slate-50 text-slate-700 border border-slate-200' },
  { label: 'View Reports', path: '/reports', icon: TrendingUp, color: 'bg-white hover:bg-slate-50 text-slate-700 border border-slate-200' },
  { label: 'Pending Approvals', path: '/approvals', icon: ClipboardCheck, color: 'bg-white hover:bg-slate-50 text-slate-700 border border-slate-200' },
];

export function Dashboard() {
  return (
    <div className="p-6 space-y-6">
      {/* Header */}
      <div className="flex items-start justify-between">
        <div>
          <h1 className="text-slate-900">Good morning, Alex 👋</h1>
          <p className="text-slate-500 text-sm mt-0.5">Here's what's happening with your procurement pipeline today.</p>
        </div>
        <Link
          to="/rfq/create"
          className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm transition-colors"
          style={{ fontWeight: 500 }}
        >
          <FileText size={15} />
          New RFQ
        </Link>
      </div>

      {/* KPIs */}
      <div className="grid grid-cols-4 gap-4">
        {kpis.map((kpi) => {
          const Icon = kpi.icon;
          return (
            <div key={kpi.label} className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
              <div className="flex items-start justify-between mb-3">
                <div className={`w-9 h-9 rounded-lg flex items-center justify-center ${kpi.color}`}>
                  <Icon size={16} />
                </div>
                <span className={`flex items-center gap-1 text-xs ${kpi.trend === 'up' ? 'text-emerald-600' : 'text-amber-600'}`}>
                  <ArrowUpRight size={12} className={kpi.trend === 'down' ? 'rotate-180' : ''} />
                </span>
              </div>
              <div className="text-2xl text-slate-900" style={{ fontWeight: 700 }}>{kpi.value}</div>
              <div className="text-slate-500 text-xs mt-0.5" style={{ fontWeight: 500 }}>{kpi.label}</div>
              <div className="text-slate-400 text-xs mt-1">{kpi.delta}</div>
            </div>
          );
        })}
      </div>

      {/* Charts Row */}
      <div className="grid grid-cols-3 gap-4">
        {/* Spend Trend */}
        <div className="col-span-2 bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
          <div className="flex items-center justify-between mb-4">
            <div>
              <h3 className="text-slate-900">Spend Trend</h3>
              <p className="text-slate-500 text-xs mt-0.5">Monthly procurement spend ($ thousands)</p>
            </div>
            <select className="text-xs border border-slate-200 rounded-lg px-2 py-1.5 text-slate-600 bg-white">
              <option>Last 7 months</option>
            </select>
          </div>
          <ResponsiveContainer width="100%" height={180}>
            <BarChart data={spendData} barSize={28}>
              <CartesianGrid strokeDasharray="3 3" stroke="#F1F5F9" vertical={false} />
              <XAxis dataKey="month" tick={{ fontSize: 11, fill: '#94A3B8' }} axisLine={false} tickLine={false} />
              <YAxis tick={{ fontSize: 11, fill: '#94A3B8' }} axisLine={false} tickLine={false} />
              <Tooltip contentStyle={{ fontSize: 12, borderRadius: 8, border: '1px solid #E2E8F0', boxShadow: '0 4px 6px -1px rgba(0,0,0,0.1)' }} />
              <Bar dataKey="value" fill="#6366F1" radius={[4, 4, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </div>

        {/* Vendor Performance */}
        <div className="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
          <div className="mb-4">
            <h3 className="text-slate-900">Vendor Scores</h3>
            <p className="text-slate-500 text-xs mt-0.5">Overall scoring this quarter</p>
          </div>
          <div className="space-y-3">
            {vendorPerf.map((v) => (
              <Link key={v.id} to={`/vendor/${v.id}`} className="block group">
                <div className="flex justify-between mb-1">
                  <span className="text-slate-700 text-xs group-hover:text-indigo-600 transition-colors" style={{ fontWeight: 500 }}>{v.name}</span>
                  <span className={`text-xs ${v.score >= 88 ? 'text-emerald-600' : v.score >= 78 ? 'text-amber-600' : 'text-red-500'}`} style={{ fontWeight: 600 }}>{v.score}</span>
                </div>
                <div className="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                  <div
                    className={`h-full rounded-full ${v.score >= 88 ? 'bg-emerald-500' : v.score >= 78 ? 'bg-amber-500' : 'bg-red-500'}`}
                    style={{ width: `${v.score}%` }}
                  />
                </div>
              </Link>
            ))}
          </div>
        </div>
      </div>

      {/* Bottom Row */}
      <div className="grid grid-cols-3 gap-4">
        {/* Recent Activity */}
        <div className="col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm">
          <div className="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <h3 className="text-slate-900">Recent Activity</h3>
            <Link to="/rfqs" className="text-indigo-600 text-xs hover:underline" style={{ fontWeight: 500 }}>View all</Link>
          </div>
          <div className="divide-y divide-slate-50">
            {recentActivity.map((item) => {
              const Icon = item.icon;
              return (
                <RichTooltip
                  key={item.id}
                  side="left"
                  align="center"
                  trigger={
                    <Link to={`/rfq/${item.id}`} className="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 cursor-pointer">
                      <div className={`w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 ${item.color}`}>
                        <Icon size={14} />
                      </div>
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2">
                          <span className="text-slate-500 text-xs" style={{ fontWeight: 500 }}>{item.id}</span>
                          <span className="text-slate-400 text-xs">·</span>
                          <span className="text-slate-700 text-sm truncate">{item.title}</span>
                          <span className={`inline-flex items-center px-1.5 py-0.5 rounded text-xs capitalize ${statusColors[item.status] ?? 'bg-slate-100 text-slate-600'}`} style={{ fontWeight: 500, fontSize: 10 }}>
                            {item.status}
                          </span>
                        </div>
                        <div className="text-slate-400 text-xs mt-0.5">{item.category}</div>
                      </div>
                      <span className="text-slate-400 text-xs flex-shrink-0">{item.time}</span>
                    </Link>
                  }
                >
                  <div className="space-y-2">
                    <div className="text-sm text-slate-900" style={{ fontWeight: 600 }}>{item.title}</div>
                    <StatusDot status={item.status === 'closed' ? 'good' : item.status === 'open' ? 'warning' : 'neutral'} label={item.status} />
                    <div className="border-t border-slate-100 pt-2 space-y-0.5">
                      <TooltipBadge label="Category" value={item.category} />
                      <TooltipBadge label="Est. Value" value={formatCurrency(item.estimatedValue)} color="text-indigo-600" />
                      <TooltipBadge label="Vendors" value={item.vendorCount} />
                      <TooltipBadge label="Quotes" value={item.quoteCount} />
                    </div>
                  </div>
                </RichTooltip>
              );
            })}
          </div>
        </div>

        {/* Alerts + Quick Actions */}
        <div className="space-y-4">
          {/* Risk Alerts */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div className="flex items-center justify-between px-4 py-3 border-b border-slate-100">
              <h3 className="text-slate-900 text-sm">Attention Required</h3>
              <AlertTriangle size={14} className="text-amber-500" />
            </div>
            <div className="p-3 space-y-2">
              {riskAlerts.filter(a => a.count > 0).map((alert) => (
                <Link
                  key={alert.label}
                  to={alert.path}
                  className={`flex items-center justify-between px-3 py-2 rounded-lg border cursor-pointer hover:opacity-80 ${
                    alert.level === 'critical' ? 'bg-red-50 border-red-200' :
                    alert.level === 'high' ? 'bg-amber-50 border-amber-200' :
                    'bg-slate-50 border-slate-200'
                  }`}
                >
                  <span className={`text-xs ${alert.level === 'critical' ? 'text-red-700' : alert.level === 'high' ? 'text-amber-700' : 'text-slate-600'}`} style={{ fontWeight: 500 }}>
                    {alert.label}
                  </span>
                  <span className={`text-xs px-1.5 py-0.5 rounded-full ${
                    alert.level === 'critical' ? 'bg-red-100 text-red-700' :
                    alert.level === 'high' ? 'bg-amber-100 text-amber-700' :
                    'bg-slate-100 text-slate-600'
                  }`} style={{ fontWeight: 600 }}>
                    {alert.count}
                  </span>
                </Link>
              ))}
            </div>
          </div>

          {/* Quick Actions */}
          <div className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
            <h3 className="text-slate-900 text-sm mb-3">Quick Actions</h3>
            <div className="space-y-2">
              {quickActions.map((action) => {
                const Icon = action.icon;
                return (
                  <Link
                    key={action.label}
                    to={action.path}
                    className={`flex items-center gap-2 w-full px-3 py-2 rounded-lg text-sm transition-colors ${action.color}`}
                    style={{ fontWeight: 500 }}
                  >
                    <Icon size={14} />
                    {action.label}
                  </Link>
                );
              })}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
