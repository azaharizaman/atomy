import { useMemo, useState } from "react";
import { useLocation } from "react-router";
import { Flag, SlidersHorizontal, LayoutTemplate, ShieldAlert } from "lucide-react";
import { Badge, GhostButton, Page, Panel, PrimaryButton, SlideOver, Stat } from "./BlueprintPrimitives";

type TabId = "policies" | "templates" | "flags" | "workflow";

const tabLabel: Record<TabId, string> = {
  policies: "Policy Thresholds",
  templates: "Templates",
  flags: "Feature Flags",
  workflow: "Default Workflow",
};

export function AdminSettings() {
  const location = useLocation();
  const path = location.pathname;
  const defaultTab = useMemo<TabId>(() => {
    if (path.includes("/templates")) return "templates";
    if (path.includes("/flags")) return "flags";
    return "policies";
  }, [path]);

  const [activeTab, setActiveTab] = useState<TabId>(defaultTab);
  const [confirmOpen, setConfirmOpen] = useState(false);

  return (
    <Page
      title="Admin Settings"
      subtitle="Configure tenant-level governance controls, templates, feature flags, and workflow behavior."
      actions={
        <>
          <GhostButton label="Restore Defaults" />
          <PrimaryButton label="Publish Config" />
        </>
      }
    >
      <div className="grid grid-cols-4 gap-4 mb-4">
        <Stat label="Config State" value="Draft" tone="warning" />
        <Stat label="Published Version" value="v18" tone="brand" />
        <Stat label="Enabled Flags" value="23" tone="success" />
        <Stat label="Pending Changes" value="6" tone="warning" />
      </div>

      <div className="grid grid-cols-12 gap-4">
        <div className="col-span-3">
          <Panel title="Settings Categories" subtitle="Scope">
            <div className="space-y-2">
              {([
                ["policies", "Policy Thresholds", SlidersHorizontal],
                ["templates", "Taxonomy / Templates", LayoutTemplate],
                ["flags", "Feature Flags", Flag],
                ["workflow", "Default Workflow", ShieldAlert],
              ] as const).map((tab) => {
                const Icon = tab[2];
                return (
                  <button
                    key={tab[0]}
                    onClick={() => setActiveTab(tab[0])}
                    className="w-full rounded-lg border p-3 text-left flex items-center gap-2"
                    style={{
                      background: activeTab === tab[0] ? "var(--app-brand-tint-8)" : "var(--app-bg-elevated)",
                      borderColor: activeTab === tab[0] ? "var(--app-brand-tint-20)" : "var(--app-border-strong)",
                    }}
                  >
                    <Icon size={13} style={{ color: "var(--app-brand-500)" }} />
                    <span style={{ fontSize: 12, color: "var(--app-text-main)" }}>{tab[1]}</span>
                  </button>
                );
              })}
            </div>
          </Panel>
        </div>

        <div className="col-span-9 space-y-4">
          <Panel title={tabLabel[activeTab]} subtitle="Editable tenant configuration">
            <div className="grid grid-cols-2 gap-3">
              {activeTab === "policies" && (
                <>
                  <SettingRow label="Auto-escalation threshold" value="$250,000" />
                  <SettingRow label="Risk escalation floor" value="Score ≥ 65" />
                  <SettingRow label="Approval chain policy" value="Manager → Finance → CFO" />
                  <SettingRow label="Split-award guardrail" value="Max 3 vendors" />
                </>
              )}
              {activeTab === "templates" && (
                <>
                  <SettingRow label="Default RFQ template" value="Industrial Equipment v5" />
                  <SettingRow label="Taxonomy set" value="UNSPSC + Internal map" />
                  <SettingRow label="Mandatory clauses" value="Legal Pack B" />
                  <SettingRow label="Scoring template linkage" value="Standard Equipment Q1" />
                </>
              )}
              {activeTab === "flags" && (
                <>
                  <SettingRow label="AI Explainability panel" value="Enabled" />
                  <SettingRow label="Negotiation simulator beta" value="Enabled" />
                  <SettingRow label="Auto-handoff after approval" value="Disabled" />
                  <SettingRow label="Policy-aware reweighting" value="Enabled" />
                </>
              )}
              {activeTab === "workflow" && (
                <>
                  <SettingRow label="Default approval route" value="Sequential" />
                  <SettingRow label="Reminder cadence" value="Every 24h" />
                  <SettingRow label="Default negotiation rounds" value="3" />
                  <SettingRow label="Decision lock window" value="2 hours" />
                </>
              )}
            </div>
          </Panel>

          <Panel title="Publish Controls" subtitle="Draft lifecycle">
            <div className="flex items-center gap-2 mb-3">
              <Badge label="Draft config" tone="warning" />
              <Badge label="Last published v18" tone="brand" />
            </div>
            <div className="flex justify-end">
              <button onClick={() => setConfirmOpen(true)} className="rounded-lg px-3 py-2" style={{ background: "var(--app-brand-600)", color: "white", fontSize: 13, fontWeight: 700 }}>
                Publish Changes
              </button>
            </div>
          </Panel>
        </div>
      </div>

      <SlideOver open={confirmOpen} onClose={() => setConfirmOpen(false)} title="Confirm Deletion / Destructive Change" subtitle="Required when publishing high-impact setting updates.">
        <Panel title="Pending High-Impact Updates" subtitle="Review before publish">
          <ul style={{ margin: 0, paddingLeft: 18, fontSize: 12, color: "var(--app-text-main)", lineHeight: 1.7 }}>
            <li>Risk escalation floor changed from 70 → 65</li>
            <li>Auto-handoff feature flag set to Disabled</li>
            <li>Reminder cadence increased from 12h → 24h</li>
          </ul>
        </Panel>
        <PrimaryButton label="Confirm Publish" />
      </SlideOver>
    </Page>
  );
}

function SettingRow({ label, value }: { label: string; value: string }) {
  return (
    <div className="rounded-lg border p-3" style={{ background: "var(--app-bg-elevated)", borderColor: "var(--app-border-strong)" }}>
      <div style={{ fontSize: 11, color: "var(--app-text-muted)", marginBottom: 4 }}>{label}</div>
      <div style={{ fontSize: 13, color: "var(--app-text-main)", fontWeight: 600 }}>{value}</div>
    </div>
  );
}
