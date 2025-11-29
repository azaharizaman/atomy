<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Service\PasswordHasherAdapter;
use PHPUnit\Framework\TestCase;

final class PasswordHasherAdapterTest extends TestCase
{
    public function testHashAndVerify(): void
    {
        $adapter = new PasswordHasherAdapter();

        $plain = 's3cureP@ssw0rd';

        $hash = $adapter->hash($plain);

        $this->assertIsString($hash);
        $this->assertNotEmpty($hash);

        $this->assertTrue($adapter->verify($plain, $hash));
        $this->assertFalse($adapter->verify('wrong', $hash));
    }

    public function testNeedsRehash(): void
    {
        $adapter = new PasswordHasherAdapter();

        $plain = 'anotherSecret';
        $hash = $adapter->hash($plain);

        // The adapter should not claim rehash immediately for a freshly created hash
        $this->assertIsBool($adapter->needsRehash($hash));
        $this->assertFalse($adapter->needsRehash($hash));
    }
}
