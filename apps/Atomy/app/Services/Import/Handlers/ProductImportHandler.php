<?php

declare(strict_types=1);

namespace App\Services\Import\Handlers;

use App\Models\Product;
use Nexus\Import\Contracts\ImportHandlerInterface;
use Nexus\Import\ValueObjects\ImportMode;
use Nexus\Import\ValueObjects\ImportError;
use Nexus\Import\ValueObjects\ErrorSeverity;

/**
 * Example Product Import Handler
 * 
 * Demonstrates how to implement ImportHandlerInterface for products with SKU validation
 */
final class ProductImportHandler implements ImportHandlerInterface
{
    public function handle(array $data, ImportMode $mode): void
    {
        $uniqueData = [
            'sku' => $data['sku'] ?? null,
        ];

        $exists = $this->exists($uniqueData);

        match (true) {
            $mode === ImportMode::CREATE && $exists => throw new \RuntimeException(
                "Product with SKU {$data['sku']} already exists"
            ),
            $mode === ImportMode::UPDATE && !$exists => throw new \RuntimeException(
                "Product with SKU {$data['sku']} does not exist"
            ),
            $mode === ImportMode::UPSERT && $exists => Product::where('sku', $data['sku'])
                ->update($this->mapToModel($data)),
            default => Product::create($this->mapToModel($data)),
        };
    }

    public function getUniqueKeyFields(): array
    {
        return ['sku'];
    }

    public function getRequiredFields(): array
    {
        return ['sku', 'name', 'price'];
    }

    public function supportsMode(ImportMode $mode): bool
    {
        // This handler supports all modes
        return true;
    }

    public function exists(array $uniqueData): bool
    {
        return Product::where('sku', $uniqueData['sku'])->exists();
    }

    public function validateData(array $data): array
    {
        $errors = [];

        // Validate required fields
        foreach ($this->getRequiredFields() as $field) {
            if (empty($data[$field])) {
                $errors[] = new ImportError(
                    rowNumber: null,
                    field: $field,
                    severity: ErrorSeverity::ERROR,
                    message: "Field '{$field}' is required",
                    code: 'REQUIRED_FIELD_MISSING',
                    context: []
                );
            }
        }

        // Validate SKU format (alphanumeric, dashes, underscores only)
        if (isset($data['sku']) && !preg_match('/^[A-Z0-9\-_]{3,50}$/i', $data['sku'])) {
            $errors[] = new ImportError(
                rowNumber: null,
                field: 'sku',
                severity: ErrorSeverity::ERROR,
                code: 'INVALID_SKU',
                message: "SKU must be 3-50 alphanumeric characters: {$data['sku']}",
                context: ['value' => $data['sku']]
            );
        }

        // Validate price is numeric and positive
        if (isset($data['price'])) {
            if (!is_numeric($data['price'])) {
                $errors[] = new ImportError(
                    rowNumber: null,
                    field: 'price',
                    severity: ErrorSeverity::ERROR,
                    code: 'INVALID_PRICE',
                    message: "Price must be numeric: {$data['price']}",
                    context: ['value' => $data['price']]
                );
            } elseif ((float)$data['price'] < 0) {
                $errors[] = new ImportError(
                    rowNumber: null,
                    field: 'price',
                    severity: ErrorSeverity::ERROR,
                    code: 'NEGATIVE_PRICE',
                    message: "Price cannot be negative: {$data['price']}",
                    context: ['value' => $data['price']]
                );
            }
        }

        // Validate quantity (if provided)
        if (isset($data['quantity']) && (!is_numeric($data['quantity']) || (int)$data['quantity'] < 0)) {
            $errors[] = new ImportError(
                rowNumber: null,
                field: 'quantity',
                severity: ErrorSeverity::WARNING,
                code: 'INVALID_QUANTITY',
                message: "Quantity must be non-negative integer: {$data['quantity']}",
                context: ['value' => $data['quantity']]
            );
        }

        return $errors;
    }

    private function mapToModel(array $data): array
    {
        return [
            'sku' => $data['sku'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'cost' => $data['cost'] ?? null,
            'quantity' => $data['quantity'] ?? 0,
            'category' => $data['category'] ?? null,
            'weight' => $data['weight'] ?? null,
            'dimensions' => $data['dimensions'] ?? null,
        ];
    }
}
