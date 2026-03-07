import { SlidersHorizontal, BellRing, Keyboard } from "lucide-react";
import { GhostButton, Page, Panel, PrimaryButton, Stat } from "./BlueprintPrimitives";

export function UserPreferences() {
  return (
    <Page
      title="Preferences"
      subtitle="Configure personal defaults for notifications, density, and keyboard workflow behavior."
      actions={
        <>
          <GhostButton label="Reset Preferences" />
          <PrimaryButton label="Save Preferences" />
        </>
      }
    >
      <div className="grid grid-cols-3 gap-4 mb-4">
        <Stat label="Density" value="Standard" tone="brand" />
        <Stat label="Digest Email" value="Daily" tone="neutral" />
        <Stat label="Hotkeys" value="Enabled" tone="success" />
      </div>

      <div className="grid grid-cols-3 gap-4">
        <Panel title="Display" subtitle="Visual defaults">
          <div className="rounded-lg border p-3" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)", fontSize: 12, color: "var(--app-text-main)" }}>
            <SlidersHorizontal size={12} style={{ display: "inline", marginRight: 6 }} />
            Data density preset: Standard
          </div>
        </Panel>
        <Panel title="Notifications" subtitle="Personal alert routing">
          <div className="rounded-lg border p-3" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)", fontSize: 12, color: "var(--app-text-main)" }}>
            <BellRing size={12} style={{ display: "inline", marginRight: 6 }} />
            SLA alerts immediate, digest at 6 PM
          </div>
        </Panel>
        <Panel title="Keyboard" subtitle="Workflow shortcuts">
          <div className="rounded-lg border p-3" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)", fontSize: 12, color: "var(--app-text-main)" }}>
            <Keyboard size={12} style={{ display: "inline", marginRight: 6 }} />
            Global search shortcut: /
          </div>
        </Panel>
      </div>
    </Page>
  );
}
