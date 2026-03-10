// Centralized mock data for all Atomy-Q screens
// All entities use consistent IDs and cross-references

// ─── Types ──────────────────────────────────────────────────────────
export type RfqStatus = 'draft' | 'open' | 'closed' | 'archived' | 'reopened';
export type QuoteStatus = 'processing' | 'parsed' | 'accepted' | 'rejected' | 'pending_assignment' | 'error';
export type NormalizationStatus = 'unmapped' | 'partial' | 'complete' | 'locked' | 'stale';
export type ComparisonRunMode = 'preview' | 'final';
export type ComparisonRunStatus = 'not_generated' | 'preview' | 'final' | 'stale' | 'locked';
export type ApprovalStatus = 'pending' | 'approved' | 'rejected' | 'returned' | 'awaiting_evidence' | 'snoozed';
export type ApprovalType = 'comparison' | 'risk_escalation' | 'policy_exception' | 'override';
export type RiskLevel = 'critical' | 'high' | 'medium' | 'low';
export type ConfidenceLevel = 'high' | 'medium' | 'low';
export type NegotiationStatus = 'not_started' | 'active' | 'bafo_requested' | 'closed';
export type AwardStatus = 'candidate_selected' | 'awaiting_signoff' | 'finalized' | 'protest_active' | 'debrief_sent';
export type HandoffStatus = 'ready' | 'validating' | 'validation_failed' | 'sending' | 'sent' | 'failed';
export type IntegrationHealth = 'healthy' | 'degraded' | 'outage' | 'disabled';
export type BundleStatus = 'draft' | 'finalized' | 'exported';
export type UserStatus = 'active' | 'suspended' | 'pending_invite';
export type PolicyStatus = 'draft' | 'published' | 'archived';
export type TemplateStatus = 'draft' | 'published' | 'archived';
export type NotificationType = 'approval' | 'mention' | 'assignment' | 'deadline' | 'alert' | 'system';
export type NotificationPriority = 'urgent' | 'high' | 'normal' | 'low';

export interface ParsedLineItem {
  id: string;
  submissionId: string;
  description: string;
  quantity: number;
  uom: string;
  unitPrice: number;
  currency: string;
  confidence: number;
  rfqLineId?: string;
  overridden?: boolean;
  mappedToRfqLineId?: string;
  validationWarning?: string;
}

// ─── Users & Auth ───────────────────────────────────────────────────
export const currentUser = {
  id: 'usr-001',
  name: 'Alex Kumar',
  email: 'alex.kumar@acmecorp.com',
  initials: 'AK',
  role: 'Procurement Lead',
  roleId: 'role-buyer',
  avatarColor: 'bg-indigo-500',
  authorityLimit: 250000,
};

export const users = [
  { id: 'usr-001', name: 'Alex Kumar', email: 'alex.kumar@acmecorp.com', initials: 'AK', role: 'Procurement Lead', roleId: 'role-buyer', status: 'active' as UserStatus, authorityLimit: 250000, lastLogin: '2026-03-09T08:30:00Z' },
  { id: 'usr-002', name: 'Sarah Chen', email: 'sarah.chen@acmecorp.com', initials: 'SC', role: 'Senior Buyer', roleId: 'role-buyer', status: 'active' as UserStatus, authorityLimit: 150000, lastLogin: '2026-03-09T07:15:00Z' },
  { id: 'usr-003', name: 'Marcus Webb', email: 'marcus.webb@acmecorp.com', initials: 'MW', role: 'Approver', roleId: 'role-approver', status: 'active' as UserStatus, authorityLimit: 500000, lastLogin: '2026-03-08T16:45:00Z' },
  { id: 'usr-004', name: 'Diana Rodriguez', email: 'diana.r@acmecorp.com', initials: 'DR', role: 'Procurement Manager', roleId: 'role-manager', status: 'active' as UserStatus, authorityLimit: 1000000, lastLogin: '2026-03-09T09:00:00Z' },
  { id: 'usr-005', name: 'James Foster', email: 'james.f@acmecorp.com', initials: 'JF', role: 'Admin', roleId: 'role-admin', status: 'active' as UserStatus, authorityLimit: 0, lastLogin: '2026-03-07T10:00:00Z' },
  { id: 'usr-006', name: 'Lisa Park', email: 'lisa.p@acmecorp.com', initials: 'LP', role: 'Requester', roleId: 'role-requester', status: 'active' as UserStatus, authorityLimit: 50000, lastLogin: '2026-03-08T14:20:00Z' },
  { id: 'usr-007', name: 'Tom Bradley', email: 'tom.b@acmecorp.com', initials: 'TB', role: 'Auditor', roleId: 'role-auditor', status: 'active' as UserStatus, authorityLimit: 0, lastLogin: '2026-03-06T11:30:00Z' },
  { id: 'usr-008', name: 'Nina Gupta', email: 'nina.g@acmecorp.com', initials: 'NG', role: 'Senior Buyer', roleId: 'role-buyer', status: 'suspended' as UserStatus, authorityLimit: 150000, lastLogin: '2026-02-20T09:00:00Z' },
  { id: 'usr-009', name: 'Raj Patel', email: 'raj.p@acmecorp.com', initials: 'RP', role: 'Requester', roleId: 'role-requester', status: 'pending_invite' as UserStatus, authorityLimit: 50000, lastLogin: '' },
];

export const roles = [
  { id: 'role-admin', name: 'Admin', permissions: ['manage_users', 'manage_settings', 'manage_integrations', 'manage_templates', 'manage_policies', 'view_all'] },
  { id: 'role-manager', name: 'Procurement Manager', permissions: ['manage_rfqs', 'approve_high', 'manage_scoring', 'view_reports', 'manage_vendors', 'view_all'] },
  { id: 'role-approver', name: 'Approver', permissions: ['approve', 'view_rfqs', 'view_comparisons', 'view_risk', 'view_trail'] },
  { id: 'role-buyer', name: 'Buyer', permissions: ['manage_rfqs', 'manage_quotes', 'run_comparisons', 'negotiate', 'view_reports'] },
  { id: 'role-requester', name: 'Requester', permissions: ['create_rfqs', 'upload_quotes', 'view_own_rfqs'] },
  { id: 'role-auditor', name: 'Auditor', permissions: ['view_trail', 'view_evidence', 'export_reports', 'view_all'] },
];

export const delegationRules = [
  { id: 'del-001', fromUserId: 'usr-003', toUserId: 'usr-004', scope: 'all', maxAmount: 500000, expiresAt: '2026-06-30' },
  { id: 'del-002', fromUserId: 'usr-004', toUserId: 'usr-003', scope: 'comparison', maxAmount: 300000, expiresAt: '' },
];

// ─── Vendors ────────────────────────────────────────────────────────
export const vendors = [
  { id: 'vnd-001', name: 'TechCorp Solutions', contactEmail: 'sales@techcorp.com', country: 'Australia', category: 'IT Hardware', overallScore: 92, riskLevel: 'low' as RiskLevel, sanctionsStatus: 'clear' as const, dueDiligence: 'complete' as const, activeRfqs: 3, totalQuotes: 47, avgResponseDays: 2.1, savingsContribution: 8.2 },
  { id: 'vnd-002', name: 'GlobalSupply Inc.', contactEmail: 'bids@globalsupply.com', country: 'Singapore', category: 'General Supplies', overallScore: 84, riskLevel: 'medium' as RiskLevel, sanctionsStatus: 'clear' as const, dueDiligence: 'complete' as const, activeRfqs: 5, totalQuotes: 62, avgResponseDays: 3.4, savingsContribution: 5.1 },
  { id: 'vnd-003', name: 'FastParts Ltd.', contactEmail: 'quotes@fastparts.co.uk', country: 'United Kingdom', category: 'IT Hardware', overallScore: 78, riskLevel: 'medium' as RiskLevel, sanctionsStatus: 'clear' as const, dueDiligence: 'partial' as const, activeRfqs: 2, totalQuotes: 31, avgResponseDays: 1.8, savingsContribution: 12.4 },
  { id: 'vnd-004', name: 'PrimeSource Co.', contactEmail: 'procurement@primesource.com.au', country: 'Australia', category: 'IT Services', overallScore: 88, riskLevel: 'low' as RiskLevel, sanctionsStatus: 'clear' as const, dueDiligence: 'complete' as const, activeRfqs: 4, totalQuotes: 55, avgResponseDays: 2.5, savingsContribution: 9.7 },
  { id: 'vnd-005', name: 'NovaTech AU', contactEmail: 'tenders@novatech.au', country: 'Australia', category: 'Network Equipment', overallScore: 71, riskLevel: 'high' as RiskLevel, sanctionsStatus: 'flagged' as const, dueDiligence: 'incomplete' as const, activeRfqs: 1, totalQuotes: 18, avgResponseDays: 4.2, savingsContribution: 3.1 },
  { id: 'vnd-006', name: 'Meridian Systems', contactEmail: 'sales@meridian.com', country: 'United States', category: 'Software', overallScore: 86, riskLevel: 'low' as RiskLevel, sanctionsStatus: 'clear' as const, dueDiligence: 'complete' as const, activeRfqs: 2, totalQuotes: 28, avgResponseDays: 2.8, savingsContribution: 6.5 },
];

export const vendorPerformance = [
  { vendorId: 'vnd-001', month: 'Oct', onTimeDelivery: 96, qualityScore: 94, responseRate: 100 },
  { vendorId: 'vnd-001', month: 'Nov', onTimeDelivery: 98, qualityScore: 92, responseRate: 100 },
  { vendorId: 'vnd-001', month: 'Dec', onTimeDelivery: 94, qualityScore: 95, responseRate: 95 },
  { vendorId: 'vnd-001', month: 'Jan', onTimeDelivery: 97, qualityScore: 93, responseRate: 100 },
  { vendorId: 'vnd-001', month: 'Feb', onTimeDelivery: 99, qualityScore: 96, responseRate: 100 },
  { vendorId: 'vnd-002', month: 'Oct', onTimeDelivery: 88, qualityScore: 82, responseRate: 90 },
  { vendorId: 'vnd-002', month: 'Nov', onTimeDelivery: 85, qualityScore: 84, responseRate: 92 },
  { vendorId: 'vnd-002', month: 'Dec', onTimeDelivery: 90, qualityScore: 86, responseRate: 88 },
  { vendorId: 'vnd-002', month: 'Jan', onTimeDelivery: 87, qualityScore: 83, responseRate: 95 },
  { vendorId: 'vnd-002', month: 'Feb', onTimeDelivery: 91, qualityScore: 85, responseRate: 93 },
];

