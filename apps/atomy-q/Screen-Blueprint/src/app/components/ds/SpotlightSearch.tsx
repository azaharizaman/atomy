import React, { useEffect, useRef, useCallback } from 'react';
import { Search, X, CornerDownLeft, ChevronUp, ChevronDown } from 'lucide-react';
import { FileText, Users, User, FolderArchive } from 'lucide-react';

// ─── Types ────────────────────────────────────────────────────────────────────

export type SpotlightResultType = 'rfq' | 'vendor' | 'person' | 'document';

export interface SpotlightResult {
  id: string;
  type: SpotlightResultType;
  label: string;
  path: string;
  description?: string;
  metrics?: Array<{ label: string; value: React.ReactNode }>;
}

interface SpotlightSearchProps {
  open: boolean;
  onClose: () => void;
  value: string;
  onChange: (value: string) => void;
  results: SpotlightResult[];
  selectedIndex: number;
  onSelectedIndexChange: (index: number) => void;
  onSelect: (result: SpotlightResult) => void;
  placeholder?: string;
  className?: string;
}

const TYPE_ICONS: Record<SpotlightResultType, React.ReactNode> = {
  rfq:      <FileText size={18} className="text-slate-500" />,
  vendor:   <Users size={18} className="text-slate-500" />,
  person:   <User size={18} className="text-slate-500" />,
  document: <FolderArchive size={18} className="text-slate-500" />,
};

// ─── Spotlight Search ──────────────────────────────────────────────────────────

