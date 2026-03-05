export const rfqs = [
  { id: "RFQ-2024-001", title: "Industrial Pumping Equipment", category: "Equipment", status: "Open", owner: "Sarah Chen", vendors: 4, budget: "$420,000", deadline: "2024-02-15", created: "2024-01-12", priority: "High" },
  { id: "RFQ-2024-002", title: "Cloud Infrastructure Services", category: "IT Services", status: "Draft", owner: "Mike Johnson", vendors: 0, budget: "$185,000", deadline: "2024-02-28", created: "2024-01-18", priority: "Medium" },
  { id: "RFQ-2024-003", title: "Office Supplies Q4 Procurement", category: "Office", status: "Closed", owner: "Amy Park", vendors: 6, budget: "$32,500", deadline: "2024-01-20", created: "2023-12-15", priority: "Low" },
  { id: "RFQ-2024-004", title: "Manufacturing Components Batch A", category: "Manufacturing", status: "Awarded", owner: "David Lee", vendors: 5, budget: "$1,240,000", deadline: "2024-01-30", created: "2023-12-01", priority: "Critical" },
  { id: "RFQ-2024-005", title: "IT Hardware Refresh 2024", category: "IT Hardware", status: "Open", owner: "Sarah Chen", vendors: 3, budget: "$310,000", deadline: "2024-03-01", created: "2024-01-20", priority: "High" },
  { id: "RFQ-2024-006", title: "Logistics Services 2025 Contract", category: "Logistics", status: "Draft", owner: "Mike Johnson", vendors: 0, budget: "$890,000", deadline: "2024-03-15", created: "2024-01-22", priority: "High" },
  { id: "RFQ-2024-007", title: "Preventive Maintenance Contracts", category: "Services", status: "Open", owner: "Tom Wilson", vendors: 7, budget: "$145,000", deadline: "2024-02-20", created: "2024-01-08", priority: "Medium" },
  { id: "RFQ-2024-008", title: "Q1 Marketing Materials Print", category: "Marketing", status: "Cancelled", owner: "Amy Park", vendors: 2, budget: "$28,000", deadline: "2024-01-15", created: "2023-12-20", priority: "Low" },
  { id: "RFQ-2024-009", title: "Enterprise Software Licenses Bundle", category: "Software", status: "Open", owner: "David Lee", vendors: 5, budget: "$560,000", deadline: "2024-02-25", created: "2024-01-15", priority: "Critical" },
  { id: "RFQ-2024-010", title: "Security Systems Campus Upgrade", category: "Security", status: "Draft", owner: "Sarah Chen", vendors: 0, budget: "$220,000", deadline: "2024-04-01", created: "2024-01-24", priority: "Medium" },
];

export const vendors = [
  { id: "V001", name: "Apex Industrial Solutions", contact: "james.carter@apexind.com", status: "Responded", invitedDate: "2024-01-14", responseDate: "2024-01-18", channel: "Email", quoteValue: "$398,200", notes: "Submitted full technical and commercial proposal" },
  { id: "V002", name: "TechFlow Dynamics", contact: "priya.mehta@techflow.io", status: "Invited", invitedDate: "2024-01-14", responseDate: null, channel: "Portal", quoteValue: null, notes: "Reminder sent on Jan 16" },
  { id: "V003", name: "GlobalPump Corp", contact: "r.schneider@globalpump.de", status: "Responded", invitedDate: "2024-01-14", responseDate: "2024-01-19", channel: "Email", quoteValue: "$412,000", notes: "Partial quote — missing warranty terms" },
  { id: "V004", name: "Pacific Industrial Co.", contact: "c.tanaka@pacificind.co.jp", status: "Declined", invitedDate: "2024-01-14", responseDate: "2024-01-16", channel: "Email", quoteValue: null, notes: "Capacity constraints cited" },
  { id: "V005", name: "Meridian Equipment Ltd", contact: "b.osei@meridian-eq.com", status: "Not Invited", invitedDate: null, responseDate: null, channel: null, quoteValue: null, notes: "Pending pre-qualification check" },
  { id: "V006", name: "Delta Flow Systems", contact: "e.russo@deltaflow.it", status: "Not Invited", invitedDate: null, responseDate: null, channel: null, quoteValue: null, notes: "Added to shortlist on Jan 20" },
];

