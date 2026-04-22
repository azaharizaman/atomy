<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nexus\Adapter\Laravel\Vendor\Exceptions\UnreadableVendorRecordException;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vendors')) {
            Schema::create('vendors', function (Blueprint $table): void {
                $this->defineVendorColumns($table);
            });

            return;
        }

        Schema::table('vendors', function (Blueprint $table): void {
            $this->addMissingVendorColumns($table);
            $this->addMissingVendorIndexes($table);
        });

        $this->backfillLegacyVendorRows();
    }

    public function down(): void
    {
        // Intentionally no-op. This migration reconciles a shared live table and
        // rollback must not delete vendor data.
    }

    private function defineVendorColumns(Blueprint $table): void
    {
        $table->ulid('id')->primary();
        $table->ulid('tenant_id')->index();
        $table->string('name');
        $table->string('trading_name')->nullable();
        $table->string('registration_number');
        $table->string('tax_id')->nullable();
        $table->string('country_code')->default('MY');
        $table->string('email');
        $table->string('phone')->nullable();
        $table->string('status')->default('draft');
        $table->timestamp('onboarded_at')->nullable();
        $table->json('metadata')->nullable();
        $table->string('legal_name')->default('');
        $table->string('display_name')->default('');
        $table->string('country_of_registration', 2)->default('');
        $table->string('primary_contact_name')->default('');
        $table->string('primary_contact_email')->default('');
        $table->string('primary_contact_phone')->default('');
        $table->string('approved_by_user_id')->nullable();
        $table->timestamp('approved_at')->nullable();
        $table->text('approval_note')->nullable();
        $table->timestamps();

        $table->index(['tenant_id', 'status']);
        $table->index(['tenant_id', 'display_name']);
    }

    private function addMissingVendorColumns(Blueprint $table): void
    {
        $this->addColumnIfMissing($table, 'legal_name', static function (Blueprint $table): void {
            $table->string('legal_name')->default('');
        });
        $this->addColumnIfMissing($table, 'display_name', static function (Blueprint $table): void {
            $table->string('display_name')->default('');
        });
        $this->addColumnIfMissing($table, 'country_of_registration', static function (Blueprint $table): void {
            $table->string('country_of_registration', 2)->default('');
        });
        $this->addColumnIfMissing($table, 'primary_contact_name', static function (Blueprint $table): void {
            $table->string('primary_contact_name')->default('');
        });
        $this->addColumnIfMissing($table, 'primary_contact_email', static function (Blueprint $table): void {
            $table->string('primary_contact_email')->default('');
        });
        $this->addColumnIfMissing($table, 'primary_contact_phone', static function (Blueprint $table): void {
            $table->string('primary_contact_phone')->default('');
        });
        $this->addColumnIfMissing($table, 'approved_by_user_id', static function (Blueprint $table): void {
            $table->string('approved_by_user_id')->nullable();
        });
        $this->addColumnIfMissing($table, 'approved_at', static function (Blueprint $table): void {
            $table->timestamp('approved_at')->nullable();
        });
        $this->addColumnIfMissing($table, 'approval_note', static function (Blueprint $table): void {
            $table->text('approval_note')->nullable();
        });
    }

    private function addMissingVendorIndexes(Blueprint $table): void
    {
        if (! Schema::hasIndex('vendors', ['tenant_id', 'status'])) {
            $table->index(['tenant_id', 'status']);
        }

        if (! Schema::hasIndex('vendors', ['tenant_id', 'display_name'])) {
            $table->index(['tenant_id', 'display_name']);
        }
    }

    private function addColumnIfMissing(Blueprint $table, string $column, callable $callback): void
    {
        if (! Schema::hasColumn('vendors', $column)) {
            $callback($table);
        }
    }

    private function backfillLegacyVendorRows(): void
    {
        foreach (DB::table('vendors')->orderBy('id')->cursor() as $row) {
            $legacyStatus = $this->normalizeLegacyStatus((string) $row->status, (string) $row->id);
            $legalName = $this->firstNonBlank([
                (string) $row->name,
                (string) $row->trading_name,
            ], 'legal_name', (string) $row->id);
            $displayName = $this->firstNonBlank([
                (string) $row->trading_name,
                (string) $row->name,
            ], 'display_name', (string) $row->id);
            $countryOfRegistration = $this->firstNonBlank([
                (string) $row->country_code,
            ], 'country_of_registration', (string) $row->id);
            $registrationNumber = $this->firstNonBlank([
                (string) ($row->registration_number ?? ''),
                (string) ($row->tax_id ?? ''),
            ], 'registration_number', (string) $row->id);
            $primaryContactName = $this->firstNonBlank([
                (string) $row->name,
                (string) $row->trading_name,
            ], 'primary_contact_name', (string) $row->id);
            $primaryContactEmail = $this->firstNonBlank([
                (string) $row->email,
            ], 'primary_contact_email', (string) $row->id);
            $primaryContactPhone = $this->optionalTrim((string) ($row->phone ?? ''));

            DB::table('vendors')
                ->where('id', $row->id)
                ->update([
                    'status' => $legacyStatus,
                    'legal_name' => $legalName,
                    'display_name' => $displayName,
                    'country_of_registration' => $countryOfRegistration,
                    'registration_number' => $registrationNumber,
                    'primary_contact_name' => $primaryContactName,
                    'primary_contact_email' => $primaryContactEmail,
                    'primary_contact_phone' => $primaryContactPhone ?? '',
                ]);
        }
    }

    private function normalizeLegacyStatus(string $status, string $vendorId): string
    {
        $normalized = strtolower(trim($status));

        return match ($normalized) {
            'active' => 'approved',
            'inactive' => 'suspended',
            'draft', 'under_review', 'approved', 'restricted', 'suspended', 'archived' => $normalized,
            '' => throw UnreadableVendorRecordException::missingField('status', $vendorId),
            default => throw UnreadableVendorRecordException::invalidStatus($status, $vendorId),
        };
    }

    /**
     * @param array<int, string> $values
     */
    private function firstNonBlank(array $values, string $field, string $vendorId): string
    {
        foreach ($values as $value) {
            $trimmed = trim($value);

            if ($trimmed !== '') {
                return $trimmed;
            }
        }

        throw UnreadableVendorRecordException::missingField($field, $vendorId);
    }

    private function optionalTrim(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }
};
