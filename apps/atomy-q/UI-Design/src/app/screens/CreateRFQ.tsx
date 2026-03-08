import { useState } from 'react';
import { Plus, Trash2, Upload, X, FileText, ChevronRight, Check, AlertTriangle, Paperclip, Calendar, Clock } from 'lucide-react';

const steps = [
  { id: 1, label: 'Basic Info', desc: 'RFQ details & metadata' },
  { id: 2, label: 'Line Items', desc: 'Products & services' },
  { id: 3, label: 'Terms & Deadlines', desc: 'Submission criteria' },
  { id: 4, label: 'Attachments', desc: 'Docs & specs' },
  { id: 5, label: 'Review & Publish', desc: 'Final check' },
];

interface LineItem {
  id: string;
  description: string;
  qty: string;
  uom: string;
  budget: string;
  required: boolean;
}

const defaultItems: LineItem[] = [
  { id: 'li1', description: 'Dell PowerEdge R750 Server', qty: '10', uom: 'EA', budget: '90000', required: true },
  { id: 'li2', description: 'HPE ProLiant DL380 Gen10', qty: '5', uom: 'EA', budget: '50000', required: true },
];

const templates = [
  { id: 't1', name: 'Server Hardware RFQ', category: 'IT Infrastructure', items: 12, lastUsed: '2 weeks ago' },
  { id: 't2', name: 'Software Licensing RFQ', category: 'IT Software', items: 6, lastUsed: '1 month ago' },
  { id: 't3', name: 'Office Supplies RFQ', category: 'Facilities', items: 24, lastUsed: '3 weeks ago' },
  { id: 't4', name: 'Professional Services RFQ', category: 'Services', items: 8, lastUsed: 'Never' },
];

