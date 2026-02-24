<?php

declare(strict_types=1);

namespace Nexus\GeneralLedger\Tests\Unit\ValueObjects;

use PHPUnit\Framework\TestCase;
use Nexus\GeneralLedger\ValueObjects\PostingResult;
use Nexus\GeneralLedger\Entities\Transaction;
use Nexus\GeneralLedger\Enums\TransactionType;
use Nexus\GeneralLedger\ValueObjects\AccountBalance;
use Nexus\Common\ValueObjects\Money;

final class PostingResultTest extends TestCase
{
    public function test_it_can_create_success_result(): void
    {
        $tx = Transaction::create(
            'tx-id', 'acc-id', 'line-id', 'je-id', 
            TransactionType::DEBIT, 
            AccountBalance::debit(Money::of('100.00', 'USD')),
            AccountBalance::debit(Money::of('100.00', 'USD')),
            'p', new \DateTimeImmutable(), new \DateTimeImmutable()
        );
        
        $result = PostingResult::success($tx, ['key' => 'value']);
        
        $this->assertTrue($result->isSuccessful());
        $this->assertFalse($result->isFailed());
        $this->assertEquals('tx-id', $result->getTransactionId());
        $this->assertEquals('value', $result->getMetadata('key'));
    }

    public function test_it_can_create_failure_result(): void
    {
        $result = PostingResult::failure('ERR_CODE', 'Error Message');
        
        $this->assertFalse($result->isSuccessful());
        $this->assertTrue($result->isFailed());
        $this->assertEquals('ERR_CODE', $result->errorCode);
        $this->assertEquals('Error Message', $result->errorMessage);
    }

    public function test_it_can_create_batch_success(): void
    {
        $result = PostingResult::batchSuccess(['tx-1', 'tx-2']);
        
        $this->assertTrue($result->isSuccessful());
        $this->assertEquals(2, $result->getMetadata('count'));
        $this->assertIsArray($result->getMetadata('transactions'));
    }

    public function test_it_can_convert_to_array(): void
    {
        $result = PostingResult::failure('ERR', 'MSG');
        $array = $result->toArray();
        
        $this->assertFalse($array['success']);
        $this->assertEquals('ERR', $array['error_code']);
    }

    public function test_it_can_create_batch_partial_success(): void
    {
        $result = PostingResult::batchPartialSuccess(['tx-1'], [['index' => 1, 'error' => 'fail']]);
        
        $this->assertFalse($result->isSuccessful());
        $this->assertEquals('PARTIAL_FAILURE', $result->errorCode);
        $this->assertEquals(1, $result->getMetadata('success_count'));
        $this->assertEquals(1, $result->getMetadata('failure_count'));
    }
}
