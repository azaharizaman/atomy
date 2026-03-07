import React, { useState, useMemo } from "react";
import { useNavigate, useParams } from "react-router";
import {
  ArrowLeft, Check, ChevronDown, Download, ExternalLink,
  FileText, GitCompareArrows, Info, MoreHorizontal,
  Printer, Save, Share2, ShieldCheck, Star,
  TrendingDown, TrendingUp, Users, Zap, AlertTriangle,
  Search, Filter, SlidersHorizontal, ArrowUpDown,
  CheckCircle2, AlertCircle, Sparkles, Trophy, Activity,
  Plus, X, MousePointer2, ListFilter, Clock, Eye,
  Lock, MessageSquare, History, UserCheck, ShieldAlert
} from "lucide-react";
import { rfqs, vendors } from "../data/mockData";
import { largeComparisonData } from "../data/largeMockData";

// Enhanced mock data for comparison
const comparisonData = largeComparisonData;

const MetricBadge = ({ value, type, inverse = false, isBest = false }: any) => {
  let displayValue = value;
  let color = "var(--app-text-main)";
  let bg = "transparent";

  if (type === "currency") {
    displayValue = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(value);
  } else if (type === "score") {
    displayValue = `${value}/100`;
  } else if (type === "boolean") {
    displayValue = value ? "Compliant" : "Non-Compliant";
  }

  if (isBest) {
    color = "var(--app-success)";
    bg = "var(--app-success-tint-10)";
  }

  return (
    <div className="px-2 py-1 rounded text-center" style={{ color, background: bg, fontWeight: isBest ? 600 : 400 }}>
      {displayValue}
    </div>
  );
};

