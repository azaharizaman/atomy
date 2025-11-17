<?php

declare(strict_types=1);

namespace Nexus\Storage\ValueObjects;

/**
 * Visibility enum defines the access level for stored files.
 *
 * @package Nexus\Storage\ValueObjects
 */
enum Visibility: string
{
    /**
     * Public files are accessible without authentication.
     * Public URLs can be generated for these files.
     */
    case Public = 'public';

    /**
     * Private files require authentication or signed URLs for access.
     * These files are not directly accessible via public URLs.
     */
    case Private = 'private';

    /**
     * Check if the visibility is public.
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this === self::Public;
    }

    /**
     * Check if the visibility is private.
     *
     * @return bool
     */
    public function isPrivate(): bool
    {
        return $this === self::Private;
    }
}
