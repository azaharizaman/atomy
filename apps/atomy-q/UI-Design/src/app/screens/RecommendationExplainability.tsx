import { useState } from 'react';
import {
  Award, Brain, RefreshCcw, ShieldCheck, Lightbulb, ChevronRight,
  Building, TrendingUp, BarChart3, Zap, Leaf, Truck, DollarSign,
  AlertTriangle, Upload, Info, SlidersHorizontal
} from 'lucide-react';
import { Link } from 'react-router';
import {
  BarChart, Bar, RadarChart, Radar, PolarGrid, PolarAngleAxis,
  PolarRadiusAxis, ResponsiveContainer, Tooltip, XAxis, YAxis, CartesianGrid, Cell
} from 'recharts';
import {
  recommendations, vendorScores, scoringModels, vendors, rfqs, formatCurrency
} from '../data/mockData';
import { SlideOver } from '../components/SlideOver';

const dimensionIcons: Record<string, typeof DollarSign> = {
  'Price/LCC': DollarSign,
  Delivery: Truck,
  Quality: Award,
  Risk: ShieldCheck,
  Sustainability: Leaf,
};

const dimensionColors: Record<string, string> = {
  'Price/LCC': '#6366f1',
  Delivery: '#0ea5e9',
  Quality: '#10b981',
  Risk: '#f59e0b',
  Sustainability: '#8b5cf6',
};

const confidenceLevelConfig = {
  high: { bg: 'bg-emerald-50', border: 'border-emerald-200', text: 'text-emerald-700', fill: '#10b981' },
  medium: { bg: 'bg-amber-50', border: 'border-amber-200', text: 'text-amber-700', fill: '#f59e0b' },
  low: { bg: 'bg-red-50', border: 'border-red-200', text: 'text-red-700', fill: '#ef4444' },
};

function ConfidenceGauge({ value, size = 160 }: { value: number; size?: number }) {
  const strokeWidth = 14;
  const radius = (size - strokeWidth) / 2;
  const cx = size / 2;
  const cy = size / 2;
  const startAngle = 135;
  const endAngle = 405;
  const range = endAngle - startAngle;
  const valueAngle = startAngle + (value / 100) * range;

  const polarToCartesian = (angle: number) => {
    const rad = ((angle - 90) * Math.PI) / 180;
    return { x: cx + radius * Math.cos(rad), y: cy + radius * Math.sin(rad) };
  };

  const arcPath = (start: number, end: number) => {
    const s = polarToCartesian(start);
    const e = polarToCartesian(end);
    const largeArc = end - start > 180 ? 1 : 0;
    return `M ${s.x} ${s.y} A ${radius} ${radius} 0 ${largeArc} 1 ${e.x} ${e.y}`;
  };

  const level = value >= 80 ? 'high' : value >= 60 ? 'medium' : 'low';
  const color = confidenceLevelConfig[level].fill;

  return (
    <svg width={size} height={size} viewBox={`0 0 ${size} ${size}`}>
      <path d={arcPath(startAngle, endAngle)} fill="none" stroke="#e2e8f0" strokeWidth={strokeWidth} strokeLinecap="round" />
      <path d={arcPath(startAngle, valueAngle)} fill="none" stroke={color} strokeWidth={strokeWidth} strokeLinecap="round" />
      <text x={cx} y={cy - 8} textAnchor="middle" className="fill-slate-900" fontSize={36} fontWeight={700}>{value}</text>
      <text x={cx} y={cy + 14} textAnchor="middle" className="fill-slate-400" fontSize={12} fontWeight={500}>/ 100</text>
    </svg>
  );
}

