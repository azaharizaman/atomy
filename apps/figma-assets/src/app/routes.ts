import { createBrowserRouter } from "react-router";
import { AppShell } from "./components/layout/AppShell";
import { SignIn } from "./screens/SignIn";
import { Dashboard } from "./screens/Dashboard";
import { RFQList } from "./screens/RFQList";
import { CreateRFQ } from "./screens/CreateRFQ";
import { RFQDetail } from "./screens/RFQDetail";
import { VendorInvitation } from "./screens/VendorInvitation";
import { QuoteIntake } from "./screens/QuoteIntake";
import { QuoteComparison } from "./screens/QuoteComparison";
import { Comparison } from "./screens/Comparison";
import { Approvals } from "./screens/Approvals";
import { Reports } from "./screens/Reports";
import { Risk } from "./screens/Risk";
import { DecisionTrail } from "./screens/DecisionTrail";
import { ScoringPolicies } from "./screens/ScoringPolicies";
import { EvidenceVault } from "./screens/EvidenceVault";
import { DesignSystem } from "./screens/DesignSystem";

export const router = createBrowserRouter([
  {
    path: "/",
    Component: SignIn,
  },
  {
    Component: AppShell,
    children: [
      { path: "/dashboard", Component: Dashboard },
      { path: "/rfqs", Component: RFQList },
      { path: "/rfqs/create", Component: CreateRFQ },
      { path: "/rfqs/:id", Component: RFQDetail },
      { path: "/rfqs/:id/vendors", Component: VendorInvitation },
      { path: "/quote-intake", Component: QuoteIntake },
      // Placeholder routes for nav items
      { path: "/quote-comparison/:id?", Component: QuoteComparison },
      { path: "/comparison/:id?", Component: Comparison },
      { path: "/approvals", Component: Approvals },
      { path: "/reports", Component: Reports },
      { path: "/risk", Component: Risk },
      { path: "/decision-trail", Component: DecisionTrail },
      
      { path: "/notifications", Component: Dashboard },
      { path: "/tasks", Component: Dashboard },
      { path: "/mentions", Component: Dashboard },
      { path: "/evidence", Component: EvidenceVault },
      { path: "/users", Component: Dashboard },
      { path: "/scoring", Component: ScoringPolicies },
      { path: "/templates", Component: Dashboard },
      { path: "/integrations", Component: Dashboard },
      { path: "/flags", Component: Dashboard },
      { path: "/design-system", Component: DesignSystem },
      { path: "/profile", Component: Dashboard },
      { path: "/preferences", Component: Dashboard },
      { path: "/help", Component: Dashboard },
    ],
  },
]);
