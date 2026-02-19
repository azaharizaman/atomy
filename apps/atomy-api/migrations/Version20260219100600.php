<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sales Stock Reservations Table - Inventory stock reservations for sales orders
 */
final class Version20260219100600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create sales_stock_reservations table for inventory stock reservation tracking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE sales_stock_reservations (
            id VARCHAR(26) NOT NULL,
            tenant_id VARCHAR(26) NOT NULL,
            sales_order_id VARCHAR(26) NOT NULL,
            sales_order_line_id VARCHAR(26) DEFAULT NULL,
            product_variant_id VARCHAR(26) NOT NULL,
            warehouse_id VARCHAR(26) NOT NULL,
            reserved_quantity DECIMAL(18,4) NOT NULL,
            allocated_quantity DECIMAL(18,4) NOT NULL DEFAULT 0,
            fulfilled_quantity DECIMAL(18,4) NOT NULL DEFAULT 0,
            released_quantity DECIMAL(18,4) NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL,
            reservation_date DATE NOT NULL,
            fulfillment_deadline DATE DEFAULT NULL,
            allocated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            fulfilled_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            released_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            metadata JSON DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Indexes for common queries
        $this->addSql('CREATE INDEX IDX_STOCK_RESERVATIONS_TENANT ON sales_stock_reservations (tenant_id)');
        $this->addSql('CREATE INDEX IDX_STOCK_RESERVATIONS_ORDER ON sales_stock_reservations (sales_order_id)');
        $this->addSql('CREATE INDEX IDX_STOCK_RESERVATIONS_ORDER_LINE ON sales_stock_reservations (sales_order_line_id)');
        $this->addSql('CREATE INDEX IDX_STOCK_RESERVATIONS_PRODUCT ON sales_stock_reservations (product_variant_id)');
        $this->addSql('CREATE INDEX IDX_STOCK_RESERVATIONS_WAREHOUSE ON sales_stock_reservations (warehouse_id)');
        $this->addSql('CREATE INDEX IDX_STOCK_RESERVATIONS_STATUS ON sales_stock_reservations (status)');
        $this->addSql('CREATE INDEX IDX_STOCK_RESERVATIONS_RESERVATION_DATE ON sales_stock_reservations (reservation_date)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS sales_stock_reservations');
    }
}
