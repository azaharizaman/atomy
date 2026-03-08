import type { EvidenceItem } from '../types';
import { StatusBadge } from './StatusBadge';

export interface EvidenceTimelineProps {
  items: EvidenceItem[];
}

const typeTone: Record<EvidenceItem['type'], 'info' | 'success' | 'warning' | 'neutral'> = {
  bid_document: 'info',
  approval_note: 'success',
  risk_attachment: 'warning',
  audit_export: 'neutral',
};

export function EvidenceTimeline({ items }: EvidenceTimelineProps) {
  return (
    <section
      style={{
        border: '1px solid #e2e8f0',
        borderRadius: 12,
        background: '#ffffff',
        overflow: 'hidden',
      }}
    >
      <header style={{ padding: 16, borderBottom: '1px solid #f1f5f9', fontWeight: 700, color: '#0f172a' }}>
        Evidence Vault
      </header>
      <div style={{ display: 'grid' }}>
        {items.map((item) => (
          <div
            key={item.id}
            style={{
              display: 'grid',
              gridTemplateColumns: '1fr auto',
              gap: 10,
              padding: 14,
              borderBottom: '1px solid #f8fafc',
            }}
          >
            <div>
              <div style={{ fontSize: 14, fontWeight: 600, color: '#0f172a' }}>{item.name}</div>
              <div style={{ fontSize: 12, color: '#64748b' }}>
                {item.source} · {item.capturedAt}
              </div>
            </div>
            <StatusBadge label={item.type.replace('_', ' ')} tone={typeTone[item.type]} />
          </div>
        ))}
      </div>
    </section>
  );
}
