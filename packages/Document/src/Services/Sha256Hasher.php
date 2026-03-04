<?php

declare(strict_types=1);

namespace Nexus\Document\Services;

use Nexus\Document\Contracts\HasherInterface;

/**
 * SHA-256 implementation of the document hasher.
 */
final class Sha256Hasher implements HasherInterface
{
    /**
     * {@inheritdoc}
     */
    public function hash(string $content): string
    {
        return hash('sha256', $content);
    }
}
