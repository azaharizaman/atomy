import { useState } from "react";
import {
  Zap, ShieldCheck, TrendingDown, Star, Activity, AlertCircle,
  CheckCircle2, Clock, Upload, Download, FileText, ChevronRight,
  Plus, X, RefreshCw, Layers, Shield, Sparkles, UserCheck,
  MessageSquare, History, Lock, Trash2, SlidersHorizontal,
  LayoutDashboard, Inbox, GitCompareArrows, BarChart2,
  Copy, Check, Trophy, ChevronDown, Eye, User, Calendar,
  Filter, ArrowLeft, GitBranch, ShieldAlert, ListFilter,
  CheckCheck, AlertTriangle, Users, Search, Play, SendHorizonal,
  ExternalLink, Mail, Phone, Settings, LogOut, Info,
  MoreVertical, Menu, Terminal, Command
} from "lucide-react";

const CodeSnippet = ({ code }: { code: string }) => {
  const [copied, setCheck] = useState(false);
  const copy = () => {
    navigator.clipboard.writeText(code);
    setCheck(true);
    setTimeout(() => setCheck(false), 2000);
  };
  return (
    <div className="relative group mt-3">
      <pre className="p-4 rounded-lg bg-slate-950 border border-white/5 overflow-x-auto">
        <code className="text-[11px] font-mono text-indigo-300 leading-relaxed">{code}</code>
      </pre>
      <button 
        onClick={copy}
        className="absolute top-3 right-3 p-1.5 rounded bg-slate-800 border border-white/10 opacity-0 group-hover:opacity-100 transition-all text-slate-400 hover:text-white"
      >
        {copied ? <Check size={12} className="text-emerald-500" /> : <Copy size={12} />}
      </button>
    </div>
  );
};

const ColorSwatch = ({ name, variable, hex }: { name: string, variable: string, hex?: string }) => (
  <div className="space-y-2 group">
    <div className="h-20 rounded-xl border border-white/5 shadow-2xl relative overflow-hidden" style={{ background: `var(${variable})` }}>
       <div className="absolute inset-0 bg-white/5 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none" />
    </div>
    <div>
      <div style={{ fontSize: 11, fontWeight: 800, color: "var(--app-text-strong)", textTransform: "uppercase" }}>{name}</div>
      <div style={{ fontSize: 10, color: "var(--app-text-faint)", fontFamily: "monospace" }}>{variable}</div>
      {hex && <div style={{ fontSize: 9, color: "var(--app-text-subtle)", marginTop: 2 }}>{hex}</div>}
    </div>
  </div>
);

const Section = ({ title, children, description }: any) => (
  <section className="space-y-6 pt-20 first:pt-0 border-t first:border-t-0 border-slate-800/50">
    <div>
      <h2 style={{ fontSize: 28, fontWeight: 900, color: "var(--app-text-strong)", letterSpacing: "-0.02em" }}>{title}</h2>
      {description && <p style={{ fontSize: 14, color: "var(--app-text-muted)", marginTop: 8, maxWidth: "800px", lineHeight: 1.6 }}>{description}</p>}
    </div>
    <div className="space-y-12">{children}</div>
  </section>
);

const ComponentExample = ({ name, children, code, dark = false }: any) => (
  <div className="space-y-4">
    <div className="flex items-center gap-2">
      <div className="w-1.5 h-1.5 rounded-full bg-indigo-500 shadow-[0_0_10px_rgba(99,102,241,0.5)]" />
      <h3 style={{ fontSize: 13, fontWeight: 800, color: "var(--app-text-main)", textTransform: "uppercase", letterSpacing: "0.1em" }}>{name}</h3>
    </div>
    <div className={`p-10 rounded-2xl border border-white/5 ${dark ? 'bg-slate-950' : 'bg-slate-900/20'} shadow-inner`}>
      {children}
    </div>
    <CodeSnippet code={code} />
  </div>
);

