// =============================================================================
// AtomyQ Design System — Unified Mock Data
// All mock data for the design system in a single file.
// =============================================================================

// --- Types -------------------------------------------------------------------

export interface RFQ {
  id: string;
  title: string;
  category: string;
  status: 'Draft' | 'Open' | 'Closed' | 'Awarded' | 'Cancelled';
  owner: string;
  vendorCount: number;
  budget: number;
  currency: string;
  deadline: string;
  created: string;
  priority: 'Low' | 'Medium' | 'High' | 'Critical';
}

export interface Vendor {
  id: string;
  name: string;
  contact: string;
  status: 'Not Invited' | 'Invited' | 'Responded' | 'Declined';
  invitedDate: string | null;
  responseDate: string | null;
  channel: 'Email' | 'Portal' | null;
  quoteValue: number | null;
  currency: string;
  rating: number;
  country: string;
}

export interface QuoteSubmission {
  id: string;
  rfqId: string;
  vendor: string;
  submittedAt: string;
  status: 'Accepted' | 'Parsed with Warnings' | 'Processing' | 'Rejected';
  confidence: number | null;
  fileType: 'PDF' | 'XLSX' | 'CSV';
  fileName: string;
  fileSize: string;
  lineItems: number | null;
  warnings: number | null;
  errors: number | null;
}

export interface ComparisonItem {
  lineItem: string;
  unit: string;
  quantity: number;
  vendors: Record<string, { unitPrice: number; total: number; leadTime: string; warranty: string }>;
  recommended: string;
}

export interface SavingsTrend {
  month: string;
  savings: number;
  target: number;
}

export interface Task {
  id: string;
  title: string;
  type: 'Review' | 'Approval' | 'Create' | 'Action' | 'Admin';
  rfq: string | null;
  priority: 'Low' | 'Medium' | 'High' | 'Critical';
  due: string;
  assignee: string;
}

export interface RiskAlert {
  id: string;
  severity: 'Critical' | 'High' | 'Medium' | 'Low';
  title: string;
  description: string;
  source: string;
  time: string;
}

export interface Comparison {
  id: string;
  rfq: string;
  title: string;
  vendorCount: number;
  status: 'Complete' | 'Stale' | 'Running';
  score: number;
  runAt: string;
  recommended: string;
}

export interface ActivityEntry {
  id: string;
  action: string;
  actor: string;
  time: string;
  type: 'system' | 'intake' | 'action' | 'vendor' | 'approval';
}

export interface User {
  id: string;
  name: string;
  email: string;
  role: 'Admin' | 'Procurement Manager' | 'Buyer' | 'Approver' | 'Viewer';
  avatar: string;
  department: string;
  status: 'Active' | 'Inactive' | 'Pending';
  permissions: string[];
}

export interface Notification {
  id: string;
  title: string;
  message: string;
  type: 'info' | 'success' | 'warning' | 'error';
  read: boolean;
  timestamp: string;
}

export interface KPIMetric {
  label: string;
  value: string;
  change: number;
  trend: 'up' | 'down' | 'flat';
  period: string;
}

export interface WorkflowStep {
  id: string;
  label: string;
  status: 'completed' | 'active' | 'pending' | 'error';
  actor?: string;
  timestamp?: string;
}

export interface ApprovalRecord {
  id: string;
  rfqId: string;
  rfqTitle: string;
  requestedBy: string;
  requestedAt: string;
  status: 'Pending' | 'Approved' | 'Rejected' | 'Escalated';
  amount: number;
  currency: string;
  level: number;
  notes: string;
}

// --- Mock Data ---------------------------------------------------------------

