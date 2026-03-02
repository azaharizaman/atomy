<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Fix VARCHAR(26) -> VARCHAR(36) for id/tenant_id columns.
 *
 * Accommodates both ULID (26 chars) and UUID (36 chars) formats.
 */
final class Version20260226160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Increase id and tenant_id columns from VARCHAR(26) to VARCHAR(36)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tenants ALTER COLUMN id TYPE VARCHAR(36)');
        $this->addSql('ALTER TABLE tenants ALTER COLUMN parent_id TYPE VARCHAR(36)');

        $this->addSql('ALTER TABLE users ALTER COLUMN id TYPE VARCHAR(36)');
        $this->addSql('ALTER TABLE users ALTER COLUMN tenant_id TYPE VARCHAR(36)');

        $this->addSql('ALTER TABLE settings ALTER COLUMN id TYPE VARCHAR(36)');
        $this->addSql('ALTER TABLE settings ALTER COLUMN tenant_id TYPE VARCHAR(36)');

        $this->addSql('ALTER TABLE feature_flags ALTER COLUMN id TYPE VARCHAR(36)');
        $this->addSql('ALTER TABLE feature_flags ALTER COLUMN tenant_id TYPE VARCHAR(36)');

        $this->addSql('ALTER TABLE installed_modules ALTER COLUMN tenant_id TYPE VARCHAR(36)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tenants ALTER COLUMN id TYPE VARCHAR(26)');
        $this->addSql('ALTER TABLE tenants ALTER COLUMN parent_id TYPE VARCHAR(26)');

        $this->addSql('ALTER TABLE users ALTER COLUMN id TYPE VARCHAR(26)');
        $this->addSql('ALTER TABLE users ALTER COLUMN tenant_id TYPE VARCHAR(26)');

        $this->addSql('ALTER TABLE settings ALTER COLUMN id TYPE VARCHAR(26)');
        $this->addSql('ALTER TABLE settings ALTER COLUMN tenant_id TYPE VARCHAR(26)');

        $this->addSql('ALTER TABLE feature_flags ALTER COLUMN id TYPE VARCHAR(26)');
        $this->addSql('ALTER TABLE feature_flags ALTER COLUMN tenant_id TYPE VARCHAR(26)');

        $this->addSql('ALTER TABLE installed_modules ALTER COLUMN tenant_id TYPE VARCHAR(26)');
    }
}
