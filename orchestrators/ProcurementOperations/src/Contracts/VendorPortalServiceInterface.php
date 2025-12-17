<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Contracts;

use Nexus\ProcurementOperations\DTOs\Vendor\VendorApiCredentials;
use Nexus\ProcurementOperations\DTOs\Vendor\VendorPortalSession;
use Nexus\ProcurementOperations\DTOs\Vendor\VendorProfileData;
use Nexus\ProcurementOperations\DTOs\Vendor\VendorRatingData;
use Nexus\ProcurementOperations\Enums\VendorPortalTier;

/**
 * VendorPortalServiceInterface - Contract for vendor self-service portal.
 *
 * This interface defines operations available to vendors through their
 * self-service portal, including profile management, document access,
 * and API credential management.
 */
interface VendorPortalServiceInterface
{
    /**
     * Authenticate vendor and create portal session.
     *
     * @param string $vendorId Vendor ID
     * @param string $credential Authentication credential
     * @param string $authMethod Authentication method (password, api_key, sso)
     * @param string|null $ipAddress Client IP address
     * @return VendorPortalSession|null Session or null if auth failed
     */
    public function authenticate(
        string $vendorId,
        string $credential,
        string $authMethod = 'password',
        ?string $ipAddress = null,
    ): ?VendorPortalSession;

    /**
     * Get vendor profile data.
     *
     * @param string $vendorId Vendor ID
     * @return VendorProfileData|null Profile or null if not found
     */
    public function getProfile(string $vendorId): ?VendorProfileData;

    /**
     * Update vendor profile.
     *
     * @param string $vendorId Vendor ID
     * @param string $updatedBy User making the update
     * @param array<string, mixed> $updates Fields to update
     * @return VendorProfileData Updated profile
     * @throws \InvalidArgumentException If updates are invalid
     */
    public function updateProfile(
        string $vendorId,
        string $updatedBy,
        array $updates,
    ): VendorProfileData;

    /**
     * Get vendor's current performance rating.
     *
     * @param string $vendorId Vendor ID
     * @return VendorRatingData|null Rating or null if not found
     */
    public function getRating(string $vendorId): ?VendorRatingData;

    /**
     * Get vendor's purchase orders (read-only view).
     *
     * @param string $vendorId Vendor ID
     * @param string|null $status Filter by status
     * @param int $limit Maximum results
     * @param int $offset Pagination offset
     * @return array<array{
     *     po_number: string,
     *     status: string,
     *     total_amount: float,
     *     currency: string,
     *     created_at: string,
     * }>
     */
    public function getPurchaseOrders(
        string $vendorId,
        ?string $status = null,
        int $limit = 50,
        int $offset = 0,
    ): array;

    /**
     * Get vendor's invoices.
     *
     * @param string $vendorId Vendor ID
     * @param string|null $status Filter by status
     * @param int $limit Maximum results
     * @param int $offset Pagination offset
     * @return array<array{
     *     invoice_number: string,
     *     status: string,
     *     total_amount: float,
     *     currency: string,
     *     due_date: string,
     *     payment_status: string,
     * }>
     */
    public function getInvoices(
        string $vendorId,
        ?string $status = null,
        int $limit = 50,
        int $offset = 0,
    ): array;

    /**
     * Get vendor's payments.
     *
     * @param string $vendorId Vendor ID
     * @param int $limit Maximum results
     * @param int $offset Pagination offset
     * @return array<array{
     *     payment_id: string,
     *     amount: float,
     *     currency: string,
     *     payment_date: string,
     *     reference: string,
     *     invoices_paid: array<string>,
     * }>
     */
    public function getPayments(
        string $vendorId,
        int $limit = 50,
        int $offset = 0,
    ): array;

    /**
     * Upload a document to vendor's document repository.
     *
     * @param string $vendorId Vendor ID
     * @param string $documentType Document type (invoice, certification, etc.)
     * @param string $filename Original filename
     * @param string $content File content
     * @param array<string, mixed> $metadata Additional metadata
     * @return string Document ID
     */
    public function uploadDocument(
        string $vendorId,
        string $documentType,
        string $filename,
        string $content,
        array $metadata = [],
    ): string;

    /**
     * List vendor's documents.
     *
     * @param string $vendorId Vendor ID
     * @param string|null $documentType Filter by type
     * @return array<array{
     *     document_id: string,
     *     document_type: string,
     *     filename: string,
     *     uploaded_at: string,
     *     status: string,
     * }>
     */
    public function listDocuments(
        string $vendorId,
        ?string $documentType = null,
    ): array;

    /**
     * Generate API credentials for vendor.
     *
     * @param string $vendorId Vendor ID
     * @param array<string> $scopes Allowed API scopes
     * @param int $rateLimit Requests per minute
     * @param int $dailyQuota Daily request quota
     * @return VendorApiCredentials Generated credentials
     */
    public function generateApiCredentials(
        string $vendorId,
        array $scopes,
        int $rateLimit = 60,
        int $dailyQuota = 1000,
    ): VendorApiCredentials;

    /**
     * Revoke vendor's API credentials.
     *
     * @param string $vendorId Vendor ID
     * @param string $apiKeyId API key ID to revoke
     * @param string $revokedBy User revoking
     * @param string $reason Revocation reason
     */
    public function revokeApiCredentials(
        string $vendorId,
        string $apiKeyId,
        string $revokedBy,
        string $reason,
    ): void;

    /**
     * Get vendor's portal tier and capabilities.
     *
     * @param string $vendorId Vendor ID
     * @return array{
     *     tier: VendorPortalTier,
     *     capabilities: array<string>,
     *     limits: array<string, int>,
     * }
     */
    public function getTierCapabilities(string $vendorId): array;

    /**
     * Submit an invoice through the portal.
     *
     * @param string $vendorId Vendor ID
     * @param array<string, mixed> $invoiceData Invoice data
     * @return array{
     *     invoice_id: string,
     *     status: string,
     *     validation_results: array<string, mixed>,
     * }
     */
    public function submitInvoice(string $vendorId, array $invoiceData): array;

    /**
     * Submit a quote/bid through the portal.
     *
     * @param string $vendorId Vendor ID
     * @param string $rfqId RFQ/RFP ID
     * @param array<string, mixed> $quoteData Quote data
     * @return array{
     *     quote_id: string,
     *     status: string,
     *     submitted_at: string,
     * }
     */
    public function submitQuote(
        string $vendorId,
        string $rfqId,
        array $quoteData,
    ): array;
}