export function QuoteComparison() {
  const navigate = useNavigate();
  const { id } = useParams();
  const [view, setView] = useState<"grid" | "list">("grid");
  const [selectedVendors, setSelectedVendors] = useState<string[]>(comparisonData.vendorResults.map(v => v.vendorId).slice(0, 3));
  const [smartSelectOpen, setSmartSelectOpen] = useState(false);
  const [oversightSidebarOpen, setOversightSidebarOpen] = useState(true);

  const rfq = rfqs.find(r => r.id === (id || comparisonData.rfqId)) || rfqs[0];

  const filteredVendorResults = useMemo(() => {
    return comparisonData.vendorResults.filter(v => selectedVendors.includes(v.vendorId));
  }, [selectedVendors]);

  const recommendedVendor = comparisonData.vendorResults.find(v => v.recommended);
  const avgScore = Math.round(comparisonData.vendorResults.reduce((acc, v) => acc + v.overallScore, 0) / comparisonData.vendorResults.length);
  const complianceRate = Math.round((comparisonData.vendorResults.filter(v => v.compliance).length / comparisonData.vendorResults.length) * 100);

  const toggleVendor = (vendorId: string) => {
    setSelectedVendors(prev => 
      prev.includes(vendorId) ? prev.filter(id => id !== vendorId) : [...prev, vendorId]
    );
  };

  const handleSmartSelect = (type: string) => {
    let newSelection: string[] = [];
    switch(type) {
      case 'all': newSelection = comparisonData.vendorResults.map(v => v.vendorId); break;
      case 'best-price': 
        newSelection = [...comparisonData.vendorResults].sort((a, b) => a.totalPrice - b.totalPrice).slice(0, 3).map(v => v.vendorId); 
        break;
      case 'top-scoring': 
        newSelection = [...comparisonData.vendorResults].sort((a, b) => b.overallScore - a.overallScore).slice(0, 3).map(v => v.vendorId); 
        break;
      case 'recommended': 
        newSelection = recommendedVendor ? [recommendedVendor.vendorId] : []; 
        break;
      case 'fastest': 
        newSelection = [...comparisonData.vendorResults].sort((a, b) => a.leadTime - b.leadTime).slice(0, 3).map(v => v.vendorId); 
        break;
    }
    setSelectedVendors([...new Set(newSelection)]);
    setSmartSelectOpen(false);
  };

  return (
    <div className="flex flex-col h-screen" style={{ background: "var(--app-bg-canvas)" }}>
      {/* 1. OVERSIGHT ACTION HEADER (New Element) */}
      <div className="border-b px-6 py-2 flex items-center justify-between" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
         <div className="flex items-center gap-4">
            <div className="flex items-center gap-2 px-2 py-1 rounded bg-orange-500/10 border border-orange-500/20">
               <AlertCircle size={14} className="text-orange-500" />
               <span style={{ fontSize: 11, fontWeight: 700, color: "var(--app-warning-soft)" }}>HUMAN REVIEW REQUIRED</span>
            </div>
            <div className="flex items-center gap-1.5 text-[11px] text-muted">
               <Users size={12} style={{ color: "var(--app-text-faint)" }} />
               <span style={{ color: "var(--app-text-muted)" }}>Active Reviewers: </span>
               <div className="flex -space-x-1.5">
                  <div className="w-5 h-5 rounded-full border-2 border-surface bg-blue-600 flex items-center justify-center text-[8px] font-bold text-white">SC</div>
                  <div className="w-5 h-5 rounded-full border-2 border-surface bg-emerald-600 flex items-center justify-center text-[8px] font-bold text-white">DM</div>
               </div>
            </div>
         </div>
         <div className="flex items-center gap-3">
            <div className="flex items-center gap-2 px-3 py-1 rounded-full border" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
               <div className="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse" />
               <span style={{ fontSize: 10, fontWeight: 600, color: "var(--app-text-subtle)" }}>AI Comparison Synced</span>
            </div>
            <button className="text-[11px] font-bold text-indigo-400 hover:underline">View Decision History</button>
         </div>
      </div>

      {/* Main Header */}
      <div className="border-b px-6 py-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-3">
            <button
              onClick={() => navigate(-1)}
              className="p-1.5 rounded-lg transition-colors hover:bg-gray-800"
              style={{ color: "var(--app-text-subtle)" }}
            >
              <ArrowLeft size={18} />
            </button>
            <div>
              <div className="flex items-center gap-2 mb-0.5">
                <span style={{ fontSize: 11, fontFamily: "monospace", color: "var(--app-brand-500)", fontWeight: 600 }}>{rfq.id}</span>
                <span style={{ fontSize: 11, color: "var(--app-text-faint)" }}>/</span>
                <span style={{ fontSize: 11, color: "var(--app-text-muted)" }}>Comparison Matrix</span>
              </div>
              <h1 style={{ fontSize: 18, fontWeight: 700, color: "var(--app-text-strong)", letterSpacing: "-0.01em" }}>{rfq.title}</h1>
            </div>
          </div>

          <div className="flex items-center gap-2">
            <button 
              onClick={() => setOversightSidebarOpen(!oversightSidebarOpen)}
              className={`flex items-center gap-2 rounded-lg px-3 py-2 transition-all border ${oversightSidebarOpen ? 'bg-indigo-500/10 border-indigo-500/50 text-indigo-400' : 'bg-slate-800/50 border-slate-700 text-slate-400'}`} style={{ fontSize: 13, fontWeight: 600 }}>
              <UserCheck size={14} /> Oversight Panel
            </button>
            <div className="w-px h-6 mx-1" style={{ background: "var(--app-border-strong)" }} />
            <button className="flex items-center gap-2 rounded-lg px-3 py-2 transition-colors" style={{ fontSize: 13, color: "var(--app-text-subtle)", background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)" }}>
              <Download size={14} /> Export
            </button>
            <button className="flex items-center gap-2 rounded-lg px-4 py-2 transition-opacity hover:opacity-90" style={{ fontSize: 13, fontWeight: 600, background: "var(--app-brand-600)", color: "white" }}>
              <Trophy size={14} /> Award Contract
            </button>
          </div>
        </div>

        {/* Vendor Selection Bar */}
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="flex items-center gap-2 bg-slate-900/50 p-1 rounded-lg border border-white/5">
               <div className="relative">
                  <button 
                    onClick={() => setSmartSelectOpen(!smartSelectOpen)}
                    className="flex items-center gap-2 px-3 py-1.5 rounded-md hover:bg-slate-800 transition-colors" style={{ background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", color: "var(--app-brand-400)", fontSize: 12, fontWeight: 600 }}>
                    <Sparkles size={13} /> Smart Selection <ChevronDown size={12} />
                  </button>
                  {smartSelectOpen && (
                    <div className="absolute left-0 top-full mt-1 w-56 rounded-lg border shadow-2xl z-50 py-1 overflow-hidden" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
                       {[
                         { id: 'recommended', label: 'AI Recommended', icon: Zap },
                         { id: 'best-price', label: 'Best Price Comparison', icon: TrendingDown },
                         { id: 'top-scoring', label: 'Best Overall Scoring', icon: Trophy },
                         { id: 'fastest', label: 'Fastest Delivery', icon: Clock },
                         { id: 'all', label: 'Compare All (4)', icon: GitCompareArrows },
                       ].map(opt => (
                         <button key={opt.id} onClick={() => handleSmartSelect(opt.id)} className="w-full flex items-center gap-2.5 px-3 py-2.5 hover:bg-slate-800 text-left transition-colors" style={{ fontSize: 12, color: "var(--app-text-main)" }}>
                            <opt.icon size={13} style={{ color: "var(--app-brand-500)" }} />
                            {opt.label}
                         </button>
                       ))}
                    </div>
                  )}
               </div>
               <div className="w-px h-4 mx-1 bg-white/10" />
               <div className="flex items-center gap-1.5 px-2">
                  {comparisonData.vendorResults.map(v => (
                    <button 
                      key={v.vendorId} 
                      onClick={() => toggleVendor(v.vendorId)}
                      className={`px-2 py-1 rounded text-[10px] font-bold border transition-all ${selectedVendors.includes(v.vendorId) ? 'bg-indigo-500/20 border-indigo-500/50 text-indigo-400' : 'bg-slate-800/50 border-slate-700 text-slate-500'}`}
                    >
                      {v.name.split(' ')[0]}
                    </button>
                  ))}
               </div>
            </div>
          </div>
          <div className="flex items-center gap-3">
             <div className="flex rounded-lg overflow-hidden border" style={{ borderColor: "var(--app-border-strong)" }}>
              <button
                onClick={() => setView("grid")}
                className="px-3 py-1.5 transition-colors"
                style={{ background: view === "grid" ? "var(--app-brand-600)" : "var(--app-bg-elevated)", color: view === "grid" ? "white" : "var(--app-text-muted)", fontSize: 12 }}
              >
                Grid
              </button>
              <button
                onClick={() => setView("list")}
                className="px-3 py-1.5 transition-colors"
                style={{ background: view === "list" ? "var(--app-brand-600)" : "var(--app-bg-elevated)", color: view === "list" ? "white" : "var(--app-text-muted)", fontSize: 12 }}
              >
                Items
              </button>
            </div>
             <button className="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 border" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)", color: "var(--app-text-subtle)", fontSize: 12 }}>
                <SlidersHorizontal size={14} /> Weights
             </button>
          </div>
        </div>
      </div>

      {/* BODY AREA: Content + Sidebar */}
      <div className="flex-1 flex overflow-hidden">
        {/* Main Content Scrollable */}
        <div className="flex-1 overflow-auto p-6 space-y-6">
          {/* Top Summary Cards */}
          <div className="grid grid-cols-6 gap-4">
            <div className="col-span-2 rounded-xl border p-5 relative overflow-hidden flex flex-col justify-between" style={{ background: "var(--app-brand-600)", borderColor: "var(--app-brand-400)" }}>
              <div className="relative z-10">
                <div className="flex items-center gap-2 mb-2">
                  <Sparkles size={12} className="text-white" />
                  <span style={{ fontSize: 10, fontWeight: 700, color: "rgba(255,255,255,0.9)", textTransform: "uppercase", letterSpacing: "0.05em" }}>AI Recommendation</span>
                </div>
                <h3 className="text-white font-bold text-lg mb-1 leading-tight">{recommendedVendor?.name}</h3>
                <p className="text-indigo-100 text-[10px] leading-relaxed mb-3 opacity-80">Lowest TCO with superior SLA history.</p>
                
                <div className="grid grid-cols-2 gap-2 pt-2 border-t border-white/10">
                   <div>
                      <div className="text-[9px] text-white/60 uppercase font-bold">Price Index</div>
                      <div className="text-xs font-bold text-white">{recommendedVendor?.metrics.priceIndex} <span className="text-[8px] font-normal text-green-300">(-6%)</span></div>
                   </div>
                   <div>
                      <div className="text-[9px] text-white/60 uppercase font-bold">Quality</div>
                      <div className="text-xs font-bold text-white">{recommendedVendor?.metrics.qualityRank}</div>
                   </div>
                   <div>
                      <div className="text-[9px] text-white/60 uppercase font-bold">Risk</div>
                      <div className="text-xs font-bold text-white">{recommendedVendor?.metrics.riskLevel}</div>
                   </div>
                   <div>
                      <div className="text-[9px] text-white/60 uppercase font-bold">Conf.</div>
                      <div className="text-xs font-bold text-white">94%</div>
                   </div>
                </div>
              </div>
              <div className="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 blur-2xl pointer-events-none" />
            </div>

            <div className="rounded-xl border p-4 flex flex-col justify-between" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
              <div>
                 <div className="flex items-center justify-between mb-2">
                   <span style={{ fontSize: 10, fontWeight: 600, color: "var(--app-text-muted)", textTransform: "uppercase", letterSpacing: "0.05em" }}>Best Price</span>
                   <TrendingDown size={14} style={{ color: "var(--app-success)" }} />
                 </div>
                 <div style={{ fontSize: 20, fontWeight: 700, color: "var(--app-text-strong)" }}>$398,200</div>
                 <div style={{ fontSize: 11, color: "var(--app-success)", marginTop: 2 }}>5.2% below budget</div>
              </div>
              <div className="mt-2 pt-2 border-t" style={{ borderColor: "var(--app-border-strong)" }}>
                 <span style={{ fontSize: 10, color: "var(--app-text-faint)" }}>Target: $420K</span>
              </div>
            </div>

            <div className="rounded-xl border p-4 flex flex-col justify-between" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
              <div>
                 <div className="flex items-center justify-between mb-2">
                   <span style={{ fontSize: 10, fontWeight: 600, color: "var(--app-text-muted)", textTransform: "uppercase", letterSpacing: "0.05em" }}>Fastest</span>
                   <Clock size={14} style={{ color: "var(--app-brand-400)" }} />
                 </div>
                 <div style={{ fontSize: 20, fontWeight: 700, color: "var(--app-text-strong)" }}>3 Weeks</div>
                 <div style={{ fontSize: 11, color: "var(--app-text-muted)", marginTop: 2 }}>Summit Flow</div>
              </div>
              <div className="mt-2 pt-2 border-t" style={{ borderColor: "var(--app-border-strong)" }}>
                 <span style={{ fontSize: 10, color: "var(--app-text-faint)" }}>Avg: 4.5 weeks</span>
              </div>
            </div>

            <div className="rounded-xl border p-4 flex flex-col justify-between" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
              <div>
                 <div className="flex items-center justify-between mb-2">
                   <span style={{ fontSize: 10, fontWeight: 600, color: "var(--app-text-muted)", textTransform: "uppercase", letterSpacing: "0.05em" }}>Compliance</span>
                   <ShieldCheck size={14} style={{ color: "var(--app-success)" }} />
                 </div>
                 <div style={{ fontSize: 20, fontWeight: 700, color: "var(--app-text-strong)" }}>{complianceRate}%</div>
                 <div style={{ fontSize: 11, color: "var(--app-success)", marginTop: 2 }}>3 of 4 vendors pass</div>
              </div>
              <div className="mt-2 pt-2 border-t" style={{ borderColor: "var(--app-border-strong)" }}>
                 <span style={{ fontSize: 10, color: "var(--app-text-faint)" }}>1 vendor flagged</span>
              </div>
            </div>

            <div className="rounded-xl border p-4 flex flex-col justify-between" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
              <div>
                 <div className="flex items-center justify-between mb-2">
                   <span style={{ fontSize: 10, fontWeight: 600, color: "var(--app-text-muted)", textTransform: "uppercase", letterSpacing: "0.05em" }}>Avg Score</span>
                   <Activity size={14} style={{ color: "var(--app-brand-400)" }} />
                 </div>
                 <div style={{ fontSize: 20, fontWeight: 700, color: "var(--app-text-strong)" }}>{avgScore}<span style={{ fontSize: 12, color: "var(--app-text-faint)", marginLeft: 2 }}>/100</span></div>
                 <div style={{ fontSize: 11, color: "var(--app-text-muted)", marginTop: 2 }}>Across bidders</div>
              </div>
              <div className="mt-2 pt-2 border-t" style={{ borderColor: "var(--app-border-strong)" }}>
                 <span style={{ fontSize: 10, color: "var(--app-text-faint)" }}>Range: 71 — 94</span>
              </div>
            </div>
          </div>

          {/* Main Comparison Section */}
          <div className="rounded-xl border" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)", overflow: "hidden" }}>
            <div className="overflow-auto" style={{ maxHeight: "600px" }}>
              <table className="w-full border-collapse">
                <thead>
                  <tr style={{ background: "var(--app-bg-elevated)", borderBottom: "1px solid var(--app-border-strong)" }}>
                    <th className="sticky top-0 left-0 z-50 p-4 text-left border-r" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)", width: 280 }}>
                      <div style={{ fontSize: 11, fontWeight: 600, color: "var(--app-text-muted)", textTransform: "uppercase", letterSpacing: "0.05em" }}>Comparison Attribute</div>
                    </th>
                    {filteredVendorResults.map(v => (
                      <th key={v.vendorId} className="sticky top-0 z-30 p-4 text-left min-w-[300px]" style={{ background: "var(--app-bg-elevated)", borderRight: "1px solid var(--app-border-strong)" }}>
                        <div className="flex items-start justify-between">
                          <div>
                            <div className="flex items-center gap-2 mb-1">
                              <h4 style={{ fontSize: 14, fontWeight: 700, color: "var(--app-text-strong)" }}>{v.name}</h4>
                              {v.recommended && (
                                 <div className="rounded-full p-1" style={{ background: "var(--app-brand-600)" }}>
                                   <Star size={10} fill="white" className="text-white" />
                                 </div>
                              )}
                            </div>
                            <div style={{ fontSize: 11, color: "var(--app-text-subtle)" }}>Vendor ID: {v.vendorId}</div>
                          </div>
                          <div className="flex flex-col items-end">
                             <div style={{ fontSize: 18, fontWeight: 800, color: v.overallScore > 80 ? "var(--app-success)" : v.overallScore > 60 ? "var(--app-warning)" : "var(--app-danger)" }}>
                                {v.overallScore}
                             </div>
                             <div style={{ fontSize: 9, fontWeight: 600, color: "var(--app-text-faint)", textTransform: "uppercase" }}>Overall</div>
                          </div>
                        </div>
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {comparisonData.attributes.map((attr, idx) => (
                    <tr key={attr.id} style={{ borderBottom: idx === comparisonData.attributes.length - 1 ? "none" : "1px solid var(--app-bg-canvas)" }}>
                      <td className="sticky left-0 z-20 p-4 border-r font-medium" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)", fontSize: 13, color: "var(--app-text-main)" }}>
                        <div className="flex items-center gap-2">
                          {attr.label}
                          <Info size={12} style={{ color: "var(--app-text-faint)" }} />
                        </div>
                        <div style={{ fontSize: 10, color: "var(--app-text-faint)", marginTop: 2 }}>Weight: {attr.weight * 100}%</div>
                      </td>
                      {filteredVendorResults.map(v => {
                         const val = (v as any)[attr.id === 'total_price' ? 'totalPrice' : attr.id === 'lead_time' ? 'leadTime' : attr.id === 'quality_score' ? 'qualityScore' : attr.id === 'risk_score' ? 'riskScore' : attr.id];
                         const allVals = filteredVendorResults.map(vr => (vr as any)[attr.id === 'total_price' ? 'totalPrice' : attr.id === 'lead_time' ? 'leadTime' : attr.id === 'quality_score' ? 'qualityScore' : attr.id === 'risk_score' ? 'riskScore' : attr.id]);
                         const bestVal = attr.inverse ? Math.min(...allVals) : (typeof val === 'boolean' ? true : Math.max(...allVals));
                         const isBest = val === bestVal;

                         return (
                          <td key={v.vendorId} className="p-4" style={{ borderRight: "1px solid var(--app-border-strong)" }}>
                             <MetricBadge value={val} type={attr.type} inverse={attr.inverse} isBest={isBest} />
                          </td>
                         );
                      })}
                    </tr>
                  ))}

                  {/* AI Insights Row */}
                  <tr style={{ background: "var(--app-bg-canvas)" }}>
                    <td className="sticky left-0 z-20 p-4 border-r" style={{ background: "var(--app-bg-canvas)", borderColor: "var(--app-border-strong)" }}>
                       <div className="flex items-center gap-2" style={{ fontSize: 11, fontWeight: 700, color: "var(--app-brand-500)", textTransform: "uppercase" }}>
                          <Sparkles size={12} /> Key Insights
                       </div>
                    </td>
                    {filteredVendorResults.map(v => (
                      <td key={v.vendorId} className="p-4" style={{ borderRight: "1px solid var(--app-border-strong)" }}>
                        <ul className="space-y-1.5">
                          {v.insights.map((insight, i) => (
                            <li key={i} className="flex items-start justify-between gap-1.5 group">
                              <div className="flex items-start gap-1.5">
                                 <div className="mt-1 flex-shrink-0">
                                    {insight.toLowerCase().includes("warning") || insight.toLowerCase().includes("above") ?
                                      <AlertCircle size={10} style={{ color: "var(--app-danger)" }} /> :
                                      <CheckCircle2 size={10} style={{ color: "var(--app-success)" }} />
                                    }
                                 </div>
                                 <span style={{ fontSize: 11, color: "var(--app-text-subtle)", lineHeight: 1.4 }}>{insight}</span>
                              </div>
                              <button className="opacity-0 group-hover:opacity-100 p-0.5 rounded hover:bg-slate-700 transition-all">
                                 <Check size={10} className="text-emerald-500" />
                              </button>
                            </li>
                          ))}
                        </ul>
                      </td>
                    ))}
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          {/* Line Items Detail */}
          <div className="space-y-4 pb-10">
             <div className="flex items-center justify-between">
                <h3 style={{ fontSize: 16, fontWeight: 600, color: "var(--app-text-strong)" }}>Line Item Breakdown</h3>
                <div className="flex items-center gap-2">
                   <button className="text-xs px-2 py-1 rounded hover:bg-slate-800" style={{ color: "var(--app-brand-500)" }}>Expand All</button>
                   <button className="text-xs px-2 py-1 rounded hover:bg-slate-800" style={{ color: "var(--app-text-muted)" }}>Export XLSX</button>
                </div>
             </div>

             <div className="rounded-xl border" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)", overflow: "hidden" }}>
                <div className="overflow-auto" style={{ maxHeight: "600px" }}>
                  <table className="w-full border-collapse">
                    <thead>
                        <tr style={{ borderBottom: "1px solid var(--app-border-strong)", background: "var(--app-bg-elevated)" }}>
                          <th className="sticky top-0 z-30 p-3 text-left" style={{ background: "var(--app-bg-elevated)", fontSize: 10, fontWeight: 600, color: "var(--app-text-subtle)", textTransform: "uppercase" }}>Description</th>
                          <th className="sticky top-0 z-30 p-3 text-center" style={{ background: "var(--app-bg-elevated)", fontSize: 10, fontWeight: 600, color: "var(--app-text-subtle)", textTransform: "uppercase" }}>Qty</th>
                          {filteredVendorResults.map(v => (
                            <th key={v.vendorId} className="sticky top-0 z-30 p-3 text-right" style={{ background: "var(--app-bg-elevated)", fontSize: 10, fontWeight: 600, color: "var(--app-text-subtle)", textTransform: "uppercase" }}>{v.name.split(' ')[0]}</th>
                          ))}
                        </tr>
                    </thead>
                   <tbody>
                      {comparisonData.vendorResults[0].lineItems.map((item, idx) => (
                        <tr key={item.id} style={{ borderBottom: idx === comparisonData.vendorResults[0].lineItems.length - 1 ? "none" : "1px solid var(--app-bg-canvas)" }}>
                          <td className="p-3">
                             <div style={{ fontSize: 13, color: "var(--app-text-main)", fontWeight: 500 }}>{item.name}</div>
                             <div style={{ fontSize: 10, color: "var(--app-text-faint)" }}>SKU: PUMP-00{idx+1}</div>
                          </td>
                          <td className="p-3 text-center" style={{ fontSize: 12, color: "var(--app-text-muted)" }}>{item.qty}</td>
                          {filteredVendorResults.map(v => {
                             const vItem = v.lineItems.find(li => li.name === item.name);
                             const prices = filteredVendorResults.map(vr => vr.lineItems.find(li => li.name === item.name)?.price || 0);
                             const isLowest = vItem?.price === Math.min(...prices);

                             return (
                               <td key={v.vendorId} className="p-3 text-right">
                                  <div style={{ fontSize: 13, color: isLowest ? "var(--app-success)" : "var(--app-text-main)", fontWeight: isLowest ? 600 : 400, fontFamily: "monospace" }}>
                                     {vItem ? `$${vItem.price.toLocaleString()}` : "—"}
                                  </div>
                               </td>
                             );
                          })}
                        </tr>
                      ))}
                   </tbody>
                   <tfoot>
                      <tr style={{ background: "var(--app-bg-elevated)", borderTop: "2px solid var(--app-border-strong)" }}>
                         <td className="p-4 font-bold" style={{ fontSize: 13, color: "var(--app-text-strong)" }}>Total Quote Value</td>
                         <td className="p-4"></td>
                         {filteredVendorResults.map(v => (
                           <td key={v.vendorId} className="p-4 text-right">
                              <div style={{ fontSize: 15, fontWeight: 700, color: "var(--app-text-strong)", fontFamily: "monospace" }}>
                                 ${v.totalPrice.toLocaleString()}
                              </div>
                           </td>
                         ))}
                      </tr>
                   </tfoot>
                </table>
             </div>
          </div>
        </div>
      </div>

      {/* 2. HUMAN OVERSIGHT SIDEBAR (Enhanced Visual Presence) */}
      {oversightSidebarOpen && (
        <div className="w-[380px] border-l flex flex-col shadow-2xl" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
           <div className="p-4 border-b flex items-center justify-between" style={{ borderColor: "var(--app-border-strong)", background: "var(--app-bg-elevated)" }}>
              <div className="flex items-center gap-2">
                 <UserCheck size={16} className="text-indigo-400" />
                 <h2 style={{ fontSize: 14, fontWeight: 700, color: "var(--app-text-strong)" }}>OVERSIGHT CONSOLE</h2>
              </div>
              <button onClick={() => setOversightSidebarOpen(false)} className="text-muted hover:text-white"><X size={16} /></button>
           </div>

           <div className="flex-1 overflow-auto p-4 space-y-6">
              {/* Section: Decision Verdict */}
              <div className="space-y-3">
                 <div className="flex items-center justify-between">
                    <label style={{ fontSize: 11, fontWeight: 700, color: "var(--app-text-muted)", textTransform: "uppercase", letterSpacing: "0.05em" }}>Human Verdict</label>
                    <span className="flex items-center gap-1 text-[10px] text-emerald-500 font-bold"><Lock size={10}/> DRAFT</span>
                 </div>
                 <div className="rounded-lg border p-3" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
                    <p style={{ fontSize: 12, color: "var(--app-text-subtle)", lineHeight: 1.6, marginBottom: 12 }}>
                       "Apex Industrial remains the strongest candidate. I've manually verified their local support capacity which AI scored as 'likely'. Summit is a viable fallback if lead times become the priority over cost."
                    </p>
                    <div className="flex items-center justify-between pt-3 border-t" style={{ borderColor: "var(--app-border-strong)" }}>
                       <div className="flex items-center gap-2">
                          <div className="w-6 h-6 rounded-full bg-slate-700" />
                          <span style={{ fontSize: 11, color: "var(--app-text-main)", fontWeight: 600 }}>Sarah Chen</span>
                       </div>
                       <button className="p-1 rounded hover:bg-slate-800"><History size={14} /></button>
                    </div>
                 </div>
                 <button className="w-full py-2 rounded-lg border border-dashed border-slate-700 text-[11px] font-bold text-slate-500 hover:text-indigo-400 hover:border-indigo-500/50 transition-all flex items-center justify-center gap-2">
                    <Plus size={12} /> Edit Decision Narrative
                 </button>
              </div>

              {/* Section: Verification Checklist */}
              <div className="space-y-3">
                 <label style={{ fontSize: 11, fontWeight: 700, color: "var(--app-text-muted)", textTransform: "uppercase", letterSpacing: "0.05em" }}>Oversight Checklist</label>
                 <div className="space-y-2">
                    {[
                      { label: "AI Data Extraction Verified", checked: true },
                      { label: "Vendor Sanctions Check Clear", checked: true },
                      { label: "Technical Specs Alignment", checked: false },
                      { label: "Budget Multi-year Impact Review", checked: false },
                    ].map((item, i) => (
                      <div key={i} className="flex items-center gap-3 p-2.5 rounded-lg border" style={{ background: item.checked ? 'var(--app-bg-elevated)' : 'transparent', borderColor: item.checked ? 'var(--app-border-strong)' : 'var(--app-border-strong)' }}>
                         <div className={`w-4 h-4 rounded border flex items-center justify-center transition-all ${item.checked ? 'bg-indigo-500 border-indigo-500' : 'border-slate-600'}`}>
                            {item.checked && <Check size={10} className="text-white" />}
                         </div>
                         <span style={{ fontSize: 12, color: item.checked ? 'var(--app-text-main)' : 'var(--app-text-muted)' }}>{item.label}</span>
                      </div>
                    ))}
                 </div>
              </div>

              {/* Section: Stakeholder Sentiment */}
              <div className="space-y-3">
                 <label style={{ fontSize: 11, fontWeight: 700, color: "var(--app-text-muted)", textTransform: "uppercase", letterSpacing: "0.05em" }}>Stakeholder Feedback</label>
                 <div className="space-y-3">
                    {[
                       { name: "Engineering", status: "Approved", feedback: "Apex technical specs meet all requirements.", icon: CheckCircle2, color: "var(--app-success)" },
                       { name: "Compliance", status: "Flagged", feedback: "GlobalPump requires L3 review.", icon: AlertCircle, color: "var(--app-danger)" },
                       { name: "Finance", status: "Approved", feedback: "Within budget tolerance.", icon: CheckCircle2, color: "var(--app-success)" }
                    ].map(s => (
                      <div key={s.name} className="p-3 rounded-lg border" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
                         <div className="flex items-center justify-between mb-1.5">
                            <span style={{ fontSize: 12, fontWeight: 700, color: "var(--app-text-strong)" }}>{s.name}</span>
                            <s.icon size={12} style={{ color: s.color }} />
                         </div>
                         <p style={{ fontSize: 11, color: "var(--app-text-muted)", lineHeight: 1.4 }}>{s.feedback}</p>
                      </div>
                    ))}
                 </div>
                 <button className="w-full py-2 rounded-lg bg-indigo-600 text-white text-[11px] font-bold shadow-lg shadow-indigo-500/20 flex items-center justify-center gap-2">
                    <MessageSquare size={12} /> Request Feedback
                 </button>
              </div>
           </div>

           <div className="p-4 border-t" style={{ borderColor: "var(--app-border-strong)", background: "var(--app-bg-elevated)" }}>
              <div className="flex items-center justify-between mb-4">
                 <div style={{ fontSize: 11, color: "var(--app-text-muted)" }}>Oversight Alignment</div>
                 <div style={{ fontSize: 11, fontWeight: 800, color: "var(--app-success)" }}>HIGH (92%)</div>
              </div>
              <div className="w-full h-1.5 rounded-full bg-slate-800 mb-6">
                 <div className="h-full rounded-full bg-emerald-500" style={{ width: "92%" }} />
              </div>
              <button className="w-full py-3 rounded-xl bg-indigo-600 text-white font-bold text-xs uppercase tracking-widest hover:bg-indigo-500 transition-all flex items-center justify-center gap-2">
                 <UserCheck size={14} /> Finalize Human Review
              </button>
           </div>
        </div>
      )}
    </div>

    {/* Click outside to close smart select */}
    {smartSelectOpen && <div className="fixed inset-0 z-40" onClick={() => setSmartSelectOpen(false)} />}
  </div>
  );
}
