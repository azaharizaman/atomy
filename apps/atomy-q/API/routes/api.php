<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\ApprovalController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AwardController;
use App\Http\Controllers\Api\V1\ComparisonRunController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DecisionTrailController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\HandoffController;
use App\Http\Controllers\Api\V1\IntegrationController;
use App\Http\Controllers\Api\V1\NegotiationController;
use App\Http\Controllers\Api\V1\NormalizationController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\QuoteSubmissionController;
use App\Http\Controllers\Api\V1\RecommendationController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\RfqController;
use App\Http\Controllers\Api\V1\RfqTemplateController;
use App\Http\Controllers\Api\V1\RiskComplianceController;
use App\Http\Controllers\Api\V1\ScenarioController;
use App\Http\Controllers\Api\V1\ScoringModelController;
use App\Http\Controllers\Api\V1\ScoringPolicyController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\VendorController;
use App\Http\Controllers\Api\V1\VendorInvitationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Section 1: Authentication & Session (7 endpoints)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('sso', [AuthController::class, 'sso']);
    Route::post('mfa/verify', [AuthController::class, 'mfaVerify']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('device-trust', [AuthController::class, 'deviceTrust']);
});

/*
|--------------------------------------------------------------------------
| Authenticated & Tenant-scoped routes
|--------------------------------------------------------------------------
*/
Route::middleware(['jwt.auth', 'tenant'])->group(function (): void {

    // --- Section 2: Dashboard (5 endpoints) ---
    Route::prefix('dashboard')->group(function (): void {
        Route::get('kpis', [DashboardController::class, 'kpis']);
        Route::get('spend-trend', [DashboardController::class, 'spendTrend']);
        Route::get('vendor-scores', [DashboardController::class, 'vendorScores']);
        Route::get('recent-activity', [DashboardController::class, 'recentActivity']);
        Route::get('risk-alerts', [DashboardController::class, 'riskAlerts']);
    });

    // --- Section 3: RFQ Management (12 endpoints) ---
    Route::prefix('rfqs')->group(function (): void {
        Route::get('/', [RfqController::class, 'index']);
        Route::post('/', [RfqController::class, 'store']);
        Route::post('bulk-action', [RfqController::class, 'bulkAction']);

        Route::prefix('{rfqId}')->group(function (): void {
            Route::get('/', [RfqController::class, 'show']);
            Route::put('/', [RfqController::class, 'update']);
            Route::patch('status', [RfqController::class, 'updateStatus']);
            Route::post('duplicate', [RfqController::class, 'duplicate']);
            Route::put('draft', [RfqController::class, 'saveDraft']);

            // Line items
            Route::get('line-items', [RfqController::class, 'lineItems']);
            Route::post('line-items', [RfqController::class, 'storeLineItem']);
            Route::put('line-items/{itemId}', [RfqController::class, 'updateLineItem']);
            Route::delete('line-items/{itemId}', [RfqController::class, 'destroyLineItem']);

            // --- Section 6: Vendor Invitations (3 endpoints) ---
            Route::get('invitations', [VendorInvitationController::class, 'index']);
            Route::post('invitations', [VendorInvitationController::class, 'store']);
            Route::post('invitations/{invId}/remind', [VendorInvitationController::class, 'remind']);
        });
    });

    // --- Section 4: RFQ Templates (7 endpoints) ---
    Route::prefix('rfq-templates')->group(function (): void {
        Route::get('/', [RfqTemplateController::class, 'index']);
        Route::post('/', [RfqTemplateController::class, 'store']);
        Route::get('{id}', [RfqTemplateController::class, 'show']);
        Route::put('{id}', [RfqTemplateController::class, 'update']);
        Route::patch('{id}/status', [RfqTemplateController::class, 'updateStatus']);
        Route::post('{id}/duplicate', [RfqTemplateController::class, 'duplicate']);
        Route::post('{id}/apply', [RfqTemplateController::class, 'apply']);
    });

    // --- Section 5: Vendor Management (5 endpoints) ---
    Route::prefix('vendors')->group(function (): void {
        Route::get('/', [VendorController::class, 'index']);
        Route::get('{id}', [VendorController::class, 'show']);
        Route::get('{id}/performance', [VendorController::class, 'performance']);
        Route::get('{id}/compliance', [VendorController::class, 'compliance']);
        Route::get('{id}/history', [VendorController::class, 'history']);

        // Risk-related vendor endpoints (Section 14)
        Route::post('{id}/sanctions-screening', [RiskComplianceController::class, 'sanctionsScreening']);
        Route::get('{id}/sanctions-history', [RiskComplianceController::class, 'sanctionsHistory']);
        Route::get('{id}/due-diligence', [RiskComplianceController::class, 'dueDiligence']);
        Route::patch('{id}/due-diligence/{itemId}', [RiskComplianceController::class, 'updateDueDiligence']);
    });

    // --- Section 7: Quote Intake (7 endpoints) ---
    Route::prefix('quote-submissions')->group(function (): void {
        Route::get('/', [QuoteSubmissionController::class, 'index']);
        Route::post('upload', [QuoteSubmissionController::class, 'upload']);
        Route::get('{id}', [QuoteSubmissionController::class, 'show']);
        Route::patch('{id}/status', [QuoteSubmissionController::class, 'updateStatus']);
        Route::post('{id}/replace', [QuoteSubmissionController::class, 'replace']);
        Route::post('{id}/reparse', [QuoteSubmissionController::class, 'reparse']);
        Route::post('{id}/assign', [QuoteSubmissionController::class, 'assign']);
    });

    // --- Section 8: Quote Normalization (10 endpoints) ---
    Route::prefix('normalization')->group(function (): void {
        Route::get('{rfqId}/source-lines', [NormalizationController::class, 'sourceLines']);
        Route::get('{rfqId}/normalized-items', [NormalizationController::class, 'normalizedItems']);
        Route::put('source-lines/{id}/mapping', [NormalizationController::class, 'updateMapping']);
        Route::post('{rfqId}/bulk-mapping', [NormalizationController::class, 'bulkMapping']);
        Route::put('source-lines/{id}/override', [NormalizationController::class, 'override']);
        Route::delete('source-lines/{id}/override', [NormalizationController::class, 'revertOverride']);
        Route::get('{rfqId}/conflicts', [NormalizationController::class, 'conflicts']);
        Route::put('conflicts/{id}/resolve', [NormalizationController::class, 'resolveConflict']);
        Route::post('{rfqId}/lock', [NormalizationController::class, 'lock']);
        Route::post('{rfqId}/unlock', [NormalizationController::class, 'unlock']);
    });

    // --- Section 9: Comparison Matrix (9 endpoints) ---
    Route::prefix('comparison-runs')->group(function (): void {
        Route::get('/', [ComparisonRunController::class, 'index']);
        Route::post('preview', [ComparisonRunController::class, 'preview']);
        Route::post('final', [ComparisonRunController::class, 'final_']);
        Route::get('{id}', [ComparisonRunController::class, 'show']);
        Route::get('{id}/matrix', [ComparisonRunController::class, 'matrix']);
        Route::get('{id}/readiness', [ComparisonRunController::class, 'readiness']);
        Route::patch('{id}/scoring-model', [ComparisonRunController::class, 'updateScoringModel']);
        Route::post('{id}/lock', [ComparisonRunController::class, 'lock']);
        Route::post('{id}/unlock', [ComparisonRunController::class, 'unlock']);
    });

    // --- Section 10: Scoring Models (8 endpoints) ---
    Route::prefix('scoring-models')->group(function (): void {
        Route::get('/', [ScoringModelController::class, 'index']);
        Route::post('/', [ScoringModelController::class, 'store']);
        Route::get('{id}', [ScoringModelController::class, 'show']);
        Route::put('{id}', [ScoringModelController::class, 'update']);
        Route::post('{id}/publish', [ScoringModelController::class, 'publish']);
        Route::get('{id}/versions', [ScoringModelController::class, 'versions']);
        Route::put('{id}/assignments', [ScoringModelController::class, 'updateAssignments']);
        Route::post('{id}/preview', [ScoringModelController::class, 'preview']);
    });

    // --- Section 11: Scoring Policies (8 endpoints) ---
    Route::prefix('scoring-policies')->group(function (): void {
        Route::get('/', [ScoringPolicyController::class, 'index']);
        Route::post('/', [ScoringPolicyController::class, 'store']);
        Route::get('{id}', [ScoringPolicyController::class, 'show']);
        Route::put('{id}', [ScoringPolicyController::class, 'update']);
        Route::post('{id}/publish', [ScoringPolicyController::class, 'publish']);
        Route::patch('{id}/status', [ScoringPolicyController::class, 'updateStatus']);
        Route::put('{id}/assignments', [ScoringPolicyController::class, 'updateAssignments']);
        Route::get('{id}/versions', [ScoringPolicyController::class, 'versions']);
    });

    // --- Section 12: Scenarios (5 endpoints) ---
    Route::prefix('scenarios')->group(function (): void {
        Route::get('/', [ScenarioController::class, 'index']);
        Route::post('/', [ScenarioController::class, 'store']);
        Route::post('compare', [ScenarioController::class, 'compare']);
        Route::put('{id}', [ScenarioController::class, 'update']);
        Route::delete('{id}', [ScenarioController::class, 'destroy']);
    });

    // --- Section 13: Recommendations (4 endpoints) ---
    Route::prefix('recommendations')->group(function (): void {
        Route::get('{runId}', [RecommendationController::class, 'show']);
        Route::get('{runId}/mcda', [RecommendationController::class, 'mcda']);
        Route::post('{runId}/override', [RecommendationController::class, 'override']);
        Route::post('{runId}/rerun', [RecommendationController::class, 'rerun']);
    });

    // --- Section 14: Risk & Compliance (3 top-level + 4 vendor sub-routes above) ---
    Route::prefix('risk-items')->group(function (): void {
        Route::get('/', [RiskComplianceController::class, 'index']);
        Route::post('{id}/escalate', [RiskComplianceController::class, 'escalate']);
        Route::post('{id}/exception', [RiskComplianceController::class, 'exception']);
    });

    // --- Section 15: Approvals (12 endpoints) ---
    Route::prefix('approvals')->group(function (): void {
        Route::get('/', [ApprovalController::class, 'index']);
        Route::post('bulk-approve', [ApprovalController::class, 'bulkApprove']);
        Route::post('bulk-reject', [ApprovalController::class, 'bulkReject']);
        Route::post('bulk-reassign', [ApprovalController::class, 'bulkReassign']);
        Route::get('{id}', [ApprovalController::class, 'show']);
        Route::post('{id}/approve', [ApprovalController::class, 'approve']);
        Route::post('{id}/reject', [ApprovalController::class, 'reject']);
        Route::post('{id}/return', [ApprovalController::class, 'return_']);
        Route::post('{id}/reassign', [ApprovalController::class, 'reassign']);
        Route::post('{id}/snooze', [ApprovalController::class, 'snooze']);
        Route::post('{id}/request-evidence', [ApprovalController::class, 'requestEvidence']);
        Route::get('{id}/history', [ApprovalController::class, 'history']);
    });

    // --- Section 16: Negotiations (5 endpoints) ---
    Route::prefix('negotiations')->group(function (): void {
        Route::get('/', [NegotiationController::class, 'index']);
        Route::post('rounds', [NegotiationController::class, 'startRound']);
        Route::post('rounds/{roundId}/counter-offer', [NegotiationController::class, 'counterOffer']);
        Route::post('{rfqId}/bafo', [NegotiationController::class, 'bafo']);
        Route::post('{rfqId}/close', [NegotiationController::class, 'close']);
    });

    // --- Section 17: Award Decision (7 endpoints) ---
    Route::prefix('awards')->group(function (): void {
        Route::get('/', [AwardController::class, 'index']);
        Route::post('/', [AwardController::class, 'store']);
        Route::put('{id}/split', [AwardController::class, 'updateSplit']);
        Route::post('{id}/debrief/{vendorId}', [AwardController::class, 'debrief']);
        Route::post('{id}/protest', [AwardController::class, 'protest']);
        Route::patch('{id}/protest/{protestId}/resolve', [AwardController::class, 'resolveProtest']);
        Route::post('{id}/signoff', [AwardController::class, 'signoff']);
    });

    // --- Section 18: PO/Contract Handoff (6 endpoints) ---
    Route::prefix('handoffs')->group(function (): void {
        Route::get('/', [HandoffController::class, 'index']);
        Route::get('destinations', [HandoffController::class, 'destinations']);
        Route::get('{id}', [HandoffController::class, 'show']);
        Route::post('{id}/validate', [HandoffController::class, 'validate_']);
        Route::post('{id}/send', [HandoffController::class, 'send']);
        Route::post('{id}/retry', [HandoffController::class, 'retry']);
    });

    // --- Section 19: Decision Trail (4 endpoints) ---
    Route::prefix('decision-trail')->group(function (): void {
        Route::get('/', [DecisionTrailController::class, 'index']);
        Route::post('verify', [DecisionTrailController::class, 'verify']);
        Route::post('export', [DecisionTrailController::class, 'export']);
        Route::get('{id}', [DecisionTrailController::class, 'show']);
    });

    // --- Section 20: Documents & Evidence Vault (10 endpoints) ---
    Route::prefix('documents')->group(function (): void {
        Route::get('/', [DocumentController::class, 'index']);
        Route::get('{id}', [DocumentController::class, 'show']);
        Route::get('{id}/download', [DocumentController::class, 'download']);
        Route::get('{id}/preview', [DocumentController::class, 'preview']);
    });
    Route::prefix('evidence-bundles')->group(function (): void {
        Route::get('/', [DocumentController::class, 'bundles']);
        Route::post('/', [DocumentController::class, 'createBundle']);
        Route::get('{id}', [DocumentController::class, 'showBundle']);
        Route::post('{id}/add-document', [DocumentController::class, 'addDocumentToBundle']);
        Route::post('{id}/finalize', [DocumentController::class, 'finalizeBundle']);
        Route::get('{id}/export', [DocumentController::class, 'exportBundle']);
    });

    // --- Section 21: Reports & Analytics (11 endpoints) ---
    Route::prefix('reports')->group(function (): void {
        Route::get('kpis', [ReportController::class, 'kpis']);
        Route::get('spend-trend', [ReportController::class, 'spendTrend']);
        Route::get('spend-by-category', [ReportController::class, 'spendByCategory']);
        Route::post('export', [ReportController::class, 'export']);
        Route::get('runs', [ReportController::class, 'runs']);
        Route::get('runs/{id}/download', [ReportController::class, 'downloadRun']);

        Route::prefix('schedules')->group(function (): void {
            Route::get('/', [ReportController::class, 'schedules']);
            Route::post('/', [ReportController::class, 'createSchedule']);
            Route::put('{id}', [ReportController::class, 'updateSchedule']);
            Route::delete('{id}', [ReportController::class, 'destroySchedule']);
            Route::post('{id}/run-now', [ReportController::class, 'runScheduleNow']);
        });
    });

    // --- Section 22: Integrations & API Monitor (11 endpoints) ---
    Route::prefix('integrations')->group(function (): void {
        Route::get('/', [IntegrationController::class, 'index']);
        Route::post('/', [IntegrationController::class, 'store']);
        Route::get('catalog', [IntegrationController::class, 'catalog']);
        Route::get('health', [IntegrationController::class, 'health']);
        Route::get('jobs', [IntegrationController::class, 'jobs']);
        Route::post('jobs/{jobId}/retry', [IntegrationController::class, 'retryJob']);
        Route::get('{id}', [IntegrationController::class, 'show']);
        Route::put('{id}', [IntegrationController::class, 'update']);
        Route::patch('{id}/status', [IntegrationController::class, 'updateStatus']);
        Route::delete('{id}', [IntegrationController::class, 'destroy']);
        Route::post('{id}/test', [IntegrationController::class, 'test']);
    });

    // --- Section 23: Users & Access Management (10 endpoints) ---
    Route::prefix('users')->group(function (): void {
        Route::get('/', [UserController::class, 'index']);
        Route::post('invite', [UserController::class, 'invite']);
        Route::get('{id}', [UserController::class, 'show']);
        Route::put('{id}', [UserController::class, 'update']);
        Route::post('{id}/suspend', [UserController::class, 'suspend']);
        Route::post('{id}/reactivate', [UserController::class, 'reactivate']);
        Route::get('{id}/delegation-rules', [UserController::class, 'delegationRules']);
        Route::put('{id}/delegation-rules', [UserController::class, 'updateDelegationRules']);
        Route::put('{id}/authority-limits', [UserController::class, 'updateAuthorityLimits']);
    });
    Route::get('roles', [UserController::class, 'roles']);

    // --- Section 24: Admin Settings (6 endpoints) ---
    Route::prefix('settings')->group(function (): void {
        Route::get('/', [SettingController::class, 'index']);
        Route::put('general', [SettingController::class, 'updateGeneral']);
        Route::put('workflow', [SettingController::class, 'updateWorkflow']);
        Route::put('compliance', [SettingController::class, 'updateCompliance']);
    });
    Route::get('feature-flags', [SettingController::class, 'featureFlags']);
    Route::put('feature-flags/{id}', [SettingController::class, 'updateFeatureFlag']);

    // --- Section 25: Notifications (5 endpoints) ---
    Route::prefix('notifications')->group(function (): void {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('{id}/read', [NotificationController::class, 'markRead']);
        Route::post('mark-all-read', [NotificationController::class, 'markAllRead']);
        Route::delete('clear-read', [NotificationController::class, 'clearRead']);
    });

    // --- Section 26: Search (1 endpoint) ---
    Route::get('search', [SearchController::class, 'search']);

    // --- Section 27: User Settings / Account (14 endpoints) ---
    Route::get('me', [AccountController::class, 'profile']);
    Route::put('me', [AccountController::class, 'updateProfile']);

    Route::prefix('account')->group(function (): void {
        Route::get('profile', [AccountController::class, 'profile']);
        Route::put('profile', [AccountController::class, 'updateProfile']);
        Route::post('change-password', [AccountController::class, 'changePassword']);
        Route::get('preferences', [AccountController::class, 'preferences']);
        Route::put('preferences', [AccountController::class, 'updatePreferences']);
        Route::get('notifications', [AccountController::class, 'notificationSettings']);
        Route::put('notifications', [AccountController::class, 'updateNotificationSettings']);

        Route::get('subscription', [AccountController::class, 'subscription']);
        Route::get('subscription/plans', [AccountController::class, 'subscriptionPlans']);
        Route::post('subscription/change', [AccountController::class, 'changeSubscription']);

        Route::get('payment-methods', [AccountController::class, 'paymentMethods']);
        Route::post('payment-methods', [AccountController::class, 'addPaymentMethod']);
        Route::delete('payment-methods/{id}', [AccountController::class, 'removePaymentMethod']);
        Route::patch('payment-methods/{id}/default', [AccountController::class, 'setDefaultPaymentMethod']);
    });
});
