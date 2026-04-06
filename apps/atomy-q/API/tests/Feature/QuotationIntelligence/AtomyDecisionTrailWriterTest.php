<?php

declare(strict_types=1);

namespace Tests\Feature\QuotationIntelligence;

use App\Adapters\QuotationIntelligence\AtomyDecisionTrailWriter;
use App\Exceptions\DecisionTrailSerializationException;
use App\Models\ComparisonRun;
use App\Models\DecisionTrailEntry;
use App\Models\Rfq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use JsonException;
use Tests\TestCase;

final class AtomyDecisionTrailWriterTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication(): \Illuminate\Foundation\Application
    {
        $app = parent::createApplication();
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $app['config']->set('logging.default', 'null');
        $app['config']->set('logging.channels.stack.channels', ['null']);

        return $app;
    }

    private function createUser(): User
    {
        /** @var User $user */
        $user = User::query()->create([
            'tenant_id' => (string) Str::ulid(),
            'email' => 'writer-' . Str::lower((string) Str::ulid()) . '@example.com',
            'name' => 'Writer Test User',
            'password_hash' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
            'timezone' => 'UTC',
            'locale' => 'en',
            'email_verified_at' => now(),
        ]);

        return $user;
    }

    private function createRfq(User $user): Rfq
    {
        /** @var Rfq $rfq */
        $rfq = Rfq::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_number' => 'RFQ-WRITER-' . Str::upper(Str::random(6)),
            'title' => 'Writer Test RFQ',
            'owner_id' => $user->id,
            'submission_deadline' => now()->addDays(14),
            'status' => 'draft',
        ]);

        return $rfq;
    }

    public function test_write_uses_null_idempotency_key_comparison_run_when_lookup_context_has_none(): void
    {
        $user = $this->createUser();
        $rfq = $this->createRfq($user);

        $nullRun = ComparisonRun::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'idempotency_key' => null,
            'name' => 'Null key run',
            'description' => 'Null key run',
            'is_preview' => false,
            'status' => 'draft',
            'version' => 1,
        ]);
        ComparisonRun::query()->whereKey($nullRun->id)->update(['created_at' => now()]);

        $nonNullRun = ComparisonRun::query()->create([
            'tenant_id' => $user->tenant_id,
            'rfq_id' => $rfq->id,
            'idempotency_key' => 'quote-submission:legacy',
            'name' => 'Non-null key run',
            'description' => 'Non-null key run',
            'is_preview' => false,
            'status' => 'draft',
            'version' => 1,
        ]);
        ComparisonRun::query()->whereKey($nonNullRun->id)->update(['created_at' => now()->addMinute()]);

        $writer = new AtomyDecisionTrailWriter();
        $writer->write($user->tenant_id, $rfq->id, [[
            'event_type' => 'auto_map',
            'payload' => ['line' => 'mapped'],
        ]]);

        $entry = DecisionTrailEntry::query()->first();

        self::assertNotNull($entry);
        self::assertSame($nullRun->id, $entry->comparison_run_id);
    }

    public function test_write_wraps_json_serialization_errors_in_domain_exception(): void
    {
        $user = $this->createUser();
        $rfq = $this->createRfq($user);

        $payload = [];
        $payload['self'] = &$payload;

        $writer = new AtomyDecisionTrailWriter();

        try {
            $writer->write($user->tenant_id, $rfq->id, [[
                'event_type' => 'auto_map',
                'payload' => $payload,
            ]]);

            self::fail('Expected DecisionTrailSerializationException to be thrown.');
        } catch (DecisionTrailSerializationException $exception) {
            self::assertInstanceOf(JsonException::class, $exception->getPrevious());
            self::assertSame('Failed to serialize decision trail payload.', $exception->getMessage());
        }
    }
}
