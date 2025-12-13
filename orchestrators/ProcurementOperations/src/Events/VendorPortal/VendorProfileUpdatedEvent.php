<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events\VendorPortal;

/**
 * Event: Vendor profile updated.
 *
 * Triggered when a vendor updates their profile information.
 */
final readonly class VendorProfileUpdatedEvent
{
    /**
     * @param string $eventId Unique event identifier
     * @param string $tenantId Tenant ID
     * @param string $vendorId Vendor ID
     * @param string $updatedBy User ID who made the update
     * @param array<string, array{old: mixed, new: mixed}> $changedFields Changed fields with old/new values
     * @param string $updateSource Source of update (portal, api, admin)
     * @param bool $requiresReview Whether changes require review
     * @param \DateTimeImmutable $occurredAt When the event occurred
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $eventId,
        public string $tenantId,
        public string $vendorId,
        public string $updatedBy,
        public array $changedFields,
        public string $updateSource = 'portal',
        public bool $requiresReview = false,
        public \DateTimeImmutable $occurredAt = new \DateTimeImmutable(),
        public array $metadata = [],
    ) {}

    /**
     * @param array<string, array{old: mixed, new: mixed}> $changedFields
     */
    public static function create(
        string $tenantId,
        string $vendorId,
        string $updatedBy,
        array $changedFields,
        string $updateSource = 'portal',
    ): self {
        // Determine if review is required based on changed fields
        $sensitiveFields = [
            'tax_id',
            'registration_number',
            'bank_account',
            'primary_contact',
            'legal_name',
        ];

        $requiresReview = ! empty(array_intersect(
            array_keys($changedFields),
            $sensitiveFields,
        ));

        return new self(
            eventId: self::generateEventId(),
            tenantId: $tenantId,
            vendorId: $vendorId,
            updatedBy: $updatedBy,
            changedFields: $changedFields,
            updateSource: $updateSource,
            requiresReview: $requiresReview,
            occurredAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Create event for banking details update.
     *
     * @param array<string, mixed> $oldDetails
     * @param array<string, mixed> $newDetails
     */
    public static function bankingDetailsChanged(
        string $tenantId,
        string $vendorId,
        string $updatedBy,
        array $oldDetails,
        array $newDetails,
    ): self {
        return new self(
            eventId: self::generateEventId(),
            tenantId: $tenantId,
            vendorId: $vendorId,
            updatedBy: $updatedBy,
            changedFields: [
                'banking_details' => ['old' => $oldDetails, 'new' => $newDetails],
            ],
            updateSource: 'portal',
            requiresReview: true,
            occurredAt: new \DateTimeImmutable(),
            metadata: ['change_type' => 'banking_details'],
        );
    }

    /**
     * Create event for contact update.
     */
    public static function contactChanged(
        string $tenantId,
        string $vendorId,
        string $updatedBy,
        string $contactType,
        array $oldContact,
        array $newContact,
    ): self {
        return new self(
            eventId: self::generateEventId(),
            tenantId: $tenantId,
            vendorId: $vendorId,
            updatedBy: $updatedBy,
            changedFields: [
                $contactType . '_contact' => ['old' => $oldContact, 'new' => $newContact],
            ],
            updateSource: 'portal',
            requiresReview: $contactType === 'primary',
            occurredAt: new \DateTimeImmutable(),
            metadata: ['change_type' => 'contact', 'contact_type' => $contactType],
        );
    }

    private static function generateEventId(): string
    {
        return sprintf('evt_vnd_upd_%s_%s', date('YmdHis'), bin2hex(random_bytes(8)));
    }

    public function getEventName(): string
    {
        return 'vendor_portal.profile_updated';
    }

    /**
     * @return array<string>
     */
    public function getChangedFieldNames(): array
    {
        return array_keys($this->changedFields);
    }

    public function hasFieldChanged(string $field): bool
    {
        return array_key_exists($field, $this->changedFields);
    }

    public function getOldValue(string $field): mixed
    {
        return $this->changedFields[$field]['old'] ?? null;
    }

    public function getNewValue(string $field): mixed
    {
        return $this->changedFields[$field]['new'] ?? null;
    }

    public function isBankingChange(): bool
    {
        return $this->hasFieldChanged('banking_details') ||
               $this->hasFieldChanged('bank_account');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => $this->getEventName(),
            'tenant_id' => $this->tenantId,
            'vendor_id' => $this->vendorId,
            'updated_by' => $this->updatedBy,
            'changed_fields' => $this->changedFields,
            'changed_field_names' => $this->getChangedFieldNames(),
            'update_source' => $this->updateSource,
            'requires_review' => $this->requiresReview,
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
            'metadata' => $this->metadata,
        ];
    }
}
