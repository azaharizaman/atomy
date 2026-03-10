import React from 'react';
import { ChevronDown, ChevronUp } from 'lucide-react';
import { Card } from './Card';
import { Button } from './Button';
import { Checkbox, SelectInput, ToggleSwitch } from './Input';
import { SLATimerBadge, StatusBadge } from './Badge';

interface SplitAllocationEditorProps {
  vendors: { id: string; name: string; value: number }[];
  onChange: (next: { id: string; name: string; value: number }[]) => void;
}

export function SplitAllocationEditor({ vendors, onChange }: SplitAllocationEditorProps) {
  const total = vendors.reduce((sum, vendor) => sum + vendor.value, 0);

  return (
    <Card padding="md">
      <p className="text-sm font-semibold text-slate-800 mb-2">Split Allocation</p>
      <div className="space-y-2">
        {vendors.map(vendor => (
          <div key={vendor.id} className="grid grid-cols-12 gap-2 items-center">
            <span className="col-span-5 text-xs text-slate-700">{vendor.name}</span>
            <input
              className="col-span-5 h-2 accent-indigo-600"
              type="range"
              min={0}
              max={100}
              value={vendor.value}
              onChange={e => {
                const next = vendors.map(item => item.id === vendor.id ? { ...item, value: Number(e.target.value) } : item);
                onChange(next);
              }}
            />
            <span className="col-span-2 text-xs font-semibold text-slate-800 text-right">{vendor.value}%</span>
          </div>
        ))}
      </div>
      <div className="mt-3 text-xs">
        <span className={total === 100 ? 'text-green-700' : 'text-red-700'}>
          Total allocation: {total}% {total === 100 ? '✓' : '(must equal 100%)'}
        </span>
      </div>
    </Card>
  );
}

interface SignOffChecklistProps {
  items: { id: string; label: string; checked: boolean }[];
  onToggle: (id: string, checked: boolean) => void;
  onFinalize?: () => void;
}

export function SignOffChecklist({ items, onToggle, onFinalize }: SignOffChecklistProps) {
  const allDone = items.every(item => item.checked);
  return (
    <Card padding="md">
      <p className="text-sm font-semibold text-slate-800 mb-2">Sign-off</p>
      <div className="space-y-2">
        {items.map(item => (
          <Checkbox
            key={item.id}
            label={item.label}
            checked={item.checked}
            onChange={e => onToggle(item.id, e.currentTarget.checked)}
          />
        ))}
      </div>
      <div className="mt-3">
        <Button onClick={onFinalize} disabled={!allDone}>
          Finalize Award
        </Button>
      </div>
    </Card>
  );
}

interface DebriefStatusListProps {
  vendors: { id: string; name: string; status: 'not-sent' | 'sent' | 'na' }[];
  onSend?: (id: string) => void;
}

export function DebriefStatusList({ vendors, onSend }: DebriefStatusListProps) {
  const statusLabel = {
    'not-sent': 'Not sent',
    sent: 'Sent',
    na: 'N/A',
  };

  return (
    <Card padding="md">
      <p className="text-sm font-semibold text-slate-800 mb-2">Vendor Debrief</p>
      <div className="space-y-2">
        {vendors.map(vendor => (
          <div key={vendor.id} className="flex items-center justify-between">
            <span className="text-xs text-slate-700">{vendor.name}</span>
            <div className="flex items-center gap-2">
              <StatusBadge status={vendor.status === 'sent' ? 'approved' : vendor.status === 'na' ? 'draft' : 'pending'} label={statusLabel[vendor.status]} size="xs" />
              {vendor.status !== 'na' && (
                <Button size="sm" variant="outline" onClick={() => onSend?.(vendor.id)}>
                  Send Debrief
                </Button>
              )}
            </div>
          </div>
        ))}
      </div>
    </Card>
  );
}

interface HandoffStatusTimelineProps {
  items: { id: string; label: string; timestamp: string; state: 'done' | 'pending' | 'failed' }[];
}

export function HandoffStatusTimeline({ items }: HandoffStatusTimelineProps) {
  return (
    <Card padding="md">
      <p className="text-sm font-semibold text-slate-800 mb-2">Handoff Status</p>
      <div className="space-y-2">
        {items.map(item => (
          <div key={item.id} className="flex items-center justify-between text-xs">
            <span className="text-slate-700">{item.label}</span>
            <div className="flex items-center gap-2">
              <StatusBadge
                status={item.state === 'done' ? 'approved' : item.state === 'failed' ? 'error' : 'pending'}
                label={item.state === 'done' ? 'Done' : item.state === 'failed' ? 'Failed' : 'Pending'}
                size="xs"
              />
              <span className="text-slate-400">{item.timestamp}</span>
            </div>
          </div>
        ))}
      </div>
    </Card>
  );
}

interface PayloadPreviewPanelProps {
  destination?: string;
  payload?: string;
}

export function PayloadPreviewPanel({
  destination = 'SAP ERP',
  payload = '{ "rfqId": "RFQ-2401", "winner": "Dell Technologies" }',
}: PayloadPreviewPanelProps) {
  const [open, setOpen] = React.useState(false);
  return (
    <Card padding="md">
      <div className="flex items-center justify-between">
        <p className="text-sm font-semibold text-slate-800">PO / Contract Handoff</p>
        <button className="text-slate-400 hover:text-slate-600" onClick={() => setOpen(v => !v)}>
          {open ? <ChevronUp size={14} /> : <ChevronDown size={14} />}
        </button>
      </div>
      <div className="mt-2 grid grid-cols-2 gap-2">
        <SelectInput
          label="Destination"
          compact
          value={destination}
          options={[{ value: 'SAP ERP', label: 'SAP ERP' }, { value: 'Oracle ERP', label: 'Oracle ERP' }]}
        />
        <div className="flex items-end">
          <Button variant="outline" size="sm">Validate Payload</Button>
        </div>
      </div>
      {open && (
        <pre className="mt-2 rounded-md bg-slate-50 border border-slate-200 p-2 text-[11px] text-slate-600 overflow-auto">
          {payload}
        </pre>
      )}
    </Card>
  );
}

export function ProtestTimerBadge({ value = 'Standstill: 10 days remaining' }: { value?: string }) {
  return <SLATimerBadge variant="warning" value={value} />;
}

interface AwardDecisionSummaryProps {
  winner: string;
  total: string;
  savings: string;
  confidence: number;
}

export function AwardDecisionSummary({
  winner,
  total,
  savings,
  confidence,
}: AwardDecisionSummaryProps) {
  const [splitAward, setSplitAward] = React.useState(false);

  return (
    <Card padding="md">
      <div className="flex items-start justify-between">
        <div>
          <p className="text-xs uppercase tracking-wide text-slate-500">Recommended Winner</p>
          <h3 className="text-base font-semibold text-slate-900">{winner}</h3>
        </div>
        <StatusBadge status="approved" label="Recommended" />
      </div>
      <div className="mt-2 text-xs text-slate-600">
        Total: <span className="font-semibold text-slate-800">{total}</span> · Savings: <span className="font-semibold text-green-700">{savings}</span> · Confidence: {confidence}%
      </div>
      <div className="mt-3">
        <ToggleSwitch checked={splitAward} onChange={setSplitAward} label="Enable split award" />
      </div>
    </Card>
  );
}
