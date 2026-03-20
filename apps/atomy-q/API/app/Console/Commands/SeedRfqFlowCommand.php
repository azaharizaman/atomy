<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\NormalizationSourceLine;
use App\Models\QuoteSubmission;
use App\Models\RfqLineItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class SeedRfqFlowCommand extends Command
{
    protected $signature = 'atomy:seed-rfq-flow
                            {--count=1 : Number of RFQs to create}
                            {--status=published : Target status: draft, published, closed, awarded}
                            {--base-url= : API base URL (default: APP_URL)}
                            {--tenant= : Tenant ID for login}
                            {--email= : User email for login}
                            {--password= : User password for login}';

    protected $description = 'Seed RFQ data by replaying API flow (create RFQ → line items → publish → invite → quotes → intake → normalization → comparison)';

    private const VENDOR_NAMES = [
        'Dell Technologies', 'HP Enterprise', 'Lenovo', 'Cisco Systems', 'IBM',
        'Microsoft', 'Oracle', 'SAP', 'Amazon Web Services', 'Google Cloud',
        'Accenture', 'Deloitte', 'Office Depot', 'CDW', 'Insight',
    ];

    private const LINE_ITEM_DESCS = [
        'Server unit', 'Network switch', 'Software license', 'Support hours',
        'Cabling and install', 'Training day', 'Maintenance contract', 'Cloud instance',
    ];

    private const UOMS = ['ea', 'kg', 'm', 'box', 'day', 'hour'];

    private string $baseUrl = '';

    private string $token = '';

    private string $tenantId = '';

    private int $rfqCount = 1;

    private string $targetStatus = 'published';

    public function handle(): int
    {
        $this->rfqCount = (int) $this->option('count') ?: 1;
        $this->targetStatus = (string) $this->option('status') ?: 'published';

        $allowedStatuses = ['draft', 'published', 'closed', 'awarded'];
        if (!in_array($this->targetStatus, $allowedStatuses, true)) {
            $this->error("Invalid --status. Use one of: " . implode(', ', $allowedStatuses));
            return self::FAILURE;
        }

        $this->baseUrl = rtrim((string) ($this->option('base-url') ?: config('app.url')), '/');
        if ($this->baseUrl === '') {
            $this->error('Set APP_URL or pass --base-url (e.g. http://localhost:8000).');
            return self::FAILURE;
        }

        if (!$this->login()) {
            return self::FAILURE;
        }

        $this->info("Creating {$this->rfqCount} RFQ(s) and driving flow to status: {$this->targetStatus}");

        for ($i = 0; $i < $this->rfqCount; $i++) {
            $this->info("--- RFQ " . ($i + 1) . " / {$this->rfqCount} ---");
            if (!$this->runFlowForOneRfq()) {
                $this->warn('Flow stopped for this RFQ.');
            }
        }

        $this->info('Done.');
        return self::SUCCESS;
    }

    private function login(): bool
    {
        $tenant = (string) ($this->option('tenant') ?: env('ATOMY_SEED_TENANT_ID', '01KKH77M4R0V8QZ1M8NB3XWWWQ'));
        $email = (string) ($this->option('email') ?: env('ATOMY_SEED_EMAIL', 'user1@example.com'));
        $password = (string) ($this->option('password') ?: env('ATOMY_SEED_PASSWORD', 'secret'));

        if (($tenant === '' || $email === '' || $password === '') && $this->input->isInteractive()) {
            if ($tenant === '') {
                $tenant = $this->ask('Tenant ID', '01KKH77M4R0V8QZ1M8NB3XWWWQ');
            }
            if ($email === '') {
                $email = $this->ask('Email', 'user1@example.com');
            }
            if ($password === '') {
                $password = $this->secret('Password');
            }
        }

        $url = $this->baseUrl . '/api/v1/auth/login';
        $this->line("Login: {$url}");
        $response = Http::asJson()->post($url, [
            'email' => $email,
            'password' => $password,
        ]);

        if (!$response->successful()) {
            $this->error('Login failed: ' . $response->status() . ' ' . $response->body());
            return false;
        }

        $data = $response->json();
        $this->token = (string) ($data['access_token'] ?? '');
        $this->tenantId = (string) ($data['user']['tenantId'] ?? $tenant);
        $this->info('Logged in.');
        return true;
    }

    private function api(string $method, string $path, array $body = []): \Illuminate\Http\Client\Response
    {
        $url = $this->baseUrl . '/api/v1/' . ltrim($path, '/');
        $req = Http::withToken($this->token)->asJson();
        return match (strtoupper($method)) {
            'GET' => $req->get($url),
            'POST' => $req->post($url, $body),
            'PUT' => $req->put($url, $body),
            'PATCH' => $req->patch($url, $body),
            'DELETE' => $req->delete($url),
            default => $req->get($url),
        };
    }

    private function apiWithFile(string $method, string $path, array $body, string $fileField, string $fileName, string $contents, string $mimeType = 'text/plain'): \Illuminate\Http\Client\Response
    {
        $url = $this->baseUrl . '/api/v1/' . ltrim($path, '/');
        $req = Http::withToken($this->token)->attach($fileField, $contents, $fileName, ['Content-Type' => $mimeType]);

        return match (strtoupper($method)) {
            'POST' => $req->post($url, $body),
            'PUT' => $req->put($url, $body),
            'PATCH' => $req->patch($url, $body),
            default => $req->post($url, $body),
        };
    }

    private function runFlowForOneRfq(): bool
    {
        $rfqId = $this->createRfq();
        if ($rfqId === '') {
            return false;
        }

        $this->addLineItems($rfqId);
        if ($this->targetStatus === 'draft') {
            return true;
        }

        $this->publishRfq($rfqId);
        $invitationIds = $this->inviteVendors($rfqId);
        if ($this->targetStatus === 'published') {
            return true;
        }

        $quoteIds = $this->submitQuotesAndAccept($rfqId, $invitationIds);
        if ($quoteIds === []) {
            $this->warn('No quotes submitted.');
            return true;
        }
        $this->syncNormalizationLinesForQuotes($rfqId, $quoteIds);
        $this->hitNormalizationAndComparison($rfqId);
        $this->closeRfq($rfqId);
        if ($this->targetStatus === 'closed') {
            return true;
        }

        $this->approveAndAward($rfqId);
        $this->awardRfq($rfqId);
        return true;
    }

    private function createRfq(): string
    {
        $title = 'Seeded RFQ ' . now()->format('Y-m-d H:i');
        $response = $this->api('POST', 'rfqs', [
            'title' => $title,
            'description' => 'Created by atomy:seed-rfq-flow',
            'category' => 'IT Hardware',
            'department' => 'Procurement',
            'estimated_value' => 50000,
            'submission_deadline' => now()->addDays(14)->toAtomString(),
        ]);
        if (!$response->successful()) {
            $this->error('Create RFQ failed: ' . $response->status());
            return '';
        }
        $id = (string) ($response->json('data.id') ?? '');
        $this->line("  RFQ created: {$id}");
        return $id;
    }

    private function addLineItems(string $rfqId): void
    {
        $count = random_int(5, 15);
        for ($j = 1; $j <= $count; $j++) {
            $desc = self::LINE_ITEM_DESCS[$j % count(self::LINE_ITEM_DESCS)];
            $uom = self::UOMS[$j % count(self::UOMS)];
            $response = $this->api('POST', "rfqs/{$rfqId}/line-items", [
                'description' => $desc . " #{$j}",
                'quantity' => (float) random_int(1, 100),
                'uom' => $uom,
                'unit_price' => (float) random_int(50, 500),
                'currency' => 'USD',
            ]);
            if ($response->successful()) {
                $this->line("  Line item {$j} added.");
            }
        }
    }

    private function publishRfq(string $rfqId): void
    {
        $response = $this->api('PATCH', "rfqs/{$rfqId}/status", ['status' => 'published']);
        if ($response->successful()) {
            $this->line('  RFQ status → published.');
        } else {
            $this->warn('  PATCH status failed: ' . $response->status());
        }
    }

    private function inviteVendors(string $rfqId): array
    {
        $num = random_int(2, 6);
        $names = self::VENDOR_NAMES;
        shuffle($names);
        $invitationIds = [];
        foreach (array_slice($names, 0, $num) as $name) {
            $email = strtolower(str_replace(' ', '.', $name)) . '@vendor.com';
            $vendorId = (string) Str::ulid();
            $response = $this->api('POST', "rfqs/{$rfqId}/invitations", [
                'vendor_email' => $email,
                'vendor_name' => $name,
                'vendor_id' => $vendorId,
            ]);
            if ($response->successful()) {
                $invitationIds[] = [
                    'id' => $response->json('data.id'),
                    'vendor_id' => $vendorId,
                    'vendor_name' => $name,
                ];
            }
        }
        $this->line('  Invitations: ' . count($invitationIds));
        return $invitationIds;
    }

    private function submitQuotesAndAccept(string $rfqId, array $invitations): array
    {
        if ($invitations === []) {
            return [];
        }
        $toSubmit = random_int(1, count($invitations));
        $subset = array_slice($invitations, 0, $toSubmit);
        $quoteIds = [];
        foreach ($subset as $inv) {
            $response = $this->apiWithFile('POST', 'quote-submissions/upload', [
                'rfq_id' => $rfqId,
                'vendor_id' => $inv['vendor_id'],
                'vendor_name' => $inv['vendor_name'],
            ], 'file', $inv['vendor_name'] . '-quote.txt', 'Seed quote payload for ' . $inv['vendor_name']);
            if ($response->successful()) {
                $quoteId = $response->json('data.id');
                $quoteIds[] = $quoteId;
                $statusRes = $this->api('PATCH', "quote-submissions/{$quoteId}/status", ['status' => 'accepted']);
                if ($statusRes->successful()) {
                    $this->line("  Quote submitted & accepted: {$quoteId}");
                }
            }
        }
        return $quoteIds;
    }

    /**
     * Ensure uploaded quotes have mapped normalization lines so comparison final/readiness gates pass.
     *
     * @param  list<string>  $quoteIds
     */
    private function syncNormalizationLinesForQuotes(string $rfqId, array $quoteIds): void
    {
        if ($quoteIds === [] || $this->tenantId === '') {
            return;
        }

        $lineItems = RfqLineItem::query()
            ->where('tenant_id', $this->tenantId)
            ->where('rfq_id', $rfqId)
            ->orderBy('sort_order')
            ->get();

        if ($lineItems->isEmpty()) {
            return;
        }

        foreach ($quoteIds as $quoteId) {
            $quoteId = (string) $quoteId;
            $submission = QuoteSubmission::query()
                ->where('tenant_id', $this->tenantId)
                ->where('id', $quoteId)
                ->first();
            if ($submission === null) {
                continue;
            }

            foreach ($lineItems as $li) {
                NormalizationSourceLine::query()->updateOrCreate(
                    [
                        'tenant_id' => $this->tenantId,
                        'quote_submission_id' => $quoteId,
                        'rfq_line_item_id' => $li->id,
                    ],
                    [
                        'source_description' => (string) $li->description,
                        'source_unit_price' => $li->unit_price,
                        'source_uom' => (string) $li->uom,
                        'source_quantity' => $li->quantity,
                        'sort_order' => (int) $li->sort_order,
                    ],
                );
            }

            $submission->status = 'ready';
            $submission->save();
        }

        $this->line('  Normalization source lines synced for ' . count($quoteIds) . ' quote(s).');
    }

    private function hitNormalizationAndComparison(string $rfqId): void
    {
        $res = $this->api('GET', "normalization/{$rfqId}/source-lines");
        if ($res->successful()) {
            $this->line('  Normalization source-lines OK.');
        }
        $res = $this->api('POST', 'comparison-runs/preview', ['rfq_id' => $rfqId]);
        if ($res->successful()) {
            $this->line('  Comparison preview OK.');
        }
        $res = $this->api('POST', 'comparison-runs/final', ['rfq_id' => $rfqId]);
        if ($res->successful()) {
            $this->line('  Comparison final OK.');
        }
    }

    private function closeRfq(string $rfqId): void
    {
        $response = $this->api('PATCH', "rfqs/{$rfqId}/status", ['status' => 'closed']);
        if ($response->successful()) {
            $this->line('  RFQ status → closed.');
        }
    }

    private function approveAndAward(string $rfqId): void
    {
        $res = $this->api('GET', 'approvals', ['rfq_id' => $rfqId]);
        if (!$res->successful()) {
            return;
        }
        $items = $res->json('data') ?? [];
        foreach ($items as $item) {
            $id = $item['id'] ?? null;
            if ($id) {
                $this->api('POST', "approvals/{$id}/approve", []);
            }
        }
        $this->api('POST', 'awards', [
            'rfq_id' => $rfqId,
            'vendor_id' => null,
            'amount' => 45000,
            'currency' => 'USD',
        ]);
    }

    private function awardRfq(string $rfqId): void
    {
        $response = $this->api('PATCH', "rfqs/{$rfqId}/status", ['status' => 'awarded']);
        if ($response->successful()) {
            $this->line('  RFQ status → awarded.');
        }
    }
}
