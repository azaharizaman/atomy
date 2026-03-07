import * as React from 'react';
import * as AvatarPrimitive from '@radix-ui/react-avatar';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const avatarVariants = cva(
  'relative flex shrink-0 overflow-hidden rounded-full bg-[var(--aq-bg-elevated)]',
  {
    variants: {
      size: {
        sm: 'size-6 text-[10px]',
        md: 'size-8 text-xs',
        lg: 'size-10 text-sm',
        xl: 'size-12 text-base',
      },
    },
    defaultVariants: { size: 'md' },
  }
);

export interface AtomyQAvatarProps
  extends React.ComponentPropsWithoutRef<typeof AvatarPrimitive.Root>,
    VariantProps<typeof avatarVariants> {
  src?: string;
  alt?: string;
  fallback: string;
  status?: 'online' | 'offline' | 'away' | 'busy';
}

const statusColors = {
  online: 'bg-[var(--aq-success-500)]',
  offline: 'bg-[var(--aq-text-subtle)]',
  away: 'bg-[var(--aq-warning-500)]',
  busy: 'bg-[var(--aq-danger-500)]',
};

const AtomyQAvatar = React.forwardRef<
  React.ComponentRef<typeof AvatarPrimitive.Root>,
  AtomyQAvatarProps
>(({ className, size, src, alt, fallback, status, ...props }, ref) => (
  <div className="relative inline-flex">
    <AvatarPrimitive.Root
      ref={ref}
      className={cn(avatarVariants({ size }), className)}
      {...props}
    >
      {src && (
        <AvatarPrimitive.Image
          src={src}
          alt={alt || fallback}
          className="aspect-square size-full object-cover"
        />
      )}
      <AvatarPrimitive.Fallback className="flex size-full items-center justify-center bg-[var(--aq-brand-100)] text-[var(--aq-brand-700)] font-medium">
        {fallback}
      </AvatarPrimitive.Fallback>
    </AvatarPrimitive.Root>
    {status && (
      <span
        className={cn(
          'absolute bottom-0 right-0 size-2.5 rounded-full border-2 border-white',
          statusColors[status]
        )}
        aria-label={`Status: ${status}`}
      />
    )}
  </div>
));

AtomyQAvatar.displayName = 'AtomyQAvatar';

export { AtomyQAvatar };
