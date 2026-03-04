<?php

declare(strict_types=1);

namespace Nexus\Reporting\Contracts;

interface NotifiableInterface
{
    public function getId(): string;

    public function getNotificationEmail(): ?string;

    public function getNotificationPhone(): ?string;

    /** @return array<int, string> */
    public function getNotificationDeviceTokens(): array;

    public function getNotificationLocale(): ?string;

    public function getNotificationTimezone(): ?string;

    public function getNotificationIdentifier(): string;
}
