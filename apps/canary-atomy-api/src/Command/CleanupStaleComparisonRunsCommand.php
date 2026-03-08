<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\StaleComparisonRunCleanupService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cleanup-stale-comparison-runs',
    description: 'Mark stale and expired comparison runs, recording audit trail entries.',
)]
final class CleanupStaleComparisonRunsCommand extends Command
{
    public function __construct(
        private readonly StaleComparisonRunCleanupService $cleanupService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $count = $this->cleanupService->cleanup();

        if ($count === 0) {
            $io->success('No stale comparison runs found.');
        } else {
            $io->success(sprintf('Marked %d comparison run(s) as stale.', $count));
        }

        return Command::SUCCESS;
    }
}