export const rfqs: RFQ[] = [
  { id: 'RFQ-2026-001', title: 'Industrial Pumping Equipment', category: 'Equipment', status: 'Open', owner: 'Sarah Chen', vendorCount: 4, budget: 420000, currency: 'MYR', deadline: '2026-04-15', created: '2026-03-01', priority: 'High' },
  { id: 'RFQ-2026-002', title: 'Cloud Infrastructure Services', category: 'IT Services', status: 'Draft', owner: 'Mike Johnson', vendorCount: 0, budget: 185000, currency: 'MYR', deadline: '2026-04-28', created: '2026-03-05', priority: 'Medium' },
  { id: 'RFQ-2026-003', title: 'Office Supplies Q2 Procurement', category: 'Office', status: 'Closed', owner: 'Amy Park', vendorCount: 6, budget: 32500, currency: 'MYR', deadline: '2026-03-20', created: '2026-02-15', priority: 'Low' },
  { id: 'RFQ-2026-004', title: 'Manufacturing Components Batch A', category: 'Manufacturing', status: 'Awarded', owner: 'David Lee', vendorCount: 5, budget: 1240000, currency: 'MYR', deadline: '2026-03-30', created: '2026-02-01', priority: 'Critical' },
  { id: 'RFQ-2026-005', title: 'IT Hardware Refresh 2026', category: 'IT Hardware', status: 'Open', owner: 'Sarah Chen', vendorCount: 3, budget: 310000, currency: 'MYR', deadline: '2026-05-01', created: '2026-03-02', priority: 'High' },
  { id: 'RFQ-2026-006', title: 'Logistics Services 2026 Contract', category: 'Logistics', status: 'Draft', owner: 'Mike Johnson', vendorCount: 0, budget: 890000, currency: 'MYR', deadline: '2026-05-15', created: '2026-03-04', priority: 'High' },
  { id: 'RFQ-2026-007', title: 'Preventive Maintenance Contracts', category: 'Services', status: 'Open', owner: 'Tom Wilson', vendorCount: 7, budget: 145000, currency: 'MYR', deadline: '2026-04-20', created: '2026-02-28', priority: 'Medium' },
  { id: 'RFQ-2026-008', title: 'Q2 Marketing Materials Print', category: 'Marketing', status: 'Cancelled', owner: 'Amy Park', vendorCount: 2, budget: 28000, currency: 'MYR', deadline: '2026-03-15', created: '2026-02-20', priority: 'Low' },
];

export const vendors: Vendor[] = [
  { id: 'V001', name: 'Apex Industrial Solutions', contact: 'james.carter@apexind.com', status: 'Responded', invitedDate: '2026-03-02', responseDate: '2026-03-05', channel: 'Email', quoteValue: 398200, currency: 'MYR', rating: 4.5, country: 'Malaysia' },
  { id: 'V002', name: 'TechFlow Dynamics', contact: 'priya.mehta@techflow.io', status: 'Invited', invitedDate: '2026-03-02', responseDate: null, channel: 'Portal', quoteValue: null, currency: 'MYR', rating: 3.8, country: 'India' },
  { id: 'V003', name: 'GlobalPump Corp', contact: 'r.schneider@globalpump.de', status: 'Responded', invitedDate: '2026-03-02', responseDate: '2026-03-06', channel: 'Email', quoteValue: 412000, currency: 'MYR', rating: 4.2, country: 'Germany' },
  { id: 'V004', name: 'Pacific Industrial Co.', contact: 'c.tanaka@pacificind.co.jp', status: 'Declined', invitedDate: '2026-03-02', responseDate: '2026-03-03', channel: 'Email', quoteValue: null, currency: 'MYR', rating: 4.0, country: 'Japan' },
  { id: 'V005', name: 'Meridian Equipment Ltd', contact: 'b.osei@meridian-eq.com', status: 'Not Invited', invitedDate: null, responseDate: null, channel: null, quoteValue: null, currency: 'MYR', rating: 3.5, country: 'Ghana' },
  { id: 'V006', name: 'Delta Flow Systems', contact: 'e.russo@deltaflow.it', status: 'Not Invited', invitedDate: null, responseDate: null, channel: null, quoteValue: null, currency: 'MYR', rating: 4.1, country: 'Italy' },
];

