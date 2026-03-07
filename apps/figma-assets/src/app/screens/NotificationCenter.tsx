import { useMemo, useState } from "react";
import { useLocation } from "react-router";
import { Bell, AtSign, ClipboardList, CheckCheck } from "lucide-react";
import { Badge, GhostButton, Page, Panel, PrimaryButton, Stat } from "./BlueprintPrimitives";

const feed = [
  { id: "NTF-101", type: "Alert", urgency: "High", title: "Approval SLA breach risk", source: "APR-2026-019", read: false },
  { id: "NTF-102", type: "Task", urgency: "Medium", title: "Review normalization conflict queue", source: "RFQ-2026-018", read: false },
  { id: "NTF-103", type: "Mention", urgency: "Low", title: "@Sarah please validate risk note", source: "Decision Trail", read: true },
  { id: "NTF-104", type: "Reminder", urgency: "High", title: "Counter-offer expires in 6 hours", source: "NEG-2026-004", read: false },
];

export function NotificationCenter() {
  const location = useLocation();
  const [activeType, setActiveType] = useState<"All" | "Alert" | "Task" | "Mention" | "Reminder">("All");
  const routeDefault = useMemo(() => {
    if (location.pathname.includes("/tasks")) return "Task";
    if (location.pathname.includes("/mentions")) return "Mention";
    return "All";
  }, [location.pathname]);

  const filtered = feed.filter((item) => (activeType === "All" ? true : item.type === activeType));
  const visible = filtered.filter((item) => (routeDefault === "All" ? true : item.type === routeDefault));

  return (
    <Page
      title="Notification Center"
      subtitle="Centralized actionable alerts, assignments, mentions, and deadline reminders."
      actions={
        <>
          <GhostButton label="Notification Settings" />
          <PrimaryButton label="Mark All Read" />
        </>
      }
    >
      <div className="grid grid-cols-4 gap-4 mb-4">
        <Stat label="Unread" value="3" tone="warning" />
        <Stat label="Mentions" value="1" tone="brand" />
        <Stat label="Tasks" value="1" tone="brand" />
        <Stat label="Urgent Alerts" value="2" tone="danger" />
      </div>

      <div className="grid grid-cols-12 gap-4">
        <div className="col-span-8">
          <Panel title="Grouped Feed" subtitle="Filter by type and urgency">
            <div className="flex gap-2 mb-3">
              {(["All", "Alert", "Task", "Mention", "Reminder"] as const).map((type) => (
                <button
                  key={type}
                  onClick={() => setActiveType(type)}
                  className="rounded-lg px-2.5 py-1.5"
                  style={{
                    fontSize: 11,
                    background: activeType === type ? "var(--app-brand-tint-10)" : "var(--app-bg-elevated)",
                    color: activeType === type ? "var(--app-brand-500)" : "var(--app-text-muted)",
                    border: "1px solid var(--app-border-strong)",
                  }}
                >
                  {type}
                </button>
              ))}
            </div>

            <div className="space-y-2">
              {visible.map((item) => (
                <button
                  key={item.id}
                  className="w-full rounded-lg border p-3 text-left"
                  style={{ background: item.read ? "var(--app-bg-elevated)" : "var(--app-brand-tint-4)", borderColor: "var(--app-border-strong)" }}
                >
                  <div className="flex items-center justify-between mb-1">
                    <div className="flex items-center gap-2">
                      {item.type === "Alert" && <Bell size={12} style={{ color: "var(--app-warning)" }} />}
                      {item.type === "Task" && <ClipboardList size={12} style={{ color: "var(--app-brand-500)" }} />}
                      {item.type === "Mention" && <AtSign size={12} style={{ color: "var(--app-accent-purple)" }} />}
                      {item.type === "Reminder" && <Bell size={12} style={{ color: "var(--app-danger)" }} />}
                      <span style={{ fontSize: 12, color: "var(--app-text-main)" }}>{item.title}</span>
                    </div>
                    <Badge label={item.urgency} tone={item.urgency === "High" ? "danger" : item.urgency === "Medium" ? "warning" : "neutral"} />
                  </div>
                  <div style={{ fontSize: 11, color: "var(--app-text-muted)" }}>{item.source}</div>
                </button>
              ))}
            </div>
          </Panel>
        </div>

        <div className="col-span-4 space-y-4">
          <Panel title="Bulk Actions" subtitle="Operate feed at scale">
            <div className="space-y-2">
              <button className="w-full rounded-lg py-2.5" style={{ background: "var(--app-success-tint-10)", color: "var(--app-success)", border: "1px solid var(--app-success-tint-20)", fontSize: 12, fontWeight: 700 }}>
                <CheckCheck size={13} style={{ display: "inline", marginRight: 6 }} />
                Mark Filtered as Read
              </button>
              <button className="w-full rounded-lg py-2.5" style={{ background: "var(--app-bg-elevated)", color: "var(--app-text-muted)", border: "1px solid var(--app-border-strong)", fontSize: 12, fontWeight: 700 }}>
                Snooze Low Priority 24h
              </button>
            </div>
          </Panel>
        </div>
      </div>
    </Page>
  );
}
