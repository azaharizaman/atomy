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
import { ApprovalDetail } from './screens/ApprovalDetail';

export const router = createBrowserRouter([
  {
    path: '/',
    Component: Layout,
    children: [
      { index: true, Component: Dashboard },
      { path: 'rfq/create', Component: CreateRFQ },
      { path: 'quote-intake', Component: QuoteIntakeInbox },
      { path: 'normalization', Component: QuoteNormalizationWorkspace },
      { path: 'comparison', Component: QuoteComparisonMatrix },
      { path: 'scoring', Component: ScoringModelBuilder },
      { path: 'scenarios', Component: ScenarioSimulator },
      { path: 'negotiations', Component: NegotiationWorkspace },
      { path: 'reports', Component: ReportsAnalytics },
      { path: 'users', Component: UserAccessManagement },
      { path: 'risk', Component: RiskComplianceReview },
      { path: 'approvals', Component: ApprovalDetail },
    ],
  },
]);
