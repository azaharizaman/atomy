<?php

declare(strict_types=1);

namespace App\Services\Receivable;

use App\Models\CustomerInvoice;
use App\Models\CustomerInvoiceLine;
use Nexus\AuditLogger\Contracts\AuditLogManagerInterface;
use Nexus\Currency\Contracts\CurrencyManagerInterface;
use Nexus\Finance\Contracts\GeneralLedgerManagerInterface;
use Nexus\Party\Contracts\PartyRepositoryInterface;
use Nexus\Receivable\Contracts\CreditLimitCheckerInterface;
use Nexus\Receivable\Contracts\CustomerInvoiceInterface;
use Nexus\Receivable\Contracts\CustomerInvoiceRepositoryInterface;
use Nexus\Receivable\Contracts\PaymentAllocationStrategyInterface;
use Nexus\Receivable\Contracts\PaymentProcessorInterface;
use Nexus\Receivable\Contracts\PaymentReceiptRepositoryInterface;
use Nexus\Receivable\Contracts\ReceivableManagerInterface;
use Nexus\Receivable\Enums\InvoiceStatus;
use Nexus\Receivable\Exceptions\InvalidInvoiceException;
use Nexus\Sales\Contracts\SalesOrderRepositoryInterface;
use Nexus\Sequencing\Contracts\SequencingManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Receivable Manager Service
 *
 * Orchestrates invoice creation, GL posting, payment application, and write-offs.
 */
