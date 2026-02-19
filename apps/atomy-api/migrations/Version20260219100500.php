<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sales Return Lines Table - Line items for sales returns
 */
final class Version20260219100500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create sales_return_lines table for sales return line items';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE sales_return_lines (
            id VARCHAR(26) NOT NULL,
            sales_return_id VARCHAR(26) NOT NULL,
            sales_order_line_id VARCHAR(26) NOT NULL,
            line_number INT NOT NULL,
            product_variant_id VARCHAR(26) DEFAULT NULL,
            product_name VARCHAR(255) NOT NULL,
            sku VARCHAR(100) DEFAULT NULL,
            quantity_returned DECIMAL(18,4) NOT NULL DEFAULT 0,
            quantity_accepted DECIMAL(18,4) NOT NULL DEFAULT 0,
            uom_code VARCHAR(10) DEFAULT NULL,
            unit_price DECIMAL(18,4) NOT NULL DEFAULT 0,
            line_amount DECIMAL(18,4) NOT NULL DEFAULT 0,
            return_reason VARCHAR(255) DEFAULT NULL,
            condition_received VARCHAR(50) DEFAULT NULL,
            serial_number VARCHAR(100) DEFAULT NULL,
            lot_number VARCHAR(100) DEFAULT NULL,
            metadata JSON DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Foreign key to sales_returns
        $this->addSql('ALTER TABLE sales_return_lines ADD CONSTRAINT FK_SALES_RETURN_LINES_RETURN FOREIGN KEY (sales_return_id) REFERENCES sales_returns (id) ON DELETE CASCADE');

        // Foreign key to sales_order_lines
        $this->addSql('ALTER TABLE sales_return_lines ADD CONSTRAINT FK_SALES_RETURN_LINES_ORDER_LINE FOREIGN KEY (sales_order_line_id) REFERENCES sales_order_lines (id) ON DELETE RESTRICT');

        // Indexes
        $this->addSql('CREATE INDEX IDX_SALES_RETURN_LINES_RETURN ON sales_return_lines (sales_return_id)');
        $this->addSql('CREATE INDEX IDX_SALES_RETURN_LINES_ORDER_LINE ON sales_return_lines (sales_order_line_id)');
        $this->addSql('CREATE INDEX IDX_SALES_RETURN_LINES_PRODUCT ON sales_return_lines (product_variant_id)');

        // Composite index for unique line ordering within a return
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SALES_RETURN_LINES_LINE_NUMBER ON sales_return_lines (sales_return_id, line_number)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sales_return_lines DROP CONSTRAINT IF EXISTS FK_SALES_RETURN_LINES_ORDER_LINE');
        $this->addSql('ALTER TABLE sales_return_lines DROP CONSTRAINT IF EXISTS FK_SALES_RETURN_LINES_RETURN');
        $this->addSql('DROP TABLE IF EXISTS sales_return_lines');
    }
}
