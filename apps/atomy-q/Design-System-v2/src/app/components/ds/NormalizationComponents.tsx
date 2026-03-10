import React from 'react';
import { AlertTriangle, Lock, Unlock } from 'lucide-react';
import { Button } from './Button';
import { Checkbox, SelectInput } from './Input';
import { StatusBadge } from './Badge';
import { OverrideChip } from './QuoteIntakeComponents';

export function ConversionBadge({ from, to }: { from: string; to: string }) {
  return (
    <span className="inline-flex items-center h-5 rounded-full px-2 text-xs font-medium bg-slate-100 border border-slate-200 text-slate-700">
      {from} → {to}
    </span>
  );
}

export function ConflictIndicator({ message }: { message?: string }) {
  return (
    <span className="inline-flex items-center gap-1 text-amber-700 text-xs font-medium">
      <AlertTriangle size={11} />
      {message ?? 'Conflict'}
    </span>
  );
}

interface NormalizationLockBarProps {
  locked?: boolean;
  progressLabel?: string;
  onBulkApply?: () => void;
  onLock?: () => void;
  onUnlock?: () => void;
}

export function NormalizationLockBar({
  locked = false,
  progressLabel = 'Fully Normalized: 18/24 lines',
  onBulkApply,
  onLock,
  onUnlock,
}: NormalizationLockBarProps) {
  return (
    <div className="flex items-center justify-between rounded-md border border-slate-200 bg-white px-3 py-2">
      <div className="flex items-center gap-2">
        <StatusBadge status={locked ? 'locked' : 'active'} label={locked ? 'Locked' : 'Unlocked'} />
        <span className="text-xs text-slate-500">{progressLabel}</span>
      </div>
      <div className="flex items-center gap-2">
        <Button variant="outline" size="sm" onClick={onBulkApply}>
          Bulk Apply Mapping
        </Button>
        {locked ? (
          <Button variant="ghost" size="sm" icon={<Unlock size={12} />} onClick={onUnlock}>
            Unlock
          </Button>
        ) : (
          <Button size="sm" icon={<Lock size={12} />} onClick={onLock}>
            Lock for Comparison
          </Button>
        )}
      </div>
    </div>
  );
}

export interface MappingGridRow {
  id: string;
  lineNumber: number;
  vendorDescription: string;
  confidence: 'high' | 'medium' | 'low';
  mappedLine: string;
  taxonomyCode: string;
  normalizedQty: string;
  normalizedUnit: string;
  normalizedPrice: string;
  currency: string;
  conflict?: boolean;
  overridden?: boolean;
}

interface MappingGridProps {
  rows: MappingGridRow[];
  selectedIds: string[];
  onSelectedIdsChange: (ids: string[]) => void;
}

export function MappingGrid({ rows, selectedIds, onSelectedIdsChange }: MappingGridProps) {
  function toggle(id: string) {
    onSelectedIdsChange(selectedIds.includes(id) ? selectedIds.filter(x => x !== id) : [...selectedIds, id]);
  }

  return (
    <div className="grid grid-cols-2 gap-3">
      <div className="rounded-lg border border-slate-200 bg-white overflow-hidden">
        <div className="px-3 py-2 border-b border-slate-200 text-sm font-semibold text-slate-800">Source Lines</div>
        <div className="divide-y divide-slate-100">
          {rows.map(row => (
            <div key={row.id} className="px-3 py-2 text-xs">
              <div className="flex items-center gap-2">
                <Checkbox checked={selectedIds.includes(row.id)} onChange={() => toggle(row.id)} />
                <span className="text-slate-400 w-8">#{row.lineNumber}</span>
                <span className="text-slate-700 flex-1 truncate">{row.vendorDescription}</span>
                {row.conflict && <ConflictIndicator />}
              </div>
            </div>
          ))}
        </div>
      </div>

      <div className="rounded-lg border border-slate-200 bg-white overflow-hidden">
        <div className="px-3 py-2 border-b border-slate-200 text-sm font-semibold text-slate-800">Normalized Mapping</div>
        <div className="divide-y divide-slate-100">
          {rows.map(row => (
            <div key={row.id} className="px-3 py-2">
              <div className="grid grid-cols-12 gap-2 items-center">
                <div className="col-span-4">
                  <SelectInput
                    compact
                    options={[
                      { value: row.mappedLine, label: row.mappedLine },
                      { value: 'CPU - line 1', label: 'CPU - line 1' },
                      { value: 'RAM - line 2', label: 'RAM - line 2' },
                    ]}
                    value={row.mappedLine}
                  />
                </div>
                <div className="col-span-2">
                  <span className="inline-flex px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[11px] font-mono">
                    {row.taxonomyCode}
                  </span>
                </div>
                <div className="col-span-2 text-xs text-slate-600">{row.normalizedQty}</div>
                <div className="col-span-1 text-xs text-slate-600">{row.normalizedUnit}</div>
                <div className="col-span-2 text-xs text-slate-700 font-medium">{row.normalizedPrice}</div>
                <div className="col-span-1 text-xs text-slate-500">{row.currency}</div>
              </div>
              <div className="mt-1 flex items-center gap-2">
                {row.conflict && <ConflictIndicator message="Mapping conflict" />}
                {row.overridden && <OverrideChip />}
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
