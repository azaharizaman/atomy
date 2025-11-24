<?php

declare(strict_types=1);

namespace Nexus\Tax\Exceptions;

/**
 * Exemption Certificate Expired Exception
 * 
 * Thrown when attempting to use an expired exemption certificate.
 */
final class ExemptionCertificateExpiredException extends \RuntimeException
{
    public function __construct(
        private readonly string $certificateId,
        private readonly \DateTimeInterface $expirationDate,
        private readonly \DateTimeInterface $attemptedUseDate,
        ?\Throwable $previous = null
    ) {
        $message = sprintf(
            "Exemption certificate '%s' expired on %s (attempted use: %s)",
            $certificateId,
            $expirationDate->format('Y-m-d'),
            $attemptedUseDate->format('Y-m-d')
        );

        parent::__construct($message, 0, $previous);
    }

    public function getCertificateId(): string
    {
        return $this->certificateId;
    }

    public function getExpirationDate(): \DateTimeInterface
    {
        return $this->expirationDate;
    }

    public function getAttemptedUseDate(): \DateTimeInterface
    {
        return $this->attemptedUseDate;
    }

    public function getContext(): array
    {
        return [
            'certificate_id' => $this->certificateId,
            'expiration_date' => $this->expirationDate->format('Y-m-d'),
            'attempted_use_date' => $this->attemptedUseDate->format('Y-m-d'),
        ];
    }
}
