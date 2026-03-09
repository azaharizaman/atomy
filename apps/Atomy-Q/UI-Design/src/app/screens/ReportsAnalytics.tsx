import { useState } from 'react';
import {
  BarChart3, Download, Plus, Calendar, RefreshCw, Filter, X,
  TrendingUp, TrendingDown, DollarSign, Package, Clock, CheckCircle,
  FileText, Settings
} from 'lucide-react';
import {
  BarChart, Bar, LineChart, Line, PieChart, Pie, Cell, XAxis, YAxis,
  CartesianGrid, Tooltip, ResponsiveContainer, Legend
} from 'recharts';

const kpis = [
  { label: 'Total Spend YTD', value: '$4.2M', delta: '-8.3% vs last year', trend: 'down', good: true, icon: DollarSign, color: 'bg-indigo-50 text-indigo-600' },
  { label: 'RFQs Issued', value: '148', delta: '+22 vs last year', trend: 'up', good: true, icon: Package, color: 'bg-emerald-50 text-emerald-600' },
  { label: 'Avg. Evaluation Time', value: '4.2 days', delta: '-1.1 days improved', trend: 'down', good: true, icon: Clock, color: 'bg-amber-50 text-amber-600' },
  { label: 'Compliance Rate', value: '96.4%', delta: '+2.1% vs target', trend: 'up', good: true, icon: CheckCircle, color: 'bg-purple-50 text-purple-600' },
];

const spendByCategory = [
  { category: 'IT Infrastructure', amount: 1840000, q: 'Q1' },
  { category: 'Software', amount: 720000, q: 'Q2' },
  { category: 'Facilities', amount: 480000, q: 'Q3' },
  { category: 'Services', amount: 620000, q: 'Q4' },
  { category: 'Office Supplies', amount: 180000, q: '' },
  { category: 'Other', amount: 360000, q: '' },
];

const monthlySpend = [
  { month: 'Oct', actual: 380, budget: 420 },
  { month: 'Nov', actual: 510, budget: 450 },
  { month: 'Dec', actual: 290, budget: 380 },
  { month: 'Jan', actual: 490, budget: 480 },
  { month: 'Feb', actual: 620, budget: 520 },
  { month: 'Mar', actual: 540, budget: 500 },
];

const vendorSavings = [
  { vendor: 'PrimeSource Co.', savings: 42000, rfqs: 8 },
  { vendor: 'TechCorp Solutions', savings: 31000, rfqs: 12 },
  { vendor: 'FastParts Ltd.', savings: 18500, rfqs: 6 },
  { vendor: 'GlobalSupply Inc.', savings: 22000, rfqs: 9 },
];

const categoryPie = [
  { name: 'IT Infrastructure', value: 44, color: '#6366F1' },
  { name: 'Software', value: 17, color: '#10B981' },
  { name: 'Services', value: 15, color: '#F59E0B' },
  { name: 'Facilities', value: 12, color: '#8B5CF6' },
  { name: 'Other', value: 12, color: '#94A3B8' },
];

const reports = [
  { id: 'rpt1', name: 'Q1 2026 Spend Analysis', type: 'Spend', generated: 'Mar 1, 2026', size: '2.4 MB', format: 'PDF' },
  { id: 'rpt2', name: 'Vendor Performance Review — FY2025', type: 'Vendor', generated: 'Feb 15, 2026', size: '1.8 MB', format: 'XLSX' },
  { id: 'rpt3', name: 'Compliance Audit Trail — Feb 2026', type: 'Compliance', generated: 'Mar 2, 2026', size: '0.9 MB', format: 'PDF' },
  { id: 'rpt4', name: 'RFQ Cycle Time Analysis', type: 'Process', generated: 'Feb 28, 2026', size: '1.2 MB', format: 'PDF' },
  { id: 'rpt5', name: 'Savings Achievement Report', type: 'Savings', generated: 'Mar 4, 2026', size: '3.1 MB', format: 'PDF' },
];

const schedules = [
  { id: 'sc1', name: 'Monthly Spend Report', frequency: 'Monthly · 1st of each month', nextRun: 'Apr 1, 2026', recipients: 3, active: true },
  { id: 'sc2', name: 'Weekly Vendor KPI Digest', frequency: 'Weekly · Every Monday', nextRun: 'Mar 9, 2026', recipients: 5, active: true },
  { id: 'sc3', name: 'Quarterly Compliance Report', frequency: 'Quarterly · End of quarter', nextRun: 'Jun 30, 2026', recipients: 2, active: false },
];

