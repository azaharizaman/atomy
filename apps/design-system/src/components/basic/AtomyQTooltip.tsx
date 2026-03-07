import * as React from 'react';
import * as TooltipPrimitive from '@radix-ui/react-tooltip';
import { cn } from '@/lib/utils';

const AtomyQTooltipProvider = TooltipPrimitive.Provider;
const AtomyQTooltipRoot = TooltipPrimitive.Root;
const AtomyQTooltipTrigger = TooltipPrimitive.Trigger;

const AtomyQTooltipContent = React.forwardRef<
  React.ComponentRef<typeof TooltipPrimitive.Content>,
  React.ComponentPropsWithoutRef<typeof TooltipPrimitive.Content>
>(({ className, sideOffset = 4, ...props }, ref) => (
  <TooltipPrimitive.Portal>
    <TooltipPrimitive.Content
      ref={ref}
      sideOffset={sideOffset}
      className={cn(
        'z-50 overflow-hidden rounded-md bg-[var(--aq-text-primary)] px-3 py-1.5 text-xs text-white shadow-md animate-in fade-in-0 zoom-in-95',
        className
      )}
      {...props}
    />
  </TooltipPrimitive.Portal>
));

AtomyQTooltipContent.displayName = 'AtomyQTooltipContent';

interface AtomyQTooltipProps {
  content: React.ReactNode;
  children: React.ReactNode;
  side?: 'top' | 'right' | 'bottom' | 'left';
  delayDuration?: number;
}

function AtomyQTooltip({ content, children, side = 'top', delayDuration = 300 }: AtomyQTooltipProps) {
  return (
    <AtomyQTooltipProvider delayDuration={delayDuration}>
      <AtomyQTooltipRoot>
        <AtomyQTooltipTrigger asChild>{children}</AtomyQTooltipTrigger>
        <AtomyQTooltipContent side={side}>{content}</AtomyQTooltipContent>
      </AtomyQTooltipRoot>
    </AtomyQTooltipProvider>
  );
}

export {
  AtomyQTooltip,
  AtomyQTooltipProvider,
  AtomyQTooltipRoot,
  AtomyQTooltipTrigger,
  AtomyQTooltipContent,
};
