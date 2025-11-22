<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Period;
use Illuminate\Database\Eloquent\Factories\Factory;
use Nexus\Period\Enums\PeriodType;
use Nexus\Period\Enums\PeriodStatus;

/**
 * Period Factory
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Period>
 */
class PeriodFactory extends Factory
{
    protected $model = Period::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 year', 'now');
        $endDate = (clone $startDate)->modify('+1 month -1 day');
        
        return [
            'type' => PeriodType::Monthly,
            'status' => PeriodStatus::Open,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'fiscal_year' => 'FY-' . $startDate->format('Y'),
            'name' => $startDate->format('F Y'),
            'description' => null,
        ];
    }

    /**
     * Set period type to Monthly.
     * 
     * @return static
     */
    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PeriodType::Monthly,
        ]);
    }

    /**
     * Set period type to Quarterly.
     * 
     * @return static
     */
    public function quarterly(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = \DateTimeImmutable::createFromMutable($attributes['start_date']);
            $endDate = $startDate->modify('+3 months -1 day');
            
            return [
                'type' => PeriodType::Quarterly,
                'end_date' => $endDate,
                'name' => 'Q' . ceil((int)$startDate->format('n') / 3) . ' ' . $startDate->format('Y'),
            ];
        });
    }

    /**
     * Set period type to Yearly.
     * 
     * @return static
     */
    public function yearly(): static
    {
        return $this->state(function (array $attributes) {
            $startDate = \DateTimeImmutable::createFromMutable($attributes['start_date']);
            $endDate = $startDate->modify('+1 year -1 day');
            
            return [
                'type' => PeriodType::Yearly,
                'end_date' => $endDate,
                'name' => 'FY-' . $startDate->format('Y'),
            ];
        });
    }

    /**
     * Set period status to Open.
     * 
     * @return static
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PeriodStatus::Open,
        ]);
    }

    /**
     * Set period status to Closed.
     * 
     * @return static
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PeriodStatus::Closed,
        ]);
    }

    /**
     * Set period status to Locked.
     * 
     * @return static
     */
    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PeriodStatus::Locked,
        ]);
    }

    /**
     * Set period for current month.
     * 
     * @return static
     */
    public function currentMonth(): static
    {
        return $this->state(function (array $attributes) {
            $now = new \DateTimeImmutable();
            $startDate = $now->modify('first day of this month');
            $endDate = $now->modify('last day of this month');
            
            return [
                'type' => PeriodType::Monthly,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'fiscal_year' => 'FY-' . $now->format('Y'),
                'name' => $now->format('F Y'),
            ];
        });
    }

    /**
     * Set custom fiscal year.
     * 
     * @param string $fiscalYear Fiscal year (e.g., 'FY-2024')
     * @return static
     */
    public function fiscalYear(string $fiscalYear): static
    {
        return $this->state(fn (array $attributes) => [
            'fiscal_year' => $fiscalYear,
        ]);
    }

    /**
     * Set custom name.
     * 
     * @param string $name Period name
     * @return static
     */
    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
        ]);
    }

    /**
     * Set custom date range.
     * 
     * @param \DateTimeInterface $startDate Start date
     * @param \DateTimeInterface $endDate End date
     * @return static
     */
    public function dateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }

    /**
     * Set description.
     * 
     * @param string $description Period description
     * @return static
     */
    public function withDescription(string $description): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => $description,
        ]);
    }
}
