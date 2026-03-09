import { useState } from 'react';
import {
  BarChart3, Download, Plus, Calendar, RefreshCw, X,
  TrendingUp, TrendingDown, DollarSign, Clock, CheckCircle,
  FileText, Settings, Trash2, Mail, AlertTriangle
} from 'lucide-react';
import {
  BarChart, Bar, PieChart, Pie, Cell, XAxis, YAxis,
  CartesianGrid, Tooltip, ResponsiveContainer, Legend,
  AreaChart, Area,
} from 'recharts';
import { SlideOver } from '../components/SlideOver';
import {
  kpiData, reportSchedules, savedReportRuns, formatCurrency, formatDateTime, getUserById,
} from '../data/mockData';

const kpiTiles = [
  { label: 'Active RFQs', value: String(kpiData.activeRfqs), icon: FileText, color: 'bg-indigo-50 text-indigo-600', delta: '+3 this week', good: true },
  { label: 'Total Savings', value: formatCurrency(kpiData.totalSavings), icon: DollarSign, color: 'bg-emerald-50 text-emerald-600', delta: `${kpiData.savingsTarget}% of target`, good: true },
  { label: 'Avg Cycle Time', value: `${kpiData.avgCycleTimeDays} days`, icon: Clock, color: 'bg-amber-50 text-amber-600', delta: '-2.1 days improved', good: true },
  { label: 'Compliance Rate', value: `${kpiData.complianceRate}%`, icon: CheckCircle, color: 'bg-purple-50 text-purple-600', delta: '+1.2% vs target', good: true },
];

const categoryColors = ['#6366F1', '#10B981', '#F59E0B', '#8B5CF6', '#3B82F6', '#94A3B8'];

type ViewState = 'dashboard' | 'schedules' | 'runs';

