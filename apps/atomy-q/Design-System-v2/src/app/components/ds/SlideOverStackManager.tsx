import React from 'react';
import { X } from 'lucide-react';
import type { SlideOverWidth } from './tokens';

export interface SlideOverStackItem {
  id: string;
  title: string;
  subtitle?: string;
  width?: SlideOverWidth;
  persistent?: boolean;
  content: React.ReactNode;
  footer?: React.ReactNode;
}

interface SlideOverStackManagerProps {
  stack: SlideOverStackItem[];
  onClose: (id: string) => void;
  maxDepth?: number;
}

const WIDTH_MAP: Record<SlideOverWidth, string> = {
  sm: 'w-[30vw] min-w-80',
  md: 'w-[40vw] min-w-96',
  lg: 'w-[50vw] min-w-[480px]',
  xl: 'w-[60vw] min-w-[560px]',
};

export function SlideOverStackManager({
  stack,
  onClose,
  maxDepth = 2,
}: SlideOverStackManagerProps) {
  const visibleStack = stack.slice(-maxDepth);
  const top = visibleStack[visibleStack.length - 1];

  if (visibleStack.length === 0) return null;

  return (
    <div className="fixed inset-0 z-50">
      <div
        className="absolute inset-0 bg-slate-900/25"
        onClick={() => {
          if (!top?.persistent && top) onClose(top.id);
        }}
      />

      <div className="absolute inset-0 flex justify-end items-stretch pointer-events-none">
        {visibleStack.map((panel, idx) => {
          const isTop = idx === visibleStack.length - 1;
          const width = panel.width ?? 'md';
          return (
            <div
              key={panel.id}
              className={[
                'relative flex flex-col h-full bg-white shadow-2xl border-l border-slate-200 overflow-hidden',
                WIDTH_MAP[width],
                isTop ? 'pointer-events-auto' : 'pointer-events-none',
                isTop ? '' : 'opacity-95',
              ].join(' ')}
              style={{ zIndex: 60 + idx }}
            >
              <div className="flex items-start justify-between gap-4 px-5 py-4 border-b border-slate-200 shrink-0">
                <div className="min-w-0">
                  <h2 className="text-base font-semibold text-slate-900 leading-tight">{panel.title}</h2>
                  {panel.subtitle && <p className="text-xs text-slate-500 mt-0.5">{panel.subtitle}</p>}
                </div>
                {isTop && (
                  <button
                    onClick={() => onClose(panel.id)}
                    className="shrink-0 p-1 rounded-md text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
                    aria-label="Close panel"
                  >
                    <X size={16} />
                  </button>
                )}
              </div>

              <div className="flex-1 overflow-y-auto">{panel.content}</div>

              {isTop && panel.footer && (
                <div className="shrink-0 flex items-center justify-end gap-2 px-5 py-3 border-t border-slate-200 bg-slate-50">
                  {panel.footer}
                </div>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
}
