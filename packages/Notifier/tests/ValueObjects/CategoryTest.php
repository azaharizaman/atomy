<?php

declare(strict_types=1);

namespace Nexus\Notifier\Tests\ValueObjects;

use Nexus\Notifier\ValueObjects\Category;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Category::class)]
final class CategoryTest extends TestCase
{
    #[Test]
    public function it_has_correct_enum_cases(): void
    {
        $this->assertSame('system', Category::System->value);
        $this->assertSame('marketing', Category::Marketing->value);
        $this->assertSame('transactional', Category::Transactional->value);
        $this->assertSame('security', Category::Security->value);
    }

    #[Test]
    public function it_can_be_created_from_string(): void
    {
        $this->assertSame(Category::System, Category::from('system'));
        $this->assertSame(Category::Marketing, Category::from('marketing'));
        $this->assertSame(Category::Transactional, Category::from('transactional'));
        $this->assertSame(Category::Security, Category::from('security'));
    }

    #[Test]
    public function it_throws_exception_for_invalid_value(): void
    {
        $this->expectException(\ValueError::class);
        Category::from('invalid');
    }
}
