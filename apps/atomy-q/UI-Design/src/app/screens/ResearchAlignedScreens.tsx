import type { ReactNode } from 'react';
import {
  EvidenceTimeline,
  GovernanceControlPanel,
  KpiCard,
  StatusBadge,
  approvalQueue,
  decisionTrail,
  evidenceVault,
  governanceControlSnapshot,
  procurementKpis,
} from '@atomy-q/design-system';

const rfqList = [
  { id: 'RFQ-2401', title: 'Server Infrastructure Components', owner: 'Alex Kumar', stage: 'Evaluation', responses: 4, due: 'Mar 10, 2026' },
  { id: 'RFQ-2408', title: 'Logistics Services FY26', owner: 'Priya Sharma', stage: 'Approvals', responses: 6, due: 'Mar 11, 2026' },
  { id: 'RFQ-2410', title: 'Facility HVAC Upgrade', owner: 'Sarah Chen', stage: 'Intake', responses: 2, due: 'Mar 13, 2026' },
];

const invitations = [
  { vendor: 'PrimeSource Co.', invitedAt: 'Mar 2, 2026', status: 'Accepted', contact: 'ops@primesource.com' },
  { vendor: 'GlobalSupply Inc.', invitedAt: 'Mar 2, 2026', status: 'Accepted', contact: 'biddesk@globalsupply.io' },
  { vendor: 'FastParts Ltd.', invitedAt: 'Mar 3, 2026', status: 'Pending', contact: 'quotes@fastparts.co.uk' },
  { vendor: 'NovaTech AU', invitedAt: 'Mar 4, 2026', status: 'Declined', contact: 'sales@novatechau.com' },
];

const integrationEvents = [
  { system: 'SAP S/4HANA', event: 'Award pushed', timestamp: 'Mar 8, 11:58', status: 'Success' },
  { system: 'Ariba', event: 'Supplier profile sync', timestamp: 'Mar 8, 10:14', status: 'Success' },
  { system: 'Sanctions API', event: 'Re-check at award', timestamp: 'Mar 8, 11:42', status: 'Warning' },
  { system: 'Doc Vault', event: 'Evidence bundle lock', timestamp: 'Mar 8, 11:45', status: 'Success' },
];

const vendorPerformance = [
  { vendor: 'PrimeSource Co.', quality: 96, onTime: 92, disputeRate: '1.2%', riskTier: 'Low' },
  { vendor: 'TechCorp Solutions', quality: 91, onTime: 88, disputeRate: '2.5%', riskTier: 'Medium' },
  { vendor: 'GlobalSupply Inc.', quality: 82, onTime: 79, disputeRate: '4.1%', riskTier: 'High' },
];

function SectionCard({ title, subtitle, children }: { title: string; subtitle?: string; children: ReactNode }) {
  return (
    <section className="bg-white rounded-xl border border-slate-200 shadow-sm">
      <header className="px-5 py-4 border-b border-slate-100">
        <h2 className="text-slate-900">{title}</h2>
        {subtitle ? <p className="text-slate-500 text-xs mt-0.5">{subtitle}</p> : null}
      </header>
      <div className="p-5">{children}</div>
    </section>
  );
}

function pageShell(title: string, subtitle: string, children: ReactNode) {
  return (
    <div className="p-6 space-y-5">
      <div>
        <h1 className="text-slate-900">{title}</h1>
        <p className="text-slate-500 text-sm mt-0.5">{subtitle}</p>
      </div>
      {children}
    </div>
  );
}

