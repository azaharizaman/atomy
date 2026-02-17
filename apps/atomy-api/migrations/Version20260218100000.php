<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration for Nexus Document package tables.
 */
final class Version20260218100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initialize Document package tables: documents, versions, relationships, holds, certifications.';
    }

    public function up(Schema $schema): void
    {
        // 1. Documents Table
        $this->addSql('CREATE TABLE documents (
            id VARCHAR(26) NOT NULL,
            tenant_id VARCHAR(26) NOT NULL,
            owner_id VARCHAR(26) NOT NULL,
            type VARCHAR(50) NOT NULL,
            state VARCHAR(50) NOT NULL,
            storage_path VARCHAR(255) NOT NULL,
            checksum VARCHAR(64) NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            file_size BIGINT NOT NULL,
            original_filename VARCHAR(255) NOT NULL,
            version INT NOT NULL DEFAULT 1,
            metadata JSON NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            deleted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        $this->addSql('CREATE INDEX IDX_DOCUMENTS_TENANT_TYPE ON documents (tenant_id, type)');
        $this->addSql('CREATE INDEX IDX_DOCUMENTS_TENANT_STATE ON documents (tenant_id, state)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DOCUMENTS_STORAGE_PATH ON documents (storage_path)');

        // 2. Document Versions Table
        $this->addSql('CREATE TABLE document_versions (
            id VARCHAR(26) NOT NULL,
            document_id VARCHAR(26) NOT NULL,
            version_number INT NOT NULL,
            storage_path VARCHAR(255) NOT NULL,
            change_description TEXT DEFAULT NULL,
            created_by VARCHAR(26) NOT NULL,
            checksum VARCHAR(64) NOT NULL,
            file_size BIGINT NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        $this->addSql('CREATE INDEX IDX_DOC_VERSIONS_DOCUMENT ON document_versions (document_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DOC_VERSIONS_PATH ON document_versions (storage_path)');
        $this->addSql('ALTER TABLE document_versions ADD CONSTRAINT FK_DOC_VERSIONS_DOCUMENT FOREIGN KEY (document_id) REFERENCES documents (id) ON DELETE CASCADE');

        // 3. Document Relationships Table
        $this->addSql('CREATE TABLE document_relationships (
            id VARCHAR(26) NOT NULL,
            source_document_id VARCHAR(26) NOT NULL,
            target_document_id VARCHAR(26) NOT NULL,
            relationship_type VARCHAR(50) NOT NULL,
            created_by VARCHAR(26) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE INDEX IDX_DOC_REL_SOURCE ON document_relationships (source_document_id)');
        $this->addSql('CREATE INDEX IDX_DOC_REL_TARGET ON document_relationships (target_document_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DOC_REL_TYPE ON document_relationships (source_document_id, target_document_id, relationship_type)');

        // 4. Legal Holds Table
        $this->addSql('CREATE TABLE legal_holds (
            id VARCHAR(26) NOT NULL,
            tenant_id VARCHAR(26) NOT NULL,
            document_id VARCHAR(26) NOT NULL,
            reason TEXT NOT NULL,
            matter_reference VARCHAR(255) DEFAULT NULL,
            applied_by VARCHAR(26) NOT NULL,
            applied_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            released_by VARCHAR(26) DEFAULT NULL,
            released_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            release_reason TEXT DEFAULT NULL,
            expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            metadata JSON NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE INDEX IDX_LEGAL_HOLDS_DOCUMENT ON legal_holds (document_id)');
        $this->addSql('CREATE INDEX IDX_LEGAL_HOLDS_TENANT ON legal_holds (tenant_id)');

        // 5. Disposal Certifications Table
        $this->addSql('CREATE TABLE disposal_certifications (
            id VARCHAR(26) NOT NULL,
            tenant_id VARCHAR(26) NOT NULL,
            document_id VARCHAR(26) NOT NULL,
            document_type VARCHAR(50) NOT NULL,
            document_name VARCHAR(255) NOT NULL,
            disposal_method VARCHAR(50) NOT NULL,
            disposed_by VARCHAR(26) NOT NULL,
            disposed_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            approved_by VARCHAR(26) DEFAULT NULL,
            approved_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            disposal_reason TEXT NOT NULL,
            document_created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            retention_period_days INT NOT NULL,
            retention_expired_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            legal_hold_verified BOOLEAN NOT NULL DEFAULT 0,
            document_checksum VARCHAR(64) NOT NULL,
            regulatory_basis VARCHAR(100) DEFAULT NULL,
            witnessed_by VARCHAR(26) DEFAULT NULL,
            metadata JSON NOT NULL,
            chain_of_custody JSON NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE INDEX IDX_DISPOSAL_CERT_TENANT ON disposal_certifications (tenant_id)');
        $this->addSql('CREATE INDEX IDX_DISPOSAL_CERT_DOCUMENT ON disposal_certifications (document_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE disposal_certifications');
        $this->addSql('DROP TABLE legal_holds');
        $this->addSql('DROP TABLE document_relationships');
        $this->addSql('DROP TABLE document_versions');
        $this->addSql('DROP TABLE documents');
    }
}