export const quoteSubmissions: QuoteSubmission[] = [
  { id: 'QS-001', rfqId: 'RFQ-2026-001', vendor: 'Apex Industrial Solutions', submittedAt: '2026-03-05 09:32', status: 'Accepted', confidence: 94, fileType: 'PDF', fileName: 'apex_quote_rfq001_v2.pdf', fileSize: '2.4 MB', lineItems: 12, warnings: 0, errors: 0 },
  { id: 'QS-002', rfqId: 'RFQ-2026-001', vendor: 'GlobalPump Corp', submittedAt: '2026-03-06 14:17', status: 'Parsed with Warnings', confidence: 71, fileType: 'XLSX', fileName: 'globalpump_quote_mar2026.xlsx', fileSize: '1.8 MB', lineItems: 11, warnings: 3, errors: 0 },
  { id: 'QS-003', rfqId: 'RFQ-2026-005', vendor: 'Nexus IT Distribution', submittedAt: '2026-03-06 11:05', status: 'Processing', confidence: null, fileType: 'PDF', fileName: 'nexus_hw_quote_2026.pdf', fileSize: '5.1 MB', lineItems: null, warnings: null, errors: null },
  { id: 'QS-004', rfqId: 'RFQ-2026-007', vendor: 'ServicePro Maintenance', submittedAt: '2026-03-07 08:20', status: 'Accepted', confidence: 99, fileType: 'XLSX', fileName: 'servicepro_maintenance_q2.xlsx', fileSize: '1.2 MB', lineItems: 24, warnings: 0, errors: 0 },
  { id: 'QS-005', rfqId: 'RFQ-2026-007', vendor: 'TechCare Solutions', submittedAt: '2026-03-07 13:48', status: 'Parsed with Warnings', confidence: 63, fileType: 'PDF', fileName: 'techcare_proposal_mar.pdf', fileSize: '3.2 MB', lineItems: 19, warnings: 4, errors: 1 },
];

export const comparisonMatrix: ComparisonItem[] = [
  {
    lineItem: 'Centrifugal Pump CP-200',
    unit: 'unit',
    quantity: 4,
    vendors: {
      'Apex Industrial': { unitPrice: 28500, total: 114000, leadTime: '6 weeks', warranty: '24 months' },
      'GlobalPump Corp': { unitPrice: 31200, total: 124800, leadTime: '8 weeks', warranty: '18 months' },
    },
    recommended: 'Apex Industrial',
  },
  {
    lineItem: 'Submersible Pump SP-150',
    unit: 'unit',
    quantity: 2,
    vendors: {
      'Apex Industrial': { unitPrice: 42000, total: 84000, leadTime: '8 weeks', warranty: '24 months' },
      'GlobalPump Corp': { unitPrice: 38500, total: 77000, leadTime: '6 weeks', warranty: '24 months' },
    },
    recommended: 'GlobalPump Corp',
  },
  {
    lineItem: 'Valve Assembly VA-300',
    unit: 'set',
    quantity: 8,
    vendors: {
      'Apex Industrial': { unitPrice: 3200, total: 25600, leadTime: '4 weeks', warranty: '12 months' },
      'GlobalPump Corp': { unitPrice: 3450, total: 27600, leadTime: '3 weeks', warranty: '18 months' },
    },
    recommended: 'Apex Industrial',
  },
  {
    lineItem: 'Control Panel CP-100',
    unit: 'unit',
    quantity: 1,
    vendors: {
      'Apex Industrial': { unitPrice: 65000, total: 65000, leadTime: '10 weeks', warranty: '36 months' },
      'GlobalPump Corp': { unitPrice: 72000, total: 72000, leadTime: '12 weeks', warranty: '24 months' },
    },
    recommended: 'Apex Industrial',
  },
  {
    lineItem: 'Installation & Commissioning',
    unit: 'lot',
    quantity: 1,
    vendors: {
      'Apex Industrial': { unitPrice: 45000, total: 45000, leadTime: '2 weeks', warranty: '6 months' },
      'GlobalPump Corp': { unitPrice: 52000, total: 52000, leadTime: '3 weeks', warranty: '6 months' },
    },
    recommended: 'Apex Industrial',
  },
];

