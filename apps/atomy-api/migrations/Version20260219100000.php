<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sales Orders Table - Core sales order entity
 */
final class Version20260219100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create sales_orders table with all required fields for sales order management';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE sales_orders (
            id VARCHAR(26) NOT NULL,
            tenant_id VARCHAR(26) NOT NULL,
            order_number VARCHAR(50) NOT NULL,
            customer_id VARCHAR(26) NOT NULL,
            quotation_id VARCHAR(26) DEFAULT NULL,
            status VARCHAR(30) NOT NULL,
            workflow_instance_id VARCHAR(26) DEFAULT NULL,
            currency_code VARCHAR(3) NOT NULL,
            exchange_rate DECIMAL(18,6) NOT NULL DEFAULT 1,
            exchange_rate_locked_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            subtotal DECIMAL(18,4) NOT NULL DEFAULT 0,
            tax_amount DECIMAL(18,4) NOT NULL DEFAULT 0,
            discount_amount DECIMAL(18,4) NOT NULL DEFAULT 0,
            total DECIMAL(18,4) NOT NULL DEFAULT 0,
            payment_term VARCHAR(50) DEFAULT NULL,
            due_date DATE DEFAULT NULL,
            customer_po_number VARCHAR(100) DEFAULT NULL,
            shipping_address_id VARCHAR(26) DEFAULT NULL,
            billing_address_id VARCHAR(26) DEFAULT NULL,
            warehouse_id VARCHAR(26) DEFAULT NULL,
            salesperson_id VARCHAR(26) DEFAULT NULL,
            commission_percentage DECIMAL(5,2) DEFAULT NULL,
            metadata JSON DEFAULT NULL,
            created_by VARCHAR(26) DEFAULT NULL,
            confirmed_by VARCHAR(26) DEFAULT NULL,
            confirmed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            deleted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Unique constraint on tenant + order_number
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SALES_ORDERS_TENANT_NUMBER ON sales_orders (tenant_id, order_number)');
        
        // Indexes for common queries
        $this->addSql('CREATE INDEX IDX_SALES_ORDERS_TENANT ON sales_orders (tenant_id)');
        $this->addSql('CREATE INDEX IDX_SALES_ORDERS_CUSTOMER ON sales_orders (customer_id)');
        $this->addSql('CREATE INDEX IDX_SALES_ORDERS_STATUS ON sales_orders (status)');
        $this->addSql('CREATE INDEX IDX_SALES_ORDERS_QUOTATION ON sales_orders (quotation_id)');
        $this->addSql('CREATE INDEX IDX_SALES_ORDERS_SALESPERSON ON sales_orders (salesperson_id)');
        $this->addSql('CREATE INDEX IDX_SALES_ORDERS_CREATED_AT ON sales_orders (created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS sales_orders');
    }
}
