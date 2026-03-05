import { useState } from "react";
import { useNavigate } from "react-router";
import {
  Check, ChevronRight, Plus, Trash2, Upload, AlertCircle,
  FileText, LayoutTemplate, X, Calendar, DollarSign, Tag, Users
} from "lucide-react";

const steps = [
  { id: 1, label: "Basic Information", desc: "RFQ metadata & context" },
  { id: 2, label: "Line Items", desc: "Products and services" },
  { id: 3, label: "Terms & Deadlines", desc: "Commercial conditions" },
  { id: 4, label: "Attachments & Review", desc: "Documents and submit" },
];

const categories = ["Equipment", "IT Services", "IT Hardware", "Software", "Manufacturing", "Logistics", "Office", "Services", "Security", "Marketing", "Other"];
const paymentTerms = ["Net 15", "Net 30", "Net 45", "Net 60", "Net 90", "Milestone-Based", "Upon Delivery"];
const currencies = ["USD", "EUR", "GBP", "JPY", "CAD", "AUD", "SGD"];
const uoms = ["Units", "Hours", "Days", "Months", "Kg", "Liters", "Meters", "Sets", "Licenses"];

interface LineItem { id: string; description: string; qty: string; uom: string; targetPrice: string; notes: string; }

export function CreateRFQ() {
  const navigate = useNavigate();
  const [step, setStep] = useState(1);
  const [showTemplate, setShowTemplate] = useState(false);
  const [saved, setSaved] = useState(false);

  const [form, setForm] = useState({
    title: "",
    category: "",
    description: "",
    budget: "",
    currency: "USD",
    department: "",
    costCenter: "",
    priority: "Medium",
    submissionDeadline: "",
    awardDeadline: "",
    deliveryDate: "",
    paymentTerms: "Net 30",
    warrantyMonths: "12",
    insuranceRequired: true,
    deliveryLocation: "",
    notes: "",
  });

  const [lineItems, setLineItems] = useState<LineItem[]>([
    { id: "1", description: "", qty: "1", uom: "Units", targetPrice: "", notes: "" },
  ]);

  const [attachments, setAttachments] = useState<string[]>(["Scope_of_Work_v2.pdf", "Technical_Specs.xlsx"]);
  const [errors, setErrors] = useState<Record<string, string>>({});

  const update = (k: string, v: string | boolean) => setForm((f) => ({ ...f, [k]: v }));

  const addLineItem = () => setLineItems((prev) => [...prev, { id: String(Date.now()), description: "", qty: "1", uom: "Units", targetPrice: "", notes: "" }]);
  const removeLineItem = (id: string) => setLineItems((prev) => prev.filter((i) => i.id !== id));
  const updateLineItem = (id: string, k: keyof LineItem, v: string) => setLineItems((prev) => prev.map((i) => i.id === id ? { ...i, [k]: v } : i));

  const validateStep = () => {
    const newErrors: Record<string, string> = {};
    if (step === 1) {
      if (!form.title.trim()) newErrors.title = "Title is required";
      if (!form.category) newErrors.category = "Category is required";
      if (!form.budget) newErrors.budget = "Budget is required";
    }
    if (step === 2 && lineItems.some((l) => !l.description.trim())) {
      newErrors.lineItems = "All line items must have a description";
    }
    if (step === 3) {
      if (!form.submissionDeadline) newErrors.submissionDeadline = "Submission deadline is required";
    }
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleNext = () => {
    if (validateStep()) setStep((s) => Math.min(4, s + 1));
  };

  const handleSubmit = async () => {
    setSaved(true);
    await new Promise((r) => setTimeout(r, 1200));
    navigate("/rfqs");
  };

  const InputField = ({ label, id, placeholder, required, error, children }: any) => (
    <div>
      <label style={{ fontSize: 12, fontWeight: 500, color: "var(--app-text-muted)", letterSpacing: "0.02em", display: "block", marginBottom: 5 }}>
        {label.toUpperCase()}{required && <span style={{ color: "var(--app-danger)", marginLeft: 3 }}>*</span>}
      </label>
      {children ?? (
        <input
          id={id}
          placeholder={placeholder}
          style={{ width: "100%", height: 38, background: "var(--app-bg-surface)", border: `1px solid ${error ? "var(--app-danger)" : "var(--app-border-strong)"}`, borderRadius: 8, padding: "0 12px", fontSize: 13, color: "var(--app-text-main)", outline: "none", boxSizing: "border-box" }}
          onFocus={(e) => { if (!error) e.currentTarget.style.borderColor = "var(--app-brand-500)"; }}
          onBlur={(e) => { if (!error) e.currentTarget.style.borderColor = "var(--app-border-strong)"; }}
        />
      )}
      {error && <p style={{ fontSize: 11, color: "var(--app-danger)", marginTop: 4 }}>{error}</p>}
    </div>
  );

  return (
    <div style={{ padding: "24px", minHeight: "100%", fontFamily: "'Inter', system-ui, sans-serif" }}>
      <div className="flex items-start gap-8 max-w-5xl">
        {/* Stepper sidebar */}
        <div className="flex-shrink-0" style={{ width: 220 }}>
          <div className="flex items-center gap-2 mb-6">
            <button
              onClick={() => navigate("/rfqs")}
              style={{ fontSize: 13, color: "var(--app-text-subtle)" }}
              className="hover:text-slate-300 transition-colors flex items-center gap-1"
            >
              ← RFQs
            </button>
          </div>
          <h1 style={{ fontSize: 18, fontWeight: 700, color: "var(--app-text-strong)", letterSpacing: "-0.01em", marginBottom: 4 }}>Create RFQ</h1>
          <p style={{ fontSize: 12, color: "var(--app-text-subtle)", marginBottom: 24 }}>Step {step} of {steps.length}</p>

          <div className="space-y-1">
            {steps.map((s) => (
              <div
                key={s.id}
                className="flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-colors"
                style={{ background: step === s.id ? "var(--app-brand-tint-10)" : "transparent", border: `1px solid ${step === s.id ? "var(--app-brand-tint-20)" : "transparent"}` }}
                onClick={() => s.id < step && setStep(s.id)}
              >
                <div
                  className="flex-shrink-0 flex items-center justify-center rounded-full"
                  style={{
                    width: 24, height: 24,
                    background: s.id < step ? "var(--app-success)" : s.id === step ? "var(--app-brand-600)" : "var(--app-border-strong)",
                    color: s.id <= step ? "white" : "var(--app-text-subtle)",
                    fontSize: 11, fontWeight: 700
                  }}
                >
                  {s.id < step ? <Check size={12} /> : s.id}
                </div>
                <div>
                  <div style={{ fontSize: 13, fontWeight: 500, color: step === s.id ? "var(--app-text-main)" : s.id < step ? "var(--app-text-subtle)" : "var(--app-text-subtle)" }}>{s.label}</div>
                  <div style={{ fontSize: 11, color: "var(--app-text-faint)", marginTop: 1 }}>{s.desc}</div>
                </div>
              </div>
            ))}
          </div>

          <button
            onClick={() => setShowTemplate(true)}
            className="mt-4 w-full flex items-center gap-2 rounded-lg px-3 py-2.5 transition-colors"
            style={{ background: "var(--app-bg-surface)", border: "1px solid var(--app-border-strong)", color: "var(--app-text-muted)", fontSize: 13 }}
            onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-text-faint)"; (e.currentTarget as HTMLButtonElement).style.color = "var(--app-text-subtle)"; }}
            onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-border-strong)"; (e.currentTarget as HTMLButtonElement).style.color = "var(--app-text-muted)"; }}
          >
            <LayoutTemplate size={14} /> Use Template
          </button>
        </div>

        {/* Form area */}
        <div className="flex-1">
          <div className="rounded-xl border p-6" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>

            {/* Step 1: Basic Info */}
            {step === 1 && (
              <div>
                <h2 style={{ fontSize: 16, fontWeight: 600, color: "var(--app-text-strong)", marginBottom: 20 }}>Basic Information</h2>
                <div className="grid grid-cols-2 gap-4">
                  <div className="col-span-2">
                    <InputField label="RFQ Title" required error={errors.title}>
                      <input
                        value={form.title}
                        onChange={(e) => update("title", e.target.value)}
                        placeholder="e.g., Industrial Pumping Equipment — Q1 2024"
                        style={{ width: "100%", height: 38, background: "var(--app-bg-elevated)", border: `1px solid ${errors.title ? "var(--app-danger)" : "var(--app-border-strong)"}`, borderRadius: 8, padding: "0 12px", fontSize: 13, color: "var(--app-text-main)", outline: "none", boxSizing: "border-box" }}
                        onFocus={(e) => { if (!errors.title) e.currentTarget.style.borderColor = "var(--app-brand-500)"; }}
                        onBlur={(e) => { if (!errors.title) e.currentTarget.style.borderColor = "var(--app-border-strong)"; }}
                      />
                    </InputField>
                    {errors.title && <p style={{ fontSize: 11, color: "var(--app-danger)", marginTop: 4 }}>{errors.title}</p>}
                  </div>

                  <div>
                    <InputField label="Category" required error={errors.category}>
                      <select
                        value={form.category}
                        onChange={(e) => update("category", e.target.value)}
                        style={{ width: "100%", height: 38, background: "var(--app-bg-elevated)", border: `1px solid ${errors.category ? "var(--app-danger)" : "var(--app-border-strong)"}`, borderRadius: 8, padding: "0 12px", fontSize: 13, color: form.category ? "var(--app-text-main)" : "var(--app-text-subtle)", outline: "none", boxSizing: "border-box", cursor: "pointer" }}
                      >
                        <option value="" style={{ background: "var(--app-bg-elevated)" }}>Select category…</option>
                        {categories.map((c) => <option key={c} value={c} style={{ background: "var(--app-bg-elevated)" }}>{c}</option>)}
                      </select>
                    </InputField>
                    {errors.category && <p style={{ fontSize: 11, color: "var(--app-danger)", marginTop: 4 }}>{errors.category}</p>}
                  </div>

                  <div>
                    <label style={{ fontSize: 12, fontWeight: 500, color: "var(--app-text-muted)", letterSpacing: "0.02em", display: "block", marginBottom: 5 }}>PRIORITY</label>
                    <div className="flex gap-2">
                      {["Low", "Medium", "High", "Critical"].map((p) => (
                        <button
                          key={p}
                          onClick={() => update("priority", p)}
                          className="flex-1 rounded-lg py-1.5 transition-all"
                          style={{
                            fontSize: 12, fontWeight: 500,
                            background: form.priority === p ? (p === "Critical" ? "var(--app-danger-tint-15)" : p === "High" ? "var(--app-orange-tint-15)" : p === "Medium" ? "var(--app-warning-tint-12)" : "var(--app-slate-tint-12)") : "var(--app-bg-elevated)",
                            color: form.priority === p ? (p === "Critical" ? "var(--app-danger-soft)" : p === "High" ? "var(--app-warning-soft)" : p === "Medium" ? "var(--app-warning-soft)" : "var(--app-text-subtle)") : "var(--app-text-subtle)",
                            border: `1px solid ${form.priority === p ? "var(--app-border-soft)" : "var(--app-border-strong)"}`,
                          }}
                        >{p}</button>
                      ))}
                    </div>
                  </div>

                  <div>
                    <label style={{ fontSize: 12, fontWeight: 500, color: "var(--app-text-muted)", letterSpacing: "0.02em", display: "block", marginBottom: 5 }}>
                      BUDGET{" "}<span style={{ color: "var(--app-danger)" }}>*</span>
                    </label>
                    <div className="flex gap-2">
                      <select
                        value={form.currency}
                        onChange={(e) => update("currency", e.target.value)}
                        style={{ width: 80, height: 38, background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", borderRadius: 8, padding: "0 8px", fontSize: 13, color: "var(--app-text-subtle)", outline: "none", cursor: "pointer" }}
                      >
                        {currencies.map((c) => <option key={c} value={c} style={{ background: "var(--app-bg-elevated)" }}>{c}</option>)}
                      </select>
                      <input
                        type="number"
                        value={form.budget}
                        onChange={(e) => update("budget", e.target.value)}
                        placeholder="0.00"
                        style={{ flex: 1, height: 38, background: "var(--app-bg-elevated)", border: `1px solid ${errors.budget ? "var(--app-danger)" : "var(--app-border-strong)"}`, borderRadius: 8, padding: "0 12px", fontSize: 13, color: "var(--app-text-main)", outline: "none", boxSizing: "border-box" }}
                        onFocus={(e) => { e.currentTarget.style.borderColor = "var(--app-brand-500)"; }}
                        onBlur={(e) => { e.currentTarget.style.borderColor = errors.budget ? "var(--app-danger)" : "var(--app-border-strong)"; }}
                      />
                    </div>
                    {errors.budget && <p style={{ fontSize: 11, color: "var(--app-danger)", marginTop: 4 }}>{errors.budget}</p>}
                  </div>

                  <div>
                    <label style={{ fontSize: 12, fontWeight: 500, color: "var(--app-text-muted)", letterSpacing: "0.02em", display: "block", marginBottom: 5 }}>DEPARTMENT</label>
                    <input
                      value={form.department}
                      onChange={(e) => update("department", e.target.value)}
                      placeholder="e.g., Operations, Engineering"
                      style={{ width: "100%", height: 38, background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", borderRadius: 8, padding: "0 12px", fontSize: 13, color: "var(--app-text-main)", outline: "none", boxSizing: "border-box" }}
                      onFocus={(e) => { e.currentTarget.style.borderColor = "var(--app-brand-500)"; }}
                      onBlur={(e) => { e.currentTarget.style.borderColor = "var(--app-border-strong)"; }}
                    />
                  </div>

                  <div className="col-span-2">
                    <label style={{ fontSize: 12, fontWeight: 500, color: "var(--app-text-muted)", letterSpacing: "0.02em", display: "block", marginBottom: 5 }}>DESCRIPTION</label>
                    <textarea
                      value={form.description}
                      onChange={(e) => update("description", e.target.value)}
                      rows={3}
                      placeholder="Describe the sourcing requirement, context, and key specifications…"
                      style={{ width: "100%", background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", borderRadius: 8, padding: "10px 12px", fontSize: 13, color: "var(--app-text-main)", outline: "none", boxSizing: "border-box", resize: "vertical", lineHeight: 1.6 }}
                      onFocus={(e) => { e.currentTarget.style.borderColor = "var(--app-brand-500)"; }}
                      onBlur={(e) => { e.currentTarget.style.borderColor = "var(--app-border-strong)"; }}
                    />
                  </div>
                </div>
              </div>
            )}

            {/* Step 2: Line Items */}
            {step === 2 && (
              <div>
                <div className="flex items-center justify-between mb-4">
                  <h2 style={{ fontSize: 16, fontWeight: 600, color: "var(--app-text-strong)" }}>Line Items</h2>
                  <span style={{ fontSize: 12, color: "var(--app-text-subtle)" }}>{lineItems.length} item{lineItems.length !== 1 ? "s" : ""}</span>
                </div>
                {errors.lineItems && (
                  <div className="flex items-center gap-2 rounded-lg px-3 py-2.5 mb-4" style={{ background: "var(--app-danger-tint-8)", border: "1px solid var(--app-danger-tint-20)" }}>
                    <AlertCircle size={13} style={{ color: "var(--app-danger)" }} />
                    <span style={{ fontSize: 12, color: "var(--app-danger-faint)" }}>{errors.lineItems}</span>
                  </div>
                )}

                <div className="rounded-lg overflow-hidden mb-3" style={{ border: "1px solid var(--app-border-strong)" }}>
                  <table style={{ width: "100%", borderCollapse: "collapse" }}>
                    <thead>
                      <tr style={{ borderBottom: "1px solid var(--app-border-strong)" }}>
                        {["#", "Description", "Qty", "UOM", "Target Unit Price", "Notes", ""].map((h) => (
                          <th key={h} style={{ fontSize: 10, fontWeight: 600, color: "var(--app-text-subtle)", letterSpacing: "0.06em", textTransform: "uppercase", padding: "8px 10px", textAlign: "left", background: "var(--app-bg-elevated)" }}>{h}</th>
                        ))}
                      </tr>
                    </thead>
                    <tbody>
                      {lineItems.map((item, i) => (
                        <tr key={item.id} style={{ borderBottom: i < lineItems.length - 1 ? "1px solid var(--app-bg-elevated)" : "none" }}>
                          <td style={{ padding: "8px 10px", width: 30, fontSize: 12, color: "var(--app-text-faint)" }}>{i + 1}</td>
                          <td style={{ padding: "8px 10px" }}>
                            <input
                              value={item.description}
                              onChange={(e) => updateLineItem(item.id, "description", e.target.value)}
                              placeholder="Item description…"
                              style={{ width: "100%", background: "transparent", border: "none", outline: "none", fontSize: 13, color: "var(--app-text-main)" }}
                            />
                          </td>
                          <td style={{ padding: "8px 10px", width: 70 }}>
                            <input
                              type="number"
                              value={item.qty}
                              onChange={(e) => updateLineItem(item.id, "qty", e.target.value)}
                              style={{ width: "100%", background: "transparent", border: "none", outline: "none", fontSize: 13, color: "var(--app-text-main)", textAlign: "right" }}
                            />
                          </td>
                          <td style={{ padding: "8px 10px", width: 90 }}>
                            <select
                              value={item.uom}
                              onChange={(e) => updateLineItem(item.id, "uom", e.target.value)}
                              style={{ background: "transparent", border: "none", outline: "none", fontSize: 13, color: "var(--app-text-subtle)", cursor: "pointer" }}
                            >
                              {uoms.map((u) => <option key={u} value={u} style={{ background: "var(--app-bg-elevated)" }}>{u}</option>)}
                            </select>
                          </td>
                          <td style={{ padding: "8px 10px", width: 130 }}>
                            <input
                              type="number"
                              value={item.targetPrice}
                              onChange={(e) => updateLineItem(item.id, "targetPrice", e.target.value)}
                              placeholder="0.00"
                              style={{ width: "100%", background: "transparent", border: "none", outline: "none", fontSize: 13, color: "var(--app-text-main)", textAlign: "right" }}
                            />
                          </td>
                          <td style={{ padding: "8px 10px" }}>
                            <input
                              value={item.notes}
                              onChange={(e) => updateLineItem(item.id, "notes", e.target.value)}
                              placeholder="Optional notes"
                              style={{ width: "100%", background: "transparent", border: "none", outline: "none", fontSize: 12, color: "var(--app-text-muted)" }}
                            />
                          </td>
                          <td style={{ padding: "8px 10px", width: 32 }}>
                            {lineItems.length > 1 && (
                              <button onClick={() => removeLineItem(item.id)} style={{ color: "var(--app-text-faint)" }} className="hover:text-red-400 transition-colors">
                                <Trash2 size={13} />
                              </button>
                            )}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>

                <button
                  onClick={addLineItem}
                  className="flex items-center gap-2 rounded-lg px-3 py-2 transition-colors"
                  style={{ fontSize: 13, color: "var(--app-brand-400)", background: "var(--app-brand-tint-6)", border: "1px solid var(--app-brand-tint-15)" }}
                  onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.background = "var(--app-brand-tint-10)"; }}
                  onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.background = "var(--app-brand-tint-6)"; }}
                >
                  <Plus size={13} /> Add Line Item
                </button>
              </div>
            )}

            {/* Step 3: Terms */}
            {step === 3 && (
              <div>
                <h2 style={{ fontSize: 16, fontWeight: 600, color: "var(--app-text-strong)", marginBottom: 20 }}>Terms & Deadlines</h2>
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label style={{ fontSize: 12, fontWeight: 500, color: "var(--app-text-muted)", letterSpacing: "0.02em", display: "block", marginBottom: 5 }}>QUOTE SUBMISSION DEADLINE <span style={{ color: "var(--app-danger)" }}>*</span></label>
                    <input
                      type="date"
                      value={form.submissionDeadline}
                      onChange={(e) => update("submissionDeadline", e.target.value)}
                      style={{ width: "100%", height: 38, background: "var(--app-bg-elevated)", border: `1px solid ${errors.submissionDeadline ? "var(--app-danger)" : "var(--app-border-strong)"}`, borderRadius: 8, padding: "0 12px", fontSize: 13, color: "var(--app-text-main)", outline: "none", boxSizing: "border-box", colorScheme: "dark" }}
                    />
                    {errors.submissionDeadline && <p style={{ fontSize: 11, color: "var(--app-danger)", marginTop: 4 }}>{errors.submissionDeadline}</p>}
                  </div>
                  <div>
                    <label style={{ fontSize: 12, fontWeight: 500, color: "var(--app-text-muted)", letterSpacing: "0.02em", display: "block", marginBottom: 5 }}>AWARD DECISION DEADLINE</label>
                    <input type="date" value={form.awardDeadline} onChange={(e) => update("awardDeadline", e.target.value)} style={{ width: "100%", height: 38, background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", borderRadius: 8, padding: "0 12px", fontSize: 13, color: "var(--app-text-main)", outline: "none", boxSizing: "border-box", colorScheme: "dark" }} />
                  </div>
                  <div>
                    <label style={{ fontSize: 12, fontWeight: 500, color: "var(--app-text-muted)", letterSpacing: "0.02em", display: "block", marginBottom: 5 }}>REQUIRED DELIVERY DATE</label>
                    <input type="date" value={form.deliveryDate} onChange={(e) => update("deliveryDate", e.target.value)} style={{ width: "100%", height: 38, background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", borderRadius: 8, padding: "0 12px", fontSize: 13, color: "var(--app-text-main)", outline: "none", boxSizing: "border-box", colorScheme: "dark" }} />
                  </div>
                  <div>
                    <label style={{ fontSize: 12, fontWeight: 500, color: "var(--app-text-muted)", letterSpacing: "0.02em", display: "block", marginBottom: 5 }}>PAYMENT TERMS</label>
                    <select value={form.paymentTerms} onChange={(e) => update("paymentTerms", e.target.value)} style={{ width: "100%", height: 38, background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", borderRadius: 8, padding: "0 12px", fontSize: 13, color: "var(--app-text-main)", outline: "none", cursor: "pointer" }}>
                      {paymentTerms.map((t) => <option key={t} value={t} style={{ background: "var(--app-bg-elevated)" }}>{t}</option>)}
                    </select>
                  </div>
                  <div>
                    <label style={{ fontSize: 12, fontWeight: 500, color: "var(--app-text-muted)", letterSpacing: "0.02em", display: "block", marginBottom: 5 }}>WARRANTY (MONTHS)</label>
                    <input type="number" value={form.warrantyMonths} onChange={(e) => update("warrantyMonths", e.target.value)} placeholder="12" style={{ width: "100%", height: 38, background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", borderRadius: 8, padding: "0 12px", fontSize: 13, color: "var(--app-text-main)", outline: "none", boxSizing: "border-box" }} />
                  </div>
                  <div>
                    <label style={{ fontSize: 12, fontWeight: 500, color: "var(--app-text-muted)", letterSpacing: "0.02em", display: "block", marginBottom: 5 }}>DELIVERY LOCATION</label>
                    <input value={form.deliveryLocation} onChange={(e) => update("deliveryLocation", e.target.value)} placeholder="Site name or address" style={{ width: "100%", height: 38, background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", borderRadius: 8, padding: "0 12px", fontSize: 13, color: "var(--app-text-main)", outline: "none", boxSizing: "border-box" }} />
                  </div>
                  <div className="col-span-2">
                    <label style={{ fontSize: 12, fontWeight: 500, color: "var(--app-text-muted)", letterSpacing: "0.02em", display: "block", marginBottom: 5 }}>ADDITIONAL NOTES TO VENDORS</label>
                    <textarea value={form.notes} onChange={(e) => update("notes", e.target.value)} rows={3} placeholder="Special instructions, evaluation criteria hints, compliance requirements…" style={{ width: "100%", background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)", borderRadius: 8, padding: "10px 12px", fontSize: 13, color: "var(--app-text-main)", outline: "none", boxSizing: "border-box", resize: "vertical", lineHeight: 1.6 }} />
                  </div>
                  <div className="col-span-2">
                    <div className="flex items-center gap-3 rounded-lg p-3" style={{ background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)" }}>
                      <button
                        onClick={() => update("insuranceRequired", !form.insuranceRequired)}
                        style={{ width: 38, height: 22, borderRadius: 11, background: form.insuranceRequired ? "var(--app-brand-600)" : "var(--app-border-strong)", transition: "all 0.15s", position: "relative", flexShrink: 0 }}
                      >
                        <div style={{ width: 16, height: 16, borderRadius: "50%", background: "white", position: "absolute", top: 3, left: form.insuranceRequired ? 19 : 3, transition: "all 0.15s" }} />
                      </button>
                      <div>
                        <div style={{ fontSize: 13, fontWeight: 500, color: "var(--app-text-main)" }}>Insurance Certificate Required</div>
                        <div style={{ fontSize: 11, color: "var(--app-text-subtle)", marginTop: 1 }}>Vendors must provide valid liability insurance before award</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* Step 4: Attachments */}
            {step === 4 && (
              <div>
                <h2 style={{ fontSize: 16, fontWeight: 600, color: "var(--app-text-strong)", marginBottom: 20 }}>Attachments & Review</h2>

                {/* Upload zone */}
                <div className="rounded-xl border-2 border-dashed p-8 text-center mb-6 transition-colors cursor-pointer"
                  style={{ borderColor: "var(--app-border-strong)", background: "var(--app-brand-tint-2)" }}
                  onMouseEnter={(e) => { (e.currentTarget as HTMLDivElement).style.borderColor = "var(--app-brand-500)"; (e.currentTarget as HTMLDivElement).style.background = "var(--app-brand-tint-4)"; }}
                  onMouseLeave={(e) => { (e.currentTarget as HTMLDivElement).style.borderColor = "var(--app-border-strong)"; (e.currentTarget as HTMLDivElement).style.background = "var(--app-brand-tint-2)"; }}
                >
                  <Upload size={22} style={{ color: "var(--app-brand-500)", margin: "0 auto 8px" }} />
                  <div style={{ fontSize: 14, fontWeight: 500, color: "var(--app-text-subtle)", marginBottom: 4 }}>Drop files here or click to upload</div>
                  <div style={{ fontSize: 12, color: "var(--app-text-subtle)" }}>PDF, XLSX, DOCX, ZIP up to 25 MB</div>
                </div>

                {attachments.length > 0 && (
                  <div className="space-y-2 mb-6">
                    {attachments.map((file) => (
                      <div key={file} className="flex items-center gap-3 rounded-lg px-3 py-2.5" style={{ background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)" }}>
                        <FileText size={14} style={{ color: "var(--app-brand-500)", flexShrink: 0 }} />
                        <span style={{ fontSize: 13, color: "var(--app-text-main)", flex: 1 }}>{file}</span>
                        <span style={{ fontSize: 11, color: "var(--app-text-subtle)" }}>Uploaded</span>
                        <button onClick={() => setAttachments((prev) => prev.filter((f) => f !== file))} style={{ color: "var(--app-text-faint)" }} className="hover:text-red-400 transition-colors">
                          <X size={13} />
                        </button>
                      </div>
                    ))}
                  </div>
                )}

                {/* Review summary */}
                <div className="rounded-xl p-4" style={{ background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)" }}>
                  <h3 style={{ fontSize: 13, fontWeight: 600, color: "var(--app-text-subtle)", marginBottom: 12, letterSpacing: "0.04em", textTransform: "uppercase" }}>Review Summary</h3>
                  <div className="grid grid-cols-2 gap-3">
                    {[
                      { label: "Title", value: form.title || "—" },
                      { label: "Category", value: form.category || "—" },
                      { label: "Budget", value: form.budget ? `${form.currency} ${Number(form.budget).toLocaleString()}` : "—" },
                      { label: "Priority", value: form.priority },
                      { label: "Line Items", value: `${lineItems.length} item${lineItems.length !== 1 ? "s" : ""}` },
                      { label: "Submission Deadline", value: form.submissionDeadline || "—" },
                      { label: "Payment Terms", value: form.paymentTerms },
                      { label: "Attachments", value: `${attachments.length} file${attachments.length !== 1 ? "s" : ""}` },
                    ].map((item) => (
                      <div key={item.label} className="flex gap-2">
                        <span style={{ fontSize: 12, color: "var(--app-text-subtle)", flexShrink: 0, minWidth: 140 }}>{item.label}:</span>
                        <span style={{ fontSize: 12, color: "var(--app-text-main)", fontWeight: 500 }}>{item.value}</span>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* Action bar */}
          <div className="flex items-center justify-between mt-4 rounded-xl border px-4 py-3" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)" }}>
            <div className="flex items-center gap-2">
              <button
                onClick={() => navigate("/rfqs")}
                className="rounded-lg px-3 py-2 transition-colors"
                style={{ fontSize: 13, color: "var(--app-text-subtle)", background: "transparent" }}
                onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.color = "var(--app-text-subtle)"; }}
                onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.color = "var(--app-text-subtle)"; }}
              >
                Cancel
              </button>
              <button
                className="rounded-lg px-3 py-2 transition-colors"
                style={{ fontSize: 13, color: "var(--app-text-muted)", background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)" }}
              >
                Save Draft
              </button>
            </div>
            <div className="flex items-center gap-2">
              {step > 1 && (
                <button
                  onClick={() => setStep((s) => s - 1)}
                  className="rounded-lg px-4 py-2 transition-colors"
                  style={{ fontSize: 13, color: "var(--app-text-subtle)", background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)" }}
                >
                  Back
                </button>
              )}
              {step < 4 ? (
                <button
                  onClick={handleNext}
                  className="flex items-center gap-2 rounded-lg px-4 py-2 transition-opacity hover:opacity-90"
                  style={{ fontSize: 13, fontWeight: 500, background: "var(--app-brand-600)", color: "white" }}
                >
                  Next <ChevronRight size={14} />
                </button>
              ) : (
                <button
                  onClick={handleSubmit}
                  disabled={saved}
                  className="flex items-center gap-2 rounded-lg px-4 py-2 transition-opacity hover:opacity-90"
                  style={{ fontSize: 13, fontWeight: 500, background: saved ? "var(--app-success)" : "var(--app-brand-600)", color: "white", opacity: saved ? 0.8 : 1 }}
                >
                  {saved ? <><div className="w-3.5 h-3.5 rounded-full border-2 border-white/30 border-t-white animate-spin" /> Publishing…</> : <><Check size={14} /> Publish RFQ</>}
                </button>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Template modal */}
      {showTemplate && (
        <div className="fixed inset-0 z-50 flex items-center justify-center" style={{ background: "var(--app-overlay)" }}>
          <div className="rounded-xl border p-6 shadow-2xl" style={{ background: "var(--app-bg-surface)", borderColor: "var(--app-border-strong)", width: 480 }}>
            <div className="flex items-center justify-between mb-4">
              <h3 style={{ fontSize: 16, fontWeight: 600, color: "var(--app-text-strong)" }}>RFQ Template Picker</h3>
              <button onClick={() => setShowTemplate(false)} style={{ color: "var(--app-text-subtle)" }} className="hover:text-slate-300 transition-colors"><X size={16} /></button>
            </div>
            <div className="space-y-2">
              {["Industrial Equipment Standard", "IT Services Procurement", "Software Licensing Bundle", "Professional Services", "Logistics & Distribution"].map((t) => (
                <button key={t} onClick={() => { update("title", t); setShowTemplate(false); }} className="w-full flex items-center gap-3 rounded-lg p-3 text-left transition-colors" style={{ background: "var(--app-bg-elevated)", border: "1px solid var(--app-border-strong)" }}
                  onMouseEnter={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-brand-500)"; }}
                  onMouseLeave={(e) => { (e.currentTarget as HTMLButtonElement).style.borderColor = "var(--app-border-strong)"; }}>
                  <LayoutTemplate size={14} style={{ color: "var(--app-brand-500)", flexShrink: 0 }} />
                  <span style={{ fontSize: 13, color: "var(--app-text-main)" }}>{t}</span>
                </button>
              ))}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
