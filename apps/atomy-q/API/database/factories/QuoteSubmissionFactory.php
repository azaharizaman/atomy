<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\QuoteSubmission;
use App\Models\Rfq;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

final class QuoteSubmissionFactory extends Factory
{
    protected $model = QuoteSubmission::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'rfq_id' => function (array $attributes) {
                return Rfq::factory()->create(['tenant_id' => $attributes['tenant_id']])->id;
            },
            'vendor_id' => function (array $attributes) {
                $vendor = Vendor::factory()->create(['tenant_id' => $attributes['tenant_id']]);
                return $vendor->id;
            },
            'vendor_name' => function (array $attributes) {
                $vendor = Vendor::find($attributes['vendor_id']);
                return $vendor->legal_name;
            },
            'uploaded_by' => function (array $attributes) {
                return User::factory()->create(['tenant_id' => $attributes['tenant_id']])->id;
            },
            'file_path' => 'quotes/' . fake()->uuid() . '.pdf',
            'file_type' => 'application/pdf',
            'original_filename' => fake()->word() . '.pdf',
            'status' => 'uploaded',
            'submitted_at' => now(),
            'confidence' => null,
            'line_items_count' => 0,
            'warnings_count' => 0,
            'errors_count' => 0,
            'error_code' => null,
            'error_message' => null,
            'processing_started_at' => null,
            'processing_completed_at' => null,
            'parsed_at' => null,
            'retry_count' => 0,
        ];
    }

    public function uploaded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'uploaded',
            'submitted_at' => now(),
        ]);
    }

    public function extracting(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'extracting',
            'processing_started_at' => now(),
        ]);
    }

    public function extracted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'extracted',
            'processing_started_at' => now(),
            'processing_completed_at' => now(),
        ]);
    }

    public function normalizing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'normalizing',
            'processing_started_at' => now(),
            'processing_completed_at' => now(),
        ]);
    }

    public function ready(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ready',
            'processing_started_at' => now(),
            'processing_completed_at' => now(),
            'parsed_at' => now(),
            'confidence' => fake()->randomFloat(2, 0.85, 1.0),
            'line_items_count' => fake()->numberBetween(3, 25),
            'warnings_count' => fake()->numberBetween(0, 3),
        ]);
    }

    public function needsReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'needs_review',
            'processing_started_at' => now(),
            'processing_completed_at' => now(),
            'parsed_at' => now(),
            'confidence' => fake()->randomFloat(2, 0.5, 0.84),
            'line_items_count' => fake()->numberBetween(3, 25),
            'warnings_count' => fake()->numberBetween(4, 10),
        ]);
    }

    public function failed(string $errorCode = 'EXTRACTION_FAILED'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_code' => $errorCode,
            'error_message' => fake()->sentence(),
            'processing_started_at' => now(),
            'processing_completed_at' => now(),
        ]);
    }

    public function forRfq(Rfq $rfq): static
    {
        return $this->state(fn (array $attributes) => [
            'rfq_id' => $rfq->id,
            'tenant_id' => $rfq->tenant_id,
            'uploaded_by' => User::factory()->create(['tenant_id' => $rfq->tenant_id])->id,
            'vendor_id' => Vendor::factory()->create(['tenant_id' => $rfq->tenant_id])->id,
            'vendor_name' => function (array $attributes) {
                $vendor = Vendor::find($attributes['vendor_id']);
                return $vendor->legal_name;
            },
        ]);
    }

    public function forVendor(Vendor $vendor): static
    {
        return $this->state(fn (array $attributes) => [
            'vendor_id' => $vendor->id,
            'vendor_name' => $vendor->legal_name,
            'tenant_id' => $vendor->tenant_id,
            'uploaded_by' => User::factory()->create(['tenant_id' => $vendor->tenant_id])->id,
            'rfq_id' => Rfq::factory()->create(['tenant_id' => $vendor->tenant_id])->id,
        ]);
    }
}