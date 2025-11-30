<?php

declare(strict_types=1);

namespace Nexus\Tenant\Enums;

/**
 * Identification Strategy Enum
 *
 * Native PHP enum representing how tenants are identified from requests.
 * Supports domain-based, subdomain-based, header-based, path-based, and token-based identification.
 *
 * @package Nexus\Tenant\Enums
 * @see https://www.php.net/manual/en/language.enumerations.backed.php
 */
enum IdentificationStrategy: string
{
    case Domain = 'domain';
    case Subdomain = 'subdomain';
    case Header = 'header';
    case Path = 'path';
    case Token = 'token';

    /**
     * Check if strategy is domain-based
     *
     * @return bool
     */
    public function isDomain(): bool
    {
        return $this === self::Domain;
    }

    /**
     * Check if strategy is subdomain-based
     *
     * @return bool
     */
    public function isSubdomain(): bool
    {
        return $this === self::Subdomain;
    }

    /**
     * Check if strategy is header-based
     *
     * @return bool
     */
    public function isHeader(): bool
    {
        return $this === self::Header;
    }

    /**
     * Check if strategy is path-based
     *
     * @return bool
     */
    public function isPath(): bool
    {
        return $this === self::Path;
    }

    /**
     * Check if strategy is token-based
     *
     * @return bool
     */
    public function isToken(): bool
    {
        return $this === self::Token;
    }

    /**
     * Check if this strategy equals another strategy
     *
     * @param IdentificationStrategy $other Strategy to compare with
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this === $other;
    }
}
