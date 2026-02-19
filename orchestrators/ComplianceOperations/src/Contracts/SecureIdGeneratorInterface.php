<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Contracts;

/**
 * Defines contract for secure ID generation.
 */
interface SecureIdGeneratorInterface
{
    /**
     * Generate a random hex string.
     *
     * @param int $length Number of bytes (output will be 2x this length)
     * @return string Hex-encoded random string
     */
    public function randomHex(int $length): string;

    /**
     * Generate a UUID v4.
     *
     * @return string UUID v4 string
     */
    public function uuid4(): string;

    /**
     * Generate a time-ordered UUID.
     *
     * @return string Time-ordered UUID string
     */
    public function timeOrderedUuid(): string;
}
