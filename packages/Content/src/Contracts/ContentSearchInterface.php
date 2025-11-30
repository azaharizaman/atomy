<?php

declare(strict_types=1);

namespace Nexus\Content\Contracts;

use Nexus\Content\ValueObjects\Article;
use Nexus\Content\ValueObjects\SearchCriteria;

/**
 * Search interface for content indexing and retrieval
 * 
 * Consuming applications must implement this to provide search functionality.
 * Can be backed by Elasticsearch, Algolia, Meilisearch, etc.
 */
interface ContentSearchInterface
{
    /**
     * Index article for search (L1.6)
     * 
     * @param Article $article
     * @return void
     */
    public function indexArticle(Article $article): void;

    /**
     * Remove article from search index
     * 
     * @param string $articleId
     * @return void
     */
    public function removeArticle(string $articleId): void;

    /**
     * Search articles with faceted criteria (L3.6)
     * 
     * @param SearchCriteria $criteria
     * @return array{articles: array<Article>, total: int}
     */
    public function search(SearchCriteria $criteria): array;

    /**
     * Reindex all articles (batch operation)
     * 
     * @return int Number of articles indexed
     */
    public function reindexAll(): int;
}
