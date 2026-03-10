export type RfqStatus = 'active' | 'closed' | 'awarded' | 'archived' | 'draft';
export type QuoteStatus = 'processing' | 'parsed' | 'accepted' | 'rejected' | 'error';
export type RunType = 'preview' | 'final';
export type RunStatus = 'generated' | 'stale' | 'locked';
export type ApprovalStatus = 'pending' | 'approved' | 'rejected' | 'awaiting-evidence' | 'returned';
export type NegotiationStatus = 'active' | 'paused' | 'closed';

export interface UserSummary {
  id: string;
  name: string;
  role: string;
  initials: string;
  email: string;
}

export interface NotificationRecord {
  id: string;
  title: string;
  description: string;
  timestamp: string;
  unread: boolean;
  targetPath: string;
}

export interface ActivityRecord {
  id: string;
  timestamp: string;
  actor: string;
  type: string;
  detail: string;
}

export interface RfqLineItemRecord {
  id: string;
  heading?: string;
  entryType?: 'heading' | 'line';
  description: string;
  qty: number;
  unit: string;
  targetPrice: number;
  category: string;
}

export interface VendorRecord {
  id: string;
  name: string;
  contact: string;
  inviteStatus: 'not-invited' | 'invited' | 'responded' | 'declined';
  risk: 'low' | 'medium' | 'high';
  leadTime: string;
  totalSpend: string;
  onTimeRate: string;
  diversity: string;
  notes: string;
}

export interface AwardState {
  winnerVendorId: string;
  confidence: number;
  total: string;
  savings: string;
  standstillDaysRemaining: number;
  finalized: boolean;
  splitAllocation: { id: string; name: string; value: number }[];
  signOffChecklist: { id: string; label: string; checked: boolean }[];
  debriefs: { id: string; name: string; status: 'not-sent' | 'sent' | 'na' }[];
  handoffTimeline: { id: string; label: string; timestamp: string; state: 'done' | 'pending' | 'failed' }[];
  payloadPreview: string;
}

export interface RfqRecord {
  id: string;
  title: string;
  status: RfqStatus;
  category: string;
  department: string;
  ownerId: string;
  description: string;
  deadline: string;
  deadlineLabel: string;
  submissionDeadline: string;
  evaluationMethod: string;
  paymentTerms: string;
  estValue: string;
  savings: string;
  primaryAction: string;
  expectedQuotes: number;
  activity: ActivityRecord[];
  lineItems: RfqLineItemRecord[];
  vendorIds: string[];
  quoteIds: string[];
  comparisonRunIds: string[];
  approvalIds: string[];
  negotiationIds: string[];
  documentIds: string[];
  riskIds: string[];
  decisionTrailIds: string[];
  award: AwardState | null;
}

export interface QuoteLineRecord {
  id: string;
  description: string;
  currency: string;
  total: string;
  mappedLineId: string;
  mappedLineLabel: string;
  confidence: 'high' | 'medium' | 'low';
  overrideReason?: string;
  normalizedQty: string;
  normalizedUnit: string;
  normalizedPrice: string;
  conflict?: boolean;
}

export interface QuoteSubmissionRecord {
  id: string;
  rfqId: string;
  vendorId: string;
  fileName: string;
  status: QuoteStatus;
  parseConfidence: number;
  uploadedAt: string;
  validationIssues: string[];
  rejectionReason?: string;
  previewPageInfo: string;
  overviewNotes: string[];
  normalizedLocked: boolean;
  normalizedStale: boolean;
  lineItems: QuoteLineRecord[];
}

export interface ComparisonRunRecord {
  id: string;
  rfqId: string;
  createdAt: string;
  createdBy: string;
  type: RunType;
  status: RunStatus;
  scoringModel: string;
  approvalState: 'approved' | 'pending' | 'rejected';
  readinessIssues: string[];
  vendors: { vendorId: string; total: string; rank: number }[];
  rows: {
    id: string;
    category: string;
    lineItem: string;
    values: (string | null)[];
    bestVendorIndex: number;
  }[];
  termsComparison: { label: string; values: string[] }[];
  recommendation: {
    vendorId: string;
    confidence: number;
    factors: string[];
  };
  linkedApprovalId?: string;
}

export interface ApprovalRecord {
  id: string;
  rfqId: string;
  type: 'comparison approval' | 'risk escalation' | 'policy exception' | 'recommendation override';
  summary: string;
  priority: 'high' | 'medium' | 'low';
  sla: { variant: 'safe' | 'warning' | 'overdue'; value: string };
  assignee: string;
  status: ApprovalStatus;
  createdAt: string;
  reasonRequired: string;
  gateReasons: string[];
  evidenceTabs: {
    documents: string[];
    screening: string[];
    audit: string[];
    comparison: string[];
  };
  history: ActivityRecord[];
  linkedRunId?: string;
  routeAfterApproval: 'award' | 'negotiation';
}

export interface NegotiationRecord {
  id: string;
  rfqId: string;
  vendorId: string;
  status: NegotiationStatus;
  strategy: string;
  bafoDue: string;
  owner: string;
  rounds: {
    id: string;
    label: string;
    timestamp: string;
    note: string;
    value: string;
  }[];
}

export interface DocumentRecord {
  id: string;
  rfqId?: string;
  title: string;
  tag: string;
  owner: string;
  updatedAt: string;
  size: string;
  bundle: string;
}

export interface RiskRecord {
  id: string;
  rfqId: string;
  title: string;
  area: string;
  severity: 'low' | 'medium' | 'high';
  status: 'clear' | 'flagged' | 'pending';
  detail: string;
}

export interface DecisionTrailRecord {
  id: string;
  rfqId: string;
  timestamp: string;
  actor: string;
  event: string;
  hash: string;
  detail: string;
}

export interface DashboardTaskRecord {
  id: string;
  title: string;
  subtitle: string;
  targetPath: string;
  priority: 'high' | 'medium' | 'low';
}

export interface DashboardAlertRecord {
  id: string;
  title: string;
  detail: string;
  targetPath: string;
  severity: 'high' | 'medium' | 'low';
}

export interface ReportRecord {
  id: string;
  title: string;
  summary: string;
  lastGenerated: string;
  owner: string;
}

export interface SettingsRecord {
  users: Array<UserSummary & { status: 'active' | 'invited'; permissions: string[] }>;
  scoringPolicies: Array<{ id: string; name: string; version: string; status: 'active' | 'draft'; weights: string[] }>;
  templates: Array<{ id: string; name: string; category: string; lastUpdated: string; lineItemCount: number }>;
  integrations: Array<{ id: string; name: string; status: 'connected' | 'attention'; lastSync: string; queueDepth: number }>;
  featureFlags: Array<{ id: string; name: string; enabled: boolean; description: string }>;
}

