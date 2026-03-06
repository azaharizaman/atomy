import { useState } from "react";
import {
  SlidersHorizontal, Save, RotateCcw, Plus, Info,
  ChevronRight, Lock, Unlock, Zap, ShieldCheck,
  TrendingDown, Star, Activity, AlertCircle, Trash2
} from "lucide-react";

interface ScoringCriterion {
  id: string;
  label: string;
  description: string;
  weight: number;
  type: "quantitative" | "qualitative" | "binary";
  icon: any;
  color: string;
}

const defaultCriteria: ScoringCriterion[] = [
  {
    id: "price",
    label: "Total Price (TCO)",
    description: "Evaluates the total cost of ownership including delivery and maintenance over 3 years.",
    weight: 40,
    type: "quantitative",
    icon: TrendingDown,
    color: "var(--app-success)"
  },
  {
    id: "quality",
    label: "Quality Rating",
    description: "Aggregated score from technical specifications alignment and previous performance.",
    weight: 20,
    type: "quantitative",
    icon: Star,
    color: "var(--app-warning)"
  },
  {
    id: "lead_time",
    label: "Lead Time",
    description: "Delivery speed and supply chain reliability metrics.",
    weight: 15,
    type: "quantitative",
    icon: Activity,
    color: "var(--app-brand-400)"
  },
  {
    id: "compliance",
    label: "Compliance Status",
    description: "Strict adherence to safety, environmental, and regional regulatory requirements.",
    weight: 15,
    type: "binary",
    icon: ShieldCheck,
    color: "var(--app-success)"
  },
  {
    id: "risk",
    label: "Risk Index",
    description: "AI-detected financial and reputational hazards.",
    weight: 10,
    type: "qualitative",
    icon: AlertCircle,
    color: "var(--app-danger)"
  }
];