// ─── RFQs ───────────────────────────────────────────────────────────
export const rfqs = [
  { id: 'RFQ-2401', title: 'Server Infrastructure Components', status: 'closed' as RfqStatus, category: 'IT Hardware', owner: 'usr-001', createdAt: '2026-02-15', deadline: '2026-03-01', closedAt: '2026-03-01', vendorCount: 4, quoteCount: 4, lineItemCount: 13, estimatedValue: 245000, comparisonRunId: 'run-001', savings: 12400 },
  { id: 'RFQ-2398', title: 'Office Supplies Q2 2026', status: 'open' as RfqStatus, category: 'General Supplies', owner: 'usr-002', createdAt: '2026-02-20', deadline: '2026-03-15', closedAt: '', vendorCount: 6, quoteCount: 3, lineItemCount: 28, estimatedValue: 42000, comparisonRunId: '', savings: 0 },
  { id: 'RFQ-2395', title: 'Network Equipment Refresh', status: 'closed' as RfqStatus, category: 'Network Equipment', owner: 'usr-001', createdAt: '2026-02-10', deadline: '2026-02-28', closedAt: '2026-02-28', vendorCount: 3, quoteCount: 3, lineItemCount: 9, estimatedValue: 186000, comparisonRunId: 'run-002', savings: 18700 },
  { id: 'RFQ-2391', title: 'Facility Maintenance Services', status: 'open' as RfqStatus, category: 'Facilities', owner: 'usr-006', createdAt: '2026-02-05', deadline: '2026-03-20', closedAt: '', vendorCount: 5, quoteCount: 2, lineItemCount: 15, estimatedValue: 120000, comparisonRunId: '', savings: 0 },
  { id: 'RFQ-2387', title: 'Cloud Software Licenses', status: 'closed' as RfqStatus, category: 'Software', owner: 'usr-002', createdAt: '2026-01-25', deadline: '2026-02-15', closedAt: '2026-02-15', vendorCount: 4, quoteCount: 4, lineItemCount: 6, estimatedValue: 89000, comparisonRunId: 'run-003', savings: 7200 },
  { id: 'RFQ-2383', title: 'Data Center Cooling Systems', status: 'draft' as RfqStatus, category: 'IT Infrastructure', owner: 'usr-001', createdAt: '2026-03-05', deadline: '2026-04-01', closedAt: '', vendorCount: 0, quoteCount: 0, lineItemCount: 8, estimatedValue: 310000, comparisonRunId: '', savings: 0 },
  { id: 'RFQ-2380', title: 'Cybersecurity Audit Services', status: 'archived' as RfqStatus, category: 'Professional Services', owner: 'usr-004', createdAt: '2025-12-10', deadline: '2026-01-10', closedAt: '2026-01-10', vendorCount: 3, quoteCount: 3, lineItemCount: 4, estimatedValue: 75000, comparisonRunId: 'run-004', savings: 5400 },
  { id: 'RFQ-2376', title: 'Ergonomic Office Furniture', status: 'open' as RfqStatus, category: 'Furniture', owner: 'usr-006', createdAt: '2026-03-01', deadline: '2026-03-25', closedAt: '', vendorCount: 4, quoteCount: 1, lineItemCount: 12, estimatedValue: 67000, comparisonRunId: '', savings: 0 },
];

export const rfqLineItems = [
  { id: 'li-001', rfqId: 'RFQ-2401', description: 'Dell PowerEdge R760 Server', quantity: 4, uom: 'each', estimatedUnitPrice: 18500, category: 'Servers' },
  { id: 'li-002', rfqId: 'RFQ-2401', description: '64GB DDR5 ECC RAM Module', quantity: 32, uom: 'each', estimatedUnitPrice: 420, category: 'Memory' },
  { id: 'li-003', rfqId: 'RFQ-2401', description: '1.92TB NVMe SSD Enterprise', quantity: 16, uom: 'each', estimatedUnitPrice: 680, category: 'Storage' },
  { id: 'li-004', rfqId: 'RFQ-2401', description: 'Rack Mount Kit 2U', quantity: 4, uom: 'each', estimatedUnitPrice: 150, category: 'Accessories' },
  { id: 'li-005', rfqId: 'RFQ-2401', description: '10GbE Network Card Dual Port', quantity: 8, uom: 'each', estimatedUnitPrice: 380, category: 'Networking' },
  { id: 'li-006', rfqId: 'RFQ-2401', description: 'Server Installation & Config', quantity: 4, uom: 'each', estimatedUnitPrice: 2200, category: 'Services' },
  { id: 'li-007', rfqId: 'RFQ-2401', description: 'Power Distribution Unit 30A', quantity: 2, uom: 'each', estimatedUnitPrice: 1100, category: 'Power' },
  { id: 'li-008', rfqId: 'RFQ-2401', description: 'UPS Battery Backup 3kVA', quantity: 2, uom: 'each', estimatedUnitPrice: 3400, category: 'Power' },
  { id: 'li-101', rfqId: 'RFQ-2398', description: 'A4 Copy Paper 80gsm', quantity: 500, uom: 'ream', estimatedUnitPrice: 6.50, category: 'Paper' },
  { id: 'li-102', rfqId: 'RFQ-2398', description: 'Ballpoint Pens Blue', quantity: 200, uom: 'box', estimatedUnitPrice: 3.80, category: 'Writing' },
  { id: 'li-103', rfqId: 'RFQ-2398', description: 'Sticky Notes Assorted', quantity: 100, uom: 'pack', estimatedUnitPrice: 4.20, category: 'Stationery' },
  { id: 'li-104', rfqId: 'RFQ-2398', description: 'Whiteboard Markers', quantity: 50, uom: 'pack', estimatedUnitPrice: 12.50, category: 'Writing' },
  { id: 'li-105', rfqId: 'RFQ-2398', description: 'Desk Organizer', quantity: 30, uom: 'each', estimatedUnitPrice: 25.00, category: 'Office' },
  { id: 'li-106', rfqId: 'RFQ-2398', description: 'Filing Folders A4', quantity: 200, uom: 'pack', estimatedUnitPrice: 8.90, category: 'Filing' },
  { id: 'li-107', rfqId: 'RFQ-2398', description: 'Printer Toner Black', quantity: 20, uom: 'cartridge', estimatedUnitPrice: 65.00, category: 'Printing' },
  { id: 'li-108', rfqId: 'RFQ-2398', description: 'USB Flash Drive 32GB', quantity: 50, uom: 'each', estimatedUnitPrice: 12.00, category: 'Accessories' },
];

// ─── Vendor Invitations ─────────────────────────────────────────────
export const vendorInvitations = [
  { id: 'inv-001', rfqId: 'RFQ-2401', vendorId: 'vnd-001', status: 'responded' as const, invitedAt: '2026-02-16', respondedAt: '2026-02-18', channel: 'email', reminderSent: false },
  { id: 'inv-002', rfqId: 'RFQ-2401', vendorId: 'vnd-002', status: 'responded' as const, invitedAt: '2026-02-16', respondedAt: '2026-02-22', channel: 'email', reminderSent: true },
  { id: 'inv-003', rfqId: 'RFQ-2401', vendorId: 'vnd-003', status: 'responded' as const, invitedAt: '2026-02-16', respondedAt: '2026-02-20', channel: 'portal', reminderSent: false },
  { id: 'inv-004', rfqId: 'RFQ-2401', vendorId: 'vnd-004', status: 'responded' as const, invitedAt: '2026-02-16', respondedAt: '2026-02-19', channel: 'email', reminderSent: false },
  { id: 'inv-005', rfqId: 'RFQ-2398', vendorId: 'vnd-002', status: 'responded' as const, invitedAt: '2026-02-21', respondedAt: '2026-02-25', channel: 'email', reminderSent: false },
  { id: 'inv-006', rfqId: 'RFQ-2398', vendorId: 'vnd-003', status: 'invited' as const, invitedAt: '2026-02-21', respondedAt: '', channel: 'portal', reminderSent: true },
  { id: 'inv-007', rfqId: 'RFQ-2398', vendorId: 'vnd-005', status: 'declined' as const, invitedAt: '2026-02-21', respondedAt: '2026-02-23', channel: 'email', reminderSent: false },
];

// ─── Quote Submissions ──────────────────────────────────────────────
export const quoteSubmissions = [
  { id: 'qs-001', rfqId: 'RFQ-2401', vendorId: 'vnd-001', fileName: 'TechCorp_Quote_2401.pdf', status: 'accepted' as QuoteStatus, confidence: 96, uploadedAt: '2026-02-18T10:30:00Z', parsedAt: '2026-02-18T10:32:00Z', lineCount: 8, totalAmount: 189200, warnings: 0, errors: 0 },
  { id: 'qs-002', rfqId: 'RFQ-2401', vendorId: 'vnd-002', fileName: 'GlobalSupply_Bid_2401.xlsx', status: 'accepted' as QuoteStatus, confidence: 88, uploadedAt: '2026-02-22T14:15:00Z', parsedAt: '2026-02-22T14:18:00Z', lineCount: 8, totalAmount: 201400, warnings: 2, errors: 0 },
  { id: 'qs-003', rfqId: 'RFQ-2401', vendorId: 'vnd-003', fileName: 'FastParts_Quotation.pdf', status: 'accepted' as QuoteStatus, confidence: 72, uploadedAt: '2026-02-20T09:00:00Z', parsedAt: '2026-02-20T09:04:00Z', lineCount: 7, totalAmount: 186400, warnings: 3, errors: 1 },
  { id: 'qs-004', rfqId: 'RFQ-2401', vendorId: 'vnd-004', fileName: 'PrimeSource_Proposal_2401.pdf', status: 'accepted' as QuoteStatus, confidence: 94, uploadedAt: '2026-02-19T11:45:00Z', parsedAt: '2026-02-19T11:47:00Z', lineCount: 8, totalAmount: 192800, warnings: 1, errors: 0 },
  { id: 'qs-005', rfqId: 'RFQ-2398', vendorId: 'vnd-002', fileName: 'GlobalSupply_OfficeSup_Q2.xlsx', status: 'parsed' as QuoteStatus, confidence: 91, uploadedAt: '2026-03-05T16:20:00Z', parsedAt: '2026-03-05T16:22:00Z', lineCount: 8, totalAmount: 38700, warnings: 1, errors: 0 },
  { id: 'qs-006', rfqId: 'RFQ-2398', vendorId: 'vnd-006', fileName: 'Meridian_Supplies_Bid.pdf', status: 'processing' as QuoteStatus, confidence: 0, uploadedAt: '2026-03-09T08:00:00Z', parsedAt: '', lineCount: 0, totalAmount: 0, warnings: 0, errors: 0 },
  { id: 'qs-007', rfqId: '', vendorId: '', fileName: 'Unknown_Vendor_Quote.pdf', status: 'pending_assignment' as QuoteStatus, confidence: 0, uploadedAt: '2026-03-08T15:30:00Z', parsedAt: '', lineCount: 0, totalAmount: 0, warnings: 0, errors: 0 },
  { id: 'qs-008', rfqId: 'RFQ-2398', vendorId: 'vnd-005', fileName: 'NovaTech_OfficeSupplies.pdf', status: 'error' as QuoteStatus, confidence: 0, uploadedAt: '2026-03-07T12:00:00Z', parsedAt: '', lineCount: 0, totalAmount: 0, warnings: 0, errors: 0, parseError: 'Document extraction failed: corrupted PDF structure on pages 3-5' },
  { id: 'qs-009', rfqId: 'RFQ-2391', vendorId: 'vnd-004', fileName: 'PrimeSource_Facilities_Q1.xlsx', status: 'parsed' as QuoteStatus, confidence: 54, uploadedAt: '2026-03-06T09:10:00Z', parsedAt: '2026-03-06T09:14:00Z', lineCount: 5, totalAmount: 62400, warnings: 2, errors: 1, validationErrors: ['Missing RFQ context: no fx_lock_date found', 'Vendor identity uncertain — name mismatch with RFQ invitation'] },
];

