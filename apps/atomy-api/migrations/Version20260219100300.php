<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Quotation Lines Table - Line items for quotations
 */
final class Version20260219100300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create quotation_lines table for quotation line items';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE quotation_lines (
            id VARCHAR(26) NOT NULL,
            quotation_id VARCHAR(26) NOT NULL,
            line_number INT NOT NULL,
            product_variant_id VARCHAR(26) DEFAULT NULL,
            product_name VARCHAR(255) NOT NULL,
            sku VARCHAR(100) DEFAULT NULL,
            quantity DECIMAL(18,4) NOT NULL,
            uom_code VARCHAR(10) DEFAULT NULL,
            unit_price DECIMAL(18,4) NOT NULL DEFAULT 0,
            discount_percentage DECIMAL(5,2) DEFAULT NULL,
            discount_amount DECIMAL(18,4) NOT NULL DEFAULT 0,
            tax_code VARCHAR(50) DEFAULT NULL,
            tax_rate DECIMAL(10,4) DEFAULT NULL,
            line_total DECIMAL(18,4) NOT NULL DEFAULT 0,
            quantity_shipped DECIMAL(18,4) NOT NULL DEFAULT 0,
            quantity_invoiced DECIMAL(18,4) NOT NULL DEFAULT 0,
            serial_number VARCHAR(100) DEFAULT NULL,
            lot_number VARCHAR(100) DEFAULT NULL,
            metadata JSON DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Foreign key to quotations
        $this->addSql('ALTER TABLE quotation_lines ADD CONSTRAINT FK_QUOTATION_LINES_QUOTATION FOREIGN KEY (quotation_id) REFERENCES quotations (id) ON DELETE CASCADE');

        // Indexes
        $this->addSql('CREATE INDEX IDX_QUOTATION_LINES_QUOTATION ON quotation_lines (quotation_id)');
        $this->addSql('CREATE INDEX IDX_QUOTATION_LINES_PRODUCT ON quotation_lines (product_variant_id)');
        $this->addSql('CREATE INDEX IDX_QUOTATION_LINES_LINE_NUMBER ON quotation_lines (quotation_id, line_number)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quotation_lines DROP CONSTRAINT IF EXISTS FK_QUOTATION_LINES_QUOTATION');
        $this->addSql('DROP TABLE IF EXISTS quotation_lines');
    }
}
