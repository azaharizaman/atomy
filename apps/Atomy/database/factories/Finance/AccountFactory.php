<?php

declare(strict_types=1);

namespace Database\Factories\Finance;

use App\Models\Finance\Account;
use Illuminate\Database\Eloquent\Factories\Factory;
use Nexus\Finance\Enums\AccountType;

/**
 * Account Factory
 * 
 * @extends Factory<Account>
 */
final class AccountFactory extends Factory
{
    protected $model = Account::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->numerify('####'),
            'name' => $this->faker->words(3, true),
            'account_type' => $this->faker->randomElement(AccountType::cases()),
            'currency' => 'MYR',
            'parent_id' => null,
            'is_header' => false,
            'is_active' => true,
            'description' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Mark account as an asset account.
     * 
     * @return static
     */
    public function asset(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => AccountType::Asset,
        ]);
    }

    /**
     * Mark account as a liability account.
     * 
     * @return static
     */
    public function liability(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => AccountType::Liability,
        ]);
    }

    /**
     * Mark account as an equity account.
     * 
     * @return static
     */
    public function equity(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => AccountType::Equity,
        ]);
    }

    /**
     * Mark account as a revenue account.
     * 
     * @return static
     */
    public function revenue(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => AccountType::Revenue,
        ]);
    }

    /**
     * Mark account as an expense account.
     * 
     * @return static
     */
    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => AccountType::Expense,
        ]);
    }

    /**
     * Mark account as active.
     * 
     * @return static
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Mark account as inactive.
     * 
     * @return static
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Mark account as a header/group account.
     * 
     * @return static
     */
    public function header(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_header' => true,
        ]);
    }

    /**
     * Set parent account.
     * 
     * @param string $parentId Parent account ID
     * @return static
     */
    public function withParent(string $parentId): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parentId,
        ]);
    }

    /**
     * Set specific account code.
     * 
     * @param string $code Account code
     * @return static
     */
    public function withCode(string $code): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => $code,
        ]);
    }
}