// ─── Source Lines & Normalization ───────────────────────────────────
export const sourceLines = [
  { id: 'sl-001', quoteId: 'qs-001', description: 'Dell PE R760 Server Unit', quantity: 4, uom: 'EA', unitPrice: 18200, currency: 'AUD', mappedTo: 'li-001', mappingStatus: 'mapped' as const, confidence: 98 },
  { id: 'sl-002', quoteId: 'qs-001', description: '64GB DDR5 ECC Memory', quantity: 32, uom: 'EA', unitPrice: 410, currency: 'AUD', mappedTo: 'li-002', mappingStatus: 'mapped' as const, confidence: 96 },
  { id: 'sl-003', quoteId: 'qs-001', description: '2TB NVMe Enterprise SSD', quantity: 16, uom: 'EA', unitPrice: 650, currency: 'AUD', mappedTo: 'li-003', mappingStatus: 'mapped' as const, confidence: 92 },
  { id: 'sl-004', quoteId: 'qs-003', description: 'Server Rack Kit 2U form factor', quantity: 4, uom: 'PCS', unitPrice: 112, currency: 'GBP', mappedTo: 'li-004', mappingStatus: 'conflict' as const, confidence: 65 },
  { id: 'sl-005', quoteId: 'qs-003', description: 'Dual 10G NIC Card', quantity: 8, uom: 'PCS', unitPrice: 285, currency: 'GBP', mappedTo: '', mappingStatus: 'unmapped' as const, confidence: 58 },
];

export const normalizedItems = [
  { id: 'ni-001', sourceLineId: 'sl-001', taxonomyCode: 'UNSPSC-43211501', normalizedDescription: 'Server, Rack-Mount, 2U', normalizedQty: 4, normalizedUom: 'EA', normalizedUnitPrice: 18200, normalizedCurrency: 'AUD', conversionApplied: false },
  { id: 'ni-002', sourceLineId: 'sl-002', taxonomyCode: 'UNSPSC-43201803', normalizedDescription: 'Memory Module, DDR5, 64GB, ECC', normalizedQty: 32, normalizedUom: 'EA', normalizedUnitPrice: 410, normalizedCurrency: 'AUD', conversionApplied: false },
  { id: 'ni-003', sourceLineId: 'sl-003', taxonomyCode: 'UNSPSC-43201811', normalizedDescription: 'SSD, NVMe, Enterprise, 1.92TB', normalizedQty: 16, normalizedUom: 'EA', normalizedUnitPrice: 650, normalizedCurrency: 'AUD', conversionApplied: false },
];

export const conflicts = [
  { id: 'cf-001', sourceLineId: 'sl-004', type: 'uom_mismatch' as const, description: 'Source UoM "PCS" vs expected "EA"', suggestedResolution: 'Map PCS to EA (1:1)', rfqId: 'RFQ-2401' },
  { id: 'cf-002', sourceLineId: 'sl-005', type: 'no_match' as const, description: 'Cannot map "Dual 10G NIC Card" to any RFQ line item', suggestedResolution: 'Map to "10GbE Network Card Dual Port"', rfqId: 'RFQ-2401' },
];

// ─── Parsed Line Items (per submission) ─────────────────────────────
export const parsedLineItems: ParsedLineItem[] = [
  // qs-001 — TechCorp, RFQ-2401, accepted, 96% confidence, no issues
  { id: 'pl-001-01', submissionId: 'qs-001', description: 'Dell PE R760 Server Unit', quantity: 4, uom: 'EA', unitPrice: 18200, currency: 'AUD', confidence: 98, rfqLineId: 'li-001' },
  { id: 'pl-001-02', submissionId: 'qs-001', description: '64GB DDR5 ECC Memory', quantity: 32, uom: 'EA', unitPrice: 410, currency: 'AUD', confidence: 96, rfqLineId: 'li-002' },
  { id: 'pl-001-03', submissionId: 'qs-001', description: '2TB NVMe Enterprise SSD', quantity: 16, uom: 'EA', unitPrice: 650, currency: 'AUD', confidence: 92, rfqLineId: 'li-003' },
  { id: 'pl-001-04', submissionId: 'qs-001', description: 'Rack Mount Kit 2U', quantity: 4, uom: 'EA', unitPrice: 150, currency: 'AUD', confidence: 95, rfqLineId: 'li-004' },
  { id: 'pl-001-05', submissionId: 'qs-001', description: '10GbE NIC Dual Port', quantity: 8, uom: 'EA', unitPrice: 380, currency: 'AUD', confidence: 94, rfqLineId: 'li-005' },
  { id: 'pl-001-06', submissionId: 'qs-001', description: 'Server Installation & Config', quantity: 4, uom: 'EA', unitPrice: 2200, currency: 'AUD', confidence: 97, rfqLineId: 'li-006' },
  { id: 'pl-001-07', submissionId: 'qs-001', description: 'Power Distribution Unit 30A', quantity: 2, uom: 'EA', unitPrice: 1100, currency: 'AUD', confidence: 96, rfqLineId: 'li-007' },
  { id: 'pl-001-08', submissionId: 'qs-001', description: 'UPS Battery Backup 3kVA', quantity: 2, uom: 'EA', unitPrice: 3400, currency: 'AUD', confidence: 93, rfqLineId: 'li-008' },

  // qs-002 — GlobalSupply, RFQ-2401, accepted, 88%, 2 warnings (UoM mismatches)
  { id: 'pl-002-01', submissionId: 'qs-002', description: 'Dell PowerEdge R760 Server', quantity: 4, uom: 'EA', unitPrice: 19100, currency: 'AUD', confidence: 90, rfqLineId: 'li-001' },
  { id: 'pl-002-02', submissionId: 'qs-002', description: 'DDR5 RAM Module 64GB', quantity: 32, uom: 'EA', unitPrice: 440, currency: 'AUD', confidence: 87, rfqLineId: 'li-002' },
  { id: 'pl-002-03', submissionId: 'qs-002', description: 'NVMe SSD 2TB Enterprise', quantity: 16, uom: 'EA', unitPrice: 700, currency: 'AUD', confidence: 85, rfqLineId: 'li-003' },
  { id: 'pl-002-04', submissionId: 'qs-002', description: 'Rack Mount Kit', quantity: 4, uom: 'PCS', unitPrice: 160, currency: 'AUD', confidence: 78, rfqLineId: 'li-004', validationWarning: 'UoM mismatch: PCS vs each' },
  { id: 'pl-002-05', submissionId: 'qs-002', description: '10GbE Network Card', quantity: 8, uom: 'EA', unitPrice: 350, currency: 'AUD', confidence: 91, rfqLineId: 'li-005' },
  { id: 'pl-002-06', submissionId: 'qs-002', description: 'Installation Service', quantity: 4, uom: 'HRS', unitPrice: 2800, currency: 'AUD', confidence: 76, rfqLineId: 'li-006', validationWarning: 'UoM mismatch: HRS vs each' },
  { id: 'pl-002-07', submissionId: 'qs-002', description: 'PDU Rack Mount 30A', quantity: 2, uom: 'EA', unitPrice: 1200, currency: 'AUD', confidence: 89, rfqLineId: 'li-007' },
  { id: 'pl-002-08', submissionId: 'qs-002', description: 'UPS 3kVA Backup', quantity: 2, uom: 'EA', unitPrice: 3600, currency: 'AUD', confidence: 88, rfqLineId: 'li-008' },

  // qs-003 — FastParts, RFQ-2401, accepted, 72%, 3 warnings, 1 error, some overridden
  { id: 'pl-003-01', submissionId: 'qs-003', description: 'Server Rack Unit 2U', quantity: 4, uom: 'PCS', unitPrice: 14200, currency: 'GBP', confidence: 72, rfqLineId: 'li-001', validationWarning: 'Currency conversion applied (GBP → AUD)' },
  { id: 'pl-003-02', submissionId: 'qs-003', description: 'DDR5 Memory Kit', quantity: 32, uom: 'PCS', unitPrice: 330, currency: 'GBP', confidence: 68, rfqLineId: 'li-002' },
  { id: 'pl-003-03', submissionId: 'qs-003', description: 'SSD NVMe 2TB', quantity: 16, uom: 'PCS', unitPrice: 520, currency: 'GBP', confidence: 70, rfqLineId: 'li-003', validationWarning: 'UoM mismatch: PCS vs each' },
  { id: 'pl-003-04', submissionId: 'qs-003', description: 'Rack Kit 2U Form Factor', quantity: 4, uom: 'PCS', unitPrice: 112, currency: 'GBP', confidence: 65, rfqLineId: 'li-004', overridden: true, mappedToRfqLineId: 'li-004', validationWarning: 'Low confidence mapping — manually confirmed' },
  { id: 'pl-003-05', submissionId: 'qs-003', description: 'Dual 10G NIC Card', quantity: 8, uom: 'PCS', unitPrice: 285, currency: 'GBP', confidence: 58, validationWarning: 'Cannot auto-map to any RFQ line item' },
  { id: 'pl-003-06', submissionId: 'qs-003', description: 'Server Setup Service', quantity: 4, uom: 'PCS', unitPrice: 1800, currency: 'GBP', confidence: 60, rfqLineId: 'li-006', validationWarning: 'Price significantly below market estimate' },
  { id: 'pl-003-07', submissionId: 'qs-003', description: 'Power Distribution Unit', quantity: 2, uom: 'PCS', unitPrice: 890, currency: 'GBP', confidence: 66, rfqLineId: 'li-007' },

  // qs-004 — PrimeSource, RFQ-2401, accepted, 94%, 1 warning, one overridden
  { id: 'pl-004-01', submissionId: 'qs-004', description: 'Dell R760 Server', quantity: 4, uom: 'EA', unitPrice: 18400, currency: 'AUD', confidence: 96, rfqLineId: 'li-001' },
  { id: 'pl-004-02', submissionId: 'qs-004', description: 'DDR5 ECC 64GB Module', quantity: 32, uom: 'EA', unitPrice: 420, currency: 'AUD', confidence: 95, rfqLineId: 'li-002' },
  { id: 'pl-004-03', submissionId: 'qs-004', description: 'NVMe SSD Enterprise 2TB', quantity: 16, uom: 'EA', unitPrice: 660, currency: 'AUD', confidence: 93, rfqLineId: 'li-003' },
  { id: 'pl-004-04', submissionId: 'qs-004', description: '2U Rack Mount Kit', quantity: 4, uom: 'EA', unitPrice: 155, currency: 'AUD', confidence: 94, rfqLineId: 'li-004' },
  { id: 'pl-004-05', submissionId: 'qs-004', description: 'Dual Port 10GbE NIC', quantity: 8, uom: 'EA', unitPrice: 390, currency: 'AUD', confidence: 92, rfqLineId: 'li-005', overridden: true, mappedToRfqLineId: 'li-005' },
  { id: 'pl-004-06', submissionId: 'qs-004', description: 'Server Installation', quantity: 4, uom: 'EA', unitPrice: 2300, currency: 'AUD', confidence: 95, rfqLineId: 'li-006' },
  { id: 'pl-004-07', submissionId: 'qs-004', description: '30A Power Distribution Unit', quantity: 2, uom: 'EA', unitPrice: 1150, currency: 'AUD', confidence: 91, rfqLineId: 'li-007', validationWarning: 'Estimated unit price variance > 5%' },
  { id: 'pl-004-08', submissionId: 'qs-004', description: '3kVA UPS Battery', quantity: 2, uom: 'EA', unitPrice: 3500, currency: 'AUD', confidence: 90, rfqLineId: 'li-008' },

  // qs-005 — GlobalSupply, RFQ-2398 office supplies, parsed, 91%, 1 warning
  { id: 'pl-005-01', submissionId: 'qs-005', description: 'A4 Copy Paper 80gsm', quantity: 500, uom: 'RM', unitPrice: 6.80, currency: 'AUD', confidence: 95, rfqLineId: 'li-101' },
  { id: 'pl-005-02', submissionId: 'qs-005', description: 'Blue Ballpoint Pens', quantity: 200, uom: 'BX', unitPrice: 3.50, currency: 'AUD', confidence: 93, rfqLineId: 'li-102' },
  { id: 'pl-005-03', submissionId: 'qs-005', description: 'Sticky Notes Assorted Pack', quantity: 100, uom: 'PK', unitPrice: 4.40, currency: 'AUD', confidence: 91, rfqLineId: 'li-103' },
  { id: 'pl-005-04', submissionId: 'qs-005', description: 'Whiteboard Marker Set', quantity: 50, uom: 'PK', unitPrice: 11.80, currency: 'AUD', confidence: 89, rfqLineId: 'li-104' },
  { id: 'pl-005-05', submissionId: 'qs-005', description: 'Desktop Organizer Tray', quantity: 30, uom: 'EA', unitPrice: 22.50, currency: 'AUD', confidence: 87, rfqLineId: 'li-105', validationWarning: 'Description partial match — verify item' },
  { id: 'pl-005-06', submissionId: 'qs-005', description: 'A4 Filing Folders', quantity: 200, uom: 'PK', unitPrice: 9.20, currency: 'AUD', confidence: 94, rfqLineId: 'li-106' },
  { id: 'pl-005-07', submissionId: 'qs-005', description: 'HP Toner Cartridge Black', quantity: 20, uom: 'EA', unitPrice: 62.00, currency: 'AUD', confidence: 92, rfqLineId: 'li-107' },
  { id: 'pl-005-08', submissionId: 'qs-005', description: 'USB Flash Drive 32GB', quantity: 50, uom: 'EA', unitPrice: 11.50, currency: 'AUD', confidence: 90, rfqLineId: 'li-108' },

  // qs-009 — PrimeSource, RFQ-2391 facilities, parsed, 54%, quote-level issues
  { id: 'pl-009-01', submissionId: 'qs-009', description: 'HVAC Maintenance Annual', quantity: 1, uom: 'LOT', unitPrice: 28000, currency: 'AUD', confidence: 62, rfqLineId: undefined, validationWarning: 'Cannot auto-map — no matching RFQ line' },
  { id: 'pl-009-02', submissionId: 'qs-009', description: 'Electrical Inspection', quantity: 2, uom: 'EA', unitPrice: 4500, currency: 'AUD', confidence: 55 },
  { id: 'pl-009-03', submissionId: 'qs-009', description: 'Fire Safety Audit', quantity: 1, uom: 'EA', unitPrice: 8200, currency: 'AUD', confidence: 48, validationWarning: 'Low confidence extraction — verify manually' },
  { id: 'pl-009-04', submissionId: 'qs-009', description: 'Plumbing Repairs Package', quantity: 1, uom: 'LOT', unitPrice: 12500, currency: 'AUD', confidence: 51, validationWarning: 'Ambiguous line item description' },
  { id: 'pl-009-05', submissionId: 'qs-009', description: 'Pest Control Quarterly', quantity: 4, uom: 'EA', unitPrice: 2300, currency: 'AUD', confidence: 58 },
];

