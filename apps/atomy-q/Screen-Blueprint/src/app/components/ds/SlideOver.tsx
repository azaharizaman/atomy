import React from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { X } from 'lucide-react';
import type { SlideOverWidth } from './tokens';

// ─── SlideOver ────────────────────────────────────────────────────────────────

const WIDTH_MAP: Record<SlideOverWidth, string> = {
  sm: 'w-[30vw] min-w-80',
  md: 'w-[40vw] min-w-96',
  lg: 'w-[50vw] min-w-[480px]',
  xl: 'w-[60vw] min-w-[560px]',
};

interface SlideOverProps {
  open: boolean;
  onClose: () => void;
  width?: SlideOverWidth;
  title: string;
  subtitle?: string;
  children: React.ReactNode;
  footer?: React.ReactNode;
  /** Prevent closing via backdrop click */
  persistent?: boolean;
}

export function SlideOver({
  open, onClose, width = 'md', title, subtitle, children, footer, persistent = false,
}: SlideOverProps) {
  // Trap focus + ESC key
  React.useEffect(() => {
    if (!open) return;
    const handler = (e: KeyboardEvent) => { if (e.key === 'Escape') onClose(); };
    document.addEventListener('keydown', handler);
    return () => document.removeEventListener('keydown', handler);
  }, [open, onClose]);

  return (
    <AnimatePresence>
      {open && (
        <div className="fixed inset-0 z-50 flex items-stretch justify-end" role="dialog" aria-modal>
          {/* Backdrop */}
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.2 }}
            className="absolute inset-0 bg-slate-900/30 backdrop-blur-[1px]"
            onClick={persistent ? undefined : onClose}
          />

          {/* Panel */}
          <motion.div
            initial={{ x: '100%' }}
            animate={{ x: 0 }}
            exit={{ x: '100%' }}
            transition={{ type: 'spring', stiffness: 340, damping: 34 }}
            className={[
              'relative flex flex-col bg-white shadow-2xl h-full overflow-hidden',
              WIDTH_MAP[width],
            ].join(' ')}
          >
            {/* Header */}
            <div className="flex items-start justify-between gap-4 px-5 py-4 border-b border-slate-200 shrink-0">
              <div className="min-w-0">
                <h2 className="text-base font-semibold text-slate-900 leading-tight">{title}</h2>
                {subtitle && <p className="text-xs text-slate-500 mt-0.5">{subtitle}</p>}
              </div>
              <button
                onClick={onClose}
                className="shrink-0 p-1 rounded-md text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500"
                aria-label="Close"
              >
                <X size={16} />
              </button>
            </div>

            {/* Scrollable body */}
            <div className="flex-1 overflow-y-auto">
              {children}
            </div>

            {/* Sticky footer */}
            {footer && (
              <div className="shrink-0 flex items-center justify-end gap-2 px-5 py-3 border-t border-slate-200 bg-slate-50">
                {footer}
              </div>
            )}
          </motion.div>
        </div>
      )}
    </AnimatePresence>
  );
}

// ─── SlideOver Section ────────────────────────────────────────────────────────

interface SlideOverSectionProps {
  title?: string;
  children: React.ReactNode;
  className?: string;
  noPad?: boolean;
}

export function SlideOverSection({ title, children, className = '', noPad = false }: SlideOverSectionProps) {
  return (
    <div className={['border-b border-slate-100 last:border-b-0', noPad ? '' : 'px-5 py-4', className].join(' ')}>
      {title && <h3 className="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">{title}</h3>}
      {children}
    </div>
  );
}
