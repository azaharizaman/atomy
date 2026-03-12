<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Testing\TestResponse;

final class ProtectedEndpointsTest extends ApiTestCase
{
    public function test_all_protected_endpoints_respond_successfully(): void
    {
        $headers = $this->authHeaders();

        foreach ($this->endpointMatrix() as [$method, $uri, $payload]) {
            $response = $this->json($method, $uri, $payload, $headers);

            $this->assertSuccessfulResponse($method, $uri, $response);
        }
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: array<string, mixed>}>
     */
    private function endpointMatrix(): array
    {
        $rfqId = 'rfq-1';
        $itemId = 'item-1';
        $invId = 'inv-1';
        $vendorId = 'vendor-1';
        $quoteId = 'quote-1';
        $sourceId = 'source-1';
        $conflictId = 'conflict-1';
        $runId = 'run-1';
        $modelId = 'model-1';
        $policyId = 'policy-1';
        $scenarioId = 'scenario-1';
        $approvalId = 'approval-1';
        $roundId = 'round-1';
        $awardId = 'award-1';
        $handoffId = 'handoff-1';
        $decisionId = 'decision-1';
        $documentId = 'document-1';
        $bundleId = 'bundle-1';
        $reportId = 'report-1';
        $scheduleId = 'schedule-1';
        $integrationId = 'integration-1';
        $jobId = 'job-1';
        $userId = 'user-1';
        $settingsId = 'settings-1';
        $notificationId = 'notification-1';

        return [
            ['GET', '/api/v1/dashboard/kpis', []],
            ['GET', '/api/v1/dashboard/spend-trend', []],
            ['GET', '/api/v1/dashboard/vendor-scores', []],
            ['GET', '/api/v1/dashboard/recent-activity', []],
            ['GET', '/api/v1/dashboard/risk-alerts', []],

            ['GET', '/api/v1/rfqs', []],
            ['POST', '/api/v1/rfqs', []],
            ['POST', '/api/v1/rfqs/bulk-action', []],
            ['GET', "/api/v1/rfqs/{$rfqId}", []],
            ['PUT', "/api/v1/rfqs/{$rfqId}", []],
            ['PATCH', "/api/v1/rfqs/{$rfqId}/status", []],
            ['POST', "/api/v1/rfqs/{$rfqId}/duplicate", []],
            ['PUT', "/api/v1/rfqs/{$rfqId}/draft", []],
            ['GET', "/api/v1/rfqs/{$rfqId}/line-items", []],
            ['POST', "/api/v1/rfqs/{$rfqId}/line-items", []],
            ['PUT', "/api/v1/rfqs/{$rfqId}/line-items/{$itemId}", []],
            ['DELETE', "/api/v1/rfqs/{$rfqId}/line-items/{$itemId}", []],
            ['GET', "/api/v1/rfqs/{$rfqId}/invitations", []],
            ['POST', "/api/v1/rfqs/{$rfqId}/invitations", []],
            ['POST', "/api/v1/rfqs/{$rfqId}/invitations/{$invId}/remind", []],

            ['GET', '/api/v1/rfq-templates', []],
            ['POST', '/api/v1/rfq-templates', []],
            ['GET', "/api/v1/rfq-templates/{$modelId}", []],
            ['PUT', "/api/v1/rfq-templates/{$modelId}", []],
            ['PATCH', "/api/v1/rfq-templates/{$modelId}/status", []],
            ['POST', "/api/v1/rfq-templates/{$modelId}/duplicate", []],
            ['POST', "/api/v1/rfq-templates/{$modelId}/apply", []],

            ['GET', '/api/v1/vendors', []],
            ['GET', "/api/v1/vendors/{$vendorId}", []],
            ['GET', "/api/v1/vendors/{$vendorId}/performance", []],
            ['GET', "/api/v1/vendors/{$vendorId}/compliance", []],
            ['GET', "/api/v1/vendors/{$vendorId}/history", []],
            ['POST', "/api/v1/vendors/{$vendorId}/sanctions-screening", []],
            ['GET', "/api/v1/vendors/{$vendorId}/sanctions-history", []],
            ['GET', "/api/v1/vendors/{$vendorId}/due-diligence", []],
            ['PATCH', "/api/v1/vendors/{$vendorId}/due-diligence/{$itemId}", []],

            ['GET', '/api/v1/quote-submissions', []],
            ['POST', '/api/v1/quote-submissions/upload', []],
            ['GET', "/api/v1/quote-submissions/{$quoteId}", []],
            ['PATCH', "/api/v1/quote-submissions/{$quoteId}/status", []],
            ['POST', "/api/v1/quote-submissions/{$quoteId}/replace", []],
            ['POST', "/api/v1/quote-submissions/{$quoteId}/reparse", []],
            ['POST', "/api/v1/quote-submissions/{$quoteId}/assign", []],

            ['GET', "/api/v1/normalization/{$rfqId}/source-lines", []],
            ['GET', "/api/v1/normalization/{$rfqId}/normalized-items", []],
            ['PUT', "/api/v1/normalization/source-lines/{$sourceId}/mapping", []],
            ['POST', "/api/v1/normalization/{$rfqId}/bulk-mapping", []],
            ['PUT', "/api/v1/normalization/source-lines/{$sourceId}/override", []],
            ['DELETE', "/api/v1/normalization/source-lines/{$sourceId}/override", []],
            ['GET', "/api/v1/normalization/{$rfqId}/conflicts", []],
            ['PUT', "/api/v1/normalization/conflicts/{$conflictId}/resolve", []],
            ['POST', "/api/v1/normalization/{$rfqId}/lock", []],
            ['POST', "/api/v1/normalization/{$rfqId}/unlock", []],

            ['GET', '/api/v1/comparison-runs', []],
            ['POST', '/api/v1/comparison-runs/preview', []],
            ['POST', '/api/v1/comparison-runs/final', []],
            ['GET', "/api/v1/comparison-runs/{$runId}", []],
            ['GET', "/api/v1/comparison-runs/{$runId}/matrix", []],
            ['GET', "/api/v1/comparison-runs/{$runId}/readiness", []],
            ['PATCH', "/api/v1/comparison-runs/{$runId}/scoring-model", []],
            ['POST', "/api/v1/comparison-runs/{$runId}/lock", []],
            ['POST', "/api/v1/comparison-runs/{$runId}/unlock", []],

            ['GET', '/api/v1/scoring-models', []],
            ['POST', '/api/v1/scoring-models', []],
            ['GET', "/api/v1/scoring-models/{$modelId}", []],
            ['PUT', "/api/v1/scoring-models/{$modelId}", []],
            ['POST', "/api/v1/scoring-models/{$modelId}/publish", []],
            ['GET', "/api/v1/scoring-models/{$modelId}/versions", []],
            ['PUT', "/api/v1/scoring-models/{$modelId}/assignments", []],
            ['POST', "/api/v1/scoring-models/{$modelId}/preview", []],

            ['GET', '/api/v1/scoring-policies', []],
            ['POST', '/api/v1/scoring-policies', []],
            ['GET', "/api/v1/scoring-policies/{$policyId}", []],
            ['PUT', "/api/v1/scoring-policies/{$policyId}", []],
            ['POST', "/api/v1/scoring-policies/{$policyId}/publish", []],
            ['PATCH', "/api/v1/scoring-policies/{$policyId}/status", []],
            ['PUT', "/api/v1/scoring-policies/{$policyId}/assignments", []],
            ['GET', "/api/v1/scoring-policies/{$policyId}/versions", []],

            ['GET', '/api/v1/scenarios', []],
            ['POST', '/api/v1/scenarios', []],
            ['POST', '/api/v1/scenarios/compare', []],
            ['PUT', "/api/v1/scenarios/{$scenarioId}", []],
            ['DELETE', "/api/v1/scenarios/{$scenarioId}", []],

            ['GET', "/api/v1/recommendations/{$runId}", []],
            ['GET', "/api/v1/recommendations/{$runId}/mcda", []],
            ['POST', "/api/v1/recommendations/{$runId}/override", []],
            ['POST', "/api/v1/recommendations/{$runId}/rerun", []],

            ['GET', '/api/v1/risk-items', []],
            ['POST', "/api/v1/risk-items/{$itemId}/escalate", []],
            ['POST', "/api/v1/risk-items/{$itemId}/exception", []],

            ['GET', '/api/v1/approvals', []],
            ['POST', '/api/v1/approvals/bulk-approve', []],
            ['POST', '/api/v1/approvals/bulk-reject', []],
            ['POST', '/api/v1/approvals/bulk-reassign', []],
            ['GET', "/api/v1/approvals/{$approvalId}", []],
            ['POST', "/api/v1/approvals/{$approvalId}/approve", []],
            ['POST', "/api/v1/approvals/{$approvalId}/reject", []],
            ['POST', "/api/v1/approvals/{$approvalId}/return", []],
            ['POST', "/api/v1/approvals/{$approvalId}/reassign", []],
            ['POST', "/api/v1/approvals/{$approvalId}/snooze", []],
            ['POST', "/api/v1/approvals/{$approvalId}/request-evidence", []],
            ['GET', "/api/v1/approvals/{$approvalId}/history", []],

            ['GET', '/api/v1/negotiations', []],
            ['POST', '/api/v1/negotiations/rounds', []],
            ['POST', "/api/v1/negotiations/rounds/{$roundId}/counter-offer", []],
            ['POST', "/api/v1/negotiations/{$rfqId}/bafo", []],
            ['POST', "/api/v1/negotiations/{$rfqId}/close", []],

            ['GET', '/api/v1/awards', []],
            ['POST', '/api/v1/awards', []],
            ['PUT', "/api/v1/awards/{$awardId}/split", []],
            ['POST', "/api/v1/awards/{$awardId}/debrief/{$vendorId}", []],
            ['POST', "/api/v1/awards/{$awardId}/protest", []],
            ['PATCH', "/api/v1/awards/{$awardId}/protest/{$runId}/resolve", []],
            ['POST', "/api/v1/awards/{$awardId}/signoff", []],

            ['GET', '/api/v1/handoffs', []],
            ['GET', '/api/v1/handoffs/destinations', []],
            ['GET', "/api/v1/handoffs/{$handoffId}", []],
            ['POST', "/api/v1/handoffs/{$handoffId}/validate", []],
            ['POST', "/api/v1/handoffs/{$handoffId}/send", []],
            ['POST', "/api/v1/handoffs/{$handoffId}/retry", []],

            ['GET', '/api/v1/decision-trail', []],
            ['POST', '/api/v1/decision-trail/verify', []],
            ['POST', '/api/v1/decision-trail/export', []],
            ['GET', "/api/v1/decision-trail/{$decisionId}", []],

            ['GET', '/api/v1/documents', []],
            ['GET', "/api/v1/documents/{$documentId}", []],
            ['GET', "/api/v1/documents/{$documentId}/download", []],
            ['GET', "/api/v1/documents/{$documentId}/preview", []],
            ['GET', '/api/v1/evidence-bundles', []],
            ['POST', '/api/v1/evidence-bundles', []],
            ['GET', "/api/v1/evidence-bundles/{$bundleId}", []],
            ['POST', "/api/v1/evidence-bundles/{$bundleId}/add-document", []],
            ['POST', "/api/v1/evidence-bundles/{$bundleId}/finalize", []],
            ['GET', "/api/v1/evidence-bundles/{$bundleId}/export", []],

            ['GET', '/api/v1/reports/kpis', []],
            ['GET', '/api/v1/reports/spend-trend', []],
            ['GET', '/api/v1/reports/spend-by-category', []],
            ['POST', '/api/v1/reports/export', []],
            ['GET', '/api/v1/reports/runs', []],
            ['GET', "/api/v1/reports/runs/{$reportId}/download", []],
            ['GET', '/api/v1/reports/schedules', []],
            ['POST', '/api/v1/reports/schedules', []],
            ['PUT', "/api/v1/reports/schedules/{$scheduleId}", []],
            ['DELETE', "/api/v1/reports/schedules/{$scheduleId}", []],
            ['POST', "/api/v1/reports/schedules/{$scheduleId}/run-now", []],

            ['GET', '/api/v1/integrations', []],
            ['POST', '/api/v1/integrations', []],
            ['GET', '/api/v1/integrations/catalog', []],
            ['GET', '/api/v1/integrations/health', []],
            ['GET', '/api/v1/integrations/jobs', []],
            ['POST', "/api/v1/integrations/jobs/{$jobId}/retry", []],
            ['GET', "/api/v1/integrations/{$integrationId}", []],
            ['PUT', "/api/v1/integrations/{$integrationId}", []],
            ['PATCH', "/api/v1/integrations/{$integrationId}/status", []],
            ['DELETE', "/api/v1/integrations/{$integrationId}", []],
            ['POST', "/api/v1/integrations/{$integrationId}/test", []],

            ['GET', '/api/v1/users', []],
            ['POST', '/api/v1/users/invite', []],
            ['GET', "/api/v1/users/{$userId}", []],
            ['PUT', "/api/v1/users/{$userId}", []],
            ['POST', "/api/v1/users/{$userId}/suspend", []],
            ['POST', "/api/v1/users/{$userId}/reactivate", []],
            ['GET', "/api/v1/users/{$userId}/delegation-rules", []],
            ['PUT', "/api/v1/users/{$userId}/delegation-rules", []],
            ['PUT', "/api/v1/users/{$userId}/authority-limits", []],
            ['GET', '/api/v1/roles', []],

            ['GET', '/api/v1/settings', []],
            ['PUT', '/api/v1/settings/general', []],
            ['PUT', '/api/v1/settings/workflow', []],
            ['PUT', '/api/v1/settings/compliance', []],
            ['GET', '/api/v1/feature-flags', []],
            ['PUT', "/api/v1/feature-flags/{$settingsId}", []],

            ['GET', '/api/v1/notifications', []],
            ['GET', '/api/v1/notifications/unread-count', []],
            ['PATCH', "/api/v1/notifications/{$notificationId}/read", []],
            ['POST', '/api/v1/notifications/mark-all-read', []],
            ['DELETE', '/api/v1/notifications/clear-read', []],

            ['GET', '/api/v1/search?query=test', []],

            ['GET', '/api/v1/me', []],
            ['PUT', '/api/v1/me', []],
            ['GET', '/api/v1/account/profile', []],
            ['PUT', '/api/v1/account/profile', []],
            ['POST', '/api/v1/account/change-password', []],
            ['GET', '/api/v1/account/preferences', []],
            ['PUT', '/api/v1/account/preferences', []],
            ['GET', '/api/v1/account/notifications', []],
            ['PUT', '/api/v1/account/notifications', []],
            ['GET', '/api/v1/account/subscription', []],
            ['GET', '/api/v1/account/subscription/plans', []],
            ['POST', '/api/v1/account/subscription/change', []],
            ['GET', '/api/v1/account/payment-methods', []],
            ['POST', '/api/v1/account/payment-methods', []],
            ['DELETE', "/api/v1/account/payment-methods/{$settingsId}", []],
            ['PATCH', "/api/v1/account/payment-methods/{$settingsId}/default", []],
        ];
    }

    private function assertSuccessfulResponse(string $method, string $uri, TestResponse $response): void
    {
        $this->assertTrue(
            $response->isSuccessful(),
            "{$method} {$uri} failed with status {$response->status()}: {$response->getContent()}"
        );
    }
}
