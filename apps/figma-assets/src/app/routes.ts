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
import { QuoteNormalization } from "./screens/QuoteNormalization";
import { ScenarioSimulator } from "./screens/ScenarioSimulator";
import { RecommendationExplainability } from "./screens/RecommendationExplainability";
import { ApprovalDetail } from "./screens/ApprovalDetail";
import { NegotiationWorkspace } from "./screens/NegotiationWorkspace";
import { AwardDecision } from "./screens/AwardDecision";
import { POContractHandoff } from "./screens/POContractHandoff";
import { VendorProfilePerformance } from "./screens/VendorProfilePerformance";
import { IntegrationMonitor } from "./screens/IntegrationMonitor";
import { UserAccessManagement } from "./screens/UserAccessManagement";
import { AdminSettings } from "./screens/AdminSettings";
import { NotificationCenter } from "./screens/NotificationCenter";
import { AccountProfile } from "./screens/AccountProfile";
import { UserPreferences } from "./screens/UserPreferences";
import { HelpDocs } from "./screens/HelpDocs";

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
      { path: "/normalization/:id?", Component: QuoteNormalization },
      { path: "/quote-comparison/:id?", Component: QuoteComparison },
      { path: "/comparison/:id?", Component: Comparison },
      { path: "/scenarios/:id?", Component: ScenarioSimulator },
      { path: "/recommendation/:id?", Component: RecommendationExplainability },
      { path: "/approvals", Component: Approvals },
      { path: "/approvals/:id", Component: ApprovalDetail },
      { path: "/negotiation/:id?", Component: NegotiationWorkspace },
      { path: "/award/:id?", Component: AwardDecision },
      { path: "/handoff/:id?", Component: POContractHandoff },
      { path: "/vendors/:id?", Component: VendorProfilePerformance },
      { path: "/reports", Component: Reports },
      { path: "/risk", Component: Risk },
      { path: "/decision-trail", Component: DecisionTrail },
      
      { path: "/notifications", Component: NotificationCenter },
      { path: "/tasks", Component: NotificationCenter },
      { path: "/mentions", Component: NotificationCenter },
      { path: "/evidence", Component: EvidenceVault },
      { path: "/users", Component: UserAccessManagement },
      { path: "/scoring", Component: ScoringPolicies },
      { path: "/settings", Component: AdminSettings },
      { path: "/templates", Component: AdminSettings },
      { path: "/integrations", Component: IntegrationMonitor },
      { path: "/flags", Component: AdminSettings },
      { path: "/design-system", Component: DesignSystem },
      { path: "/profile", Component: AccountProfile },
      { path: "/preferences", Component: UserPreferences },
      { path: "/help", Component: HelpDocs },
    ],
  },
]);
