import type { Tone } from '../types';

const toneMap: Record<Tone, { bg: string; fg: string; border: string }> = {
  neutral: { bg: '#f8fafc', fg: '#334155', border: '#cbd5e1' },
  info: { bg: '#eef2ff', fg: '#4338ca', border: '#c7d2fe' },
  success: { bg: '#ecfdf5', fg: '#047857', border: '#a7f3d0' },
  warning: { bg: '#fffbeb', fg: '#b45309', border: '#fcd34d' },
  danger: { bg: '#fef2f2', fg: '#b91c1c', border: '#fecaca' },
};

export interface StatusBadgeProps {
  label: string;
  tone?: Tone;
  onClick?: () => void;
}

export function StatusBadge({ label, tone = 'neutral', onClick }: StatusBadgeProps) {
  const colors = toneMap[tone];

  return (
    <button
      type="button"
      onClick={onClick}
      style={{
        backgroundColor: colors.bg,
        color: colors.fg,
        border: `1px solid ${colors.border}`,
        borderRadius: 999,
        padding: '4px 10px',
        fontSize: 12,
        fontWeight: 600,
        cursor: onClick ? 'pointer' : 'default',
      }}
    >
      {label}
    </button>
  );
}
