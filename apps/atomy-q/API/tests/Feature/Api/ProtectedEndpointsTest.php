<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;

final class ProtectedEndpointsTest extends ApiTestCase
{
    #[DataProvider('endpointMatrix')]
    public function test_protected_endpoints_require_auth(string $method, string $uri, array $payload): void
    {
        $headers = $this->authHeaders();

        $response = $this->json($method, $uri, $payload, $headers);

        $this->assertNotUnauthorizedOrForbidden($method, $uri, $response);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: array<string, mixed>}>
     */
    public static function endpointMatrix(): array
    {
        $matrix = [
            ['GET', '/api/v1/dashboard/kpis', []],
            ['GET', '/api/v1/dashboard/spend-trend', []],
            ['GET', '/api/v1/dashboard/vendor-scores', []],
            ['GET', '/api/v1/dashboard/recent-activity', []],
            ['GET', '/api/v1/dashboard/risk-alerts', []],

            ['GET', '/api/v1/rfqs', []],
            ['POST', '/api/v1/rfqs', []],
            ['POST', '/api/v1/rfqs/bulk-action', []],
            ['GET', '/api/v1/rfqs/rfq-1', []],
            ['PUT', '/api/v1/rfqs/rfq-1', []],
            ['PATCH', '/api/v1/rfqs/rfq-1/status', []],
            ['POST', '/api/v1/rfqs/rfq-1/duplicate', []],
            ['PUT', '/api/v1/rfqs/rfq-1/draft', []],
            ['GET', '/api/v1/rfqs/rfq-1/line-items', []],
            ['POST', '/api/v1/rfqs/rfq-1/line-items', []],
            ['PUT', '/api/v1/rfqs/rfq-1/line-items/item-1', []],
            ['DELETE', '/api/v1/rfqs/rfq-1/line-items/item-1', []],
            ['GET', '/api/v1/rfqs/rfq-1/invitations', []],
            ['POST', '/api/v1/rfqs/rfq-1/invitations', []],
            ['POST', '/api/v1/rfqs/rfq-1/invitations/inv-1/remind', []],

            ['GET', '/api/v1/rfq-templates', []],
            ['POST', '/api/v1/rfq-templates', []],
            ['GET', '/api/v1/rfq-templates/model-1', []],
            ['PUT', '/api/v1/rfq-templates/model-1', []],
            ['PATCH', '/api/v1/rfq-templates/model-1/status', []],
            ['POST', '/api/v1/rfq-templates/model-1/duplicate', []],
            ['POST', '/api/v1/rfq-templates/model-1/apply', []],

            ['GET', '/api/v1/vendors', []],
            ['GET', '/api/v1/vendors/vendor-1', []],
            ['GET', '/api/v1/vendors/vendor-1/performance', []],
            ['GET', '/api/v1/vendors/vendor-1/compliance', []],
            ['GET', '/api/v1/vendors/vendor-1/history', []],
            ['POST', '/api/v1/vendors/vendor-1/sanctions-screening', []],
            ['GET', '/api/v1/vendors/vendor-1/sanctions-history', []],
            ['GET', '/api/v1/vendors/vendor-1/due-diligence', []],
            ['PATCH', '/api/v1/vendors/vendor-1/due-diligence/item-1', []],

            ['GET', '/api/v1/quote-submissions', []],
            ['POST', '/api/v1/quote-submissions/upload', []],
            ['GET', '/api/v1/quote-submissions/quote-1', []],
            ['PATCH', '/api/v1/quote-submissions/quote-1/status', []],
            ['POST', '/api/v1/quote-submissions/quote-1/replace', []],
            ['POST', '/api/v1/quote-submissions/quote-1/reparse', []],
            ['POST', '/api/v1/quote-submissions/quote-1/assign', []],

            ['GET', '/api/v1/normalization/rfq-1/source-lines', []],
            ['GET', '/api/v1/normalization/rfq-1/normalized-items', []],
            ['PUT', '/api/v1/normalization/source-lines/source-1/mapping', []],
            ['POST', '/api/v1/normalization/rfq-1/bulk-mapping', []],
            ['PUT', '/api/v1/normalization/source-lines/source-1/override', []],
            ['DELETE', '/api/v1/normalization/source-lines/source-1/override', []],
            ['GET', '/api/v1/normalization/rfq-1/conflicts', []],
            ['PUT', '/api/v1/normalization/conflicts/conflict-1/resolve', []],
            ['POST', '/api/v1/normalization/rfq-1/lock', []],
            ['POST', '/api/v1/normalization/rfq-1/unlock', []],

            ['GET', '/api/v1/comparison-runs', []],
            ['POST', '/api/v1/comparison-runs/preview', []],
            ['POST', '/api/v1/comparison-runs/final', []],
            ['GET', '/api/v1/comparison-runs/run-1', []],
            ['GET', '/api/v1/comparison-runs/run-1/matrix', []],
            ['GET', '/api/v1/comparison-runs/run-1/readiness', []],
            ['PATCH', '/api/v1/comparison-runs/run-1/scoring-model', []],
            ['POST', '/api/v1/comparison-runs/run-1/lock', []],
            ['POST', '/api/v1/comparison-runs/run-1/unlock', []],

            ['GET', '/api/v1/scoring-models', []],
            ['POST', '/api/v1/scoring-models', []],
            ['GET', '/api/v1/scoring-models/model-1', []],
            ['PUT', '/api/v1/scoring-models/model-1', []],
            ['POST', '/api/v1/scoring-models/model-1/publish', []],
            ['GET', '/api/v1/scoring-models/model-1/versions', []],
            ['PUT', '/api/v1/scoring-models/model-1/assignments', []],
            ['POST', '/api/v1/scoring-models/model-1/preview', []],

            ['GET', '/api/v1/scoring-policies', []],
            ['POST', '/api/v1/scoring-policies', []],
            ['GET', '/api/v1/scoring-policies/policy-1', []],
            ['PUT', '/api/v1/scoring-policies/policy-1', []],
            ['POST', '/api/v1/scoring-policies/policy-1/publish', []],
            ['PATCH', '/api/v1/scoring-policies/policy-1/status', []],
            ['PUT', '/api/v1/scoring-policies/policy-1/assignments', []],
            ['GET', '/api/v1/scoring-policies/policy-1/versions', []],

            ['GET', '/api/v1/scenarios', []],
            ['POST', '/api/v1/scenarios', []],
            ['POST', '/api/v1/scenarios/compare', []],
            ['PUT', '/api/v1/scenarios/scenario-1', []],
            ['DELETE', '/api/v1/scenarios/scenario-1', []],

            ['GET', '/api/v1/recommendations/run-1', []],
            ['GET', '/api/v1/recommendations/run-1/mcda', []],
            ['POST', '/api/v1/recommendations/run-1/override', []],
            ['POST', '/api/v1/recommendations/run-1/rerun', []],

            ['GET', '/api/v1/risk-items', []],
            ['POST', '/api/v1/risk-items/item-1/escalate', []],
            ['POST', '/api/v1/risk-items/item-1/exception', []],

            ['GET', '/api/v1/approvals', []],
            ['POST', '/api/v1/approvals/bulk-approve', []],
            ['POST', '/api/v1/approvals/bulk-reject', []],
            ['POST', '/api/v1/approvals/bulk-reassign', []],
            ['GET', '/api/v1/approvals/approval-1', []],
            ['POST', '/api/v1/approvals/approval-1/approve', []],
            ['POST', '/api/v1/approvals/approval-1/reject', []],
            ['POST', '/api/v1/approvals/approval-1/return', []],
            ['POST', '/api/v1/approvals/approval-1/reassign', []],
            ['POST', '/api/v1/approvals/approval-1/snooze', []],
            ['POST', '/api/v1/approvals/approval-1/request-evidence', []],
            ['GET', '/api/v1/approvals/approval-1/history', []],

            ['GET', '/api/v1/negotiations', []],
            ['POST', '/api/v1/negotiations/rounds', []],
            ['POST', '/api/v1/negotiations/rounds/round-1/counter-offer', []],
            ['POST', '/api/v1/negotiations/rfq-1/bafo', []],
            ['POST', '/api/v1/negotiations/rfq-1/close', []],

            ['GET', '/api/v1/awards', []],
            ['POST', '/api/v1/awards', []],
            ['PUT', '/api/v1/awards/award-1/split', []],
            ['POST', '/api/v1/awards/award-1/debrief/vendor-1', []],
            ['POST', '/api/v1/awards/award-1/protest', []],
            ['PATCH', '/api/v1/awards/award-1/protest/run-1/resolve', []],
            ['POST', '/api/v1/awards/award-1/signoff', []],

            ['GET', '/api/v1/handoffs', []],
            ['GET', '/api/v1/handoffs/destinations', []],
            ['GET', '/api/v1/handoffs/handoff-1', []],
            ['POST', '/api/v1/handoffs/handoff-1/validate', []],
            ['POST', '/api/v1/handoffs/handoff-1/send', []],
            ['POST', '/api/v1/handoffs/handoff-1/retry', []],

            ['GET', '/api/v1/decision-trail', []],
            ['POST', '/api/v1/decision-trail/verify', []],
            ['POST', '/api/v1/decision-trail/export', []],
            ['GET', '/api/v1/decision-trail/decision-1', []],

            ['GET', '/api/v1/documents', []],
            ['GET', '/api/v1/documents/document-1', []],
            ['GET', '/api/v1/documents/document-1/download', []],
            ['GET', '/api/v1/documents/document-1/preview', []],
            ['GET', '/api/v1/evidence-bundles', []],
            ['POST', '/api/v1/evidence-bundles', []],
            ['GET', '/api/v1/evidence-bundles/bundle-1', []],
            ['POST', '/api/v1/evidence-bundles/bundle-1/add-document', []],
            ['POST', '/api/v1/evidence-bundles/bundle-1/finalize', []],
            ['GET', '/api/v1/evidence-bundles/bundle-1/export', []],

            ['GET', '/api/v1/reports/kpis', []],
            ['GET', '/api/v1/reports/spend-trend', []],
            ['GET', '/api/v1/reports/spend-by-category', []],
            ['POST', '/api/v1/reports/export', []],
            ['GET', '/api/v1/reports/runs', []],
            ['GET', '/api/v1/reports/runs/report-1/download', []],
            ['GET', '/api/v1/reports/schedules', []],
            ['POST', '/api/v1/reports/schedules', []],
            ['PUT', '/api/v1/reports/schedules/schedule-1', []],
            ['DELETE', '/api/v1/reports/schedules/schedule-1', []],
            ['POST', '/api/v1/reports/schedules/schedule-1/run-now', []],

            ['GET', '/api/v1/integrations', []],
            ['POST', '/api/v1/integrations', []],
            ['GET', '/api/v1/integrations/catalog', []],
            ['GET', '/api/v1/integrations/health', []],
            ['GET', '/api/v1/integrations/jobs', []],
            ['POST', '/api/v1/integrations/jobs/job-1/retry', []],
            ['GET', '/api/v1/integrations/integration-1', []],
            ['PUT', '/api/v1/integrations/integration-1', []],
            ['PATCH', '/api/v1/integrations/integration-1/status', []],
            ['DELETE', '/api/v1/integrations/integration-1', []],
            ['POST', '/api/v1/integrations/integration-1/test', []],

            ['GET', '/api/v1/users', []],
            ['POST', '/api/v1/users/invite', []],
            ['GET', '/api/v1/users/user-1', []],
            ['PUT', '/api/v1/users/user-1', []],
            ['POST', '/api/v1/users/user-1/suspend', []],
            ['POST', '/api/v1/users/user-1/reactivate', []],
            ['GET', '/api/v1/users/user-1/delegation-rules', []],
            ['PUT', '/api/v1/users/user-1/delegation-rules', []],
            ['PUT', '/api/v1/users/user-1/authority-limits', []],
            ['GET', '/api/v1/roles', []],

            ['GET', '/api/v1/settings', []],
            ['PUT', '/api/v1/settings/general', []],
            ['PUT', '/api/v1/settings/workflow', []],
            ['PUT', '/api/v1/settings/compliance', []],
            ['GET', '/api/v1/feature-flags', []],
            ['PUT', '/api/v1/feature-flags/settings-1', []],

            ['GET', '/api/v1/notifications', []],
            ['GET', '/api/v1/notifications/unread-count', []],
            ['PATCH', '/api/v1/notifications/notification-1/read', []],
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
            ['DELETE', '/api/v1/account/payment-methods/settings-1', []],
            ['PATCH', '/api/v1/account/payment-methods/settings-1/default', []],
        ];

        $cases = [];
        $index = 1;
        foreach ($matrix as [$method, $uri, $payload]) {
            $suffix = (string) $index;
            $resolvedUri = self::withUniqueIds($uri, $suffix);
            $cases["{$method} {$resolvedUri}"] = [$method, $resolvedUri, $payload];
            $index++;
        }

        return $cases;
    }

