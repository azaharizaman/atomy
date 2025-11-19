<?php

declare(strict_types=1);

namespace App\Services\Import\Handlers;

use App\Models\Customer;
use Nexus\Import\Contracts\ImportHandlerInterface;
use Nexus\Import\ValueObjects\ImportMode;
use Nexus\Import\ValueObjects\ImportError;
use Nexus\Import\ValueObjects\ErrorSeverity;

/**
 * Example Customer Import Handler
 * 
 * Demonstrates how to implement ImportHandlerInterface for a specific entity
 */
final class CustomerImportHandler implements ImportHandlerInterface
{
    public function handle(array $data, ImportMode $mode): void
    {
        $uniqueData = [
            'email' => $data['email'] ?? null,
        ];

        $exists = $this->exists($uniqueData);

        match (true) {
            $mode === ImportMode::CREATE && $exists => throw new \RuntimeException(
                "Customer with email {$data['email']} already exists"
            ),
            $mode === ImportMode::UPDATE && !$exists => throw new \RuntimeException(
                "Customer with email {$data['email']} does not exist"
            ),
            $mode === ImportMode::UPSERT && $exists => Customer::where('email', $data['email'])
                ->update($this->mapToModel($data)),
            default => Customer::create($this->mapToModel($data)),
        };
    }

    public function getUniqueKeyFields(): array
    {
        return ['email'];
    }

    public function getRequiredFields(): array
    {
        return ['name', 'email'];
    }

    public function supportsMode(ImportMode $mode): bool
    {
        // This handler supports all modes
        return true;
    }

    public function exists(array $uniqueData): bool
    {
        return Customer::where('email', $uniqueData['email'])->exists();
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

        // Validate email format
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = new ImportError(
                rowNumber: null,
                field: 'email',
                severity: ErrorSeverity::ERROR,
                code: 'INVALID_EMAIL',
                message: "Invalid email format: {$data['email']}",
                context: ['value' => $data['email']]
            );
        }

        // Validate phone format (optional field)
        if (isset($data['phone']) && !preg_match('/^\+?[0-9\s\-\(\)]{7,20}$/', $data['phone'])) {
            $errors[] = new ImportError(
                rowNumber: null,
                field: 'phone',
                severity: ErrorSeverity::WARNING,
                code: 'INVALID_PHONE',
                message: "Phone number may be invalid: {$data['phone']}",
                context: ['value' => $data['phone']]
            );
        }

        return $errors;
    }

    private function mapToModel(array $data): array
    {
        return [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
        ];
    }
}