export interface AppSeed {
  tenantName: string;
  currentUser: UserSummary;
  users: UserSummary[];
  notifications: NotificationRecord[];
  dashboard: {
    tasks: DashboardTaskRecord[];
    alerts: DashboardAlertRecord[];
  };
  rfqs: RfqRecord[];
  vendors: VendorRecord[];
  quotes: QuoteSubmissionRecord[];
  comparisonRuns: ComparisonRunRecord[];
  approvals: ApprovalRecord[];
  negotiations: NegotiationRecord[];
  documents: DocumentRecord[];
  risks: RiskRecord[];
  decisionTrail: DecisionTrailRecord[];
  reports: ReportRecord[];
  settings: SettingsRecord;
  aiInsights: string[];
}

const users: UserSummary[] = [
  { id: 'user-alex', name: 'Alex Kumar', role: 'Procurement Manager', initials: 'AK', email: 'alex@atomyq.com' },
  { id: 'user-maya', name: 'Maya Tan', role: 'Category Buyer', initials: 'MT', email: 'maya@atomyq.com' },
  { id: 'user-jordan', name: 'Jordan Lee', role: 'Finance Approver', initials: 'JL', email: 'jordan@atomyq.com' },
  { id: 'user-fatimah', name: 'Fatimah Rahman', role: 'Compliance Lead', initials: 'FR', email: 'fatimah@atomyq.com' },
];

const vendors: VendorRecord[] = [
  {
    id: 'vendor-dell',
    name: 'Dell Technologies',
    contact: 'Siti Wahab',
    inviteStatus: 'responded',
    risk: 'low',
    leadTime: '14 days',
    totalSpend: '$4.2M',
    onTimeRate: '97%',
    diversity: 'Global strategic supplier',
    notes: 'Best historic service score on infrastructure refresh programs.',
  },
  {
    id: 'vendor-hpe',
    name: 'HPE Enterprise',
    contact: 'Arjun Nair',
    inviteStatus: 'responded',
    risk: 'medium',
    leadTime: '18 days',
    totalSpend: '$2.8M',
    onTimeRate: '93%',
    diversity: 'Regional service coverage',
    notes: 'Competitive pricing but higher variance in change requests.',
  },
  {
    id: 'vendor-lenovo',
    name: 'Lenovo Infrastructure',
    contact: 'Clara Wong',
    inviteStatus: 'invited',
    risk: 'low',
    leadTime: '21 days',
    totalSpend: '$1.1M',
    onTimeRate: '95%',
    diversity: 'Emerging strategic supplier',
    notes: 'Recently added to preferred vendor panel.',
  },
  {
    id: 'vendor-cisco',
    name: 'Cisco Solutions',
    contact: 'Marcus Lim',
    inviteStatus: 'declined',
    risk: 'high',
    leadTime: '30 days',
    totalSpend: '$6.9M',
    onTimeRate: '90%',
    diversity: 'Networking specialist',
    notes: 'Declined this cycle due to manufacturing allocation constraints.',
  },
  {
    id: 'vendor-siemens',
    name: 'Siemens Smart Ops',
    contact: 'Priya Gopal',
    inviteStatus: 'responded',
    risk: 'medium',
    leadTime: '24 days',
    totalSpend: '$1.9M',
    onTimeRate: '92%',
    diversity: 'Industrial automation partner',
    notes: 'Strong in controls and telemetry stacks.',
  },
  {
    id: 'vendor-abb',
    name: 'ABB Motion',
    contact: 'Leonard Ho',
    inviteStatus: 'responded',
    risk: 'low',
    leadTime: '20 days',
    totalSpend: '$3.1M',
    onTimeRate: '96%',
    diversity: 'Controls and instrumentation',
    notes: 'Consistent compliance package submissions.',
  },
];

