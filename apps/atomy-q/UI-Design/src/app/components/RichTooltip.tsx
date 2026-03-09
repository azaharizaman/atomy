import * as React from 'react';
import * as HoverCardPrimitive from '@radix-ui/react-hover-card';
import { cn } from './ui/utils';

interface RichTooltipProps {
  trigger: React.ReactNode;
  children: React.ReactNode;
  side?: 'top' | 'right' | 'bottom' | 'left';
  align?: 'start' | 'center' | 'end';
  className?: string;
  width?: string;
}

export function RichTooltip({ trigger, children, side = 'right', align = 'start', className, width = 'w-80' }: RichTooltipProps) {
  return (
    <HoverCardPrimitive.Root openDelay={300} closeDelay={150}>
      <HoverCardPrimitive.Trigger asChild>
        {trigger}
      </HoverCardPrimitive.Trigger>
      <HoverCardPrimitive.Portal>
        <HoverCardPrimitive.Content
          side={side}
          align={align}
          sideOffset={8}
          className={cn(
            width,
            'z-[100] rounded-xl border border-slate-200 bg-white p-4 shadow-xl',
            'data-[state=open]:animate-in data-[state=closed]:animate-out',
            'data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0',
            'data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95',
            'data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2',
            'data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2',
            className,
          )}
        >
          {children}
        </HoverCardPrimitive.Content>
      </HoverCardPrimitive.Portal>
    </HoverCardPrimitive.Root>
  );
}

interface TooltipBadgeProps {
  label: string;
  value: string | number;
  color?: string;
}

export function TooltipBadge({ label, value, color = 'text-slate-600' }: TooltipBadgeProps) {
  return (
    <div className="flex items-center justify-between py-1">
      <span className="text-xs text-slate-500">{label}</span>
      <span className={cn('text-xs', color)} style={{ fontWeight: 600 }}>{value}</span>
    </div>
  );
}

interface StatusDotProps {
  status: 'good' | 'warning' | 'danger' | 'neutral';
  label?: string;
}

const dotColors = {
  good: 'bg-emerald-500',
  warning: 'bg-amber-500',
  danger: 'bg-red-500',
  neutral: 'bg-slate-400',
};

export function StatusDot({ status, label }: StatusDotProps) {
  return (
    <span className="inline-flex items-center gap-1.5">
      <span className={cn('w-1.5 h-1.5 rounded-full flex-shrink-0', dotColors[status])} />
      {label && <span className="text-xs text-slate-600">{label}</span>}
    </span>
  );
}