// ─── Comparison & Scoring ───────────────────────────────────────────
export const comparisonRuns = [
  { id: 'run-001', rfqId: 'RFQ-2401', mode: 'final' as ComparisonRunMode, status: 'locked' as ComparisonRunStatus, scoringModelId: 'sm-001', createdAt: '2026-03-06T09:14:00Z', createdBy: 'usr-001', vendorCount: 4, lineItemCount: 8, totalLowest: 186400, totalRecommended: 189200, recommendedVendorId: 'vnd-004', approvalRequired: true, approvalId: 'apv-001' },
  { id: 'run-002', rfqId: 'RFQ-2395', mode: 'final' as ComparisonRunMode, status: 'final' as ComparisonRunStatus, scoringModelId: 'sm-001', createdAt: '2026-03-04T14:20:00Z', createdBy: 'usr-001', vendorCount: 3, lineItemCount: 9, totalLowest: 168200, totalRecommended: 172500, recommendedVendorId: 'vnd-001', approvalRequired: false, approvalId: '' },
  { id: 'run-003', rfqId: 'RFQ-2387', mode: 'final' as ComparisonRunMode, status: 'final' as ComparisonRunStatus, scoringModelId: 'sm-002', createdAt: '2026-02-20T11:00:00Z', createdBy: 'usr-002', vendorCount: 4, lineItemCount: 6, totalLowest: 81200, totalRecommended: 83500, recommendedVendorId: 'vnd-006', approvalRequired: false, approvalId: '' },
];

export const comparisonMatrix = {
  runId: 'run-001',
  rfqId: 'RFQ-2401',
  categories: [
    {
      name: 'Servers', items: [
        { lineItemId: 'li-001', description: 'Dell PowerEdge R760 Server', prices: { 'vnd-001': 72800, 'vnd-002': 76400, 'vnd-003': 71200, 'vnd-004': 73600 }, bestVendor: 'vnd-003' },
      ],
    },
    {
      name: 'Memory', items: [
        { lineItemId: 'li-002', description: '64GB DDR5 ECC RAM Module', prices: { 'vnd-001': 13120, 'vnd-002': 14080, 'vnd-003': 12800, 'vnd-004': 13440 }, bestVendor: 'vnd-003' },
      ],
    },
    {
      name: 'Storage', items: [
        { lineItemId: 'li-003', description: '1.92TB NVMe SSD Enterprise', prices: { 'vnd-001': 10400, 'vnd-002': 11200, 'vnd-003': 10880, 'vnd-004': 10560 }, bestVendor: 'vnd-001' },
      ],
    },
    {
      name: 'Accessories', items: [
        { lineItemId: 'li-004', description: 'Rack Mount Kit 2U', prices: { 'vnd-001': 600, 'vnd-002': 640, 'vnd-003': 580, 'vnd-004': 620 }, bestVendor: 'vnd-003' },
      ],
    },
    {
      name: 'Networking', items: [
        { lineItemId: 'li-005', description: '10GbE Network Card Dual Port', prices: { 'vnd-001': 3040, 'vnd-002': 3200, 'vnd-003': 2960, 'vnd-004': 3120 }, bestVendor: 'vnd-003' },
      ],
    },
    {
      name: 'Services', items: [
        { lineItemId: 'li-006', description: 'Server Installation & Config', prices: { 'vnd-001': 8800, 'vnd-002': 9600, 'vnd-003': 7200, 'vnd-004': 8400 }, bestVendor: 'vnd-003' },
      ],
    },
    {
      name: 'Power', items: [
        { lineItemId: 'li-007', description: 'Power Distribution Unit 30A', prices: { 'vnd-001': 2200, 'vnd-002': 2400, 'vnd-003': 2100, 'vnd-004': 2300 }, bestVendor: 'vnd-003' },
        { lineItemId: 'li-008', description: 'UPS Battery Backup 3kVA', prices: { 'vnd-001': 6800, 'vnd-002': 7200, 'vnd-003': 6400, 'vnd-004': 6600 }, bestVendor: 'vnd-003' },
      ],
    },
  ],
  vendorTotals: { 'vnd-001': 189200, 'vnd-002': 201400, 'vnd-003': 186400, 'vnd-004': 192800 },
  terms: [
    { label: 'Payment Terms', values: { 'vnd-001': 'Net 30', 'vnd-002': 'Net 45', 'vnd-003': 'Net 60', 'vnd-004': 'Net 60' } },
    { label: 'Delivery Lead Time', values: { 'vnd-001': '14 days', 'vnd-002': '21 days', 'vnd-003': '10 days', 'vnd-004': '14 days' } },
    { label: 'Warranty', values: { 'vnd-001': '3 years', 'vnd-002': '2 years', 'vnd-003': '3 years', 'vnd-004': '5 years' } },
    { label: 'Incoterm', values: { 'vnd-001': 'DAP', 'vnd-002': 'CIF', 'vnd-003': 'FCA', 'vnd-004': 'DAP' } },
  ],
};

