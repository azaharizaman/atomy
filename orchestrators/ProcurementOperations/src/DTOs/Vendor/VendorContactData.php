<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs\Vendor;

/**
 * Vendor contact data DTO.
 */
final readonly class VendorContactData
{
    /**
     * @param string $contactType Type of contact (primary, billing, technical, etc.)
     * @param string $contactName Full name
     * @param string $email Email address
     * @param string $phone Phone number
     * @param string|null $mobilePhone Mobile phone number
     * @param string|null $jobTitle Job title/position
     * @param string|null $department Department
     * @param bool $isPrimary Whether this is the primary contact
     * @param bool $receiveNotifications Whether contact receives notifications
     * @param array<string> $notificationTypes Types of notifications to receive
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $contactType,
        public string $contactName,
        public string $email,
        public string $phone,
        public ?string $mobilePhone = null,
        public ?string $jobTitle = null,
        public ?string $department = null,
        public bool $isPrimary = false,
        public bool $receiveNotifications = true,
        public array $notificationTypes = ['all'],
        public array $metadata = [],
    ) {}

    /**
     * Create primary contact.
     */
    public static function primary(
        string $contactName,
        string $email,
        string $phone,
        ?string $jobTitle = null,
    ): self {
        return new self(
            contactType: 'primary',
            contactName: $contactName,
            email: $email,
            phone: $phone,
            mobilePhone: null,
            jobTitle: $jobTitle,
            department: null,
            isPrimary: true,
            receiveNotifications: true,
            notificationTypes: ['all'],
        );
    }

    /**
     * Create billing contact.
     */
    public static function billing(
        string $contactName,
        string $email,
        string $phone,
    ): self {
        return new self(
            contactType: 'billing',
            contactName: $contactName,
            email: $email,
            phone: $phone,
            mobilePhone: null,
            jobTitle: null,
            department: 'Finance',
            isPrimary: false,
            receiveNotifications: true,
            notificationTypes: ['invoice', 'payment', 'statement'],
        );
    }

    /**
     * Create technical contact.
     */
    public static function technical(
        string $contactName,
        string $email,
        string $phone,
    ): self {
        return new self(
            contactType: 'technical',
            contactName: $contactName,
            email: $email,
            phone: $phone,
            mobilePhone: null,
            jobTitle: null,
            department: 'IT',
            isPrimary: false,
            receiveNotifications: true,
            notificationTypes: ['api', 'integration', 'security'],
        );
    }

    /**
     * Create sales contact.
     */
    public static function sales(
        string $contactName,
        string $email,
        string $phone,
    ): self {
        return new self(
            contactType: 'sales',
            contactName: $contactName,
            email: $email,
            phone: $phone,
            mobilePhone: null,
            jobTitle: null,
            department: 'Sales',
            isPrimary: false,
            receiveNotifications: true,
            notificationTypes: ['order', 'quote', 'contract'],
        );
    }

    public function shouldReceiveNotification(string $notificationType): bool
    {
        if (! $this->receiveNotifications) {
            return false;
        }

        if (in_array('all', $this->notificationTypes, true)) {
            return true;
        }

        return in_array($notificationType, $this->notificationTypes, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'contact_type' => $this->contactType,
            'contact_name' => $this->contactName,
            'email' => $this->email,
            'phone' => $this->phone,
            'mobile_phone' => $this->mobilePhone,
            'job_title' => $this->jobTitle,
            'department' => $this->department,
            'is_primary' => $this->isPrimary,
            'receive_notifications' => $this->receiveNotifications,
            'notification_types' => $this->notificationTypes,
            'metadata' => $this->metadata,
        ];
    }
}
