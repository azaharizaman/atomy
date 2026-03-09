import { Link } from 'react-router';
import {
  FileText, Inbox, BarChart3, TrendingUp, ShieldAlert, ArrowUpRight,
  Clock, CheckCircle, AlertTriangle, DollarSign, Package, Users, Zap
} from 'lucide-react';
import { BarChart, Bar, LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';

const kpis = [
  { label: 'Active RFQs', value: '24', delta: '+3 this week', icon: FileText, color: 'bg-indigo-50 text-indigo-600', trend: 'up' },
  { label: 'Quotes Received', value: '187', delta: '+12 today', icon: Inbox, color: 'bg-emerald-50 text-emerald-600', trend: 'up' },
  { label: 'Avg. Response Time', value: '2.4d', delta: '-0.3d vs last mo.', icon: Clock, color: 'bg-amber-50 text-amber-600', trend: 'down' },
  { label: 'Total Savings', value: '$1.2M', delta: '+8.4% vs target', icon: DollarSign, color: 'bg-purple-50 text-purple-600', trend: 'up' },
];

const spendData = [
  { month: 'Sep', value: 420 }, { month: 'Oct', value: 380 }, { month: 'Nov', value: 510 },
  { month: 'Dec', value: 340 }, { month: 'Jan', value: 490 }, { month: 'Feb', value: 620 },
  { month: 'Mar', value: 580 },
];

const vendorPerf = [
  { name: 'TechCorp', score: 92 }, { name: 'GlobalSup.', score: 84 }, { name: 'FastParts', score: 78 },
  { name: 'PrimeSrc.', score: 88 }, { name: 'NovaTech', score: 71 },
];

const recentActivity = [
  { id: 'RFQ-2401', title: 'Server Infrastructure Components', status: 'In Comparison', time: '2h ago', icon: BarChart3, color: 'text-indigo-600 bg-indigo-50' },
  { id: 'RFQ-2398', title: 'Office Supplies Q2 2026', status: 'Quotes Received', time: '4h ago', icon: Inbox, color: 'text-emerald-600 bg-emerald-50' },
  { id: 'RFQ-2395', title: 'Network Equipment Refresh', status: 'Risk Review', time: '6h ago', icon: ShieldAlert, color: 'text-amber-600 bg-amber-50' },
  { id: 'RFQ-2391', title: 'Facility Maintenance Services', status: 'Negotiating', time: '1d ago', icon: TrendingUp, color: 'text-purple-600 bg-purple-50' },
  { id: 'RFQ-2387', title: 'Cloud Software Licenses', status: 'Awarded', time: '2d ago', icon: CheckCircle, color: 'text-emerald-600 bg-emerald-50' },
];

const riskAlerts = [
  { label: 'Low Confidence Parse', count: 3, level: 'high', path: '/quote-intake' },
  { label: 'Policy Violations', count: 1, level: 'critical', path: '/risk' },
  { label: 'Missing Compliance Docs', count: 5, level: 'medium', path: '/risk' },
  { label: 'Overdue RFQ Deadlines', count: 2, level: 'high', path: '/rfq/create' },
];

const quickActions = [
  { label: 'Create New RFQ', path: '/rfq/create', icon: FileText, color: 'bg-indigo-600 hover:bg-indigo-700 text-white' },
  { label: 'Review Intake Queue', path: '/quote-intake', icon: Inbox, color: 'bg-white hover:bg-slate-50 text-slate-700 border border-slate-200' },
  { label: 'Compare Quotes', path: '/comparison', icon: BarChart3, color: 'bg-white hover:bg-slate-50 text-slate-700 border border-slate-200' },
  { label: 'View Reports', path: '/reports', icon: TrendingUp, color: 'bg-white hover:bg-slate-50 text-slate-700 border border-slate-200' },
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
            <p className="text-slate-500 text-xs mt-0.5">Average scoring this quarter</p>
          </div>
          <div className="space-y-3">
            {vendorPerf.map((v) => (
              <div key={v.name}>
                <div className="flex justify-between mb-1">
                  <span className="text-slate-700 text-xs" style={{ fontWeight: 500 }}>{v.name}</span>
                  <span className={`text-xs ${v.score >= 88 ? 'text-emerald-600' : v.score >= 78 ? 'text-amber-600' : 'text-red-500'}`} style={{ fontWeight: 600 }}>{v.score}</span>
                </div>
                <div className="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                  <div
                    className={`h-full rounded-full ${v.score >= 88 ? 'bg-emerald-500' : v.score >= 78 ? 'bg-amber-500' : 'bg-red-500'}`}
                    style={{ width: `${v.score}%` }}
                  />
                </div>
              </div>
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
            <Link to="/rfq/create" className="text-indigo-600 text-xs hover:underline" style={{ fontWeight: 500 }}>View all</Link>
          </div>
          <div className="divide-y divide-slate-50">
            {recentActivity.map((item) => {
              const Icon = item.icon;
              return (
                <div key={item.id} className="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 cursor-pointer">
                  <div className={`w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 ${item.color}`}>
                    <Icon size={14} />
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2">
                      <span className="text-slate-500 text-xs" style={{ fontWeight: 500 }}>{item.id}</span>
                      <span className="text-slate-400 text-xs">·</span>
                      <span className="text-slate-700 text-sm truncate">{item.title}</span>
                    </div>
                    <div className="text-slate-400 text-xs mt-0.5">{item.status}</div>
                  </div>
                  <span className="text-slate-400 text-xs flex-shrink-0">{item.time}</span>
                </div>
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
              {riskAlerts.map((alert) => (
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
