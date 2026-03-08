export type Tone = 'neutral' | 'info' | 'success' | 'warning' | 'danger';

export interface KpiDatum {
  label: string;
  value: string;
  delta: string;
  tone: Tone;
}

export interface GovernanceControlSnapshot {
  evaluationMethod: 'lowest_cost' | 'weighted_score' | 'best_value';
  committeeMode: 'single_approver' | 'sequential' | 'parallel' | 'quorum';
  technicalGate: 'enabled' | 'disabled';
  dueDiligenceStatus: 'pending' | 'pass' | 'conditional_pass' | 'fail';
  fraudSignalCount: number;
  fraudSignalSeverity: 'low' | 'medium' | 'high' | 'critical';
  exceptionId: string | null;
  expiryDate: string | null;
  approverRole: string;
  awardRationaleSummary: string;
  evidenceBundleLink: string;
}

export interface ApprovalQueueItem {
  id: string;
  rfqId: string;
  title: string;
  owner: string;
  requiredBy: string;
  dueDiligenceStatus: GovernanceControlSnapshot['dueDiligenceStatus'];
  fraudSignalSeverity: GovernanceControlSnapshot['fraudSignalSeverity'];
}

export interface DecisionTrailEvent {
  id: string;
  happenedAt: string;
  actor: string;
  action: string;
  details: string;
}

export interface EvidenceItem {
  id: string;
  name: string;
  source: string;
  capturedAt: string;
  type: 'bid_document' | 'approval_note' | 'risk_attachment' | 'audit_export';
}
