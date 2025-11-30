<?php

declare(strict_types=1);

namespace Nexus\Procurement\Contracts;

/**
 * Goods receipt note entity interface.
 */
interface GoodsReceiptNoteInterface
{
    /**
     * Get GRN ID.
     *
     * @return string ULID
     */
    public function getId(): string;

    /**
     * Get GRN number.
     *
     * @return string e.g., "GRN-2024-001"
     */
    public function getGrnNumber(): string;

    /**
     * Get purchase order ID.
     *
     * @return string PO ULID
     */
    public function getPurchaseOrderId(): string;

    /**
     * Get received date.
     *
     * @return \DateTimeImmutable
     */
    public function getReceivedDate(): \DateTimeImmutable;

    /**
     * Get received by user ID.
     *
     * @return string User ULID
     */
    public function getReceivedBy(): string;

    /**
     * Get GRN lines.
     *
     * @return array<GoodsReceiptLineInterface>
     */
    public function getLines(): array;

    /**
     * Get created timestamp.
     *
     * @return \DateTimeImmutable
     */
    public function getCreatedAt(): \DateTimeImmutable;
}
