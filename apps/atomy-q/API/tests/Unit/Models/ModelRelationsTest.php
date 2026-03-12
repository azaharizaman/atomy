<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Approval;
use App\Models\ApprovalHistory;
use App\Models\Award;
use App\Models\ComparisonRun;
use App\Models\DecisionTrailEntry;
use App\Models\EvidenceBundle;
use App\Models\Handoff;
use App\Models\Integration;
use App\Models\IntegrationJob;
use App\Models\NegotiationRound;
use App\Models\NormalizationConflict;
use App\Models\NormalizationSourceLine;
use App\Models\Notification;
use App\Models\QuoteSubmission;
use App\Models\ReportRun;
use App\Models\ReportSchedule;
use App\Models\Rfq;
use App\Models\RfqLineItem;
use App\Models\RfqTemplate;
use App\Models\RiskItem;
use App\Models\Scenario;
use App\Models\ScoringModel;
use App\Models\ScoringPolicy;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VendorInvitation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class ModelRelationsTest extends TestCase
{
    #[DataProvider('relationProvider')]
    public function test_model_relations(Model $model, string $relation, string $expectedClass): void
    {
        $relationInstance = $model->{$relation}();

        $this->assertInstanceOf($expectedClass, $relationInstance);
    }

    /**
     * @return array<int, array{0: Model, 1: string, 2: class-string}>
     */
    public static function relationProvider(): array
    {
        return [
            [new Tenant(), 'users', HasMany::class],
            [new User(), 'tenant', BelongsTo::class],
            [new Rfq(), 'owner', BelongsTo::class],
            [new Rfq(), 'lineItems', HasMany::class],
            [new Rfq(), 'quoteSubmissions', HasMany::class],
            [new Rfq(), 'comparisonRuns', HasMany::class],
            [new Rfq(), 'approvals', HasMany::class],
            [new Rfq(), 'vendorInvitations', HasMany::class],
            [new RfqLineItem(), 'rfq', BelongsTo::class],
            [new RfqTemplate(), 'creator', BelongsTo::class],
            [new VendorInvitation(), 'rfq', BelongsTo::class],
            [new QuoteSubmission(), 'rfq', BelongsTo::class],
            [new QuoteSubmission(), 'uploader', BelongsTo::class],
            [new QuoteSubmission(), 'normalizationSourceLines', HasMany::class],
            [new NormalizationSourceLine(), 'rfq', BelongsTo::class],
            [new NormalizationSourceLine(), 'quoteSubmission', BelongsTo::class],
            [new NormalizationSourceLine(), 'rfqLine', BelongsTo::class],
            [new NormalizationSourceLine(), 'mappedToRfqLine', BelongsTo::class],
            [new NormalizationSourceLine(), 'conflicts', HasMany::class],
            [new NormalizationConflict(), 'rfq', BelongsTo::class],
            [new NormalizationConflict(), 'sourceLine', BelongsTo::class],
            [new NormalizationConflict(), 'resolver', BelongsTo::class],
            [new ComparisonRun(), 'rfq', BelongsTo::class],
            [new ComparisonRun(), 'scoringModel', BelongsTo::class],
            [new ComparisonRun(), 'creator', BelongsTo::class],
            [new ComparisonRun(), 'lockedByUser', BelongsTo::class],
            [new ComparisonRun(), 'approvals', HasMany::class],
            [new ComparisonRun(), 'awards', HasMany::class],
            [new ScoringModel(), 'creator', BelongsTo::class],
            [new ScoringModel(), 'comparisonRuns', HasMany::class],
            [new ScoringPolicy(), 'creator', BelongsTo::class],
            [new Scenario(), 'rfq', BelongsTo::class],
            [new Scenario(), 'creator', BelongsTo::class],
            [new Approval(), 'rfq', BelongsTo::class],
            [new Approval(), 'comparisonRun', BelongsTo::class],
            [new Approval(), 'assignee', BelongsTo::class],
            [new Approval(), 'creator', BelongsTo::class],
            [new Approval(), 'history', HasMany::class],
            [new ApprovalHistory(), 'approval', BelongsTo::class],
            [new ApprovalHistory(), 'actor', BelongsTo::class],
            [new NegotiationRound(), 'rfq', BelongsTo::class],
            [new NegotiationRound(), 'creator', BelongsTo::class],
            [new Award(), 'rfq', BelongsTo::class],
            [new Award(), 'comparisonRun', BelongsTo::class],
            [new Award(), 'creator', BelongsTo::class],
            [new Award(), 'handoffs', HasMany::class],
            [new Handoff(), 'award', BelongsTo::class],
            [new Handoff(), 'rfq', BelongsTo::class],
            [new DecisionTrailEntry(), 'rfq', BelongsTo::class],
            [new DecisionTrailEntry(), 'actor', BelongsTo::class],
            [new EvidenceBundle(), 'creator', BelongsTo::class],
            [new ReportSchedule(), 'creator', BelongsTo::class],
            [new ReportSchedule(), 'runs', HasMany::class],
            [new ReportRun(), 'schedule', BelongsTo::class],
            [new Integration(), 'creator', BelongsTo::class],
            [new Integration(), 'jobs', HasMany::class],
            [new IntegrationJob(), 'integration', BelongsTo::class],
            [new Notification(), 'user', BelongsTo::class],
            [new RiskItem(), 'rfq', BelongsTo::class],
        ];
    }
}
