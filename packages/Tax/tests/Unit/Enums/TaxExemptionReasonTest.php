<?php

declare(strict_types=1);

namespace Nexus\Tax\Tests\Unit\Enums;

use Nexus\Tax\Enums\TaxExemptionReason;
use PHPUnit\Framework\TestCase;

final class TaxExemptionReasonTest extends TestCase
{
    public function test_it_has_expected_cases(): void
    {
        $this->assertSame('resale', TaxExemptionReason::Resale->value);
        $this->assertSame('government', TaxExemptionReason::Government->value);
        $this->assertSame('nonprofit', TaxExemptionReason::Nonprofit->value);
        $this->assertSame('export', TaxExemptionReason::Export->value);
        $this->assertSame('diplomatic', TaxExemptionReason::Diplomatic->value);
        $this->assertSame('agricultural', TaxExemptionReason::Agricultural->value);
    }

    public function test_label_returns_human_readable_name(): void
    {
        $this->assertSame('Resale Certificate', TaxExemptionReason::Resale->label());
        $this->assertSame('Government Entity', TaxExemptionReason::Government->label());
        $this->assertSame('Nonprofit Organization', TaxExemptionReason::Nonprofit->label());
        $this->assertSame('International Export', TaxExemptionReason::Export->label());
        $this->assertSame('Diplomatic Immunity', TaxExemptionReason::Diplomatic->label());
        $this->assertSame('Agricultural Producer', TaxExemptionReason::Agricultural->label());
    }

    public function test_typically_full_exemption_logic(): void
    {
        // Full exemptions (100%)
        $this->assertTrue(TaxExemptionReason::Government->isTypicallyFullExemption());
        $this->assertTrue(TaxExemptionReason::Export->isTypicallyFullExemption());
        $this->assertTrue(TaxExemptionReason::Diplomatic->isTypicallyFullExemption());

        // Partial exemptions (may vary)
        $this->assertFalse(TaxExemptionReason::Resale->isTypicallyFullExemption());
        $this->assertFalse(TaxExemptionReason::Nonprofit->isTypicallyFullExemption());
        $this->assertFalse(TaxExemptionReason::Agricultural->isTypicallyFullExemption());
    }
}
