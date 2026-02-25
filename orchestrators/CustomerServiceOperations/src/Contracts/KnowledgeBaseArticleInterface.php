<?php

declare(strict_types=1);

namespace Nexus\CustomerServiceOperations\Contracts;

interface KnowledgeBaseArticleInterface
{
    public function getId(): string;
    public function getTitle(): string;
    public function getContent(): string;

    /**
     * @return string[]
     */
    public function getTags(): array;
}
