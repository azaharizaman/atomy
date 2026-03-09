import { useState } from 'react';
import { useParams, Link } from 'react-router';
import {
  ChevronRight, ArrowLeft, Building, Globe, Tag, Shield, FileCheck,
  TrendingUp, Clock, Star, BarChart3, FileText, CheckCircle,
  AlertTriangle, XCircle, Award, ExternalLink, CircleDot
} from 'lucide-react';
import {
  LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer
} from 'recharts';
import {
  vendors, vendorPerformance, dueDiligenceChecklists, sanctionsScreenings,
  riskItems, rfqs, quoteSubmissions, formatDate, formatDateTime,
  riskLevelConfig, statusColors
} from '../data/mockData';

type Tab = 'performance' | 'compliance' | 'history';

const scoreColor = (score: number): string => {
  if (score >= 85) return 'text-emerald-700 bg-emerald-50 border-emerald-300';
  if (score >= 70) return 'text-amber-700 bg-amber-50 border-amber-300';
  return 'text-red-700 bg-red-50 border-red-300';
};

const scoreBgRing = (score: number): string => {
  if (score >= 85) return 'border-emerald-500';
  if (score >= 70) return 'border-amber-500';
  return 'border-red-500';
};

const sanctionsConfig: Record<string, { bg: string; text: string; label: string }> = {
  clear: { bg: 'bg-emerald-100 text-emerald-700', text: 'text-emerald-700', label: 'Clear' },
  flagged: { bg: 'bg-red-100 text-red-700', text: 'text-red-700', label: 'Flagged' },
};

const ddConfig: Record<string, { bg: string; label: string }> = {
  complete: { bg: 'bg-emerald-100 text-emerald-700', label: 'Complete' },
  partial: { bg: 'bg-amber-100 text-amber-700', label: 'Partial' },
  incomplete: { bg: 'bg-red-100 text-red-700', label: 'Incomplete' },
};