export function ReportsAnalytics() {
  const [view, setView] = useState<ViewState>('dashboard');
  const [showExport, setShowExport] = useState(false);
  const [showSchedule, setShowSchedule] = useState(false);
  const [deleteScheduleId, setDeleteScheduleId] = useState<string | null>(null);

  const deleteSchedule = reportSchedules.find(s => s.id === deleteScheduleId);

  return (
    <div className="flex flex-col h-full bg-slate-50">
      {/* Header */}
      <div className="bg-white border-b border-slate-200 px-6 py-4 flex-shrink-0">
        <div className="flex items-center justify-between mb-4">
          <div>
            <h1 className="text-slate-900">Reports & Analytics</h1>
            <p className="text-slate-500 text-xs mt-0.5">YTD through March 9, 2026</p>
          </div>
          <div className="flex items-center gap-2">
            <button className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
              <RefreshCw size={14} /> Refresh
            </button>
            <button onClick={() => setShowExport(true)} className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-3 py-2 text-sm" style={{ fontWeight: 500 }}>
              <Download size={14} /> Export
            </button>
          </div>
        </div>
        <div className="flex gap-1 border border-slate-200 rounded-lg p-1 w-fit bg-slate-50">
          {([
            { id: 'dashboard', label: 'Dashboard', icon: BarChart3 },
            { id: 'schedules', label: 'Schedules', icon: Calendar },
            { id: 'runs', label: 'Report Runs', icon: FileText },
          ] as const).map(tab => {
            const Icon = tab.icon;
            return (
              <button
                key={tab.id}
                onClick={() => setView(tab.id)}
                className={`flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm transition-colors ${view === tab.id ? 'bg-white shadow-sm text-slate-800' : 'text-slate-500 hover:text-slate-700'}`}
                style={{ fontWeight: view === tab.id ? 600 : 400 }}
              >
                <Icon size={14} /> {tab.label}
              </button>
            );
          })}
        </div>
      </div>

      <div className="flex-1 overflow-auto p-6 space-y-5">
        {/* Dashboard View */}
        {view === 'dashboard' && (
          <>
            {/* KPI Tiles */}
            <div className="grid grid-cols-4 gap-4">
              {kpiTiles.map(kpi => {
                const Icon = kpi.icon;
                return (
                  <div key={kpi.label} className="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
                    <div className={`w-9 h-9 rounded-lg flex items-center justify-center mb-3 ${kpi.color}`}>
                      <Icon size={16} />
                    </div>
                    <div className="text-2xl text-slate-900" style={{ fontWeight: 700 }}>{kpi.value}</div>
                    <div className="text-slate-500 text-xs mt-0.5" style={{ fontWeight: 500 }}>{kpi.label}</div>
                    <div className={`flex items-center gap-1 mt-1 text-xs ${kpi.good ? 'text-emerald-600' : 'text-red-500'}`} style={{ fontWeight: 500 }}>
                      {kpi.good ? <TrendingUp size={11} /> : <TrendingDown size={11} />}
                      {kpi.delta}
                    </div>
                  </div>
                );
              })}
            </div>

            {/* Charts Row */}
            <div className="grid grid-cols-3 gap-4">
              {/* Spend Trend */}
              <div className="col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <div className="flex items-center justify-between mb-4">
                  <h3 className="text-slate-900">Spend Trend</h3>
                  <span className="text-slate-400 text-xs">7 months</span>
                </div>
                <ResponsiveContainer width="100%" height={220}>
                  <AreaChart data={kpiData.spendTrend}>
                    <defs>
                      <linearGradient id="spendGradient" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="5%" stopColor="#6366F1" stopOpacity={0.15} />
                        <stop offset="95%" stopColor="#6366F1" stopOpacity={0} />
                      </linearGradient>
                    </defs>
                    <CartesianGrid strokeDasharray="3 3" stroke="#F1F5F9" vertical={false} />
                    <XAxis dataKey="month" tick={{ fontSize: 11, fill: '#94A3B8' }} axisLine={false} tickLine={false} />
                    <YAxis tickFormatter={v => `$${(v / 1000).toFixed(0)}k`} tick={{ fontSize: 11, fill: '#94A3B8' }} axisLine={false} tickLine={false} />
                    <Tooltip formatter={(v: number) => [formatCurrency(v), 'Spend']} contentStyle={{ fontSize: 12, borderRadius: 8, border: '1px solid #E2E8F0' }} />
                    <Area type="monotone" dataKey="value" stroke="#6366F1" strokeWidth={2} fill="url(#spendGradient)" />
                  </AreaChart>
                </ResponsiveContainer>
              </div>

              {/* Spend by Category */}
              <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <h3 className="text-slate-900 mb-4">Spend by Category</h3>
                <ResponsiveContainer width="100%" height={160}>
                  <PieChart>
                    <Pie data={kpiData.spendByCategory} cx="50%" cy="50%" innerRadius={40} outerRadius={70} paddingAngle={2} dataKey="percentage" nameKey="category">
                      {kpiData.spendByCategory.map((_, i) => <Cell key={i} fill={categoryColors[i % categoryColors.length]} />)}
                    </Pie>
                    <Tooltip formatter={(v: number) => [`${v}%`, '']} contentStyle={{ fontSize: 11, borderRadius: 6 }} />
                  </PieChart>
                </ResponsiveContainer>
                <div className="space-y-1.5 mt-2">
                  {kpiData.spendByCategory.slice(0, 5).map((c, i) => (
                    <div key={c.category} className="flex items-center gap-2">
                      <div className="w-2.5 h-2.5 rounded-full flex-shrink-0" style={{ background: categoryColors[i] }} />
                      <span className="text-slate-600 text-xs flex-1">{c.category}</span>
                      <span className="text-slate-700 text-xs" style={{ fontWeight: 600 }}>{c.percentage}%</span>
                    </div>
                  ))}
                </div>
              </div>
            </div>

            {/* Pending Approvals + Risk */}
            <div className="grid grid-cols-3 gap-4">
              <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <div className="text-slate-900 text-sm mb-1" style={{ fontWeight: 600 }}>Pending Approvals</div>
                <div className="text-3xl text-amber-600" style={{ fontWeight: 700 }}>{kpiData.pendingApprovals}</div>
                <div className="text-slate-400 text-xs mt-1">Awaiting reviewer action</div>
              </div>
              <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <div className="text-slate-900 text-sm mb-1" style={{ fontWeight: 600 }}>Risk Exposure</div>
                <div className="text-3xl text-red-600" style={{ fontWeight: 700 }}>{kpiData.riskExposure}</div>
                <div className="text-slate-400 text-xs mt-1">Critical/high risk items</div>
              </div>
              <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <div className="text-slate-900 text-sm mb-1" style={{ fontWeight: 600 }}>Savings Target</div>
                <div className="text-3xl text-emerald-600" style={{ fontWeight: 700 }}>{kpiData.savingsTarget}%</div>
                <div className="text-slate-400 text-xs mt-1">{formatCurrency(kpiData.totalSavings)} achieved</div>
              </div>
            </div>
          </>
        )}

        {/* Schedules View */}
        {view === 'schedules' && (
          <div className="space-y-4">
            <div className="flex justify-between items-center">
              <h3 className="text-slate-700">Report Schedules</h3>
              <button onClick={() => setShowSchedule(true)} className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-3 py-2 text-sm" style={{ fontWeight: 500 }}>
                <Plus size={14} /> New Schedule
              </button>
            </div>
            {reportSchedules.map(s => {
              const recipientNames = s.recipients.map(uid => getUserById(uid)?.name ?? uid);
              return (
                <div key={s.id} className="bg-white rounded-xl border border-slate-200 shadow-sm p-4 flex items-center gap-4">
                  <div className={`w-9 h-9 rounded-xl flex items-center justify-center ${s.active ? 'bg-indigo-50' : 'bg-slate-100'}`}>
                    <Calendar size={16} className={s.active ? 'text-indigo-600' : 'text-slate-400'} />
                  </div>
                  <div className="flex-1">
                    <div className="flex items-center gap-2">
                      <span className="text-slate-800 text-sm" style={{ fontWeight: 600 }}>{s.name}</span>
                      <span className={`text-xs px-2 py-0.5 rounded-full ${s.active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'}`} style={{ fontWeight: 500 }}>
                        {s.active ? 'Active' : 'Paused'}
                      </span>
                      <span className="text-xs bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded">{s.format.toUpperCase()}</span>
                    </div>
                    <div className="text-slate-500 text-xs mt-0.5">{s.frequency} · {recipientNames.join(', ')}</div>
                  </div>
                  <div className="text-right">
                    <div className="text-slate-600 text-xs" style={{ fontWeight: 500 }}>Next run</div>
                    <div className="text-slate-800 text-sm">{formatDateTime(s.nextRun)}</div>
                  </div>
                  <div className="flex items-center gap-2">
                    <button onClick={() => setDeleteScheduleId(s.id)} className="text-slate-300 hover:text-red-500 p-1 rounded transition-colors">
                      <Trash2 size={14} />
                    </button>
                  </div>
                </div>
              );
            })}
          </div>
        )}

        {/* Saved Report Runs View */}
        {view === 'runs' && (
          <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="flex items-center justify-between px-5 py-4 border-b border-slate-100">
              <h3 className="text-slate-900">Generated Report Runs</h3>
            </div>
            <table className="w-full">
              <thead>
                <tr className="border-b border-slate-100 bg-slate-50">
                  {['Report Name', 'Status', 'Generated', 'Format', 'Size', ''].map(h => (
                    <th key={h} className="text-left px-5 py-3 text-xs text-slate-500" style={{ fontWeight: 600 }}>{h}</th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {savedReportRuns.map(r => (
                  <tr key={r.id} className="border-b border-slate-50 hover:bg-slate-50">
                    <td className="px-5 py-3">
                      <div className="flex items-center gap-2">
                        <FileText size={14} className="text-slate-400" />
                        <span className="text-slate-700 text-sm" style={{ fontWeight: 500 }}>{r.name}</span>
                      </div>
                    </td>
                    <td className="px-5 py-3">
                      <span className={`text-xs px-2 py-0.5 rounded-full ${r.status === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'}`} style={{ fontWeight: 600 }}>
                        {r.status}
                      </span>
                    </td>
                    <td className="px-5 py-3 text-slate-500 text-sm">{formatDateTime(r.generatedAt)}</td>
                    <td className="px-5 py-3">
                      <span className={`text-xs px-2 py-0.5 rounded ${r.format === 'pdf' ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'}`} style={{ fontWeight: 600 }}>
                        {r.format.toUpperCase()}
                      </span>
                    </td>
                    <td className="px-5 py-3 text-slate-500 text-sm">{r.size}</td>
                    <td className="px-5 py-3">
                      <button className="flex items-center gap-1 text-indigo-600 text-xs hover:text-indigo-700" style={{ fontWeight: 500 }}>
                        <Download size={12} /> Download
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* SlideOver: Export Options */}
      <SlideOver
        open={showExport}
        onOpenChange={setShowExport}
        title="Export Options"
        description="Configure and download a report."
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowExport(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button onClick={() => setShowExport(false)} className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>
              <Download size={14} /> Export Now
            </button>
          </div>
        }
      >
        <div className="space-y-5">
          <div>
            <label className="text-slate-600 text-xs block mb-2" style={{ fontWeight: 500 }}>Format</label>
            <div className="flex gap-2">
              {['PDF', 'XLSX', 'CSV'].map(f => (
                <button key={f} className={`flex-1 py-2.5 rounded-lg border text-sm text-center ${f === 'PDF' ? 'border-indigo-300 bg-indigo-50 text-indigo-700' : 'border-slate-200 text-slate-600 hover:border-slate-300'}`} style={{ fontWeight: f === 'PDF' ? 600 : 400 }}>{f}</button>
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
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Report Sections</label>
            <div className="space-y-2">
              {['KPI Summary', 'Spend Trend', 'Spend by Category', 'Approval Metrics', 'Risk Exposure'].map(sec => (
                <label key={sec} className="flex items-center gap-2 text-sm text-slate-700">
                  <input type="checkbox" defaultChecked className="rounded text-indigo-600 focus:ring-indigo-500" />
                  {sec}
                </label>
              ))}
            </div>
          </div>
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Email Delivery (optional)</label>
            <div className="flex items-center gap-2">
              <Mail size={14} className="text-slate-400" />
              <input type="email" placeholder="Enter email address..." className="flex-1 border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-400 outline-none" />
            </div>
          </div>
        </div>
      </SlideOver>

      {/* SlideOver: Schedule Report */}
      <SlideOver
        open={showSchedule}
        onOpenChange={setShowSchedule}
        title="Schedule Report"
        description="Create a recurring report schedule."
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowSchedule(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button onClick={() => setShowSchedule(false)} className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Create Schedule</button>
          </div>
        }
      >
        <div className="space-y-5">
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Report Name</label>
            <input type="text" placeholder="e.g. Monthly Savings Report" className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-400 outline-none" />
          </div>
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Report Type</label>
            <select className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white">
              <option>Savings</option>
              <option>Compliance</option>
              <option>Executive</option>
              <option>Vendor Performance</option>
              <option>Spend Analysis</option>
            </select>
          </div>
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Frequency</label>
            <select className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white">
              <option>Weekly</option>
              <option>Monthly</option>
              <option>Quarterly</option>
            </select>
          </div>
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Format</label>
            <div className="flex gap-2">
              {['PDF', 'Excel'].map(f => (
                <button key={f} className={`flex-1 py-2 rounded-lg border text-xs text-center ${f === 'PDF' ? 'border-indigo-300 bg-indigo-50 text-indigo-700' : 'border-slate-200 text-slate-600 hover:border-slate-300'}`} style={{ fontWeight: f === 'PDF' ? 600 : 400 }}>{f}</button>
              ))}
            </div>
          </div>
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Recipients</label>
            <input type="text" placeholder="Search users to add..." className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-400 outline-none" />
          </div>
        </div>
      </SlideOver>

      {/* SlideOver: Confirm Delete Schedule */}
      <SlideOver
        open={!!deleteScheduleId}
        onOpenChange={() => setDeleteScheduleId(null)}
        title="Confirm Delete Schedule"
        description={deleteSchedule ? `Delete "${deleteSchedule.name}" schedule permanently.` : ''}
        width="sm"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setDeleteScheduleId(null)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">Cancel</button>
            <button onClick={() => setDeleteScheduleId(null)} className="bg-red-600 hover:bg-red-700 text-white rounded-lg px-4 py-2 text-sm" style={{ fontWeight: 500 }}>Delete Schedule</button>
          </div>
        }
      >
        <div className="space-y-4">
          <div className="bg-red-50 border border-red-200 rounded-xl p-4 flex gap-3">
            <AlertTriangle size={18} className="text-red-500 flex-shrink-0 mt-0.5" />
            <div>
              <div className="text-red-700 text-sm" style={{ fontWeight: 600 }}>This action cannot be undone</div>
              <p className="text-red-600 text-xs mt-1">Deleting this schedule will stop all future report runs and remove the configuration. Past generated reports will remain available.</p>
            </div>
          </div>
          {deleteSchedule && (
            <div className="bg-slate-50 rounded-xl border border-slate-200 p-4 space-y-2">
              <div className="flex justify-between text-sm"><span className="text-slate-500">Schedule</span><span className="text-slate-700" style={{ fontWeight: 500 }}>{deleteSchedule.name}</span></div>
              <div className="flex justify-between text-sm"><span className="text-slate-500">Frequency</span><span className="text-slate-700">{deleteSchedule.frequency}</span></div>
              <div className="flex justify-between text-sm"><span className="text-slate-500">Next Run</span><span className="text-slate-700">{formatDateTime(deleteSchedule.nextRun)}</span></div>
            </div>
          )}
        </div>
      </SlideOver>
    </div>
  );
}
