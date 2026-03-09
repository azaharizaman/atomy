import { createBrowserRouter } from 'react-router';
import { Layout } from './components/Layout';
import { Dashboard } from './screens/Dashboard';
import { CreateRFQ } from './screens/CreateRFQ';
import { QuoteIntakeInbox } from './screens/QuoteIntakeInbox';
import { QuoteNormalizationWorkspace } from './screens/QuoteNormalizationWorkspace';
import { QuoteComparisonMatrix } from './screens/QuoteComparisonMatrix';
import { ScoringModelBuilder } from './screens/ScoringModelBuilder';
import { ScenarioSimulator } from './screens/ScenarioSimulator';
import { NegotiationWorkspace } from './screens/NegotiationWorkspace';
import { ReportsAnalytics } from './screens/ReportsAnalytics';
import { UserAccessManagement } from './screens/UserAccessManagement';
import { RiskComplianceReview } from './screens/RiskComplianceReview';
import {
  AdminSettingsScreen,
  ApprovalDetailScreen,
  ApprovalQueueScreen,
  AwardDecisionScreen,
  DecisionTrailScreen,
  EvidenceVaultScreen,
  IntegrationMonitorScreen,
  POContractHandoffScreen,
  RecommendationExplainabilityScreen,
  RFQDetailScreen,
  RFQListScreen,
  VendorInvitationScreen,
  VendorPerformanceScreen,
} from './screens/ResearchAlignedScreens';

export const router = createBrowserRouter([
  {
    path: '/',
    Component: Layout,
    children: [
      { index: true, Component: Dashboard },
      { path: 'rfq/list', Component: RFQListScreen },
      { path: 'rfq/create', Component: CreateRFQ },
      { path: 'rfq/detail', Component: RFQDetailScreen },
      { path: 'vendors/invitations', Component: VendorInvitationScreen },
      { path: 'quote-intake', Component: QuoteIntakeInbox },
      { path: 'normalization', Component: QuoteNormalizationWorkspace },
      { path: 'comparison', Component: QuoteComparisonMatrix },
      { path: 'scoring', Component: ScoringModelBuilder },
      { path: 'scenarios', Component: ScenarioSimulator },
      { path: 'recommendation', Component: RecommendationExplainabilityScreen },
      { path: 'negotiations', Component: NegotiationWorkspace },
      { path: 'approvals/queue', Component: ApprovalQueueScreen },
      { path: 'approvals/detail', Component: ApprovalDetailScreen },
      { path: 'award', Component: AwardDecisionScreen },
      { path: 'handoff', Component: POContractHandoffScreen },
      { path: 'decision-trail', Component: DecisionTrailScreen },
      { path: 'vendors/performance', Component: VendorPerformanceScreen },
      { path: 'evidence-vault', Component: EvidenceVaultScreen },
      { path: 'reports', Component: ReportsAnalytics },
      { path: 'integration-monitor', Component: IntegrationMonitorScreen },
      { path: 'users', Component: UserAccessManagement },
      { path: 'risk', Component: RiskComplianceReview },
      { path: 'admin/settings', Component: AdminSettingsScreen },
    ],
  },
]);