export const savingsTrend: SavingsTrend[] = [
  { month: 'Oct', savings: 198000, target: 220000 },
  { month: 'Nov', savings: 315000, target: 230000 },
  { month: 'Dec', savings: 290000, target: 240000 },
  { month: 'Jan', savings: 428000, target: 250000 },
  { month: 'Feb', savings: 380000, target: 260000 },
  { month: 'Mar', savings: 465000, target: 270000 },
];

export const tasks: Task[] = [
  { id: 'T1', title: 'Review GlobalPump Corp quote', type: 'Review', rfq: 'RFQ-2026-001', priority: 'High', due: 'Today', assignee: 'Sarah Chen' },
  { id: 'T2', title: 'Approve Apex Industrial comparison', type: 'Approval', rfq: 'RFQ-2026-001', priority: 'Critical', due: 'Overdue', assignee: 'David Lee' },
  { id: 'T3', title: 'Complete RFQ metadata — IT Hardware', type: 'Create', rfq: 'RFQ-2026-005', priority: 'Medium', due: 'Tomorrow', assignee: 'Mike Johnson' },
  { id: 'T4', title: 'Invite additional vendors — Logistics', type: 'Action', rfq: 'RFQ-2026-006', priority: 'High', due: 'Mar 10', assignee: 'Sarah Chen' },
  { id: 'T5', title: 'Publish Scoring Policy v4 for review', type: 'Admin', rfq: null, priority: 'Medium', due: 'Mar 12', assignee: 'Tom Wilson' },
];

export const riskAlerts: RiskAlert[] = [
  { id: 'R1', severity: 'Critical', title: 'Sanctions check required', description: 'GlobalPump Corp flagged against OFAC database. Manual review needed before award.', source: 'RFQ-2026-001', time: '2h ago' },
  { id: 'R2', severity: 'High', title: 'SLA breach imminent', description: 'Approval on RFQ-2026-004 award decision due in 1h 48m. Escalation will auto-trigger.', source: 'RFQ-2026-004', time: 'Now' },
  { id: 'R3', severity: 'Medium', title: 'Missing compliance certificate', description: 'ServicePro Maintenance has not submitted ISO 9001 certificate required by policy.', source: 'RFQ-2026-007', time: '5h ago' },
  { id: 'R4', severity: 'Low', title: 'Budget threshold exceeded', description: 'RFQ-2026-005 lowest quote (RM 298K) exceeds approved budget by 3.8%.', source: 'RFQ-2026-005', time: '1d ago' },
];

export const recentComparisons: Comparison[] = [
  { id: 'CMP-001', rfq: 'RFQ-2026-001', title: 'Industrial Pumping — Final Run', vendorCount: 2, status: 'Complete', score: 92, runAt: '2026-03-06 15:30', recommended: 'Apex Industrial' },
  { id: 'CMP-002', rfq: 'RFQ-2026-004', title: 'Mfg Components — Round 2', vendorCount: 4, status: 'Stale', score: 78, runAt: '2026-03-04 11:00', recommended: 'CoreMetal Inc.' },
  { id: 'CMP-003', rfq: 'RFQ-2026-005', title: 'IT Hardware — Initial', vendorCount: 3, status: 'Complete', score: 85, runAt: '2026-03-07 09:15', recommended: 'Nexus IT Distribution' },
];

export const activityTimeline: ActivityEntry[] = [
  { id: 'A1', action: 'Comparison run generated', actor: 'System', time: 'Mar 6, 15:30', type: 'system' },
  { id: 'A2', action: 'GlobalPump Corp quote accepted', actor: 'AI Parser', time: 'Mar 6, 14:17', type: 'intake' },
  { id: 'A3', action: 'Apex Industrial quote accepted', actor: 'AI Parser', time: 'Mar 5, 09:32', type: 'intake' },
  { id: 'A4', action: 'Reminder sent to TechFlow Dynamics', actor: 'Sarah Chen', time: 'Mar 4, 10:00', type: 'action' },
  { id: 'A5', action: 'Pacific Industrial declined invitation', actor: 'Vendor Portal', time: 'Mar 3, 08:44', type: 'vendor' },
  { id: 'A6', action: 'Vendor invitations sent (4 vendors)', actor: 'Sarah Chen', time: 'Mar 2, 09:00', type: 'action' },
  { id: 'A7', action: 'RFQ published & opened', actor: 'Sarah Chen', time: 'Mar 1, 14:22', type: 'system' },
  { id: 'A8', action: 'RFQ created as draft', actor: 'Sarah Chen', time: 'Mar 1, 11:05', type: 'system' },
];