export function SpotlightSearch({
  open,
  onClose,
  value,
  onChange,
  results,
  selectedIndex,
  onSelectedIndexChange,
  onSelect,
  placeholder = 'Search RFQs, vendors, documents…',
  className = '',
}: SpotlightSearchProps) {
  const inputRef = useRef<HTMLInputElement>(null);
  const listRef = useRef<HTMLDivElement>(null);
  const safeIndex = Math.max(0, Math.min(selectedIndex, results.length - 1));
  const selected = results[safeIndex];

  useEffect(() => {
    if (open) {
      inputRef.current?.focus();
    }
  }, [open]);

  const handleKeyDown = useCallback((e: React.KeyboardEvent) => {
    if (e.key === 'Escape') {
      e.preventDefault();
      onClose();
      return;
    }
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      onSelectedIndexChange(Math.min(selectedIndex + 1, results.length - 1));
      return;
    }
    if (e.key === 'ArrowUp') {
      e.preventDefault();
      onSelectedIndexChange(Math.max(selectedIndex - 1, 0));
      return;
    }
    if (e.key === 'Enter' && selected) {
      e.preventDefault();
      onSelect(selected);
      onClose();
      return;
    }
  }, [selectedIndex, results.length, selected, onSelect, onClose, onSelectedIndexChange]);

  useEffect(() => {
    if (!open) return;
    const el = listRef.current;
    if (!el) return;
    const item = el.querySelector(`[data-index="${safeIndex}"]`);
    item?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
  }, [open, safeIndex]);

  if (!open) return null;

  return (
    <>
      <div
        className="fixed inset-0 z-50 bg-slate-900/30 backdrop-blur-sm"
        onClick={onClose}
        aria-hidden
      />
      <div
        role="dialog"
        aria-label="Spotlight search"
        className={[
          'fixed left-1/2 top-[15%] z-50 w-full max-w-2xl -translate-x-1/2',
          'rounded-xl border border-slate-200 bg-white shadow-xl',
          'flex flex-col overflow-hidden',
          className,
        ].join(' ')}
        onKeyDown={handleKeyDown}
      >
        <div className="flex items-center gap-2 border-b border-slate-100 px-4 py-3">
          <Search size={18} className="shrink-0 text-slate-400" />
          <input
            ref={inputRef}
            type="search"
            value={value}
            onChange={e => onChange(e.target.value)}
            onKeyDown={handleKeyDown}
            placeholder={placeholder}
            className="flex-1 bg-transparent text-sm text-slate-900 placeholder:text-slate-400 outline-none"
            autoComplete="off"
          />
          <button
            type="button"
            onClick={onClose}
            className="shrink-0 rounded p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors"
            aria-label="Close"
          >
            <X size={16} />
          </button>
        </div>

        <div className="flex min-h-0 flex-1" style={{ minHeight: 280, maxHeight: 400 }}>
          <div
            ref={listRef}
            className="w-1/2 flex flex-col overflow-y-auto border-r border-slate-100"
          >
            {results.length === 0 ? (
              <div className="flex items-center justify-center py-12 text-sm text-slate-500">
                {value.trim() ? 'No results found' : 'Type to search…'}
              </div>
            ) : (
              results.map((result, i) => (
                <button
                  key={result.id}
                  type="button"
                  data-index={i}
                  onClick={() => {
                    onSelect(result);
                    onClose();
                  }}
                  onMouseEnter={() => onSelectedIndexChange(i)}
                  className={[
                    'flex items-center gap-3 px-4 py-2.5 text-left transition-colors',
                    i === safeIndex ? 'bg-indigo-50 text-slate-900' : 'text-slate-700 hover:bg-slate-50',
                  ].join(' ')}
                >
                  <span className="shrink-0 flex items-center justify-center w-8 h-8 rounded-lg bg-slate-100">
                    {TYPE_ICONS[result.type]}
                  </span>
                  <span className="flex-1 truncate text-sm font-medium">{result.label}</span>
                  {i === safeIndex && (
                    <span className="shrink-0 text-indigo-600">
                      <CornerDownLeft size={14} className="rotate-180" />
                    </span>
                  )}
                </button>
              ))
            )}
          </div>

          <div className="w-1/2 flex flex-col overflow-y-auto bg-slate-50/50 p-4">
            {selected ? (
              <>
                <div className="flex items-start gap-3 mb-3">
                  <span className="shrink-0 flex items-center justify-center w-10 h-10 rounded-lg bg-white border border-slate-200">
                    {TYPE_ICONS[selected.type]}
                  </span>
                  <div className="min-w-0 flex-1">
                    <h3 className="text-base font-semibold text-slate-900 truncate">{selected.label}</h3>
                    {selected.description && (
                      <p className="text-xs text-slate-500 mt-0.5 line-clamp-2">{selected.description}</p>
                    )}
                  </div>
                </div>
                {selected.metrics && selected.metrics.length > 0 && (
                  <div className="space-y-2">
                    {selected.metrics.map((m, i) => (
                      <div key={i} className="flex items-center justify-between gap-2 text-xs">
                        <span className="text-slate-500">{m.label}</span>
                        <span className="font-medium text-slate-700">{m.value}</span>
                      </div>
                    ))}
                  </div>
                )}
              </>
            ) : (
              <div className="flex flex-col items-center justify-center py-8 text-slate-400 text-sm">
                <CornerDownLeft size={24} className="mb-2 opacity-50" />
                <span>Select a result to view details</span>
              </div>
            )}
          </div>
        </div>

        <div className="flex items-center gap-4 border-t border-slate-100 px-4 py-2 text-[11px] text-slate-400">
          <span className="flex items-center gap-1">
            <kbd className="inline-flex items-center justify-center w-5 h-5 rounded border border-slate-200 bg-white font-mono">
              <CornerDownLeft size={10} />
            </kbd>
            to select
          </span>
          <span className="flex items-center gap-1">
            <kbd className="inline-flex items-center justify-center w-5 h-5 rounded border border-slate-200 bg-white font-mono">
              <ChevronUp size={10} />
            </kbd>
            <kbd className="inline-flex items-center justify-center w-5 h-5 rounded border border-slate-200 bg-white font-mono">
              <ChevronDown size={10} />
            </kbd>
            to navigate
          </span>
          <span className="flex items-center gap-1">
            <kbd className="px-1.5 py-0.5 rounded border border-slate-200 bg-white font-mono">esc</kbd>
            to close
          </span>
        </div>
      </div>
    </>
  );
}