export function DesignSystem() {
  return (
    <div className="flex flex-col h-full bg-canvas overflow-auto" style={{ background: "var(--app-bg-canvas)" }}>
      {/* Hero Header */}
      <div className="px-8 py-20 border-b bg-surface relative overflow-hidden" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
        <div className="max-w-6xl mx-auto relative z-10">
          <div className="flex items-center gap-3 mb-6">
            <div className="p-2.5 rounded-2xl bg-indigo-600 shadow-2xl shadow-indigo-500/40 border border-indigo-400/20">
              <Layers size={28} className="text-white" />
            </div>
            <span style={{ fontSize: 12, fontWeight: 900, color: "var(--app-brand-500)", textTransform: "uppercase", letterSpacing: "0.3em" }}>Atomy-Q Global</span>
          </div>
          <h1 style={{ fontSize: 56, fontWeight: 900, color: "var(--app-text-strong)", letterSpacing: "-0.05em", lineHeight: 1 }}>
            Design System <span className="text-slate-600">v1.2</span>
          </h1>
          <p className="mt-8 text-xl text-muted max-w-3xl leading-relaxed" style={{ color: "var(--app-text-muted)" }}>
            The definitive technical guide to building Atomy-Q screens. 
            Focused on consistency, high-density data management, and immutable governance patterns.
          </p>
        </div>
        {/* Abstract background glow */}
        <div className="absolute -top-24 -right-24 w-96 h-96 bg-indigo-600/10 rounded-full blur-[120px] pointer-events-none" />
        <div className="absolute -bottom-24 -left-24 w-96 h-96 bg-blue-600/10 rounded-full blur-[120px] pointer-events-none" />
      </div>

      <div className="px-8 py-20 max-w-6xl mx-auto w-full space-y-32 pb-64">
        
        {/* 1. COLOR PALETTE */}
        <Section title="Color Palette" description="Semantic tokens used throughout the application to ensure behavioral consistency. Signal colors carry strict functional meaning.">
          
          <ComponentExample name="Core Backgrounds" code={`--app-bg-canvas: #070b14;   /* Main page background */
--app-bg-surface: #0d1117;  /* Primary container cards */
--app-bg-elevated: #111827; /* Headers and nested elements */`}>
            <div className="grid grid-cols-4 gap-8">
              <ColorSwatch name="Canvas" variable="--app-bg-canvas" hex="#070B14" />
              <ColorSwatch name="Surface" variable="--app-bg-surface" hex="#0D1117" />
              <ColorSwatch name="Elevated" variable="--app-bg-elevated" hex="#111827" />
              <ColorSwatch name="Glass" variable="--app-surface-glass" hex="RGBA(15,23,42,0.8)" />
            </div>
          </ComponentExample>

          <ComponentExample name="Functional Signals" code={`--app-brand-500: #3b82f6; /* Action / Primary */
--app-success: #10b981;   /* High Score / Accepted */
--app-warning: #f59e0b;   /* Low Confidence / Pending */
--app-danger: #ef4444;    /* Risk / Rejected */`}>
            <div className="grid grid-cols-4 gap-8">
              <ColorSwatch name="Action / Brand" variable="--app-brand-500" hex="#3B82F6" />
              <ColorSwatch name="Success / Safe" variable="--app-success" hex="#10B981" />
              <ColorSwatch name="Warning / Review" variable="--app-warning" hex="#F59E0B" />
              <ColorSwatch name="Danger / Risk" variable="--app-danger" hex="#EF4444" />
            </div>
          </ComponentExample>
        </Section>

        {/* 2. TYPOGRAPHY */}
        <Section title="Typography" description="Scalable font system designed for readability in dense data matrices. Built on Inter and JetBrains Mono.">
          
          <ComponentExample name="Heading Scale" code={`<h1 className="text-4xl font-black">Display Large</h1>
<h2 className="text-2xl font-bold">Screen Title</h2>
<h3 className="text-lg font-semibold">Section Title</h3>`}>
            <div className="space-y-8">
              <div style={{ fontSize: 42, fontWeight: 900, color: "var(--app-text-strong)", letterSpacing: "-0.04em" }}>Display Title · 42px Black</div>
              <div style={{ fontSize: 24, fontWeight: 800, color: "var(--app-text-strong)", letterSpacing: "-0.02em" }}>Screen Header · 24px ExtraBold</div>
              <div style={{ fontSize: 18, fontWeight: 700, color: "var(--app-text-main)" }}>Sub-Section Header · 18px Bold</div>
              <div style={{ fontSize: 14, fontWeight: 800, color: "var(--app-text-muted)", textTransform: "uppercase", letterSpacing: "0.15em" }}>Overline Label · 14px Heavy Upper</div>
            </div>
          </ComponentExample>

          <ComponentExample name="Monospace Data Scale" code={`<code className="font-mono text-brand">RFQ-2024-001</code>
<span className="font-mono text-xs">sha256:8f3e...2a1b</span>`}>
            <div className="space-y-4">
              <div className="flex items-center gap-4">
                <span className="p-2 rounded bg-slate-900 border border-white/5 font-mono text-sm text-indigo-400">ID-90210</span>
                <span className="p-2 rounded bg-slate-900 border border-white/5 font-mono text-sm text-emerald-400">$165,600.00</span>
                <span className="p-2 rounded bg-slate-900 border border-white/5 font-mono text-xs text-slate-500">sha256:4d1a7b...9c0e</span>
              </div>
            </div>
          </ComponentExample>
        </Section>

        {/* 3. BUTTONS & INPUTS */}
        <Section title="Buttons & Interactive Elements" description="Standardized actions including sizes, semantic variants, and state feedback.">
          
          <ComponentExample name="Button Variants & States" code={`<button className="btn-primary">Primary</button>
<button className="btn-secondary">Secondary</button>
<button className="btn-danger" disabled>Disabled</button>`}>
            <div className="space-y-10">
              {/* Sizes */}
              <div className="flex items-end gap-6">
                <button className="px-3 py-1.5 rounded-md bg-indigo-600 text-white font-bold text-[11px] uppercase tracking-wider">Small</button>
                <button className="px-4 py-2 rounded-lg bg-indigo-600 text-white font-bold text-sm shadow-lg shadow-indigo-500/20">Medium Default</button>
                <button className="px-6 py-3 rounded-xl bg-indigo-600 text-white font-black text-base shadow-xl shadow-indigo-500/30">Large Primary</button>
              </div>

              {/* Semantic Variants */}
              <div className="flex gap-4">
                <button className="px-4 py-2 rounded-lg bg-blue-600 text-white font-bold text-sm flex items-center gap-2 hover:bg-blue-500 transition-all"><Trophy size={14}/> Award</button>
                <button className="px-4 py-2 rounded-lg bg-slate-800 border border-white/10 text-slate-300 font-bold text-sm hover:bg-slate-700 transition-all"><Download size={14}/> Export</button>
                <button className="px-4 py-2 rounded-lg bg-emerald-600/10 border border-emerald-500/20 text-emerald-500 font-bold text-sm hover:bg-emerald-500/20 transition-all"><Check size={14}/> Verify</button>
                <button className="px-4 py-2 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 font-bold text-sm hover:bg-red-500/20 transition-all"><Trash2 size={14}/> Reject</button>
              </div>

              {/* States */}
              <div className="flex gap-4 items-center">
                <button className="px-4 py-2 rounded-lg bg-indigo-600/50 text-white/50 font-bold text-sm cursor-not-allowed border border-white/5" disabled>Disabled State</button>
                <button className="px-4 py-2 rounded-lg bg-indigo-500 text-white font-bold text-sm shadow-[0_0_20px_rgba(99,102,241,0.4)] ring-2 ring-indigo-400/50">Active / Focus</button>
                <button className="flex items-center justify-center w-10 h-10 rounded-full bg-slate-800 border border-white/10 text-slate-400 hover:text-white transition-all"><MoreVertical size={16}/></button>
              </div>
            </div>
          </ComponentExample>

          <ComponentExample name="Button Groups" code={`<div className="flex rounded-lg overflow-hidden border">
  <button className="px-3 py-1.5 bg-brand text-white">Grid</button>
  <button className="px-3 py-1.5 bg-elevated text-muted">List</button>
</div>`}>
            <div className="flex flex-col gap-6">
              <div className="flex rounded-lg overflow-hidden border border-white/10 w-fit shadow-2xl">
                <button className="px-4 py-2 bg-indigo-600 text-white text-xs font-bold border-r border-indigo-500 flex items-center gap-2"><LayoutDashboard size={13}/> Dashboard</button>
                <button className="px-4 py-2 bg-slate-900 text-slate-400 text-xs font-bold border-r border-white/5 hover:bg-slate-800 transition-all">Analytics</button>
                <button className="px-4 py-2 bg-slate-900 text-slate-400 text-xs font-bold hover:bg-slate-800 transition-all">Settings</button>
              </div>

              <div className="flex p-1 rounded-xl bg-slate-900/50 border border-white/5 w-fit">
                <button className="px-4 py-1.5 rounded-lg bg-slate-800 text-indigo-400 text-[11px] font-black uppercase tracking-tighter shadow-inner">V001</button>
                <button className="px-4 py-1.5 rounded-lg text-slate-500 text-[11px] font-bold uppercase hover:text-slate-300 transition-all">V002</button>
                <button className="px-4 py-1.5 rounded-lg text-slate-500 text-[11px] font-bold uppercase hover:text-slate-300 transition-all">V003</button>
              </div>
            </div>
          </ComponentExample>

          <ComponentExample name="Links & Inline Actions" code={`<a href="#" className="text-brand hover:underline font-bold">View Profile</a>
<button className="text-subtle hover:text-white flex items-center gap-1">
  History <RotateCcw size={12}/>
</button>`}>
            <div className="flex items-center gap-10">
              <a href="#" className="text-indigo-400 hover:text-indigo-300 font-bold text-sm border-b border-indigo-400/30 hover:border-indigo-300 transition-all flex items-center gap-1.5">
                Primary Text Link <ExternalLink size={13}/>
              </a>
              <button className="text-slate-500 hover:text-slate-200 text-xs font-bold uppercase tracking-widest flex items-center gap-2 transition-all">
                <History size={14}/> View Audit History
              </button>
              <button className="text-emerald-500 hover:text-emerald-400 text-sm font-bold flex items-center gap-1">
                Resolved <CheckCheck size={14}/>
              </button>
            </div>
          </ComponentExample>
        </Section>

        {/* 4. CORE PATTERNS (KPIs, Grids, Layouts) */}
        <Section title="Core Patterns" description="Complex compositions extracted from benchmark screens like Quote Intake and Comparison Matrix.">
          
          <ComponentExample name="KPI Scorecard (High Fidelity)" code={`<div className="rounded-xl border p-5 bg-surface">
  <label className="text-[11px] font-black text-muted uppercase">Confidence</label>
  <div className="text-4xl font-black text-success mt-2">92%</div>
  <div className="progress-bar mt-4" />
</div>`}>
            <div className="grid grid-cols-2 gap-6 max-w-2xl">
              <div className="rounded-2xl border p-6 bg-surface shadow-2xl" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
                <div style={{ fontSize: 11, fontWeight: 800, color: "var(--app-text-subtle)", letterSpacing: "0.1em", textTransform: "uppercase", marginBottom: 16 }}>AI Content Confidence</div>
                <div className="flex items-end gap-2 mb-4">
                  <div style={{ fontSize: 48, fontWeight: 900, color: "var(--app-success)", letterSpacing: "-0.05em", lineHeight: 1 }}>94%</div>
                  <div className="mb-1 text-emerald-500/50 font-black text-sm uppercase">Secure</div>
                </div>
                <div className="h-2 w-full bg-slate-900 rounded-full overflow-hidden mb-4 border border-white/5">
                  <div className="h-full bg-gradient-to-r from-emerald-600 to-emerald-400 w-[94%] rounded-full shadow-[0_0_10px_rgba(16,185,129,0.3)]" />
                </div>
                <p style={{ fontSize: 12, color: "var(--app-text-muted)", lineHeight: 1.6 }}>Statistical confidence interval based on neural extraction of 12 line items and 4 commercial terms.</p>
              </div>
            </div>
          </ComponentExample>

          <ComponentExample name="Pivot Table (Column Comparison)" code={`<table className="pivot-grid">
  <thead>
    <tr className="bg-dark text-white">
      <th>Attribute</th>
      <th>Vendor A</th>
      <th>Vendor B</th>
    </tr>
  </thead>
  ...
</table>`}>
            <div className="rounded-2xl border overflow-hidden shadow-2xl" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
              <table style={{ width: "100%", borderCollapse: "collapse" }}>
                <thead>
                  <tr style={{ background: "#090c14", borderBottom: "1px solid var(--app-border-strong)" }}>
                    <th style={{ padding: "20px", textAlign: "left", width: 240, borderRight: "1px solid var(--app-border-strong)", fontSize: 11, fontWeight: 900, color: "var(--app-brand-500)", textTransform: "uppercase", letterSpacing: "0.1em" }}>Comparison Data</th>
                    <th style={{ padding: "20px", textAlign: "center", minWidth: 200 }}>
                       <div className="text-white font-black text-sm">Apex Industrial</div>
                       <div className="text-[10px] text-slate-500 font-mono mt-1 uppercase">ID: V001</div>
                    </th>
                    <th style={{ padding: "20px", textAlign: "center", minWidth: 200 }}>
                       <div className="text-white font-black text-sm">GlobalPump Corp</div>
                       <div className="text-[10px] text-slate-500 font-mono mt-1 uppercase">ID: V003</div>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {[
                    { label: "Total Quote Value", val1: "$165,600", val2: "$152,400", b1: true },
                    { label: "SLA / Lead Time", val1: "45 Days", val2: "60 Days", b1: true },
                    { label: "Compliance Check", val1: "Verified", val2: "Flagged", b1: true, alert: true },
                  ].map((row) => (
                    <tr key={row.label} style={{ borderBottom: "1px solid var(--app-bg-elevated)" }}>
                      <td style={{ padding: "16px 20px", fontSize: 13, fontWeight: 700, color: "var(--app-text-main)", borderRight: "1px solid var(--app-border-strong)" }}>{row.label}</td>
                      <td style={{ padding: "16px 20px", textAlign: "center" }}>
                        <div className={`px-4 py-1.5 rounded-lg font-black text-xs border ${row.b1 ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-500' : 'bg-slate-800 border-white/5 text-slate-400'}`}>{row.val1}</div>
                      </td>
                      <td style={{ padding: "16px 20px", textAlign: "center" }}>
                        <div className={`px-4 py-1.5 rounded-lg font-black text-xs border ${row.alert ? 'bg-red-500/10 border-red-500/20 text-red-500' : 'bg-slate-800 border-white/5 text-slate-400'}`}>{row.val2}</div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </ComponentExample>

          <ComponentExample name="Structural Bodies (Queues & Sidebar)" code={`<div className="flex h-screen overflow-hidden">
  <aside className="w-64 border-r bg-surface">Queue</aside>
  <main className="flex-1 overflow-auto">Content</main>
  <aside className="w-80 border-l bg-surface shadow-2xl">Oversight</aside>
</div>`}>
            <div className="flex rounded-2xl border h-[500px] overflow-hidden bg-canvas relative shadow-2xl" style={{ borderColor: "var(--app-border-strong)" }}>
              {/* Mini Left Sidebar */}
              <div className="w-48 border-r bg-surface flex flex-col" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
                <div className="p-4 border-b font-black text-[10px] text-muted uppercase tracking-widest" style={{ borderColor: "var(--app-border-strong)" }}>Queue List</div>
                <div className="flex-1 p-2 space-y-1">
                  <div className="p-2.5 rounded-lg bg-blue-500/10 border border-blue-500/30 text-blue-400 font-bold text-xs">Active Record</div>
                  <div className="p-2.5 rounded-lg text-slate-500 font-bold text-xs opacity-50">Standard Item</div>
                </div>
              </div>
              {/* Mini Content Area */}
              <div className="flex-1 flex flex-col bg-canvas" style={{ background: "var(--app-bg-canvas)" }}>
                <div className="h-12 border-b bg-surface px-4 flex items-center justify-between" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
                  <div className="text-xs font-black text-slate-300 uppercase">Record Detail Header</div>
                  <div className="flex gap-2">
                    <div className="w-4 h-4 rounded-full bg-slate-800" />
                    <div className="w-12 h-4 rounded bg-indigo-600" />
                  </div>
                </div>
                <div className="flex-1 p-6 text-faint text-[10px] italic">Central data workspace scrollable area.</div>
              </div>
              {/* Mini Right Sidebar */}
              <div className="w-56 border-l bg-surface shadow-2xl flex flex-col" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
                <div className="p-4 border-b font-black text-[10px] text-indigo-400 uppercase tracking-widest bg-slate-900/50" style={{ borderColor: "var(--app-border-strong)" }}>Oversight</div>
                <div className="flex-1 p-4 space-y-4">
                  <div className="h-20 w-full rounded-lg bg-slate-900 border border-white/5" />
                  <div className="space-y-2">
                    <div className="h-3 w-full rounded bg-slate-800" />
                    <div className="h-3 w-2/3 rounded bg-slate-800" />
                  </div>
                </div>
                <div className="p-4 border-t" style={{ borderColor: "var(--app-border-strong)" }}>
                  <div className="h-8 w-full rounded bg-indigo-600" />
                </div>
              </div>
            </div>
          </ComponentExample>
        </Section>

      </div>
    </div>
  );
}