export const quoteSubmissions = [
  { id: "QS-001", rfqId: "RFQ-2024-001", vendor: "Apex Industrial Solutions", submittedAt: "2024-01-18 09:32", status: "Accepted", confidence: 94, fileType: "PDF", fileName: "apex_quote_rfq001_v2.pdf", fileSize: "2.4 MB", lineItems: 12, warnings: 0, errors: 0 },
  { id: "QS-002", rfqId: "RFQ-2024-001", vendor: "GlobalPump Corp", submittedAt: "2024-01-19 14:17", status: "Parsed with Warnings", confidence: 71, fileType: "XLSX", fileName: "globalpump_quote_jan2024.xlsx", fileSize: "1.8 MB", lineItems: 11, warnings: 3, errors: 0 },
  { id: "QS-003", rfqId: "RFQ-2024-005", vendor: "Nexus IT Distribution", submittedAt: "2024-01-21 11:05", status: "Processing", confidence: null, fileType: "PDF", fileName: "nexus_hw_quote_2024.pdf", fileSize: "5.1 MB", lineItems: null, warnings: null, errors: null },
  { id: "QS-004", rfqId: "RFQ-2024-009", vendor: "SoftCore Licensing", submittedAt: "2024-01-20 16:44", status: "Rejected", confidence: 38, fileType: "PDF", fileName: "softcore_quote_draft.pdf", fileSize: "0.9 MB", lineItems: 8, warnings: 2, errors: 5 },
  { id: "QS-005", rfqId: "RFQ-2024-007", vendor: "ServicePro Maintenance", submittedAt: "2024-01-22 08:20", status: "Accepted", confidence: 99, fileType: "XLSX", fileName: "servicepro_maintenance_q1.xlsx", fileSize: "1.2 MB", lineItems: 24, warnings: 0, errors: 0 },
  { id: "QS-006", rfqId: "RFQ-2024-007", vendor: "TechCare Solutions", submittedAt: "2024-01-22 13:48", status: "Parsed with Warnings", confidence: 63, fileType: "PDF", fileName: "techcare_proposal_jan.pdf", fileSize: "3.2 MB", lineItems: 19, warnings: 4, errors: 1 },
];

export const savingsTrend = [
  { month: "Aug", savings: 185000, target: 200000 },
  { month: "Sep", savings: 240000, target: 210000 },
  { month: "Oct", savings: 198000, target: 220000 },
  { month: "Nov", savings: 315000, target: 230000 },
  { month: "Dec", savings: 290000, target: 240000 },
  { month: "Jan", savings: 428000, target: 250000 },
];

export const myTasks = [
  { id: "T1", title: "Review GlobalPump Corp quote", type: "Review", rfq: "RFQ-2024-001", priority: "High", due: "Today" },
  { id: "T2", title: "Approve Apex Industrial comparison run", type: "Approval", rfq: "RFQ-2024-001", priority: "Critical", due: "Overdue" },
  { id: "T3", title: "Complete RFQ metadata — IT Hardware", type: "Create", rfq: "RFQ-2024-005", priority: "Medium", due: "Tomorrow" },
  { id: "T4", title: "Invite additional vendors — Logistics", type: "Action", rfq: "RFQ-2024-006", priority: "High", due: "Jan 28" },
  { id: "T5", title: "Publish Scoring Policy v4 for review", type: "Admin", rfq: null, priority: "Medium", due: "Jan 30" },
];

export const riskAlerts = [
  { id: "R1", severity: "Critical", title: "Sanctions check required", description: "GlobalPump Corp flagged against OFAC database. Manual review needed before award.", source: "RFQ-2024-001", time: "2h ago" },
  { id: "R2", severity: "High", title: "SLA breach imminent", description: "Approval on RFQ-2024-004 award decision due in 1h 48m. Escalation will auto-trigger.", source: "RFQ-2024-004", time: "Now" },
  { id: "R3", severity: "Medium", title: "Missing compliance certificate", description: "ServicePro Maintenance has not submitted ISO 9001 certificate required by policy.", source: "RFQ-2024-007", time: "5h ago" },
  { id: "R4", severity: "Low", title: "Budget threshold exceeded", description: "RFQ-2024-009 lowest quote ($527K) exceeds approved budget by 5.9%.", source: "RFQ-2024-009", time: "1d ago" },
];

export const recentComparisons = [
  { id: "CMP-001", rfq: "RFQ-2024-001", title: "Industrial Pumping — Final Run", vendors: 2, status: "Complete", score: 92, runAt: "2024-01-19 15:30", recommended: "Apex Industrial" },
  { id: "CMP-002", rfq: "RFQ-2024-004", title: "Mfg Components — Round 2", vendors: 4, status: "Stale", score: 78, runAt: "2024-01-17 11:00", recommended: "CoreMetal Inc." },
  { id: "CMP-003", rfq: "RFQ-2024-009", title: "Software Licenses — Initial", vendors: 3, status: "Complete", score: 85, runAt: "2024-01-21 09:15", recommended: "SoftCore Licensing" },
];

export const activityTimeline = [
  { id: "A1", action: "Comparison run generated", actor: "System", time: "Jan 19, 15:30", type: "system" },
  { id: "A2", action: "GlobalPump Corp quote accepted", actor: "AI Parser", time: "Jan 19, 14:17", type: "intake" },
  { id: "A3", action: "Apex Industrial quote accepted", actor: "AI Parser", time: "Jan 18, 09:32", type: "intake" },
  { id: "A4", action: "Reminder sent to TechFlow Dynamics", actor: "Sarah Chen", time: "Jan 16, 10:00", type: "action" },
  { id: "A5", action: "Pacific Industrial declined invitation", actor: "Vendor Portal", time: "Jan 16, 08:44", type: "vendor" },
  { id: "A6", action: "Vendor invitations sent (4 vendors)", actor: "Sarah Chen", time: "Jan 14, 09:00", type: "action" },
  { id: "A7", action: "RFQ published & opened", actor: "Sarah Chen", time: "Jan 12, 14:22", type: "system" },
  { id: "A8", action: "RFQ created as draft", actor: "Sarah Chen", time: "Jan 12, 11:05", type: "system" },
];
