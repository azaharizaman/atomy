import { StatusBadge } from './StatusBadge';
import type { KpiDatum } from '../types';

export interface KpiCardProps extends KpiDatum {}

export function KpiCard({ label, value, delta, tone }: KpiCardProps) {
  return (
    <article
      style={{
        border: '1px solid #e2e8f0',
        borderRadius: 12,
        padding: 16,
        background: '#ffffff',
        display: 'grid',
        gap: 10,
      }}
    >
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <span style={{ fontSize: 13, color: '#475569', fontWeight: 600 }}>{label}</span>
        <StatusBadge label={tone.toUpperCase()} tone={tone} />
      </div>
      <div style={{ fontSize: 30, lineHeight: 1.1, fontWeight: 700, color: '#0f172a' }}>{value}</div>
      <div style={{ fontSize: 12, color: '#64748b' }}>{delta}</div>
    </article>
  );
}