export function RFQListScreen() {
  return pageShell(
    'RFQ List',
    'Active and recent sourcing events across Atomy-Q.',
    <>
      <div className="grid grid-cols-4 gap-3">
        {procurementKpis.map((kpi) => (
          <KpiCard key={kpi.label} {...kpi} />
        ))}
      </div>
      <SectionCard title="Active RFQs" subtitle="Prioritized by due date and governance stage">
        <table className="w-full">
          <thead>
            <tr className="text-left text-xs text-slate-500 border-b border-slate-100">
              <th className="py-2">RFQ</th>
              <th>Owner</th>
              <th>Responses</th>
              <th>Due</th>
              <th>Stage</th>
            </tr>
          </thead>
          <tbody>
            {rfqList.map((rfq) => (
              <tr key={rfq.id} className="border-b border-slate-50 text-sm text-slate-700">
                <td className="py-2.5">
                  <div className="font-medium">{rfq.id}</div>
                  <div className="text-xs text-slate-500">{rfq.title}</div>
                </td>
                <td>{rfq.owner}</td>
                <td>{rfq.responses}</td>
                <td>{rfq.due}</td>
                <td>
                  <StatusBadge
                    label={rfq.stage}
                    tone={rfq.stage === 'Approvals' ? 'warning' : rfq.stage === 'Evaluation' ? 'info' : 'neutral'}
                  />
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </SectionCard>
    </>,
  );
}

export function RFQDetailScreen() {
  return pageShell(
    'RFQ Detail',
    'Lot structure, key dates, controls, and supplier progress.',
    <div className="grid grid-cols-3 gap-4">
      <div className="col-span-2 space-y-4">
        <SectionCard title="Lot overview" subtitle="RFQ-2401 · Server Infrastructure Components">
          <div className="grid grid-cols-3 gap-3">
            <div className="bg-slate-50 rounded-lg border border-slate-200 p-3">
              <div className="text-xs text-slate-500">Lots</div>
              <div className="text-xl text-slate-900 font-semibold">3</div>
            </div>
            <div className="bg-slate-50 rounded-lg border border-slate-200 p-3">
              <div className="text-xs text-slate-500">Invited suppliers</div>
              <div className="text-xl text-slate-900 font-semibold">6</div>
            </div>
            <div className="bg-slate-50 rounded-lg border border-slate-200 p-3">
              <div className="text-xs text-slate-500">Responses</div>
              <div className="text-xl text-slate-900 font-semibold">4</div>
            </div>
          </div>
        </SectionCard>
        <SectionCard title="Milestones">
          <div className="space-y-2 text-sm">
            <div className="flex justify-between"><span className="text-slate-500">Publication</span><span>Mar 1, 2026</span></div>
            <div className="flex justify-between"><span className="text-slate-500">Technical opening lock</span><span>Mar 8, 2026 · 09:00 UTC</span></div>
            <div className="flex justify-between"><span className="text-slate-500">Commercial opening lock</span><span>Mar 8, 2026 · 15:00 UTC</span></div>
            <div className="flex justify-between"><span className="text-slate-500">Award target</span><span>Mar 12, 2026</span></div>
          </div>
        </SectionCard>
      </div>
      <GovernanceControlPanel snapshot={governanceControlSnapshot} />
    </div>,
  );
}

export function VendorInvitationScreen() {
  return pageShell(
    'Vendor Invitation Management',
    'Track invite acceptance, reminders, and communication readiness.',
    <SectionCard title="Invitation status">
      <table className="w-full text-sm">
        <thead>
          <tr className="text-left text-xs text-slate-500 border-b border-slate-100">
            <th className="py-2">Vendor</th>
            <th>Contact</th>
            <th>Invited</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          {invitations.map((row) => (
            <tr key={row.vendor} className="border-b border-slate-50">
              <td className="py-2.5 text-slate-700">{row.vendor}</td>
              <td className="text-slate-500">{row.contact}</td>
              <td className="text-slate-500">{row.invitedAt}</td>
              <td>
                <StatusBadge
                  label={row.status}
                  tone={row.status === 'Accepted' ? 'success' : row.status === 'Pending' ? 'warning' : 'neutral'}
                />
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </SectionCard>,
  );
}

export function RecommendationExplainabilityScreen() {
  return pageShell(
    'Recommendation & Explainability',
    'Defensible rationale tied to controls, risk posture, and evidence bundle.',
    <div className="grid grid-cols-3 gap-4">
      <div className="col-span-2 space-y-4">
        <SectionCard title="Award rationale summary">
          <p className="text-sm text-slate-700 leading-relaxed">{governanceControlSnapshot.awardRationaleSummary}</p>
          <div className="mt-3 text-xs text-slate-500">
            Evidence bundle: <span className="text-indigo-600">{governanceControlSnapshot.evidenceBundleLink}</span>
          </div>
        </SectionCard>
        <SectionCard title="Decision factors">
          <div className="space-y-2 text-sm">
            <div className="flex justify-between"><span className="text-slate-500">Commercial score</span><span>89 / 100</span></div>
            <div className="flex justify-between"><span className="text-slate-500">Technical score</span><span>93 / 100</span></div>
            <div className="flex justify-between"><span className="text-slate-500">Risk adjustment</span><span>-4 points (conditional pass)</span></div>
            <div className="flex justify-between"><span className="text-slate-500">Final ranking</span><span>#1 PrimeSource Co.</span></div>
          </div>
        </SectionCard>
      </div>
      <GovernanceControlPanel snapshot={governanceControlSnapshot} />
    </div>,
  );
}

export function ApprovalQueueScreen() {
  return pageShell(
    'Approval Queue',
    'Maker-checker queue with due diligence and fraud signal visibility.',
    <SectionCard title="Pending approvals" subtitle="Ordered by urgency and policy impact">
      <table className="w-full text-sm">
        <thead>
          <tr className="text-left text-xs text-slate-500 border-b border-slate-100">
            <th className="py-2">Approval ID</th>
            <th>RFQ</th>
            <th>Owner</th>
            <th>Due diligence</th>
            <th>Fraud severity</th>
          </tr>
        </thead>
        <tbody>
          {approvalQueue.map((item) => (
            <tr key={item.id} className="border-b border-slate-50">
              <td className="py-2.5 font-medium text-slate-800">{item.id}</td>
              <td>
                <div className="text-slate-700">{item.rfqId}</div>
                <div className="text-xs text-slate-500">{item.title}</div>
              </td>
              <td className="text-slate-600">{item.owner}</td>
              <td><StatusBadge label={item.dueDiligenceStatus} tone={item.dueDiligenceStatus === 'pass' ? 'success' : 'warning'} /></td>
              <td><StatusBadge label={item.fraudSignalSeverity} tone={item.fraudSignalSeverity === 'high' ? 'danger' : 'warning'} /></td>
            </tr>
          ))}
        </tbody>
      </table>
    </SectionCard>,
  );
}

export function ApprovalDetailScreen() {
  return pageShell(
    'Approval Detail',
    'Decision sign-off with exception expiry and compensating controls.',
    <div className="grid grid-cols-3 gap-4">
      <div className="col-span-2 space-y-4">
        <SectionCard title="Approval packet AQ-1411">
          <div className="space-y-2 text-sm">
            <div className="flex justify-between"><span className="text-slate-500">Approver role</span><span>{governanceControlSnapshot.approverRole}</span></div>
            <div className="flex justify-between"><span className="text-slate-500">Exception ID</span><span>{governanceControlSnapshot.exceptionId}</span></div>
            <div className="flex justify-between"><span className="text-slate-500">Expiry date</span><span>{governanceControlSnapshot.expiryDate}</span></div>
            <div className="flex justify-between"><span className="text-slate-500">Compensating control</span><span>Sanctions re-check before award issue</span></div>
          </div>
        </SectionCard>
        <SectionCard title="Committee log">
          <div className="space-y-2 text-sm text-slate-700">
            <div>11:02 · Finance reviewer confirmed budget envelope.</div>
            <div>11:10 · Compliance validated conflict-of-interest declaration.</div>
            <div>11:14 · Procurement lead attached debrief narrative draft.</div>
          </div>
        </SectionCard>
      </div>
      <GovernanceControlPanel snapshot={governanceControlSnapshot} />
    </div>,
  );
}

export function AwardDecisionScreen() {
  return pageShell(
    'Award Decision',
    'Finalize selected vendor and lock pre-award due diligence evidence.',
    <SectionCard title="Award candidate">
      <div className="grid grid-cols-3 gap-3">
        <div className="bg-emerald-50 border border-emerald-200 rounded-lg p-3">
          <div className="text-xs text-emerald-700">Selected vendor</div>
          <div className="text-slate-900 font-semibold mt-1">PrimeSource Co.</div>
        </div>
        <div className="bg-slate-50 border border-slate-200 rounded-lg p-3">
          <div className="text-xs text-slate-500">Award value</div>
          <div className="text-slate-900 font-semibold mt-1">$179,000</div>
        </div>
        <div className="bg-amber-50 border border-amber-200 rounded-lg p-3">
          <div className="text-xs text-amber-700">Due diligence</div>
          <div className="text-slate-900 font-semibold mt-1">{governanceControlSnapshot.dueDiligenceStatus}</div>
        </div>
      </div>
    </SectionCard>,
  );
}

export function POContractHandoffScreen() {
  return pageShell(
    'PO / Contract Handoff',
    'Bridge approved award to ERP and contract lifecycle systems.',
    <SectionCard title="Handoff checklist">
      <div className="space-y-2 text-sm">
        <div className="flex items-center justify-between"><span className="text-slate-600">PO draft payload</span><StatusBadge label="Ready" tone="success" /></div>
        <div className="flex items-center justify-between"><span className="text-slate-600">Contract metadata mapping</span><StatusBadge label="Ready" tone="success" /></div>
        <div className="flex items-center justify-between"><span className="text-slate-600">Sanctions re-check token</span><StatusBadge label="Required" tone="warning" /></div>
      </div>
    </SectionCard>,
  );
}

export function DecisionTrailScreen() {
  return pageShell(
    'Decision Trail',
    'Immutable governance event log for audit and debrief readiness.',
    <SectionCard title="Event ledger">
      <div className="space-y-3">
        {decisionTrail.map((event) => (
          <div key={event.id} className="rounded-lg border border-slate-200 p-3 bg-slate-50">
            <div className="flex justify-between text-xs text-slate-500">
              <span>{event.id}</span>
              <span>{event.happenedAt}</span>
            </div>
            <div className="text-sm text-slate-800 font-medium mt-1">{event.action}</div>
            <div className="text-xs text-slate-500 mt-0.5">{event.actor}</div>
            <p className="text-sm text-slate-600 mt-2 mb-0">{event.details}</p>
          </div>
        ))}
      </div>
    </SectionCard>,
  );
}

export function VendorPerformanceScreen() {
  return pageShell(
    'Vendor Profile & Performance',
    'Historic supplier reliability and risk profile at decision time.',
    <SectionCard title="Performance snapshot">
      <table className="w-full text-sm">
        <thead>
          <tr className="text-left text-xs text-slate-500 border-b border-slate-100">
            <th className="py-2">Vendor</th>
            <th>Quality</th>
            <th>On-time</th>
            <th>Dispute rate</th>
            <th>Risk tier</th>
          </tr>
        </thead>
        <tbody>
          {vendorPerformance.map((row) => (
            <tr key={row.vendor} className="border-b border-slate-50">
              <td className="py-2.5 font-medium text-slate-700">{row.vendor}</td>
              <td>{row.quality}%</td>
              <td>{row.onTime}%</td>
              <td>{row.disputeRate}</td>
              <td><StatusBadge label={row.riskTier} tone={row.riskTier === 'Low' ? 'success' : row.riskTier === 'Medium' ? 'warning' : 'danger'} /></td>
            </tr>
          ))}
        </tbody>
      </table>
    </SectionCard>,
  );
}

export function EvidenceVaultScreen() {
  return pageShell(
    'Documents & Evidence Vault',
    'Evidence-first repository for bid documents, risk checks, and approvals.',
    <EvidenceTimeline items={evidenceVault} />,
  );
}

export function IntegrationMonitorScreen() {
  return pageShell(
    'Integration Monitor',
    'Operational status for ERP, supplier network, and risk engine handoffs.',
    <SectionCard title="Recent integration events">
      <table className="w-full text-sm">
        <thead>
          <tr className="text-left text-xs text-slate-500 border-b border-slate-100">
            <th className="py-2">System</th>
            <th>Event</th>
            <th>Timestamp</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          {integrationEvents.map((row) => (
            <tr key={`${row.system}-${row.event}`} className="border-b border-slate-50">
              <td className="py-2.5 font-medium text-slate-700">{row.system}</td>
              <td className="text-slate-600">{row.event}</td>
              <td className="text-slate-500">{row.timestamp}</td>
              <td><StatusBadge label={row.status} tone={row.status === 'Success' ? 'success' : 'warning'} /></td>
            </tr>
          ))}
        </tbody>
      </table>
    </SectionCard>,
  );
}

export function AdminSettingsScreen() {
  return pageShell(
    'Admin Settings',
    'Policy flags for tender governance, opening locks, and committee workflows.',
    <SectionCard title="Governance policy flags">
      <div className="grid grid-cols-2 gap-3">
        <div className="rounded-lg border border-slate-200 p-3 bg-slate-50">
          <div className="text-xs text-slate-500">committee_mode</div>
          <div className="text-sm text-slate-800 font-semibold mt-1">quorum</div>
        </div>
        <div className="rounded-lg border border-slate-200 p-3 bg-slate-50">
          <div className="text-xs text-slate-500">technical_gate</div>
          <div className="text-sm text-slate-800 font-semibold mt-1">enabled</div>
        </div>
        <div className="rounded-lg border border-slate-200 p-3 bg-slate-50">
          <div className="text-xs text-slate-500">opening lock policy</div>
          <div className="text-sm text-slate-800 font-semibold mt-1">two-envelope enforced</div>
        </div>
        <div className="rounded-lg border border-slate-200 p-3 bg-slate-50">
          <div className="text-xs text-slate-500">waiver approval</div>
          <div className="text-sm text-slate-800 font-semibold mt-1">CPO + Compliance dual sign-off</div>
        </div>
      </div>
    </SectionCard>,
  );
}