    private static function withUniqueIds(string $uri, string $suffix): string
    {
        $tokens = [
            'rfq-1' => "rfq-{$suffix}",
            'item-1' => "item-{$suffix}",
            'inv-1' => "inv-{$suffix}",
            'vendor-1' => "vendor-{$suffix}",
            'quote-1' => "quote-{$suffix}",
            'source-1' => "source-{$suffix}",
            'conflict-1' => "conflict-{$suffix}",
            'run-1' => "run-{$suffix}",
            'model-1' => "model-{$suffix}",
            'policy-1' => "policy-{$suffix}",
            'scenario-1' => "scenario-{$suffix}",
            'approval-1' => "approval-{$suffix}",
            'round-1' => "round-{$suffix}",
            'award-1' => "award-{$suffix}",
            'handoff-1' => "handoff-{$suffix}",
            'decision-1' => "decision-{$suffix}",
            'document-1' => "document-{$suffix}",
            'bundle-1' => "bundle-{$suffix}",
            'report-1' => "report-{$suffix}",
            'schedule-1' => "schedule-{$suffix}",
            'integration-1' => "integration-{$suffix}",
            'job-1' => "job-{$suffix}",
            'user-1' => "user-{$suffix}",
            'settings-1' => "settings-{$suffix}",
            'notification-1' => "notification-{$suffix}",
        ];

        return str_replace(array_keys($tokens), array_values($tokens), $uri);
    }

    private function assertNotUnauthorizedOrForbidden(string $method, string $uri, TestResponse $response): void
    {
        $status = $response->status();
        $this->assertFalse(
            $status === 401 || $status === 403,
            "{$method} {$uri} unexpectedly returned {$status}: {$response->getContent()}"
        );
    }
}