type ViewState = 'dashboard' | 'reports' | 'schedule';

export function ReportsAnalytics() {
  const [view, setView] = useState<ViewState>('dashboard');
  const [showExport, setShowExport] = useState(false);

  return (
    <div className="flex flex-col h-full bg-slate-50">
      {/* Header */}
      <div className="bg-white border-b border-slate-200 px-6 py-4 flex-shrink-0">
        <div className="flex items-center justify-between mb-4">
          <div>
            <h1 className="text-slate-900">Reports & Analytics</h1>
            <p className="text-slate-500 text-xs mt-0.5">YTD through March 8, 2026</p>
          </div>
          <div className="flex items-center gap-2">
            <button className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
              <Calendar size={14} />
              FY2026 YTD
              <ChevronDownSmall />
            </button>
            <button className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
              <RefreshCw size={14} />
              Refresh
            </button>
            <button onClick={() => setShowExport(true)} className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-3 py-2 text-sm" style={{ fontWeight: 500 }}>
              <Download size={14} />
              Export
            </button>
          </div>
        </div>
        {/* View Tabs */}
        <div className="flex gap-1 border border-slate-200 rounded-lg p-1 w-fit bg-slate-50">
          {([
            { id: 'dashboard', label: 'Dashboard', icon: BarChart3 },
            { id: 'reports', label: 'Reports', icon: FileText },
            { id: 'schedule', label: 'Scheduled', icon: Calendar },
          ] as const).map(tab => {
            const Icon = tab.icon;
            return (
              <button
                key={tab.id}
                onClick={() => setView(tab.id)}
                className={`flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm transition-colors ${view === tab.id ? 'bg-white shadow-sm text-slate-800' : 'text-slate-500 hover:text-slate-700'}`}
                style={{ fontWeight: view === tab.id ? 600 : 400 }}
              >
                <Icon size={14} />
                {tab.label}
              </button>
            );
          })}
        </div>
      </div>

      <div className="flex-1 overflow-auto p-6 space-y-5">
        {/* Dashboard View */}
        {view === 'dashboard' && (
          <>
            {/* KPIs */}
            <div className="grid grid-cols-4 gap-4">
              {kpis.map(kpi => {
                const Icon = kpi.icon;
                return (
                  <div key={kpi.label} className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                    <div className={`w-9 h-9 rounded-lg flex items-center justify-center mb-3 ${kpi.color}`}>
                      <Icon size={16} />
                    </div>
                    <div className="text-2xl text-slate-900" style={{ fontWeight: 700 }}>{kpi.value}</div>
                    <div className="text-slate-500 text-xs mt-0.5" style={{ fontWeight: 500 }}>{kpi.label}</div>
                    <div className={`flex items-center gap-1 mt-1 text-xs ${kpi.good ? 'text-emerald-600' : 'text-red-500'}`} style={{ fontWeight: 500 }}>
                      {kpi.good ? <TrendingDown size={11} /> : <TrendingUp size={11} />}
                      {kpi.delta}
                    </div>
                  </div>
                );
              })}
            </div>

            {/* Charts Row */}
            <div className="grid grid-cols-3 gap-4">
              {/* Spend vs Budget */}
              <div className="col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <div className="flex items-center justify-between mb-4">
                  <h3 className="text-slate-900">Actual vs Budget ($ thousands)</h3>
                  <button className="text-slate-400 hover:text-slate-600"><Settings size={14} /></button>
                </div>
                <ResponsiveContainer width="100%" height={220}>
                  <BarChart data={monthlySpend} barGap={4}>
                    <CartesianGrid strokeDasharray="3 3" stroke="#F1F5F9" vertical={false} />
                    <XAxis dataKey="month" tick={{ fontSize: 11, fill: '#94A3B8' }} axisLine={false} tickLine={false} />
                    <YAxis tick={{ fontSize: 11, fill: '#94A3B8' }} axisLine={false} tickLine={false} />
                    <Tooltip contentStyle={{ fontSize: 12, borderRadius: 8, border: '1px solid #E2E8F0' }} />
                    <Legend iconType="circle" iconSize={8} wrapperStyle={{ fontSize: 11 }} />
                    <Bar dataKey="actual" name="Actual" fill="#6366F1" radius={[3, 3, 0, 0]} maxBarSize={36} />
                    <Bar dataKey="budget" name="Budget" fill="#E2E8F0" radius={[3, 3, 0, 0]} maxBarSize={36} />
                  </BarChart>
                </ResponsiveContainer>
              </div>

              {/* Category Breakdown */}
              <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <div className="flex items-center justify-between mb-4">
                  <h3 className="text-slate-900">Spend by Category</h3>
                </div>
                <ResponsiveContainer width="100%" height={160}>
                  <PieChart>
                    <Pie data={categoryPie} cx="50%" cy="50%" innerRadius={40} outerRadius={70} paddingAngle={2} dataKey="value">
                      {categoryPie.map((entry, i) => <Cell key={i} fill={entry.color} />)}
                    </Pie>
                    <Tooltip formatter={(v: number) => [`${v}%`, '']} contentStyle={{ fontSize: 11, borderRadius: 6 }} />
                  </PieChart>
                </ResponsiveContainer>
                <div className="space-y-1.5 mt-2">
                  {categoryPie.slice(0, 4).map(c => (
                    <div key={c.name} className="flex items-center gap-2">
                      <div className="w-2.5 h-2.5 rounded-full flex-shrink-0" style={{ background: c.color }} />
                      <span className="text-slate-600 text-xs flex-1">{c.name}</span>
                      <span className="text-slate-700 text-xs" style={{ fontWeight: 600 }}>{c.value}%</span>
                    </div>
                  ))}
                </div>
              </div>
            </div>

            {/* Vendor Savings */}
            <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-slate-900">Top Savings by Vendor</h3>
                <span className="text-slate-500 text-xs">YTD</span>
              </div>
              <div className="grid grid-cols-4 gap-4">
                {vendorSavings.map(v => (
                  <div key={v.vendor} className="bg-slate-50 rounded-xl border border-slate-200 p-4">
                    <div className="text-slate-700 text-xs mb-1" style={{ fontWeight: 600 }}>{v.vendor}</div>
                    <div className="text-emerald-700 text-lg" style={{ fontWeight: 700 }}>${v.savings.toLocaleString()}</div>
                    <div className="text-slate-400 text-xs mt-0.5">{v.rfqs} RFQs</div>
                  </div>
                ))}
              </div>
            </div>
          </>
        )}

        {/* Reports List View */}
        {view === 'reports' && (
          <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="flex items-center justify-between px-5 py-4 border-b border-slate-100">
              <h3 className="text-slate-900">Generated Reports</h3>
              <button className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-3 py-2 text-sm" style={{ fontWeight: 500 }}>
                <Plus size={14} />
                New Report
              </button>
            </div>
            <table className="w-full">
              <thead>
                <tr className="border-b border-slate-100 bg-slate-50">
                  {['Report Name', 'Type', 'Generated', 'Size', 'Format', ''].map(h => (
                    <th key={h} className="text-left px-5 py-3 text-xs text-slate-500" style={{ fontWeight: 600 }}>{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {reports.map(r => (
                  <tr key={r.id} className="border-b border-slate-50 hover:bg-slate-50 cursor-pointer">
                    <td className="px-5 py-3">
                      <div className="flex items-center gap-2">
                        <FileText size={14} className="text-slate-400" />
                        <span className="text-slate-700 text-sm" style={{ fontWeight: 500 }}>{r.name}</span>
                      </div>
                    </td>
                    <td className="px-5 py-3">
                      <span className="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded">{r.type}</span>
                    </td>
                    <td className="px-5 py-3 text-slate-500 text-sm">{r.generated}</td>
                    <td className="px-5 py-3 text-slate-500 text-sm">{r.size}</td>
                    <td className="px-5 py-3">
                      <span className={`text-xs px-2 py-0.5 rounded ${r.format === 'PDF' ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'}`} style={{ fontWeight: 600 }}>{r.format}</span>
                    </td>
                    <td className="px-5 py-3">
                      <button className="flex items-center gap-1 text-indigo-600 text-xs hover:text-indigo-700" style={{ fontWeight: 500 }}>
                        <Download size={12} />
                        Download
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        {/* Schedule View */}
        {view === 'schedule' && (
          <div className="space-y-4">
            <div className="flex justify-between items-center">
              <h3 className="text-slate-700">Scheduled Reports</h3>
              <button className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-3 py-2 text-sm" style={{ fontWeight: 500 }}>
                <Plus size={14} />
                New Schedule
              </button>
            </div>
            {schedules.map(s => (
              <div key={s.id} className="bg-white rounded-xl border border-slate-200 shadow-sm p-4 flex items-center gap-4">
                <div className={`w-9 h-9 rounded-xl flex items-center justify-center ${s.active ? 'bg-indigo-50' : 'bg-slate-100'}`}>
                  <Calendar size={16} className={s.active ? 'text-indigo-600' : 'text-slate-400'} />
                </div>
                <div className="flex-1">
                  <div className="flex items-center gap-2">
                    <span className="text-slate-800 text-sm" style={{ fontWeight: 600 }}>{s.name}</span>
                    <span className={`text-xs px-2 py-0.5 rounded-full ${s.active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'}`} style={{ fontWeight: 500 }}>{s.active ? 'Active' : 'Paused'}</span>
                  </div>
                  <div className="text-slate-500 text-xs mt-0.5">{s.frequency} · {s.recipients} recipients</div>
                </div>
                <div className="text-right">
                  <div className="text-slate-600 text-xs" style={{ fontWeight: 500 }}>Next run</div>
                  <div className="text-slate-800 text-sm">{s.nextRun}</div>
                </div>
                <div className="flex items-center gap-2">
                  <button className={`w-10 h-5 rounded-full transition-colors relative ${s.active ? 'bg-indigo-600' : 'bg-slate-200'}`}>
                    <span className={`absolute top-0.5 w-4 h-4 bg-white rounded-full shadow ${s.active ? 'left-5.5' : 'left-0.5'}`} />
                  </button>
                  <Settings size={14} className="text-slate-400 hover:text-slate-600 cursor-pointer" />
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Export Modal */}
      {showExport && (
        <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div className="flex items-center justify-between px-6 py-4 border-b border-slate-100">
              <h2 className="text-slate-900 text-sm">Export Report</h2>
              <button onClick={() => setShowExport(false)} className="text-slate-400 hover:text-slate-600"><X size={18} /></button>
            </div>
            <div className="p-6 space-y-4">
              <div>
                <label className="text-slate-600 text-xs block mb-2" style={{ fontWeight: 500 }}>Report Type</label>
                <div className="grid grid-cols-2 gap-2">
                  {['Full Dashboard', 'Spend Analysis', 'Vendor Performance', 'Compliance Summary'].map(t => (
                    <button key={t} className="border border-slate-200 rounded-lg px-3 py-2.5 text-xs text-slate-700 hover:border-indigo-300 hover:bg-indigo-50/20 text-left" style={{ fontWeight: 500 }}>{t}</button>
                  ))}
                </div>
              </div>
              <div>
                <label className="text-slate-600 text-xs block mb-2" style={{ fontWeight: 500 }}>Format</label>
                <div className="flex gap-2">
                  {['PDF', 'XLSX', 'CSV'].map(f => (
                    <button key={f} className={`flex-1 py-2 rounded-lg border text-xs text-center ${f === 'PDF' ? 'border-indigo-300 bg-indigo-50 text-indigo-700' : 'border-slate-200 text-slate-600 hover:border-slate-300'}`} style={{ fontWeight: f === 'PDF' ? 600 : 400 }}>{f}</button>
                  ))}
                </div>
              </div>
              <div>
                <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Date Range</label>
                <select className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white">
                  <option>FY2026 YTD</option>
                  <option>Last 30 days</option>
                  <option>Last quarter</option>
                  <option>Custom range</option>
                </select>
              </div>
            </div>
            <div className="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100">
              <button onClick={() => setShowExport(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
              <button onClick={() => setShowExport(false)} className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>
                <Download size={14} />
                Export Now
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

function ChevronDownSmall() {
  return <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M6 9l6 6 6-6" /></svg>;
}
