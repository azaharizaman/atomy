<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Ai\Contracts\ProviderContractVerifierInterface;
use App\Services\Ai\ProviderContractVerificationService;
use InvalidArgumentException;
use Illuminate\Console\Command;

/**
 * Sends representative payloads through every configured AI endpoint group.
 *
 * Use this as an operator-facing smoke check for provider contract shape, not
 * as a business workflow. Any provider response must remain an associative
 * payload so downstream adapters can reject malformed AI output truthfully.
 */
final class AiVerifyContractsCommand extends Command
{
    protected $signature = 'atomy:ai-verify-contracts
        {--endpoint-group=* : Restrict verification to one or more endpoint groups}
        {--tenant-id=plan6-tenant : Tenant identifier used in sample payloads}
        {--rfq-id=plan6-rfq : RFQ identifier used in sample payloads}';

    protected $description = 'Run provider contract verification requests for every configured Atomy-Q AI endpoint group.';

    public function __construct(
        private readonly ProviderContractVerifierInterface $verifier,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenantId = trim((string) $this->option('tenant-id'));
        $rfqId = trim((string) $this->option('rfq-id'));
        if ($tenantId === '') {
            $this->error('The --tenant-id option must be non-empty.');

            return self::FAILURE;
        }

        if ($rfqId === '') {
            $this->error('The --rfq-id option must be non-empty.');

            return self::FAILURE;
        }

        try {
            $endpointGroups = $this->requestedEndpointGroups();
            $this->verifier->assertEndpointGroups($endpointGroups);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        foreach ($this->verifier->verify($endpointGroups, $tenantId, $rfqId) as $result) {
            if (! $result->verified) {
                $this->error($result->message);

                return self::FAILURE;
            }

            $this->info($result->message);
        }

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function requestedEndpointGroups(): array
    {
        $requested = $this->option('endpoint-group');
        if (! is_array($requested) || $requested === []) {
            return ProviderContractVerificationService::ENDPOINT_GROUPS;
        }

        $filtered = array_values(array_filter(array_map(
            static function (mixed $value): ?string {
                if (! is_string($value)) {
                    return null;
                }

                $normalized = trim($value);

                return $normalized !== '' ? $normalized : null;
            },
            $requested,
        )));

        if ($filtered === []) {
            return ProviderContractVerificationService::ENDPOINT_GROUPS;
        }

        $unsupported = array_values(array_diff($filtered, ProviderContractVerificationService::ENDPOINT_GROUPS));
        if ($unsupported !== []) {
            throw new InvalidArgumentException(
                'Unsupported endpoint group(s): ' . implode(', ', $unsupported),
            );
        }

        return $filtered;
    }
}
