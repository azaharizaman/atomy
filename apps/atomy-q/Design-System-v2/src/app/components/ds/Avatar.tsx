import React from 'react';
import type { AvatarSize } from './tokens';

interface AvatarProps {
  src?: string;
  name: string;
  size?: AvatarSize;
  className?: string;
  ring?: boolean;
}

const SIZE_MAP: Record<AvatarSize, { container: string; text: string }> = {
  xs: { container: 'w-5 h-5',   text: 'text-[9px]'  },
  sm: { container: 'w-6 h-6',   text: 'text-[10px]' },
  md: { container: 'w-7 h-7',   text: 'text-xs'     },
  lg: { container: 'w-8 h-8',   text: 'text-sm'     },
  xl: { container: 'w-10 h-10', text: 'text-sm'     },
};

function getInitials(name: string): string {
  const parts = name.trim().split(/\s+/);
  if (parts.length === 1) return parts[0][0]?.toUpperCase() ?? '?';
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

/** Deterministic background color from name */
const PALETTE = [
  'bg-indigo-500', 'bg-blue-500', 'bg-violet-500', 'bg-teal-500',
  'bg-emerald-500', 'bg-orange-500', 'bg-rose-500', 'bg-sky-500',
];

function getColorClass(name: string): string {
  let hash = 0;
  for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
  return PALETTE[Math.abs(hash) % PALETTE.length];
}

export function Avatar({ src, name, size = 'md', className = '', ring = false }: AvatarProps) {
  const { container, text } = SIZE_MAP[size];
  const [imgError, setImgError] = React.useState(false);

  const base = [
    'inline-flex items-center justify-center rounded-full overflow-hidden flex-shrink-0 select-none',
    container,
    ring ? 'ring-2 ring-white' : '',
    className,
  ].join(' ');

  if (src && !imgError) {
    return (
      <span className={base}>
        <img
          src={src}
          alt={name}
          className="w-full h-full object-cover"
          onError={() => setImgError(true)}
        />
      </span>
    );
  }

  return (
    <span className={[base, getColorClass(name), 'text-white font-medium'].join(' ')}>
      <span className={text}>{getInitials(name)}</span>
    </span>
  );
}

/** Avatar with name label beside it */
interface AvatarLabelProps {
  src?: string;
  name: string;
  subtitle?: string;
  size?: AvatarSize;
  className?: string;
}

export function AvatarLabel({ src, name, subtitle, size = 'md', className = '' }: AvatarLabelProps) {
  return (
    <div className={['flex items-center gap-2', className].join(' ')}>
      <Avatar src={src} name={name} size={size} />
      <div className="min-w-0">
        <div className="text-sm font-medium text-slate-800 truncate leading-tight">{name}</div>
        {subtitle && <div className="text-xs text-slate-500 truncate leading-tight">{subtitle}</div>}
      </div>
    </div>
  );
}

/** Avatar stack (overlapping) */
interface AvatarStackProps {
  names: string[];
  size?: AvatarSize;
  max?: number;
  className?: string;
}

export function AvatarStack({ names, size = 'sm', max = 4, className = '' }: AvatarStackProps) {
  const visible = names.slice(0, max);
  const overflow = names.length - max;

  return (
    <div className={['flex -space-x-1', className].join(' ')}>
      {visible.map((n, i) => (
        <Avatar key={i} name={n} size={size} ring />
      ))}
      {overflow > 0 && (
        <span
          className={[
            'inline-flex items-center justify-center rounded-full bg-slate-200 text-slate-600 font-medium ring-2 ring-white',
            SIZE_MAP[size].container,
            SIZE_MAP[size].text,
          ].join(' ')}
        >
          +{overflow}
        </span>
      )}
    </div>
  );
}
