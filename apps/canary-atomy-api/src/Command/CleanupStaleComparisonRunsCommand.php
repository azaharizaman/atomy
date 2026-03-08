<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\StaleComparisonRunCleanupService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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

    protected function configure(): void
    {
        $this->addOption(
            'stale-hours',
            null,
            InputOption::VALUE_REQUIRED,
            'Override the default inactivity threshold for stale drafts (in hours).'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $staleHours = null;
        if ($input->getOption('stale-hours') !== null) {
            $staleHours = (int)$input->getOption('stale-hours');
            if ($staleHours <= 0) {
                $io->error('--stale-hours must be a positive integer.');
                return Command::FAILURE;
            }
        }

        $count = $this->cleanupService->cleanup($staleHours);

        if ($count === 0) {
            $io->success('No stale comparison runs found.');
        } else {
            $io->success(sprintf(
                'Marked %d comparison run(s) as stale (using %d hour threshold for drafts).',
                $count,
                $staleHours ?? 24
            ));
        }

        return Command::SUCCESS;
    }
}
