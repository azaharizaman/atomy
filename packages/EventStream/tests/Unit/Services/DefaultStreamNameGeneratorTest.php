<?php

declare(strict_types=1);

namespace Nexus\EventStream\Tests\Unit\Services;

use Nexus\EventStream\Exceptions\InvalidStreamNameException;
use Nexus\EventStream\Services\DefaultStreamNameGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DefaultStreamNameGenerator::class)]
#[Group('eventstream')]
#[Group('stream-naming')]
class DefaultStreamNameGeneratorTest extends TestCase
{
    private DefaultStreamNameGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new DefaultStreamNameGenerator();
    }

    #[Test]
    public function it_generates_canonical_format(): void
    {
        $streamName = $this->generator->generate('finance', 'account', '01JCQR8XYZ1234567890ABCDEF');

        $this->assertSame('finance-account-01jcqr8xyz1234567890abcdef', $streamName);
    }

    #[Test]
    public function it_converts_to_lowercase(): void
    {
        $streamName = $this->generator->generate('Finance', 'Account', '01JCQR8XYZ1234567890ABCDEF');

        $this->assertSame('finance-account-01jcqr8xyz1234567890abcdef', $streamName);
    }

    #[Test]
    public function it_throws_exception_for_empty_context(): void
    {
        $this->expectException(InvalidStreamNameException::class);
        $this->expectExceptionMessage('component "context" cannot be empty');

        $this->generator->generate('', 'account', '01JCQR8XYZ1234567890ABCDEF');
    }

    #[Test]
    public function it_throws_exception_for_empty_aggregate_type(): void
    {
        $this->expectException(InvalidStreamNameException::class);
        $this->expectExceptionMessage('component "aggregateType" cannot be empty');

        $this->generator->generate('finance', '', '01JCQR8XYZ1234567890ABCDEF');
    }

    #[Test]
    public function it_throws_exception_for_empty_aggregate_id(): void
    {
        $this->expectException(InvalidStreamNameException::class);
        $this->expectExceptionMessage('component "aggregateId" cannot be empty');

        $this->generator->generate('finance', 'account', '');
    }

    #[Test]
    public function it_throws_exception_for_whitespace_only_context(): void
    {
        $this->expectException(InvalidStreamNameException::class);
        $this->expectExceptionMessage('component "context" cannot be empty');

        $this->generator->generate('   ', 'account', '01JCQR8XYZ1234567890ABCDEF');
    }

    #[Test]
    public function it_throws_exception_for_name_exceeding_255_chars(): void
    {
        $this->expectException(InvalidStreamNameException::class);
        $this->expectExceptionMessage('exceeds maximum length');

        // Create a stream name that will exceed 255 characters
        $longId = str_repeat('A', 250);
        $this->generator->generate('finance', 'account', $longId);
    }

    #[Test]
    public function it_accepts_valid_255_character_name(): void
    {
        // Create exactly 255 characters: finance-account- (16) + 239 char ID = 255
        $id = str_repeat('A', 239);
        $streamName = $this->generator->generate('finance', 'account', $id);

        $this->assertSame(255, strlen($streamName));
    }

    #[Test]
    #[DataProvider('invalidCharacterProvider')]
    public function it_throws_exception_for_invalid_characters(string $context, string $type, string $id): void
    {
        $this->expectException(InvalidStreamNameException::class);
        $this->expectExceptionMessage('contains invalid characters');

        $this->generator->generate($context, $type, $id);
    }

    public static function invalidCharacterProvider(): array
    {
        return [
            'space in context' => ['fin ance', 'account', '01JCQR'],
            'slash in type' => ['finance', 'account/savings', '01JCQR'],
            'special chars in id' => ['finance', 'account', '01JCQR@#$'],
            'dot in context' => ['finance.gl', 'account', '01JCQR'],
            'colon in type' => ['finance', 'account:checking', '01JCQR'],
        ];
    }

    #[Test]
    public function it_allows_hyphens_and_underscores(): void
    {
        $streamName = $this->generator->generate('finance_gl', 'account-checking', '01JCQR-8XYZ_123');

        $this->assertStringContainsString('finance_gl', $streamName);
        $this->assertStringContainsString('account-checking', $streamName);
        $this->assertStringContainsString('01jcqr-8xyz_123', $streamName);
    }

    #[Test]
    public function it_allows_numeric_components(): void
    {
        $streamName = $this->generator->generate('finance123', 'account456', '789');

        $this->assertSame('finance123-account456-789', $streamName);
    }

    #[Test]
    public function it_is_idempotent(): void
    {
        $name1 = $this->generator->generate('finance', 'account', '01JCQR8XYZ');
        $name2 = $this->generator->generate('finance', 'account', '01JCQR8XYZ');

        $this->assertSame($name1, $name2);
    }

    #[Test]
    public function it_generates_unique_names_for_different_aggregates(): void
    {
        $name1 = $this->generator->generate('finance', 'account', '01JCQR8XYZ1');
        $name2 = $this->generator->generate('finance', 'account', '01JCQR8XYZ2');

        $this->assertNotSame($name1, $name2);
    }

    #[Test]
    public function it_generates_different_names_for_different_contexts(): void
    {
        $name1 = $this->generator->generate('finance', 'account', '01JCQR8XYZ');
        $name2 = $this->generator->generate('inventory', 'account', '01JCQR8XYZ');

        $this->assertNotSame($name1, $name2);
    }
}
