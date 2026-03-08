import {
  EvidenceTimeline,
  GovernanceControlPanel,
  KpiCard,
  evidenceVault,
  governanceControlSnapshot,
  procurementKpis,
} from './index';

function App() {
  return (
    <div
      style={{
        maxWidth: 1080,
        margin: '0 auto',
        padding: 24,
        display: 'grid',
        gap: 16,
        fontFamily: 'Inter, system-ui, sans-serif',
      }}
    >
      <h1 style={{ margin: 0, color: '#0f172a' }}>Atomy-Q Design System</h1>
      <p style={{ margin: 0, color: '#64748b' }}>
        Reusable procurement controls and evidence-first components.
      </p>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, minmax(0, 1fr))', gap: 12 }}>
        {procurementKpis.map((kpi) => (
          <KpiCard key={kpi.label} {...kpi} />
        ))}
      </div>
      <GovernanceControlPanel snapshot={governanceControlSnapshot} />
      <EvidenceTimeline items={evidenceVault} />
    </div>
  );
}

export default App;