export const vendorScores = [
  { vendorId: 'vnd-004', name: 'PrimeSource Co.', overall: 91, price: 88, delivery: 90, quality: 95, risk: 87, sustainability: 92, recommended: true },
  { vendorId: 'vnd-001', name: 'TechCorp Solutions', overall: 86, price: 84, delivery: 91, quality: 88, risk: 79, sustainability: 85, recommended: false },
  { vendorId: 'vnd-003', name: 'FastParts Ltd.', overall: 78, price: 92, delivery: 76, quality: 74, risk: 72, sustainability: 68, recommended: false },
  { vendorId: 'vnd-002', name: 'GlobalSupply Inc.', overall: 74, price: 72, delivery: 82, quality: 70, risk: 68, sustainability: 75, recommended: false },
];

export const scoringModels = [
  { id: 'sm-001', name: 'Standard Procurement v3', version: 3, status: 'published' as PolicyStatus, criteria: [
    { dimension: 'Price/LCC', weight: 35, mandatory: true },
    { dimension: 'Delivery', weight: 20, mandatory: true },
    { dimension: 'Quality', weight: 25, mandatory: true },
    { dimension: 'Risk', weight: 10, mandatory: true },
    { dimension: 'Sustainability', weight: 10, mandatory: false },
  ], createdAt: '2026-01-15', updatedAt: '2026-03-01', assignedCategories: ['IT Hardware', 'Network Equipment', 'General Supplies'] },
  { id: 'sm-002', name: 'Software & Services Model v2', version: 2, status: 'published' as PolicyStatus, criteria: [
    { dimension: 'Price/LCC', weight: 30, mandatory: true },
    { dimension: 'Delivery', weight: 15, mandatory: true },
    { dimension: 'Quality', weight: 30, mandatory: true },
    { dimension: 'Risk', weight: 15, mandatory: true },
    { dimension: 'Sustainability', weight: 10, mandatory: false },
  ], createdAt: '2025-11-20', updatedAt: '2026-02-10', assignedCategories: ['Software', 'Professional Services'] },
  { id: 'sm-003', name: 'High-Value Asset Model', version: 1, status: 'draft' as PolicyStatus, criteria: [
    { dimension: 'Price/LCC', weight: 25, mandatory: true },
    { dimension: 'Delivery', weight: 15, mandatory: true },
    { dimension: 'Quality', weight: 20, mandatory: true },
    { dimension: 'Risk', weight: 20, mandatory: true },
    { dimension: 'Sustainability', weight: 20, mandatory: true },
  ], createdAt: '2026-03-05', updatedAt: '2026-03-05', assignedCategories: [] },
];

// ─── Scoring Policies ───────────────────────────────────────────────
export const scoringPolicies = [
  { id: 'sp-001', name: 'General Procurement Policy', version: 4, status: 'published' as PolicyStatus, description: 'Default scoring constraints for standard procurement.', dimensions: [
    { name: 'Price/LCC', weightMin: 25, weightMax: 45, mandatory: true },
    { name: 'Delivery', weightMin: 10, weightMax: 30, mandatory: true },
    { name: 'Quality', weightMin: 15, weightMax: 35, mandatory: true },
    { name: 'Risk', weightMin: 5, weightMax: 20, mandatory: true },
    { name: 'Sustainability', weightMin: 5, weightMax: 25, mandatory: false },
  ], rules: [
    'Price weight must be at least 25%',
    'Sustainability is mandatory for contracts above $200,000',
    'Risk weight cannot exceed Quality weight',
  ], assignedCategories: ['IT Hardware', 'General Supplies', 'Network Equipment', 'Furniture', 'Facilities'], updatedAt: '2026-02-28' },
  { id: 'sp-002', name: 'High-Risk Procurement Policy', version: 2, status: 'published' as PolicyStatus, description: 'Enhanced risk weighting for regulated or high-value procurement.', dimensions: [
    { name: 'Price/LCC', weightMin: 20, weightMax: 35, mandatory: true },
    { name: 'Delivery', weightMin: 10, weightMax: 25, mandatory: true },
    { name: 'Quality', weightMin: 15, weightMax: 30, mandatory: true },
    { name: 'Risk', weightMin: 15, weightMax: 30, mandatory: true },
    { name: 'Sustainability', weightMin: 10, weightMax: 25, mandatory: true },
  ], rules: [
    'Risk weight must be at least 15%',
    'Sustainability is always mandatory',
    'Combined Risk + Sustainability must be at least 30%',
  ], assignedCategories: ['IT Infrastructure', 'Professional Services', 'Software'], updatedAt: '2026-01-20' },
];

// ─── Scenarios ──────────────────────────────────────────────────────
export const scenarios = [
  { id: 'sc-001', rfqId: 'RFQ-2401', name: 'Baseline', isBaseline: true, assumptions: { volumeMultiplier: 1.0, paymentTermsDays: 30, freightCostPct: 0, fxRateAdjust: 0 }, vendorRankings: [{ vendorId: 'vnd-004', total: 192800, score: 91 }, { vendorId: 'vnd-001', total: 189200, score: 86 }, { vendorId: 'vnd-003', total: 186400, score: 78 }, { vendorId: 'vnd-002', total: 201400, score: 74 }] },
  { id: 'sc-002', rfqId: 'RFQ-2401', name: 'Volume +20%', isBaseline: false, assumptions: { volumeMultiplier: 1.2, paymentTermsDays: 30, freightCostPct: 0, fxRateAdjust: 0 }, vendorRankings: [{ vendorId: 'vnd-003', total: 223680, score: 82 }, { vendorId: 'vnd-004', total: 231360, score: 88 }, { vendorId: 'vnd-001', total: 227040, score: 84 }, { vendorId: 'vnd-002', total: 241680, score: 72 }] },
  { id: 'sc-003', rfqId: 'RFQ-2401', name: 'GBP +5%', isBaseline: false, assumptions: { volumeMultiplier: 1.0, paymentTermsDays: 30, freightCostPct: 0, fxRateAdjust: 5 }, vendorRankings: [{ vendorId: 'vnd-004', total: 192800, score: 91 }, { vendorId: 'vnd-001', total: 189200, score: 86 }, { vendorId: 'vnd-003', total: 195720, score: 74 }, { vendorId: 'vnd-002', total: 201400, score: 74 }] },
];

// ─── Recommendations ────────────────────────────────────────────────
export const recommendations = [
  { id: 'rec-001', runId: 'run-001', rfqId: 'RFQ-2401', vendorId: 'vnd-004', vendorName: 'PrimeSource Co.', confidence: 91, confidenceLevel: 'high' as ConfidenceLevel, overallScore: 91,
    factors: [
      { dimension: 'Quality', score: 95, weight: 25, contribution: 23.75, narrative: 'Highest quality score with 5-year warranty and proven track record.' },
      { dimension: 'Sustainability', score: 92, weight: 10, contribution: 9.2, narrative: 'Strong ESG credentials with carbon-neutral shipping program.' },
      { dimension: 'Delivery', score: 90, weight: 20, contribution: 18.0, narrative: 'Reliable 14-day delivery with 97% on-time rate.' },
      { dimension: 'Price/LCC', score: 88, weight: 35, contribution: 30.8, narrative: 'Competitive pricing, 3.4% above lowest but offset by superior lifecycle cost.' },
      { dimension: 'Risk', score: 87, weight: 10, contribution: 8.7, narrative: 'Low risk profile with clear sanctions status and complete due diligence.' },
    ],
    tradeoffNarrative: 'PrimeSource Co. is recommended despite not being the lowest-priced vendor ($192,800 vs $186,400 from FastParts). The 3.4% price premium is offset by: (1) 5-year warranty vs 3-year from competitors, saving estimated $12,000 in lifecycle costs; (2) significantly higher quality and delivery reliability scores; (3) complete due diligence and low risk profile.',
    overridden: false, overrideVendorId: '', overrideReason: '', overrideBy: '',
  },
];

// ─── Risk & Compliance ──────────────────────────────────────────────
export const riskItems = [
  { id: 'ri-001', rfqId: 'RFQ-2401', vendorId: 'vnd-004', title: 'Single Source Dependency', level: 'critical' as RiskLevel, category: 'commercial', description: '4 of 13 critical line items sole-sourced. Business continuity risk if supply disruption occurs.', status: 'open' as const, escalated: true },
  { id: 'ri-002', rfqId: 'RFQ-2401', vendorId: 'vnd-001', title: 'Missing COI Declaration', level: 'high' as RiskLevel, category: 'policy', description: 'Vendor has a known relationship with a board member. COI declaration not filed for this RFQ.', status: 'open' as const, escalated: false },
  { id: 'ri-003', rfqId: 'RFQ-2401', vendorId: 'vnd-003', title: 'FX Exposure — GBP Pricing', level: 'medium' as RiskLevel, category: 'commercial', description: 'Quote submitted in GBP. AUD/GBP rate movement of ±5% could affect budget by $9,800.', status: 'mitigated' as const, escalated: false },
  { id: 'ri-004', rfqId: 'RFQ-2401', vendorId: 'vnd-005', title: 'Sanctions Screening Flagged', level: 'critical' as RiskLevel, category: 'sanctions', description: 'Vendor flagged in OFAC screening. Potential match with restricted entity list.', status: 'escalated' as const, escalated: true },
  { id: 'ri-005', rfqId: 'RFQ-2395', vendorId: 'vnd-002', title: 'Late Delivery History', level: 'medium' as RiskLevel, category: 'performance', description: 'Vendor has 15% late delivery rate over last 6 months.', status: 'open' as const, escalated: false },
];

export const dueDiligenceChecklists = [
  { vendorId: 'vnd-001', items: [
    { id: 'dd-001', label: 'Financial Stability Verified', status: 'complete' as const, attachmentId: 'doc-010' },
    { id: 'dd-002', label: 'Insurance Certificates', status: 'complete' as const, attachmentId: 'doc-011' },
    { id: 'dd-003', label: 'COI Declaration', status: 'pending' as const, attachmentId: '' },
    { id: 'dd-004', label: 'Reference Checks', status: 'complete' as const, attachmentId: '' },
    { id: 'dd-005', label: 'Adverse Media Review', status: 'complete' as const, attachmentId: '' },
  ]},
  { vendorId: 'vnd-004', items: [
    { id: 'dd-006', label: 'Financial Stability Verified', status: 'complete' as const, attachmentId: 'doc-012' },
    { id: 'dd-007', label: 'Insurance Certificates', status: 'complete' as const, attachmentId: 'doc-013' },
    { id: 'dd-008', label: 'COI Declaration', status: 'complete' as const, attachmentId: 'doc-014' },
    { id: 'dd-009', label: 'Reference Checks', status: 'complete' as const, attachmentId: '' },
    { id: 'dd-010', label: 'Adverse Media Review', status: 'complete' as const, attachmentId: '' },
  ]},
];

