<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sales Returns Table - Sales return/credit request entity
 */
final class Version20260219100400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create sales_returns table for sales returns and credit requests';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE sales_returns (
            id VARCHAR(26) NOT NULL,
            tenant_id VARCHAR(26) NOT NULL,
            return_number VARCHAR(50) NOT NULL,
            sales_order_id VARCHAR(26) NOT NULL,
            status VARCHAR(30) NOT NULL,
            return_reason VARCHAR(255) DEFAULT NULL,
            return_type VARCHAR(20) NOT NULL,
            resolution VARCHAR(50) DEFAULT NULL,
            credit_note_id VARCHAR(26) DEFAULT NULL,
            refund_amount DECIMAL(18,4) NOT NULL DEFAULT 0,
            approved_by VARCHAR(26) DEFAULT NULL,
            approved_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            customer_notes TEXT DEFAULT NULL,
            internal_notes TEXT DEFAULT NULL,
            requested_by VARCHAR(26) DEFAULT NULL,
            processed_by VARCHAR(26) DEFAULT NULL,
            processed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Unique constraint on tenant + return_number
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SALES_RETURNS_TENANT_NUMBER ON sales_returns (tenant_id, return_number)');
        
        // Indexes for common queries
        $this->addSql('CREATE INDEX IDX_SALES_RETURNS_TENANT ON sales_returns (tenant_id)');
        $this->addSql('CREATE INDEX IDX_SALES_RETURNS_ORDER ON sales_returns (sales_order_id)');
        $this->addSql('CREATE INDEX IDX_SALES_RETURNS_STATUS ON sales_returns (status)');
        $this->addSql('CREATE INDEX IDX_SALES_RETURNS_CREDIT_NOTE ON sales_returns (credit_note_id)');
        $this->addSql('CREATE INDEX IDX_SALES_RETURNS_CREATED_AT ON sales_returns (created_at)');

        // Foreign key constraint for sales_order_id
        $this->addSql('ALTER TABLE sales_returns ADD CONSTRAINT FK_SALES_RETURNS_SALES_ORDER FOREIGN KEY (sales_order_id) REFERENCES sales_orders (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS sales_returns');
    }
}
