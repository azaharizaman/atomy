import { useState } from "react";
import { useNavigate, useParams } from "react-router";
import {
  ArrowLeft, TrendingDown, TrendingUp, AlertTriangle, CheckCircle2,
  Star, Award, Shield, Clock, DollarSign, FileText, BarChart3,
  Zap, ChevronDown, Download, Send, Info, Minus
} from "lucide-react";
import { vendors, rfqs } from "../data/mockData";

interface VendorQuote {
  vendorId: string;
  vendorName: string;
  totalPrice: number;
  leadTime: number;
  warranty: number;
  paymentTerms: string;
  compliance: number;
  qualityScore: number;
  lineItems: {
    id: number;
    description: string;
    qty: number;
    uom: string;
    unitPrice: number;
    total: number;
  }[];
}

const mockQuotes: VendorQuote[] = [
  {
    vendorId: "V001",
    vendorName: "Apex Industrial Solutions",
    totalPrice: 165600,
    leadTime: 45,
    warranty: 24,
    paymentTerms: "Net 30",
    compliance: 98,
    qualityScore: 94,
    lineItems: [
      { id: 1, description: "Industrial Centrifugal Pump — 6\" Outlet", qty: 4, uom: "Units", unitPrice: 18200, total: 72800 },
      { id: 2, description: "Installation & Commissioning Service", qty: 8, uom: "Days", unitPrice: 4500, total: 36000 },
      { id: 3, description: "Spare Parts Kit (2-Year Supply)", qty: 1, uom: "Set", unitPrice: 24800, total: 24800 },
      { id: 4, description: "Annual Maintenance Contract", qty: 2, uom: "Years", unitPrice: 12600, total: 25200 },
      { id: 5, description: "Training Program — 2 Engineers", qty: 1, uom: "Program", unitPrice: 6800, total: 6800 },
    ],
  },
  {
    vendorId: "V003",
    vendorName: "GlobalPump Corp",
    totalPrice: 152400,
    leadTime: 60,
    warranty: 12,
    paymentTerms: "Net 45",
    compliance: 92,
    qualityScore: 88,
    lineItems: [
      { id: 1, description: "Centrifugal Pump System 6-inch", qty: 4, uom: "Units", unitPrice: 16800, total: 67200 },
      { id: 2, description: "On-site Installation", qty: 8, uom: "Days", unitPrice: 3900, total: 31200 },
      { id: 3, description: "Spare Components Package", qty: 1, uom: "Set", unitPrice: 21600, total: 21600 },
      { id: 4, description: "Maintenance Service Contract", qty: 2, uom: "Years", unitPrice: 11200, total: 22400 },
      { id: 5, description: "Technical Training", qty: 1, uom: "Program", unitPrice: 10000, total: 10000 },
    ],
  },
  {
    vendorId: "V002",
    vendorName: "TechFlow Dynamics",
    totalPrice: 178200,
    leadTime: 30,
    warranty: 36,
    paymentTerms: "Net 30",
    compliance: 96,
    qualityScore: 96,
    lineItems: [
      { id: 1, description: "Premium Industrial Pump — 6\" Capacity", qty: 4, uom: "Units", unitPrice: 19500, total: 78000 },
      { id: 2, description: "Professional Installation Services", qty: 8, uom: "Days", unitPrice: 5200, total: 41600 },
      { id: 3, description: "Extended Spare Parts Kit", qty: 1, uom: "Set", unitPrice: 28000, total: 28000 },
      { id: 4, description: "Premium Maintenance Plan", qty: 2, uom: "Years", unitPrice: 13800, total: 27600 },
      { id: 5, description: "Comprehensive Training Package", qty: 1, uom: "Program", unitPrice: 3000, total: 3000 },
    ],
  },
];

const criteriaWeights = {
  price: 40,
  leadTime: 20,
  warranty: 15,
  compliance: 15,
  quality: 10,
};

