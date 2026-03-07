import { UserRound, Mail, ShieldCheck } from "lucide-react";
import { GhostButton, Page, Panel, PrimaryButton, Stat } from "./BlueprintPrimitives";

export function AccountProfile() {
  return (
    <Page
      title="Profile"
      subtitle="Manage your account identity and visibility settings."
      actions={
        <>
          <GhostButton label="View Access History" />
          <PrimaryButton label="Save Profile" />
        </>
      }
    >
      <div className="grid grid-cols-3 gap-4 mb-4">
        <Stat label="Role" value="Buyer" tone="brand" />
        <Stat label="MFA" value="Enabled" tone="success" />
        <Stat label="Last Login" value="Today 09:12" tone="neutral" />
      </div>

      <div className="grid grid-cols-2 gap-4">
        <Panel title="Identity" subtitle="User account details">
          <div className="space-y-2">
            <div className="rounded-lg border p-3" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)", fontSize: 12, color: "var(--app-text-main)" }}>
              <UserRound size={12} style={{ display: "inline", marginRight: 6 }} />
              Sarah Chen
            </div>
            <div className="rounded-lg border p-3" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)", fontSize: 12, color: "var(--app-text-main)" }}>
              <Mail size={12} style={{ display: "inline", marginRight: 6 }} />
              sarah.chen@atomyq.example
            </div>
          </div>
        </Panel>

        <Panel title="Security Notice" subtitle="Account protection state">
          <div className="rounded-lg border p-3" style={{ background: "var(--app-success-tint-10)", borderColor: "var(--app-success-tint-20)", fontSize: 12, color: "var(--app-success)" }}>
            <ShieldCheck size={12} style={{ display: "inline", marginRight: 6 }} />
            MFA and SSO policy are enforced for this account.
          </div>
        </Panel>
      </div>
    </Page>
  );
}
