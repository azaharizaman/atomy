<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\QuoteSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Nexus\QuoteIngestion\QuoteIngestionOrchestrator;

class ProcessQuoteSubmissionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60, 300];

    public function __construct(
        public string $quoteSubmissionId,
    ) {}

    public function handle(QuoteIngestionOrchestrator $orchestrator): void
    {
        $submission = QuoteSubmission::find($this->quoteSubmissionId);
        
        if ($submission === null) {
            Log::warning('Quote submission not found', ['id' => $this->quoteSubmissionId]);
            return;
        }

        if (in_array($submission->status, ['extracting', 'normalizing'], true)) {
            Log::info('Quote submission already processing', ['id' => $this->quoteSubmissionId]);
            return;
        }

        $submission->retry_count = ($submission->retry_count ?? 0) + 1;
        $submission->save();

        $orchestrator->process($this->quoteSubmissionId, $submission->tenant_id);
    }

    public function failed(\Throwable $exception): void
    {
        $submission = QuoteSubmission::find($this->quoteSubmissionId);

        Log::error('ProcessQuoteSubmissionJob exhausted retries', [
            'quote_submission_id' => $this->quoteSubmissionId,
            'error_class' => $exception::class,
            'error_message' => self::sanitizeExceptionMessage($exception),
        ]);

        if ($submission !== null) {
            $submission->status = 'failed';
            $submission->error_code = 'MAX_RETRIES_EXCEEDED';
            $submission->error_message = QuoteIngestionOrchestrator::GENERIC_FAILURE_MESSAGE;
            $submission->processing_completed_at = now();
            $submission->save();
        }
    }

    private static function sanitizeExceptionMessage(\Throwable $exception): string
    {
        $message = $exception->getMessage();

        $message = preg_replace('/https?:\/\/[^\s\'"]*/i', '[REDACTED_URL]', $message) ?? $message;

        $message = preg_replace('/(api[_-]?key|apikey|secret|password|token|auth)[=:]\s*[\'"]?([A-Za-z0-9_\-]{4})[^\s\'"]*/i', '$1=[REDACTED]', $message);

        if (strlen($message) > 200) {
            $message = substr($message, 0, 200) . '...[TRUNCATED]';
        }

        return $message;
    }

    public function runSync(QuoteIngestionOrchestrator $orchestrator): void
    {
        Log::warning('ProcessQuoteSubmissionJob running in sync fallback mode', [
            'quote_submission_id' => $this->quoteSubmissionId,
        ]);
        $this->handle($orchestrator);
    }
}
