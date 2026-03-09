import { useState } from 'react';
import {
  Download, Filter, SlidersHorizontal, ChevronDown, ChevronRight,
  CheckCircle, AlertTriangle, XCircle, Info, Star, ArrowUpDown
} from 'lucide-react';
import React from 'react';

const vendors = [
  { id: 'v1', name: 'TechCorp Solutions', score: 88, status: 'Shortlisted' },
  { id: 'v2', name: 'GlobalSupply Inc.', score: 76, status: 'Under Review' },
  { id: 'v3', name: 'FastParts Ltd.', score: 82, status: 'Shortlisted' },
  { id: 'v4', name: 'PrimeSource Co.', score: 91, status: 'Preferred' },
];

const categories = [
  {
    id: 'cat1', label: 'Server Hardware', collapsed: false,
    items: [
      { id: 'li1', desc: 'Dell PowerEdge R750 Server', qty: 10, uom: 'EA', prices: { v1: 8200, v2: 7950, v3: 8400, v4: 7800 } },
      { id: 'li2', desc: 'HPE ProLiant DL380 Gen10', qty: 5, uom: 'EA', prices: { v1: 9100, v2: null, v3: 8750, v4: 9200 } },
      { id: 'li3', desc: '32GB DDR4 ECC RDIMM', qty: 80, uom: 'EA', prices: { v1: 420, v2: 395, v3: 410, v4: 380 } },
    ],
  },
  {
    id: 'cat2', label: 'Network Equipment', collapsed: false,
    items: [
      { id: 'li4', desc: 'Cisco Catalyst 9300 48-Port', qty: 8, uom: 'EA', prices: { v1: 4800, v2: 5100, v3: null, v4: 4650 } },
      { id: 'li5', desc: 'Fortinet FortiGate 100F Firewall', qty: 2, uom: 'EA', prices: { v1: 6200, v2: 5900, v3: 6400, v4: 5950 } },
      { id: 'li6', desc: 'Cat6A Patch Cable 1m', qty: 200, uom: 'EA', prices: { v1: 12, v2: 11, v3: 10, v4: 13 } },
    ],
  },
  {
    id: 'cat3', label: 'Software Licenses', collapsed: false,
    items: [
      { id: 'li7', desc: 'VMware vSphere Enterprise Plus', qty: 10, uom: 'License', prices: { v1: 3400, v2: 3200, v3: null, v4: null } },
      { id: 'li8', desc: 'Microsoft SQL Server Standard', qty: 5, uom: 'License', prices: { v1: 1800, v2: 1750, v3: 1900, v4: 1720 } },
    ],
  },
];

const terms = [
  { label: 'Payment Terms', values: { v1: 'Net 30', v2: 'Net 45', v3: 'Net 30', v4: 'Net 60' } },
  { label: 'Delivery Lead Time', values: { v1: '2 weeks', v2: '3 weeks', v3: '10 days', v4: '2 weeks' } },
  { label: 'Warranty Period', values: { v1: '3 years', v2: '2 years', v3: '3 years', v4: '5 years' } },
  { label: 'Freight Included', values: { v1: 'Yes', v2: 'No', v3: 'Yes', v4: 'Yes' } },
  { label: 'Accepts Returns', values: { v1: '30 days', v2: '14 days', v3: '30 days', v4: '60 days' } },
];

function getBest(prices: Record<string, number | null>) {
  const valid = Object.entries(prices).filter(([, v]) => v !== null) as [string, number][];
  if (!valid.length) return null;
  return valid.reduce((a, b) => (a[1] < b[1] ? a : b))[0];
}

function getDelta(price: number | null, best: number | null) {
  if (price === null || best === null) return null;
  return ((price - best) / best) * 100;
}

