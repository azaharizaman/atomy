<?php

declare(strict_types=1);

namespace Nexus\Payment\Tests\Unit\ValueObjects;

use Nexus\Payment\Exceptions\InvalidRecipientInfoException;
use Nexus\Payment\ValueObjects\RecipientInfo;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(RecipientInfo::class)]
final class RecipientInfoTest extends TestCase
{
    #[Test]
    public function it_can_be_created_with_basic_info(): void
    {
        $recipient = new RecipientInfo('RCP-001', 'John Doe');

        $this->assertSame('RCP-001', $recipient->id);
        $this->assertSame('John Doe', $recipient->name);
        $this->assertNull($recipient->accountId);
        $this->assertNull($recipient->email);
        $this->assertSame([], $recipient->metadata);
    }

    #[Test]
    public function it_can_be_created_with_all_fields(): void
    {
        $recipient = new RecipientInfo(
            'RCP-001',
            'John Doe',
            'ACC-123',
            'john@example.com',
            ['department' => 'Sales'],
        );

        $this->assertSame('RCP-001', $recipient->id);
        $this->assertSame('John Doe', $recipient->name);
        $this->assertSame('ACC-123', $recipient->accountId);
        $this->assertSame('john@example.com', $recipient->email);
        $this->assertSame(['department' => 'Sales'], $recipient->metadata);
    }

    #[Test]
    public function it_throws_exception_for_empty_id(): void
    {
        $this->expectException(InvalidRecipientInfoException::class);
        $this->expectExceptionMessage('ID cannot be empty');

        new RecipientInfo('', 'John Doe');
    }

    #[Test]
    public function it_throws_exception_for_whitespace_only_id(): void
    {
        $this->expectException(InvalidRecipientInfoException::class);
        $this->expectExceptionMessage('ID cannot be empty');

        new RecipientInfo('   ', 'John Doe');
    }

    #[Test]
    public function it_throws_exception_for_empty_name(): void
    {
        $this->expectException(InvalidRecipientInfoException::class);
        $this->expectExceptionMessage('name cannot be empty');

        new RecipientInfo('RCP-001', '');
    }

    #[Test]
    public function it_throws_exception_for_invalid_email(): void
    {
        $this->expectException(InvalidRecipientInfoException::class);
        $this->expectExceptionMessage('Invalid recipient email format');

        new RecipientInfo('RCP-001', 'John Doe', null, 'invalid-email');
    }

    #[Test]
    public function it_accepts_null_email(): void
    {
        $recipient = new RecipientInfo('RCP-001', 'John Doe', null, null);

        $this->assertNull($recipient->email);
    }

    #[Test]
    public function it_can_be_created_using_factory_method(): void
    {
        $recipient = RecipientInfo::create('RCP-001', 'John Doe');

        $this->assertSame('RCP-001', $recipient->id);
        $this->assertSame('John Doe', $recipient->name);
    }

    #[Test]
    public function it_can_be_created_with_account(): void
    {
        $recipient = RecipientInfo::withAccount('RCP-001', 'John Doe', 'ACC-123');

        $this->assertSame('ACC-123', $recipient->accountId);
    }

    #[Test]
    public function it_checks_if_has_account(): void
    {
        $withAccount = RecipientInfo::withAccount('RCP-001', 'John Doe', 'ACC-123');
        $withoutAccount = RecipientInfo::create('RCP-001', 'John Doe');

        $this->assertTrue($withAccount->hasAccount());
        $this->assertFalse($withoutAccount->hasAccount());
    }

    #[Test]
    public function it_checks_if_has_email(): void
    {
        $withEmail = new RecipientInfo('RCP-001', 'John Doe', null, 'john@example.com');
        $withoutEmail = RecipientInfo::create('RCP-001', 'John Doe');

        $this->assertTrue($withEmail->hasEmail());
        $this->assertFalse($withoutEmail->hasEmail());
    }

    #[Test]
    public function it_retrieves_metadata(): void
    {
        $recipient = new RecipientInfo(
            'RCP-001',
            'John Doe',
            null,
            null,
            ['department' => 'Sales', 'region' => 'APAC'],
        );

        $this->assertSame('Sales', $recipient->getMetadata('department'));
        $this->assertSame('APAC', $recipient->getMetadata('region'));
        $this->assertNull($recipient->getMetadata('nonexistent'));
        $this->assertSame('default', $recipient->getMetadata('nonexistent', 'default'));
    }

    #[Test]
    public function it_creates_copy_with_email(): void
    {
        $original = RecipientInfo::create('RCP-001', 'John Doe');
        $withEmail = $original->withEmail('john@example.com');

        $this->assertNull($original->email);
        $this->assertSame('john@example.com', $withEmail->email);
        $this->assertSame($original->id, $withEmail->id);
        $this->assertSame($original->name, $withEmail->name);
    }

    #[Test]
    public function it_creates_copy_with_metadata(): void
    {
        $original = RecipientInfo::create('RCP-001', 'John Doe');
        $withMetadata = $original->withMetadata(['department' => 'Finance']);

        $this->assertSame([], $original->metadata);
        $this->assertSame(['department' => 'Finance'], $withMetadata->metadata);
    }
}
