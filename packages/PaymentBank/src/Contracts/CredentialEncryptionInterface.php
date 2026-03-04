<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Contracts;

use Nexus\PaymentBank\ValueObjects\EncryptedSecret;

interface CredentialEncryptionInterface
{
    public function encrypt(string $plaintext): EncryptedSecret;

    public function decrypt(EncryptedSecret $ciphertext): string;
}
