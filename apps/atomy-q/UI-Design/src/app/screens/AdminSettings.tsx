import { useState } from 'react';
import {
  Settings, Building2, GitBranch, ShieldCheck, ToggleLeft,
  ChevronRight, Save, AlertTriangle, Clock, DollarSign,
  CalendarDays, Globe, Lock, Leaf, Hash, RotateCcw,
  Fingerprint, ShieldAlert, FileCheck
} from 'lucide-react';
import { Link } from 'react-router';
import { adminSettings, featureFlags } from '../data/mockData';
import { SlideOver } from '../components/SlideOver';

type Category = 'general' | 'workflow' | 'compliance' | 'flags';

const categories: { id: Category; label: string; icon: typeof Settings; description: string }[] = [
  { id: 'general', label: 'General', icon: Building2, description: 'Tenant identity, locale, and display preferences' },
  { id: 'workflow', label: 'Workflow', icon: GitBranch, description: 'Approval thresholds, negotiation, and process controls' },
  { id: 'compliance', label: 'Compliance', icon: ShieldCheck, description: 'Sanctions screening, due diligence, and retention' },
  { id: 'flags', label: 'Feature Flags', icon: ToggleLeft, description: 'Toggle platform capabilities on or off' },
];

const timezones = [
  'Australia/Sydney', 'Australia/Melbourne', 'Australia/Perth', 'Australia/Brisbane',
  'Pacific/Auckland', 'Asia/Singapore', 'Asia/Tokyo', 'Europe/London',
  'America/New_York', 'America/Los_Angeles', 'UTC',
];

const currencies = ['AUD', 'USD', 'EUR', 'GBP', 'SGD', 'NZD', 'JPY'];
const dateFormats = ['DD MMM YYYY', 'DD/MM/YYYY', 'MM/DD/YYYY', 'YYYY-MM-DD'];
const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

function Toggle({ enabled, onChange }: { enabled: boolean; onChange: (v: boolean) => void }) {
  return (
    <button
      type="button"
      onClick={() => onChange(!enabled)}
      className={`relative inline-flex h-5 w-9 items-center rounded-full transition-colors ${enabled ? 'bg-indigo-600' : 'bg-slate-300'}`}
    >
      <span
        className={`inline-block h-3.5 w-3.5 rounded-full bg-white shadow transition-transform ${enabled ? 'translate-x-[18px]' : 'translate-x-[3px]'}`}
      />
    </button>
  );
}