export const users: User[] = [
  { id: 'U001', name: 'Sarah Chen', email: 'sarah.chen@atomyq.com', role: 'Procurement Manager', avatar: 'SC', department: 'Procurement', status: 'Active', permissions: ['rfq.create', 'rfq.read', 'rfq.update', 'comparison.read', 'comparison.run', 'quotation.approve'] },
  { id: 'U002', name: 'Mike Johnson', email: 'mike.johnson@atomyq.com', role: 'Buyer', avatar: 'MJ', department: 'Procurement', status: 'Active', permissions: ['rfq.create', 'rfq.read', 'rfq.update', 'comparison.read'] },
  { id: 'U003', name: 'David Lee', email: 'david.lee@atomyq.com', role: 'Approver', avatar: 'DL', department: 'Finance', status: 'Active', permissions: ['rfq.read', 'comparison.read', 'quotation.approve', 'quotation.reject'] },
  { id: 'U004', name: 'Amy Park', email: 'amy.park@atomyq.com', role: 'Admin', avatar: 'AP', department: 'IT', status: 'Active', permissions: ['admin.all', 'rfq.create', 'rfq.read', 'rfq.update', 'rfq.delete', 'comparison.read', 'comparison.run', 'quotation.approve'] },
  { id: 'U005', name: 'Tom Wilson', email: 'tom.wilson@atomyq.com', role: 'Viewer', avatar: 'TW', department: 'Warehouse', status: 'Inactive', permissions: ['rfq.read', 'comparison.read'] },
];

export const notifications: Notification[] = [
  { id: 'N1', title: 'Quote Received', message: 'GlobalPump Corp submitted a quote for RFQ-2026-001.', type: 'info', read: false, timestamp: '2026-03-07T14:17:00Z' },
  { id: 'N2', title: 'Comparison Complete', message: 'AI comparison for Industrial Pumping Equipment is ready.', type: 'success', read: false, timestamp: '2026-03-06T15:30:00Z' },
  { id: 'N3', title: 'Approval Required', message: 'RFQ-2026-004 award decision requires your approval.', type: 'warning', read: true, timestamp: '2026-03-06T10:00:00Z' },
  { id: 'N4', title: 'Parsing Failed', message: 'Unable to parse softcore_quote_draft.pdf — 5 errors found.', type: 'error', read: true, timestamp: '2026-03-05T16:44:00Z' },
];

export const kpiMetrics: KPIMetric[] = [
  { label: 'Active RFQs', value: '12', change: 20, trend: 'up', period: 'vs last month' },
  { label: 'Total Savings', value: 'RM 2.08M', change: 15.3, trend: 'up', period: 'YTD' },
  { label: 'Avg. Cycle Time', value: '8.4 days', change: -12, trend: 'down', period: 'vs last quarter' },
  { label: 'AI Confidence', value: '91.2%', change: 3.1, trend: 'up', period: 'last 30 days' },
];

export const workflowSteps: WorkflowStep[] = [
  { id: 'W1', label: 'Create RFQ', status: 'completed', actor: 'Sarah Chen', timestamp: '2026-03-01 11:05' },
  { id: 'W2', label: 'Invite Vendors', status: 'completed', actor: 'Sarah Chen', timestamp: '2026-03-02 09:00' },
  { id: 'W3', label: 'Collect Quotes', status: 'completed', actor: 'System', timestamp: '2026-03-06 14:17' },
  { id: 'W4', label: 'AI Comparison', status: 'active', actor: 'System' },
  { id: 'W5', label: 'Review & Approve', status: 'pending' },
  { id: 'W6', label: 'Award & PO', status: 'pending' },
];

