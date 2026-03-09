import { createBrowserRouter } from 'react-router';
import { Layout } from './components/Layout';
import { SignIn } from './screens/SignIn';
import { Dashboard } from './screens/Dashboard';
import { RFQList } from './screens/RFQList';
import { CreateRFQ } from './screens/CreateRFQ';
import { RFQDetail } from './screens/RFQDetail';
import { QuoteIntakeInbox } from './screens/QuoteIntakeInbox';
import { QuoteNormalizationWorkspace } from './screens/QuoteNormalizationWorkspace';
import { QuoteComparisonMatrix } from './screens/QuoteComparisonMatrix';
import { ScoringModelBuilder } from './screens/ScoringModelBuilder';
import { ScenarioSimulator } from './screens/ScenarioSimulator';
import { RecommendationExplainability } from './screens/RecommendationExplainability';
import { RiskComplianceReview } from './screens/RiskComplianceReview';
import { ApprovalQueue } from './screens/ApprovalQueue';
import { ApprovalDetail } from './screens/ApprovalDetail';
import { DecisionTrail } from './screens/DecisionTrail';
import { EvidenceVault } from './screens/EvidenceVault';
import { NegotiationWorkspace } from './screens/NegotiationWorkspace';
import { AwardDecision } from './screens/AwardDecision';
import { POContractHandoff } from './screens/POContractHandoff';
import { VendorProfile } from './screens/VendorProfile';
import { ReportsAnalytics } from './screens/ReportsAnalytics';
import { IntegrationMonitor } from './screens/IntegrationMonitor';
import { UserAccessManagement } from './screens/UserAccessManagement';
import { AdminSettings } from './screens/AdminSettings';
import { RFQTemplates } from './screens/RFQTemplates';
import { ScoringPolicies } from './screens/ScoringPolicies';
import { IntegrationsConfig } from './screens/IntegrationsConfig';
import { NotificationCenter } from './screens/NotificationCenter';

export const router = createBrowserRouter([
  {
    path: '/signin',
    Component: SignIn,
  },
  {
    path: '/',
    Component: Layout,
    children: [
      { index: true, Component: Dashboard },
      { path: 'rfqs', Component: RFQList },
      { path: 'rfq/create', Component: CreateRFQ },
      { path: 'rfq/:id', Component: RFQDetail },
      { path: 'quote-intake', Component: QuoteIntakeInbox },
      { path: 'normalization', Component: QuoteNormalizationWorkspace },
      { path: 'comparison', Component: QuoteComparisonMatrix },
      { path: 'scoring', Component: ScoringModelBuilder },
      { path: 'scenarios', Component: ScenarioSimulator },
      { path: 'recommendation', Component: RecommendationExplainability },
      { path: 'risk', Component: RiskComplianceReview },
      { path: 'approvals', Component: ApprovalQueue },
      { path: 'approvals/:id', Component: ApprovalDetail },
      { path: 'decision-trail', Component: DecisionTrail },
      { path: 'evidence-vault', Component: EvidenceVault },
      { path: 'negotiations', Component: NegotiationWorkspace },
      { path: 'award', Component: AwardDecision },
      { path: 'handoff', Component: POContractHandoff },
      { path: 'vendors/:id', Component: VendorProfile },
      { path: 'reports', Component: ReportsAnalytics },
      { path: 'integrations/monitor', Component: IntegrationMonitor },
      { path: 'admin/users', Component: UserAccessManagement },
      { path: 'admin/settings', Component: AdminSettings },
      { path: 'admin/templates', Component: RFQTemplates },
      { path: 'admin/scoring-policies', Component: ScoringPolicies },
      { path: 'admin/integrations', Component: IntegrationsConfig },
      { path: 'notifications', Component: NotificationCenter },
    ],
  },
]);
