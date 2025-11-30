<?php

declare(strict_types=1);

namespace Nexus\Sales\Services;

use DateTimeImmutable;
use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\Sales\Contracts\QuotationInterface;
use Nexus\Sales\Contracts\QuotationRepositoryInterface;
use Nexus\Sales\Enums\QuoteStatus;
use Nexus\Sales\Exceptions\InvalidQuoteStatusException;
use Nexus\Sales\Exceptions\QuotationNotFoundException;
use Nexus\Sequencing\Contracts\SequenceGeneratorInterface;
use Psr\Log\LoggerInterface;

/**
 * Quotation lifecycle management service.
 */
final readonly class QuotationManager
{
    public function __construct(
        private QuotationRepositoryInterface $quotationRepository,
        private SequenceGeneratorInterface $sequenceGenerator,
        private AuditLogManagerInterface $auditLogger,
        private LoggerInterface $logger
    ) {}

    /**
     * Create a new quotation (draft status).
     *
     * @param string $tenantId
     * @param string $customerId
     * @param array $lines Array of line data
     * @param array $data Additional quotation data
     * @return QuotationInterface
     */
    public function createQuotation(
        string $tenantId,
        string $customerId,
        array $lines,
        array $data
    ): QuotationInterface {
        // Generate quote number
        $quoteNumber = $this->sequenceGenerator->generate(
            $tenantId,
            'sales_quotation',
            ['prefix' => 'QT']
        );

        // Create quotation entity (implementation-specific, will be in Atomy)
        // For now, this signature serves as the contract definition
        
        $this->logger->info('Quotation created', [
            'tenant_id' => $tenantId,
            'quote_number' => $quoteNumber,
            'customer_id' => $customerId,
        ]);

        return $quotation ?? throw new \RuntimeException('Quotation creation not implemented in package layer');
    }

    /**
     * Send quotation to customer.
     *
     * @param string $quotationId
     * @return void
     * @throws QuotationNotFoundException
     * @throws InvalidQuoteStatusException
     */
    public function sendQuotation(string $quotationId): void
    {
        $quotation = $this->quotationRepository->findById($quotationId);

        if (!$quotation->getStatus()->canBeSent()) {
            throw InvalidQuoteStatusException::cannotSend($quotationId, $quotation->getStatus());
        }

        // Update status to SENT
        // (Implementation-specific mutation will be in Atomy's Eloquent model)
        
        $this->auditLogger->log(
            $quotationId,
            'quotation_sent',
            "Quotation {$quotation->getQuoteNumber()} sent to customer"
        );

        $this->logger->info('Quotation sent', [
            'quotation_id' => $quotationId,
            'quote_number' => $quotation->getQuoteNumber(),
        ]);
    }

    /**
     * Mark quotation as accepted.
     *
     * @param string $quotationId
     * @return void
     * @throws QuotationNotFoundException
     * @throws InvalidQuoteStatusException
     */
    public function acceptQuotation(string $quotationId): void
    {
        $quotation = $this->quotationRepository->findById($quotationId);

        if (!$quotation->getStatus()->canTransitionTo(QuoteStatus::ACCEPTED)) {
            throw InvalidQuoteStatusException::cannotTransition(
                $quotationId,
                $quotation->getStatus(),
                QuoteStatus::ACCEPTED
            );
        }

        // Update status to ACCEPTED
        // (Implementation-specific mutation will be in Atomy's Eloquent model)

        $this->auditLogger->log(
            $quotationId,
            'quotation_accepted',
            "Quotation {$quotation->getQuoteNumber()} accepted by customer"
        );

        $this->logger->info('Quotation accepted', [
            'quotation_id' => $quotationId,
            'quote_number' => $quotation->getQuoteNumber(),
        ]);
    }

    /**
     * Reject quotation.
     *
     * @param string $quotationId
     * @param string|null $reason
     * @return void
     * @throws QuotationNotFoundException
     * @throws InvalidQuoteStatusException
     */
    public function rejectQuotation(string $quotationId, ?string $reason = null): void
    {
        $quotation = $this->quotationRepository->findById($quotationId);

        if (!$quotation->getStatus()->canTransitionTo(QuoteStatus::REJECTED)) {
            throw InvalidQuoteStatusException::cannotTransition(
                $quotationId,
                $quotation->getStatus(),
                QuoteStatus::REJECTED
            );
        }

        // Update status to REJECTED
        // (Implementation-specific mutation will be in Atomy's Eloquent model)

        $this->auditLogger->log(
            $quotationId,
            'quotation_rejected',
            "Quotation {$quotation->getQuoteNumber()} rejected" . ($reason ? ": {$reason}" : '')
        );

        $this->logger->info('Quotation rejected', [
            'quotation_id' => $quotationId,
            'quote_number' => $quotation->getQuoteNumber(),
            'reason' => $reason,
        ]);
    }

    /**
     * Mark quotation as expired.
     *
     * @param string $quotationId
     * @return void
     * @throws QuotationNotFoundException
     */
    public function expireQuotation(string $quotationId): void
    {
        $quotation = $this->quotationRepository->findById($quotationId);

        // Update status to EXPIRED
        // (Implementation-specific mutation will be in Atomy's Eloquent model)

        $this->auditLogger->log(
            $quotationId,
            'quotation_expired',
            "Quotation {$quotation->getQuoteNumber()} expired"
        );

        $this->logger->info('Quotation expired', [
            'quotation_id' => $quotationId,
            'quote_number' => $quotation->getQuoteNumber(),
        ]);
    }

    /**
     * Find quotation by ID.
     *
     * @param string $quotationId
     * @return QuotationInterface
     * @throws QuotationNotFoundException
     */
    public function findQuotation(string $quotationId): QuotationInterface
    {
        return $this->quotationRepository->findById($quotationId);
    }

    /**
     * Find quotations by customer.
     *
     * @param string $tenantId
     * @param string $customerId
     * @return QuotationInterface[]
     */
    public function findQuotationsByCustomer(string $tenantId, string $customerId): array
    {
        return $this->quotationRepository->findByCustomer($tenantId, $customerId);
    }
}
