<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Quotations Table - Sales quotation/estimate entity
 */
final class Version20260219100200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create quotations table for sales quotations and estimates';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE quotations (
            id VARCHAR(26) NOT NULL,
            tenant_id VARCHAR(26) NOT NULL,
            quote_number VARCHAR(50) NOT NULL,
            customer_id VARCHAR(26) NOT NULL,
            status VARCHAR(30) NOT NULL,
            workflow_instance_id VARCHAR(26) DEFAULT NULL,
            quote_date DATE NOT NULL,
            valid_until DATE NOT NULL,
            currency_code VARCHAR(3) NOT NULL,
            exchange_rate DECIMAL(18,6) NOT NULL DEFAULT 1,
            exchange_rate_locked_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            subtotal DECIMAL(18,4) NOT NULL DEFAULT 0,
            tax_amount DECIMAL(18,4) NOT NULL DEFAULT 0,
            discount_amount DECIMAL(18,4) NOT NULL DEFAULT 0,
            total DECIMAL(18,4) NOT NULL DEFAULT 0,
            payment_term VARCHAR(50) DEFAULT NULL,
            customer_po_number VARCHAR(100) DEFAULT NULL,
            shipping_address_id VARCHAR(26) DEFAULT NULL,
            billing_address_id VARCHAR(26) DEFAULT NULL,
            warehouse_id VARCHAR(26) DEFAULT NULL,
            salesperson_id VARCHAR(26) DEFAULT NULL,
            commission_percentage DECIMAL(5,2) DEFAULT NULL,
            customer_notes TEXT DEFAULT NULL,
            internal_notes TEXT DEFAULT NULL,
            converted_to_order_id VARCHAR(26) DEFAULT NULL,
            prepared_by VARCHAR(26) DEFAULT NULL,
            sent_by VARCHAR(26) DEFAULT NULL,
            sent_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            accepted_by VARCHAR(26) DEFAULT NULL,
            accepted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            rejected_by VARCHAR(26) DEFAULT NULL,
            rejected_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            rejection_reason TEXT DEFAULT NULL,
            metadata JSON DEFAULT NULL,
            created_by VARCHAR(26) DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            deleted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Unique constraint on tenant + quote_number
        $this->addSql('CREATE UNIQUE INDEX UNIQ_QUOTATIONS_TENANT_NUMBER ON quotations (tenant_id, quote_number)');
        
        // Indexes for common queries
        $this->addSql('CREATE INDEX IDX_QUOTATIONS_TENANT ON quotations (tenant_id)');
        $this->addSql('CREATE INDEX IDX_QUOTATIONS_CUSTOMER ON quotations (customer_id)');
        $this->addSql('CREATE INDEX IDX_QUOTATIONS_STATUS ON quotations (status)');
        $this->addSql('CREATE INDEX IDX_QUOTATIONS_CONVERTED ON quotations (converted_to_order_id)');
        $this->addSql('CREATE INDEX IDX_QUOTATIONS_SALESPERSON ON quotations (salesperson_id)');
        $this->addSql('CREATE INDEX IDX_QUOTATIONS_QUOTE_DATE ON quotations (quote_date)');
        $this->addSql('CREATE INDEX IDX_QUOTATIONS_VALID_UNTIL ON quotations (valid_until)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS quotations');
    }
}
