import {
  Bell,
  ClipboardList,
  FileSearch,
  Gauge,
  Gavel,
  Landmark,
  LayoutDashboard,
  ShieldCheck,
  Table2,
  Users
} from "lucide-react";

import { type NavSection } from "@/types/navigation";

export const navigationSections: readonly NavSection[] = [
  {
    title: "Primary",
    items: [
      { label: "Dashboard", href: "#", icon: LayoutDashboard },
      { label: "RFQs", href: "#", icon: ClipboardList },
      { label: "Quote Intake", href: "#", icon: FileSearch },
      { label: "Comparison Matrix", href: "#", icon: Table2 },
      { label: "Approvals", href: "#", icon: Gavel },
      { label: "Reports", href: "#", icon: Gauge }
    ]
  },
  {
    title: "Governance",
    items: [
      { label: "Risk & Compliance", href: "#", icon: ShieldCheck },
      { label: "Decision Trail", href: "#", icon: Landmark },
      { label: "Notifications", href: "#", icon: Bell },
      { label: "Users & Access", href: "#", icon: Users }
    ]
  }
] as const;
