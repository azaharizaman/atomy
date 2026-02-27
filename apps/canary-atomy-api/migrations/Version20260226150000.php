<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create installed_modules table.
 *
 * This migration creates the table to track which modules
 * have been installed for each tenant.
 */
final class Version20260226150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create installed_modules table for module tracking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE installed_modules (
            id UUID NOT NULL,
            module_id VARCHAR(255) NOT NULL,
            installed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            installed_by VARCHAR(255) NOT NULL,
            metadata JSON NOT NULL,
            tenant_id VARCHAR(26) DEFAULT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql('CREATE UNIQUE INDEX idx_installed_modules_module_id ON installed_modules (module_id)');
        $this->addSql('CREATE INDEX idx_installed_modules_tenant_id ON installed_modules (tenant_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE installed_modules');
    }
}