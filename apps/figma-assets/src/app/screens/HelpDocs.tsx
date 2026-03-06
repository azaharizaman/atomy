import { BookOpenText, LifeBuoy, ExternalLink } from "lucide-react";
import { GhostButton, Page, Panel, PrimaryButton, Stat } from "./BlueprintPrimitives";

export function HelpDocs() {
  return (
    <Page
      title="Help & Docs"
      subtitle="Find workflow guides, governance documentation, and support channels."
      actions={
        <>
          <GhostButton label="Open System Status" />
          <PrimaryButton label="Contact Support" />
        </>
      }
    >
      <div className="grid grid-cols-3 gap-4 mb-4">
        <Stat label="Docs Version" value="v2.4.1" tone="brand" />
        <Stat label="System Status" value="Operational" tone="success" />
        <Stat label="Support SLA" value="< 4h" tone="neutral" />
      </div>

      <div className="grid grid-cols-2 gap-4">
        <Panel title="Documentation Library" subtitle="Guides and references">
          <div className="space-y-2">
            {["RFQ Workflow Guide", "Approval Governance Playbook", "Integration Runbook"].map((doc) => (
              <button key={doc} className="w-full rounded-lg border p-3 text-left" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)", fontSize: 12, color: "var(--app-text-main)" }}>
                <BookOpenText size={12} style={{ display: "inline", marginRight: 6 }} />
                {doc}
              </button>
            ))}
          </div>
        </Panel>

        <Panel title="Support" subtitle="Assistance channels">
          <div className="space-y-2">
            <div className="rounded-lg border p-3" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)", fontSize: 12, color: "var(--app-text-main)" }}>
              <LifeBuoy size={12} style={{ display: "inline", marginRight: 6 }} />
              In-app chat support available
            </div>
            <div className="rounded-lg border p-3" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)", fontSize: 12, color: "var(--app-text-main)" }}>
              <ExternalLink size={12} style={{ display: "inline", marginRight: 6 }} />
              Knowledge base and API docs
            </div>
          </div>
        </Panel>
      </div>
    </Page>
  );
}
