import { useState } from "react";
import { useNavigate } from "react-router";
import {
  AlertTriangle, Shield, TrendingUp, TrendingDown, XCircle,
  CheckCircle2, AlertCircle, DollarSign, FileText, Users,
  Clock, Globe, Award, BarChart3, Eye, ChevronRight, Info
} from "lucide-react";

interface RiskAssessment {
  id: string;
  rfqId: string;
  rfqTitle: string;
  vendor: string;
  overallRisk: "Low" | "Medium" | "High" | "Critical";
  riskScore: number;
  factors: {
    financial: number;
    compliance: number;
    delivery: number;
    quality: number;
    reputation: number;
  };
  flags: {
    type: "warning" | "critical" | "info";
    category: string;
    message: string;
  }[];
  lastAssessed: string;
  assessedBy: string;
}

const mockRisks: RiskAssessment[] = [
  {
    id: "RA-001",
    rfqId: "RFQ-2024-005",
    rfqTitle: "IT Hardware Refresh 2024",
    vendor: "TechSource Partners",
    overallRisk: "High",
    riskScore: 72,
    factors: {
      financial: 45,
      compliance: 88,
      delivery: 62,
      quality: 78,
      reputation: 82,
    },
    flags: [
      { type: "critical", category: "Financial", message: "Vendor reported 28% revenue decline in last fiscal year" },
      { type: "warning", category: "Delivery", message: "2 delivery delays reported in past 6 months by other buyers" },
      { type: "info", category: "Compliance", message: "ISO 27001 certification expires in 45 days" },
    ],
    lastAssessed: "2024-01-23 14:22",
    assessedBy: "Risk AI Engine",
  },
  {
    id: "RA-002",
    rfqId: "RFQ-2024-001",
    rfqTitle: "Industrial Pumping Equipment",
    vendor: "Apex Industrial Solutions",
    overallRisk: "Low",
    riskScore: 18,
    factors: {
      financial: 92,
      compliance: 98,
      delivery: 94,
      quality: 96,
      reputation: 95,
    },
    flags: [
      { type: "info", category: "Quality", message: "Strong track record: 96% on-time delivery rate" },
    ],
    lastAssessed: "2024-01-22 09:15",
    assessedBy: "Risk AI Engine",
  },
  {
    id: "RA-003",
    rfqId: "RFQ-2024-009",
    rfqTitle: "Security Systems Upgrade",
    vendor: "SecureGuard Systems",
    overallRisk: "Medium",
    riskScore: 48,
    factors: {
      financial: 75,
      compliance: 68,
      delivery: 82,
      quality: 71,
      reputation: 79,
    },
    flags: [
      { type: "warning", category: "Compliance", message: "Pending legal dispute with former client (non-material)" },
      { type: "warning", category: "Quality", message: "Recent 1-star review citing installation issues" },
    ],
    lastAssessed: "2024-01-21 16:48",
    assessedBy: "Risk AI Engine",
  },
];

const riskLevelConfig: Record<string, { bg: string; color: string; border: string }> = {
  Low: { bg: "var(--app-success-tint-10)", color: "var(--app-success)", border: "var(--app-success-tint-20)" },
  Medium: { bg: "var(--app-warning-tint-10)", color: "var(--app-warning)", border: "var(--app-warning-tint-20)" },
  High: { bg: "var(--app-orange-tint-10)", color: "var(--app-warning-soft)", border: "var(--app-orange-tint-20)" },
  Critical: { bg: "var(--app-danger-tint-10)", color: "var(--app-danger)", border: "var(--app-danger-tint-20)" },
};

const flagTypeConfig = {
  critical: { color: "var(--app-danger)", bg: "var(--app-danger-tint-8)", icon: XCircle },
  warning: { color: "var(--app-warning)", bg: "var(--app-warning-tint-8)", icon: AlertTriangle },
  info: { color: "var(--app-brand-500)", bg: "var(--app-brand-tint-8)", icon: Info },
};