export function CreateRFQ() {
  const [step, setStep] = useState(1);
  const [lineItems, setLineItems] = useState<LineItem[]>(defaultItems);
  const [showTemplate, setShowTemplate] = useState(false);
  const [showDiscard, setShowDiscard] = useState(false);
  const [attachments, setAttachments] = useState<string[]>(['Technical_Specs_v2.pdf', 'Vendor_Requirements.docx']);

  const addItem = () => {
    setLineItems(prev => [...prev, { id: `li${Date.now()}`, description: '', qty: '1', uom: 'EA', budget: '', required: false }]);
  };

  const removeItem = (id: string) => setLineItems(prev => prev.filter(i => i.id !== id));

  const updateItem = (id: string, field: keyof LineItem, value: string | boolean) => {
    setLineItems(prev => prev.map(i => i.id === id ? { ...i, [field]: value } : i));
  };

  return (
    <div className="flex flex-col h-full bg-slate-50">
      {/* Header */}
      <div className="bg-white border-b border-slate-200 px-6 py-4 flex-shrink-0">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-slate-900">Create RFQ</h1>
            <p className="text-slate-500 text-xs mt-0.5">Draft · RFQ-2402 will be assigned on publish</p>
          </div>
          <div className="flex items-center gap-2">
            <button onClick={() => setShowTemplate(true)} className="flex items-center gap-2 border border-slate-200 bg-white rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
              <FileText size={14} />
              Use Template
            </button>
            <button onClick={() => setShowDiscard(true)} className="flex items-center gap-2 border border-red-200 bg-red-50 rounded-lg px-3 py-2 text-sm text-red-600 hover:bg-red-100">
              <X size={14} />
              Discard
            </button>
          </div>
        </div>

        {/* Stepper */}
        <div className="flex items-center gap-0 mt-5">
          {steps.map((s, i) => {
            const isDone = s.id < step;
            const isActive = s.id === step;
            return (
              <div key={s.id} className="flex items-center flex-1 last:flex-none">
                <button onClick={() => s.id <= step && setStep(s.id)} className={`flex items-center gap-2.5 px-0 py-0 cursor-pointer group ${s.id > step ? 'opacity-40 cursor-not-allowed' : ''}`}>
                  <div className={`w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 border-2 transition-all ${isDone ? 'bg-indigo-600 border-indigo-600' : isActive ? 'border-indigo-600 bg-white' : 'border-slate-300 bg-white'}`}>
                    {isDone ? <Check size={13} className="text-white" /> : <span className={`text-xs ${isActive ? 'text-indigo-600' : 'text-slate-400'}`} style={{ fontWeight: 700 }}>{s.id}</span>}
                  </div>
                  <div className="text-left hidden sm:block">
                    <div className={`text-xs ${isActive ? 'text-indigo-600' : isDone ? 'text-slate-700' : 'text-slate-400'}`} style={{ fontWeight: isActive ? 600 : 500 }}>{s.label}</div>
                    <div className="text-slate-400 text-xs">{s.desc}</div>
                  </div>
                </button>
                {i < steps.length - 1 && (
                  <div className={`flex-1 h-px mx-3 ${isDone ? 'bg-indigo-600' : 'bg-slate-200'}`} />
                )}
              </div>
            );
          })}
        </div>
      </div>

      {/* Content */}
      <div className="flex-1 overflow-auto pb-24">
        <div className="max-w-4xl mx-auto px-6 py-6">
          {/* Step 1: Basic Info */}
          {step === 1 && (
            <div className="space-y-5">
              <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <h3 className="text-slate-900 mb-5">RFQ Details</h3>
                <div className="grid grid-cols-2 gap-5">
                  <div className="col-span-2">
                    <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>RFQ Title *</label>
                    <input defaultValue="Server Infrastructure Components — FY2026 Q2" className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 outline-none" />
                  </div>
                  <div>
                    <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Category</label>
                    <select className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white">
                      <option>IT Infrastructure</option>
                      <option>IT Software</option>
                      <option>Facilities</option>
                    </select>
                  </div>
                  <div>
                    <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Business Unit</label>
                    <select className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white">
                      <option>Technology Division</option>
                      <option>Operations</option>
                      <option>Finance</option>
                    </select>
                  </div>
                  <div>
                    <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Total Budget</label>
                    <div className="relative">
                      <span className="absolute left-3 top-2.5 text-slate-400 text-sm">$</span>
                      <input defaultValue="250,000" className="w-full border border-slate-200 rounded-lg pl-7 pr-3 py-2.5 text-sm text-slate-800 focus:border-indigo-400 outline-none" />
                    </div>
                  </div>
                  <div>
                    <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Priority</label>
                    <select className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white">
                      <option>High</option>
                      <option>Medium</option>
                      <option>Low</option>
                    </select>
                  </div>
                  <div className="col-span-2">
                    <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>Description</label>
                    <textarea rows={4} defaultValue="Procurement of server hardware and network equipment for the data center refresh program. All submitted quotes must include warranty terms and support options." className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 resize-none focus:border-indigo-400 outline-none" />
                  </div>
                </div>
              </div>

              <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <h3 className="text-slate-900 mb-5">Invited Vendors</h3>
                <div className="space-y-2 mb-3">
                  {['TechCorp Solutions', 'GlobalSupply Inc.', 'FastParts Ltd.', 'PrimeSource Co.'].map((v) => (
                    <div key={v} className="flex items-center justify-between px-3 py-2 bg-slate-50 rounded-lg border border-slate-200">
                      <span className="text-slate-700 text-sm">{v}</span>
                      <button className="text-slate-400 hover:text-red-400"><X size={14} /></button>
                    </div>
                  ))}
                </div>
                <button className="flex items-center gap-2 text-indigo-600 text-sm hover:text-indigo-700" style={{ fontWeight: 500 }}>
                  <Plus size={14} />
                  Invite vendor
                </button>
              </div>
            </div>
          )}

          {/* Step 2: Line Items */}
          {step === 2 && (
            <div className="bg-white rounded-xl border border-slate-200 shadow-sm">
              <div className="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <h3 className="text-slate-900">Line Items</h3>
                <button onClick={addItem} className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-3 py-2 text-xs" style={{ fontWeight: 500 }}>
                  <Plus size={13} />
                  Add Item
                </button>
              </div>

              {/* Table Header */}
              <div className="grid px-5 py-2.5 bg-slate-50 border-b border-slate-100 text-xs text-slate-500" style={{ gridTemplateColumns: '3fr 80px 100px 120px 40px', fontWeight: 600 }}>
                <span>DESCRIPTION</span>
                <span>QTY</span>
                <span>UOM</span>
                <span>BUDGET/UNIT</span>
                <span />
              </div>

              {lineItems.map((item, idx) => (
                <div key={item.id} className="grid px-5 py-3 border-b border-slate-50 hover:bg-slate-50/50 items-center gap-2" style={{ gridTemplateColumns: '3fr 80px 100px 120px 40px' }}>
                  <input
                    value={item.description}
                    onChange={e => updateItem(item.id, 'description', e.target.value)}
                    placeholder="Item description..."
                    className="border border-slate-200 rounded-lg px-3 py-2 text-xs text-slate-800 focus:border-indigo-400 outline-none"
                  />
                  <input
                    value={item.qty}
                    onChange={e => updateItem(item.id, 'qty', e.target.value)}
                    className="border border-slate-200 rounded-lg px-2 py-2 text-xs text-slate-800 text-center focus:border-indigo-400 outline-none"
                  />
                  <select className="border border-slate-200 rounded-lg px-2 py-2 text-xs text-slate-800 bg-white">
                    <option>EA</option><option>Box</option><option>License</option><option>Service</option>
                  </select>
                  <div className="relative">
                    <span className="absolute left-2.5 top-2 text-slate-400 text-xs">$</span>
                    <input
                      value={item.budget}
                      onChange={e => updateItem(item.id, 'budget', e.target.value)}
                      className="w-full border border-slate-200 rounded-lg pl-6 pr-2 py-2 text-xs text-slate-800 focus:border-indigo-400 outline-none"
                    />
                  </div>
                  <button onClick={() => removeItem(item.id)} className="flex justify-center text-slate-300 hover:text-red-400">
                    <Trash2 size={14} />
                  </button>
                </div>
              ))}

              <div className="px-5 py-3">
                <button onClick={addItem} className="flex items-center gap-2 text-indigo-600 text-sm hover:text-indigo-700" style={{ fontWeight: 500 }}>
                  <Plus size={14} />
                  Add line item
                </button>
              </div>
            </div>
          )}

          {/* Step 3: Terms & Deadlines */}
          {step === 3 && (
            <div className="space-y-5">
              <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <h3 className="text-slate-900 mb-5">Deadlines</h3>
                <div className="grid grid-cols-2 gap-5">
                  {[
                    { label: 'Quote Submission Deadline', value: '2026-03-28', icon: Calendar },
                    { label: 'Quote Validity Period', value: '60 days', icon: Clock },
                    { label: 'Evaluation Start Date', value: '2026-03-29', icon: Calendar },
                    { label: 'Award Target Date', value: '2026-04-10', icon: Calendar },
                  ].map((f) => {
                    const Icon = f.icon;
                    return (
                      <div key={f.label}>
                        <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>{f.label}</label>
                        <div className="relative">
                          <Icon size={14} className="absolute left-3 top-2.5 text-slate-400" />
                          <input defaultValue={f.value} className="w-full border border-slate-200 rounded-lg pl-8 pr-3 py-2.5 text-sm text-slate-800 focus:border-indigo-400 outline-none" />
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>

              <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <h3 className="text-slate-900 mb-5">Commercial Terms</h3>
                <div className="grid grid-cols-2 gap-5">
                  {[
                    { label: 'Required Payment Terms', options: ['Net 30', 'Net 45', 'Net 60'] },
                    { label: 'Max Delivery Lead Time', options: ['2 weeks', '3 weeks', '4 weeks'] },
                    { label: 'Minimum Warranty', options: ['1 year', '2 years', '3 years'] },
                    { label: 'Currency', options: ['USD', 'EUR', 'GBP', 'AUD'] },
                  ].map(f => (
                    <div key={f.label}>
                      <label className="text-slate-600 text-xs block mb-1.5" style={{ fontWeight: 500 }}>{f.label}</label>
                      <select className="w-full border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-800 bg-white">
                        {f.options.map(o => <option key={o}>{o}</option>)}
                      </select>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}

          {/* Step 4: Attachments */}
          {step === 4 && (
            <div className="space-y-5">
              <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
                <h3 className="text-slate-900 mb-4">Upload Documents</h3>
                <div className="border-2 border-dashed border-slate-300 rounded-xl p-10 text-center hover:border-indigo-400 hover:bg-indigo-50/20 transition-colors cursor-pointer">
                  <Upload size={28} className="mx-auto text-slate-400 mb-3" />
                  <p className="text-slate-600 text-sm" style={{ fontWeight: 500 }}>Drop files here or click to upload</p>
                  <p className="text-slate-400 text-xs mt-1">PDF, DOCX, XLSX · Max 25MB per file</p>
                </div>
              </div>

              <div className="bg-white rounded-xl border border-slate-200 shadow-sm">
                <div className="px-5 py-3 border-b border-slate-100">
                  <h3 className="text-slate-900">Attached Files</h3>
                </div>
                <div className="divide-y divide-slate-100">
                  {attachments.map((file) => (
                    <div key={file} className="flex items-center gap-3 px-5 py-3">
                      <Paperclip size={14} className="text-indigo-500" />
                      <span className="flex-1 text-slate-700 text-sm">{file}</span>
                      <span className="text-slate-400 text-xs">Uploaded</span>
                      <button onClick={() => setAttachments(p => p.filter(f => f !== file))} className="text-slate-300 hover:text-red-400">
                        <X size={14} />
                      </button>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}

          {/* Step 5: Review */}
          {step === 5 && (
            <div className="space-y-4">
              <div className="bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex items-start gap-3">
                <Check size={16} className="text-emerald-600 mt-0.5" />
                <div>
                  <p className="text-emerald-700 text-sm" style={{ fontWeight: 600 }}>Ready to Publish</p>
                  <p className="text-emerald-600 text-xs mt-0.5">All required fields completed. Review details below before publishing.</p>
                </div>
              </div>

              {[
                { title: 'Basic Info', items: [['Title', 'Server Infrastructure Components — FY2026 Q2'], ['Category', 'IT Infrastructure'], ['Budget', '$250,000'], ['Priority', 'High']] },
                { title: 'Line Items', items: [['Items', `${lineItems.length} items`], ['Total Budget', `$${lineItems.reduce((s, i) => s + (parseFloat(i.budget || '0')), 0).toLocaleString()}`]] },
                { title: 'Terms', items: [['Submission Deadline', 'March 28, 2026'], ['Payment Terms', 'Net 30'], ['Warranty', '3 years minimum']] },
                { title: 'Attachments', items: [['Files', `${attachments.length} files attached`]] },
              ].map(section => (
                <div key={section.title} className="bg-white rounded-xl border border-slate-200 shadow-sm">
                  <div className="flex items-center justify-between px-5 py-3 border-b border-slate-100">
                    <h3 className="text-slate-900">{section.title}</h3>
                    <button className="text-indigo-600 text-xs hover:underline" style={{ fontWeight: 500 }}>Edit</button>
                  </div>
                  <div className="px-5 py-3 grid grid-cols-2 gap-3">
                    {section.items.map(([k, v]) => (
                      <div key={k}>
                        <span className="text-slate-500 text-xs block">{k}</span>
                        <span className="text-slate-800 text-sm" style={{ fontWeight: 500 }}>{v}</span>
                      </div>
                    ))}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      {/* Sticky Action Bar */}
      <div className="fixed bottom-0 left-64 right-0 bg-white border-t border-slate-200 px-6 py-4 flex items-center justify-between z-20">
        <div className="text-slate-500 text-xs">
          Step {step} of {steps.length} · <span style={{ fontWeight: 500 }} className="text-slate-700">{steps[step - 1].label}</span>
        </div>
        <div className="flex items-center gap-3">
          <button className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">
            Save Draft
          </button>
          {step > 1 && (
            <button onClick={() => setStep(s => s - 1)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">
              Previous
            </button>
          )}
          {step < 5 ? (
            <button onClick={() => setStep(s => s + 1)} className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-5 py-2 text-sm" style={{ fontWeight: 500 }}>
              Continue
              <ChevronRight size={15} />
            </button>
          ) : (
            <button className="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-5 py-2 text-sm" style={{ fontWeight: 500 }}>
              <Check size={15} />
              Publish RFQ
            </button>
          )}
        </div>
      </div>

      {/* Template Modal */}
      {showTemplate && (
        <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
            <div className="flex items-center justify-between px-6 py-4 border-b border-slate-100">
              <h2 className="text-slate-900 text-sm">Choose a Template</h2>
              <button onClick={() => setShowTemplate(false)} className="text-slate-400 hover:text-slate-600"><X size={18} /></button>
            </div>
            <div className="p-4 space-y-2">
              {templates.map(t => (
                <button key={t.id} onClick={() => setShowTemplate(false)} className="w-full flex items-center gap-4 px-4 py-3 rounded-xl border border-slate-200 hover:border-indigo-300 hover:bg-indigo-50/30 text-left transition-colors">
                  <FileText size={20} className="text-indigo-500 flex-shrink-0" />
                  <div className="flex-1">
                    <div className="text-slate-800 text-sm" style={{ fontWeight: 500 }}>{t.name}</div>
                    <div className="text-slate-500 text-xs mt-0.5">{t.category} · {t.items} items · Last used {t.lastUsed}</div>
                  </div>
                  <ChevronRight size={14} className="text-slate-400" />
                </button>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* Discard Modal */}
      {showDiscard && (
        <div className="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-sm">
            <div className="p-6 text-center">
              <div className="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
                <AlertTriangle size={22} className="text-red-600" />
              </div>
              <h2 className="text-slate-900 mb-2">Discard RFQ Draft?</h2>
              <p className="text-slate-500 text-sm">All changes to this draft will be permanently lost. This cannot be undone.</p>
            </div>
            <div className="flex gap-3 px-6 pb-6">
              <button onClick={() => setShowDiscard(false)} className="flex-1 border border-slate-200 rounded-lg px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50">Keep Editing</button>
              <button className="flex-1 bg-red-600 hover:bg-red-700 text-white rounded-lg px-4 py-2.5 text-sm" style={{ fontWeight: 500 }}>Discard</button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
