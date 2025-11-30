<?php

declare(strict_types=1);

use Nexus\\Sequencing\\Contracts\\ReservationRepositoryInterface;
use Nexus\\Sequencing\\Contracts\\SequenceDefinitionInterface;
use Nexus\\Sequencing\\Contracts\\SequenceRepositoryInterface;
use Nexus\\Sequencing\\Contracts\\CounterRepositoryInterface;
use Nexus\\Sequencing\\Contracts\\GapRepositoryInterface;
use Nexus\\Sequencing\\Contracts\\SequenceAuditInterface;
use Nexus\\Sequencing\\Services\\SequenceManager;
use Nexus\\Sequencing\\Services\\PatternParser;
use Nexus\\Sequencing\\Services\\CounterService;
use Nexus\\Sequencing\\ValueObjects\\SequenceReservation;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Advanced Usage Example
 * ----------------------
 * Demonstrates reservation workflows, manual voids, and gap reclamation.
 */

final readonly class ReservationWorkflow
{
    public function __construct(
        private SequenceManager $sequenceManager,
        private ReservationRepositoryInterface $reservationRepository,
        private SequenceRepositoryInterface $sequenceRepository,
    ) {}

    public function reserveAndIssue(string $sequenceName, string $scope, int $count): array
    {
        $reservation = $this->sequenceManager->reserve(
            sequenceName: $sequenceName,
            scopeIdentifier: $scope,
            count: $count,
            expiresAt: new DateTimeImmutable('+30 minutes'),
        );

        $numbers = [];
        foreach ($reservation->slots as $slot) {
            $numbers[] = $slot->value;
        }

        // Commit first reserved number by marking slot as consumed.
        $this->sequenceManager->commitReservationSlot($reservation->id, $numbers[0]);

        // Release remaining slots so other workers can reuse them.
        $this->sequenceManager->releaseReservation($reservation->id);

        return $numbers;
    }

    public function voidNumber(string $sequenceName, string $scope, string $number): void
    {
        $sequence = $this->sequenceRepository->findByNameAndScope($sequenceName, $scope);
        $this->sequenceManager->void(
            sequence: $sequence,
            voidedNumber: $number,
            reason: 'customer_cancelled',
        );
    }
}

$container = require __DIR__ . '/../bootstrap/container.php';

$sequenceManager = new SequenceManager(
    sequenceRepository: $container->get(SequenceRepositoryInterface::class),
    counterRepository: $container->get(CounterRepositoryInterface::class),
    gapRepository: $container->get(GapRepositoryInterface::class),
    patternParser: new PatternParser(),
    counterService: new CounterService(
        counterRepository: $container->get(CounterRepositoryInterface::class),
        reservationRepository: $container->get(ReservationRepositoryInterface::class),
    ),
    auditLogger: $container->get(SequenceAuditInterface::class),
);

$workflow = new ReservationWorkflow(
    sequenceManager: $sequenceManager,
    reservationRepository: $container->get(ReservationRepositoryInterface::class),
    sequenceRepository: $container->get(SequenceRepositoryInterface::class),
);

$poNumbers = $workflow->reserveAndIssue('po_number', 'tenant_123', 5);
echo 'Reserved numbers: ' . implode(', ', $poNumbers) . PHP_EOL;

$workflow->voidNumber('po_number', 'tenant_123', $poNumbers[2]);
echo sprintf('Voided number %s and recorded gap.', $poNumbers[2]) . PHP_EOL;
