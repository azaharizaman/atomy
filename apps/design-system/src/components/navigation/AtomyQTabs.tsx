import * as React from 'react';
import * as TabsPrimitive from '@radix-ui/react-tabs';
import { cn } from '@/lib/utils';

export interface AtomyQTabItem {
  value: string;
  label: string;
  badge?: string;
  disabled?: boolean;
}

export interface AtomyQTabsProps extends React.ComponentPropsWithoutRef<typeof TabsPrimitive.Root> {
  items: AtomyQTabItem[];
  variant?: 'default' | 'pills' | 'underline';
}

function AtomyQTabs({ items, variant = 'default', children, className, ...props }: AtomyQTabsProps) {
  const listStyles = {
    default: 'bg-[var(--aq-bg-elevated)] rounded-lg p-1 gap-1',
    pills: 'gap-1',
    underline: 'border-b border-[var(--aq-border-default)] gap-0',
  };

  const triggerStyles = {
    default: 'rounded-md px-3 py-1.5 text-sm data-[state=active]:bg-white data-[state=active]:shadow-sm',
    pills: 'rounded-full px-4 py-1.5 text-sm data-[state=active]:bg-[var(--aq-brand-600)] data-[state=active]:text-white',
    underline: 'px-4 py-2 text-sm border-b-2 border-transparent -mb-px data-[state=active]:border-[var(--aq-brand-600)] data-[state=active]:text-[var(--aq-brand-600)] rounded-none',
  };

  return (
    <TabsPrimitive.Root className={cn('w-full', className)} {...props}>
      <TabsPrimitive.List className={cn('inline-flex items-center', listStyles[variant])}>
        {items.map((item) => (
          <TabsPrimitive.Trigger
            key={item.value}
            value={item.value}
            disabled={item.disabled}
            className={cn(
              'inline-flex items-center justify-center gap-1.5 whitespace-nowrap font-medium transition-all outline-none',
              'text-[var(--aq-text-muted)] data-[state=active]:text-[var(--aq-text-primary)]',
              'focus-visible:ring-2 focus-visible:ring-[var(--aq-brand-500)]/20',
              'disabled:pointer-events-none disabled:opacity-50',
              triggerStyles[variant]
            )}
          >
            {item.label}
            {item.badge && (
              <span className="rounded-full bg-[var(--aq-bg-elevated)] text-[var(--aq-text-muted)] text-[10px] font-medium px-1.5 min-w-[18px] text-center">
                {item.badge}
              </span>
            )}
          </TabsPrimitive.Trigger>
        ))}
      </TabsPrimitive.List>
      {children}
    </TabsPrimitive.Root>
  );
}

const AtomyQTabContent = React.forwardRef<
  React.ComponentRef<typeof TabsPrimitive.Content>,
  React.ComponentPropsWithoutRef<typeof TabsPrimitive.Content>
>(({ className, ...props }, ref) => (
  <TabsPrimitive.Content
    ref={ref}
    className={cn('mt-3 outline-none focus-visible:ring-2 focus-visible:ring-[var(--aq-brand-500)]/20 rounded-md', className)}
    {...props}
  />
));

AtomyQTabContent.displayName = 'AtomyQTabContent';

export { AtomyQTabs, AtomyQTabContent };
