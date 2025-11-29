<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Audit Logs table for security event tracking.
 */
final class Version20251126100001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create audit_logs table for security event tracking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE audit_logs (
            id VARCHAR(26) NOT NULL,
            log_name VARCHAR(100) NOT NULL,
            description TEXT NOT NULL,
            subject_type VARCHAR(100) DEFAULT NULL,
            subject_id VARCHAR(100) DEFAULT NULL,
            causer_type VARCHAR(100) DEFAULT NULL,
            causer_id VARCHAR(100) DEFAULT NULL,
            properties JSON DEFAULT NULL,
            event VARCHAR(50) DEFAULT NULL,
            level SMALLINT NOT NULL DEFAULT 2,
            batch_uuid VARCHAR(36) DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent VARCHAR(500) DEFAULT NULL,
            tenant_id VARCHAR(100) DEFAULT NULL,
            retention_days INTEGER NOT NULL DEFAULT 90,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        
        // Indexes for common query patterns
        $this->addSql('CREATE INDEX idx_audit_log_name ON audit_logs (log_name)');
        $this->addSql('CREATE INDEX idx_audit_subject ON audit_logs (subject_type, subject_id)');
        $this->addSql('CREATE INDEX idx_audit_causer ON audit_logs (causer_type, causer_id)');
        $this->addSql('CREATE INDEX idx_audit_tenant ON audit_logs (tenant_id)');
        $this->addSql('CREATE INDEX idx_audit_created ON audit_logs (created_at)');
        $this->addSql('CREATE INDEX idx_audit_expires ON audit_logs (expires_at)');
        $this->addSql('CREATE INDEX idx_audit_batch ON audit_logs (batch_uuid)');
        $this->addSql('CREATE INDEX idx_audit_level ON audit_logs (level)');
        
        $this->addSql('COMMENT ON COLUMN audit_logs.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN audit_logs.expires_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS audit_logs');
    }
}
