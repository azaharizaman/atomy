import React from 'react';
import { Plus, Trash2, UploadCloud } from 'lucide-react';
import { Card, UploadZone } from './Card';
import { TextInput, SelectInput } from './Input';
import { Button } from './Button';
import { ProgressBar } from './Progress';

export interface StepperStep {
  id: string;
  label: string;
}

interface StepperProps {
  steps: StepperStep[];
  activeStepId: string;
  className?: string;
}

export function Stepper({ steps, activeStepId, className = '' }: StepperProps) {
  const activeIndex = Math.max(steps.findIndex(step => step.id === activeStepId), 0);

  return (
    <div className={className}>
      <div className="flex items-center gap-2">
        {steps.map((step, idx) => {
          const state = idx < activeIndex ? 'done' : idx === activeIndex ? 'active' : 'todo';
          return (
            <div key={step.id} className="flex items-center flex-1 gap-2 min-w-0">
              <div
                className={[
                  'w-5 h-5 rounded-full text-[10px] font-semibold flex items-center justify-center shrink-0',
                  state === 'done'
                    ? 'bg-green-100 text-green-700'
                    : state === 'active'
                      ? 'bg-indigo-600 text-white'
                      : 'bg-slate-200 text-slate-500',
                ].join(' ')}
              >
                {idx + 1}
              </div>
              <span className={['text-xs truncate', state === 'active' ? 'text-slate-800 font-medium' : 'text-slate-500'].join(' ')}>
                {step.label}
              </span>
              {idx < steps.length - 1 && <div className="h-px flex-1 bg-slate-200" />}
            </div>
          );
        })}
      </div>
      <div className="mt-2">
        <ProgressBar value={((activeIndex + 1) / steps.length) * 100} size="sm" variant="indigo" />
      </div>
    </div>
  );
}

interface StickyActionBarProps {
  onCancel?: () => void;
  onSaveDraft?: () => void;
  onSubmit?: () => void;
  submitLabel?: string;
  disabledSubmit?: boolean;
  className?: string;
}

export function StickyActionBar({
  onCancel,
  onSaveDraft,
  onSubmit,
  submitLabel = 'Submit / Publish',
  disabledSubmit = false,
  className = '',
}: StickyActionBarProps) {
  return (
    <div className={['sticky bottom-0 z-10 border-t border-slate-200 bg-white px-4 py-3', className].join(' ')}>
      <div className="flex items-center justify-end gap-2">
        <Button variant="ghost" onClick={onCancel}>Cancel</Button>
        <Button variant="secondary" onClick={onSaveDraft}>Save Draft</Button>
        <Button onClick={onSubmit} disabled={disabledSubmit}>{submitLabel}</Button>
      </div>
    </div>
  );
}

export interface LineItem {
  id: string;
  description: string;
  qty: number;
  unit: string;
  targetPrice: number;
}

interface LineItemEditorProps {
  items: LineItem[];
  onChange: (items: LineItem[]) => void;
  className?: string;
}

export function LineItemEditor({ items, onChange, className = '' }: LineItemEditorProps) {
  function update(id: string, patch: Partial<LineItem>) {
    onChange(items.map(item => (item.id === id ? { ...item, ...patch } : item)));
  }

  function remove(id: string) {
    onChange(items.filter(item => item.id !== id));
  }

  function add() {
    onChange([
      ...items,
      {
        id: `new-${Date.now()}`,
        description: '',
        qty: 1,
        unit: 'EA',
        targetPrice: 0,
      },
    ]);
  }

  return (
    <Card padding="none" className={className}>
      <div className="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
        <p className="text-sm font-semibold text-slate-800">Line Items</p>
        <Button size="sm" variant="outline" icon={<Plus size={13} />} onClick={add}>
          Add Line
        </Button>
      </div>
      <div className="divide-y divide-slate-100">
        {items.map(item => (
          <div key={item.id} className="grid grid-cols-12 gap-2 p-3">
            <div className="col-span-5">
              <TextInput
                placeholder="Description"
                value={item.description}
                onChange={e => update(item.id, { description: e.target.value })}
              />
            </div>
            <div className="col-span-2">
              <TextInput
                type="number"
                placeholder="Qty"
                value={String(item.qty)}
                onChange={e => update(item.id, { qty: Number(e.target.value || 0) })}
              />
            </div>
            <div className="col-span-2">
              <SelectInput
                options={[
                  { value: 'EA', label: 'EA' },
                  { value: 'BOX', label: 'BOX' },
                  { value: 'LOT', label: 'LOT' },
                ]}
                value={item.unit}
                onChange={e => update(item.id, { unit: e.target.value })}
              />
            </div>
            <div className="col-span-2">
              <TextInput
                type="number"
                placeholder="Target $"
                value={String(item.targetPrice)}
                onChange={e => update(item.id, { targetPrice: Number(e.target.value || 0) })}
              />
            </div>
            <div className="col-span-1 flex justify-end">
              <Button variant="ghost" size="sm" onClick={() => remove(item.id)} icon={<Trash2 size={13} />}>
                Remove
              </Button>
            </div>
          </div>
        ))}
      </div>
    </Card>
  );
}

export interface UploadItemProgress {
  id: string;
  fileName: string;
  progress: number;
}

interface UploadDropzoneWithProgressProps {
  uploads: UploadItemProgress[];
  onBrowse?: () => void;
}

export function UploadDropzoneWithProgress({ uploads, onBrowse }: UploadDropzoneWithProgressProps) {
  return (
    <Card padding="md">
      <div className="flex items-center gap-2 mb-3">
        <UploadCloud size={14} className="text-indigo-600" />
        <p className="text-sm font-semibold text-slate-800">Attachments</p>
      </div>
      <UploadZone compact onBrowse={onBrowse} />
      {uploads.length > 0 && (
        <div className="mt-3 space-y-2">
          {uploads.map(item => (
            <div key={item.id}>
              <div className="flex items-center justify-between mb-1">
                <span className="text-xs text-slate-600 truncate">{item.fileName}</span>
                <span className="text-[11px] text-slate-500">{item.progress}%</span>
              </div>
              <ProgressBar value={item.progress} size="sm" variant="indigo" />
            </div>
          ))}
        </div>
      )}
    </Card>
  );
}