export function AdminSettings() {
  const [activeCategory, setActiveCategory] = useState<Category>('general');
  const [editedSettings, setEditedSettings] = useState({ ...adminSettings });
  const [editedFlags, setEditedFlags] = useState(featureFlags.map((f) => ({ ...f })));
  const [showConfirmModal, setShowConfirmModal] = useState(false);
  const [pendingAction, setPendingAction] = useState<{ label: string; description: string } | null>(null);

  const updateGeneral = <K extends keyof typeof adminSettings.general>(key: K, value: typeof adminSettings.general[K]) => {
    setEditedSettings((prev) => ({ ...prev, general: { ...prev.general, [key]: value } }));
  };

  const updateWorkflow = <K extends keyof typeof adminSettings.workflow>(key: K, value: typeof adminSettings.workflow[K]) => {
    setEditedSettings((prev) => ({ ...prev, workflow: { ...prev.workflow, [key]: value } }));
  };

  const updateCompliance = <K extends keyof typeof adminSettings.compliance>(key: K, value: typeof adminSettings.compliance[K]) => {
    setEditedSettings((prev) => ({ ...prev, compliance: { ...prev.compliance, [key]: value } }));
  };

  const toggleFlag = (id: string) => {
    setEditedFlags((prev) => prev.map((f) => (f.id === id ? { ...f, enabled: !f.enabled } : f)));
  };

  const handleDestructiveAction = (label: string, description: string) => {
    setPendingAction({ label, description });
    setShowConfirmModal(true);
  };

  return (
    <div className="flex flex-col h-full bg-slate-50">
      {/* Header */}
      <div className="bg-white border-b border-slate-200 px-6 py-4 flex-shrink-0">
        <div className="flex items-center gap-1.5 text-xs text-slate-500 mb-3">
          <Link to="/" className="hover:text-slate-700">Home</Link>
          <ChevronRight size={11} />
          <span className="text-slate-700" style={{ fontWeight: 500 }}>Admin Settings</span>
        </div>
        <div className="flex items-center gap-3 mb-1">
          <Settings size={20} className="text-indigo-600" />
          <h1 className="text-slate-900 text-lg" style={{ fontWeight: 700 }}>Platform Settings</h1>
        </div>
        <p className="text-xs text-slate-500 mt-0.5">Configure tenant-level governance controls, workflow behavior, and feature availability.</p>
      </div>

      {/* Body */}
      <div className="flex flex-1 overflow-hidden">
        {/* Left Nav */}
        <div className="w-64 bg-white border-r border-slate-200 flex-shrink-0 overflow-y-auto py-4 px-3">
          <div className="text-[10px] uppercase tracking-wider text-slate-400 px-3 mb-2" style={{ fontWeight: 700 }}>Categories</div>
          <div className="space-y-0.5">
            {categories.map((cat) => {
              const Icon = cat.icon;
              const isActive = activeCategory === cat.id;
              return (
                <button
                  key={cat.id}
                  onClick={() => setActiveCategory(cat.id)}
                  className={`w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-left transition-colors ${
                    isActive ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50'
                  }`}
                >
                  <Icon size={16} className={isActive ? 'text-indigo-600' : 'text-slate-400'} />
                  <div>
                    <div className="text-sm" style={{ fontWeight: isActive ? 600 : 500 }}>{cat.label}</div>
                    <div className="text-[10px] text-slate-400 leading-tight mt-0.5">{cat.description}</div>
                  </div>
                </button>
              );
            })}
          </div>
        </div>

        {/* Right Content */}
        <div className="flex-1 overflow-y-auto p-6">
          <div className="max-w-2xl">
            {/* ─── General ─── */}
            {activeCategory === 'general' && (
              <div className="space-y-6">
                <div>
                  <h2 className="text-slate-900 text-base" style={{ fontWeight: 700 }}>General Settings</h2>
                  <p className="text-xs text-slate-500 mt-1">Configure your organization's identity and display preferences.</p>
                </div>

                <div className="bg-white rounded-xl border border-slate-200 shadow-sm divide-y divide-slate-100">
                  {/* Tenant Name */}
                  <div className="p-4">
                    <label className="flex items-center gap-2 text-sm text-slate-700 mb-2" style={{ fontWeight: 600 }}>
                      <Building2 size={14} className="text-slate-400" />
                      Tenant Name
                    </label>
                    <input
                      type="text"
                      value={editedSettings.general.tenantName}
                      onChange={(e) => updateGeneral('tenantName', e.target.value)}
                      className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-800 focus:border-indigo-400 outline-none"
                    />
                  </div>

                  {/* Timezone */}
                  <div className="p-4">
                    <label className="flex items-center gap-2 text-sm text-slate-700 mb-2" style={{ fontWeight: 600 }}>
                      <Globe size={14} className="text-slate-400" />
                      Timezone
                    </label>
                    <select
                      value={editedSettings.general.timezone}
                      onChange={(e) => updateGeneral('timezone', e.target.value)}
                      className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-800 bg-white focus:border-indigo-400 outline-none"
                    >
                      {timezones.map((tz) => (
                        <option key={tz} value={tz}>{tz}</option>
                      ))}
                    </select>
                  </div>

                  {/* Currency */}
                  <div className="p-4">
                    <label className="flex items-center gap-2 text-sm text-slate-700 mb-2" style={{ fontWeight: 600 }}>
                      <DollarSign size={14} className="text-slate-400" />
                      Default Currency
                    </label>
                    <select
                      value={editedSettings.general.currency}
                      onChange={(e) => updateGeneral('currency', e.target.value)}
                      className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-800 bg-white focus:border-indigo-400 outline-none"
                    >
                      {currencies.map((c) => (
                        <option key={c} value={c}>{c}</option>
                      ))}
                    </select>
                  </div>

                  {/* Date Format */}
                  <div className="p-4">
                    <label className="flex items-center gap-2 text-sm text-slate-700 mb-2" style={{ fontWeight: 600 }}>
                      <CalendarDays size={14} className="text-slate-400" />
                      Date Format
                    </label>
                    <select
                      value={editedSettings.general.dateFormat}
                      onChange={(e) => updateGeneral('dateFormat', e.target.value)}
                      className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-800 bg-white focus:border-indigo-400 outline-none"
                    >
                      {dateFormats.map((df) => (
                        <option key={df} value={df}>{df}</option>
                      ))}
                    </select>
                  </div>

                  {/* Fiscal Year Start */}
                  <div className="p-4">
                    <label className="flex items-center gap-2 text-sm text-slate-700 mb-2" style={{ fontWeight: 600 }}>
                      <CalendarDays size={14} className="text-slate-400" />
                      Fiscal Year Start
                    </label>
                    <select
                      value={editedSettings.general.fiscalYearStart}
                      onChange={(e) => updateGeneral('fiscalYearStart', e.target.value)}
                      className="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-800 bg-white focus:border-indigo-400 outline-none"
                    >
                      {months.map((m) => (
                        <option key={m} value={m}>{m}</option>
                      ))}
                    </select>
                  </div>
                </div>

                <div className="flex justify-end">
                  <button className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-5 py-2.5 text-sm" style={{ fontWeight: 500 }}>
                    <Save size={14} />
                    Save General Settings
                  </button>
                </div>
              </div>
            )}

            {/* ─── Workflow ─── */}
            {activeCategory === 'workflow' && (
              <div className="space-y-6">
                <div>
                  <h2 className="text-slate-900 text-base" style={{ fontWeight: 700 }}>Workflow Settings</h2>
                  <p className="text-xs text-slate-500 mt-1">Control approval thresholds, negotiation limits, and process behavior.</p>
                </div>

                <div className="bg-white rounded-xl border border-slate-200 shadow-sm divide-y divide-slate-100">
                  {/* Auto-approve Threshold */}
                  <div className="p-4">
                    <label className="flex items-center gap-2 text-sm text-slate-700 mb-1" style={{ fontWeight: 600 }}>
                      <DollarSign size={14} className="text-slate-400" />
                      Auto-approve Threshold
                    </label>
                    <p className="text-xs text-slate-400 mb-2">Awards below this value are automatically approved without manual review.</p>
                    <div className="relative w-64">
                      <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">$</span>
                      <input
                        type="number"
                        min={0}
                        step={1000}
                        value={editedSettings.workflow.autoApproveThreshold}
                        onChange={(e) => updateWorkflow('autoApproveThreshold', parseInt(e.target.value) || 0)}
                        className="w-full border border-slate-200 rounded-lg pl-7 pr-3 py-2 text-sm text-slate-800 focus:border-indigo-400 outline-none"
                      />
                    </div>
                  </div>

                  {/* Require MFA */}
                  <div className="p-4 flex items-center justify-between">
                    <div>
                      <label className="flex items-center gap-2 text-sm text-slate-700" style={{ fontWeight: 600 }}>
                        <Fingerprint size={14} className="text-slate-400" />
                        Require MFA for Approvals
                      </label>
                      <p className="text-xs text-slate-400 mt-0.5 ml-6">Enforce multi-factor authentication before any approval action.</p>
                    </div>
                    <Toggle
                      enabled={editedSettings.workflow.requireMfaForApprovals}
                      onChange={(v) => updateWorkflow('requireMfaForApprovals', v)}
                    />
                  </div>

                  {/* Standstill Period */}
                  <div className="p-4">
                    <label className="flex items-center gap-2 text-sm text-slate-700 mb-1" style={{ fontWeight: 600 }}>
                      <Clock size={14} className="text-slate-400" />
                      Standstill Period (days)
                    </label>
                    <p className="text-xs text-slate-400 mb-2">Mandatory waiting period after award decision before finalization.</p>
                    <input
                      type="number"
                      min={0}
                      max={90}
                      value={editedSettings.workflow.standstillPeriodDays}
                      onChange={(e) => updateWorkflow('standstillPeriodDays', parseInt(e.target.value) || 0)}
                      className="w-32 border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-800 focus:border-indigo-400 outline-none"
                    />
                  </div>

                  {/* Max Negotiation Rounds */}
                  <div className="p-4">
                    <label className="flex items-center gap-2 text-sm text-slate-700 mb-1" style={{ fontWeight: 600 }}>
                      <RotateCcw size={14} className="text-slate-400" />
                      Max Negotiation Rounds
                    </label>
                    <p className="text-xs text-slate-400 mb-2">Maximum number of negotiation rounds per RFQ before BAFO is required.</p>
                    <input
                      type="number"
                      min={1}
                      max={20}
                      value={editedSettings.workflow.maxNegotiationRounds}
                      onChange={(e) => updateWorkflow('maxNegotiationRounds', parseInt(e.target.value) || 1)}
                      className="w-32 border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-800 focus:border-indigo-400 outline-none"
                    />
                  </div>

                  {/* Allow RFQ Reopen */}
                  <div className="p-4 flex items-center justify-between">
                    <div>
                      <label className="flex items-center gap-2 text-sm text-slate-700" style={{ fontWeight: 600 }}>
                        <GitBranch size={14} className="text-slate-400" />
                        Allow RFQ Reopen
                      </label>
                      <p className="text-xs text-slate-400 mt-0.5 ml-6">Permit reopening closed RFQs for additional quote submissions.</p>
                    </div>
                    <Toggle
                      enabled={editedSettings.workflow.allowRfqReopen}
                      onChange={(v) => updateWorkflow('allowRfqReopen', v)}
                    />
                  </div>
                </div>

                <div className="flex justify-end">
                  <button className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-5 py-2.5 text-sm" style={{ fontWeight: 500 }}>
                    <Save size={14} />
                    Save Workflow Settings
                  </button>
                </div>
              </div>
            )}

            {/* ─── Compliance ─── */}
            {activeCategory === 'compliance' && (
              <div className="space-y-6">
                <div>
                  <h2 className="text-slate-900 text-base" style={{ fontWeight: 700 }}>Compliance Settings</h2>
                  <p className="text-xs text-slate-500 mt-1">Manage sanctions screening, due diligence requirements, and data retention.</p>
                </div>

                <div className="bg-white rounded-xl border border-slate-200 shadow-sm divide-y divide-slate-100">
                  {/* Mandatory Sanctions Screening */}
                  <div className="p-4 flex items-center justify-between">
                    <div>
                      <label className="flex items-center gap-2 text-sm text-slate-700" style={{ fontWeight: 600 }}>
                        <ShieldAlert size={14} className="text-slate-400" />
                        Mandatory Sanctions Screening
                      </label>
                      <p className="text-xs text-slate-400 mt-0.5 ml-6">Require all vendors to pass sanctions screening before quote acceptance.</p>
                    </div>
                    <Toggle
                      enabled={editedSettings.compliance.mandatorySanctionsScreening}
                      onChange={(v) => {
                        if (!v) {
                          handleDestructiveAction(
                            'Disable Sanctions Screening',
                            'Disabling mandatory sanctions screening may expose the organization to regulatory risk. Vendors will no longer be automatically screened before quote acceptance.'
                          );
                        }
                        updateCompliance('mandatorySanctionsScreening', v);
                      }}
                    />
                  </div>

                  {/* Due Diligence Required */}
                  <div className="p-4 flex items-center justify-between">
                    <div>
                      <label className="flex items-center gap-2 text-sm text-slate-700" style={{ fontWeight: 600 }}>
                        <FileCheck size={14} className="text-slate-400" />
                        Due Diligence Required
                      </label>
                      <p className="text-xs text-slate-400 mt-0.5 ml-6">Require completed due diligence checklist before vendor engagement.</p>
                    </div>
                    <Toggle
                      enabled={editedSettings.compliance.dueDiligenceRequired}
                      onChange={(v) => updateCompliance('dueDiligenceRequired', v)}
                    />
                  </div>

                  {/* Retention Period */}
                  <div className="p-4">
                    <label className="flex items-center gap-2 text-sm text-slate-700 mb-1" style={{ fontWeight: 600 }}>
                      <Lock size={14} className="text-slate-400" />
                      Retention Period (years)
                    </label>
                    <p className="text-xs text-slate-400 mb-2">Duration documents and decision trail records are retained before archival.</p>
                    <input
                      type="number"
                      min={1}
                      max={25}
                      value={editedSettings.compliance.retentionPeriodYears}
                      onChange={(e) => updateCompliance('retentionPeriodYears', parseInt(e.target.value) || 1)}
                      className="w-32 border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-800 focus:border-indigo-400 outline-none"
                    />
                  </div>

                  {/* Audit Hash Algorithm */}
                  <div className="p-4">
                    <label className="flex items-center gap-2 text-sm text-slate-700 mb-1" style={{ fontWeight: 600 }}>
                      <Hash size={14} className="text-slate-400" />
                      Audit Hash Algorithm
                    </label>
                    <p className="text-xs text-slate-400 mb-2">Cryptographic algorithm used to sign decision trail entries.</p>
                    <div className="flex items-center gap-2">
                      <div className="border border-slate-200 bg-slate-50 rounded-lg px-3 py-2 text-sm text-slate-600 inline-flex items-center gap-2">
                        <Lock size={12} className="text-slate-400" />
                        {editedSettings.compliance.auditHashAlgorithm}
                      </div>
                      <span className="text-[10px] text-slate-400">Read-only — managed by security policy</span>
                    </div>
                  </div>

                  {/* Sustainability Threshold */}
                  <div className="p-4">
                    <label className="flex items-center gap-2 text-sm text-slate-700 mb-1" style={{ fontWeight: 600 }}>
                      <Leaf size={14} className="text-slate-400" />
                      Sustainability Threshold
                    </label>
                    <p className="text-xs text-slate-400 mb-2">Contract value above which sustainability scoring becomes mandatory.</p>
                    <div className="relative w-64">
                      <span className="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">$</span>
                      <input
                        type="number"
                        min={0}
                        step={10000}
                        value={editedSettings.compliance.sustainabilityThreshold}
                        onChange={(e) => updateCompliance('sustainabilityThreshold', parseInt(e.target.value) || 0)}
                        className="w-full border border-slate-200 rounded-lg pl-7 pr-3 py-2 text-sm text-slate-800 focus:border-indigo-400 outline-none"
                      />
                    </div>
                  </div>
                </div>

                <div className="flex justify-end">
                  <button className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-5 py-2.5 text-sm" style={{ fontWeight: 500 }}>
                    <Save size={14} />
                    Save Compliance Settings
                  </button>
                </div>
              </div>
            )}

            {/* ─── Feature Flags ─── */}
            {activeCategory === 'flags' && (
              <div className="space-y-6">
                <div>
                  <h2 className="text-slate-900 text-base" style={{ fontWeight: 700 }}>Feature Flags</h2>
                  <p className="text-xs text-slate-500 mt-1">Enable or disable platform capabilities for your tenant.</p>
                </div>

                <div className="bg-white rounded-xl border border-slate-200 shadow-sm divide-y divide-slate-100">
                  {editedFlags.map((flag) => (
                    <div key={flag.id} className="p-4 flex items-center justify-between gap-4">
                      <div className="flex-1">
                        <div className="flex items-center gap-2">
                          <span className="text-sm text-slate-800" style={{ fontWeight: 600 }}>{flag.label}</span>
                          <span className={`text-[10px] px-1.5 py-0.5 rounded ${flag.enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'}`} style={{ fontWeight: 600 }}>
                            {flag.enabled ? 'ON' : 'OFF'}
                          </span>
                        </div>
                        <p className="text-xs text-slate-400 mt-0.5">{flag.description}</p>
                        <div className="text-[10px] text-slate-300 mt-1 font-mono">{flag.name}</div>
                      </div>
                      <Toggle enabled={flag.enabled} onChange={() => toggleFlag(flag.id)} />
                    </div>
                  ))}
                </div>

                <div className="flex justify-end">
                  <button className="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-5 py-2.5 text-sm" style={{ fontWeight: 500 }}>
                    <Save size={14} />
                    Save Feature Flags
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* ─── Confirm Destructive Change Modal ─── */}
      <SlideOver
        open={showConfirmModal}
        onOpenChange={setShowConfirmModal}
        title="Confirm Destructive Change"
        description="This action may have significant impact on platform behavior."
        width="sm"
        footer={
          <div className="flex items-center justify-end gap-3">
            <button onClick={() => setShowConfirmModal(false)} className="border border-slate-200 rounded-lg px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">
              Cancel
            </button>
            <button
              onClick={() => setShowConfirmModal(false)}
              className="bg-red-600 hover:bg-red-700 text-white rounded-lg px-4 py-2 text-sm"
              style={{ fontWeight: 500 }}
            >
              Confirm Change
            </button>
          </div>
        }
      >
        <div className="space-y-4">
          <div className="bg-red-50 border border-red-200 rounded-xl p-4">
            <div className="flex items-center gap-2 mb-2">
              <AlertTriangle size={14} className="text-red-600" />
              <span className="text-sm text-red-700" style={{ fontWeight: 700 }}>{pendingAction?.label}</span>
            </div>
            <p className="text-xs text-red-600">{pendingAction?.description}</p>
          </div>
          <div className="bg-amber-50 border border-amber-200 rounded-xl p-3">
            <div className="flex items-start gap-2">
              <AlertTriangle size={13} className="text-amber-600 mt-0.5" />
              <span className="text-xs text-amber-700" style={{ fontWeight: 500 }}>
                This change will take effect immediately and may impact active procurement workflows. A record of this change will be logged in the decision trail.
              </span>
            </div>
          </div>
        </div>
      </SlideOver>
    </div>
  );
}
