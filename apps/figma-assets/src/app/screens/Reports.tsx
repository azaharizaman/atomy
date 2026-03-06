import { useState } from "react";
import {
  TrendingUp, TrendingDown, DollarSign, FileText, Users,
  Clock, Award, AlertTriangle, Calendar, Download, Filter,
  BarChart3, PieChart, Activity, Target, Zap, ChevronDown
} from "lucide-react";
import { BarChart, Bar, LineChart, Line, PieChart as RechartsPie, Pie, Cell, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from "recharts";

const rfqVolumeData = [
  { month: "Jul", created: 24, awarded: 18, cancelled: 3 },
  { month: "Aug", created: 31, awarded: 24, cancelled: 4 },
  { month: "Sep", created: 28, awarded: 22, cancelled: 2 },
  { month: "Oct", created: 35, awarded: 28, cancelled: 5 },
  { month: "Nov", created: 42, awarded: 32, cancelled: 6 },
  { month: "Dec", created: 38, awarded: 30, cancelled: 4 },
  { month: "Jan", created: 45, awarded: 35, cancelled: 3 },
];

const spendByCategory = [
  { category: "IT Hardware", value: 1245000, color: "var(--app-brand-500)" },
  { category: "Equipment", value: 892000, color: "var(--app-accent-purple)" },
  { category: "Services", value: 675000, color: "var(--app-success)" },
  { category: "Manufacturing", value: 534000, color: "var(--app-warning)" },
  { category: "Logistics", value: 412000, color: "var(--app-danger)" },
  { category: "Other", value: 298000, color: "var(--app-text-muted)" },
];

const vendorPerformance = [
  { vendor: "Apex Industrial", quotes: 12, won: 8, avgScore: 94, savings: 42000 },
  { vendor: "GlobalPump Tech", quotes: 15, won: 6, avgScore: 88, savings: 28000 },
  { vendor: "TechFlow Dynamics", quotes: 10, won: 7, avgScore: 96, savings: 51000 },
  { vendor: "Meridian Equipment", quotes: 8, won: 3, avgScore: 82, savings: 18000 },
  { vendor: "SecureGuard Systems", quotes: 11, won: 5, avgScore: 85, savings: 35000 },
];

const cycleTimeData = [
  { stage: "Draft to Open", avgDays: 2.3, target: 2 },
  { stage: "Open to Quotes", avgDays: 12.5, target: 14 },
  { stage: "Quotes to Comparison", avgDays: 3.8, target: 3 },
  { stage: "Comparison to Approval", avgDays: 5.2, target: 5 },
  { stage: "Approval to Award", avgDays: 1.5, target: 2 },
];

export function Reports() {
  const [dateRange, setDateRange] = useState("Last 6 Months");
  const [reportType, setReportType] = useState("Overview");

  const reportTypes = ["Overview", "Spend Analysis", "Vendor Performance", "Cycle Time", "Compliance"];
  const dateRanges = ["Last 30 Days", "Last 90 Days", "Last 6 Months", "Last Year", "Custom"];

  const MetricCard = ({ label, value, change, changeLabel, icon: Icon, trend }: any) => (
    <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
      <div className="flex items-center justify-between mb-3">
        <div className="flex items-center gap-2">
          <div className="flex items-center justify-center rounded" style={{ width: 32, height: 32, background: "var(--app-bg-elevated)" }}>
            <Icon size={16} style={{ color: "var(--app-text-muted)" }} />
          </div>
          <span style={{ fontSize: 12, fontWeight: 500, color: "var(--app-text-muted)", letterSpacing: "0.02em", textTransform: "uppercase" }}>
            {label}
          </span>
        </div>
      </div>
      <div className="flex items-end justify-between">
        <div>
          <div style={{ fontSize: 28, fontWeight: 800, color: "var(--app-text-strong)", letterSpacing: "-0.02em", lineHeight: 1, marginBottom: 6 }}>
            {value}
          </div>
          {change !== undefined && (
            <div className="flex items-center gap-1.5">
              {trend === "up" ? (
                <TrendingUp size={13} style={{ color: "var(--app-success)" }} />
              ) : (
                <TrendingDown size={13} style={{ color: "var(--app-danger)" }} />
              )}
              <span style={{ fontSize: 12, color: trend === "up" ? "var(--app-success)" : "var(--app-danger)", fontWeight: 600 }}>
                {change}%
              </span>
              <span style={{ fontSize: 11, color: "var(--app-text-muted)" }}>{changeLabel}</span>
            </div>
          )}
        </div>
      </div>
    </div>
  );

  return (
    <div style={{ padding: "24px", minHeight: "100%", fontFamily: "'Inter', system-ui, sans-serif", background: "var(--app-bg-canvas)" }}>
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 style={{ fontSize: 20, fontWeight: 700, color: "var(--app-text-strong)", letterSpacing: "-0.01em", marginBottom: 3 }}>
            Reports & Analytics
          </h1>
          <p style={{ fontSize: 13, color: "var(--app-text-muted)" }}>Procurement intelligence and performance insights</p>
        </div>
        <div className="flex items-center gap-2">
          <div className="flex items-center gap-2 rounded-lg px-3" style={{ height: 36, background: "var(--app-bg-surface)", border: "1px solid var(--app-border-strong)" }}>
            <Calendar size={12} style={{ color: "var(--app-text-muted)" }} />
            <select
              value={dateRange}
              onChange={(e) => setDateRange(e.target.value)}
              style={{ background: "transparent", border: "none", outline: "none", fontSize: 13, color: "var(--app-text-main)", cursor: "pointer" }}
            >
              {dateRanges.map((r) => (
                <option key={r} value={r} style={{ background: "var(--app-bg-surface)", color: "var(--app-text-main)" }}>
                  {r}
                </option>
              ))}
            </select>
          </div>
          <button
            className="flex items-center gap-2 rounded-lg px-3 py-2 transition-colors"
            style={{ fontSize: 13, color: "var(--app-text-subtle)", background: "var(--app-bg-surface)", border: "1px solid var(--app-border-strong)" }}
            onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-text-faint)"; }}
            onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-border-strong)"; }}
          >
            <Download size={13} /> Export PDF
          </button>
        </div>
      </div>

      {/* Report Type Tabs */}
      <div className="flex items-center gap-1 p-1 rounded-lg mb-6" style={{ background: "var(--app-bg-surface)", border: "1px solid var(--app-border-strong)", width: "fit-content" }}>
        {reportTypes.map((type) => (
          <button
            key={type}
            onClick={() => setReportType(type)}
            className="rounded transition-all"
            style={{ fontSize: 12, padding: "6px 14px", background: reportType === type ? "var(--app-bg-elevated)" : "transparent", color: reportType === type ? "var(--app-text-main)" : "var(--app-text-muted)", fontWeight: reportType === type ? 500 : 400 }}
          >
            {type}
          </button>
        ))}
      </div>

      {/* Overview Report */}
      {reportType === "Overview" && (
        <>
          {/* Key Metrics */}
          <div className="grid grid-cols-4 gap-4 mb-6">
            <MetricCard
              label="Total Spend"
              value="$4.06M"
              change={12.4}
              changeLabel="vs prev period"
              icon={DollarSign}
              trend="up"
            />
            <MetricCard
              label="Active RFQs"
              value="42"
              change={8.2}
              changeLabel="vs prev period"
              icon={FileText}
              trend="up"
            />
            <MetricCard
              label="Avg Savings"
              value="18.2%"
              change={3.1}
              changeLabel="improvement"
              icon={Target}
              trend="up"
            />
            <MetricCard
              label="Cycle Time"
              value="25.3d"
              change={6.8}
              changeLabel="reduction"
              icon={Clock}
              trend="down"
            />
          </div>

          {/* Charts Row 1 */}
          <div className="grid grid-cols-2 gap-4 mb-4">
            {/* RFQ Volume Trend */}
            <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
              <div className="flex items-center justify-between mb-4">
                <h3 style={{ fontSize: 13, fontWeight: 600, color: "var(--app-text-strong)" }}>RFQ Volume Trend</h3>
                <div className="flex items-center gap-3">
                  <div className="flex items-center gap-1.5">
                    <div style={{ width: 8, height: 8, borderRadius: 2, background: "var(--app-brand-500)" }} />
                    <span style={{ fontSize: 11, color: "var(--app-text-muted)" }}>Created</span>
                  </div>
                  <div className="flex items-center gap-1.5">
                    <div style={{ width: 8, height: 8, borderRadius: 2, background: "var(--app-success)" }} />
                    <span style={{ fontSize: 11, color: "var(--app-text-muted)" }}>Awarded</span>
                  </div>
                  <div className="flex items-center gap-1.5">
                    <div style={{ width: 8, height: 8, borderRadius: 2, background: "var(--app-danger)" }} />
                    <span style={{ fontSize: 11, color: "var(--app-text-muted)" }}>Cancelled</span>
                  </div>
                </div>
              </div>
              <ResponsiveContainer width="100%" height={220}>
                <BarChart data={rfqVolumeData}>
                  <CartesianGrid strokeDasharray="3 3" stroke="var(--app-border-strong)" />
                  <XAxis dataKey="month" stroke="var(--app-text-muted)" style={{ fontSize: 11 }} />
                  <YAxis stroke="var(--app-text-muted)" style={{ fontSize: 11 }} />
                  <Tooltip
                    contentStyle={{ background: "var(--app-bg-surface)", border: "1px solid var(--app-border-strong)", borderRadius: 8, fontSize: 12 }}
                    labelStyle={{ color: "var(--app-text-strong)", fontWeight: 600, marginBottom: 4 }}
                    itemStyle={{ color: "var(--app-text-main)" }}
                  />
                  <Bar dataKey="created" fill="var(--app-brand-500)" radius={[4, 4, 0, 0]} />
                  <Bar dataKey="awarded" fill="var(--app-success)" radius={[4, 4, 0, 0]} />
                  <Bar dataKey="cancelled" fill="var(--app-danger)" radius={[4, 4, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            </div>

            {/* Spend by Category */}
            <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
              <h3 style={{ fontSize: 13, fontWeight: 600, color: "var(--app-text-strong)", marginBottom: 4 }}>Spend by Category</h3>
              <ResponsiveContainer width="100%" height={220}>
                <RechartsPie>
                  <Pie
                    data={spendByCategory}
                    dataKey="value"
                    nameKey="category"
                    cx="50%"
                    cy="50%"
                    outerRadius={80}
                    label={(entry) => `${entry.category} $${(entry.value / 1000).toFixed(0)}K`}
                    labelStyle={{ fontSize: 11, fill: "var(--app-text-muted)", fontWeight: 500 }}
                  >
                    {spendByCategory.map((entry, index) => (
                      <Cell key={`cell-${index}`} fill={entry.color} />
                    ))}
                  </Pie>
                  <Tooltip
                    contentStyle={{ background: "var(--app-bg-surface)", border: "1px solid var(--app-border-strong)", borderRadius: 8, fontSize: 12 }}
                    formatter={(value: any) => `$${(value / 1000).toFixed(0)}K`}
                  />
                </RechartsPie>
              </ResponsiveContainer>
            </div>
          </div>

          {/* Vendor Performance Table */}
          <div className="rounded-xl border overflow-hidden mb-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
            <div className="px-4 py-3 border-b flex items-center justify-between" style={{ borderColor: "var(--app-border-strong)" }}>
              <h3 style={{ fontSize: 13, fontWeight: 600, color: "var(--app-text-strong)" }}>Top Vendor Performance</h3>
              <button style={{ fontSize: 12, color: "var(--app-brand-500)" }} className="hover:opacity-80 transition-opacity">
                View all vendors →
              </button>
            </div>
            <table style={{ width: "100%", borderCollapse: "collapse" }}>
              <thead>
                <tr style={{ borderBottom: "1px solid var(--app-border-strong)" }}>
                  {["Vendor", "Quotes Submitted", "Awards Won", "Win Rate", "Avg Score", "Cost Savings"].map((h) => (
                    <th key={h} style={{ fontSize: 10, fontWeight: 600, color: "var(--app-text-muted)", letterSpacing: "0.06em", textTransform: "uppercase", padding: "10px 14px", textAlign: "left", background: "var(--app-bg-elevated)" }}>
                      {h}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {vendorPerformance.map((vendor, i) => {
                  const winRate = Math.round((vendor.won / vendor.quotes) * 100);
                  return (
                    <tr key={vendor.vendor} style={{ borderBottom: i < vendorPerformance.length - 1 ? "1px solid var(--app-border-strong)" : "none" }}>
                      <td style={{ padding: "10px 14px", fontSize: 13, fontWeight: 500, color: "var(--app-text-strong)" }}>{vendor.vendor}</td>
                      <td style={{ padding: "10px 14px", fontSize: 13, color: "var(--app-text-main)", textAlign: "left" }}>{vendor.quotes}</td>
                      <td style={{ padding: "10px 14px", fontSize: 13, color: "var(--app-text-main)", textAlign: "left" }}>{vendor.won}</td>
                      <td style={{ padding: "10px 14px", textAlign: "left" }}>
                        <div className="flex items-center gap-2">
                          <div className="flex-1 rounded-full overflow-hidden" style={{ height: 4, background: "var(--app-border-strong)", maxWidth: 80 }}>
                            <div style={{ height: "100%", width: `${winRate}%`, background: winRate >= 60 ? "var(--app-success)" : "var(--app-warning)", borderRadius: 2 }} />
                          </div>
                          <span style={{ fontSize: 12, fontWeight: 600, color: winRate >= 60 ? "var(--app-success)" : "var(--app-warning)", fontFamily: "'JetBrains Mono', monospace" }}>
                            {winRate}%
                          </span>
                        </div>
                      </td>
                      <td style={{ padding: "10px 14px", fontSize: 13, fontFamily: "'JetBrains Mono', monospace", color: "var(--app-text-main)", textAlign: "left" }}>
                        {vendor.avgScore}/100
                      </td>
                      <td style={{ padding: "10px 14px", fontSize: 13, fontFamily: "'JetBrains Mono', monospace", fontWeight: 600, color: "var(--app-success)", textAlign: "left" }}>
                        ${vendor.savings.toLocaleString()}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>

          {/* Cycle Time Analysis */}
          <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
            <div className="flex items-center justify-between mb-4">
              <h3 style={{ fontSize: 13, fontWeight: 600, color: "var(--app-text-strong)" }}>RFQ Cycle Time by Stage</h3>
              <div className="flex items-center gap-2 rounded-lg px-2.5 py-1" style={{ background: "var(--app-success-tint-10)", border: "1px solid var(--app-success-tint-20)" }}>
                <Activity size={11} style={{ color: "var(--app-success)" }} />
                <span style={{ fontSize: 11, color: "var(--app-success)", fontWeight: 600 }}>On Track</span>
              </div>
            </div>
            <div className="space-y-3">
              {cycleTimeData.map((stage) => {
                const percentage = (stage.avgDays / stage.target) * 100;
                const isOnTrack = stage.avgDays <= stage.target;
                return (
                  <div key={stage.stage}>
                    <div className="flex items-center justify-between mb-1.5">
                      <span style={{ fontSize: 12, color: "var(--app-text-muted)" }}>{stage.stage}</span>
                      <div className="flex items-center gap-3">
                        <span style={{ fontSize: 11, color: "var(--app-text-faint)" }}>Target: {stage.target}d</span>
                        <span style={{ fontSize: 13, fontWeight: 700, color: isOnTrack ? "var(--app-success)" : "var(--app-warning)", fontFamily: "'JetBrains Mono', monospace" }}>
                          {stage.avgDays}d
                        </span>
                      </div>
                    </div>
                    <div className="rounded-full overflow-hidden" style={{ height: 6, background: "var(--app-border-strong)" }}>
                      <div
                        style={{
                          height: "100%",
                          width: `${Math.min(percentage, 100)}%`,
                          background: isOnTrack ? "var(--app-success)" : "var(--app-warning)",
                          borderRadius: 3,
                          transition: "width 0.3s",
                        }}
                      />
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        </>
      )}
    </div>
  );
}
