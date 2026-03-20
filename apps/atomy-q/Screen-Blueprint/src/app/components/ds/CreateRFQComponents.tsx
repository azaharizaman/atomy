import React, { useMemo } from 'react';
import { Plus, UploadCloud, GripVertical, Rows3 } from 'lucide-react';
import { Card, UploadZone } from './Card';
import { TextInput, SelectInput } from './Input';
import { Button } from './Button';
import { ProgressBar } from './Progress';
import { HorizontalProcessTrack, type HorizontalProcessTrackStep } from './HorizontalProcessTrack';

export interface StepperStep {
  id: string;
  label: string;
  description?: string;
  /** Maps to track health (amber/red node + segment). */
  status?: 'default' | 'issue' | 'blocked';
  /** ISO date string; use with `showTodayCursor` on `Stepper`. */
  date?: string;
}

interface StepperProps {
  steps: StepperStep[];
  activeStepId: string;
  className?: string;
  /** @default true */
  showProgressBar?: boolean;
  showTodayCursor?: boolean;
  today?: Date;
  pinTodayCursorToEnds?: boolean;
  variant?: 'compact' | 'detailed';
}

export function Stepper({
  steps,
  activeStepId,
  className = '',
  showProgressBar = true,
  showTodayCursor = false,
  today,
  pinTodayCursorToEnds = false,
  variant = 'compact',
}: StepperProps) {
  const activeIndex = Math.max(steps.findIndex(step => step.id === activeStepId), 0);

  const trackSteps: HorizontalProcessTrackStep[] = useMemo(
    () =>
      steps.map((step, idx) => {
        const progress =
          idx < activeIndex ? 'complete' : idx === activeIndex ? 'current' : 'upcoming';
        const health =
          step.status === 'issue'
            ? 'issue'
            : step.status === 'blocked'
              ? 'blocked'
              : 'default';
        return {
          id: step.id,
          label: step.label,
          description: step.description,
          progress,
          health,
          date: step.date,
        };
      }),
    [steps, activeIndex],
  );

  return (
    <div className={className}>
      <HorizontalProcessTrack
        steps={trackSteps}
        variant={variant}
        completeAppearance="success"
        showTodayCursor={showTodayCursor}
        today={today}
        pinTodayCursorToEnds={pinTodayCursorToEnds}
      />
      {showProgressBar && (
        <div className="mt-3">
          <ProgressBar
            value={((activeIndex + 1) / steps.length) * 100}
            size="sm"
            variant="indigo"
          />
        </div>
      )}
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
  entryType?: 'line' | 'heading';
  heading?: string;
  subheading?: string;
  description: string;
  qty: number;
  unit: string;
  targetPrice: number;
}

interface LineItemEditorProps {
  items: LineItem[];
  onChange: (items: LineItem[]) => void;
  currency?: string;
  locale?: string;
  currencyPosition?: 'auto' | 'prefix' | 'suffix';
  className?: string;
}

function detectCurrencyPosition(locale: string, currency: string): 'prefix' | 'suffix' {
  const formatted = new Intl.NumberFormat(locale, {
    style: 'currency',
    currency,
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).formatToParts(1);
  const currencyIndex = formatted.findIndex(part => part.type === 'currency');
  const numberIndex = formatted.findIndex(part => part.type === 'integer');
  return currencyIndex <= numberIndex ? 'prefix' : 'suffix';
}

export function LineItemEditor({
  items,
  onChange,
  currency = 'USD',
  locale = 'en-US',
  currencyPosition = 'auto',
  className = '',
}: LineItemEditorProps) {
  const [draggingId, setDraggingId] = React.useState<string | null>(null);
  const activeCurrencyPosition = currencyPosition === 'auto'
    ? detectCurrencyPosition(locale, currency)
    : currencyPosition;

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
        entryType: 'line',
        description: '',
        qty: 1,
        unit: 'EA',
        targetPrice: 0,
      },
    ]);
  }

  function addGrouping() {
    onChange([
      ...items,
      {
        id: `heading-${Date.now()}`,
        entryType: 'heading',
        heading: '',
        subheading: '',
        description: '',
        qty: 0,
        unit: 'EA',
        targetPrice: 0,
      },
    ]);
  }

  function moveItem(dragId: string, targetId: string) {
    if (dragId === targetId) return;
    const sourceIndex = items.findIndex(item => item.id === dragId);
    const targetIndex = items.findIndex(item => item.id === targetId);
    if (sourceIndex < 0 || targetIndex < 0) return;
    const next = [...items];
    const [moved] = next.splice(sourceIndex, 1);
    next.splice(targetIndex, 0, moved);
    onChange(next);
  }

  return (
    <Card padding="none" className={className}>
      <div className="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
        <p className="text-sm font-semibold text-slate-800">Line Items</p>
        <div className="flex items-center gap-2">
          <Button size="sm" variant="ghost" icon={<Rows3 size={13} />} onClick={addGrouping}>
            Add Heading
          </Button>
          <Button size="sm" variant="outline" icon={<Plus size={13} />} onClick={add}>
            Add Line
          </Button>
        </div>
      </div>
      <div className="grid grid-cols-12 gap-2 px-3 py-2 bg-slate-50 border-b border-slate-200">
        <span className="col-span-1 text-[10px] font-semibold text-slate-500 uppercase tracking-wide">Sort</span>
        <span className="col-span-4 text-[10px] font-semibold text-slate-500 uppercase tracking-wide">Description / Heading</span>
        <span className="col-span-2 text-[10px] font-semibold text-slate-500 uppercase tracking-wide">Qty</span>
        <span className="col-span-2 text-[10px] font-semibold text-slate-500 uppercase tracking-wide">Unit</span>
        <span className="col-span-2 text-[10px] font-semibold text-slate-500 uppercase tracking-wide">Target Price</span>
        <span className="col-span-1 text-[10px] font-semibold text-slate-500 uppercase tracking-wide text-right">Action</span>
      </div>
      <div className="divide-y divide-slate-100">
        {items.map(item => (
          <div
            key={item.id}
            className={['grid grid-cols-12 gap-2 p-3', draggingId === item.id ? 'bg-indigo-50/50' : ''].join(' ')}
            draggable
            onDragStart={() => setDraggingId(item.id)}
            onDragEnd={() => setDraggingId(null)}
            onDragOver={e => e.preventDefault()}
            onDrop={() => {
              if (draggingId) moveItem(draggingId, item.id);
            }}
          >
            <div className="col-span-1 flex items-center">
              <span className="inline-flex w-6 h-6 items-center justify-center rounded text-slate-400">
                <GripVertical size={14} />
              </span>
            </div>

            {item.entryType === 'heading' ? (
              <>
                <div className="col-span-4">
                  <TextInput
                    placeholder="Heading"
                    value={item.heading ?? ''}
                    onChange={e => update(item.id, { heading: e.target.value })}
                  />
                  <div className="mt-1">
                    <TextInput
                      placeholder="Subheading (optional)"
                      value={item.subheading ?? ''}
                      onChange={e => update(item.id, { subheading: e.target.value })}
                    />
                  </div>
                </div>
                <div className="col-span-6 flex items-center">
                  <span className="text-xs text-slate-500">Section heading to group related RFQ lines.</span>
                </div>
                <div className="col-span-1 flex justify-end items-start">
                  <button
                    className="h-7 px-2 text-xs text-slate-500"
                    onClick={() => remove(item.id)}
                    aria-label="Remove heading"
                  >
                    Remove
                  </button>
                </div>
              </>
            ) : (
              <>
                <div className="col-span-4">
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
                  <div className="relative">
                    {activeCurrencyPosition === 'prefix' && (
                      <span className="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-slate-500">{currency}</span>
                    )}
                    <input
                      type="number"
                      className={[
                        'w-full h-8 rounded-md border border-slate-200 bg-white text-slate-900 text-sm',
                        'focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-400',
                        activeCurrencyPosition === 'prefix' ? 'pl-11 pr-3' : 'pl-3 pr-11',
                      ].join(' ')}
                      value={item.targetPrice}
                      onChange={e => update(item.id, { targetPrice: Number(e.target.value || 0) })}
                    />
                    {activeCurrencyPosition === 'suffix' && (
                      <span className="absolute right-2.5 top-1/2 -translate-y-1/2 text-xs text-slate-500">{currency}</span>
                    )}
                  </div>
                </div>
                <div className="col-span-1 flex justify-end">
                  <button
                    className="h-7 px-2 text-xs text-slate-500"
                    onClick={() => remove(item.id)}
                    aria-label="Remove line item"
                  >
                    Remove
                  </button>
                </div>
              </>
            )}
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
