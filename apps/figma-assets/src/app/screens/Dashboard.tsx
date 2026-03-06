import { useNavigate } from "react-router";
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from "recharts";
import {
  TrendingUp, TrendingDown, FileText, CheckCircle2, DollarSign, Clock,
  AlertTriangle, AlertCircle, Info, ChevronRight, Plus, ArrowUpRight,
  GitCompareArrows, Zap, BarChart3
} from "lucide-react";
import { myTasks, riskAlerts, recentComparisons, savingsTrend } from "../data/mockData";

const kpis = [
  { label: "Active RFQs", value: "24", delta: "+3 this week", trend: "up", icon: FileText, accent: "var(--app-brand-500)", bg: "var(--app-brand-tint-8)" },
  { label: "Pending Approvals", value: "7", delta: "2 overdue SLA", trend: "warn", icon: CheckCircle2, accent: "var(--app-warning)", bg: "var(--app-warning-tint-8)" },
  { label: "YTD Savings", value: "$1.24M", delta: "+18% vs target", trend: "up", icon: DollarSign, accent: "var(--app-success)", bg: "var(--app-success-tint-8)" },
  { label: "Avg Cycle Time", value: "14.2d", delta: "-1.8d vs last qtr", trend: "up", icon: Clock, accent: "var(--app-accent-purple)", bg: "var(--app-purple-tint-8)" },
];

const priorityStyles: Record<string, { bg: string; color: string }> = {
  Critical: { bg: "var(--app-danger-tint-10)", color: "var(--app-danger-soft)" },
  High: { bg: "var(--app-warning-tint-10)", color: "var(--app-warning-soft)" },
  Medium: { bg: "var(--app-brand-tint-10)", color: "var(--app-brand-400)" },
  Low: { bg: "var(--app-slate-tint-10)", color: "var(--app-text-subtle)" },
  Overdue: { bg: "var(--app-danger-tint-10)", color: "var(--app-danger-soft)" },
};

const severityIcon = (s: string) => {
  if (s === "Critical") return <AlertCircle size={13} style={{ color: "var(--app-danger)", flexShrink: 0 }} />;
  if (s === "High") return <AlertTriangle size={13} style={{ color: "var(--app-warning)", flexShrink: 0 }} />;
  if (s === "Medium") return <AlertTriangle size={13} style={{ color: "var(--app-warning)", flexShrink: 0 }} />;
  return <Info size={13} style={{ color: "var(--app-brand-500)", flexShrink: 0 }} />;
};

const severityBorder: Record<string, string> = {
  Critical: "var(--app-danger-tint-20)",
  High: "var(--app-orange-tint-20)",
  Medium: "var(--app-warning-tint-15)",
  Low: "var(--app-brand-tint-15)",
};

const taskTypeColors: Record<string, string> = {
  Review: "var(--app-brand-400)",
  Approval: "var(--app-danger-soft)",
  Create: "var(--app-accent-purple)",
  Action: "var(--app-warning-soft)",
  Admin: "var(--app-text-subtle)",
};

function SectionHeader({ title, action, onAction }: { title: string; action?: string; onAction?: () => void }) {
  return (
    <div className="flex items-center justify-between mb-3">
      <h3 style={{ fontSize: 12, fontWeight: 600, color: "var(--app-text-muted)", letterSpacing: "0.08em", textTransform: "uppercase" }}>{title}</h3>
      {action && (
        <button onClick={onAction} style={{ fontSize: 12, color: "var(--app-brand-500)" }} className="flex items-center gap-1 hover:opacity-80 transition-opacity">
          {action} <ChevronRight size={11} />
        </button>
      )}
    </div>
  );
}

