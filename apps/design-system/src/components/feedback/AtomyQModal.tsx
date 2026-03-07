import * as React from 'react';
import * as DialogPrimitive from '@radix-ui/react-dialog';
import { X } from 'lucide-react';
import { cn } from '@/lib/utils';

export interface AtomyQModalProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  title: string;
  description?: string;
  children: React.ReactNode;
  footer?: React.ReactNode;
  size?: 'sm' | 'md' | 'lg' | 'xl' | 'slideOver';
  className?: string;
}

const sizeClasses = {
  sm: 'max-w-md',
  md: 'max-w-lg',
  lg: 'max-w-2xl',
  xl: 'max-w-4xl',
  slideOver: 'fixed right-0 top-0 h-full max-w-md rounded-none rounded-l-xl',
};

function AtomyQModal({
  open,
  onOpenChange,
  title,
  description,
  children,
  footer,
  size = 'md',
  className,
}: AtomyQModalProps) {
  const isSlideOver = size === 'slideOver';

  return (
    <DialogPrimitive.Root open={open} onOpenChange={onOpenChange}>
      <DialogPrimitive.Portal>
        <DialogPrimitive.Overlay className="fixed inset-0 z-50 bg-[var(--aq-overlay)] data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0" />
        <DialogPrimitive.Content
          className={cn(
            'fixed z-50 bg-white shadow-xl outline-none',
            isSlideOver
              ? 'right-0 top-0 h-full w-full max-w-md rounded-l-xl data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:slide-out-to-right data-[state=open]:slide-in-from-right'
              : 'left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-full rounded-xl data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95',
            !isSlideOver && sizeClasses[size],
            className
          )}
        >
          {/* Header */}
          <div className="flex items-center justify-between border-b border-[var(--aq-border-default)] px-6 py-4">
            <div>
              <DialogPrimitive.Title className="text-base font-semibold text-[var(--aq-text-primary)]">
                {title}
              </DialogPrimitive.Title>
              {description && (
                <DialogPrimitive.Description className="text-sm text-[var(--aq-text-muted)] mt-0.5">
                  {description}
                </DialogPrimitive.Description>
              )}
            </div>
            <DialogPrimitive.Close className="rounded-md p-1.5 text-[var(--aq-text-muted)] hover:text-[var(--aq-text-primary)] hover:bg-[var(--aq-bg-elevated)] transition-colors outline-none focus-visible:ring-2 focus-visible:ring-[var(--aq-brand-500)]">
              <X className="size-4" />
              <span className="sr-only">Close</span>
            </DialogPrimitive.Close>
          </div>

          {/* Body */}
          <div className={cn('px-6 py-4', isSlideOver && 'flex-1 overflow-y-auto')}>
            {children}
          </div>

          {/* Footer */}
          {footer && (
            <div className="flex items-center justify-end gap-2 border-t border-[var(--aq-border-default)] px-6 py-4">
              {footer}
            </div>
          )}
        </DialogPrimitive.Content>
      </DialogPrimitive.Portal>
    </DialogPrimitive.Root>
  );
}

export { AtomyQModal };