export function ScoringPolicies() {
  const [criteria, setCriteria] = useState(defaultCriteria);
  const [activePolicy, setActivePolicy] = useState("Standard Equipment Q1");
  const [isLocked, setIsLocked] = useState(true);

  const totalWeight = criteria.reduce((sum, c) => sum + c.weight, 0);

  const updateWeight = (id: string, newWeight: number) => {
    if (isLocked) return;
    setCriteria(prev => prev.map(c => c.id === id ? { ...c, weight: Math.max(0, Math.min(100, newWeight)) } : c));
  };

  return (
    <div className="flex flex-col h-full" style={{ background: "var(--app-bg-canvas)" }}>
      {/* Header */}
      <div className="border-b px-6 py-4 flex items-center justify-between" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
        <div>
          <div className="flex items-center gap-2 mb-1">
            <SlidersHorizontal size={14} className="text-indigo-400" />
            <span style={{ fontSize: 11, fontWeight: 700, color: "var(--app-text-muted)", textTransform: "uppercase", letterSpacing: "0.05em" }}>Governance & Logic</span>
          </div>
          <h1 style={{ fontSize: 20, fontWeight: 700, color: "var(--app-text-strong)" }}>Scoring Policies</h1>
        </div>
        <div className="flex items-center gap-3">
          <button 
            onClick={() => setIsLocked(!isLocked)}
            className="flex items-center gap-2 px-3 py-2 rounded-lg border transition-all"
            style={{ 
              fontSize: 13, 
              background: isLocked ? "var(--app-bg-elevated)" : "var(--app-brand-600)",
              borderColor: isLocked ? "var(--app-border-strong)" : "var(--app-brand-400)",
              color: isLocked ? "var(--app-text-subtle)" : "white"
            }}
          >
            {isLocked ? <Lock size={14} /> : <Unlock size={14} />}
            {isLocked ? "Edit Policy" : "Release Lock"}
          </button>
          <div className="w-px h-6 bg-slate-800" />
          <button className="flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white font-bold text-sm shadow-lg shadow-indigo-500/20 hover:bg-indigo-500 transition-all">
            <Save size={14} /> Save Global Logic
          </button>
        </div>
      </div>

      <div className="flex-1 flex overflow-hidden">
        {/* Left: Policy List */}
        <div className="w-64 border-r flex flex-col" style={{ borderColor: "var(--app-border-strong)", background: "var(--app-bg-surface)" }}>
          <div className="p-4 border-b" style={{ borderColor: "var(--app-border-strong)" }}>
            <div className="text-[10px] font-bold text-muted uppercase mb-3 tracking-widest">Active Policies</div>
            <div className="space-y-1">
              {["Standard Equipment Q1", "IT Services Master", "Logistics Primary", "Raw Materials"].map(policy => (
                <button
                  key={policy}
                  onClick={() => setActivePolicy(policy)}
                  className="w-full flex items-center justify-between px-3 py-2 rounded text-left transition-all"
                  style={{ 
                    background: activePolicy === policy ? "var(--app-brand-tint-10)" : "transparent",
                    color: activePolicy === policy ? "var(--app-brand-400)" : "var(--app-text-muted)",
                    fontSize: 12,
                    fontWeight: activePolicy === policy ? 600 : 400
                  }}
                >
                  {policy}
                  {activePolicy === policy && <ChevronRight size={12} />}
                </button>
              ))}
            </div>
          </div>
          <div className="p-4">
            <button className="w-full py-2 flex items-center justify-center gap-2 rounded-lg border border-dashed border-slate-700 text-[11px] font-bold text-slate-500 hover:text-indigo-400 transition-all">
              <Plus size={12} /> Create Template
            </button>
          </div>
        </div>

        {/* Center: Weight Editor */}
        <div className="flex-1 overflow-auto p-8">
          <div className="max-w-4xl mx-auto space-y-8">
            <div className="flex items-center justify-between p-6 rounded-2xl border bg-slate-900/30" style={{ borderColor: "var(--app-border-strong)" }}>
              <div>
                <h2 style={{ fontSize: 18, fontWeight: 700, color: "white" }}>{activePolicy}</h2>
                <p style={{ fontSize: 13, color: "var(--app-text-muted)", marginTop: 4 }}>Applied to 14 active RFQs. Changes here will trigger a recalculation of all associated Comparison Matrices.</p>
              </div>
              <div className="text-right">
                <div style={{ fontSize: 32, fontWeight: 900, color: totalWeight === 100 ? "var(--app-success)" : "var(--app-danger)", fontFamily: "monospace" }}>{totalWeight}%</div>
                <div style={{ fontSize: 10, fontWeight: 700, color: "var(--app-text-muted)", textTransform: "uppercase" }}>Total Allocation</div>
              </div>
            </div>

            <div className="space-y-4">
              {criteria.map((item) => (
                <div key={item.id} className="group p-5 rounded-xl border transition-all hover:bg-slate-900/40" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
                  <div className="flex items-start gap-4">
                    <div className="mt-1 p-2 rounded-lg bg-slate-800 border border-white/5" style={{ color: item.color }}>
                      <item.icon size={18} />
                    </div>
                    <div className="flex-1">
                      <div className="flex items-center justify-between mb-1">
                        <div className="flex items-center gap-2">
                          <h3 style={{ fontSize: 14, fontWeight: 700, color: "var(--app-text-strong)" }}>{item.label}</h3>
                          <span className="text-[10px] px-1.5 py-0.5 rounded bg-slate-800 text-slate-400 border border-white/5 font-bold uppercase">{item.type}</span>
                        </div>
                        <div className="flex items-center gap-4">
                          {!isLocked && (
                            <button className="opacity-0 group-hover:opacity-100 text-slate-600 hover:text-red-400 transition-all">
                              <Trash2 size={14} />
                            </button>
                          )}
                          <div className="flex items-center gap-2">
                            <input
                              type="number"
                              value={item.weight}
                              onChange={(e) => updateWeight(item.id, parseInt(e.target.value))}
                              disabled={isLocked}
                              className="w-16 px-2 py-1 rounded border text-center font-bold font-mono outline-none focus:border-indigo-500 bg-slate-900 text-white"
                              style={{ borderColor: "var(--app-border-strong)" }}
                            />
                            <span className="text-slate-500 font-bold">%</span>
                          </div>
                        </div>
                      </div>
                      <p style={{ fontSize: 12, color: "var(--app-text-muted)", lineHeight: 1.5, maxWidth: "80%" }}>{item.description}</p>
                      
                      <div className="mt-4 flex items-center gap-4">
                        <div className="flex-1 h-1.5 rounded-full bg-slate-800 overflow-hidden">
                          <div 
                            className="h-full transition-all duration-500" 
                            style={{ width: `${item.weight}%`, background: item.color }} 
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              ))}
            </div>

            <div className="flex items-center gap-3 p-4 rounded-xl border border-dashed border-slate-700 bg-slate-900/20">
               <div className="p-2 rounded-full bg-slate-800">
                  <Plus size={16} className="text-slate-400" />
               </div>
               <div>
                  <div style={{ fontSize: 13, fontWeight: 600, color: "var(--app-text-subtle)" }}>Add Custom Criterion</div>
                  <div style={{ fontSize: 11, color: "var(--app-text-faint)" }}>Define new quantitative or qualitative inputs for the AI agent.</div>
               </div>
            </div>
          </div>
        </div>

        {/* Right: AI Simulation */}
        <div className="w-80 border-l p-6 space-y-6" style={{ borderColor: "var(--app-border-strong)", background: "var(--app-bg-surface)" }}>
          <div className="flex items-center gap-2 mb-2">
            <Zap size={16} className="text-indigo-400" />
            <h2 style={{ fontSize: 13, fontWeight: 700, color: "var(--app-text-strong)", textTransform: "uppercase" }}>Scoring Simulation</h2>
          </div>
          
          <div className="p-4 rounded-xl border bg-slate-900/50 space-y-4" style={{ borderColor: "var(--app-border-strong)" }}>
            <div style={{ fontSize: 11, color: "var(--app-text-muted)" }}>Simulated outcome for a typical $500K equipment bid:</div>
            
            <div className="space-y-3">
              {[
                { label: "High Price / High Quality", score: 82, trend: "up" },
                { label: "Low Price / Low Compliance", score: 45, trend: "down" },
                { label: "Optimal Balanced Quote", score: 94, trend: "up" }
              ].map((sim, i) => (
                <div key={i}>
                  <div className="flex items-center justify-between mb-1.5">
                    <span style={{ fontSize: 11, color: "var(--app-text-subtle)" }}>{sim.label}</span>
                    <span style={{ fontSize: 11, fontWeight: 700, color: sim.score > 70 ? "var(--app-success)" : "var(--app-danger)", fontFamily: "monospace" }}>{sim.score} pts</span>
                  </div>
                  <div className="w-full h-1 rounded-full bg-slate-800">
                    <div className="h-full rounded-full bg-indigo-500" style={{ width: `${sim.score}%` }} />
                  </div>
                </div>
              ))}
            </div>
          </div>

          <div className="p-4 rounded-xl border border-indigo-500/20 bg-indigo-500/5">
            <div className="flex items-center gap-2 mb-2">
              <Info size={14} className="text-indigo-400" />
              <span style={{ fontSize: 11, fontWeight: 700, color: "var(--app-text-strong)" }}>AI Insight</span>
            </div>
            <p style={{ fontSize: 11, color: "var(--app-text-muted)", lineHeight: 1.6 }}>
              Current weights heavily favor **Price (40%)**. This makes Apex Industrial (V001) the likely recommendation despite slightly higher risk. To prioritize reliability, increase **Risk Index** weight to 20%+.
            </p>
          </div>

          <button className="w-full py-2.5 rounded-lg border border-slate-700 text-xs font-bold text-slate-400 hover:bg-slate-800 transition-all flex items-center justify-center gap-2">
            <RotateCcw size={14} /> Reset to Defaults
          </button>
        </div>
      </div>
    </div>
  );
}
