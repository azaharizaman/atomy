<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\GeneralLedger\ValueObjects\SubledgerPostingRequest;
use Nexus\GeneralLedger\ValueObjects\AccountBalance;
use Nexus\GeneralLedger\Enums\SubledgerType;
use Nexus\GeneralLedger\Enums\TransactionType;
use Nexus\Common\ValueObjects\Money;

final class SubledgerPostingRequestTest extends TestCase
{
    public function test_it_can_be_instantiated(): void
    {
        $amount = AccountBalance::debit(Money::of('100.00', 'USD'));
        $now = new \DateTimeImmutable();
        
        $request = new SubledgerPostingRequest(
            'sub-id',
            SubledgerType::RECEIVABLE,
            'acc-id',
            TransactionType::DEBIT,
            $amount,
            'period-id',
            $now,
            'doc-id',
            'line-id',
            'Description',
            'REF'
        );

        $this->assertEquals('sub-id', $request->subledgerId);
        $this->assertEquals(SubledgerType::RECEIVABLE, $request->subledgerType);
        $this->assertEquals('USD', $request->getCurrency());
        $this->assertEquals('Description', $request->description);
    }
}
