<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\QuoteIntake\DecisionTrailRecorder;
use InvalidArgumentException;
use Tests\TestCase;

final class DecisionTrailRecorderTest extends TestCase
{
    public function testRecordAiArtifactGeneratedRejectsUnknownEventType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported AI artifact decision-trail event type');

        $recorder = new DecisionTrailRecorder();
        $recorder->recordAiArtifactGenerated(
            tenantId: 'tenant-1',
            rfqId: 'rfq-1',
            comparisonRunId: 'run-1',
            eventType: 'approval_ai_summary_generated:',
            summary: [
                'artifact_kind' => 'approval_ai_summary',
                'artifact_origin' => 'provider_drafted',
                'feature_key' => 'approval_ai_summary',
            ],
        );
    }
}
