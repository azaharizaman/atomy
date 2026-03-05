import { type LucideIcon } from "lucide-react";

export interface NavItem {
  readonly label: string;
  readonly href: string;
  readonly icon: LucideIcon;
}

export interface NavSection {
  readonly title: string;
  readonly items: readonly NavItem[];
}

export interface QuickAction {
  readonly label: string;
  readonly onClick: () => void;
}
