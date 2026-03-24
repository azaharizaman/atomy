<?php

declare(strict_types=1);

namespace Nexus\Laravel\Sourcing\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Nexus\Sourcing\Contracts\QuotationInterface;
use Nexus\Sourcing\ValueObjects\NormalizationLine;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $sourcing_event_id
 * @property string $vendor_id
 * @property string $status
 * @property array<int, array<string, mixed>>|null $normalization_data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class EloquentQuotation extends Model implements QuotationInterface
{
    use HasUlids;

    protected $table = 'nexus_quotations';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'sourcing_event_id',
        'vendor_id',
        'status',
        'normalization_data',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'normalization_data' => 'array',
        ];
    }

    public function getId(): string
    {
        return (string) $this->id;
    }

    public function getTenantId(): string
    {
        return (string) $this->tenant_id;
    }

    public function getVendorId(): string
    {
        return (string) $this->vendor_id;
    }

    public function getStatus(): string
    {
        return (string) $this->status;
    }

    /**
     * @return array<NormalizationLine>
     */
    public function getNormalizationLines(): array
    {
        /** @var array<int, array<string, mixed>> $lines */
        $lines = $this->normalization_data ?? [];

        $out = [];
        foreach ($lines as $line) {
            $out[] = self::normalizationLineFromStoredRow($line);
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $line
     */
    private static function normalizationLineFromStoredRow(array $line): NormalizationLine
    {
        return new NormalizationLine(
            self::stringFromMixed($line['id'] ?? ''),
            self::stringFromMixed($line['description'] ?? ''),
            self::floatFromMixed($line['quantity'] ?? 0),
            self::stringFromMixed($line['uom'] ?? ''),
            self::floatFromMixed($line['unit_price'] ?? 0),
            self::optionalStringFromMixed($line['rfq_line_id'] ?? null),
        );
    }

    private static function optionalStringFromMixed(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return null;
    }

    private static function stringFromMixed(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return '';
    }

    private static function floatFromMixed(mixed $value): float
    {
        if (is_float($value)) {
            return $value;
        }

        if (is_int($value)) {
            return (float) $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        return 0.0;
    }
}
