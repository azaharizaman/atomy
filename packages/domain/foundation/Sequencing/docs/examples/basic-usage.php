<?php

declare(strict_types=1);

use Nexus\\Sequencing\\Contracts\\SequenceRepositoryInterface;
use Nexus\\Sequencing\\Contracts\\CounterRepositoryInterface;
use Nexus\\Sequencing\\Contracts\\GapRepositoryInterface;
use Nexus\\Sequencing\\Contracts\\ReservationRepositoryInterface;
use Nexus\\Sequencing\\Contracts\\SequenceAuditInterface;
use Nexus\\Sequencing\\Services\\SequenceManager;
use Nexus\\Sequencing\\Services\\PatternParser;
use Nexus\\Sequencing\\Services\\CounterService;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Basic Usage Example
 * -------------------
 * Shows how to wire SequenceManager and generate an invoice number.
 */

// These concrete implementations are registered by your application container.
$sequenceRepository = $container->get(SequenceRepositoryInterface::class);
$counterRepository = $container->get(CounterRepositoryInterface::class);
$gapRepository = $container->get(GapRepositoryInterface::class);
$reservationRepository = $container->get(ReservationRepositoryInterface::class);
$auditLogger = $container->get(SequenceAuditInterface::class);

$sequenceManager = new SequenceManager(
    sequenceRepository: $sequenceRepository,
    counterRepository: $counterRepository,
    gapRepository: $gapRepository,
    patternParser: new PatternParser(),
    counterService: new CounterService($counterRepository, $reservationRepository),
    auditLogger: $auditLogger,
);

$tenantId = 'tenant_01HM4ZC650W';

// Preview the next invoice number without mutating counters.
$preview = $sequenceManager->preview(
    sequenceName: 'invoice_number',
    scopeIdentifier: $tenantId,
    contextVariables: ['DEPARTMENT' => 'SALES']
);

printf("Next invoice would be: %s\n", $preview->value);

// Generate and increment the counter atomically.
$invoiceNumber = $sequenceManager->generate(
    sequenceName: 'invoice_number',
    scopeIdentifier: $tenantId,
    contextVariables: ['DEPARTMENT' => 'SALES']
);

printf("Issued invoice number: %s\n", $invoiceNumber);

// Persist invoice using the generated number as a reference.
$invoiceRepository->save([
    'number' => $invoiceNumber,
    'tenant_id' => $tenantId,
    'total' => 2599_00,
]);

echo "Invoice saved successfully." . PHP_EOL;
