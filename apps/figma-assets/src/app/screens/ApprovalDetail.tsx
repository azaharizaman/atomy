import { useState } from "react";
import { useParams } from "react-router";
import { CheckCircle2, XCircle, FileText, Shield, History, MessageSquare } from "lucide-react";
import { Badge, GhostButton, Page, Panel, PrimaryButton, SlideOver, Stat } from "./BlueprintPrimitives";

export function ApprovalDetail() {
  const { id } = useParams();
  const [confirmOpen, setConfirmOpen] = useState(false);
  const [decision, setDecision] = useState<"approve" | "reject">("approve");

  return (
    <Page
      title={`Approval Detail ${id ? `• ${id}` : ""}`}
      subtitle="Execute approval with complete evidence context, decision history, and mandatory rationale."
      actions={
        <>
          <GhostButton label="Open Related RFQ" />
          <PrimaryButton label="View Full Audit Trail" />
        </>
      }
    >
      <div className="grid grid-cols-4 gap-4 mb-4">
        <Stat label="Status" value="Pending" tone="warning" />
        <Stat label="Recommended Vendor" value="Apex Industrial" tone="brand" />
        <Stat label="Award Amount" value="$165,600" tone="success" />
        <Stat label="Prior Decisions" value="2 approvals" tone="neutral" />
      </div>

      <div className="grid grid-cols-12 gap-4">
        <div className="col-span-8 space-y-4">
          <Panel title="Decision Context" subtitle="Summary and recommendation">
            <p style={{ fontSize: 13, color: "var(--app-text-main)", lineHeight: 1.7 }}>
              Recommendation favors Apex Industrial based on lowest TCO, strong compliance profile, and delivery reliability. One alternative
              vendor offers faster delivery but introduces higher non-price risk.
            </p>
          </Panel>

          <Panel title="Evidence Panel" subtitle="Documents, extraction logs, and risk checks">
            <div className="space-y-2">
              {[
                ["Comparison Matrix Export", "Verified", "success"],
                ["Risk Assessment Report", "Verified", "success"],
                ["Legal Terms Review", "Pending", "warning"],
              ].map((doc) => (
                <div key={doc[0]} className="rounded-lg border p-3 flex items-center justify-between" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
                  <div className="flex items-center gap-2">
                    <FileText size={13} style={{ color: "var(--app-brand-500)" }} />
                    <span style={{ fontSize: 12, color: "var(--app-text-main)" }}>{doc[0]}</span>
                  </div>
                  <Badge label={doc[1]} tone={doc[2] as "success"} />
                </div>
              ))}
            </div>
          </Panel>

          <Panel title="Decision Reason (Mandatory)" subtitle="Will be recorded into immutable trail">
            <div className="rounded-lg border p-3" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
              <p style={{ fontSize: 12, color: "var(--app-text-muted)", lineHeight: 1.7 }}>
                Approving based on best-value ranking and no unresolved critical compliance findings. Risk mitigation terms to be included in contract handoff.
              </p>
            </div>
            <div className="flex gap-2 mt-3">
              <button
                onClick={() => {
                  setDecision("reject");
                  setConfirmOpen(true);
                }}
                className="rounded-lg px-3 py-2"
                style={{ fontSize: 13, fontWeight: 700, color: "var(--app-danger)", background: "var(--app-danger-tint-10)", border: "1px solid var(--app-danger-tint-20)" }}
              >
                <XCircle size={14} style={{ display: "inline", marginRight: 6, verticalAlign: "text-bottom" }} />
                Reject
              </button>
              <button
                onClick={() => {
                  setDecision("approve");
                  setConfirmOpen(true);
                }}
                className="rounded-lg px-3 py-2"
                style={{ fontSize: 13, fontWeight: 700, color: "white", background: "var(--app-success)" }}
              >
                <CheckCircle2 size={14} style={{ display: "inline", marginRight: 6, verticalAlign: "text-bottom" }} />
                Approve
              </button>
            </div>
          </Panel>
        </div>

        <div className="col-span-4 space-y-4">
          <Panel title="Prior Approval History" subtitle="Chronological decision timeline">
            <div className="space-y-2">
              {[
                ["David Martinez", "Approved", "2026-03-05 14:12"],
                ["Emily Wong", "Approved", "2026-03-05 16:45"],
                ["Your decision", "Pending", "Now"],
              ].map((item) => (
                <div key={item[0]} className="rounded-lg border p-3" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
                  <div className="flex items-center justify-between mb-1">
                    <span style={{ fontSize: 12, color: "var(--app-text-main)" }}>{item[0]}</span>
                    <Badge label={item[1]} tone={item[1] === "Approved" ? "success" : "warning"} />
                  </div>
                  <div style={{ fontSize: 11, color: "var(--app-text-muted)" }}>{item[2]}</div>
                </div>
              ))}
            </div>
          </Panel>

          <Panel title="Governance Checks" subtitle="Policy gate conditions">
            <div className="space-y-2">
              {["Budget threshold validated", "Risk escalation reviewed", "Conflict of interest check complete"].map((item) => (
                <div key={item} className="rounded-lg border p-2" style={{ background: "var(--app-success-tint-10)", borderColor: "var(--app-success-tint-20)", fontSize: 12, color: "var(--app-success)" }}>
                  <Shield size={12} style={{ display: "inline", marginRight: 6 }} />
                  {item}
                </div>
              ))}
            </div>
          </Panel>
        </div>
      </div>

      <SlideOver
        open={confirmOpen}
        onClose={() => setConfirmOpen(false)}
        title="Confirm Decision"
        subtitle="Final confirmation required for audit traceability."
      >
        <div className="space-y-3">
          <Panel title="Decision Summary" subtitle="Recorded as immutable event">
            <div className="space-y-2" style={{ fontSize: 12, color: "var(--app-text-main)" }}>
              <div><History size={12} style={{ display: "inline", marginRight: 6, color: "var(--app-brand-500)" }} />Approval ID: {id ?? "APR-2026-001"}</div>
              <div><MessageSquare size={12} style={{ display: "inline", marginRight: 6, color: "var(--app-brand-500)" }} />Reason statement attached</div>
              <div>Action: <strong>{decision === "approve" ? "Approve" : "Reject"}</strong></div>
            </div>
          </Panel>
          <button className="w-full rounded-lg py-2.5" style={{ background: decision === "approve" ? "var(--app-success)" : "var(--app-danger)", color: "white", fontSize: 13, fontWeight: 700 }}>
            Confirm {decision === "approve" ? "Approval" : "Rejection"}
          </button>
        </div>
      </SlideOver>
    </Page>
  );
}