final readonly class ReceivableManager implements ReceivableManagerInterface
{
    public function __construct(
        private CustomerInvoiceRepositoryInterface $invoiceRepository,
        private PaymentReceiptRepositoryInterface $receiptRepository,
        private SalesOrderRepositoryInterface $salesOrderRepository,
        private PartyRepositoryInterface $partyRepository,
        private GeneralLedgerManagerInterface $glManager,
        private CurrencyManagerInterface $currencyManager,
        private SequencingManagerInterface $sequencingManager,
        private CreditLimitCheckerInterface $creditLimitChecker,
        private PaymentProcessorInterface $paymentProcessor,
        private AuditLogManagerInterface $auditLogger,
        private LoggerInterface $logger
    ) {}

    public function createInvoiceFromOrder(string $salesOrderId): CustomerInvoiceInterface
    {
        $order = $this->salesOrderRepository->getById($salesOrderId);

        // Check credit limit before creating invoice
        $this->creditLimitChecker->checkCreditLimit(
            $order->getCustomerId(),
            $order->getTotalAmount(),
            $order->getCurrency()
        );

        // Generate invoice number
        $invoiceNumber = $this->sequencingManager->getNextSequence(
            'customer_invoice',
            $order->getTenantId()
        );

        // Create invoice header
        $invoice = new CustomerInvoice([
            'tenant_id' => $order->getTenantId(),
            'customer_id' => $order->getCustomerId(),
            'sales_order_id' => $order->getId(),
            'invoice_number' => $invoiceNumber,
            'invoice_date' => new \DateTimeImmutable(),
            'due_date' => $this->calculateDueDate($order->getCustomerId()),
            'currency' => $order->getCurrency(),
            'exchange_rate' => 1.0, // Will be updated if multi-currency
            'amount_in_invoice_currency' => $order->getTotalAmount(),
            'subtotal_amount' => $order->getSubtotalAmount(),
            'tax_amount' => $order->getTaxAmount(),
            'discount_amount' => $order->getDiscountAmount(),
            'total_amount' => $order->getTotalAmount(),
            'outstanding_balance' => $order->getTotalAmount(),
            'status' => InvoiceStatus::DRAFT->value,
        ]);

        $this->invoiceRepository->save($invoice);

        // Create invoice lines from order lines
        foreach ($order->getLines() as $orderLine) {
            $invoiceLine = new CustomerInvoiceLine([
                'customer_invoice_id' => $invoice->getId(),
                'line_number' => $orderLine->getLineNumber(),
                'description' => $orderLine->getProductName() ?? $orderLine->getDescription(),
                'quantity' => $orderLine->getQuantity(),
                'unit_price' => $orderLine->getUnitPrice(),
                'line_subtotal' => $orderLine->getLineSubtotal(),
                'line_tax_amount' => $orderLine->getLineTaxAmount(),
                'line_discount_amount' => $orderLine->getLineDiscountAmount(),
                'line_total' => $orderLine->getLineTotal(),
                'gl_account' => $this->getRevenueAccount($orderLine->getProductId() ?? ''),
            ]);

            $invoiceLine->save();
        }

        $this->logger->info('Invoice created from sales order', [
            'invoice_id' => $invoice->getId(),
            'invoice_number' => $invoiceNumber,
            'sales_order_id' => $salesOrderId,
            'customer_id' => $order->getCustomerId(),
            'total_amount' => $order->getTotalAmount(),
        ]);

        $this->auditLogger->log(
            $order->getCustomerId(),
            'invoice_created',
            "Invoice {$invoiceNumber} created from sales order {$order->getOrderNumber()} for " .
            "{$order->getTotalAmount()} {$order->getCurrency()}"
        );

        return $invoice;
    }

    public function postInvoiceToGL(string $invoiceId): void
    {
        $invoice = $this->invoiceRepository->getById($invoiceId);

        if (!$invoice->getStatus()->canBePosted()) {
            throw InvalidInvoiceException::cannotPost($invoiceId, $invoice->getStatus());
        }

        // Create GL entry: Debit AR Control, Credit Revenue
        $glLines = [];

        // Debit: AR Control Account
        $glLines[] = [
            'account' => '1200-AR-CONTROL',
            'debit' => $invoice->getTotalAmount(),
            'credit' => 0.0,
            'description' => "Customer Invoice {$invoice->getInvoiceNumber()}",
        ];

        // Credit: Revenue Accounts (per line)
        foreach ($invoice->getLines() as $line) {
            $glLines[] = [
                'account' => $line->getGlAccount(),
                'debit' => 0.0,
                'credit' => $line->getLineTotal(),
                'description' => $line->getDescription(),
            ];
        }

        $glEntry = $this->glManager->createEntry(
            $invoice->getTenantId(),
            $invoice->getInvoiceDate(),
            "Customer Invoice {$invoice->getInvoiceNumber()}",
            $glLines
        );

        // Update invoice status to POSTED
        if ($invoice instanceof CustomerInvoice) {
            $invoice->status = InvoiceStatus::POSTED->value;
            $invoice->gl_entry_id = $glEntry->getId();
            $this->invoiceRepository->save($invoice);
        }

        $this->logger->info('Invoice posted to GL', [
            'invoice_id' => $invoiceId,
            'invoice_number' => $invoice->getInvoiceNumber(),
            'gl_entry_id' => $glEntry->getId(),
            'total_amount' => $invoice->getTotalAmount(),
        ]);

        $this->auditLogger->log(
            $invoice->getCustomerId(),
            'invoice_posted',
            "Invoice {$invoice->getInvoiceNumber()} posted to general ledger (GL Entry {$glEntry->getEntryNumber()})"
        );
    }

    public function applyPayment(
        string $receiptId,
        PaymentAllocationStrategyInterface $strategy
    ): void {
        // Delegate to PaymentProcessor
        $this->paymentProcessor->allocatePayment($receiptId, $strategy);

        $receipt = $this->receiptRepository->getById($receiptId);

        // Post GL entry: Debit Cash, Credit AR Control
        $cashAccount = $this->getCashAccount($receipt->getPaymentMethod());

        $glLines = [
            [
                'account' => $cashAccount,
                'debit' => $receipt->getAmount(),
                'credit' => 0.0,
                'description' => "Payment Receipt {$receipt->getReceiptNumber()}",
            ],
            [
                'account' => '1200-AR-CONTROL',
                'debit' => 0.0,
                'credit' => $receipt->getAmount(),
                'description' => "Payment Receipt {$receipt->getReceiptNumber()}",
            ],
        ];

        $glEntry = $this->glManager->createEntry(
            $receipt->getTenantId(),
            $receipt->getReceiptDate(),
            "Payment Receipt {$receipt->getReceiptNumber()}",
            $glLines
        );

        $this->logger->info('Payment applied to invoices', [
            'receipt_id' => $receiptId,
            'receipt_number' => $receipt->getReceiptNumber(),
            'gl_entry_id' => $glEntry->getId(),
            'strategy' => $strategy->getName(),
            'allocations' => $receipt->getAllocations(),
        ]);

        $this->auditLogger->log(
            $receipt->getCustomerId(),
            'payment_applied',
            "Payment {$receipt->getReceiptNumber()} ({$receipt->getAmount()} {$receipt->getCurrency()}) " .
            "allocated using {$strategy->getName()} strategy"
        );
    }

    public function writeOffInvoice(string $invoiceId, string $reason): void
    {
        $invoice = $this->invoiceRepository->getById($invoiceId);

        if (!$invoice->getStatus()->canBeWrittenOff()) {
            throw InvalidInvoiceException::cannotWriteOff($invoiceId, $invoice->getStatus());
        }

        $outstandingBalance = $invoice->getOutstandingBalance();

        if ($outstandingBalance <= 0) {
            throw InvalidInvoiceException::noOutstandingBalance($invoiceId);
        }

        // Create GL entry: Debit Bad Debt Expense, Credit AR Control
        $glLines = [
            [
                'account' => '5200-BAD-DEBT-EXPENSE',
                'debit' => $outstandingBalance,
                'credit' => 0.0,
                'description' => "Bad debt write-off: Invoice {$invoice->getInvoiceNumber()}",
            ],
            [
                'account' => '1200-AR-CONTROL',
                'debit' => 0.0,
                'credit' => $outstandingBalance,
                'description' => "Bad debt write-off: Invoice {$invoice->getInvoiceNumber()}",
            ],
        ];

        $glEntry = $this->glManager->createEntry(
            $invoice->getTenantId(),
            new \DateTimeImmutable(),
            "Bad Debt Write-off: Invoice {$invoice->getInvoiceNumber()}",
            $glLines
        );

        // Update invoice
        if ($invoice instanceof CustomerInvoice) {
            $invoice->status = InvoiceStatus::WRITTEN_OFF->value;
            $invoice->outstanding_balance = 0.0;
            $invoice->notes = ($invoice->notes ?? '') . "\n\nWritten off: {$reason}";
            $this->invoiceRepository->save($invoice);
        }

        $this->logger->info('Invoice written off', [
            'invoice_id' => $invoiceId,
            'invoice_number' => $invoice->getInvoiceNumber(),
            'gl_entry_id' => $glEntry->getId(),
            'amount' => $outstandingBalance,
            'reason' => $reason,
        ]);

        $this->auditLogger->log(
            $invoice->getCustomerId(),
            'invoice_written_off',
            "Invoice {$invoice->getInvoiceNumber()} written off: {$outstandingBalance} {$invoice->getCurrency()}. " .
            "Reason: {$reason}"
        );
    }

    public function voidInvoice(string $invoiceId, string $reason): void
    {
        $invoice = $this->invoiceRepository->getById($invoiceId);

        if (!$invoice->getStatus()->canBeVoided()) {
            throw InvalidInvoiceException::cannotVoid($invoiceId, $invoice->getStatus());
        }

        // If invoice was posted, reverse the GL entry
        if ($invoice->getStatus() === InvoiceStatus::POSTED || 
            $invoice->getStatus() === InvoiceStatus::OVERDUE) {
            
            $glLines = [];

            // Credit: AR Control Account (reverse debit)
            $glLines[] = [
                'account' => '1200-AR-CONTROL',
                'debit' => 0.0,
                'credit' => $invoice->getTotalAmount(),
                'description' => "Void Invoice {$invoice->getInvoiceNumber()}",
            ];

            // Debit: Revenue Accounts (reverse credit)
            foreach ($invoice->getLines() as $line) {
                $glLines[] = [
                    'account' => $line->getGlAccount(),
                    'debit' => $line->getLineTotal(),
                    'credit' => 0.0,
                    'description' => "Void: " . $line->getDescription(),
                ];
            }

            $this->glManager->createEntry(
                $invoice->getTenantId(),
                new \DateTimeImmutable(),
                "Void Invoice {$invoice->getInvoiceNumber()}",
                $glLines
            );
        }

        // Update invoice status
        if ($invoice instanceof CustomerInvoice) {
            $invoice->status = InvoiceStatus::VOIDED->value;
            $invoice->outstanding_balance = 0.0;
            $invoice->notes = ($invoice->notes ?? '') . "\n\nVoided: {$reason}";
            $this->invoiceRepository->save($invoice);
        }

        $this->logger->info('Invoice voided', [
            'invoice_id' => $invoiceId,
            'invoice_number' => $invoice->getInvoiceNumber(),
            'reason' => $reason,
        ]);

        $this->auditLogger->log(
            $invoice->getCustomerId(),
            'invoice_voided',
            "Invoice {$invoice->getInvoiceNumber()} voided. Reason: {$reason}"
        );
    }

    public function getById(string $invoiceId): CustomerInvoiceInterface
    {
        return $this->invoiceRepository->getById($invoiceId);
    }

    public function getByNumber(string $tenantId, string $invoiceNumber): ?CustomerInvoiceInterface
    {
        return $this->invoiceRepository->findByNumber($tenantId, $invoiceNumber);
    }

    /**
     * Calculate due date based on customer's credit terms
     */
    private function calculateDueDate(string $customerId): \DateTimeInterface
    {
        $customer = $this->partyRepository->findById($customerId);
        
        if ($customer === null) {
            // Default to Net 30
            return (new \DateTimeImmutable())->modify('+30 days');
        }

        $creditTerm = $customer->getCreditTerm();
        
        if ($creditTerm === null) {
            return (new \DateTimeImmutable())->modify('+30 days');
        }

        $dueDays = $creditTerm->getDueDays();
        
        return (new \DateTimeImmutable())->modify("+{$dueDays} days");
    }

    /**
     * Get revenue GL account for product
     */
    private function getRevenueAccount(string $productId): string
    {
        // TODO: Implement product-to-GL-account mapping
        // For now, return default revenue account
        return '4000-SALES-REVENUE';
    }

    /**
     * Get cash account based on payment method
     */
    private function getCashAccount(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'CASH' => '1010-CASH-ON-HAND',
            'CHECK' => '1020-CASH-IN-BANK',
            'BANK_TRANSFER', 'WIRE_TRANSFER' => '1020-CASH-IN-BANK',
            'CREDIT_CARD', 'DEBIT_CARD' => '1030-MERCHANT-ACCOUNT',
            'PAYPAL', 'STRIPE' => '1040-PAYMENT-GATEWAY',
            default => '1020-CASH-IN-BANK',
        };
    }
}
