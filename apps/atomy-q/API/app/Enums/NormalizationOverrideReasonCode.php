<?php

declare(strict_types=1);

namespace App\Enums;

enum NormalizationOverrideReasonCode: string
{
    case SupplierDocumentMismatch = 'supplier_document_mismatch';
    case RfqMappingIncorrect = 'rfq_mapping_incorrect';
    case QuantityOrUomCorrection = 'quantity_or_uom_correction';
    case PriceCorrection = 'price_correction';
    case ManualEntryRequired = 'manual_entry_required';
    case Other = 'other';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $case): string => $case->value,
            self::cases(),
        );
    }
}
