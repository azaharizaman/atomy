import React from 'react';
import { cva, type VariantProps } from 'class-variance-authority';
import { X } from 'lucide-react';

import { cn } from '../ui/utils';
import type { TagVariant } from './tokens';

const tagVariants = cva(
  'inline-flex max-w-full items-center gap-1 rounded-full border font-medium whitespace-nowrap shrink-0 [&>svg]:shrink-0',
  {
    variants: {
      variant: {
        neutral: '',
        success: '',
        warning: '',
        danger: '',
        info: '',
        accent: '',
      },
      appearance: {
        solid: '',
        outline: 'bg-transparent',
      },
      size: {
        sm: 'h-5 px-2 text-xs [&>svg]:size-3',
        xs: 'h-4 px-1.5 text-[10px] gap-0.5 [&>svg]:size-2.5',
      },
    },
    compoundVariants: [
      { variant: 'neutral', appearance: 'solid', class: 'border-slate-200 bg-slate-100 text-slate-700' },
      { variant: 'neutral', appearance: 'outline', class: 'border-slate-300 text-slate-700' },
      { variant: 'success', appearance: 'solid', class: 'border-green-200 bg-green-100 text-green-700' },
      { variant: 'success', appearance: 'outline', class: 'border-green-300 text-green-700' },
      { variant: 'warning', appearance: 'solid', class: 'border-amber-200 bg-amber-100 text-amber-700' },
      { variant: 'warning', appearance: 'outline', class: 'border-amber-300 text-amber-700' },
      { variant: 'danger', appearance: 'solid', class: 'border-red-200 bg-red-100 text-red-700' },
      { variant: 'danger', appearance: 'outline', class: 'border-red-300 text-red-700' },
      { variant: 'info', appearance: 'solid', class: 'border-blue-200 bg-blue-100 text-blue-700' },
      { variant: 'info', appearance: 'outline', class: 'border-blue-300 text-blue-700' },
      { variant: 'accent', appearance: 'solid', class: 'border-indigo-200 bg-indigo-50 text-indigo-700' },
      { variant: 'accent', appearance: 'outline', class: 'border-indigo-300 text-indigo-700' },
    ],
    defaultVariants: {
      variant: 'neutral',
      appearance: 'solid',
      size: 'sm',
    },
  },
);

const detailVariants = cva('tabular-nums border-l pl-1.5 ml-0.5 opacity-90', {
  variants: {
    variant: {
      neutral: 'border-slate-300/80',
      success: 'border-green-300/80',
      warning: 'border-amber-300/80',
      danger: 'border-red-300/80',
      info: 'border-blue-300/80',
      accent: 'border-indigo-300/80',
    },
  },
  defaultVariants: { variant: 'neutral' },
});

export interface TagProps
  extends React.HTMLAttributes<HTMLSpanElement>,
    VariantProps<typeof tagVariants> {
  variant?: TagVariant;
  icon?: React.ReactNode;
  detail?: React.ReactNode;
  onRemove?: () => void;
  removeLabel?: string;
}

export function Tag({
  className,
  variant = 'neutral',
  appearance = 'solid',
  size = 'sm',
  icon,
  detail,
  onRemove,
  removeLabel = 'Remove',
  children,
  ...props
}: TagProps) {
  return (
    <span
      className={cn(tagVariants({ variant, appearance, size }), className)}
      {...props}
    >
      {icon}
      <span className="min-w-0 truncate">{children}</span>
      {detail !== undefined && detail !== null && (
        <span className={cn(detailVariants({ variant }))}>{detail}</span>
      )}
      {onRemove && (
        <button
          type="button"
          onClick={(e) => {
            e.stopPropagation();
            onRemove();
          }}
          className={cn(
            '-mr-0.5 inline-flex rounded-full p-0.5 hover:bg-black/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-1',
            size === 'xs' && 'p-px',
          )}
          aria-label={removeLabel}
        >
          <X className={size === 'xs' ? 'size-2.5' : 'size-3'} strokeWidth={2.5} />
        </button>
      )}
    </span>
  );
}

// ─── Corner label (parent must be position: relative; use overflow-hidden on rounded parents) ─

export interface CornerLabelProps {
  children: React.ReactNode;
  position?: 'top-right' | 'top-left';
  className?: string;
}

export function CornerLabel({ children, position = 'top-right', className = '' }: CornerLabelProps) {
  return (
    <div
      className={cn(
        'pointer-events-none absolute z-10',
        position === 'top-right' && 'top-0 right-0',
        position === 'top-left' && 'top-0 left-0',
        className,
      )}
    >
      <div className="pointer-events-auto">{children}</div>
    </div>
  );
}
