import * as React from 'react';
import { AtomyQModal } from './AtomyQModal';
import { AtomyQButton } from '../basic/AtomyQButton';
import { AlertTriangle } from 'lucide-react';
import { cn } from '@/lib/utils';

export interface AtomyQConfirmDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  title: string;
  description: string;
  confirmLabel?: string;
  cancelLabel?: string;
  onConfirm: () => void;
  onCancel?: () => void;
  variant?: 'default' | 'destructive';
  loading?: boolean;
}

function AtomyQConfirmDialog({
  open,
  onOpenChange,
  title,
  description,
  confirmLabel = 'Confirm',
  cancelLabel = 'Cancel',
  onConfirm,
  onCancel,
  variant = 'default',
  loading = false,
}: AtomyQConfirmDialogProps) {
  return (
    <AtomyQModal
      open={open}
      onOpenChange={onOpenChange}
      title={title}
      size="sm"
      footer={
        <>
          <AtomyQButton
            variant="outline"
            onClick={() => {
              onCancel?.();
              onOpenChange(false);
            }}
          >
            {cancelLabel}
          </AtomyQButton>
          <AtomyQButton
            variant={variant === 'destructive' ? 'destructive' : 'primary'}
            onClick={onConfirm}
            loading={loading}
          >
            {confirmLabel}
          </AtomyQButton>
        </>
      }
    >
      <div className="flex gap-4">
        {variant === 'destructive' && (
          <div className={cn(
            'flex size-10 shrink-0 items-center justify-center rounded-full',
            'bg-[var(--aq-danger-50)] text-[var(--aq-danger-600)]'
          )}>
            <AlertTriangle className="size-5" />
          </div>
        )}
        <p className="text-sm text-[var(--aq-text-secondary)]">{description}</p>
      </div>
    </AtomyQModal>
  );
}

export { AtomyQConfirmDialog };