export const sanctionsScreenings = [
  { id: 'ss-001', vendorId: 'vnd-005', screenedAt: '2026-03-07T10:00:00Z', result: 'flagged' as const, matchedEntries: ['OFAC SDN List — Potential match: NovaTech Group LLC'], screener: 'usr-001' },
  { id: 'ss-002', vendorId: 'vnd-001', screenedAt: '2026-02-15T09:00:00Z', result: 'clear' as const, matchedEntries: [], screener: 'usr-002' },
  { id: 'ss-003', vendorId: 'vnd-004', screenedAt: '2026-02-15T09:05:00Z', result: 'clear' as const, matchedEntries: [], screener: 'usr-002' },
];

// ─── Approvals ──────────────────────────────────────────────────────
export const approvalItems = [
  { id: 'apv-001', type: 'comparison' as ApprovalType, rfqId: 'RFQ-2401', runId: 'run-001', title: 'Server Infrastructure — Final Comparison Approval', status: 'pending' as ApprovalStatus, priority: 'high' as const, assigneeId: 'usr-003', requesterId: 'usr-001', createdAt: '2026-03-06T09:16:00Z', slaDeadline: '2026-03-08T17:00:00Z', snoozeUntil: '', value: 192800, reasons: ['Single-source dependency flagged as critical', 'Award exceeds $150K threshold', 'Unresolved COI for shortlisted vendor'] },
  { id: 'apv-002', type: 'risk_escalation' as ApprovalType, rfqId: 'RFQ-2401', runId: '', title: 'NovaTech AU — Sanctions Screening Escalation', status: 'pending' as ApprovalStatus, priority: 'urgent' as const, assigneeId: 'usr-004', requesterId: 'usr-001', createdAt: '2026-03-07T10:05:00Z', slaDeadline: '2026-03-08T10:00:00Z', snoozeUntil: '', value: 0, reasons: ['OFAC SDN match flagged during sanctions screening'] },
  { id: 'apv-003', type: 'comparison' as ApprovalType, rfqId: 'RFQ-2395', runId: 'run-002', title: 'Network Equipment — Final Comparison Approval', status: 'approved' as ApprovalStatus, priority: 'normal' as const, assigneeId: 'usr-003', requesterId: 'usr-001', createdAt: '2026-03-04T14:25:00Z', slaDeadline: '2026-03-06T17:00:00Z', snoozeUntil: '', value: 172500, reasons: ['Auto-approved: within authority limits'] },
  { id: 'apv-004', type: 'policy_exception' as ApprovalType, rfqId: 'RFQ-2391', runId: '', title: 'Facility Maintenance — Sustainability Waiver', status: 'pending' as ApprovalStatus, priority: 'normal' as const, assigneeId: 'usr-004', requesterId: 'usr-006', createdAt: '2026-03-08T11:00:00Z', slaDeadline: '2026-03-11T17:00:00Z', snoozeUntil: '', value: 120000, reasons: ['Sustainability dimension waiver requested for facilities category'] },
  { id: 'apv-005', type: 'override' as ApprovalType, rfqId: 'RFQ-2387', runId: 'run-003', title: 'Cloud Licenses — Recommendation Override', status: 'approved' as ApprovalStatus, priority: 'high' as const, assigneeId: 'usr-004', requesterId: 'usr-002', createdAt: '2026-02-22T09:00:00Z', slaDeadline: '2026-02-24T17:00:00Z', snoozeUntil: '', value: 89000, reasons: ['User override: selected alternative vendor with existing contract'] },
];

export const approvalHistory = [
  { id: 'ah-001', approvalId: 'apv-003', action: 'approved', actor: 'usr-003', timestamp: '2026-03-05T10:30:00Z', reason: 'Comparison results are within acceptable parameters. TechCorp recommended with strong delivery score.', evidenceIds: [] },
  { id: 'ah-002', approvalId: 'apv-005', action: 'approved', actor: 'usr-004', timestamp: '2026-02-23T14:15:00Z', reason: 'Override justified — existing Meridian contract offers better integration and support terms.', evidenceIds: ['doc-020'] },
];

// ─── Negotiation ────────────────────────────────────────────────────
export const negotiationRounds = [
  { id: 'neg-001', rfqId: 'RFQ-2401', roundNumber: 1, status: 'closed' as const, startedAt: '2026-03-02T09:00:00Z', deadline: '2026-03-04T17:00:00Z', vendors: ['vnd-001', 'vnd-004'], scope: 'Price and delivery terms', createdBy: 'usr-001',
    offers: [
      { vendorId: 'vnd-001', originalTotal: 189200, counterTotal: 185400, deltaPercent: -2.0, terms: 'Net 45, 12-day delivery', submittedAt: '2026-03-03T10:00:00Z' },
      { vendorId: 'vnd-004', originalTotal: 192800, counterTotal: 188500, deltaPercent: -2.2, terms: 'Net 60, 14-day delivery, 5yr warranty', submittedAt: '2026-03-03T14:30:00Z' },
    ],
  },
  { id: 'neg-002', rfqId: 'RFQ-2401', roundNumber: 2, status: 'active' as const, startedAt: '2026-03-05T09:00:00Z', deadline: '2026-03-07T17:00:00Z', vendors: ['vnd-001', 'vnd-004'], scope: 'Final pricing and warranty extension', createdBy: 'usr-001',
    offers: [
      { vendorId: 'vnd-001', originalTotal: 185400, counterTotal: 183200, deltaPercent: -1.2, terms: 'Net 45, 12-day delivery, 4yr warranty', submittedAt: '2026-03-06T11:00:00Z' },
    ],
  },
];

// ─── Award Decision ─────────────────────────────────────────────────
export const awardDecisions = [
  { id: 'awd-001', rfqId: 'RFQ-2395', status: 'finalized' as AwardStatus, winnerVendorId: 'vnd-001', awardType: 'single' as const, totalValue: 172500, savings: 18700, savingsPercent: 9.8, signedOffBy: 'usr-003', signedOffAt: '2026-03-05T15:00:00Z', standstillDays: 0, protestId: '', splitAllocations: [], debriefsSent: ['vnd-002', 'vnd-003'] },
  { id: 'awd-002', rfqId: 'RFQ-2387', status: 'debrief_sent' as AwardStatus, winnerVendorId: 'vnd-006', awardType: 'single' as const, totalValue: 83500, savings: 7200, savingsPercent: 7.9, signedOffBy: 'usr-004', signedOffAt: '2026-02-25T10:00:00Z', standstillDays: 0, protestId: '', splitAllocations: [], debriefsSent: ['vnd-001', 'vnd-002', 'vnd-004'] },
];

// ─── PO/Contract Handoff ────────────────────────────────────────────
export const handoffRecords = [
  { id: 'ho-001', rfqId: 'RFQ-2395', awardId: 'awd-001', vendorId: 'vnd-001', destination: 'SAP S/4HANA', status: 'sent' as HandoffStatus, validationPassed: true, payload: '{"po_number":"PO-20260305-001","vendor_code":"V10001","total_value":172500,"currency":"AUD","line_items":9}', sentAt: '2026-03-05T16:30:00Z', errorMessage: '' },
  { id: 'ho-002', rfqId: 'RFQ-2387', awardId: 'awd-002', vendorId: 'vnd-006', destination: 'Oracle ERP', status: 'failed' as HandoffStatus, validationPassed: true, payload: '{"po_number":"PO-20260225-002","vendor_code":"V10006","total_value":83500,"currency":"AUD","line_items":6}', sentAt: '2026-02-25T11:00:00Z', errorMessage: 'Connection timeout: Oracle ERP endpoint unreachable after 30s' },
];

// ─── Decision Trail ─────────────────────────────────────────────────
export const decisionTrailEntries = [
  { id: 'dt-001', sequence: 1, rfqId: 'RFQ-2401', eventType: 'rfq_created', actor: 'usr-001', actorRole: 'Procurement Lead', timestamp: '2026-02-15T09:00:00Z', description: 'RFQ created: Server Infrastructure Components', entryHash: 'a1b2c3d4e5f6', previousHash: '—', verified: true, relatedId: 'RFQ-2401' },
  { id: 'dt-002', sequence: 2, rfqId: 'RFQ-2401', eventType: 'rfq_published', actor: 'usr-001', actorRole: 'Procurement Lead', timestamp: '2026-02-16T08:00:00Z', description: 'RFQ published and vendor invitations sent', entryHash: 'b2c3d4e5f6g7', previousHash: 'a1b2c3d4e5f6', verified: true, relatedId: 'RFQ-2401' },
  { id: 'dt-003', sequence: 3, rfqId: 'RFQ-2401', eventType: 'quote_accepted', actor: 'System', actorRole: 'System', timestamp: '2026-02-22T14:20:00Z', description: 'Quote from GlobalSupply Inc. accepted with 88% confidence', entryHash: 'c3d4e5f6g7h8', previousHash: 'b2c3d4e5f6g7', verified: true, relatedId: 'qs-002' },
  { id: 'dt-004', sequence: 4, rfqId: 'RFQ-2401', eventType: 'normalization_locked', actor: 'usr-001', actorRole: 'Procurement Lead', timestamp: '2026-03-06T09:10:00Z', description: 'Normalization data locked for comparison run', entryHash: 'd4e5f6g7h8i9', previousHash: 'c3d4e5f6g7h8', verified: true, relatedId: 'run-001' },
  { id: 'dt-005', sequence: 5, rfqId: 'RFQ-2401', eventType: 'matrix_built', actor: 'System', actorRole: 'System', timestamp: '2026-03-06T09:14:00Z', description: 'Comparison matrix built with 4 vendors, 8 line items', entryHash: 'e5f6g7h8i9j0', previousHash: 'd4e5f6g7h8i9', verified: true, relatedId: 'run-001' },
  { id: 'dt-006', sequence: 6, rfqId: 'RFQ-2401', eventType: 'scoring_computed', actor: 'System', actorRole: 'System', timestamp: '2026-03-06T09:15:00Z', description: 'Weighted scoring computed using Standard Procurement v3 model', entryHash: 'f6g7h8i9j0k1', previousHash: 'e5f6g7h8i9j0', verified: true, relatedId: 'run-001' },
  { id: 'dt-007', sequence: 7, rfqId: 'RFQ-2401', eventType: 'approval_required', actor: 'System', actorRole: 'Approval Engine', timestamp: '2026-03-06T09:16:00Z', description: 'Approval gate triggered: high-risk flags and value threshold exceeded', entryHash: 'g7h8i9j0k1l2', previousHash: 'f6g7h8i9j0k1', verified: true, relatedId: 'apv-001' },
  { id: 'dt-008', sequence: 8, rfqId: 'RFQ-2401', eventType: 'sanctions_flagged', actor: 'usr-001', actorRole: 'Procurement Lead', timestamp: '2026-03-07T10:05:00Z', description: 'NovaTech AU flagged in OFAC sanctions screening', entryHash: 'h8i9j0k1l2m3', previousHash: 'g7h8i9j0k1l2', verified: true, relatedId: 'ss-001' },
];