export function Dashboard() {
  const navigate = useNavigate();

  return (
    <div style={{ padding: "24px", minHeight: "100%", maxWidth: 1600 }}>
      {/* Page title */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 style={{ fontSize: 20, fontWeight: 700, color: "var(--app-text-strong)", letterSpacing: "-0.01em", marginBottom: 3 }}>Good morning, Sarah</h1>
          <p style={{ fontSize: 13, color: "var(--app-text-subtle)" }}>Thursday, March 5, 2026 · Fiscal Week 10</p>
        </div>
        <button
          onClick={() => navigate("/rfqs/create")}
          className="flex items-center gap-2 rounded-lg transition-colors"
          style={{ height: 36, paddingLeft: 14, paddingRight: 14, background: "var(--app-brand-600)", color: "white", fontSize: 13, fontWeight: 500 }}
        >
          <Plus size={14} /> New RFQ
        </button>
      </div>

      {/* KPI Strip */}
      <div className="grid grid-cols-4 gap-4 mb-6">
        {kpis.map((kpi) => {
          const Icon = kpi.icon;
          return (
            <div
              key={kpi.label}
              className="rounded-xl p-4 border"
              style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}
            >
              <div className="flex items-start justify-between mb-3">
                <div className="rounded-lg p-2" style={{ background: kpi.bg }}>
                  <Icon size={16} style={{ color: kpi.accent }} />
                </div>
                {kpi.trend === "up" ? (
                  <TrendingUp size={13} style={{ color: "var(--app-success)" }} />
                ) : kpi.trend === "warn" ? (
                  <AlertTriangle size={13} style={{ color: "var(--app-warning)" }} />
                ) : (
                  <TrendingDown size={13} style={{ color: "var(--app-danger)" }} />
                )}
              </div>
              <div style={{ fontSize: 26, fontWeight: 700, color: "var(--app-text-strong)", letterSpacing: "-0.02em", lineHeight: 1, marginBottom: 4 }}>
                {kpi.value}
              </div>
              <div style={{ fontSize: 12, color: "var(--app-text-muted)", marginBottom: 2 }}>{kpi.label}</div>
              <div style={{ fontSize: 11, color: kpi.trend === "warn" ? "var(--app-warning)" : "var(--app-success)" }}>{kpi.delta}</div>
            </div>
          );
        })}
      </div>

      {/* Three-column layout */}
      <div className="grid grid-cols-12 gap-4">
        {/* Left: Tasks + Comparisons */}
        <div className="col-span-5 space-y-4">
          {/* My Tasks */}
          <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
            <SectionHeader title="My Tasks" action="View All" onAction={() => navigate("/tasks")} />
            <div className="space-y-1">
              {myTasks.map((task) => (
                <div
                  key={task.id}
                  className="flex items-center gap-3 rounded-lg px-3 py-2.5 cursor-pointer transition-colors"
                  style={{ background: "transparent" }}
                  onMouseEnter={(e) => { (e.currentTarget as HTMLDivElement).style.background = "var(--app-hover-soft)"; }}
                  onMouseLeave={(e) => { (e.currentTarget as HTMLDivElement).style.background = "transparent"; }}
                  onClick={() => task.rfq && navigate(`/rfqs/${task.rfq.toLowerCase().replace("rfq-", "RFQ-")}`)}
                >
                  <div style={{ width: 6, height: 6, borderRadius: "50%", background: taskTypeColors[task.type] ?? "var(--app-text-subtle)", flexShrink: 0 }} />
                  <div className="flex-1 min-w-0">
                    <div style={{ fontSize: 13, color: "var(--app-text-main)", whiteSpace: "nowrap", overflow: "hidden", textOverflow: "ellipsis" }}>{task.title}</div>
                    {task.rfq && <div style={{ fontSize: 11, color: "var(--app-text-subtle)", marginTop: 1 }}>{task.rfq}</div>}
                  </div>
                  <div className="flex items-center gap-2 flex-shrink-0">
                    <span className="rounded px-2 py-0.5" style={{ fontSize: 10, fontWeight: 600, background: priorityStyles[task.priority]?.bg ?? "var(--app-border-strong)", color: priorityStyles[task.priority]?.color ?? "var(--app-text-subtle)" }}>
                      {task.priority}
                    </span>
                    <span style={{ fontSize: 11, color: task.due === "Overdue" ? "var(--app-danger)" : "var(--app-text-subtle)" }}>{task.due}</span>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Recent Comparisons */}
          <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
            <SectionHeader title="Recent Comparison Runs" action="All Comparisons" onAction={() => navigate("/comparison")} />
            <div className="space-y-2">
              {recentComparisons.map((cmp) => (
                <div key={cmp.id} className="rounded-lg p-3 border cursor-pointer transition-colors" style={{ borderColor: "var(--app-border-strong)", background: "var(--app-bg-elevated)" }}
                  onMouseEnter={(e) => { (e.currentTarget as HTMLDivElement).style.borderColor = "var(--app-text-faint)"; }}
                  onMouseLeave={(e) => { (e.currentTarget as HTMLDivElement).style.borderColor = "var(--app-border-strong)"; }}
                  onClick={() => navigate(`/comparison/${cmp.rfq}`)}
                >
                  <div className="flex items-start justify-between mb-2">
                    <div>
                      <div style={{ fontSize: 13, fontWeight: 500, color: "var(--app-text-main)" }}>{cmp.title}</div>
                      <div style={{ fontSize: 11, color: "var(--app-text-subtle)", marginTop: 1 }}>{cmp.rfq} · {cmp.vendors} vendors</div>
                    </div>
                    <span className="rounded px-2 py-0.5" style={{ fontSize: 10, fontWeight: 600, background: cmp.status === "Complete" ? "var(--app-success-tint-10)" : "var(--app-warning-tint-10)", color: cmp.status === "Complete" ? "var(--app-success)" : "var(--app-warning)" }}>
                      {cmp.status}
                    </span>
                  </div>
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-1.5">
                      <Zap size={11} style={{ color: "var(--app-brand-500)" }} />
                      <span style={{ fontSize: 12, color: "var(--app-brand-400)" }}>Recommended: {cmp.recommended}</span>
                    </div>
                    <div className="flex items-center gap-1">
                      <div className="rounded-full" style={{ width: 6, height: 6, background: "var(--app-brand-500)" }} />
                      <span style={{ fontSize: 11, color: "var(--app-text-subtle)" }}>Score {cmp.score}</span>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Middle: Savings Chart */}
        <div className="col-span-4">
          <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)", height: "100%" }}>
            <SectionHeader title="Savings Trend (6M)" />
            <div className="flex items-end gap-4 mb-4">
              <div style={{ fontSize: 28, fontWeight: 700, color: "var(--app-text-strong)", letterSpacing: "-0.02em" }}>$1.24M</div>
              <div className="flex items-center gap-1 mb-1" style={{ fontSize: 12, color: "var(--app-success)" }}>
                <TrendingUp size={13} /> +18% vs target
              </div>
            </div>
            <ResponsiveContainer width="100%" height={160}>
              <AreaChart data={savingsTrend} margin={{ top: 0, right: 0, left: -20, bottom: 0 }}>
                <defs>
                  <linearGradient id="savingsGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="var(--app-brand-500)" stopOpacity={0.15} />
                    <stop offset="95%" stopColor="var(--app-brand-500)" stopOpacity={0} />
                  </linearGradient>
                  <linearGradient id="targetGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="var(--app-accent-indigo)" stopOpacity={0.08} />
                    <stop offset="95%" stopColor="var(--app-accent-indigo)" stopOpacity={0} />
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" stroke="var(--app-border-strong)" vertical={false} />
                <XAxis dataKey="month" tick={{ fontSize: 11, fill: "var(--app-text-subtle)" }} axisLine={false} tickLine={false} />
                <YAxis tick={{ fontSize: 10, fill: "var(--app-text-subtle)" }} axisLine={false} tickLine={false} tickFormatter={(v) => `$${(v / 1000).toFixed(0)}k`} />
                <Tooltip
                  contentStyle={{ background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", borderRadius: 8, fontSize: 12, color: "var(--app-text-main)" }}
                  formatter={(v: number) => [`$${(v / 1000).toFixed(0)}k`, ""]}
                  labelStyle={{ color: "var(--app-text-subtle)" }}
                />
                <Area type="monotone" dataKey="target" stroke="var(--app-accent-indigo)" strokeWidth={1} strokeDasharray="4 4" fill="url(#targetGrad)" dot={false} />
                <Area type="monotone" dataKey="savings" stroke="var(--app-brand-500)" strokeWidth={2} fill="url(#savingsGrad)" dot={{ fill: "var(--app-brand-500)", r: 3, strokeWidth: 0 }} />
              </AreaChart>
            </ResponsiveContainer>
            <div className="flex items-center gap-4 mt-3" style={{ fontSize: 11, color: "var(--app-text-subtle)" }}>
              <div className="flex items-center gap-1.5"><div style={{ width: 12, height: 2, background: "var(--app-brand-500)", borderRadius: 1 }} /> Actual</div>
              <div className="flex items-center gap-1.5"><div style={{ width: 12, height: 2, background: "var(--app-accent-indigo)", borderRadius: 1, opacity: 0.7 }} /> Target</div>
            </div>

            {/* Mini KPIs */}
            <div className="grid grid-cols-2 gap-3 mt-4">
              {[
                { label: "Best Single Award", value: "$412K", icon: ArrowUpRight, color: "var(--app-success)" },
                { label: "Avg per RFQ", value: "$51.7K", icon: BarChart3, color: "var(--app-brand-500)" },
              ].map((item) => (
                <div key={item.label} className="rounded-lg p-3" style={{ background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)" }}>
                  <item.icon size={13} style={{ color: item.color, marginBottom: 6 }} />
                  <div style={{ fontSize: 16, fontWeight: 700, color: "var(--app-text-strong)", letterSpacing: "-0.01em" }}>{item.value}</div>
                  <div style={{ fontSize: 11, color: "var(--app-text-subtle)", marginTop: 2 }}>{item.label}</div>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Right: Risk/SLA Alerts */}
        <div className="col-span-3">
          <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)", height: "100%" }}>
            <SectionHeader title="Risk & SLA Alerts" action="View All" onAction={() => navigate("/risk")} />
            <div className="space-y-2">
              {riskAlerts.map((alert) => (
                <div
                  key={alert.id}
                  className="rounded-lg p-3 cursor-pointer transition-colors"
                  style={{ background: "var(--app-bg-elevated)", border: `1px solid ${severityBorder[alert.severity] ?? "var(--app-border-strong)"}` }}
                  onMouseEnter={(e) => { (e.currentTarget as HTMLDivElement).style.background = "var(--app-border-strong)"; }}
                  onMouseLeave={(e) => { (e.currentTarget as HTMLDivElement).style.background = "var(--app-bg-elevated)"; }}
                >
                  <div className="flex items-start gap-2 mb-1">
                    {severityIcon(alert.severity)}
                    <span style={{ fontSize: 12, fontWeight: 600, color: "var(--app-text-main)", lineHeight: 1.3 }}>{alert.title}</span>
                  </div>
                  <p style={{ fontSize: 11, color: "var(--app-text-muted)", lineHeight: 1.5, marginBottom: 6 }}>{alert.description}</p>
                  <div className="flex items-center justify-between">
                    <span style={{ fontSize: 10, fontFamily: "monospace", color: "var(--app-brand-500)" }}>{alert.source}</span>
                    <span style={{ fontSize: 10, color: "var(--app-text-faint)" }}>{alert.time}</span>
                  </div>
                </div>
              ))}
            </div>

            <div className="mt-4 rounded-lg p-3 border" style={{ background: "var(--app-brand-tint-4)", borderColor: "var(--app-brand-tint-12)" }}>
              <div className="flex items-center gap-2 mb-1">
                <GitCompareArrows size={12} style={{ color: "var(--app-brand-400)" }} />
                <span style={{ fontSize: 11, fontWeight: 600, color: "var(--app-brand-400)" }}>AI Agent Summary</span>
              </div>
              <p style={{ fontSize: 11, color: "var(--app-text-muted)", lineHeight: 1.6 }}>
                2 approvals require action within 4 hours. GlobalPump sanctions flag is blocking the RFQ-2024-001 award decision.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
