import { useState } from "react";
import { Send, GitBranchPlus, Minus, Plus } from "lucide-react";
import { Badge, GhostButton, Page, Panel, PrimaryButton, SlideOver, Stat } from "./BlueprintPrimitives";

export function NegotiationWorkspace() {
  const [roundOpen, setRoundOpen] = useState(false);
  const [offerOpen, setOfferOpen] = useState(false);

  return (
    <Page
      title="Negotiation Workspace"
      subtitle="Track negotiation rounds, counter-offer changes, and concession deltas with clear history."
      actions={
        <>
          <GhostButton label="Export Round History" />
          <PrimaryButton label="Finalize Current Round" />
        </>
      }
    >
      <div className="grid grid-cols-4 gap-4 mb-4">
        <Stat label="Current Round" value="Round 3" tone="brand" />
        <Stat label="Total Concession" value="-4.2%" tone="success" />
        <Stat label="Lead-time Change" value="+3 days" tone="warning" />
        <Stat label="Status" value="Active" tone="warning" />
      </div>

      <div className="grid grid-cols-12 gap-4">
        <div className="col-span-7">
          <Panel title="Negotiation Timeline" subtitle="Chronological round history">
            <div className="space-y-2">
              {[
                ["Round 1", "Vendor opened at $412,000", "Closed", "success"],
                ["Round 2", "Counter submitted at $404,800", "Closed", "success"],
                ["Round 3", "Vendor revised to $398,200", "Active", "warning"],
              ].map((round) => (
                <div key={round[0]} className="rounded-lg border p-3" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
                  <div className="flex items-center justify-between mb-1">
                    <span style={{ fontSize: 13, fontWeight: 700, color: "var(--app-text-strong)" }}>{round[0]}</span>
                    <Badge label={round[2]} tone={round[3] as "success"} />
                  </div>
                  <div style={{ fontSize: 12, color: "var(--app-text-muted)" }}>{round[1]}</div>
                </div>
              ))}
            </div>
          </Panel>
        </div>

        <div className="col-span-5 space-y-4">
          <Panel title="Current Counter-Offer" subtitle="Editable offer terms">
            <div className="space-y-2">
              {[
                ["Total Price", "$398,200", "-$6,800"],
                ["Lead Time", "4.5 weeks", "+0.5 weeks"],
                ["Warranty", "24 months", "No change"],
              ].map((row) => (
                <div key={row[0]} className="rounded-lg border p-3 flex items-center justify-between" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
                  <span style={{ fontSize: 12, color: "var(--app-text-muted)" }}>{row[0]}</span>
                  <div style={{ textAlign: "right" }}>
                    <div style={{ fontSize: 12, color: "var(--app-text-main)", fontWeight: 600 }}>{row[1]}</div>
                    <div style={{ fontSize: 11, color: row[2].includes("-") ? "var(--app-success)" : "var(--app-warning)" }}>{row[2]}</div>
                  </div>
                </div>
              ))}
            </div>
          </Panel>

          <Panel title="Concession Delta Summary" subtitle="Buyer vs vendor movement">
            <div className="space-y-2">
              <div className="rounded-lg border p-2" style={{ background: "var(--app-success-tint-10)", borderColor: "var(--app-success-tint-20)", fontSize: 12, color: "var(--app-success)" }}>
                <Minus size={12} style={{ display: "inline", marginRight: 6 }} />Vendor reduced price by 3.3%
              </div>
              <div className="rounded-lg border p-2" style={{ background: "var(--app-warning-tint-10)", borderColor: "var(--app-warning-tint-20)", fontSize: 12, color: "var(--app-warning)" }}>
                <Plus size={12} style={{ display: "inline", marginRight: 6 }} />Delivery timeline increased by 3 days
              </div>
            </div>
          </Panel>

          <div className="flex gap-2 justify-end">
            <button onClick={() => setRoundOpen(true)} className="rounded-lg px-3 py-2" style={{ background: "var(--app-purple-tint-10)", color: "var(--app-accent-purple)", border: "1px solid var(--app-purple-tint-20)", fontSize: 12, fontWeight: 700 }}>
              <GitBranchPlus size={13} style={{ display: "inline", marginRight: 6 }} />
              Launch Round
            </button>
            <button onClick={() => setOfferOpen(true)} className="rounded-lg px-3 py-2" style={{ background: "var(--app-brand-600)", color: "white", fontSize: 12, fontWeight: 700 }}>
              <Send size={13} style={{ display: "inline", marginRight: 6 }} />
              Submit Counter Offer
            </button>
          </div>
        </div>
      </div>

      <SlideOver open={roundOpen} onClose={() => setRoundOpen(false)} title="Launch Negotiation Round" subtitle="Prepare scope and objectives for the next round.">
        <Panel title="Round 4 Plan" subtitle="Proposed focus">
          <ul style={{ margin: 0, paddingLeft: 18, fontSize: 12, color: "var(--app-text-main)", lineHeight: 1.7 }}>
            <li>Target 1.5% additional price reduction</li>
            <li>Maintain delivery within 4.5 weeks</li>
            <li>Request extended spare parts coverage</li>
          </ul>
        </Panel>
      </SlideOver>

      <SlideOver open={offerOpen} onClose={() => setOfferOpen(false)} title="Submit Counter Offer" subtitle="Review and send current offer package.">
        <Panel title="Submission Summary" subtitle="To be sent to vendor portal">
          <div style={{ fontSize: 12, color: "var(--app-text-main)", lineHeight: 1.7 }}>
            Proposed total: <strong>$396,800</strong><br />
            Delivery target: <strong>4.5 weeks</strong><br />
            Expiry: <strong>72 hours</strong>
          </div>
        </Panel>
        <PrimaryButton label="Confirm Submission" />
      </SlideOver>
    </Page>
  );
}
