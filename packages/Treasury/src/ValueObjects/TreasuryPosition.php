<?php

declare(strict_types=1);

namespace Nexus\Treasury\ValueObjects;

use Nexus\Common\ValueObjects\Money;
use DateTimeImmutable;
use Nexus\Treasury\Contracts\TreasuryPositionInterface;

final readonly class TreasuryPosition implements TreasuryPositionInterface
{
    private string $id;

    public function __construct(
        ?string $id = null,
        public string $tenantId = 'unknown',
        public ?string $entityId = null,
        public Money $totalCashBalance,
        public Money $availableCashBalance,
        public Money $reservedCashBalance,
        public Money $investedCashBalance,
        public Money $projectedInflows,
        public Money $projectedOutflows,
        public DateTimeImmutable $positionDate,
    ) {
        $this->id = $id ?? self::generateId();
    }

    public static function generateId(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
        return 'TRE-POS-' . $uuid;
    }

    public static function fromArray(array $data): self
    {
        $currency = $data['currency'] ?? 'USD';

        return new self(
            id: $data['id'] ?? null,
            tenantId: $data['tenant_id'] ?? $data['tenantId'] ?? 'unknown',
            entityId: $data['entity_id'] ?? $data['entityId'] ?? null,
            totalCashBalance: Money::of($data['total_cash_balance'] ?? $data['totalCashBalance'] ?? 0, $currency),
            availableCashBalance: Money::of($data['available_cash_balance'] ?? $data['availableCashBalance'] ?? 0, $currency),
            reservedCashBalance: Money::of($data['reserved_cash_balance'] ?? $data['reservedCashBalance'] ?? 0, $currency),
            investedCashBalance: Money::of($data['invested_cash_balance'] ?? $data['investedCashBalance'] ?? 0, $currency),
            projectedInflows: Money::of($data['projected_inflows'] ?? $data['projectedInflows'] ?? 0, $currency),
            projectedOutflows: Money::of($data['projected_outflows'] ?? $data['projectedOutflows'] ?? 0, $currency),
            positionDate: new DateTimeImmutable($data['position_date'] ?? $data['positionDate'] ?? 'now'),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenantId' => $this->tenantId,
            'entityId' => $this->entityId,
            'totalCashBalance' => $this->totalCashBalance->toArray(),
            'availableCashBalance' => $this->availableCashBalance->toArray(),
            'reservedCashBalance' => $this->reservedCashBalance->toArray(),
            'investedCashBalance' => $this->investedCashBalance->toArray(),
            'projectedInflows' => $this->projectedInflows->toArray(),
            'projectedOutflows' => $this->projectedOutflows->toArray(),
            'positionDate' => $this->positionDate->format('Y-m-d'),
        ];
    }

    public function getCurrency(): string
    {
        return $this->totalCashBalance->getCurrency();
    }

    public function getNetCashFlow(): Money
    {
        return $this->projectedInflows->subtract($this->projectedOutflows);
    }

    public function getNetPosition(): Money
    {
        return $this->availableCashBalance->add($this->getNetCashFlow());
    }

    public function hasSufficientLiquidity(Money $amount): bool
    {
        if ($this->availableCashBalance->getCurrency() !== $amount->getCurrency()) {
            return false;
        }

        return $this->availableCashBalance->greaterThanOrEqual($amount);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getEntityId(): ?string
    {
        return $this->entityId;
    }

    public function getPositionDate(): DateTimeImmutable
    {
        return $this->positionDate;
    }

    public function getTotalCashBalance(): Money
    {
        return $this->totalCashBalance;
    }

    public function getAvailableCashBalance(): Money
    {
        return $this->availableCashBalance;
    }

    public function getReservedCashBalance(): Money
    {
        return $this->reservedCashBalance;
    }

    public function getInvestedCashBalance(): Money
    {
        return $this->investedCashBalance;
    }

    public function getProjectedInflows(): Money
    {
        return $this->projectedInflows;
    }

    public function getProjectedOutflows(): Money
    {
        return $this->projectedOutflows;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->positionDate;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->positionDate;
    }
}
