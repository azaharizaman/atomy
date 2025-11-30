<?php

declare(strict_types=1);

namespace Nexus\Content\Exceptions;

/**
 * Thrown when article is not found in repository
 */
class ArticleNotFoundException extends ContentException
{
    public static function forId(string $articleId): self
    {
        return new self("Article not found: {$articleId}");
    }

    public static function forSlug(string $slug): self
    {
        return new self("Article not found with slug: {$slug}");
    }
}
