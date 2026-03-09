import * as React from 'react';
import * as DialogPrimitive from '@radix-ui/react-dialog';
import { X } from 'lucide-react';
import { cn } from './ui/utils';

type SlideOverWidth = 'sm' | 'md' | 'lg' | 'xl';

const widthClasses: Record<SlideOverWidth, string> = {
  sm: 'w-[30vw] min-w-[400px]',
  md: 'w-[40vw] min-w-[500px]',
  lg: 'w-[50vw] min-w-[600px]',
  xl: 'w-[60vw] min-w-[700px]',
};

interface SlideOverProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  title: string;
  description?: string;
  width?: SlideOverWidth;
  children: React.ReactNode;
  footer?: React.ReactNode;
}

export function SlideOver({ open, onOpenChange, title, description, width = 'md', children, footer }: SlideOverProps) {
  return (
    <DialogPrimitive.Root open={open} onOpenChange={onOpenChange}>
      <DialogPrimitive.Portal>
        <DialogPrimitive.Overlay className="fixed inset-0 z-50 bg-black/40 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0" />
        <DialogPrimitive.Content
          className={cn(
            'fixed inset-y-0 right-0 z-50 flex flex-col bg-white shadow-2xl',
            'data-[state=open]:animate-in data-[state=closed]:animate-out',
            'data-[state=closed]:slide-out-to-right data-[state=open]:slide-in-from-right',
            'data-[state=closed]:duration-300 data-[state=open]:duration-300',
            widthClasses[width],
          )}
        >
          <div className="flex items-start justify-between border-b border-slate-200 px-6 py-4 flex-shrink-0">
            <div>
              <DialogPrimitive.Title className="text-lg text-slate-900" style={{ fontWeight: 600 }}>
                {title}
              </DialogPrimitive.Title>
              {description && (
                <DialogPrimitive.Description className="text-sm text-slate-500 mt-0.5">
                  {description}
                </DialogPrimitive.Description>
              )}
            </div>
            <DialogPrimitive.Close className="rounded-lg p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
              <X size={18} />
            </DialogPrimitive.Close>
          </div>

          <div className="flex-1 overflow-y-auto px-6 py-4">
            {children}
          </div>

          {footer && (
            <div className="border-t border-slate-200 px-6 py-4 flex-shrink-0 bg-slate-50">
              {footer}
            </div>
          )}
        </DialogPrimitive.Content>
      </DialogPrimitive.Portal>
    </DialogPrimitive.Root>
  );
}