export function Risk() {
  const navigate = useNavigate();
  const [selectedRisk, setSelectedRisk] = useState<RiskAssessment | null>(null);
  const [riskFilter, setRiskFilter] = useState("All");

  const riskLevels = ["All", "Critical", "High", "Medium", "Low"];
  const filtered = mockRisks.filter((r) => riskFilter === "All" || r.overallRisk === riskFilter);

  const stats = {
    critical: mockRisks.filter((r) => r.overallRisk === "Critical").length,
    high: mockRisks.filter((r) => r.overallRisk === "High").length,
    medium: mockRisks.filter((r) => r.overallRisk === "Medium").length,
    low: mockRisks.filter((r) => r.overallRisk === "Low").length,
  };

  const RiskGauge = ({ score, size = 80 }: { score: number; size?: number }) => {
    const getColor = (s: number) => {
      if (s >= 75) return "var(--app-danger)";
      if (s >= 50) return "var(--app-warning-soft)";
      if (s >= 25) return "var(--app-warning)";
      return "var(--app-success)";
    };

    return (
      <div style={{ width: size, height: size, position: "relative" }}>
        <svg width={size} height={size} style={{ transform: "rotate(-90deg)" }}>
          <circle
            cx={size / 2}
            cy={size / 2}
            r={(size - 8) / 2}
            fill="none"
            stroke="var(--app-border-strong)"
            strokeWidth="6"
          />
          <circle
            cx={size / 2}
            cy={size / 2}
            r={(size - 8) / 2}
            fill="none"
            stroke={getColor(score)}
            strokeWidth="6"
            strokeDasharray={`${((size - 8) * Math.PI * score) / 100} ${(size - 8) * Math.PI}`}
            strokeLinecap="round"
          />
        </svg>
        <div
          style={{
            position: "absolute",
            top: "50%",
            left: "50%",
            transform: "translate(-50%, -50%)",
            textAlign: "center",
          }}
        >
          <div style={{ fontSize: size * 0.28, fontWeight: 800, color: getColor(score), fontFamily: "'JetBrains Mono', monospace", lineHeight: 1 }}>
            {score}
          </div>
        </div>
      </div>
    );
  };

  return (
    <div style={{ display: "flex", height: "100%", minHeight: "calc(100vh - 88px)", fontFamily: "'Inter', system-ui, sans-serif", background: "var(--app-bg-canvas)" }}>
      {/* Left: Risk List */}
      <div className="flex-shrink-0 flex flex-col border-r" style={{ width: 360, background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
        {/* Header */}
        <div className="px-4 py-4 border-b" style={{ borderColor: "var(--app-border-strong)" }}>
          <div className="flex items-center gap-2 mb-3">
            <Shield size={18} style={{ color: "var(--app-brand-400)" }} />
            <h2 style={{ fontSize: 16, fontWeight: 700, color: "var(--app-text-strong)" }}>Risk Assessment</h2>
          </div>

          {/* Stats */}
          <div className="grid grid-cols-4 gap-1.5 mb-4">
            {[
              { label: "Critical", count: stats.critical, color: "var(--app-danger)" },
              { label: "High", count: stats.high, color: "var(--app-warning-soft)" },
              { label: "Medium", count: stats.medium, color: "var(--app-warning)" },
              { label: "Low", count: stats.low, color: "var(--app-success)" },
            ].map((stat) => (
              <div key={stat.label} className="rounded-lg border p-2 text-center" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
                <div style={{ fontSize: 18, fontWeight: 800, color: stat.color, letterSpacing: "-0.02em", lineHeight: 1 }}>
                  {stat.count}
                </div>
                <div style={{ fontSize: 9, color: "var(--app-text-muted)", marginTop: 3, textTransform: "uppercase", letterSpacing: "0.05em" }}>
                  {stat.label}
                </div>
              </div>
            ))}
          </div>

          {/* Filter */}
          <div className="flex items-center gap-1 p-1 rounded-lg" style={{ background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)" }}>
            {riskLevels.map((level) => (
              <button
                key={level}
                onClick={() => setRiskFilter(level)}
                className="flex-1 rounded transition-all"
                style={{ fontSize: 10, padding: "4px 4px", background: riskFilter === level ? "var(--app-bg-surface)" : "transparent", color: riskFilter === level ? "var(--app-text-main)" : "var(--app-text-muted)", fontWeight: riskFilter === level ? 500 : 400 }}
              >
                {level}
              </button>
            ))}
          </div>
        </div>

        {/* Risk List */}
        <div className="flex-1 overflow-y-auto py-2" style={{ scrollbarWidth: "none" }}>
          {filtered.map((risk) => {
            const rc = riskLevelConfig[risk.overallRisk];
            const isSelected = selectedRisk?.id === risk.id;

            return (
              <div
                key={risk.id}
                className="mx-2 mb-1 rounded-lg p-3 cursor-pointer border transition-all"
                style={{
                  background: isSelected ? "var(--app-brand-tint-8)" : "transparent",
                  borderColor: isSelected ? "var(--app-brand-tint-20)" : "transparent",
                }}
                onClick={() => setSelectedRisk(risk)}
                onMouseEnter={(e) => {
                  if (!isSelected) (e.currentTarget as HTMLDivElement).style.background = "var(--app-hover-soft)";
                }}
                onMouseLeave={(e) => {
                  if (!isSelected) (e.currentTarget as HTMLDivElement).style.background = "transparent";
                }}
              >
                <div className="flex items-start justify-between mb-2">
                  <div>
                    <div className="flex items-center gap-2 mb-1">
                      <span style={{ fontSize: 11, fontFamily: "'JetBrains Mono', monospace", color: "var(--app-brand-500)", fontWeight: 500 }}>
                        {risk.id}
                      </span>
                      {risk.flags.filter(f => f.type === "critical").length > 0 && (
                        <div className="flex items-center gap-0.5 rounded px-1.5 py-0.5" style={{ background: "var(--app-danger-tint-10)", fontSize: 9, fontWeight: 700, color: "var(--app-danger)" }}>
                          <AlertTriangle size={8} />
                          {risk.flags.filter(f => f.type === "critical").length}
                        </div>
                      )}
                    </div>
                    <div style={{ fontSize: 12, fontWeight: 600, color: "var(--app-text-strong)", marginBottom: 2, whiteSpace: "nowrap", overflow: "hidden", textOverflow: "ellipsis", maxWidth: 220 }}>
                      {risk.vendor}
                    </div>
                    <div style={{ fontSize: 11, color: "var(--app-text-muted)" }}>{risk.rfqId}</div>
                  </div>
                  <RiskGauge score={risk.riskScore} size={52} />
                </div>
                <div className="flex items-center gap-1.5 rounded-full px-2.5 py-1 w-fit" style={{ background: rc.bg, border: `1px solid ${rc.border}` }}>
                  <Shield size={9} style={{ color: rc.color }} />
                  <span style={{ fontSize: 10, fontWeight: 700, color: rc.color }}>{risk.overallRisk.toUpperCase()} RISK</span>
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Right: Risk Detail */}
      <div className="flex-1 overflow-auto" style={{ background: "var(--app-bg-canvas)" }}>
        {selectedRisk ? (
          <>
            {/* Header */}
            <div className="px-6 py-4 border-b flex items-start justify-between" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
              <div className="flex-1">
                <div className="flex items-center gap-3 mb-2">
                  <span style={{ fontSize: 12, fontFamily: "'JetBrains Mono', monospace", color: "var(--app-brand-500)", fontWeight: 500 }}>
                    {selectedRisk.id}
                  </span>
                  <span
                    className="flex items-center gap-1.5 rounded-full px-2.5 py-1"
                    style={{
                      background: riskLevelConfig[selectedRisk.overallRisk].bg,
                      color: riskLevelConfig[selectedRisk.overallRisk].color,
                      border: `1px solid ${riskLevelConfig[selectedRisk.overallRisk].border}`,
                      fontSize: 11,
                      fontWeight: 700,
                    }}
                  >
                    <Shield size={11} />
                    {selectedRisk.overallRisk.toUpperCase()} RISK
                  </span>
                </div>
                <h2 style={{ fontSize: 18, fontWeight: 700, color: "var(--app-text-strong)", letterSpacing: "-0.01em", marginBottom: 3 }}>
                  {selectedRisk.vendor}
                </h2>
                <div className="flex items-center gap-4" style={{ fontSize: 12, color: "var(--app-text-muted)" }}>
                  <button
                    onClick={() => navigate(`/rfqs/${selectedRisk.rfqId}`)}
                    className="flex items-center gap-1.5 hover:text-blue-400 transition-colors"
                  >
                    <FileText size={12} />
                    {selectedRisk.rfqId} — {selectedRisk.rfqTitle}
                  </button>
                  <span className="flex items-center gap-1.5">
                    <Clock size={12} />
                    Assessed {selectedRisk.lastAssessed}
                  </span>
                </div>
              </div>
              <div className="flex items-center gap-2">
                <button
                  onClick={() => navigate(`/rfqs/${selectedRisk.rfqId}`)}
                  className="flex items-center gap-2 rounded-lg px-3 py-2 transition-colors"
                  style={{ fontSize: 13, color: "var(--app-text-subtle)", background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)" }}
                  onMouseEnter={(e) => {
                    (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-text-faint)";
                  }}
                  onMouseLeave={(e) => {
                    (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-border-strong)";
                  }}
                >
                  <Eye size={13} /> View RFQ
                </button>
              </div>
            </div>

            <div className="p-6 space-y-5">
              {/* Overall Risk Score */}
              <div className="rounded-xl border p-5 flex items-center gap-6" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
                <RiskGauge score={selectedRisk.riskScore} size={120} />
                <div className="flex-1">
                  <h3 style={{ fontSize: 14, fontWeight: 600, color: "var(--app-text-strong)", marginBottom: 8 }}>Overall Risk Score</h3>
                  <p style={{ fontSize: 13, color: "var(--app-text-muted)", lineHeight: 1.6, marginBottom: 12 }}>
                    Composite risk assessment based on financial health, compliance status, delivery performance, quality metrics, and market reputation.
                    {selectedRisk.overallRisk === "Critical" && " Immediate action recommended."}
                    {selectedRisk.overallRisk === "High" && " Close monitoring required."}
                  </p>
                  <div className="flex items-center gap-2">
                    <Users size={12} style={{ color: "var(--app-text-faint)" }} />
                    <span style={{ fontSize: 11, color: "var(--app-text-muted)" }}>Assessment by {selectedRisk.assessedBy}</span>
                  </div>
                </div>
              </div>

              {/* Risk Flags */}
              {selectedRisk.flags.length > 0 && (
                <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
                  <div className="flex items-center gap-2 mb-3">
                    <AlertTriangle size={14} style={{ color: "var(--app-warning)" }} />
                    <h3 style={{ fontSize: 13, fontWeight: 600, color: "var(--app-text-strong)" }}>Risk Flags ({selectedRisk.flags.length})</h3>
                  </div>
                  <div className="space-y-2">
                    {selectedRisk.flags.map((flag, i) => {
                      const fc = flagTypeConfig[flag.type];
                      const FlagIcon = fc.icon;
                      return (
                        <div
                          key={i}
                          className="flex items-start gap-3 rounded-lg p-3"
                          style={{ background: fc.bg, border: `1px solid ${fc.color}33` }}
                        >
                          <FlagIcon size={14} style={{ color: fc.color, flexShrink: 0, marginTop: 1 }} />
                          <div className="flex-1">
                            <div className="flex items-center gap-2 mb-1">
                              <span style={{ fontSize: 12, fontWeight: 600, color: "var(--app-text-main)" }}>{flag.category}</span>
                              <span
                                className="rounded px-1.5 py-0.5"
                                style={{
                                  fontSize: 9,
                                  fontWeight: 700,
                                  background: `${fc.color}22`,
                                  color: fc.color,
                                  textTransform: "uppercase",
                                  letterSpacing: "0.05em",
                                }}
                              >
                                {flag.type}
                              </span>
                            </div>
                            <div style={{ fontSize: 12, color: "var(--app-text-muted)", lineHeight: 1.5 }}>{flag.message}</div>
                          </div>
                        </div>
                      );
                    })}
                  </div>
                </div>
              )}

              {/* Risk Factor Breakdown */}
              <div className="rounded-xl border p-4" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
                <h3 style={{ fontSize: 13, fontWeight: 600, color: "var(--app-text-strong)", marginBottom: 16 }}>Risk Factor Breakdown</h3>
                <div className="space-y-4">
                  {Object.entries(selectedRisk.factors).map(([factor, score]) => {
                    const riskScore = 100 - score;
                    const isHigh = riskScore >= 50;
                    const icon = {
                      financial: DollarSign,
                      compliance: Shield,
                      delivery: Clock,
                      quality: Award,
                      reputation: Globe,
                    }[factor] || Info;
                    const Icon = icon;

                    return (
                      <div key={factor}>
                        <div className="flex items-center justify-between mb-2">
                          <div className="flex items-center gap-2">
                            <Icon size={13} style={{ color: "var(--app-text-faint)" }} />
                            <span style={{ fontSize: 12, color: "var(--app-text-muted)", textTransform: "capitalize" }}>{factor}</span>
                          </div>
                          <div className="flex items-center gap-2">
                            <span style={{ fontSize: 11, color: "var(--app-text-faint)" }}>Risk: {riskScore}%</span>
                            <span
                              style={{
                                fontSize: 13,
                                fontWeight: 700,
                                color: isHigh ? "var(--app-danger)" : riskScore >= 25 ? "var(--app-warning)" : "var(--app-success)",
                                fontFamily: "'JetBrains Mono', monospace",
                              }}
                            >
                              {score}
                            </span>
                          </div>
                        </div>
                        <div className="rounded-full overflow-hidden" style={{ height: 6, background: "var(--app-border-strong)" }}>
                          <div
                            style={{
                              height: "100%",
                              width: `${score}%`,
                              background: isHigh ? "var(--app-danger)" : riskScore >= 25 ? "var(--app-warning)" : "var(--app-success)",
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

              {/* Recommendation */}
              <div
                className="rounded-xl border p-4 flex items-start gap-3"
                style={{
                  background:
                    selectedRisk.overallRisk === "Critical" || selectedRisk.overallRisk === "High"
                      ? "var(--app-danger-tint-4)"
                      : selectedRisk.overallRisk === "Medium"
                      ? "var(--app-warning-tint-4)"
                      : "var(--app-success-tint-4)",
                  borderColor:
                    selectedRisk.overallRisk === "Critical" || selectedRisk.overallRisk === "High"
                      ? "var(--app-danger-tint-20)"
                      : selectedRisk.overallRisk === "Medium"
                      ? "var(--app-warning-tint-20)"
                      : "var(--app-success-tint-20)",
                }}
              >
                <BarChart3
                  size={16}
                  style={{
                    color:
                      selectedRisk.overallRisk === "Critical" || selectedRisk.overallRisk === "High"
                        ? "var(--app-danger)"
                        : selectedRisk.overallRisk === "Medium"
                        ? "var(--app-warning)"
                        : "var(--app-success)",
                    flexShrink: 0,
                    marginTop: 2,
                  }}
                />
                <div>
                  <h4 style={{ fontSize: 13, fontWeight: 600, color: "var(--app-text-strong)", marginBottom: 4 }}>AI Recommendation</h4>
                  <p style={{ fontSize: 12, color: "var(--app-text-muted)", lineHeight: 1.6 }}>
                    {selectedRisk.overallRisk === "Critical" &&
                      "Do not proceed with award. Critical financial and compliance risks detected. Consider alternative vendors or request additional guarantees."}
                    {selectedRisk.overallRisk === "High" &&
                      "Proceed with caution. Recommend additional due diligence, performance bonds, or staged payment terms to mitigate identified risks."}
                    {selectedRisk.overallRisk === "Medium" &&
                      "Acceptable risk level. Monitor flagged areas closely and include appropriate contract terms to address identified concerns."}
                    {selectedRisk.overallRisk === "Low" &&
                      "Low risk profile. Vendor demonstrates strong performance across all assessment criteria. Safe to proceed with standard terms."}
                  </p>
                </div>
              </div>
            </div>
          </>
        ) : (
          <div className="flex items-center justify-center h-full" style={{ color: "var(--app-text-subtle)" }}>
            <div className="text-center">
              <Shield size={48} style={{ color: "var(--app-border-strong)", margin: "0 auto 16px" }} />
              <p style={{ fontSize: 14 }}>Select a risk assessment to review</p>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
