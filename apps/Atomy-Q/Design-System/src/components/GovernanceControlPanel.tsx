import type { GovernanceControlSnapshot } from '../types';
import { StatusBadge } from './StatusBadge';

export interface GovernanceControlPanelProps {
  snapshot: GovernanceControlSnapshot;
  onApprove?: (exceptionId: string | null) => void;
  onRequestWaiver?: () => void;
}

function humanize(value: string): string {
  return value.replaceAll('_', ' ');
}

export function GovernanceControlPanel({
  snapshot,
  onApprove,
  onRequestWaiver,
}: GovernanceControlPanelProps) {
  return (
    <section
      style={{
        border: '1px solid #e2e8f0',
        borderRadius: 12,
        padding: 16,
        background: '#ffffff',
        display: 'grid',
        gap: 12,
      }}
    >
      <header style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
        <h3 style={{ margin: 0, fontSize: 16, color: '#0f172a' }}>Governance Controls</h3>
        <StatusBadge
          label={`${snapshot.fraudSignalCount} fraud signals`}
          tone={snapshot.fraudSignalSeverity === 'critical' || snapshot.fraudSignalSeverity === 'high' ? 'warning' : 'info'}
        />
      </header>

      <dl style={{ margin: 0, display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 10 }}>
        <div>
          <dt style={{ fontSize: 12, color: '#64748b' }}>Evaluation method</dt>
          <dd style={{ margin: 0, fontWeight: 600 }}>{humanize(snapshot.evaluationMethod)}</dd>
        </div>
        <div>
          <dt style={{ fontSize: 12, color: '#64748b' }}>Committee mode</dt>
          <dd style={{ margin: 0, fontWeight: 600 }}>{humanize(snapshot.committeeMode)}</dd>
        </div>
        <div>
          <dt style={{ fontSize: 12, color: '#64748b' }}>Technical gate</dt>
          <dd style={{ margin: 0, fontWeight: 600 }}>{humanize(snapshot.technicalGate)}</dd>
        </div>
        <div>
          <dt style={{ fontSize: 12, color: '#64748b' }}>Due diligence</dt>
          <dd style={{ margin: 0, fontWeight: 600 }}>{humanize(snapshot.dueDiligenceStatus)}</dd>
        </div>
        <div>
          <dt style={{ fontSize: 12, color: '#64748b' }}>Exception ID</dt>
          <dd style={{ margin: 0, fontWeight: 600 }}>{snapshot.exceptionId ?? 'None'}</dd>
        </div>
        <div>
          <dt style={{ fontSize: 12, color: '#64748b' }}>Approver role</dt>
          <dd style={{ margin: 0, fontWeight: 600 }}>{snapshot.approverRole}</dd>
        </div>
      </dl>

      <p style={{ margin: 0, color: '#334155', fontSize: 13 }}>{snapshot.awardRationaleSummary}</p>

      <div style={{ display: 'flex', gap: 8 }}>
        <button
          type="button"
          onClick={() => onRequestWaiver?.()}
          style={{
            borderRadius: 8,
            border: '1px solid #cbd5e1',
            background: '#fff',
            padding: '8px 12px',
            color: '#334155',
            cursor: 'pointer',
          }}
        >
          Request waiver
        </button>
        <button
          type="button"
          onClick={() => onApprove?.(snapshot.exceptionId)}
          style={{
            borderRadius: 8,
            border: '1px solid #4338ca',
            background: '#4f46e5',
            padding: '8px 12px',
            color: '#fff',
            cursor: 'pointer',
          }}
        >
          Approve with controls
        </button>
      </div>
    </section>
  );
}
