<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Identity Management Tables: API Tokens, Sessions, Roles, Permissions, MFA
 */
final class Version20251126100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create identity management tables: api_tokens, sessions, roles, permissions, mfa_enrollments, role_permissions';
    }

    public function up(Schema $schema): void
    {
        // API Tokens table
        $this->addSql('CREATE TABLE api_tokens (
            id VARCHAR(26) NOT NULL,
            user_id VARCHAR(26) NOT NULL,
            name VARCHAR(255) NOT NULL,
            token_hash VARCHAR(64) NOT NULL,
            scopes JSON DEFAULT NULL,
            expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            last_used_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            revoked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2CAD560E6D4F4DC ON api_tokens (token_hash)');
        $this->addSql('CREATE INDEX idx_api_tokens_user ON api_tokens (user_id)');
        $this->addSql('COMMENT ON COLUMN api_tokens.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN api_tokens.last_used_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN api_tokens.revoked_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN api_tokens.created_at IS \'(DC2Type:datetime_immutable)\'');

        // Sessions table
        $this->addSql('CREATE TABLE sessions (
            id VARCHAR(26) NOT NULL,
            token VARCHAR(128) NOT NULL,
            user_id VARCHAR(26) NOT NULL,
            metadata JSON DEFAULT NULL,
            device_fingerprint VARCHAR(64) DEFAULT NULL,
            expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            last_activity_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            revoked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9A609D135F37A13B ON sessions (token)');
        $this->addSql('CREATE INDEX idx_sessions_user ON sessions (user_id)');
        $this->addSql('CREATE INDEX idx_sessions_expires ON sessions (expires_at)');
        $this->addSql('COMMENT ON COLUMN sessions.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN sessions.last_activity_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN sessions.revoked_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN sessions.created_at IS \'(DC2Type:datetime_immutable)\'');

        // Permissions table
        $this->addSql('CREATE TABLE permissions (
            id VARCHAR(26) NOT NULL,
            name VARCHAR(100) NOT NULL,
            resource VARCHAR(100) NOT NULL,
            action VARCHAR(100) NOT NULL,
            description VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2DEDCC6F5E237E06 ON permissions (name)');
        $this->addSql('COMMENT ON COLUMN permissions.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN permissions.updated_at IS \'(DC2Type:datetime_immutable)\'');

        // Roles table
        $this->addSql('CREATE TABLE roles (
            id VARCHAR(26) NOT NULL,
            name VARCHAR(100) NOT NULL,
            description VARCHAR(255) DEFAULT NULL,
            tenant_id VARCHAR(26) DEFAULT NULL,
            system_role BOOLEAN NOT NULL DEFAULT FALSE,
            parent_role_id VARCHAR(26) DEFAULT NULL,
            requires_mfa BOOLEAN NOT NULL DEFAULT FALSE,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_role_name_tenant ON roles (name, tenant_id)');
        $this->addSql('COMMENT ON COLUMN roles.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN roles.updated_at IS \'(DC2Type:datetime_immutable)\'');

        // Role-Permissions pivot table
        $this->addSql('CREATE TABLE role_permissions (
            role_id VARCHAR(26) NOT NULL,
            permission_id VARCHAR(26) NOT NULL,
            PRIMARY KEY(role_id, permission_id)
        )');
        $this->addSql('CREATE INDEX IDX_1FBA94E6D60322AC ON role_permissions (role_id)');
        $this->addSql('CREATE INDEX IDX_1FBA94E6FED90CCA ON role_permissions (permission_id)');

        // MFA Enrollments table
        $this->addSql('CREATE TABLE mfa_enrollments (
            id VARCHAR(26) NOT NULL,
            user_id VARCHAR(26) NOT NULL,
            method VARCHAR(20) NOT NULL,
            secret VARCHAR(128) DEFAULT NULL,
            verified BOOLEAN NOT NULL DEFAULT FALSE,
            is_primary BOOLEAN NOT NULL DEFAULT FALSE,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_mfa_user ON mfa_enrollments (user_id)');
        $this->addSql('COMMENT ON COLUMN mfa_enrollments.created_at IS \'(DC2Type:datetime_immutable)\'');

        // Foreign keys
        $this->addSql('ALTER TABLE api_tokens ADD CONSTRAINT FK_2CAD560EA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE sessions ADD CONSTRAINT FK_9A609D13A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE role_permissions ADD CONSTRAINT FK_1FBA94E6D60322AC FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE role_permissions ADD CONSTRAINT FK_1FBA94E6FED90CCA FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE mfa_enrollments ADD CONSTRAINT FK_MFA_USER FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE api_tokens DROP CONSTRAINT IF EXISTS FK_2CAD560EA76ED395');
        $this->addSql('ALTER TABLE sessions DROP CONSTRAINT IF EXISTS FK_9A609D13A76ED395');
        $this->addSql('ALTER TABLE role_permissions DROP CONSTRAINT IF EXISTS FK_1FBA94E6D60322AC');
        $this->addSql('ALTER TABLE role_permissions DROP CONSTRAINT IF EXISTS FK_1FBA94E6FED90CCA');
        $this->addSql('ALTER TABLE mfa_enrollments DROP CONSTRAINT IF EXISTS FK_MFA_USER');
        
        $this->addSql('DROP TABLE IF EXISTS mfa_enrollments');
        $this->addSql('DROP TABLE IF EXISTS role_permissions');
        $this->addSql('DROP TABLE IF EXISTS roles');
        $this->addSql('DROP TABLE IF EXISTS permissions');
        $this->addSql('DROP TABLE IF EXISTS sessions');
        $this->addSql('DROP TABLE IF EXISTS api_tokens');
    }
}
