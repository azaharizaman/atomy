import { useState } from "react";
import { useNavigate, useParams } from "react-router";
import {
  ArrowLeft, Check, ChevronDown, Download, ExternalLink,
  FileText, GitCompareArrows, Info, MoreHorizontal,
  Printer, Save, Share2, ShieldCheck, Star,
  TrendingDown, TrendingUp, Users, Zap, AlertTriangle,
  Search, Filter, SlidersHorizontal, ArrowUpDown,
  CheckCircle2, AlertCircle, Sparkles, Trophy, Activity,
  Plus
} from "lucide-react";
import { rfqs, vendors } from "../data/mockData";

// Enhanced mock data for comparison
const comparisonData = {
  rfqId: "RFQ-2024-001",
  attributes: [
    { id: "total_price", label: "Total Quote Value", type: "currency", weight: 0.4 },
    { id: "lead_time", label: "Lead Time (Weeks)", type: "number", weight: 0.2, inverse: true },
    { id: "quality_score", label: "Quality Rating", type: "score", weight: 0.2 },
    { id: "compliance", label: "Policy Compliance", type: "boolean", weight: 0.1 },
    { id: "risk_score", label: "Risk Index", type: "score", weight: 0.1, inverse: true },
  ],
  vendorResults: [
    {
      vendorId: "V001",
      name: "Apex Industrial Solutions",
      totalPrice: 398200,
      leadTime: 4,
      qualityScore: 92,
      compliance: true,
      riskScore: 15,
      overallScore: 94,
      recommended: true,
      insights: ["Lowest TCO", "Superior SLA history", "Local support available"],
      lineItems: [
        { id: "L1", name: "High-Pressure Pump Unit", price: 245000, qty: 1 },
        { id: "L2", name: "Installation Kit", price: 12500, qty: 1 },
        { id: "L3", name: "3-Year Maintenance", price: 140700, qty: 1 },
      ]
    },
    {
      vendorId: "V003",
      name: "GlobalPump Corp",
      totalPrice: 412000,
      leadTime: 6,
      qualityScore: 88,
      compliance: false,
      riskScore: 42,
      overallScore: 71,
      recommended: false,
      insights: ["Premium pricing", "Compliance warning (Sanctions)", "Extended lead time"],
      lineItems: [
        { id: "L1", name: "High-Pressure Pump Unit", price: 260000, qty: 1 },
        { id: "L2", name: "Installation Kit", price: 15000, qty: 1 },
        { id: "L3", name: "3-Year Maintenance", price: 137000, qty: 1 },
      ]
    },
    {
      vendorId: "V007",
      name: "Summit Flow Systems",
      totalPrice: 425500,
      leadTime: 3,
      qualityScore: 95,
      compliance: true,
      riskScore: 8,
      overallScore: 89,
      recommended: false,
      insights: ["Fastest delivery", "Highest quality rating", "Above budget"],
      lineItems: [
        { id: "L1", name: "High-Pressure Pump Unit", price: 255000, qty: 1 },
        { id: "L2", name: "Installation Kit", price: 18000, qty: 1 },
        { id: "L3", name: "3-Year Maintenance", price: 152500, qty: 1 },
      ]
    }
  ]
};

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
  const [selectedVendors, setSelectedVendors] = useState<string[]>(comparisonData.vendorResults.map(v => v.vendorId));

  const rfq = rfqs.find(r => r.id === (id || comparisonData.rfqId)) || rfqs[0];

  const budgetNum = parseInt(rfq.budget.replace(/[^0-9]/g, ''));
  const recommendedVendor = comparisonData.vendorResults.find(v => v.recommended);

  return (
    <div className="flex flex-col min-h-screen" style={{ background: "var(--app-bg-canvas)" }}>
      {/* Header */}
      <div className="border-b px-6 py-4 sticky top-0 z-10" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
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
                <span style={{ fontSize: 11, color: "var(--app-text-muted)" }}>Quote Comparison</span>
              </div>
              <h1 style={{ fontSize: 18, fontWeight: 700, color: "var(--app-text-strong)", letterSpacing: "-0.01em" }}>{rfq.title}</h1>
            </div>
          </div>

          <div className="flex items-center gap-2">
            <button className="flex items-center gap-2 rounded-lg px-3 py-2 transition-colors" style={{ fontSize: 13, color: "var(--app-text-subtle)", background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)" }}>
              <Printer size={14} /> Print
            </button>
            <button className="flex items-center gap-2 rounded-lg px-3 py-2 transition-colors" style={{ fontSize: 13, color: "var(--app-text-subtle)", background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)" }}>
              <Download size={14} /> Export
            </button>
            <div className="w-px h-6 mx-1" style={{ background: "var(--app-border-strong)" }} />
            <button className="flex items-center gap-2 rounded-lg px-4 py-2 transition-opacity hover:opacity-90" style={{ fontSize: 13, fontWeight: 600, background: "var(--app-brand-600)", color: "white" }}>
              <Trophy size={14} /> Award Contract
            </button>
          </div>
        </div>

        {/* Filters/Toolbar */}
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-4">
            <div className="flex rounded-lg overflow-hidden border" style={{ borderColor: "var(--app-border-strong)" }}>
              <button
                onClick={() => setView("grid")}
                className="px-3 py-1.5 transition-colors"
                style={{ background: view === "grid" ? "var(--app-brand-600)" : "var(--app-bg-elevated)", color: view === "grid" ? "white" : "var(--app-text-muted)", fontSize: 12 }}
              >
                Comparison Grid
              </button>
              <button
                onClick={() => setView("list")}
                className="px-3 py-1.5 transition-colors"
                style={{ background: view === "list" ? "var(--app-brand-600)" : "var(--app-bg-elevated)", color: view === "list" ? "white" : "var(--app-text-muted)", fontSize: 12 }}
              >
                Line Item Detail
              </button>
            </div>
            <div className="flex items-center gap-2 text-xs text-muted">
              <span style={{ color: "var(--app-text-faint)" }}>Comparing {selectedVendors.length} of {comparisonData.vendorResults.length} vendors</span>
            </div>
          </div>
          <div className="flex items-center gap-3">
             <div className="relative">
                <Search size={14} className="absolute left-2.5 top-1/2 -translate-y-1/2" style={{ color: "var(--app-text-faint)" }} />
                <input
                  type="text"
                  placeholder="Find attribute..."
                  className="rounded-lg pl-8 pr-3 py-1.5 border outline-none transition-all focus:border-indigo-500"
                  style={{ background: "var(--app-bg-canvas)", borderColor: "var(--app-border-strong)", fontSize: 12, color: "var(--app-text-main)", width: 200 }}
                />
             </div>
             <button className="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 border" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)", color: "var(--app-text-subtle)", fontSize: 12 }}>
                <Filter size={14} /> Filters
             </button>
             <button className="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 border" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)", color: "var(--app-text-subtle)", fontSize: 12 }}>
                <SlidersHorizontal size={14} /> Scenarios
             </button>
          </div>
        </div>
      </div>

      <div className="flex-1 overflow-auto p-6 space-y-6">
        {/* Top Summary Cards */}
        <div className="grid grid-cols-12 gap-4">
          <div className="col-span-3 rounded-xl border p-4 flex flex-col justify-between" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
            <div>
               <div className="flex items-center justify-between mb-2">
                 <span style={{ fontSize: 11, fontWeight: 600, color: "var(--app-text-muted)", textTransform: "uppercase", letterSpacing: "0.05em" }}>Best Price</span>
                 <TrendingDown size={14} style={{ color: "var(--app-success)" }} />
               </div>
               <div style={{ fontSize: 24, fontWeight: 700, color: "var(--app-text-strong)" }}>$398,200</div>
               <div style={{ fontSize: 12, color: "var(--app-success)", marginTop: 2 }}>5.2% below budget</div>
            </div>
            <div className="mt-4 pt-4 border-t" style={{ borderColor: "var(--app-border-strong)" }}>
               <span style={{ fontSize: 11, color: "var(--app-text-subtle)" }}>Budget: {rfq.budget}</span>
            </div>
          </div>

          <div className="col-span-6 rounded-xl border p-4 relative overflow-hidden" style={{ background: "var(--app-brand-600)", borderColor: "var(--app-brand-400)" }}>
            <div className="relative z-10 flex h-full">
              <div className="flex-1">
                <div className="flex items-center gap-2 mb-2">
                  <div className="rounded-full bg-white/20 p-1">
                    <Sparkles size={12} className="text-white" />
                  </div>
                  <span style={{ fontSize: 11, fontWeight: 600, color: "rgba(255,255,255,0.8)", textTransform: "uppercase", letterSpacing: "0.05em" }}>AI Recommendation</span>
                </div>
                <h3 className="text-white font-bold text-lg mb-1">{recommendedVendor?.name}</h3>
                <p className="text-indigo-100 text-xs leading-relaxed max-w-md">
                  Offers the best balance of cost efficiency (lowest TCO) and proven reliability. Compliance risks are minimal compared to GlobalPump Corp.
                </p>
                <div className="flex items-center gap-3 mt-4">
                   <div className="flex items-center gap-1.5 px-2 py-1 rounded bg-white/10 border border-white/20">
                      <ShieldCheck size={12} className="text-white" />
                      <span className="text-white text-[10px] font-semibold">94% Confidence</span>
                   </div>
                   <div className="flex items-center gap-1.5 px-2 py-1 rounded bg-white/10 border border-white/20">
                      <Zap size={12} className="text-white" />
                      <span className="text-white text-[10px] font-semibold">Low Risk</span>
                   </div>
                </div>
              </div>
              <div className="flex-shrink-0 flex flex-col justify-end">
                 <button className="bg-white text-indigo-700 text-xs font-bold px-4 py-2 rounded-lg hover:bg-indigo-50 transition-colors shadow-lg">
                    View Logic
                 </button>
              </div>
            </div>
            {/* Abstract Background Element */}
            <div className="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -mr-20 -mt-20 blur-3xl pointer-events-none" />
            <div className="absolute bottom-0 left-0 w-32 h-32 bg-white/5 rounded-full -ml-10 -mb-10 blur-2xl pointer-events-none" />
          </div>

          <div className="col-span-3 rounded-xl border p-4 flex flex-col justify-between" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
            <div>
               <div className="flex items-center justify-between mb-2">
                 <span style={{ fontSize: 11, fontWeight: 600, color: "var(--app-text-muted)", textTransform: "uppercase", letterSpacing: "0.05em" }}>Average Score</span>
                 <Activity size={14} style={{ color: "var(--app-brand-400)" }} />
               </div>
               <div style={{ fontSize: 24, fontWeight: 700, color: "var(--app-text-strong)" }}>84.6<span style={{ fontSize: 14, color: "var(--app-text-faint)", marginLeft: 2 }}>/100</span></div>
               <div style={{ fontSize: 12, color: "var(--app-text-subtle)", marginTop: 2 }}>Across 3 vendors</div>
            </div>
            <div className="mt-4 pt-4 border-t" style={{ borderColor: "var(--app-border-strong)" }}>
               <div className="flex items-center gap-1.5">
                  <div className="flex -space-x-2">
                    {[1,2,3].map(i => <div key={i} className="w-5 h-5 rounded-full border-2 border-surface bg-slate-700 flex items-center justify-center text-[8px] font-bold text-white">V{i}</div>)}
                  </div>
                  <span style={{ fontSize: 11, color: "var(--app-text-subtle)" }}>+1 response pending</span>
               </div>
            </div>
          </div>
        </div>

        {/* Main Comparison Section */}
        <div className="rounded-xl border overflow-hidden" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
          <div className="overflow-x-auto">
            <table className="w-full border-collapse">
              <thead>
                <tr style={{ background: "var(--app-bg-elevated)", borderBottom: "1px solid var(--app-border-strong)" }}>
                  <th className="sticky left-0 z-20 p-4 text-left border-r" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)", width: 280 }}>
                    <div style={{ fontSize: 11, fontWeight: 600, color: "var(--app-text-muted)", textTransform: "uppercase", letterSpacing: "0.05em" }}>Comparison Attribute</div>
                  </th>
                  {comparisonData.vendorResults.map(v => (
                    <th key={v.vendorId} className="p-4 text-left min-w-[300px]" style={{ borderRight: "1px solid var(--app-border-strong)" }}>
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
                    {comparisonData.vendorResults.map(v => {
                       const val = (v as any)[attr.id === 'total_price' ? 'totalPrice' : attr.id === 'lead_time' ? 'leadTime' : attr.id === 'quality_score' ? 'qualityScore' : attr.id === 'risk_score' ? 'riskScore' : attr.id];
                       // Simple heuristic for "best"
                       const allVals = comparisonData.vendorResults.map(vr => (vr as any)[attr.id === 'total_price' ? 'totalPrice' : attr.id === 'lead_time' ? 'leadTime' : attr.id === 'quality_score' ? 'qualityScore' : attr.id === 'risk_score' ? 'riskScore' : attr.id]);
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
                  {comparisonData.vendorResults.map(v => (
                    <td key={v.vendorId} className="p-4" style={{ borderRight: "1px solid var(--app-border-strong)" }}>
                      <ul className="space-y-1.5">
                        {v.insights.map((insight, i) => (
                          <li key={i} className="flex items-start gap-1.5">
                            <div className="mt-1 flex-shrink-0">
                               {insight.toLowerCase().includes("warning") || insight.toLowerCase().includes("above") ?
                                 <AlertCircle size={10} style={{ color: "var(--app-danger)" }} /> :
                                 <CheckCircle2 size={10} style={{ color: "var(--app-success)" }} />
                               }
                            </div>
                            <span style={{ fontSize: 11, color: "var(--app-text-subtle)", lineHeight: 1.4 }}>{insight}</span>
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

        {/* Line Items Detail (Visible if toggled or below) */}
        <div className="space-y-4">
           <div className="flex items-center justify-between">
              <h3 style={{ fontSize: 16, fontWeight: 600, color: "var(--app-text-strong)" }}>Line Item Breakdown</h3>
              <div className="flex items-center gap-2">
                 <button className="text-xs px-2 py-1 rounded hover:bg-slate-800" style={{ color: "var(--app-brand-500)" }}>Expand All</button>
                 <button className="text-xs px-2 py-1 rounded hover:bg-slate-800" style={{ color: "var(--app-text-muted)" }}>Export XLSX</button>
              </div>
           </div>

           <div className="rounded-xl border overflow-hidden" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
              <table className="w-full border-collapse">
                 <thead>
                    <tr style={{ borderBottom: "1px solid var(--app-border-strong)", background: "var(--app-bg-elevated)" }}>
                       <th className="p-3 text-left" style={{ fontSize: 10, fontWeight: 600, color: "var(--app-text-subtle)", textTransform: "uppercase" }}>Description</th>
                       <th className="p-3 text-center" style={{ fontSize: 10, fontWeight: 600, color: "var(--app-text-subtle)", textTransform: "uppercase" }}>Qty</th>
                       {comparisonData.vendorResults.map(v => (
                         <th key={v.vendorId} className="p-3 text-right" style={{ fontSize: 10, fontWeight: 600, color: "var(--app-text-subtle)", textTransform: "uppercase" }}>{v.name.split(' ')[0]}</th>
                       ))}
                    </tr>
                 </thead>
                 <tbody>
                    {comparisonData.vendorResults[0].lineItems.map((item, idx) => (
                      <tr key={item.id} style={{ borderBottom: idx === 2 ? "none" : "1px solid var(--app-bg-canvas)" }}>
                        <td className="p-3">
                           <div style={{ fontSize: 13, color: "var(--app-text-main)", fontWeight: 500 }}>{item.name}</div>
                           <div style={{ fontSize: 10, color: "var(--app-text-faint)" }}>SKU: PUMP-00{idx+1}</div>
                        </td>
                        <td className="p-3 text-center" style={{ fontSize: 12, color: "var(--app-text-muted)" }}>{item.qty}</td>
                        {comparisonData.vendorResults.map(v => {
                           const vItem = v.lineItems.find(li => li.name === item.name);
                           const prices = comparisonData.vendorResults.map(vr => vr.lineItems.find(li => li.name === item.name)?.price || 0);
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
                       {comparisonData.vendorResults.map(v => (
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

        {/* Decision Trail / Notes */}
        <div className="grid grid-cols-2 gap-6">
           <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
              <h3 className="flex items-center gap-2 mb-4" style={{ fontSize: 14, fontWeight: 600, color: "var(--app-text-strong)" }}>
                 <GitCompareArrows size={16} /> Decision Narrative
              </h3>
              <div className="space-y-3">
                 <div className="p-3 rounded-lg border" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
                    <p style={{ fontSize: 12, color: "var(--app-text-subtle)", lineHeight: 1.6 }}>
                       "Apex Industrial is the clear choice for this requirement. While Summit Flow Systems offers a faster delivery by 1 week, the 7% price premium isn't justified given our current lead time buffer. GlobalPump's sanctions flag remains a critical blocker."
                    </p>
                    <div className="flex items-center gap-2 mt-3 pt-3 border-t" style={{ borderColor: "var(--app-border-strong)" }}>
                       <div className="w-5 h-5 rounded-full bg-slate-700" />
                       <span style={{ fontSize: 11, color: "var(--app-text-muted)" }}>Sarah Chen · Jan 19, 2024</span>
                    </div>
                 </div>
                 <button className="flex items-center gap-1.5 text-xs font-semibold" style={{ color: "var(--app-brand-500)" }}>
                    <Plus size={12} /> Add Analysis Note
                 </button>
              </div>
           </div>

           <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
              <h3 className="flex items-center gap-2 mb-4" style={{ fontSize: 14, fontWeight: 600, color: "var(--app-text-strong)" }}>
                 <Users size={16} /> Stakeholder Sentiment
              </h3>
              <div className="space-y-4">
                 {[
                    { name: "Engineering", status: "Approved", feedback: "Apex technical specs meet all Tier-1 requirements.", icon: CheckCircle2, color: "var(--app-success)" },
                    { name: "Compliance", status: "Flagged", feedback: "GlobalPump requires L3 manual review.", icon: AlertCircle, color: "var(--app-danger)" },
                    { name: "Finance", status: "Approved", feedback: "Within budget tolerance.", icon: CheckCircle2, color: "var(--app-success)" }
                 ].map(s => (
                   <div key={s.name} className="flex items-start gap-3">
                      <s.icon size={14} style={{ color: s.color, marginTop: 2 }} />
                      <div>
                         <div className="flex items-center gap-2">
                            <span style={{ fontSize: 12, fontWeight: 600, color: "var(--app-text-main)" }}>{s.name}</span>
                            <span style={{ fontSize: 10, color: s.color }}>{s.status}</span>
                         </div>
                         <p style={{ fontSize: 11, color: "var(--app-text-muted)", marginTop: 1 }}>{s.feedback}</p>
                      </div>
                   </div>
                 ))}
              </div>
           </div>
        </div>
      </div>
    </div>
  );
}
