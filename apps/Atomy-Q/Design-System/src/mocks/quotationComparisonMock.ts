import type {
  ApprovalQueueItem,
  DecisionTrailEvent,
  EvidenceItem,
  GovernanceControlSnapshot,
  KpiDatum,
} from '../types';

export const procurementKpis: KpiDatum[] = [
  { label: 'Open RFQs', value: '27', delta: '+4 this week', tone: 'info' },
  { label: 'Quotes Parsed', value: '214', delta: '92% auto-resolved', tone: 'success' },
  { label: 'Compliance Alerts', value: '6', delta: '2 critical', tone: 'warning' },
  { label: 'Expected Savings', value: '$1.46M', delta: '+11.2% vs target', tone: 'success' },
];

export const governanceControlSnapshot: GovernanceControlSnapshot = {
  evaluationMethod: 'best_value',
  committeeMode: 'quorum',
  technicalGate: 'enabled',
  dueDiligenceStatus: 'conditional_pass',
  fraudSignalCount: 3,
  fraudSignalSeverity: 'high',
  exceptionId: 'EXC-2026-019',
  expiryDate: '2026-04-30',
  approverRole: 'Chief Procurement Officer',
  awardRationaleSummary:
    'PrimeSource leads on landed cost and delivery certainty; conditional approval requires sanctions re-check at award issue.',
  evidenceBundleLink: '/evidence/rfq-2401-bundle',
};

export const approvalQueue: ApprovalQueueItem[] = [
  {
    id: 'AQ-1411',
    rfqId: 'RFQ-2401',
    title: 'Server Infrastructure Components',
    owner: 'Alex Kumar',
    requiredBy: '2026-03-10 16:00 UTC',
    dueDiligenceStatus: 'conditional_pass',
    fraudSignalSeverity: 'high',
  },
  {
    id: 'AQ-1412',
    rfqId: 'RFQ-2408',
    title: 'Logistics Services FY26',
    owner: 'Priya Sharma',
    requiredBy: '2026-03-11 10:00 UTC',
    dueDiligenceStatus: 'pass',
    fraudSignalSeverity: 'medium',
  },
  {
    id: 'AQ-1413',
    rfqId: 'RFQ-2399',
    title: 'Facilities Consumables Q2',
    owner: 'Sarah Chen',
    requiredBy: '2026-03-09 18:00 UTC',
    dueDiligenceStatus: 'pending',
    fraudSignalSeverity: 'low',
  },
];

export const decisionTrail: DecisionTrailEvent[] = [
  {
    id: 'DT-9901',
    happenedAt: '2026-03-08 09:10 UTC',
    actor: 'System',
    action: 'Fraud signal generated',
    details: 'Detected unusual last-minute pricing revision pattern for vendor GlobalSupply Inc.',
  },
  {
    id: 'DT-9902',
    happenedAt: '2026-03-08 10:40 UTC',
    actor: 'Nina Patel (Compliance)',
    action: 'Exception requested',
    details: 'Requested temporary waiver EXC-2026-019 pending sanctions re-check.',
  },
  {
    id: 'DT-9903',
    happenedAt: '2026-03-08 11:15 UTC',
    actor: 'Marcus Williams (Finance Approver)',
    action: 'Conditional approval',
    details: 'Approved with mandatory evidence bundle lock and expiry date enforcement.',
  },
];

export const evidenceVault: EvidenceItem[] = [
  {
    id: 'EV-4501',
    name: 'PrimeSource_Round3_Submission.pdf',
    source: 'Supplier Portal',
    capturedAt: '2026-03-07 17:31 UTC',
    type: 'bid_document',
  },
  {
    id: 'EV-4502',
    name: 'Sanctions_Recheck_Result.json',
    source: 'Risk Engine',
    capturedAt: '2026-03-08 08:41 UTC',
    type: 'risk_attachment',
  },
  {
    id: 'EV-4503',
    name: 'Approval_AQ-1411_Signoff.md',
    source: 'Governance Workflow',
    capturedAt: '2026-03-08 11:16 UTC',
    type: 'approval_note',
  },
  {
    id: 'EV-4504',
    name: 'Debrief_Packet_RFP-2401.zip',
    source: 'Reporting Service',
    capturedAt: '2026-03-08 11:45 UTC',
    type: 'audit_export',
  },
];