export function RecommendationExplainability() {
  const [showWhyModal, setShowWhyModal] = useState(false);
  const [showOverrideModal, setShowOverrideModal] = useState(false);
  const [showReRunModal, setShowReRunModal] = useState(false);
  const [selectedOverrideVendor, setSelectedOverrideVendor] = useState('');
  const [overrideReason, setOverrideReason] = useState('');

  const rec = recommendations[0];
  const rfq = rfqs.find((r) => r.id === rec.rfqId);
  const vendor = vendors.find((v) => v.id === rec.vendorId);
  const model = scoringModels.find((m) => m.id === 'sm-001');
  const levelCfg = confidenceLevelConfig[rec.confidenceLevel];

  const radarData = rec.factors.map((f) => ({
    dimension: f.dimension,
    recommended: f.score,
    ...Object.fromEntries(
      vendorScores
        .filter((vs) => vs.vendorId !== rec.vendorId)
        .map((vs) => [vs.name, vs[f.dimension.toLowerCase().replace('/lcc', '').replace('price', 'price') as keyof typeof vs] as number])
    ),
  }));

  const barData = vendorScores.map((vs) => ({
    name: vs.name.length > 16 ? vs.name.slice(0, 14) + '...' : vs.name,
    overall: vs.overall,
    recommended: vs.recommended,
  }));

  const [reRunWeights, setReRunWeights] = useState<Record<string, number>>(
    Object.fromEntries((model?.criteria ?? []).map((c) => [c.dimension, c.weight]))
  );
  const [reRunModel, setReRunModel] = useState(model?.id ?? '');

  return (
    <div className="flex flex-col h-full bg-slate-50">
      {/* Header */}
      <div className="bg-white border-b border-slate-200 px-6 py-4 flex-shrink-0">
        <div className="flex items-center gap-1.5 text-xs text-slate-500 mb-3">
          <Link to="/" className="hover:text-slate-700">Home</Link>
          <ChevronRight size={11} />
          <Link to="/comparison" className="hover:text-slate-700">Comparison</Link>
          <ChevronRight size={11} />
          <span className="text-slate-700" style={{ fontWeight: 500 }}>Recommendation</span>
        </div>

        <div className="flex items-start justify-between">
          <div>
            <div className="flex items-center gap-3 mb-1">
              <Brain size={20} className="text-indigo-600" />
              <h1 className="text-slate-900 text-lg" style={{ fontWeight: 700 }}>AI Recommendation</h1>
              <span className={`text-xs px-2.5 py-1 rounded-full border ${levelCfg.bg} ${levelCfg.border} ${levelCfg.text}`} style={{ fontWeight: 600 }}>
                {rec.confidenceLevel.charAt(0).toUpperCase() + rec.confidenceLevel.slice(1)} Confidence
              </span>
            </div>
            <div className="flex items-center gap-4 text-xs text-slate-500 mt-1">
              <span>RFQ: <b className="text-slate-700">{rec.rfqId}</b></span>
              <span className="text-slate-300">|</span>
              <span>{rfq?.title}</span>
              <span className="text-slate-300">|</span>
              <span>Model: <b className="text-slate-700">{model?.name}</b></span>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <button
              onClick={() => setShowReRunModal(true)}
              className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50"
            >
              <RefreshCcw size={14} />
              Re-Run
            </button>
            <button
              onClick={() => setShowOverrideModal(true)}
              className="flex items-center gap-2 border border-amber-300 bg-white text-amber-700 rounded-lg px-3 py-2 text-sm hover:bg-amber-50"
              style={{ fontWeight: 500 }}
            >
              <AlertTriangle size={14} />
              Override
            </button>
          </div>
        </div>
      </div>

      {/* Content */}
      <div className="flex-1 overflow-auto p-5 space-y-5">
        {/* Top Row: Recommendation Card + Confidence Gauge */}
        <div className="grid grid-cols-12 gap-5">
          {/* Recommended Vendor Card */}
          <div className="col-span-4 bg-white rounded-xl border border-emerald-200 shadow-sm p-5">
            <div className="flex items-center gap-2 mb-4">
              <Award size={15} className="text-emerald-600" />
              <span className="text-xs text-emerald-600" style={{ fontWeight: 700 }}>RECOMMENDED VENDOR</span>
            </div>
            <div className="flex items-center gap-3 mb-4">
              <div className="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                <Building size={18} className="text-emerald-700" />
              </div>
              <div>
                <div className="text-slate-900 text-base" style={{ fontWeight: 700 }}>{rec.vendorName}</div>
                <div className="text-emerald-600 text-xs">{vendor?.country} · {vendor?.category}</div>
              </div>
            </div>
            <div className="grid grid-cols-3 gap-3 py-3 border-t border-emerald-100">
              <div>
                <div className="text-xs text-slate-500">Score</div>
                <div className="text-emerald-700 text-lg" style={{ fontWeight: 700 }}>{rec.overallScore}</div>
              </div>
              <div>
                <div className="text-xs text-slate-500">Total</div>
                <div className="text-slate-800 text-sm" style={{ fontWeight: 600 }}>
                  {formatCurrency(rfq?.estimatedValue ?? 0)}
                </div>
              </div>
              <div>
                <div className="text-xs text-slate-500">Risk</div>
                <span className="text-xs bg-emerald-600 text-white px-1.5 py-0.5 rounded" style={{ fontWeight: 600 }}>
                  {vendor?.riskLevel.charAt(0).toUpperCase()}{vendor?.riskLevel.slice(1)}
                </span>
              </div>
            </div>
          </div>

          {/* Confidence Gauge */}
          <div className="col-span-3 bg-white rounded-xl border border-slate-200 shadow-sm p-5 flex flex-col items-center justify-center">
            <span className="text-xs text-slate-500 mb-2" style={{ fontWeight: 600 }}>CONFIDENCE SCORE</span>
            <ConfidenceGauge value={rec.confidence} />
            <span className={`text-xs mt-2 px-2 py-0.5 rounded-full ${levelCfg.bg} ${levelCfg.text}`} style={{ fontWeight: 600 }}>
              {rec.confidenceLevel.charAt(0).toUpperCase() + rec.confidenceLevel.slice(1)}
            </span>
          </div>

          {/* Contributing Factors */}
          <div className="col-span-5 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div className="flex items-center gap-2 px-5 py-3 border-b border-slate-100 bg-slate-50/50">
              <Zap size={14} className="text-indigo-600" />
              <span className="text-slate-800 text-sm" style={{ fontWeight: 700 }}>Top Contributing Factors</span>
              <button
                onClick={() => setShowWhyModal(true)}
                className="ml-auto text-xs text-indigo-600 hover:text-indigo-700 flex items-center gap-1"
                style={{ fontWeight: 500 }}
              >
                Full Breakdown <ChevronRight size={12} />
              </button>
            </div>
            <div className="px-5 py-3 space-y-3">
              {rec.factors
                .sort((a, b) => b.contribution - a.contribution)
                .map((f) => {
                  const Icon = dimensionIcons[f.dimension] ?? Info;
                  const color = dimensionColors[f.dimension] ?? '#6366f1';
                  return (
                    <div key={f.dimension}>
                      <div className="flex items-center justify-between mb-1">
                        <div className="flex items-center gap-2">
                          <Icon size={13} style={{ color }} />
                          <span className="text-xs text-slate-700" style={{ fontWeight: 600 }}>{f.dimension}</span>
                        </div>
                        <div className="flex items-center gap-3 text-xs text-slate-500">
                          <span>Score: <b className="text-slate-700">{f.score}</b></span>
                          <span>W: <b className="text-slate-700">{f.weight}%</b></span>
                          <span style={{ fontWeight: 700, color }}>+{f.contribution.toFixed(1)}</span>
                        </div>
                      </div>
                      <div className="h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div className="h-full rounded-full" style={{ width: `${(f.contribution / 35) * 100}%`, backgroundColor: color }} />
                      </div>
                    </div>
                  );
                })}
            </div>
          </div>
        </div>

        {/* Trade-off Narrative */}
        <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
          <div className="flex items-center gap-2 mb-3">
            <Lightbulb size={15} className="text-amber-500" />
            <span className="text-slate-800 text-sm" style={{ fontWeight: 700 }}>Trade-Off Analysis</span>
          </div>
          <p className="text-slate-600 text-sm leading-relaxed">{rec.tradeoffNarrative}</p>
        </div>

        {/* Charts Row */}
        <div className="grid grid-cols-2 gap-5">
          {/* Radar Chart */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div className="flex items-center gap-2 mb-4">
              <TrendingUp size={15} className="text-indigo-600" />
              <span className="text-slate-800 text-sm" style={{ fontWeight: 700 }}>Multi-Dimension Comparison</span>
            </div>
            <ResponsiveContainer width="100%" height={280}>
              <RadarChart data={radarData}>
                <PolarGrid stroke="#e2e8f0" />
                <PolarAngleAxis dataKey="dimension" tick={{ fontSize: 11, fill: '#64748b' }} />
                <PolarRadiusAxis angle={90} domain={[0, 100]} tick={{ fontSize: 10, fill: '#94a3b8' }} />
                <Radar name={rec.vendorName} dataKey="recommended" stroke="#10b981" fill="#10b981" fillOpacity={0.2} strokeWidth={2} />
                {vendorScores
                  .filter((vs) => !vs.recommended)
                  .map((vs, i) => {
                    const colors = ['#6366f1', '#f59e0b', '#94a3b8'];
                    return (
                      <Radar
                        key={vs.vendorId}
                        name={vs.name}
                        dataKey={vs.name}
                        stroke={colors[i % colors.length]}
                        fill={colors[i % colors.length]}
                        fillOpacity={0.05}
                        strokeWidth={1}
                        strokeDasharray="4 4"
                      />
                    );
                  })}
                <Tooltip contentStyle={{ fontSize: 12, borderRadius: 8, border: '1px solid #e2e8f0' }} />
              </RadarChart>
            </ResponsiveContainer>
            <div className="flex items-center gap-4 justify-center mt-2">
              {vendorScores.map((vs, i) => {
                const colors = ['#10b981', '#6366f1', '#f59e0b', '#94a3b8'];
                return (
                  <div key={vs.vendorId} className="flex items-center gap-1.5 text-xs text-slate-600">
                    <span className="w-2.5 h-2.5 rounded-full" style={{ backgroundColor: colors[i] }} />
                    {vs.name}
                  </div>
                );
              })}
            </div>
          </div>

          {/* Bar Chart */}
          <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div className="flex items-center gap-2 mb-4">
              <BarChart3 size={15} className="text-indigo-600" />
              <span className="text-slate-800 text-sm" style={{ fontWeight: 700 }}>Overall Score Ranking</span>
            </div>
            <ResponsiveContainer width="100%" height={280}>
              <BarChart data={barData} layout="vertical" margin={{ left: 10, right: 20 }}>
                <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" />
                <XAxis type="number" domain={[0, 100]} tick={{ fontSize: 11, fill: '#94a3b8' }} />
                <YAxis dataKey="name" type="category" tick={{ fontSize: 11, fill: '#64748b' }} width={110} />
                <Tooltip contentStyle={{ fontSize: 12, borderRadius: 8, border: '1px solid #e2e8f0' }} />
                <Bar dataKey="overall" radius={[0, 4, 4, 0]} barSize={24}>
                  {barData.map((entry) => (
                    <Cell key={entry.name} fill={entry.recommended ? '#10b981' : '#a5b4fc'} />
                  ))}
                </Bar>
              </BarChart>
            </ResponsiveContainer>
          </div>
        </div>
      </div>

      {/* ─── Why This Recommendation Modal ─── */}
      <SlideOver
        open={showWhyModal}
        onOpenChange={setShowWhyModal}
        title="Why This Recommendation"
        description={`Full MCDA breakdown for ${rec.vendorName}`}
        width="lg"
      >
        <div className="space-y-5">
          <div className="bg-emerald-50 border border-emerald-200 rounded-xl p-4">
            <div className="flex items-center gap-2 mb-2">
              <Brain size={15} className="text-emerald-600" />
              <span className="text-sm text-emerald-700" style={{ fontWeight: 700 }}>Multi-Criteria Decision Analysis</span>
            </div>
            <p className="text-xs text-emerald-600">
              Scoring model: <b>{model?.name} v{model?.version}</b> · {rec.factors.length} dimensions evaluated
            </p>
          </div>

          <div className="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <table className="w-full">
              <thead>
                <tr className="border-b border-slate-100 bg-slate-50">
                  <th className="text-left px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>DIMENSION</th>
                  <th className="text-center px-3 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>SCORE</th>
                  <th className="text-center px-3 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>WEIGHT</th>
                  <th className="text-center px-3 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>CONTRIBUTION</th>
                  <th className="px-4 py-2.5 text-xs text-slate-500" style={{ fontWeight: 600 }}>NARRATIVE</th>
                </tr>
              </thead>
              <tbody>
                {rec.factors
                  .sort((a, b) => b.contribution - a.contribution)
                  .map((f) => {
                    const Icon = dimensionIcons[f.dimension] ?? Info;
                    return (
                      <tr key={f.dimension} className="border-b border-slate-50 hover:bg-slate-50">
                        <td className="px-4 py-3">
                          <div className="flex items-center gap-2">
                            <Icon size={13} className="text-slate-500" />
                            <span className="text-sm text-slate-700" style={{ fontWeight: 600 }}>{f.dimension}</span>
                          </div>
                        </td>
                        <td className="px-3 py-3 text-center">
                          <span className={`text-xs px-2 py-0.5 rounded ${f.score >= 90 ? 'bg-emerald-50 text-emerald-700' : f.score >= 80 ? 'bg-indigo-50 text-indigo-700' : 'bg-amber-50 text-amber-700'}`} style={{ fontWeight: 600 }}>
                            {f.score}
                          </span>
                        </td>
                        <td className="px-3 py-3 text-center text-xs text-slate-600" style={{ fontWeight: 600 }}>{f.weight}%</td>
                        <td className="px-3 py-3 text-center text-xs text-indigo-600" style={{ fontWeight: 700 }}>+{f.contribution.toFixed(1)}</td>
                        <td className="px-4 py-3 text-xs text-slate-500 leading-relaxed">{f.narrative}</td>
                      </tr>
                    );
                  })}
              </tbody>
              <tfoot>
                <tr className="bg-slate-50 border-t border-slate-200">
                  <td className="px-4 py-3 text-sm text-slate-800" style={{ fontWeight: 700 }}>Total</td>
                  <td className="px-3 py-3 text-center text-sm text-emerald-700" style={{ fontWeight: 700 }}>{rec.overallScore}</td>
                  <td className="px-3 py-3 text-center text-xs text-slate-600" style={{ fontWeight: 600 }}>100%</td>
                  <td className="px-3 py-3 text-center text-sm text-indigo-700" style={{ fontWeight: 700 }}>
                    {rec.factors.reduce((sum, f) => sum + f.contribution, 0).toFixed(1)}
                  </td>
                  <td />
                </tr>
              </tfoot>
            </table>
          </div>

          <div className="space-y-3">
            <span className="text-sm text-slate-800" style={{ fontWeight: 700 }}>Comparative Per-Dimension Scores</span>
            {rec.factors.map((f) => (
              <div key={f.dimension} className="bg-white rounded-lg border border-slate-200 p-3">
                <div className="text-xs text-slate-600 mb-2" style={{ fontWeight: 600 }}>{f.dimension}</div>
                <div className="space-y-1.5">
                  {vendorScores
                    .sort((a, b) => {
                      const key = f.dimension.toLowerCase().replace('/lcc', '') as keyof typeof a;
                      return (b[key] as number) - (a[key] as number);
                    })
                    .map((vs) => {
                      const key = f.dimension.toLowerCase().replace('/lcc', '') as keyof typeof vs;
                      const score = vs[key] as number;
                      return (
                        <div key={vs.vendorId} className="flex items-center gap-2">
                          <span className="text-xs text-slate-500 w-32 truncate">{vs.name}</span>
                          <div className="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div
                              className="h-full rounded-full"
                              style={{
                                width: `${score}%`,
                                backgroundColor: vs.recommended ? '#10b981' : '#a5b4fc',
                              }}
                            />
                          </div>
                          <span className="text-xs text-slate-600 w-7 text-right" style={{ fontWeight: 600 }}>{score}</span>
                        </div>
                      );
                    })}
                </div>
              </div>
            ))}
          </div>
        </div>
      </SlideOver>

      {/* ─── Override Modal ─── */}
      <SlideOver
        open={showOverrideModal}
        onOpenChange={setShowOverrideModal}
        title="Override Recommendation"
        description="Select an alternative vendor and provide justification"
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button
              onClick={() => setShowOverrideModal(false)}
              className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50"
            >
              Cancel
            </button>
            <button
              disabled={!selectedOverrideVendor || !overrideReason.trim()}
              className={`rounded-lg px-4 py-2 text-sm text-white ${selectedOverrideVendor && overrideReason.trim() ? 'bg-amber-600 hover:bg-amber-700' : 'bg-amber-300 cursor-not-allowed'}`}
              style={{ fontWeight: 500 }}
            >
              Submit Override
            </button>
          </div>
        }
      >
        <div className="space-y-5">
          <div className="bg-amber-50 border border-amber-200 rounded-xl p-3">
            <div className="flex items-center gap-2">
              <AlertTriangle size={14} className="text-amber-600" />
              <span className="text-xs text-amber-700" style={{ fontWeight: 500 }}>
                Overriding the AI recommendation requires mandatory justification and will be logged in the decision trail.
              </span>
            </div>
          </div>

          <div>
            <label className="text-slate-600 text-xs block mb-2" style={{ fontWeight: 600 }}>Select Alternative Vendor</label>
            <div className="space-y-2">
              {vendorScores
                .filter((vs) => vs.vendorId !== rec.vendorId)
                .map((vs) => (
                  <label
                    key={vs.vendorId}
                    className={`flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors ${
                      selectedOverrideVendor === vs.vendorId
                        ? 'border-amber-400 bg-amber-50'
                        : 'border-slate-200 hover:bg-slate-50'
                    }`}
                  >
                    <input
                      type="radio"
                      name="overrideVendor"
                      value={vs.vendorId}
                      checked={selectedOverrideVendor === vs.vendorId}
                      onChange={(e) => setSelectedOverrideVendor(e.target.value)}
                      className="accent-amber-600"
                    />
                    <div className="flex-1">
                      <div className="text-sm text-slate-800" style={{ fontWeight: 600 }}>{vs.name}</div>
                      <div className="text-xs text-slate-500">Overall Score: {vs.overall} · Price: {vs.price} · Quality: {vs.quality}</div>
                    </div>
                    <span className="text-xs text-slate-400" style={{ fontWeight: 600 }}>#{vendorScores.indexOf(vs) + 1}</span>
                  </label>
                ))}
            </div>
          </div>

          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 600 }}>
              Justification <span className="text-red-500">*</span>
            </label>
            <textarea
              rows={4}
              value={overrideReason}
              onChange={(e) => setOverrideReason(e.target.value)}
              className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 resize-none focus:border-amber-400 outline-none"
              placeholder="Explain the reason for overriding the AI recommendation..."
            />
          </div>

          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 600 }}>
              Supporting Evidence (optional)
            </label>
            <div className="border-2 border-dashed border-slate-200 rounded-lg p-6 text-center hover:border-slate-300 cursor-pointer">
              <Upload size={20} className="text-slate-400 mx-auto mb-2" />
              <span className="text-xs text-slate-500">Click to upload or drag and drop</span>
              <div className="text-xs text-slate-400 mt-1">PDF, DOCX, XLSX up to 10MB</div>
            </div>
          </div>
        </div>
      </SlideOver>

      {/* ─── Re-Run Modal ─── */}
      <SlideOver
        open={showReRunModal}
        onOpenChange={setShowReRunModal}
        title="Request Re-Run"
        description="Adjust weights and scoring parameters"
        width="md"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button
              onClick={() => setShowReRunModal(false)}
              className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50"
            >
              Cancel
            </button>
            <button
              className="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2 text-sm"
              style={{ fontWeight: 500 }}
            >
              <span className="flex items-center gap-2">
                <RefreshCcw size={14} />
                Run Comparison
              </span>
            </button>
          </div>
        }
      >
        <div className="space-y-5">
          <div>
            <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 600 }}>Scoring Model</label>
            <select
              value={reRunModel}
              onChange={(e) => setReRunModel(e.target.value)}
              className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white"
            >
              {scoringModels.map((sm) => (
                <option key={sm.id} value={sm.id}>
                  {sm.name} v{sm.version} {sm.status === 'draft' ? '(Draft)' : ''}
                </option>
              ))}
            </select>
          </div>

          <div>
            <div className="flex items-center gap-2 mb-3">
              <SlidersHorizontal size={14} className="text-indigo-600" />
              <span className="text-slate-700 text-sm" style={{ fontWeight: 700 }}>Weight Adjustments</span>
              <span className="ml-auto text-xs text-slate-400">
                Total: <b className={Object.values(reRunWeights).reduce((a, b) => a + b, 0) === 100 ? 'text-emerald-600' : 'text-red-600'}>
                  {Object.values(reRunWeights).reduce((a, b) => a + b, 0)}%
                </b>
              </span>
            </div>
            <div className="space-y-4">
              {(model?.criteria ?? []).map((c) => {
                const Icon = dimensionIcons[c.dimension] ?? Info;
                const color = dimensionColors[c.dimension] ?? '#6366f1';
                return (
                  <div key={c.dimension}>
                    <div className="flex items-center justify-between mb-1">
                      <div className="flex items-center gap-2">
                        <Icon size={13} style={{ color }} />
                        <span className="text-xs text-slate-700" style={{ fontWeight: 600 }}>{c.dimension}</span>
                        {c.mandatory && <span className="text-xs text-red-400">Required</span>}
                      </div>
                      <span className="text-xs text-slate-700" style={{ fontWeight: 700 }}>{reRunWeights[c.dimension]}%</span>
                    </div>
                    <input
                      type="range"
                      min={0}
                      max={50}
                      value={reRunWeights[c.dimension]}
                      onChange={(e) => setReRunWeights((prev) => ({ ...prev, [c.dimension]: parseInt(e.target.value) }))}
                      className="w-full h-1.5 bg-slate-200 rounded-full appearance-none cursor-pointer accent-indigo-600"
                    />
                    <div className="flex justify-between text-xs text-slate-400 mt-0.5">
                      <span>0%</span>
                      <span>50%</span>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>

          <div className="bg-slate-50 border border-slate-200 rounded-xl p-3">
            <span className="text-xs text-slate-500">
              Re-running will create a new comparison run using the adjusted weights. The current run will be preserved in the decision trail.
            </span>
          </div>
        </div>
      </SlideOver>
    </div>
  );
}