export function VendorProfile() {
  const { id } = useParams<{ id: string }>();
  const [activeTab, setActiveTab] = useState<Tab>('performance');

  const vendor = vendors.find(v => v.id === id);

  if (!vendor) {
    return (
      <div className="flex flex-col h-full bg-slate-50 items-center justify-center">
        <Building size={40} className="text-slate-300 mb-3" />
        <h2 className="text-lg text-slate-700" style={{ fontWeight: 600 }}>Vendor not found</h2>
        <p className="text-sm text-slate-500 mt-1">The vendor with ID "{id}" could not be located.</p>
        <Link to="/" className="mt-4 flex items-center gap-2 border border-slate-200 bg-white rounded-lg px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50">
          <ArrowLeft size={14} />
          Back to Dashboard
        </Link>
      </div>
    );
  }

  const perfData = vendorPerformance.filter(p => p.vendorId === vendor.id);
  const ddChecklist = dueDiligenceChecklists.find(d => d.vendorId === vendor.id);
  const vendorScreenings = sanctionsScreenings.filter(s => s.vendorId === vendor.id);
  const vendorRisks = riskItems.filter(r => r.vendorId === vendor.id);
  const vendorQuotes = quoteSubmissions.filter(q => q.vendorId === vendor.id);
  const participatedRfqIds = Array.from(new Set(vendorQuotes.map(q => q.rfqId).filter(Boolean)));
  const participatedRfqs = rfqs.filter(r => participatedRfqIds.includes(r.id));

  const riskCfg = riskLevelConfig[vendor.riskLevel];
  const sanctionsCfg = sanctionsConfig[vendor.sanctionsStatus] || sanctionsConfig.clear;
  const ddCfg = ddConfig[vendor.dueDiligence] || ddConfig.complete;

  const latestPerf = perfData.length > 0 ? perfData[perfData.length - 1] : null;

  const tabs: { key: Tab; label: string; icon: typeof TrendingUp }[] = [
    { key: 'performance', label: 'Performance', icon: TrendingUp },
    { key: 'compliance', label: 'Compliance', icon: Shield },
    { key: 'history', label: 'History', icon: Clock },
  ];

  return (
    <div className="flex flex-col h-full bg-slate-50">
      {/* Header */}
      <div className="bg-white border-b border-slate-200 px-6 py-4 flex-shrink-0">
        <div className="flex items-center gap-1.5 text-xs text-slate-500 mb-3">
          <Link to="/" className="hover:text-slate-700">Home</Link>
          <ChevronRight size={11} />
          <span className="hover:text-slate-700">Vendors</span>
          <ChevronRight size={11} />
          <span className="text-slate-700" style={{ fontWeight: 500 }}>{vendor.name}</span>
        </div>

        <div className="flex items-start gap-5">
          {/* Score Circle */}
          <div className={`w-16 h-16 rounded-full border-[3px] flex items-center justify-center flex-shrink-0 ${scoreBgRing(vendor.overallScore)}`}>
            <div className="text-center">
              <div className="text-xl text-slate-800" style={{ fontWeight: 800 }}>{vendor.overallScore}</div>
            </div>
          </div>

          {/* Vendor Info */}
          <div className="flex-1 min-w-0">
            <div className="flex items-center gap-3 mb-1.5">
              <h1 className="text-lg text-slate-900" style={{ fontWeight: 700 }}>{vendor.name}</h1>
            </div>
            <div className="flex items-center gap-2 flex-wrap">
              <span className="flex items-center gap-1 text-xs text-slate-500">
                <Globe size={11} />
                {vendor.country}
              </span>
              <span className="flex items-center gap-1 text-xs bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded" style={{ fontWeight: 500 }}>
                <Tag size={10} />
                {vendor.category}
              </span>
              <span className={`text-xs px-2 py-0.5 rounded capitalize ${riskCfg.badgeBg} ${riskCfg.badgeText}`} style={{ fontWeight: 600 }}>
                {vendor.riskLevel} risk
              </span>
              <span className={`text-xs px-2 py-0.5 rounded ${sanctionsCfg.bg}`} style={{ fontWeight: 600 }}>
                <Shield size={9} className="inline mr-0.5" />
                Sanctions: {sanctionsCfg.label}
              </span>
              <span className={`text-xs px-2 py-0.5 rounded ${ddCfg.bg}`} style={{ fontWeight: 600 }}>
                <FileCheck size={9} className="inline mr-0.5" />
                DD: {ddCfg.label}
              </span>
            </div>
          </div>

          <Link
            to="/"
            className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 flex-shrink-0"
          >
            <ArrowLeft size={14} />
            Back
          </Link>
        </div>

        {/* Tabs */}
        <div className="flex items-center gap-1 mt-4 border-b border-transparent">
          {tabs.map(tab => (
            <button
              key={tab.key}
              onClick={() => setActiveTab(tab.key)}
              className={`flex items-center gap-1.5 px-4 py-2 rounded-t-lg text-sm transition-colors border-b-2 ${
                activeTab === tab.key
                  ? 'text-indigo-700 border-indigo-600 bg-indigo-50/50'
                  : 'text-slate-500 border-transparent hover:text-slate-700 hover:bg-slate-50'
              }`}
              style={{ fontWeight: activeTab === tab.key ? 600 : 400 }}
            >
              <tab.icon size={14} />
              {tab.label}
            </button>
          ))}
        </div>
      </div>

      {/* Tab Content */}
      <div className="flex-1 overflow-auto p-5">
        {/* ── Performance Tab ── */}
        {activeTab === 'performance' && (
          <div className="space-y-5">
            {/* Score Cards */}
            <div className="grid grid-cols-4 gap-3">
              {[
                { label: 'Overall Score', value: vendor.overallScore, suffix: '/100', icon: Star },
                { label: 'On-Time Delivery', value: latestPerf?.onTimeDelivery ?? '—', suffix: '%', icon: Clock },
                { label: 'Quality Score', value: latestPerf?.qualityScore ?? '—', suffix: '/100', icon: Award },
                { label: 'Response Rate', value: latestPerf?.responseRate ?? '—', suffix: '%', icon: BarChart3 },
              ].map(card => (
                <div key={card.label} className="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                  <div className="flex items-center gap-1.5 text-xs text-slate-500 mb-2">
                    <card.icon size={12} />
                    {card.label}
                  </div>
                  <div className="flex items-baseline gap-1">
                    <span className={`text-2xl ${typeof card.value === 'number' ? scoreColor(card.value).split(' ')[0] : 'text-slate-800'}`} style={{ fontWeight: 800 }}>
                      {card.value}
                    </span>
                    <span className="text-xs text-slate-400">{card.suffix}</span>
                  </div>
                </div>
              ))}
            </div>

            {/* Performance Trend Chart */}
            {perfData.length > 0 && (
              <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <div className="flex items-center gap-2 mb-4">
                  <TrendingUp size={14} className="text-indigo-600" />
                  <span className="text-sm text-slate-800" style={{ fontWeight: 600 }}>Performance Trend</span>
                  <span className="text-xs text-slate-400 ml-1">(Last {perfData.length} months)</span>
                </div>
                <div className="h-64">
                  <ResponsiveContainer width="100%" height="100%">
                    <LineChart data={perfData} margin={{ top: 5, right: 20, left: 0, bottom: 5 }}>
                      <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" />
                      <XAxis dataKey="month" tick={{ fontSize: 11, fill: '#94a3b8' }} />
                      <YAxis domain={[60, 100]} tick={{ fontSize: 11, fill: '#94a3b8' }} />
                      <Tooltip
                        contentStyle={{ fontSize: 12, borderRadius: 8, border: '1px solid #e2e8f0' }}
                        labelStyle={{ fontWeight: 600, color: '#334155' }}
                      />
                      <Line type="monotone" dataKey="onTimeDelivery" name="On-Time Delivery" stroke="#10b981" strokeWidth={2} dot={{ r: 3 }} />
                      <Line type="monotone" dataKey="qualityScore" name="Quality" stroke="#6366f1" strokeWidth={2} dot={{ r: 3 }} />
                      <Line type="monotone" dataKey="responseRate" name="Response Rate" stroke="#f59e0b" strokeWidth={2} dot={{ r: 3 }} />
                    </LineChart>
                  </ResponsiveContainer>
                </div>
              </div>
            )}

            {perfData.length === 0 && (
              <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-8 text-center text-slate-400">
                <BarChart3 size={28} className="mx-auto mb-2 opacity-40" />
                <div className="text-sm" style={{ fontWeight: 500 }}>No performance history available</div>
              </div>
            )}

            {/* Quick Stats */}
            <div className="grid grid-cols-3 gap-3">
              {[
                { label: 'Active RFQs', value: vendor.activeRfqs },
                { label: 'Total Quotes', value: vendor.totalQuotes },
                { label: 'Avg Response Days', value: `${vendor.avgResponseDays}d` },
              ].map(stat => (
                <div key={stat.label} className="bg-white rounded-xl border border-slate-200 shadow-sm p-4 flex items-center gap-3">
                  <div className="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center">
                    <CircleDot size={16} className="text-indigo-600" />
                  </div>
                  <div>
                    <div className="text-xs text-slate-500">{stat.label}</div>
                    <div className="text-lg text-slate-800" style={{ fontWeight: 700 }}>{stat.value}</div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* ── Compliance Tab ── */}
        {activeTab === 'compliance' && (
          <div className="space-y-5">
            {/* Due Diligence Checklist */}
            <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
              <div className="flex items-center gap-3 px-5 py-3.5 border-b border-slate-100 bg-slate-50/50">
                <div className="w-7 h-7 rounded-lg bg-indigo-100 flex items-center justify-center">
                  <FileCheck size={14} className="text-indigo-600" />
                </div>
                <span className="text-sm text-slate-800" style={{ fontWeight: 700 }}>Due Diligence Checklist</span>
                {ddChecklist && (
                  <span className="text-xs text-slate-400 ml-1">
                    {ddChecklist.items.filter(i => i.status === 'complete').length}/{ddChecklist.items.length} completed
                  </span>
                )}
              </div>
              {ddChecklist ? (
                <div className="divide-y divide-slate-50">
                  {ddChecklist.items.map(item => (
                    <div key={item.id} className="flex items-center gap-3 px-5 py-3">
                      {item.status === 'complete' ? (
                        <CheckCircle size={16} className="text-emerald-500 flex-shrink-0" />
                      ) : (
                        <div className="w-4 h-4 rounded-full border-2 border-amber-400 flex-shrink-0" />
                      )}
                      <span className="text-sm text-slate-700 flex-1" style={{ fontWeight: 500 }}>{item.label}</span>
                      <span className={`text-xs px-2 py-0.5 rounded ${statusColors[item.status] || ''}`} style={{ fontWeight: 600 }}>
                        {item.status}
                      </span>
                      {item.attachmentId && (
                        <span className="text-xs text-indigo-600 flex items-center gap-1 cursor-pointer hover:underline">
                          <FileText size={10} />
                          Attached
                        </span>
                      )}
                    </div>
                  ))}
                </div>
              ) : (
                <div className="p-8 text-center text-slate-400">
                  <FileCheck size={28} className="mx-auto mb-2 opacity-40" />
                  <div className="text-sm" style={{ fontWeight: 500 }}>No due diligence checklist found for this vendor</div>
                </div>
              )}
            </div>

            {/* Sanctions Screening History */}
            <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
              <div className="flex items-center gap-3 px-5 py-3.5 border-b border-slate-100 bg-slate-50/50">
                <div className="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center">
                  <Shield size={14} className="text-slate-600" />
                </div>
                <span className="text-sm text-slate-800" style={{ fontWeight: 700 }}>Sanctions Screening History</span>
                <span className="text-xs text-slate-400 ml-1">{vendorScreenings.length} screening{vendorScreenings.length !== 1 ? 's' : ''}</span>
              </div>
              {vendorScreenings.length > 0 ? (
                <div className="divide-y divide-slate-50">
                  {vendorScreenings.map(screening => (
                    <div key={screening.id} className={`flex items-start gap-3 px-5 py-3 ${screening.result === 'flagged' ? 'bg-red-50/30' : ''}`}>
                      {screening.result === 'clear' ? (
                        <CheckCircle size={16} className="text-emerald-500 flex-shrink-0 mt-0.5" />
                      ) : (
                        <XCircle size={16} className="text-red-500 flex-shrink-0 mt-0.5" />
                      )}
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2 mb-0.5">
                          <span className={`text-xs px-2 py-0.5 rounded capitalize ${
                            screening.result === 'clear' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'
                          }`} style={{ fontWeight: 600 }}>
                            {screening.result}
                          </span>
                          <span className="text-xs text-slate-500">{formatDateTime(screening.screenedAt)}</span>
                        </div>
                        {screening.matchedEntries.length > 0 && (
                          <div className="mt-1">
                            {screening.matchedEntries.map((entry, i) => (
                              <div key={i} className="text-xs text-red-600 leading-relaxed">{entry}</div>
                            ))}
                          </div>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="p-8 text-center text-slate-400">
                  <Shield size={28} className="mx-auto mb-2 opacity-40" />
                  <div className="text-sm" style={{ fontWeight: 500 }}>No sanctions screenings recorded</div>
                </div>
              )}
            </div>

            {/* Risk Items */}
            <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
              <div className="flex items-center gap-3 px-5 py-3.5 border-b border-slate-100 bg-slate-50/50">
                <div className="w-7 h-7 rounded-lg bg-orange-100 flex items-center justify-center">
                  <AlertTriangle size={14} className="text-orange-600" />
                </div>
                <span className="text-sm text-slate-800" style={{ fontWeight: 700 }}>Risk Items</span>
                <span className="text-xs text-slate-400 ml-1">{vendorRisks.length} item{vendorRisks.length !== 1 ? 's' : ''}</span>
              </div>
              {vendorRisks.length > 0 ? (
                <div className="divide-y divide-slate-50">
                  {vendorRisks.map(risk => {
                    const cfg = riskLevelConfig[risk.level];
                    return (
                      <div key={risk.id} className="flex items-start gap-3 px-5 py-3">
                        <div className={`w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 ${cfg.bg} border ${cfg.border}`}>
                          <AlertTriangle size={13} className={cfg.text} />
                        </div>
                        <div className="flex-1 min-w-0">
                          <div className="flex items-center gap-2 mb-0.5">
                            <span className="text-sm text-slate-800" style={{ fontWeight: 600 }}>{risk.title}</span>
                            <span className={`text-xs px-2 py-0.5 rounded capitalize ${cfg.badgeBg} ${cfg.badgeText}`} style={{ fontWeight: 600 }}>
                              {risk.level}
                            </span>
                            <span className="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded">{risk.category}</span>
                            {risk.escalated && (
                              <span className="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded" style={{ fontWeight: 500 }}>Escalated</span>
                            )}
                          </div>
                          <p className="text-xs text-slate-500 leading-relaxed">{risk.description}</p>
                          <div className="text-xs text-slate-400 mt-1">RFQ: {risk.rfqId} · Status: {risk.status}</div>
                        </div>
                      </div>
                    );
                  })}
                </div>
              ) : (
                <div className="p-8 text-center text-slate-400">
                  <Shield size={28} className="mx-auto mb-2 opacity-40" />
                  <div className="text-sm" style={{ fontWeight: 500 }}>No risk items for this vendor</div>
                </div>
              )}
            </div>
          </div>
        )}

        {/* ── History Tab ── */}
        {activeTab === 'history' && (
          <div className="space-y-5">
            {/* Prior Quote Outcomes */}
            <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
              <div className="flex items-center gap-3 px-5 py-3.5 border-b border-slate-100 bg-slate-50/50">
                <div className="w-7 h-7 rounded-lg bg-blue-100 flex items-center justify-center">
                  <FileText size={14} className="text-blue-600" />
                </div>
                <span className="text-sm text-slate-800" style={{ fontWeight: 700 }}>RFQ Participation</span>
                <span className="text-xs text-slate-400 ml-1">{participatedRfqs.length} RFQ{participatedRfqs.length !== 1 ? 's' : ''}</span>
              </div>
              {participatedRfqs.length > 0 ? (
                <div className="overflow-x-auto">
                  <table className="w-full">
                    <thead>
                      <tr className="border-b border-slate-100">
                        {['RFQ ID', 'Title', 'Category', 'Status', 'Deadline', 'Value', ''].map(h => (
                          <th key={h} className="text-left text-xs text-slate-500 px-4 py-2.5" style={{ fontWeight: 600 }}>{h}</th>
                        ))}
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-50">
                      {participatedRfqs.map(rfq => {
                        const quote = vendorQuotes.find(q => q.rfqId === rfq.id);
                        return (
                          <tr key={rfq.id} className="hover:bg-slate-50 transition-colors">
                            <td className="px-4 py-3">
                              <span className="text-xs text-indigo-600 font-mono" style={{ fontWeight: 600 }}>{rfq.id}</span>
                            </td>
                            <td className="px-4 py-3">
                              <span className="text-sm text-slate-700" style={{ fontWeight: 500 }}>{rfq.title}</span>
                            </td>
                            <td className="px-4 py-3">
                              <span className="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded">{rfq.category}</span>
                            </td>
                            <td className="px-4 py-3">
                              <span className={`text-xs px-2 py-0.5 rounded ${statusColors[rfq.status] || ''}`} style={{ fontWeight: 600 }}>
                                {rfq.status}
                              </span>
                            </td>
                            <td className="px-4 py-3 text-xs text-slate-500">{formatDate(rfq.deadline)}</td>
                            <td className="px-4 py-3">
                              {quote && (
                                <span className="text-xs text-slate-700" style={{ fontWeight: 500 }}>
                                  ${quote.totalAmount.toLocaleString()}
                                </span>
                              )}
                            </td>
                            <td className="px-4 py-3">
                              <Link
                                to={`/rfq/${rfq.id}`}
                                className="flex items-center gap-1 text-xs text-indigo-600 hover:underline"
                              >
                                <ExternalLink size={10} />
                                View
                              </Link>
                            </td>
                          </tr>
                        );
                      })}
                    </tbody>
                  </table>
                </div>
              ) : (
                <div className="p-8 text-center text-slate-400">
                  <FileText size={28} className="mx-auto mb-2 opacity-40" />
                  <div className="text-sm" style={{ fontWeight: 500 }}>No RFQ participation found</div>
                </div>
              )}
            </div>

            {/* Quote Submissions History */}
            <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
              <div className="flex items-center gap-3 px-5 py-3.5 border-b border-slate-100 bg-slate-50/50">
                <div className="w-7 h-7 rounded-lg bg-emerald-100 flex items-center justify-center">
                  <Award size={14} className="text-emerald-600" />
                </div>
                <span className="text-sm text-slate-800" style={{ fontWeight: 700 }}>Quote Submissions</span>
                <span className="text-xs text-slate-400 ml-1">{vendorQuotes.length} quote{vendorQuotes.length !== 1 ? 's' : ''}</span>
              </div>
              {vendorQuotes.length > 0 ? (
                <div className="divide-y divide-slate-50">
                  {vendorQuotes.map(quote => {
                    const rfq = rfqs.find(r => r.id === quote.rfqId);
                    return (
                      <div key={quote.id} className="flex items-center gap-4 px-5 py-3 hover:bg-slate-50 transition-colors">
                        <div className="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0">
                          <FileText size={14} className="text-slate-600" />
                        </div>
                        <div className="flex-1 min-w-0">
                          <div className="flex items-center gap-2 mb-0.5">
                            <span className="text-sm text-slate-800" style={{ fontWeight: 500 }}>{quote.fileName}</span>
                            <span className={`text-xs px-2 py-0.5 rounded ${statusColors[quote.status] || ''}`} style={{ fontWeight: 600 }}>
                              {quote.status.replace('_', ' ')}
                            </span>
                          </div>
                          <div className="flex items-center gap-3 text-xs text-slate-500">
                            <span>RFQ: {rfq?.id || '—'}</span>
                            <span className="text-slate-300">|</span>
                            <span>{formatDateTime(quote.uploadedAt)}</span>
                            <span className="text-slate-300">|</span>
                            <span>{quote.lineCount} lines</span>
                          </div>
                        </div>
                        <div className="text-right flex-shrink-0">
                          {quote.totalAmount > 0 && (
                            <div className="text-sm text-slate-800" style={{ fontWeight: 600 }}>${quote.totalAmount.toLocaleString()}</div>
                          )}
                          {quote.confidence > 0 && (
                            <div className="text-xs text-slate-500">Confidence: {quote.confidence}%</div>
                          )}
                        </div>
                      </div>
                    );
                  })}
                </div>
              ) : (
                <div className="p-8 text-center text-slate-400">
                  <Award size={28} className="mx-auto mb-2 opacity-40" />
                  <div className="text-sm" style={{ fontWeight: 500 }}>No quote submissions found</div>
                </div>
              )}
            </div>

            {/* Savings Contribution */}
            {vendor.savingsContribution > 0 && (
              <div className="bg-white rounded-xl border border-emerald-200 shadow-sm p-5">
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                    <TrendingUp size={18} className="text-emerald-600" />
                  </div>
                  <div>
                    <div className="text-xs text-slate-500">Savings Contribution</div>
                    <div className="text-xl text-emerald-700" style={{ fontWeight: 800 }}>{vendor.savingsContribution}%</div>
                  </div>
                  <div className="ml-auto text-xs text-slate-400">Percentage of total procurement savings attributed to this vendor</div>
                </div>
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
}