// ─── Documents & Evidence ───────────────────────────────────────────
export const documents = [
  { id: 'doc-001', name: 'TechCorp_Quote_2401.pdf', type: 'quote', rfqId: 'RFQ-2401', vendorId: 'vnd-001', uploadedAt: '2026-02-18T10:30:00Z', uploadedBy: 'usr-001', size: '2.4 MB', version: 1, tags: ['quote', 'IT Hardware', 'RFQ-2401'], retentionUntil: '2033-02-18' },
  { id: 'doc-002', name: 'GlobalSupply_Bid_2401.xlsx', type: 'quote', rfqId: 'RFQ-2401', vendorId: 'vnd-002', uploadedAt: '2026-02-22T14:15:00Z', uploadedBy: 'usr-001', size: '1.8 MB', version: 1, tags: ['quote', 'IT Hardware', 'RFQ-2401'], retentionUntil: '2033-02-22' },
  { id: 'doc-003', name: 'FastParts_Quotation.pdf', type: 'quote', rfqId: 'RFQ-2401', vendorId: 'vnd-003', uploadedAt: '2026-02-20T09:00:00Z', uploadedBy: 'usr-002', size: '3.1 MB', version: 1, tags: ['quote', 'IT Hardware', 'RFQ-2401'], retentionUntil: '2033-02-20' },
  { id: 'doc-004', name: 'PrimeSource_Proposal_2401.pdf', type: 'quote', rfqId: 'RFQ-2401', vendorId: 'vnd-004', uploadedAt: '2026-02-19T11:45:00Z', uploadedBy: 'usr-001', size: '4.2 MB', version: 1, tags: ['quote', 'IT Hardware', 'RFQ-2401'], retentionUntil: '2033-02-19' },
  { id: 'doc-005', name: 'Comparison_Report_RFQ2401.pdf', type: 'report', rfqId: 'RFQ-2401', vendorId: '', uploadedAt: '2026-03-06T09:20:00Z', uploadedBy: 'System', size: '1.2 MB', version: 1, tags: ['comparison', 'report', 'RFQ-2401'], retentionUntil: '2033-03-06' },
  { id: 'doc-010', name: 'TechCorp_Financial_Statement_2025.pdf', type: 'due_diligence', rfqId: '', vendorId: 'vnd-001', uploadedAt: '2026-01-10T09:00:00Z', uploadedBy: 'usr-002', size: '5.6 MB', version: 1, tags: ['due-diligence', 'financial'], retentionUntil: '2033-01-10' },
  { id: 'doc-011', name: 'TechCorp_Insurance_Certificate.pdf', type: 'due_diligence', rfqId: '', vendorId: 'vnd-001', uploadedAt: '2026-01-10T09:05:00Z', uploadedBy: 'usr-002', size: '0.8 MB', version: 1, tags: ['due-diligence', 'insurance'], retentionUntil: '2033-01-10' },
  { id: 'doc-012', name: 'PrimeSource_Financials_2025.pdf', type: 'due_diligence', rfqId: '', vendorId: 'vnd-004', uploadedAt: '2026-01-12T10:00:00Z', uploadedBy: 'usr-002', size: '4.8 MB', version: 1, tags: ['due-diligence', 'financial'], retentionUntil: '2033-01-12' },
  { id: 'doc-020', name: 'Meridian_Existing_Contract_Terms.pdf', type: 'evidence', rfqId: 'RFQ-2387', vendorId: 'vnd-006', uploadedAt: '2026-02-22T08:50:00Z', uploadedBy: 'usr-002', size: '1.1 MB', version: 1, tags: ['evidence', 'contract', 'override'], retentionUntil: '2033-02-22' },
];

export const evidenceBundles = [
  { id: 'eb-001', name: 'RFQ-2401 Comparison Evidence', scope: 'RFQ-2401', description: 'Complete evidence package for Server Infrastructure comparison and award decision.', status: 'draft' as BundleStatus, documentIds: ['doc-001', 'doc-002', 'doc-003', 'doc-004', 'doc-005'], createdAt: '2026-03-06T10:00:00Z', createdBy: 'usr-001', finalizedAt: '' },
  { id: 'eb-002', name: 'RFQ-2387 Audit Package', scope: 'RFQ-2387', description: 'Full audit trail and evidence for Cloud Software Licenses procurement.', status: 'finalized' as BundleStatus, documentIds: ['doc-020'], createdAt: '2026-02-26T09:00:00Z', createdBy: 'usr-007', finalizedAt: '2026-02-27T14:00:00Z' },
];

// ─── Reports ────────────────────────────────────────────────────────
export const kpiData = {
  activeRfqs: 24, pendingApprovals: 4, totalSavings: 1200000, savingsTarget: 8.4,
  avgCycleTimeDays: 18.2, complianceRate: 94.6, riskExposure: 3,
  spendTrend: [
    { month: 'Sep', value: 420000 }, { month: 'Oct', value: 380000 }, { month: 'Nov', value: 510000 },
    { month: 'Dec', value: 340000 }, { month: 'Jan', value: 490000 }, { month: 'Feb', value: 620000 },
    { month: 'Mar', value: 580000 },
  ],
  spendByCategory: [
    { category: 'IT Hardware', amount: 680000, percentage: 34 },
    { category: 'Software', amount: 420000, percentage: 21 },
    { category: 'Professional Services', amount: 310000, percentage: 15.5 },
    { category: 'General Supplies', amount: 240000, percentage: 12 },
    { category: 'Network Equipment', amount: 200000, percentage: 10 },
    { category: 'Other', amount: 150000, percentage: 7.5 },
  ],
};

export const reportSchedules = [
  { id: 'rs-001', name: 'Weekly Savings Report', type: 'savings', frequency: 'weekly', nextRun: '2026-03-14T08:00:00Z', recipients: ['usr-004', 'usr-001'], format: 'pdf', active: true },
  { id: 'rs-002', name: 'Monthly Compliance Summary', type: 'compliance', frequency: 'monthly', nextRun: '2026-04-01T08:00:00Z', recipients: ['usr-004', 'usr-007'], format: 'pdf', active: true },
  { id: 'rs-003', name: 'Quarterly Executive Dashboard', type: 'executive', frequency: 'quarterly', nextRun: '2026-04-01T08:00:00Z', recipients: ['usr-004'], format: 'excel', active: true },
];

export const savedReportRuns = [
  { id: 'rr-001', scheduleId: 'rs-001', name: 'Weekly Savings Report — Mar 7', generatedAt: '2026-03-07T08:00:00Z', status: 'completed' as const, format: 'pdf', size: '1.4 MB' },
  { id: 'rr-002', scheduleId: 'rs-001', name: 'Weekly Savings Report — Feb 28', generatedAt: '2026-02-28T08:00:00Z', status: 'completed' as const, format: 'pdf', size: '1.3 MB' },
  { id: 'rr-003', scheduleId: 'rs-002', name: 'Monthly Compliance — Feb 2026', generatedAt: '2026-03-01T08:00:00Z', status: 'completed' as const, format: 'pdf', size: '2.8 MB' },
];

// ─── Integrations ───────────────────────────────────────────────────
export const connectorCatalog = [
  { id: 'cat-001', name: 'SAP S/4HANA', type: 'ERP', description: 'Enterprise resource planning connector for PO and contract handoff.', icon: 'server' },
  { id: 'cat-002', name: 'Oracle ERP Cloud', type: 'ERP', description: 'Cloud ERP connector for procurement operations.', icon: 'cloud' },
  { id: 'cat-003', name: 'Microsoft 365', type: 'Email', description: 'Email and calendar integration for notifications and invitations.', icon: 'mail' },
  { id: 'cat-004', name: 'OFAC/Sanctions API', type: 'Compliance', description: 'Sanctions screening service for vendor due diligence.', icon: 'shield' },
  { id: 'cat-005', name: 'Azure AD / Entra ID', type: 'SSO', description: 'Single sign-on and directory integration.', icon: 'key' },
  { id: 'cat-006', name: 'AWS S3', type: 'Storage', description: 'Document storage for evidence vault and attachments.', icon: 'hard-drive' },
];

export const integrations = [
  { id: 'int-001', catalogId: 'cat-001', name: 'SAP S/4HANA — Production', health: 'healthy' as IntegrationHealth, endpoint: 'https://sap-prod.acmecorp.com/api/v2', lastSync: '2026-03-09T08:45:00Z', syncFrequency: '15 min', enabled: true, failureCount: 0 },
  { id: 'int-002', catalogId: 'cat-002', name: 'Oracle ERP — Production', health: 'degraded' as IntegrationHealth, endpoint: 'https://oracle-cloud.acmecorp.com/api/v1', lastSync: '2026-03-09T07:30:00Z', syncFrequency: '30 min', enabled: true, failureCount: 3 },
  { id: 'int-003', catalogId: 'cat-003', name: 'Microsoft 365 — Notifications', health: 'healthy' as IntegrationHealth, endpoint: 'https://graph.microsoft.com/v1.0', lastSync: '2026-03-09T09:00:00Z', syncFrequency: '5 min', enabled: true, failureCount: 0 },
  { id: 'int-004', catalogId: 'cat-004', name: 'OFAC Sanctions API', health: 'healthy' as IntegrationHealth, endpoint: 'https://sanctions-api.acmecorp.com/v1', lastSync: '2026-03-07T10:00:00Z', syncFrequency: 'on-demand', enabled: true, failureCount: 0 },
  { id: 'int-005', catalogId: 'cat-005', name: 'Azure AD — SSO', health: 'healthy' as IntegrationHealth, endpoint: 'https://login.microsoftonline.com', lastSync: '2026-03-09T09:01:00Z', syncFrequency: 'real-time', enabled: true, failureCount: 0 },
  { id: 'int-006', catalogId: 'cat-006', name: 'AWS S3 — Document Storage', health: 'healthy' as IntegrationHealth, endpoint: 'https://s3.ap-southeast-2.amazonaws.com', lastSync: '2026-03-09T08:50:00Z', syncFrequency: 'real-time', enabled: true, failureCount: 0 },
];

