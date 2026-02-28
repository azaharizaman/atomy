<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create base tables: tenants, users, settings, feature_flags.
 *
 * These tables are required before running fixtures.
 */
final class Version20260226140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tenants, users, settings, feature_flags tables';
    }

    public function up(Schema $schema): void
    {
        // tenants
        $this->addSql('CREATE TABLE tenants (
            id VARCHAR(36) NOT NULL,
            code VARCHAR(50) NOT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'pending\',
            domain VARCHAR(255) DEFAULT NULL,
            subdomain VARCHAR(255) DEFAULT NULL,
            database_name VARCHAR(255) DEFAULT NULL,
            timezone VARCHAR(50) NOT NULL DEFAULT \'UTC\',
            locale VARCHAR(10) NOT NULL DEFAULT \'en_US\',
            currency VARCHAR(3) NOT NULL DEFAULT \'USD\',
            date_format VARCHAR(20) NOT NULL DEFAULT \'Y-m-d\',
            time_format VARCHAR(20) NOT NULL DEFAULT \'H:i:s\',
            parent_id VARCHAR(36) DEFAULT NULL,
            metadata JSON NOT NULL DEFAULT \'[]\',
            trial_ends_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            storage_quota INT DEFAULT NULL,
            storage_used INT NOT NULL DEFAULT 0,
            max_users INT DEFAULT NULL,
            rate_limit INT DEFAULT NULL,
            read_only BOOLEAN NOT NULL DEFAULT false,
            billing_cycle_start_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            onboarding_progress INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2A18272F77153098 ON tenants (code)');

        // users
        $this->addSql('CREATE TABLE users (
            id VARCHAR(36) NOT NULL,
            email VARCHAR(180) NOT NULL,
            roles JSON NOT NULL,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(255) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'pending_activation\',
            tenant_id VARCHAR(36) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            email_verified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            password_changed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            mfa_enabled BOOLEAN NOT NULL DEFAULT false,
            metadata JSON DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE INDEX IDX_1483A5E99033212A ON users (tenant_id)');

        // settings (composite PK: id, key)
        $this->addSql('CREATE TABLE settings (
            id VARCHAR(36) NOT NULL,
            key VARCHAR(100) NOT NULL,
            value JSON DEFAULT NULL,
            type VARCHAR(20) NOT NULL DEFAULT \'string\',
            scope VARCHAR(20) NOT NULL DEFAULT \'tenant\',
            is_encrypted BOOLEAN NOT NULL DEFAULT false,
            is_read_only BOOLEAN NOT NULL DEFAULT false,
            tenant_id VARCHAR(36) DEFAULT NULL,
            PRIMARY KEY(id, key)
        )');
        $this->addSql('CREATE INDEX IDX_E545A0C59033212A ON settings (tenant_id)');

        // feature_flags (composite PK: id, name)
        $this->addSql('CREATE TABLE feature_flags (
            id VARCHAR(36) NOT NULL,
            name VARCHAR(100) NOT NULL,
            enabled BOOLEAN NOT NULL DEFAULT false,
            strategy VARCHAR(20) NOT NULL DEFAULT \'system_wide\',
            value JSON DEFAULT NULL,
            override VARCHAR(20) DEFAULT NULL,
            metadata JSON NOT NULL DEFAULT \'[]\',
            checksum VARCHAR(64) NOT NULL DEFAULT \'\',
            tenant_id VARCHAR(36) DEFAULT NULL,
            PRIMARY KEY(id, name)
        )');
        $this->addSql('CREATE INDEX IDX_FF_TENANT ON feature_flags (tenant_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE feature_flags');
        $this->addSql('DROP TABLE settings');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE tenants');
    }
}