export const mockData: AppSeed = {
  tenantName: 'Apex Manufacturing Group',
  currentUser: users[0],
  users,
  notifications: [
    {
      id: 'notif-1',
      title: 'Approval APR-00412 requires your review',
      description: 'RFQ-2401 has crossed the policy gate for commercial variance.',
      timestamp: '2m ago',
      unread: true,
      targetPath: '/rfqs/RFQ-2401/approvals/APR-00412',
    },
    {
      id: 'notif-2',
      title: 'Dell uploaded a revised quote',
      description: 'Normalization needs to be reviewed before the next run.',
      timestamp: '35m ago',
      unread: true,
      targetPath: '/rfqs/RFQ-2401/intake/QT-1003',
    },
    {
      id: 'notif-3',
      title: 'Comparison Run RUN-005 is stale',
      description: 'Underlying quote mappings changed after the final lock.',
      timestamp: '1h ago',
      unread: true,
      targetPath: '/rfqs/RFQ-2398/runs/RUN-005',
    },
    {
      id: 'notif-4',
      title: 'ERP handoff failed for RFQ-2384',
      description: 'Retry after validating supplier payment terms payload.',
      timestamp: 'Yesterday',
      unread: false,
      targetPath: '/rfqs/RFQ-2384/award',
    },
  ],
  dashboard: {
    tasks: [
      {
        id: 'task-1',
        title: 'Complete approval decision',
        subtitle: 'APR-00412 · Server Infrastructure Refresh',
        targetPath: '/rfqs/RFQ-2401/approvals/APR-00412',
        priority: 'high',
      },
      {
        id: 'task-2',
        title: 'Lock normalization for Dell revision',
        subtitle: 'QT-1003 · Quote Intake',
        targetPath: '/rfqs/RFQ-2401/intake/QT-1003/normalize',
        priority: 'medium',
      },
      {
        id: 'task-3',
        title: 'Finalize award handoff retry',
        subtitle: 'Managed Security Services',
        targetPath: '/rfqs/RFQ-2384/award',
        priority: 'medium',
      },
    ],
    alerts: [
      {
        id: 'alert-1',
        title: 'SLA breach risk on approval queue',
        detail: 'Two approvals drop into overdue status within 6 hours.',
        targetPath: '/approvals',
        severity: 'high',
      },
      {
        id: 'alert-2',
        title: 'Normalization incomplete',
        detail: 'Three accepted quotes are still missing taxonomy mapping.',
        targetPath: '/rfqs/RFQ-2401/intake/QT-1003/normalize',
        severity: 'medium',
      },
      {
        id: 'alert-3',
        title: 'ERP connectivity warning',
        detail: 'Oracle ERP connector queue depth exceeds threshold.',
        targetPath: '/settings/integrations',
        severity: 'low',
      },
    ],
  },
  rfqs: [
    {
      id: 'RFQ-2401',
      title: 'Server Infrastructure Refresh',
      status: 'active',
      category: 'IT Infrastructure',
      department: 'Technology Services',
      ownerId: 'user-maya',
      description: 'Refresh core compute and storage footprint for the regional production cluster.',
      deadline: '2026-03-18',
      deadlineLabel: 'Mar 18, 2026',
      submissionDeadline: '2026-03-18 17:00',
      evaluationMethod: 'Weighted commercial + technical score',
      paymentTerms: 'Net 45',
      estValue: '$1.24M',
      savings: '12.4%',
      primaryAction: 'Close for Submissions',
      expectedQuotes: 5,
      activity: [
        { id: 'act-1', timestamp: 'Today, 09:32', actor: 'Maya Tan', type: 'Quote uploaded', detail: 'Dell revision ingested into Quote Intake.' },
        { id: 'act-2', timestamp: 'Today, 08:14', actor: 'System', type: 'Approval required', detail: 'Commercial variance exceeded policy threshold.' },
        { id: 'act-3', timestamp: 'Yesterday, 16:22', actor: 'Alex Kumar', type: 'Comparison run completed', detail: 'RUN-007 generated as Preview.' },
        { id: 'act-4', timestamp: 'Yesterday, 09:10', actor: 'System', type: 'Vendor response', detail: 'HPE submitted quote package.' },
      ],
      lineItems: [
        { id: 'li-head-1', entryType: 'heading', description: '', qty: 0, unit: 'EA', targetPrice: 0, category: 'Compute', heading: 'Compute Cluster' },
        { id: 'li-1', description: 'Blade chassis with redundant power', qty: 6, unit: 'EA', targetPrice: 48000, category: 'Compute' },
        { id: 'li-2', description: 'Intel server nodes 2U', qty: 28, unit: 'EA', targetPrice: 14500, category: 'Compute' },
        { id: 'li-3', description: 'NVMe storage shelf 180TB', qty: 4, unit: 'EA', targetPrice: 91000, category: 'Storage' },
        { id: 'li-4', description: 'VMware support renewal 36 months', qty: 1, unit: 'LOT', targetPrice: 215000, category: 'Software' },
      ],
      vendorIds: ['vendor-dell', 'vendor-hpe', 'vendor-lenovo', 'vendor-cisco'],
      quoteIds: ['QT-1001', 'QT-1002', 'QT-1003', 'QT-1004', 'QT-1005'],
      comparisonRunIds: ['RUN-007', 'RUN-006'],
      approvalIds: ['APR-00412', 'APR-00415'],
      negotiationIds: ['NEG-210'],
      documentIds: ['DOC-100', 'DOC-101', 'DOC-102', 'DOC-103'],
      riskIds: ['RISK-1', 'RISK-2', 'RISK-3'],
      decisionTrailIds: ['DT-1', 'DT-2', 'DT-3', 'DT-4'],
      award: {
        winnerVendorId: 'vendor-dell',
        confidence: 82,
        total: '$1.08M',
        savings: '12.4%',
        standstillDaysRemaining: 7,
        finalized: false,
        splitAllocation: [
          { id: 'vendor-dell', name: 'Dell Technologies', value: 70 },
          { id: 'vendor-hpe', name: 'HPE Enterprise', value: 30 },
        ],
        signOffChecklist: [
          { id: 'check-1', label: 'Approved comparison run selected', checked: true },
          { id: 'check-2', label: 'Compliance evidence attached', checked: true },
          { id: 'check-3', label: 'Commercial debrief plan prepared', checked: false },
        ],
        debriefs: [
          { id: 'vendor-dell', name: 'Dell Technologies', status: 'na' },
          { id: 'vendor-hpe', name: 'HPE Enterprise', status: 'not-sent' },
          { id: 'vendor-lenovo', name: 'Lenovo Infrastructure', status: 'not-sent' },
        ],
        handoffTimeline: [
          { id: 'handoff-1', label: 'Validate payload', timestamp: 'Pending', state: 'pending' },
          { id: 'handoff-2', label: 'Create PO in ERP', timestamp: 'Pending', state: 'pending' },
        ],
        payloadPreview: '{\n  "rfqId": "RFQ-2401",\n  "awardMode": "split",\n  "vendors": ["Dell Technologies", "HPE Enterprise"],\n  "currency": "USD"\n}',
      },
    },
    {
      id: 'RFQ-2398',
      title: 'Warehouse Automation Sensors',
      status: 'closed',
      category: 'Operations Automation',
      department: 'Supply Chain',
      ownerId: 'user-alex',
      description: 'Sensor grid and control cabinets for autonomous aisle pilot.',
      deadline: '2026-03-11',
      deadlineLabel: 'Mar 11, 2026',
      submissionDeadline: '2026-03-11 12:00',
      evaluationMethod: 'Lowest normalized total with compliance gate',
      paymentTerms: 'Net 30',
      estValue: '$420K',
      savings: '8.1%',
      primaryAction: 'Reopen',
      expectedQuotes: 3,
      activity: [
        { id: 'act-5', timestamp: 'Today, 10:50', actor: 'System', type: 'Run stale', detail: 'RUN-005 marked stale after mapping edit.' },
        { id: 'act-6', timestamp: 'Yesterday, 17:12', actor: 'Jordan Lee', type: 'Approval granted', detail: 'APR-00408 approved for final recommendation.' },
      ],
      lineItems: [
        { id: 'li-20', description: 'Industrial sensors', qty: 340, unit: 'EA', targetPrice: 220, category: 'Hardware' },
        { id: 'li-21', description: 'Edge controller cabinets', qty: 12, unit: 'EA', targetPrice: 6500, category: 'Hardware' },
        { id: 'li-22', description: 'Calibration service', qty: 1, unit: 'LOT', targetPrice: 32000, category: 'Services' },
      ],
      vendorIds: ['vendor-siemens', 'vendor-abb'],
      quoteIds: ['QT-2001', 'QT-2002'],
      comparisonRunIds: ['RUN-005'],
      approvalIds: ['APR-00408'],
      negotiationIds: [],
      documentIds: ['DOC-110', 'DOC-111'],
      riskIds: ['RISK-4'],
      decisionTrailIds: ['DT-10', 'DT-11'],
      award: {
        winnerVendorId: 'vendor-abb',
        confidence: 91,
        total: '$386K',
        savings: '8.1%',
        standstillDaysRemaining: 3,
        finalized: false,
        splitAllocation: [
          { id: 'vendor-abb', name: 'ABB Motion', value: 100 },
        ],
        signOffChecklist: [
          { id: 'check-20', label: 'Approved comparison run selected', checked: true },
          { id: 'check-21', label: 'Legal clauses validated', checked: true },
          { id: 'check-22', label: 'Budget holder sign-off', checked: true },
        ],
        debriefs: [
          { id: 'vendor-abb', name: 'ABB Motion', status: 'na' },
          { id: 'vendor-siemens', name: 'Siemens Smart Ops', status: 'not-sent' },
        ],
        handoffTimeline: [
          { id: 'handoff-20', label: 'Validate payload', timestamp: 'Today, 11:05', state: 'done' },
          { id: 'handoff-21', label: 'Create PO in ERP', timestamp: 'Pending', state: 'pending' },
        ],
        payloadPreview: '{\n  "rfqId": "RFQ-2398",\n  "winner": "ABB Motion",\n  "total": 386000\n}',
      },
    },
    {
      id: 'RFQ-2384',
      title: 'Managed Security Services',
      status: 'awarded',
      category: 'Cyber Security',
      department: 'Information Security',
      ownerId: 'user-fatimah',
      description: 'SOC monitoring, incident retainer, and vulnerability management coverage.',
      deadline: '2026-02-26',
      deadlineLabel: 'Feb 26, 2026',
      submissionDeadline: '2026-02-26 18:00',
      evaluationMethod: 'Best value with risk weighting',
      paymentTerms: 'Quarterly billing',
      estValue: '$860K',
      savings: '5.2%',
      primaryAction: 'View Award',
      expectedQuotes: 4,
      activity: [
        { id: 'act-7', timestamp: 'Yesterday, 14:02', actor: 'System', type: 'Handoff failed', detail: 'Oracle ERP rejected payment term mapping.' },
      ],
      lineItems: [
        { id: 'li-30', description: '24x7 SOC monitoring', qty: 12, unit: 'MONTH', targetPrice: 42000, category: 'Service' },
        { id: 'li-31', description: 'Incident response retainer', qty: 1, unit: 'LOT', targetPrice: 150000, category: 'Service' },
      ],
      vendorIds: ['vendor-dell', 'vendor-hpe'],
      quoteIds: ['QT-3001', 'QT-3002'],
      comparisonRunIds: ['RUN-004'],
      approvalIds: ['APR-00391'],
      negotiationIds: ['NEG-188'],
      documentIds: ['DOC-120', 'DOC-121'],
      riskIds: ['RISK-5'],
      decisionTrailIds: ['DT-20', 'DT-21'],
      award: {
        winnerVendorId: 'vendor-hpe',
        confidence: 88,
        total: '$815K',
        savings: '5.2%',
        standstillDaysRemaining: 0,
        finalized: true,
        splitAllocation: [
          { id: 'vendor-hpe', name: 'HPE Enterprise', value: 100 },
        ],
        signOffChecklist: [
          { id: 'check-30', label: 'Approved comparison run selected', checked: true },
          { id: 'check-31', label: 'Standstill elapsed', checked: true },
          { id: 'check-32', label: 'ERP handoff successful', checked: false },
        ],
        debriefs: [
          { id: 'vendor-hpe', name: 'HPE Enterprise', status: 'na' },
          { id: 'vendor-dell', name: 'Dell Technologies', status: 'sent' },
        ],
        handoffTimeline: [
          { id: 'handoff-30', label: 'Validate payload', timestamp: 'Yesterday, 13:10', state: 'done' },
          { id: 'handoff-31', label: 'Create contract in ERP', timestamp: 'Yesterday, 13:18', state: 'failed' },
        ],
        payloadPreview: '{\n  "rfqId": "RFQ-2384",\n  "winner": "HPE Enterprise",\n  "paymentTerms": "Quarterly billing"\n}',
      },
    },
    {
      id: 'RFQ-2355',
      title: 'Laboratory Consumables',
      status: 'archived',
      category: 'Lab Operations',
      department: 'R&D',
      ownerId: 'user-maya',
      description: 'Archived supply event retained for audit comparison.',
      deadline: '2025-12-12',
      deadlineLabel: 'Dec 12, 2025',
      submissionDeadline: '2025-12-12 15:00',
      evaluationMethod: 'Lowest landed cost',
      paymentTerms: 'Net 60',
      estValue: '$94K',
      savings: '3.5%',
      primaryAction: 'Archived',
      expectedQuotes: 4,
      activity: [],
      lineItems: [
        { id: 'li-40', description: 'Micropipette tips', qty: 500, unit: 'BOX', targetPrice: 18, category: 'Consumable' },
      ],
      vendorIds: ['vendor-abb'],
      quoteIds: [],
      comparisonRunIds: [],
      approvalIds: [],
      negotiationIds: [],
      documentIds: [],
      riskIds: [],
      decisionTrailIds: [],
      award: null,
    },
    {
      id: 'RFQ-2410',
      title: 'Office Build-Out Furnishing',
      status: 'draft',
      category: 'Facilities',
      department: 'Workplace Experience',
      ownerId: 'user-alex',
      description: 'Draft sourcing event for HQ project rooms, collaborative spaces, and ergonomic seating.',
      deadline: '2026-04-02',
      deadlineLabel: 'Apr 02, 2026',
      submissionDeadline: '2026-04-02 17:00',
      evaluationMethod: 'Weighted quality and lead time',
      paymentTerms: '30% deposit / 70% on delivery',
      estValue: '$290K',
      savings: '0%',
      primaryAction: 'Submit/Publish',
      expectedQuotes: 0,
      activity: [],
      lineItems: [
        { id: 'li-50', description: 'Task chair ergonomic', qty: 240, unit: 'EA', targetPrice: 430, category: 'Furniture' },
        { id: 'li-51', description: 'Meeting room table', qty: 16, unit: 'EA', targetPrice: 1800, category: 'Furniture' },
      ],
      vendorIds: ['vendor-dell'],
      quoteIds: [],
      comparisonRunIds: [],
      approvalIds: [],
      negotiationIds: [],
      documentIds: ['DOC-140'],
      riskIds: [],
      decisionTrailIds: [],
      award: null,
    },
  ],
  vendors,
  quotes: [
    {
      id: 'QT-1001',
      rfqId: 'RFQ-2401',
      vendorId: 'vendor-dell',
      fileName: 'dell_server_refresh_rev1.pdf',
      status: 'accepted',
      parseConfidence: 93,
      uploadedAt: 'Today, 09:32',
      validationIssues: [],
      previewPageInfo: '17 pages · PDF',
      overviewNotes: ['Pricing annex included', 'Support matrix attached', 'Currency normalized to USD'],
      normalizedLocked: true,
      normalizedStale: false,
      lineItems: [
        { id: 'ql-1', description: 'Blade chassis with redundant power', currency: 'USD', total: '$282,000', mappedLineId: 'li-1', mappedLineLabel: 'Blade chassis with redundant power', confidence: 'high', normalizedQty: '6', normalizedUnit: 'EA', normalizedPrice: '$47,000' },
        { id: 'ql-2', description: 'Intel server nodes 2U', currency: 'USD', total: '$392,000', mappedLineId: 'li-2', mappedLineLabel: 'Intel server nodes 2U', confidence: 'high', normalizedQty: '28', normalizedUnit: 'EA', normalizedPrice: '$14,000' },
        { id: 'ql-3', description: 'NVMe storage shelf 180TB', currency: 'USD', total: '$344,000', mappedLineId: 'li-3', mappedLineLabel: 'NVMe storage shelf 180TB', confidence: 'medium', normalizedQty: '4', normalizedUnit: 'EA', normalizedPrice: '$86,000', overrideReason: 'Buyer mapped vendor bundle to target storage shelf.', conflict: true },
      ],
    },
    {
      id: 'QT-1002',
      rfqId: 'RFQ-2401',
      vendorId: 'vendor-hpe',
      fileName: 'hpe_refresh_submission.xlsx',
      status: 'parsed',
      parseConfidence: 74,
      uploadedAt: 'Yesterday, 09:10',
      validationIssues: ['Warranty term missing for storage shelf.', 'One line item mapped to multiple target lines.'],
      previewPageInfo: '4 sheets · XLSX',
      overviewNotes: ['Commercial file parsed from spreadsheet', 'Tax handling separated into notes'],
      normalizedLocked: false,
      normalizedStale: false,
      lineItems: [
        { id: 'ql-4', description: 'Compute enclosure', currency: 'USD', total: '$288,000', mappedLineId: 'li-1', mappedLineLabel: 'Blade chassis with redundant power', confidence: 'medium', normalizedQty: '6', normalizedUnit: 'EA', normalizedPrice: '$48,000' },
        { id: 'ql-5', description: 'Server node package', currency: 'USD', total: '$414,400', mappedLineId: 'li-2', mappedLineLabel: 'Intel server nodes 2U', confidence: 'medium', normalizedQty: '28', normalizedUnit: 'EA', normalizedPrice: '$14,800', conflict: true },
      ],
    },
    {
      id: 'QT-1003',
      rfqId: 'RFQ-2401',
      vendorId: 'vendor-dell',
      fileName: 'dell_server_refresh_rev2.pdf',
      status: 'accepted',
      parseConfidence: 88,
      uploadedAt: 'Today, 09:32',
      validationIssues: ['Storage warranty term changed from 36 to 24 months.'],
      previewPageInfo: '19 pages · PDF',
      overviewNotes: ['Revision contains reduced storage lead time', 'Submitted under expedited negotiation'],
      normalizedLocked: false,
      normalizedStale: true,
      lineItems: [
        { id: 'ql-6', description: 'Blade chassis with redundant power', currency: 'USD', total: '$279,000', mappedLineId: 'li-1', mappedLineLabel: 'Blade chassis with redundant power', confidence: 'high', normalizedQty: '6', normalizedUnit: 'EA', normalizedPrice: '$46,500' },
        { id: 'ql-7', description: 'Intel server nodes 2U', currency: 'USD', total: '$386,400', mappedLineId: 'li-2', mappedLineLabel: 'Intel server nodes 2U', confidence: 'high', normalizedQty: '28', normalizedUnit: 'EA', normalizedPrice: '$13,800' },
        { id: 'ql-8', description: 'NVMe storage shelf 180TB bundle', currency: 'USD', total: '$336,000', mappedLineId: 'li-3', mappedLineLabel: 'NVMe storage shelf 180TB', confidence: 'low', normalizedQty: '4', normalizedUnit: 'EA', normalizedPrice: '$84,000', conflict: true },
      ],
    },
    {
      id: 'QT-1004',
      rfqId: 'RFQ-2401',
      vendorId: 'vendor-lenovo',
      fileName: 'lenovo_refresh_response.pdf',
      status: 'processing',
      parseConfidence: 41,
      uploadedAt: '14m ago',
      validationIssues: ['OCR still running.'],
      previewPageInfo: 'Pending parse',
      overviewNotes: ['File ingest in progress'],
      normalizedLocked: false,
      normalizedStale: false,
      lineItems: [],
    },
    {
      id: 'QT-1005',
      rfqId: 'RFQ-2401',
      vendorId: 'vendor-cisco',
      fileName: 'cisco_partial_quote.zip',
      status: 'rejected',
      parseConfidence: 52,
      uploadedAt: 'Yesterday, 13:15',
      validationIssues: ['Archive missing priced BoM.'],
      rejectionReason: 'Submission incomplete and outside commercial template requirements.',
      previewPageInfo: 'ZIP archive',
      overviewNotes: ['Rejected for missing priced schedule'],
      normalizedLocked: false,
      normalizedStale: false,
      lineItems: [],
    },
    {
      id: 'QT-2001',
      rfqId: 'RFQ-2398',
      vendorId: 'vendor-siemens',
      fileName: 'siemens_sensor_grid.pdf',
      status: 'accepted',
      parseConfidence: 91,
      uploadedAt: 'Mar 08, 09:12',
      validationIssues: [],
      previewPageInfo: '11 pages · PDF',
      overviewNotes: ['Accepted into final run'],
      normalizedLocked: true,
      normalizedStale: false,
      lineItems: [
        { id: 'ql-20', description: 'Industrial sensors', currency: 'USD', total: '$79,900', mappedLineId: 'li-20', mappedLineLabel: 'Industrial sensors', confidence: 'high', normalizedQty: '340', normalizedUnit: 'EA', normalizedPrice: '$235' },
      ],
    },
    {
      id: 'QT-2002',
      rfqId: 'RFQ-2398',
      vendorId: 'vendor-abb',
      fileName: 'abb_sensor_grid.xlsx',
      status: 'accepted',
      parseConfidence: 96,
      uploadedAt: 'Mar 08, 10:40',
      validationIssues: [],
      previewPageInfo: '3 sheets · XLSX',
      overviewNotes: ['Lowest landed total in final run'],
      normalizedLocked: true,
      normalizedStale: false,
      lineItems: [
        { id: 'ql-21', description: 'Industrial sensors', currency: 'USD', total: '$74,800', mappedLineId: 'li-20', mappedLineLabel: 'Industrial sensors', confidence: 'high', normalizedQty: '340', normalizedUnit: 'EA', normalizedPrice: '$220' },
      ],
    },
    {
      id: 'QT-3001',
      rfqId: 'RFQ-2384',
      vendorId: 'vendor-dell',
      fileName: 'dell_soc_retainer.pdf',
      status: 'accepted',
      parseConfidence: 90,
      uploadedAt: 'Feb 21, 12:10',
      validationIssues: [],
      previewPageInfo: '7 pages · PDF',
      overviewNotes: ['Accepted'],
      normalizedLocked: true,
      normalizedStale: false,
      lineItems: [],
    },
    {
      id: 'QT-3002',
      rfqId: 'RFQ-2384',
      vendorId: 'vendor-hpe',
      fileName: 'hpe_soc_retainer.pdf',
      status: 'accepted',
      parseConfidence: 94,
      uploadedAt: 'Feb 21, 13:05',
      validationIssues: [],
      previewPageInfo: '8 pages · PDF',
      overviewNotes: ['Accepted'],
      normalizedLocked: true,
      normalizedStale: false,
      lineItems: [],
    },
  ],
  comparisonRuns: [
    {
      id: 'RUN-007',
      rfqId: 'RFQ-2401',
      createdAt: 'Yesterday, 16:22',
      createdBy: 'Alex Kumar',
      type: 'preview',
      status: 'generated',
      scoringModel: 'Balanced Procurement v3.2',
      approvalState: 'pending',
      readinessIssues: ['One accepted quote has stale normalization.', 'Two vendors missing final service credits.'],
      vendors: [
        { vendorId: 'vendor-dell', total: '$1.08M', rank: 1 },
        { vendorId: 'vendor-hpe', total: '$1.13M', rank: 2 },
        { vendorId: 'vendor-lenovo', total: '$1.19M', rank: 3 },
      ],
      rows: [
        { id: 'row-1', category: 'Compute', lineItem: 'Blade chassis with redundant power', values: ['$279,000', '$288,000', '$291,000'], bestVendorIndex: 0 },
        { id: 'row-2', category: 'Compute', lineItem: 'Intel server nodes 2U', values: ['$386,400', '$414,400', '$427,000'], bestVendorIndex: 0 },
        { id: 'row-3', category: 'Storage', lineItem: 'NVMe storage shelf 180TB', values: ['$336,000', '$349,000', null], bestVendorIndex: 0 },
      ],
      termsComparison: [
        { label: 'Payment Terms', values: ['Net 45', 'Net 30', 'Net 30'] },
        { label: 'Lead Time', values: ['14 days', '18 days', '21 days'] },
      ],
      recommendation: {
        vendorId: 'vendor-dell',
        confidence: 82,
        factors: ['Lowest normalized total', 'Best service credit package', 'Strong historical delivery score'],
      },
      linkedApprovalId: 'APR-00412',
    },
    {
      id: 'RUN-006',
      rfqId: 'RFQ-2401',
      createdAt: 'Mar 08, 10:15',
      createdBy: 'Maya Tan',
      type: 'preview',
      status: 'stale',
      scoringModel: 'Balanced Procurement v3.1',
      approvalState: 'rejected',
      readinessIssues: ['Vendor revision superseded previous accepted quote.'],
      vendors: [
        { vendorId: 'vendor-dell', total: '$1.12M', rank: 1 },
        { vendorId: 'vendor-hpe', total: '$1.14M', rank: 2 },
      ],
      rows: [],
      termsComparison: [],
      recommendation: {
        vendorId: 'vendor-dell',
        confidence: 64,
        factors: ['Historic preference only'],
      },
    },
    {
      id: 'RUN-005',
      rfqId: 'RFQ-2398',
      createdAt: 'Yesterday, 15:02',
      createdBy: 'Alex Kumar',
      type: 'final',
      status: 'stale',
      scoringModel: 'Operational Equipment v2.0',
      approvalState: 'approved',
      readinessIssues: [],
      vendors: [
        { vendorId: 'vendor-abb', total: '$386K', rank: 1 },
        { vendorId: 'vendor-siemens', total: '$408K', rank: 2 },
      ],
      rows: [
        { id: 'row-20', category: 'Hardware', lineItem: 'Industrial sensors', values: ['$74,800', '$79,900'], bestVendorIndex: 0 },
        { id: 'row-21', category: 'Hardware', lineItem: 'Edge controller cabinets', values: ['$76,200', '$82,100'], bestVendorIndex: 0 },
        { id: 'row-22', category: 'Services', lineItem: 'Calibration service', values: ['$28,400', '$31,500'], bestVendorIndex: 0 },
      ],
      termsComparison: [
        { label: 'Warranty', values: ['24 months', '18 months'] },
        { label: 'Lead Time', values: ['20 days', '24 days'] },
      ],
      recommendation: {
        vendorId: 'vendor-abb',
        confidence: 91,
        factors: ['Best total price', 'Lower lead time', 'No compliance findings'],
      },
      linkedApprovalId: 'APR-00408',
    },
    {
      id: 'RUN-004',
      rfqId: 'RFQ-2384',
      createdAt: 'Feb 24, 16:42',
      createdBy: 'Fatimah Rahman',
      type: 'final',
      status: 'locked',
      scoringModel: 'Cyber Services v4.1',
      approvalState: 'approved',
      readinessIssues: [],
      vendors: [
        { vendorId: 'vendor-hpe', total: '$815K', rank: 1 },
        { vendorId: 'vendor-dell', total: '$860K', rank: 2 },
      ],
      rows: [
        { id: 'row-30', category: 'Service', lineItem: '24x7 SOC monitoring', values: ['$510K', '$534K'], bestVendorIndex: 0 },
        { id: 'row-31', category: 'Service', lineItem: 'Incident response retainer', values: ['$305K', '$326K'], bestVendorIndex: 0 },
      ],
      termsComparison: [
        { label: 'Incident SLA', values: ['30 min', '45 min'] },
      ],
      recommendation: {
        vendorId: 'vendor-hpe',
        confidence: 88,
        factors: ['Best incident SLA', 'Better vulnerability coverage', 'Lower total cost'],
      },
      linkedApprovalId: 'APR-00391',
    },
  ],
  approvals: [
    {
      id: 'APR-00412',
      rfqId: 'RFQ-2401',
      type: 'comparison approval',
      summary: 'Preview run exceeds target savings threshold and requires finance review.',
      priority: 'high',
      sla: { variant: 'warning', value: '1d 18h' },
      assignee: 'Jordan Lee',
      status: 'pending',
      createdAt: 'Today, 08:14',
      reasonRequired: 'Commercial variance over 10% requires explicit rationale.',
      gateReasons: ['Savings exceeds auto-approval ceiling', 'Vendor revision introduced warranty delta'],
      evidenceTabs: {
        documents: ['dell_server_refresh_rev2.pdf', 'rfq_2401_budget_guardrails.xlsx'],
        screening: ['Sanctions clear', 'Insurance document expiring in 14 days'],
        audit: ['Normalization override on storage shelf', 'Run recalculation requested by buyer'],
        comparison: ['Dell ranked #1', 'HPE ranked #2', 'Preview run only'],
      },
      history: [
        { id: 'ah-1', timestamp: 'Today, 08:14', actor: 'System', type: 'Approval created', detail: 'Policy gate opened from RUN-007.' },
      ],
      linkedRunId: 'RUN-007',
      routeAfterApproval: 'award',
    },
    {
      id: 'APR-00415',
      rfqId: 'RFQ-2401',
      type: 'risk escalation',
      summary: 'Warranty variance on revised storage bundle requires risk sign-off.',
      priority: 'medium',
      sla: { variant: 'safe', value: '3d 4h' },
      assignee: 'Fatimah Rahman',
      status: 'awaiting-evidence',
      createdAt: 'Today, 10:15',
      reasonRequired: 'Risk disposition must be documented.',
      gateReasons: ['Supplier shortened storage warranty coverage'],
      evidenceTabs: {
        documents: ['revised_storage_warranty.pdf'],
        screening: ['Coverage mismatch found'],
        audit: ['Requested evidence from vendor'],
        comparison: ['Delta limited to storage category'],
      },
      history: [
        { id: 'ah-2', timestamp: 'Today, 10:20', actor: 'Fatimah Rahman', type: 'Evidence requested', detail: 'Awaiting revised warranty addendum.' },
      ],
      linkedRunId: 'RUN-007',
      routeAfterApproval: 'award',
    },
    {
      id: 'APR-00408',
      rfqId: 'RFQ-2398',
      type: 'comparison approval',
      summary: 'Final run approved for ABB recommendation.',
      priority: 'medium',
      sla: { variant: 'safe', value: 'Closed' },
      assignee: 'Jordan Lee',
      status: 'approved',
      createdAt: 'Yesterday, 14:30',
      reasonRequired: 'Approval recorded.',
      gateReasons: ['Final run created under equipment procurement policy'],
      evidenceTabs: {
        documents: ['abb_sensor_grid.xlsx'],
        screening: ['No findings'],
        audit: ['Final run locked'],
        comparison: ['ABB ranked #1'],
      },
      history: [
        { id: 'ah-3', timestamp: 'Yesterday, 17:12', actor: 'Jordan Lee', type: 'Approved', detail: 'Award may proceed.' },
      ],
      linkedRunId: 'RUN-005',
      routeAfterApproval: 'award',
    },
    {
      id: 'APR-00391',
      rfqId: 'RFQ-2384',
      type: 'recommendation override',
      summary: 'Override accepted due to stronger incident response capability.',
      priority: 'low',
      sla: { variant: 'safe', value: 'Closed' },
      assignee: 'Fatimah Rahman',
      status: 'approved',
      createdAt: 'Feb 24, 14:10',
      reasonRequired: 'Historical approval archived.',
      gateReasons: ['Override to reflect risk posture weighting'],
      evidenceTabs: {
        documents: ['override_note.pdf'],
        screening: ['Security certifications validated'],
        audit: ['Override approved'],
        comparison: ['HPE promoted to rank #1'],
      },
      history: [
        { id: 'ah-4', timestamp: 'Feb 24, 15:02', actor: 'Fatimah Rahman', type: 'Approved', detail: 'Override adopted.' },
      ],
      linkedRunId: 'RUN-004',
      routeAfterApproval: 'award',
    },
  ],
  negotiations: [
    {
      id: 'NEG-210',
      rfqId: 'RFQ-2401',
      vendorId: 'vendor-hpe',
      status: 'active',
      strategy: 'Service credits and lead time compression',
      bafoDue: 'Tomorrow, 17:00',
      owner: 'Alex Kumar',
      rounds: [
        { id: 'round-1', label: 'Round 1', timestamp: 'Yesterday, 11:20', note: 'Requested improved storage lead time.', value: '$1.14M' },
        { id: 'round-2', label: 'Round 2', timestamp: 'Today, 09:05', note: 'Vendor offered bundled service credits.', value: '$1.13M' },
      ],
    },
    {
      id: 'NEG-188',
      rfqId: 'RFQ-2384',
      vendorId: 'vendor-hpe',
      status: 'closed',
      strategy: 'Cyber coverage expansion',
      bafoDue: 'Completed',
      owner: 'Fatimah Rahman',
      rounds: [
        { id: 'round-30', label: 'BAFO', timestamp: 'Feb 22, 15:10', note: 'Accepted final coverage expansion.', value: '$815K' },
      ],
    },
  ],
  documents: [
    { id: 'DOC-100', rfqId: 'RFQ-2401', title: 'Requirements workbook', tag: 'scope', owner: 'Maya Tan', updatedAt: 'Today, 08:00', size: '2.1 MB', bundle: 'RFQ Core Pack' },
    { id: 'DOC-101', rfqId: 'RFQ-2401', title: 'Budget guardrails', tag: 'finance', owner: 'Alex Kumar', updatedAt: 'Yesterday', size: '540 KB', bundle: 'Governance' },
    { id: 'DOC-102', rfqId: 'RFQ-2401', title: 'Dell revision 2', tag: 'quote', owner: 'System', updatedAt: 'Today', size: '4.3 MB', bundle: 'Vendor Evidence' },
    { id: 'DOC-103', rfqId: 'RFQ-2401', title: 'Warranty addendum request', tag: 'risk', owner: 'Fatimah Rahman', updatedAt: 'Today', size: '184 KB', bundle: 'Governance' },
    { id: 'DOC-110', rfqId: 'RFQ-2398', title: 'Sensor calibration matrix', tag: 'technical', owner: 'Alex Kumar', updatedAt: 'Yesterday', size: '880 KB', bundle: 'RFQ Core Pack' },
    { id: 'DOC-111', rfqId: 'RFQ-2398', title: 'ABB final quote', tag: 'quote', owner: 'System', updatedAt: 'Yesterday', size: '1.2 MB', bundle: 'Vendor Evidence' },
    { id: 'DOC-120', rfqId: 'RFQ-2384', title: 'Security questionnaire', tag: 'risk', owner: 'Fatimah Rahman', updatedAt: 'Feb 24', size: '920 KB', bundle: 'Governance' },
    { id: 'DOC-121', rfqId: 'RFQ-2384', title: 'ERP handoff payload', tag: 'handoff', owner: 'System', updatedAt: 'Yesterday', size: '64 KB', bundle: 'Handoff' },
    { id: 'DOC-140', rfqId: 'RFQ-2410', title: 'Furniture design brief', tag: 'scope', owner: 'Alex Kumar', updatedAt: 'Today', size: '3.6 MB', bundle: 'Draft Pack' },
    { id: 'DOC-150', title: 'Corporate procurement policy', tag: 'policy', owner: 'Legal', updatedAt: 'Last week', size: '1.4 MB', bundle: 'Shared Evidence' },
  ],
  risks: [
    { id: 'RISK-1', rfqId: 'RFQ-2401', title: 'Warranty mismatch on revised storage bundle', area: 'Commercial', severity: 'medium', status: 'flagged', detail: 'Dell revision 2 shortened manufacturer warranty by 12 months.' },
    { id: 'RISK-2', rfqId: 'RFQ-2401', title: 'Insurance certificate expiring', area: 'Compliance', severity: 'low', status: 'pending', detail: 'Supplier certificate expires before first shipment milestone.' },
    { id: 'RISK-3', rfqId: 'RFQ-2401', title: 'Cross-region logistics lead time', area: 'Supply', severity: 'medium', status: 'pending', detail: 'Potential delay due to consolidated sea freight allocation.' },
    { id: 'RISK-4', rfqId: 'RFQ-2398', title: 'No major findings', area: 'Compliance', severity: 'low', status: 'clear', detail: 'Supplier screening cleared for final award.' },
    { id: 'RISK-5', rfqId: 'RFQ-2384', title: 'ERP contract payload schema mismatch', area: 'Integration', severity: 'high', status: 'flagged', detail: 'Quarterly billing code failed destination schema validation.' },
  ],
  decisionTrail: [
    { id: 'DT-1', rfqId: 'RFQ-2401', timestamp: 'Today, 09:32', actor: 'System', event: 'Quote uploaded', hash: '0x8d92a1', detail: 'Dell revision 2 stored in vault.' },
    { id: 'DT-2', rfqId: 'RFQ-2401', timestamp: 'Today, 10:12', actor: 'Maya Tan', event: 'Normalization override', hash: '0x8d92a2', detail: 'Storage shelf bundle forced to RFQ line 3.' },
    { id: 'DT-3', rfqId: 'RFQ-2401', timestamp: 'Today, 10:21', actor: 'System', event: 'Approval created', hash: '0x8d92a3', detail: 'APR-00412 opened.' },
    { id: 'DT-4', rfqId: 'RFQ-2401', timestamp: 'Today, 10:24', actor: 'Fatimah Rahman', event: 'Evidence requested', hash: '0x8d92a4', detail: 'Warranty addendum requested.' },
    { id: 'DT-10', rfqId: 'RFQ-2398', timestamp: 'Yesterday, 15:02', actor: 'System', event: 'Final run locked', hash: '0x8d82a1', detail: 'RUN-005 persisted.' },
    { id: 'DT-11', rfqId: 'RFQ-2398', timestamp: 'Yesterday, 17:12', actor: 'Jordan Lee', event: 'Approval approved', hash: '0x8d82a2', detail: 'APR-00408 approved.' },
    { id: 'DT-20', rfqId: 'RFQ-2384', timestamp: 'Yesterday, 13:18', actor: 'System', event: 'ERP handoff failed', hash: '0x8d72a2', detail: 'Destination rejected payment term code.' },
    { id: 'DT-21', rfqId: 'RFQ-2384', timestamp: 'Yesterday, 13:20', actor: 'System', event: 'Retry available', hash: '0x8d72a3', detail: 'Buyer can retry handoff after validation.' },
  ],
  reports: [
    { id: 'REP-1', title: 'Savings realization by category', summary: 'Tracks approved vs forecast savings across open and awarded RFQs.', lastGenerated: 'Today', owner: 'Alex Kumar' },
    { id: 'REP-2', title: 'Approval SLA performance', summary: 'Visualizes breached, warning, and healthy approvals over rolling 90 days.', lastGenerated: 'Today', owner: 'Jordan Lee' },
    { id: 'REP-3', title: 'Vendor response rate', summary: 'Measures invite-to-response conversion by category and buyer.', lastGenerated: 'Yesterday', owner: 'Maya Tan' },
  ],
  settings: {
    users: [
      { ...users[0], status: 'active', permissions: ['rfq.manage', 'runs.approve', 'settings.view'] },
      { ...users[1], status: 'active', permissions: ['rfq.manage', 'quotes.triage'] },
      { ...users[2], status: 'active', permissions: ['approvals.decide'] },
      { ...users[3], status: 'invited', permissions: ['risk.review', 'documents.manage'] },
    ],
    scoringPolicies: [
      { id: 'score-1', name: 'Balanced Procurement', version: 'v3.2', status: 'active', weights: ['Commercial 45%', 'Technical 35%', 'Risk 20%'] },
      { id: 'score-2', name: 'Operational Equipment', version: 'v2.0', status: 'active', weights: ['Commercial 60%', 'Lead Time 20%', 'Risk 20%'] },
      { id: 'score-3', name: 'Cyber Services', version: 'v4.1', status: 'draft', weights: ['Coverage 35%', 'Response SLA 25%', 'Commercial 25%', 'Risk 15%'] },
    ],
    templates: [
      { id: 'tpl-1', name: 'Infrastructure Refresh', category: 'IT Infrastructure', lastUpdated: 'Today', lineItemCount: 18 },
      { id: 'tpl-2', name: 'Facilities Furniture', category: 'Facilities', lastUpdated: 'Yesterday', lineItemCount: 24 },
      { id: 'tpl-3', name: 'Managed Service Renewal', category: 'Professional Services', lastUpdated: 'Last week', lineItemCount: 8 },
    ],
    integrations: [
      { id: 'int-1', name: 'Oracle ERP', status: 'attention', lastSync: '8 minutes ago', queueDepth: 17 },
      { id: 'int-2', name: 'Azure AD SSO', status: 'connected', lastSync: '1 minute ago', queueDepth: 0 },
      { id: 'int-3', name: 'DocuSign', status: 'connected', lastSync: '12 minutes ago', queueDepth: 2 },
    ],
    featureFlags: [
      { id: 'flag-1', name: 'recommendation_explainability_v2', enabled: true, description: 'Enable MCDA factor breakdown in matrix slide-over.' },
      { id: 'flag-2', name: 'auto_lock_normalization', enabled: false, description: 'Automatically lock accepted quotes once all mappings are complete.' },
      { id: 'flag-3', name: 'vendor_self_service_debrief', enabled: true, description: 'Allow vendor portal access to debrief summaries after award.' },
    ],
  },
  aiInsights: [
    'Dell remains the leading recommendation because its revised storage bundle preserves first-place commercial score despite the warranty warning.',
    'Approval queue pressure is concentrated on infrastructure and security categories; two items are within amber SLA.',
    'Oracle ERP handoff failures correlate with non-standard payment terms; validate payload mapping before retrying.',
  ],
};

export function cloneMockData(): AppSeed {
  return structuredClone(mockData);
}
