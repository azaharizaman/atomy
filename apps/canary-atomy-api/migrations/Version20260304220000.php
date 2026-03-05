<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260304220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add quote comparison run, approval decision, and decision trail tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE quote_comparison_runs (
            id VARCHAR(36) NOT NULL,
            tenant_id VARCHAR(36) NOT NULL,
            rfq_id VARCHAR(64) NOT NULL,
            idempotency_key VARCHAR(128) DEFAULT NULL,
            request_payload JSON NOT NULL,
            matrix_payload JSON NOT NULL,
            scoring_payload JSON NOT NULL,
            approval_payload JSON NOT NULL,
            response_payload JSON NOT NULL,
            status VARCHAR(32) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_QCR_TENANT_RFQ ON quote_comparison_runs (tenant_id, rfq_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_QCR_TENANT_RFQ_IDEMPOTENCY ON quote_comparison_runs (tenant_id, rfq_id, idempotency_key)');

        $this->addSql('CREATE TABLE quote_approval_decisions (
            id VARCHAR(36) NOT NULL,
            comparison_run_id VARCHAR(36) NOT NULL,
            tenant_id VARCHAR(36) NOT NULL,
            rfq_id VARCHAR(64) NOT NULL,
            decision VARCHAR(16) NOT NULL,
            reason TEXT NOT NULL,
            decided_by VARCHAR(128) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_QAD_RUN ON quote_approval_decisions (comparison_run_id)');
        $this->addSql('CREATE INDEX IDX_QAD_TENANT_RFQ ON quote_approval_decisions (tenant_id, rfq_id)');
        $this->addSql('ALTER TABLE quote_approval_decisions ADD CONSTRAINT FK_QAD_RUN FOREIGN KEY (comparison_run_id) REFERENCES quote_comparison_runs (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('CREATE TABLE quote_decision_trail_entries (
            id VARCHAR(36) NOT NULL,
            comparison_run_id VARCHAR(36) NOT NULL,
            tenant_id VARCHAR(36) NOT NULL,
            rfq_id VARCHAR(64) NOT NULL,
            sequence INT NOT NULL,
            event_type VARCHAR(64) NOT NULL,
            payload_hash VARCHAR(64) NOT NULL,
            previous_hash VARCHAR(64) NOT NULL,
            entry_hash VARCHAR(64) NOT NULL,
            occurred_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_QDTE_RUN ON quote_decision_trail_entries (comparison_run_id)');
        $this->addSql('CREATE INDEX IDX_QDTE_TENANT_RFQ ON quote_decision_trail_entries (tenant_id, rfq_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_QDTE_RUN_SEQ ON quote_decision_trail_entries (comparison_run_id, sequence)');
        $this->addSql('ALTER TABLE quote_decision_trail_entries ADD CONSTRAINT FK_QDTE_RUN FOREIGN KEY (comparison_run_id) REFERENCES quote_comparison_runs (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Add CHECK constraints
        $this->addSql("ALTER TABLE quote_approval_decisions ADD CONSTRAINT CHK_QAD_DECISION CHECK (decision IN ('approve', 'reject'))");
        $this->addSql("ALTER TABLE quote_decision_trail_entries ADD CONSTRAINT CHK_QDTE_SEQUENCE CHECK (sequence > 0)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quote_decision_trail_entries DROP CONSTRAINT FK_QDTE_RUN');
        $this->addSql('DROP TABLE quote_decision_trail_entries');
        $this->addSql('ALTER TABLE quote_approval_decisions DROP CONSTRAINT FK_QAD_RUN');
        $this->addSql('DROP TABLE quote_approval_decisions');
        $this->addSql('DROP TABLE quote_comparison_runs');
    }
}