export function QuoteComparisonMatrix() {
  const [collapsed, setCollapsed] = useState<Record<string, boolean>>({});
  const [normalized, setNormalized] = useState(false);
  const [highlightBest, setHighlightBest] = useState(true);

  const toggleCat = (id: string) => setCollapsed(p => ({ ...p, [id]: !p[id] }));

  // Compute totals per vendor
  const totals: Record<string, number | null> = { v1: 0, v2: 0, v3: 0, v4: 0 };
  categories.forEach(cat =>
    cat.items.forEach(item =>
      vendors.forEach(v => {
        const p = item.prices[v.id as keyof typeof item.prices];
        if (p === null) totals[v.id] = null;
        else if (totals[v.id] !== null) (totals[v.id] as number) += p * item.qty;
      })
    )
  );

  const bestTotal = Math.min(...Object.values(totals).filter(v => v !== null) as number[]);

  return (
    <div className="flex flex-col h-full bg-slate-50">
      {/* Page Header */}
      <div className="bg-white border-b border-slate-200 px-6 py-4 flex-shrink-0">
        <div className="flex items-start justify-between">
          <div>
            <div className="flex items-center gap-2 text-xs text-slate-500 mb-1">
              <span>RFQ-2401</span>
              <span>·</span>
              <span>Server Infrastructure Components</span>
            </div>
            <h1 className="text-slate-900">Quote Comparison Matrix</h1>
            <p className="text-slate-500 text-xs mt-0.5">4 vendors · 8 line items · 3 categories · Last updated 2h ago</p>
          </div>
          <div className="flex items-center gap-2">
            <div className="flex items-center gap-2 border border-slate-200 rounded-lg px-3 py-2 bg-white">
              <span className="text-xs text-slate-600" style={{ fontWeight: 500 }}>Normalize Prices</span>
              <button
                onClick={() => setNormalized(!normalized)}
                className={`relative w-9 h-5 rounded-full transition-colors ${normalized ? 'bg-indigo-600' : 'bg-slate-200'}`}
              >
                <span className={`absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transition-all ${normalized ? 'left-4.5' : 'left-0.5'}`} />
              </button>
            </div>
            <button className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
              <Filter size={14} />
              Filter
            </button>
            <button className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
              <Download size={14} />
              Export
            </button>
            <button className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-3 py-2 text-sm" style={{ fontWeight: 500 }}>
              <Star size={14} />
              Score & Award
            </button>
          </div>
        </div>

        {/* Vendor Summary Cards */}
        <div className="grid grid-cols-4 gap-3 mt-4">
          {vendors.map((v) => {
            const total = totals[v.id];
            const isLowest = total === bestTotal;
            const delta = total !== null ? ((total - bestTotal) / bestTotal * 100) : null;
            return (
              <div key={v.id} className={`rounded-lg border px-3 py-2.5 ${isLowest ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200 bg-white'}`}>
                <div className="flex items-center justify-between mb-1">
                  <span className="text-xs text-slate-700" style={{ fontWeight: 600 }}>{v.name}</span>
                  {isLowest && <span className="text-xs bg-emerald-600 text-white px-1.5 py-0.5 rounded" style={{ fontWeight: 600 }}>BEST</span>}
                  {v.status === 'Preferred' && !isLowest && <span className="text-xs bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded" style={{ fontWeight: 500 }}>★ Preferred</span>}
                </div>
                <div className={`text-lg ${isLowest ? 'text-emerald-700' : 'text-slate-900'}`} style={{ fontWeight: 700 }}>
                  {total !== null ? `$${total.toLocaleString()}` : <span className="text-slate-400 text-sm">Incomplete</span>}
                </div>
                <div className="flex items-center gap-2 mt-0.5">
                  <span className="text-xs text-slate-500">Score: {v.score}/100</span>
                  {delta !== null && delta > 0 && (
                    <span className="text-xs text-amber-600">+{delta.toFixed(1)}%</span>
                  )}
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* Matrix */}
      <div className="flex-1 overflow-auto">
        <table className="w-full border-collapse" style={{ minWidth: '900px' }}>
          {/* Sticky Header */}
          <thead className="sticky top-0 z-20 bg-white border-b-2 border-slate-200">
            <tr>
              <th className="sticky left-0 z-30 bg-white border-r border-slate-200 text-left px-4 py-3 w-72">
                <div className="flex items-center gap-1 text-slate-500 text-xs" style={{ fontWeight: 600 }}>
                  <span>LINE ITEM</span>
                  <ArrowUpDown size={11} className="cursor-pointer" />
                </div>
              </th>
              <th className="bg-slate-50 border-r border-slate-200 px-3 py-3 text-center text-slate-500 text-xs" style={{ fontWeight: 600, width: 60 }}>QTY</th>
              {vendors.map((v) => {
                const total = totals[v.id];
                const isLowest = total === bestTotal;
                return (
                  <th key={v.id} className={`border-r border-slate-200 px-4 py-3 text-center text-xs ${isLowest ? 'bg-emerald-50' : 'bg-white'}`} style={{ minWidth: 160 }}>
                    <div className={`${isLowest ? 'text-emerald-700' : 'text-slate-700'}`} style={{ fontWeight: 600 }}>{v.name}</div>
                    <div className="text-slate-400 text-xs mt-0.5" style={{ fontWeight: 400 }}>Unit Price</div>
                  </th>
                );
              })}
            </tr>
          </thead>
          <tbody>
            {categories.map((cat) => {
              const isColl = collapsed[cat.id];
              return (
                <React.Fragment key={cat.id}>
                  {/* Category Row */}
                  <tr
                    key={cat.id}
                    className="bg-slate-100 border-y border-slate-200 cursor-pointer hover:bg-slate-200/60"
                    onClick={() => toggleCat(cat.id)}
                  >
                    <td className="sticky left-0 z-10 bg-slate-100 border-r border-slate-200 px-4 py-2" colSpan={1}>
                      <div className="flex items-center gap-2">
                        {isColl ? <ChevronRight size={14} className="text-slate-500" /> : <ChevronDown size={14} className="text-slate-500" />}
                        <span className="text-slate-700 text-xs" style={{ fontWeight: 700 }}>{cat.label}</span>
                        <span className="text-slate-400 text-xs">({cat.items.length} items)</span>
                      </div>
                    </td>
                    <td className="bg-slate-100 border-r border-slate-200 px-3 py-2" />
                    {vendors.map((v) => {
                      const catTotal = cat.items.reduce((s, item) => {
                        const p = item.prices[v.id as keyof typeof item.prices];
                        return p !== null ? s + p * item.qty : s;
                      }, 0);
                      return (
                        <td key={v.id} className="bg-slate-100 border-r border-slate-200 px-4 py-2 text-center">
                          <span className="text-slate-600 text-xs" style={{ fontWeight: 500 }}>${catTotal.toLocaleString()}</span>
                        </td>
                      );
                    })}
                  </tr>

                  {/* Line Item Rows */}
                  {!isColl && cat.items.map((item) => {
                    const bestVendorId = getBest(item.prices);
                    const bestPrice = bestVendorId ? item.prices[bestVendorId as keyof typeof item.prices] : null;
                    return (
                      <tr key={item.id} className="border-b border-slate-100 hover:bg-blue-50/30 transition-colors">
                        <td className="sticky left-0 z-10 bg-white border-r border-slate-100 px-4 py-3 hover:bg-blue-50/30">
                          <div className="text-slate-800 text-sm">{item.desc}</div>
                        </td>
                        <td className="border-r border-slate-100 px-3 py-3 text-center text-slate-600 text-xs">
                          <span style={{ fontWeight: 500 }}>{item.qty}</span>
                          <span className="text-slate-400"> {item.uom}</span>
                        </td>
                        {vendors.map((v) => {
                          const price = item.prices[v.id as keyof typeof item.prices];
                          const isBest = v.id === bestVendorId;
                          const delta = getDelta(price, bestPrice as number | null);
                          return (
                            <td key={v.id} className={`border-r border-slate-100 px-4 py-3 text-center ${isBest && highlightBest ? 'bg-emerald-50' : ''}`}>
                              {price === null ? (
                                <div className="flex flex-col items-center gap-1">
                                  <span className="text-red-400 text-xs" style={{ fontWeight: 500 }}>—</span>
                                  <span className="text-red-400 text-xs bg-red-50 px-1.5 py-0.5 rounded border border-red-200" style={{ fontWeight: 500 }}>Missing</span>
                                </div>
                              ) : (
                                <div className="flex flex-col items-center gap-0.5">
                                  <span className={`text-sm ${isBest ? 'text-emerald-700' : 'text-slate-800'}`} style={{ fontWeight: isBest ? 700 : 500 }}>
                                    ${price.toLocaleString()}
                                  </span>
                                  {isBest ? (
                                    <span className="text-emerald-600 text-xs" style={{ fontWeight: 600 }}>✓ Best</span>
                                  ) : delta !== null && delta > 0 ? (
                                    <span className="text-amber-600 text-xs" style={{ fontWeight: 500 }}>+{delta.toFixed(1)}%</span>
                                  ) : null}
                                  <span className="text-slate-400 text-xs">${(price * item.qty).toLocaleString()} total</span>
                                </div>
                              )}
                            </td>
                          );
                        })}
                      </tr>
                    );
                  })}
                </React.Fragment>
              );
            })}

            {/* Summary Row */}
            <tr className="border-t-2 border-slate-300 bg-slate-900 sticky bottom-0 z-10">
              <td className="sticky left-0 z-10 bg-slate-900 border-r border-slate-700 px-4 py-3">
                <span className="text-white text-sm" style={{ fontWeight: 700 }}>GRAND TOTAL</span>
              </td>
              <td className="border-r border-slate-700 px-3 py-3" />
              {vendors.map((v) => {
                const total = totals[v.id];
                const isLowest = total === bestTotal;
                const delta = total !== null ? ((total - bestTotal) / bestTotal * 100) : null;
                return (
                  <td key={v.id} className={`border-r border-slate-700 px-4 py-3 text-center`}>
                    {total !== null ? (
                      <div>
                        <div className={`text-sm ${isLowest ? 'text-emerald-400' : 'text-white'}`} style={{ fontWeight: 700 }}>
                          ${total.toLocaleString()}
                        </div>
                        {isLowest ? (
                          <div className="text-emerald-400 text-xs mt-0.5" style={{ fontWeight: 600 }}>LOWEST</div>
                        ) : (
                          <div className="text-amber-400 text-xs mt-0.5" style={{ fontWeight: 500 }}>+{delta?.toFixed(1)}%</div>
                        )}
                      </div>
                    ) : (
                      <span className="text-slate-500 text-xs">Incomplete</span>
                    )}
                  </td>
                );
              })}
            </tr>
          </tbody>
        </table>

        {/* Terms Comparison */}
        <div className="bg-white border-t-2 border-slate-200 mt-0">
          <div className="px-4 py-3 border-b border-slate-100 bg-slate-50">
            <span className="text-slate-700 text-xs" style={{ fontWeight: 700 }}>COMMERCIAL TERMS COMPARISON</span>
          </div>
          <table className="w-full" style={{ minWidth: '900px' }}>
            <tbody>
              {terms.map((term, i) => (
                <tr key={term.label} className={`border-b border-slate-100 ${i % 2 === 0 ? 'bg-white' : 'bg-slate-50/50'}`}>
                  <td className="sticky left-0 bg-inherit border-r border-slate-100 px-4 py-2.5 w-72">
                    <span className="text-slate-600 text-xs" style={{ fontWeight: 500 }}>{term.label}</span>
                  </td>
                  <td className="border-r border-slate-100 px-3 py-2.5 w-16" />
                  {vendors.map((v) => (
                    <td key={v.id} className="border-r border-slate-100 px-4 py-2.5 text-center">
                      <span className="text-slate-700 text-xs">{term.values[v.id as keyof typeof term.values]}</span>
                    </td>
                  ))}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}