export const integrationJobs = [
  { id: 'job-001', integrationId: 'int-002', type: 'sync', status: 'failed' as const, startedAt: '2026-03-09T07:30:00Z', completedAt: '2026-03-09T07:30:45Z', errorMessage: 'Connection timeout after 30s', retryCount: 2, maxRetries: 3 },
  { id: 'job-002', integrationId: 'int-002', type: 'sync', status: 'failed' as const, startedAt: '2026-03-09T07:00:00Z', completedAt: '2026-03-09T07:00:32Z', errorMessage: 'HTTP 503 Service Unavailable', retryCount: 1, maxRetries: 3 },
  { id: 'job-003', integrationId: 'int-001', type: 'handoff', status: 'completed' as const, startedAt: '2026-03-05T16:30:00Z', completedAt: '2026-03-05T16:30:12Z', errorMessage: '', retryCount: 0, maxRetries: 3 },
];

// ─── Notifications ──────────────────────────────────────────────────
export const notifications = [
  { id: 'ntf-001', type: 'approval' as NotificationType, priority: 'urgent' as NotificationPriority, title: 'Sanctions Escalation Requires Approval', body: 'NovaTech AU flagged in OFAC screening. Immediate review required.', link: '/approvals/apv-002', read: false, createdAt: '2026-03-07T10:05:00Z', actorId: 'usr-001' },
  { id: 'ntf-002', type: 'approval' as NotificationType, priority: 'high' as NotificationPriority, title: 'Comparison Approval Pending', body: 'Server Infrastructure (RFQ-2401) final comparison requires your approval.', link: '/approvals/apv-001', read: false, createdAt: '2026-03-06T09:16:00Z', actorId: 'usr-001' },
  { id: 'ntf-003', type: 'deadline' as NotificationType, priority: 'high' as NotificationPriority, title: 'RFQ Deadline Approaching', body: 'Office Supplies Q2 2026 (RFQ-2398) closes in 6 days.', link: '/rfq/RFQ-2398', read: false, createdAt: '2026-03-09T08:00:00Z', actorId: '' },
  { id: 'ntf-004', type: 'assignment' as NotificationType, priority: 'normal' as NotificationPriority, title: 'New Task Assigned', body: 'You have been assigned to review normalization for RFQ-2398.', link: '/normalization', read: true, createdAt: '2026-03-05T16:30:00Z', actorId: 'usr-002' },
  { id: 'ntf-005', type: 'system' as NotificationType, priority: 'normal' as NotificationPriority, title: 'Handoff Failed', body: 'PO handoff to Oracle ERP failed. Retry available.', link: '/handoff', read: true, createdAt: '2026-02-25T11:05:00Z', actorId: '' },
  { id: 'ntf-006', type: 'mention' as NotificationType, priority: 'normal' as NotificationPriority, title: 'Sarah Chen mentioned you', body: '@Alex Kumar please review the FastParts quote confidence score.', link: '/quote-intake', read: true, createdAt: '2026-03-04T14:00:00Z', actorId: 'usr-002' },
  { id: 'ntf-007', type: 'alert' as NotificationType, priority: 'high' as NotificationPriority, title: 'Oracle ERP Integration Degraded', body: '3 consecutive sync failures detected.', link: '/integrations/monitor', read: false, createdAt: '2026-03-09T07:35:00Z', actorId: '' },
];

// ─── Admin Settings ─────────────────────────────────────────────────
export const adminSettings = {
  general: {
    tenantName: 'Acme Corporation',
    timezone: 'Australia/Sydney',
    currency: 'AUD',
    dateFormat: 'DD MMM YYYY',
    fiscalYearStart: 'July',
  },
  workflow: {
    autoApproveThreshold: 50000,
    requireMfaForApprovals: true,
    standstillPeriodDays: 10,
    maxNegotiationRounds: 5,
    allowRfqReopen: true,
  },
  compliance: {
    mandatorySanctionsScreening: true,
    dueDiligenceRequired: true,
    retentionPeriodYears: 7,
    auditHashAlgorithm: 'SHA-256',
    sustainabilityThreshold: 200000,
  },
};

export const featureFlags = [
  { id: 'ff-001', name: 'scenario_simulator', label: 'Scenario Simulator', enabled: true, description: 'Allow users to create and compare what-if scenarios.' },
  { id: 'ff-002', name: 'ai_recommendations', label: 'AI Recommendations', enabled: true, description: 'Show AI-powered vendor recommendations with explainability.' },
  { id: 'ff-003', name: 'bafo_negotiation', label: 'BAFO Negotiation', enabled: true, description: 'Enable Best and Final Offer workflow in negotiations.' },
  { id: 'ff-004', name: 'split_awards', label: 'Split Awards', enabled: false, description: 'Allow splitting award across multiple vendors.' },
  { id: 'ff-005', name: 'protest_period', label: 'Protest/Standstill Period', enabled: true, description: 'Enable standstill period before award finalization.' },
  { id: 'ff-006', name: 'evidence_bundles', label: 'Evidence Bundles', enabled: true, description: 'Allow creation and export of evidence bundles for auditing.' },
];

// ─── RFQ Templates ──────────────────────────────────────────────────
export const rfqTemplates = [
  { id: 'tpl-001', name: 'IT Hardware Standard', category: 'IT Hardware', status: 'published' as TemplateStatus, usageCount: 12, lastUsedAt: '2026-03-05', createdBy: 'usr-005', description: 'Standard template for IT hardware procurement with pre-defined line item categories.', defaultDeadlineDays: 14, lockedFields: ['category', 'terms.warranty'] },
  { id: 'tpl-002', name: 'Professional Services', category: 'Professional Services', status: 'published' as TemplateStatus, usageCount: 8, lastUsedAt: '2026-02-20', createdBy: 'usr-005', description: 'Template for sourcing professional and consulting services.', defaultDeadlineDays: 21, lockedFields: ['category'] },
  { id: 'tpl-003', name: 'Office Supplies Quarterly', category: 'General Supplies', status: 'published' as TemplateStatus, usageCount: 4, lastUsedAt: '2026-02-20', createdBy: 'usr-002', description: 'Quarterly office supplies procurement template with standard items.', defaultDeadlineDays: 14, lockedFields: [] },
  { id: 'tpl-004', name: 'Network Equipment (Draft)', category: 'Network Equipment', status: 'draft' as TemplateStatus, usageCount: 0, lastUsedAt: '', createdBy: 'usr-001', description: 'Draft template for network equipment refresh cycles.', defaultDeadlineDays: 21, lockedFields: [] },
];

// ─── Helper functions ───────────────────────────────────────────────
export function getVendorById(id: string) { return vendors.find(v => v.id === id); }
export function getUserById(id: string) { return users.find(u => u.id === id); }
export function getRfqById(id: string) { return rfqs.find(r => r.id === id); }
export function getParsedLineItemsBySubmissionId(submissionId: string) { return parsedLineItems.filter(pl => pl.submissionId === submissionId); }
export function getRfqLineItemsByRfqId(rfqId: string) { return rfqLineItems.filter(li => li.rfqId === rfqId); }

export function formatCurrency(amount: number, currency = 'AUD'): string {
  return new Intl.NumberFormat('en-AU', { style: 'currency', currency, minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(amount);
}

export function formatDate(dateString: string): string {
  if (!dateString) return '—';
  return new Date(dateString).toLocaleDateString('en-AU', { day: 'numeric', month: 'short', year: 'numeric' });
}

export function formatDateTime(dateString: string): string {
  if (!dateString) return '—';
  return new Date(dateString).toLocaleString('en-AU', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

export function getTimeAgo(dateString: string): string {
  const diff = Date.now() - new Date(dateString).getTime();
  const mins = Math.floor(diff / 60000);
  if (mins < 60) return `${mins}m ago`;
  const hours = Math.floor(mins / 60);
  if (hours < 24) return `${hours}h ago`;
  const days = Math.floor(hours / 24);
  return `${days}d ago`;
}

export const riskLevelConfig = {
  critical: { bg: 'bg-red-50', border: 'border-red-200', text: 'text-red-700', badgeBg: 'bg-red-600', badgeText: 'text-white', dot: 'bg-red-500' },
  high: { bg: 'bg-orange-50', border: 'border-orange-200', text: 'text-orange-700', badgeBg: 'bg-orange-500', badgeText: 'text-white', dot: 'bg-orange-500' },
  medium: { bg: 'bg-amber-50', border: 'border-amber-200', text: 'text-amber-700', badgeBg: 'bg-amber-400', badgeText: 'text-white', dot: 'bg-amber-400' },
  low: { bg: 'bg-slate-50', border: 'border-slate-200', text: 'text-slate-600', badgeBg: 'bg-slate-300', badgeText: 'text-slate-700', dot: 'bg-slate-400' },
};

export const statusColors: Record<string, string> = {
  draft: 'bg-slate-100 text-slate-600',
  open: 'bg-blue-100 text-blue-700',
  closed: 'bg-emerald-100 text-emerald-700',
  archived: 'bg-gray-100 text-gray-500',
  reopened: 'bg-purple-100 text-purple-700',
  processing: 'bg-blue-100 text-blue-700',
  parsed: 'bg-indigo-100 text-indigo-700',
  accepted: 'bg-emerald-100 text-emerald-700',
  rejected: 'bg-red-100 text-red-700',
  error: 'bg-red-100 text-red-700',
  pending_assignment: 'bg-amber-100 text-amber-700',
  pending: 'bg-amber-100 text-amber-700',
  approved: 'bg-emerald-100 text-emerald-700',
  returned: 'bg-purple-100 text-purple-700',
  awaiting_evidence: 'bg-blue-100 text-blue-700',
  snoozed: 'bg-slate-100 text-slate-600',
  healthy: 'bg-emerald-100 text-emerald-700',
  degraded: 'bg-amber-100 text-amber-700',
  outage: 'bg-red-100 text-red-700',
  disabled: 'bg-slate-100 text-slate-500',
  published: 'bg-emerald-100 text-emerald-700',
  active: 'bg-emerald-100 text-emerald-700',
  suspended: 'bg-red-100 text-red-700',
  pending_invite: 'bg-amber-100 text-amber-700',
  complete: 'bg-emerald-100 text-emerald-700',
  partial: 'bg-amber-100 text-amber-700',
  unmapped: 'bg-slate-100 text-slate-600',
  locked: 'bg-indigo-100 text-indigo-700',
  stale: 'bg-orange-100 text-orange-700',
  sent: 'bg-emerald-100 text-emerald-700',
  failed: 'bg-red-100 text-red-700',
  ready: 'bg-blue-100 text-blue-700',
  finalized: 'bg-emerald-100 text-emerald-700',
  exported: 'bg-indigo-100 text-indigo-700',
};