export const approvalRecords: ApprovalRecord[] = [
  { id: 'APR-001', rfqId: 'RFQ-2026-004', rfqTitle: 'Manufacturing Components Batch A', requestedBy: 'Sarah Chen', requestedAt: '2026-03-05 10:30', status: 'Pending', amount: 1240000, currency: 'MYR', level: 2, notes: 'Recommended vendor: CoreMetal Inc.' },
  { id: 'APR-002', rfqId: 'RFQ-2026-001', rfqTitle: 'Industrial Pumping Equipment', requestedBy: 'Mike Johnson', requestedAt: '2026-03-06 16:00', status: 'Approved', amount: 398200, currency: 'MYR', level: 1, notes: 'AI score 92/100. Apex Industrial recommended.' },
  { id: 'APR-003', rfqId: 'RFQ-2026-003', rfqTitle: 'Office Supplies Q2 Procurement', requestedBy: 'Amy Park', requestedAt: '2026-02-28 09:15', status: 'Rejected', amount: 32500, currency: 'MYR', level: 1, notes: 'Budget not yet allocated for Q2.' },
];

export const navigationItems = [
  {
    section: 'Workspace',
    items: [
      { label: 'Dashboard', icon: 'LayoutDashboard', href: '/', badge: null },
      { label: 'RFQ List', icon: 'FileText', href: '/rfqs', badge: '12' },
      { label: 'Quote Intake', icon: 'Inbox', href: '/intake', badge: '3' },
      { label: 'Comparisons', icon: 'GitCompare', href: '/comparisons', badge: null },
    ],
  },
  {
    section: 'Collaboration',
    items: [
      { label: 'Vendor Management', icon: 'Building2', href: '/vendors', badge: null },
      { label: 'Approvals', icon: 'CheckCircle', href: '/approvals', badge: '2' },
      { label: 'Negotiations', icon: 'MessageSquare', href: '/negotiations', badge: null },
    ],
  },
  {
    section: 'Governance',
    items: [
      { label: 'Decision Trail', icon: 'History', href: '/decisions', badge: null },
      { label: 'Scoring Policies', icon: 'Settings2', href: '/policies', badge: null },
      { label: 'Evidence Vault', icon: 'Shield', href: '/evidence', badge: null },
      { label: 'Risk Monitor', icon: 'AlertTriangle', href: '/risk', badge: null },
      { label: 'Reports', icon: 'BarChart3', href: '/reports', badge: null },
    ],
  },
  {
    section: 'Administration',
    items: [
      { label: 'Users & Roles', icon: 'Users', href: '/admin/users', badge: null },
      { label: 'Settings', icon: 'Settings', href: '/admin/settings', badge: null },
      { label: 'Integrations', icon: 'Plug', href: '/admin/integrations', badge: null },
    ],
  },
];

export const apiResponseExample = {
  data: [] as unknown[],
  pagination: {
    page: 1,
    limit: 25,
    total: 200,
    totalPages: 8,
  },
  meta: {
    requestId: 'req_abc123',
    timestamp: '2026-03-07T10:30:00Z',
    version: 'v1',
  },
};

export const apiErrorExample = {
  error: {
    code: 'VALIDATION_ERROR',
    message: 'The given data was invalid.',
    details: [
      { field: 'budget', message: 'Budget must be a positive number.' },
      { field: 'deadline', message: 'Deadline must be a future date.' },
    ],
  },
  meta: {
    requestId: 'req_xyz789',
    timestamp: '2026-03-07T10:31:00Z',
  },
};

export const permissionKeys = {
  rfq: ['rfq.create', 'rfq.read', 'rfq.update', 'rfq.delete'],
  comparison: ['comparison.read', 'comparison.run', 'comparison.export'],
  quotation: ['quotation.approve', 'quotation.reject', 'quotation.escalate'],
  vendor: ['vendor.create', 'vendor.read', 'vendor.update', 'vendor.invite'],
  admin: ['admin.users', 'admin.roles', 'admin.settings', 'admin.all'],
  report: ['report.view', 'report.export', 'report.schedule'],
};
