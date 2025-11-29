<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Feature Flags tables for application and user-level feature management.
 */
final class Version20251127100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create feature_flags and user_flag_overrides tables for feature flag management';
    }

    public function up(Schema $schema): void
    {
        // Feature Flags table - application level flags
        $this->addSql('CREATE TABLE feature_flags (
            id VARCHAR(26) NOT NULL,
            tenant_id VARCHAR(26) NOT NULL,
            name VARCHAR(100) NOT NULL,
            description VARCHAR(255) DEFAULT NULL,
            enabled BOOLEAN NOT NULL DEFAULT FALSE,
            strategy VARCHAR(32) NOT NULL DEFAULT \'system_wide\',
            value JSON DEFAULT NULL,
            override VARCHAR(16) DEFAULT NULL,
            metadata JSON NOT NULL DEFAULT \'[]\',
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            created_by VARCHAR(26) DEFAULT NULL,
            updated_by VARCHAR(26) DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        
        // Unique constraint for tenant + flag name
        $this->addSql('CREATE UNIQUE INDEX unique_tenant_flag ON feature_flags (tenant_id, name)');
        
        // Indexes for common queries
        $this->addSql('CREATE INDEX idx_feature_flags_tenant ON feature_flags (tenant_id)');
        $this->addSql('CREATE INDEX idx_feature_flags_enabled ON feature_flags (enabled)');
        $this->addSql('CREATE INDEX idx_feature_flags_strategy ON feature_flags (strategy)');
        
        $this->addSql('COMMENT ON COLUMN feature_flags.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN feature_flags.updated_at IS \'(DC2Type:datetime_immutable)\'');
        
        // User Flag Overrides table - user-level flag settings
        $this->addSql('CREATE TABLE user_flag_overrides (
            id VARCHAR(26) NOT NULL,
            tenant_id VARCHAR(26) NOT NULL,
            user_id VARCHAR(26) NOT NULL,
            flag_name VARCHAR(100) NOT NULL,
            enabled BOOLEAN NOT NULL DEFAULT FALSE,
            value JSON DEFAULT NULL,
            reason VARCHAR(255) DEFAULT NULL,
            expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            created_by VARCHAR(26) DEFAULT NULL,
            updated_by VARCHAR(26) DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        
        // Unique constraint for tenant + user + flag name
        $this->addSql('CREATE UNIQUE INDEX unique_user_flag_override ON user_flag_overrides (tenant_id, user_id, flag_name)');
        
        // Indexes for common queries
        $this->addSql('CREATE INDEX idx_user_flag_overrides_tenant ON user_flag_overrides (tenant_id)');
        $this->addSql('CREATE INDEX idx_user_flag_overrides_user ON user_flag_overrides (user_id)');
        $this->addSql('CREATE INDEX idx_user_flag_overrides_flag ON user_flag_overrides (flag_name)');
        $this->addSql('CREATE INDEX idx_user_flag_overrides_enabled ON user_flag_overrides (enabled)');
        $this->addSql('CREATE INDEX idx_user_flag_overrides_expires ON user_flag_overrides (expires_at)');
        
        $this->addSql('COMMENT ON COLUMN user_flag_overrides.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN user_flag_overrides.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN user_flag_overrides.expires_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS user_flag_overrides');
        $this->addSql('DROP TABLE IF EXISTS feature_flags');
    }
}