export function Comparison() {
  const navigate = useNavigate();
  const { id } = useParams();
  const rfq = rfqs.find(r => r.id === id) || rfqs[0];
  
  const [selectedVendors, setSelectedVendors] = useState<Set<string>>(new Set(["V001", "V003", "V002"]));
  const [showScoring, setShowScoring] = useState(false);

  const quotes = mockQuotes.filter((q) => selectedVendors.has(q.vendorId));

  // Calculate scores
  const calculateScore = (quote: VendorQuote) => {
    const minPrice = Math.min(...quotes.map(q => q.totalPrice));
    const maxPrice = Math.max(...quotes.map(q => q.totalPrice));
    const minLead = Math.min(...quotes.map(q => q.leadTime));
    const maxLead = Math.max(...quotes.map(q => q.leadTime));

    const priceScore = maxPrice === minPrice ? 100 : ((maxPrice - quote.totalPrice) / (maxPrice - minPrice)) * 100;
    const leadScore = maxLead === minLead ? 100 : ((maxLead - quote.leadTime) / (maxLead - minLead)) * 100;
    const warrantyScore = (quote.warranty / 36) * 100;

    const totalScore =
      (priceScore * criteriaWeights.price) / 100 +
      (leadScore * criteriaWeights.leadTime) / 100 +
      (warrantyScore * criteriaWeights.warranty) / 100 +
      (quote.compliance * criteriaWeights.compliance) / 100 +
      (quote.qualityScore * criteriaWeights.quality) / 100;

    return Math.round(totalScore);
  };

  const quotesWithScores = quotes.map((q) => ({ ...q, score: calculateScore(q) }));
  const rankedQuotes = [...quotesWithScores].sort((a, b) => b.score - a.score);
  const bestQuote = rankedQuotes[0];

  const MetricCard = ({ label, icon: Icon, children }: any) => (
    <div className="rounded-lg border p-3" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
      <div className="flex items-center gap-2 mb-2">
        <Icon size={12} style={{ color: "var(--app-text-muted)" }} />
        <span style={{ fontSize: 11, fontWeight: 500, color: "var(--app-text-muted)", letterSpacing: "0.02em", textTransform: "uppercase" }}>{label}</span>
      </div>
      {children}
    </div>
  );

  return (
    <div style={{ minHeight: "100%", fontFamily: "'Inter', system-ui, sans-serif", background: "var(--app-bg-canvas)" }}>
      {/* Header */}
      <div className="border-b px-6 py-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
        <button
          onClick={() => navigate("/rfqs")}
          className="flex items-center gap-1.5 mb-3 transition-colors hover:opacity-80"
          style={{ fontSize: 12, color: "var(--app-text-subtle)" }}
        >
          <ArrowLeft size={13} /> Back to RFQs
        </button>
        <div className="flex items-start justify-between">
          <div>
            <div className="flex items-center gap-3 mb-1">
              <span style={{ fontSize: 12, fontFamily: "'JetBrains Mono', monospace", color: "var(--app-brand-500)", fontWeight: 500 }}>{rfq.id}</span>
              <span className="rounded px-2 py-0.5" style={{ fontSize: 10, fontWeight: 600, background: "var(--app-brand-tint-10)", color: "var(--app-brand-400)" }}>
                Comparison Analysis
              </span>
            </div>
            <h1 style={{ fontSize: 20, fontWeight: 700, color: "var(--app-text-strong)", letterSpacing: "-0.01em", marginBottom: 4 }}>{rfq.title}</h1>
            <p style={{ fontSize: 13, color: "var(--app-text-muted)" }}>Comparing {quotes.length} qualified vendor quotes</p>
          </div>
          <div className="flex items-center gap-2">
            <button
              onClick={() => setShowScoring(!showScoring)}
              className="flex items-center gap-2 rounded-lg px-3 py-2 transition-colors"
              style={{ fontSize: 13, color: "var(--app-text-subtle)", background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)" }}
              onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-text-faint)"; }}
              onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-border-strong)"; }}
            >
              <BarChart3 size={13} /> {showScoring ? "Hide" : "Show"} Scoring
            </button>
            <button
              className="flex items-center gap-2 rounded-lg px-3 py-2 transition-colors"
              style={{ fontSize: 13, color: "var(--app-text-subtle)", background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)" }}
              onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-text-faint)"; }}
              onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-border-strong)"; }}
            >
              <Download size={13} /> Export Report
            </button>
            <button
              onClick={() => navigate("/approvals")}
              className="flex items-center gap-2 rounded-lg px-3 py-2 transition-opacity hover:opacity-90"
              style={{ fontSize: 13, fontWeight: 500, background: "var(--app-brand-600)", color: "white" }}
            >
              <Send size={13} /> Send for Approval
            </button>
          </div>
        </div>
      </div>

      <div className="p-6">
        {/* Winner Banner */}
        {bestQuote && (
          <div className="flex items-start gap-4 rounded-xl border p-4 mb-6" style={{ background: "var(--app-brand-tint-4)", borderColor: "var(--app-brand-tint-20)" }}>
            <div className="flex items-center justify-center rounded-full" style={{ width: 48, height: 48, background: "linear-gradient(135deg, var(--app-brand-500) 0%, var(--app-brand-700) 100%)", flexShrink: 0 }}>
              <Award size={22} style={{ color: "white" }} />
            </div>
            <div className="flex-1">
              <div className="flex items-center gap-2 mb-1">
                <h3 style={{ fontSize: 16, fontWeight: 700, color: "var(--app-text-strong)" }}>Recommended Winner</h3>
                <div className="flex items-center gap-1 rounded-full px-2.5 py-0.5" style={{ background: "var(--app-success-tint-10)", border: "1px solid var(--app-success-tint-20)" }}>
                  <Star size={10} style={{ color: "var(--app-success)", fill: "var(--app-success)" }} />
                  <span style={{ fontSize: 11, fontWeight: 700, color: "var(--app-success)" }}>BEST VALUE</span>
                </div>
              </div>
              <p style={{ fontSize: 14, color: "var(--app-text-main)", marginBottom: 3 }}><strong>{bestQuote.vendorName}</strong> — Overall Score: <span style={{ fontSize: 15, fontFamily: "'JetBrains Mono', monospace", color: "var(--app-brand-400)", fontWeight: 700 }}>{bestQuote.score}/100</span></p>
              <p style={{ fontSize: 12, color: "var(--app-text-muted)", lineHeight: 1.6 }}>
                Best combination of competitive pricing (${bestQuote.totalPrice.toLocaleString()}), {bestQuote.leadTime}-day lead time, and {bestQuote.compliance}% compliance score. {bestQuote.warranty}-month warranty included.
              </p>
            </div>
            <button
              className="flex items-center gap-2 rounded-lg px-4 py-2 transition-opacity hover:opacity-90"
              style={{ fontSize: 13, fontWeight: 600, background: "var(--app-accent-purple)", color: "white", alignSelf: "center" }}
            >
              <CheckCircle2 size={14} /> Award to Vendor
            </button>
          </div>
        )}

        {/* Scoring Matrix */}
        {showScoring && (
          <div className="rounded-xl border p-4 mb-6" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
            <div className="flex items-center gap-2 mb-4">
              <Zap size={14} style={{ color: "var(--app-brand-400)" }} />
              <h3 style={{ fontSize: 13, fontWeight: 600, color: "var(--app-text-strong)" }}>AI-Powered Scoring Matrix</h3>
            </div>
            <div className="rounded-lg border overflow-hidden" style={{ borderColor: "var(--app-border-strong)" }}>
              <table style={{ width: "100%", borderCollapse: "collapse" }}>
                <thead>
                  <tr style={{ borderBottom: "1px solid var(--app-border-strong)" }}>
                    <th style={{ fontSize: 10, fontWeight: 600, color: "var(--app-text-muted)", letterSpacing: "0.06em", textTransform: "uppercase", padding: "10px 14px", textAlign: "left", background: "var(--app-bg-elevated)" }}>Criteria</th>
                    <th style={{ fontSize: 10, fontWeight: 600, color: "var(--app-text-muted)", letterSpacing: "0.06em", textTransform: "uppercase", padding: "10px 14px", textAlign: "center", background: "var(--app-bg-elevated)" }}>Weight</th>
                    {rankedQuotes.map((q) => (
                      <th key={q.vendorId} style={{ fontSize: 10, fontWeight: 600, color: "var(--app-text-muted)", letterSpacing: "0.06em", textTransform: "uppercase", padding: "10px 14px", textAlign: "center", background: "var(--app-bg-elevated)" }}>
                        {q.vendorName.split(" ")[0]}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {[
                    { label: "Price Competitiveness", weight: criteriaWeights.price, values: rankedQuotes.map(q => Math.round(((Math.max(...quotes.map(x => x.totalPrice)) - q.totalPrice) / (Math.max(...quotes.map(x => x.totalPrice)) - Math.min(...quotes.map(x => x.totalPrice)))) * 100)) },
                    { label: "Lead Time", weight: criteriaWeights.leadTime, values: rankedQuotes.map(q => Math.round(((Math.max(...quotes.map(x => x.leadTime)) - q.leadTime) / (Math.max(...quotes.map(x => x.leadTime)) - Math.min(...quotes.map(x => x.leadTime)))) * 100)) },
                    { label: "Warranty Coverage", weight: criteriaWeights.warranty, values: rankedQuotes.map(q => Math.round((q.warranty / 36) * 100)) },
                    { label: "Compliance", weight: criteriaWeights.compliance, values: rankedQuotes.map(q => q.compliance) },
                    { label: "Quality Score", weight: criteriaWeights.quality, values: rankedQuotes.map(q => q.qualityScore) },
                  ].map((row, i) => (
                    <tr key={row.label} style={{ borderBottom: i < 4 ? "1px solid var(--app-border-strong)" : "2px solid var(--app-border-strong)" }}>
                      <td style={{ padding: "10px 14px", fontSize: 12, color: "var(--app-text-main)" }}>{row.label}</td>
                      <td style={{ padding: "10px 14px", fontSize: 12, color: "var(--app-text-muted)", textAlign: "center", fontFamily: "'JetBrains Mono', monospace" }}>{row.weight}%</td>
                      {row.values.map((val, j) => {
                        const isMax = val === Math.max(...row.values);
                        return (
                          <td key={j} style={{ padding: "10px 14px", textAlign: "center" }}>
                            <span style={{ fontSize: 13, fontWeight: isMax ? 700 : 500, color: isMax ? "var(--app-success)" : "var(--app-text-muted)", fontFamily: "'JetBrains Mono', monospace" }}>
                              {val}
                            </span>
                          </td>
                        );
                      })}
                    </tr>
                  ))}
                  <tr style={{ background: "var(--app-bg-elevated)" }}>
                    <td style={{ padding: "10px 14px", fontSize: 12, fontWeight: 700, color: "var(--app-text-strong)" }}>TOTAL SCORE</td>
                    <td />
                    {rankedQuotes.map((q) => (
                      <td key={q.vendorId} style={{ padding: "10px 14px", textAlign: "center" }}>
                        <span style={{ fontSize: 16, fontWeight: 800, color: q.vendorId === bestQuote.vendorId ? "var(--app-brand-500)" : "var(--app-text-muted)", fontFamily: "'JetBrains Mono', monospace" }}>
                          {q.score}
                        </span>
                      </td>
                    ))}
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        )}

        {/* Summary Comparison */}
        <div className="grid grid-cols-3 gap-4 mb-6">
          {rankedQuotes.map((quote, idx) => (
            <div key={quote.vendorId} className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: quote.vendorId === bestQuote.vendorId ? "var(--app-brand-tint-20)" : "var(--app-border-strong)" }}>
              <div className="flex items-center justify-between mb-3">
                <div>
                  <div className="flex items-center gap-2 mb-1">
                    <h3 style={{ fontSize: 14, fontWeight: 600, color: "var(--app-text-strong)" }}>{quote.vendorName}</h3>
                    {idx === 0 && <Star size={12} style={{ color: "var(--app-warning)", fill: "var(--app-warning)" }} />}
                  </div>
                  <span style={{ fontSize: 11, fontFamily: "'JetBrains Mono', monospace", color: "var(--app-text-faint)" }}>{quote.vendorId}</span>
                </div>
                <div className="rounded-full" style={{ width: 44, height: 44, background: `conic-gradient(var(--app-brand-500) 0deg ${(quote.score / 100) * 360}deg, var(--app-border-strong) ${(quote.score / 100) * 360}deg 360deg)`, display: "flex", alignItems: "center", justifyContent: "center" }}>
                  <div className="rounded-full flex items-center justify-center" style={{ width: 36, height: 36, background: "var(--app-bg-surface)" }}>
                    <span style={{ fontSize: 11, fontWeight: 800, color: "var(--app-brand-400)", fontFamily: "'JetBrains Mono', monospace" }}>{quote.score}</span>
                  </div>
                </div>
              </div>

              <div className="space-y-2">
                <MetricCard label="Total Price" icon={DollarSign}>
                  <div className="flex items-baseline gap-1">
                    <span style={{ fontSize: 20, fontWeight: 800, color: "var(--app-text-strong)", fontFamily: "'JetBrains Mono', monospace", letterSpacing: "-0.02em" }}>
                      ${quote.totalPrice.toLocaleString()}
                    </span>
                    {quote.totalPrice === Math.min(...quotes.map(q => q.totalPrice)) && (
                      <span className="flex items-center gap-0.5 rounded px-1.5 py-0.5" style={{ fontSize: 9, fontWeight: 700, background: "var(--app-success-tint-10)", color: "var(--app-success)" }}>
                        <TrendingDown size={9} /> LOWEST
                      </span>
                    )}
                    {quote.totalPrice === Math.max(...quotes.map(q => q.totalPrice)) && (
                      <span className="flex items-center gap-0.5 rounded px-1.5 py-0.5" style={{ fontSize: 9, fontWeight: 700, background: "var(--app-danger-tint-10)", color: "var(--app-danger)" }}>
                        <TrendingUp size={9} /> HIGHEST
                      </span>
                    )}
                  </div>
                </MetricCard>

                <div className="grid grid-cols-2 gap-2">
                  <MetricCard label="Lead Time" icon={Clock}>
                    <div style={{ fontSize: 15, fontWeight: 700, color: quote.leadTime === Math.min(...quotes.map(q => q.leadTime)) ? "var(--app-success)" : "var(--app-text-muted)" }}>
                      {quote.leadTime} days
                    </div>
                  </MetricCard>
                  <MetricCard label="Warranty" icon={Shield}>
                    <div style={{ fontSize: 15, fontWeight: 700, color: quote.warranty === Math.max(...quotes.map(q => q.warranty)) ? "var(--app-success)" : "var(--app-text-muted)" }}>
                      {quote.warranty} mo
                    </div>
                  </MetricCard>
                </div>

                <div className="grid grid-cols-2 gap-2">
                  <MetricCard label="Compliance" icon={CheckCircle2}>
                    <div className="flex items-center gap-2">
                      <div className="flex-1 rounded-full overflow-hidden" style={{ height: 4, background: "var(--app-border-strong)" }}>
                        <div style={{ height: "100%", width: `${quote.compliance}%`, background: quote.compliance >= 95 ? "var(--app-success)" : "var(--app-warning)", borderRadius: 2 }} />
                      </div>
                      <span style={{ fontSize: 12, fontWeight: 700, color: quote.compliance >= 95 ? "var(--app-success)" : "var(--app-warning)" }}>{quote.compliance}%</span>
                    </div>
                  </MetricCard>
                  <MetricCard label="Quality" icon={Star}>
                    <div className="flex items-center gap-2">
                      <div className="flex-1 rounded-full overflow-hidden" style={{ height: 4, background: "var(--app-border-strong)" }}>
                        <div style={{ height: "100%", width: `${quote.qualityScore}%`, background: quote.qualityScore >= 90 ? "var(--app-success)" : "var(--app-warning)", borderRadius: 2 }} />
                      </div>
                      <span style={{ fontSize: 12, fontWeight: 700, color: quote.qualityScore >= 90 ? "var(--app-success)" : "var(--app-warning)" }}>{quote.qualityScore}%</span>
                    </div>
                  </MetricCard>
                </div>

                <MetricCard label="Payment" icon={FileText}>
                  <div style={{ fontSize: 13, color: "var(--app-text-muted)" }}>{quote.paymentTerms}</div>
                </MetricCard>
              </div>
            </div>
          ))}
        </div>

        {/* Line Item Comparison */}
        <div className="rounded-xl border overflow-hidden" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
          <div className="px-4 py-3 border-b" style={{ borderColor: "var(--app-border-strong)" }}>
            <h3 style={{ fontSize: 12, fontWeight: 600, color: "var(--app-text-muted)", letterSpacing: "0.08em", textTransform: "uppercase" }}>
              Line-by-Line Comparison
            </h3>
          </div>
          <table style={{ width: "100%", borderCollapse: "collapse" }}>
            <thead>
              <tr style={{ borderBottom: "1px solid var(--app-border-strong)" }}>
                <th style={{ fontSize: 10, fontWeight: 600, color: "var(--app-text-muted)", letterSpacing: "0.06em", textTransform: "uppercase", padding: "10px 14px", textAlign: "left", background: "var(--app-bg-elevated)", width: 220 }}>Item</th>
                {rankedQuotes.map((q) => (
                  <th key={q.vendorId} colSpan={2} style={{ fontSize: 10, fontWeight: 600, color: "var(--app-text-muted)", letterSpacing: "0.06em", textTransform: "uppercase", padding: "10px 14px", textAlign: "center", background: "var(--app-bg-elevated)", borderLeft: "1px solid var(--app-border-strong)" }}>
                    {q.vendorName.split(" ")[0]}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {[1, 2, 3, 4, 5].map((lineId, i) => (
                <tr key={lineId} style={{ borderBottom: i < 4 ? "1px solid var(--app-border-strong)" : "none" }}>
                  <td style={{ padding: "10px 14px", fontSize: 12, color: "var(--app-text-main)", verticalAlign: "top" }}>
                    <div style={{ fontWeight: 500, color: "var(--app-text-strong)", marginBottom: 2 }}>
                      {rankedQuotes[0].lineItems.find(l => l.id === lineId)?.description}
                    </div>
                    <div style={{ fontSize: 11, color: "var(--app-text-faint)" }}>
                      {rankedQuotes[0].lineItems.find(l => l.id === lineId)?.qty} {rankedQuotes[0].lineItems.find(l => l.id === lineId)?.uom}
                    </div>
                  </td>
                  {rankedQuotes.map((quote) => {
                    const item = quote.lineItems.find(l => l.id === lineId);
                    if (!item) return <td key={quote.vendorId} colSpan={2} style={{ padding: "10px 14px", textAlign: "center", color: "var(--app-text-faint)", borderLeft: "1px solid var(--app-border-strong)" }}>—</td>;
                    const minPrice = Math.min(...rankedQuotes.map(q => q.lineItems.find(l => l.id === lineId)?.total || Infinity));
                    const isLowest = item.total === minPrice;
                    return (
                      <>
                        <td key={`${quote.vendorId}-unit`} style={{ padding: "10px 14px", textAlign: "right", borderLeft: "1px solid var(--app-border-strong)", fontSize: 12, color: "var(--app-text-muted)", fontFamily: "'JetBrains Mono', monospace" }}>
                          ${item.unitPrice.toLocaleString()}
                        </td>
                        <td key={`${quote.vendorId}-total`} style={{ padding: "10px 14px", textAlign: "right", fontSize: 13, fontWeight: isLowest ? 700 : 500, color: isLowest ? "var(--app-success)" : "var(--app-text-muted)", fontFamily: "'JetBrains Mono', monospace" }}>
                          ${item.total.toLocaleString()}
                        </td>
                      </>
                    );
                  })}
                </tr>
              ))}
              <tr style={{ borderTop: "2px solid var(--app-border-strong)", background: "var(--app-bg-elevated)" }}>
                <td style={{ padding: "10px 14px", fontSize: 12, fontWeight: 700, color: "var(--app-text-strong)" }}>TOTAL</td>
                {rankedQuotes.map((quote) => (
                  <td key={quote.vendorId} colSpan={2} style={{ padding: "10px 14px", textAlign: "right", borderLeft: "1px solid var(--app-border-strong)" }}>
                    <span style={{ fontSize: 16, fontWeight: 800, color: quote.vendorId === bestQuote.vendorId ? "var(--app-brand-400)" : "var(--app-text-strong)", fontFamily: "'JetBrains Mono', monospace" }}>
                      ${quote.totalPrice.toLocaleString()}
                    </span>
                  </td>
                ))}
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
