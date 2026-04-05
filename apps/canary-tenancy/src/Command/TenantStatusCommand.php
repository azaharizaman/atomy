<?php

declare(strict_types=1);

namespace App\Command;

use Nexus\TenantOperations\Services\TenantReadinessChecker;
use Nexus\TenantOperations\DataProviders\TenantContextDataProvider;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'tenant:status',
    description: 'Check tenant domain readiness and specific tenant status',
)]
final class TenantStatusCommand extends Command
{
    public function __construct(
        private readonly TenantReadinessChecker $readinessChecker,
        private readonly TenantContextDataProvider $contextProvider,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('code', null, InputOption::VALUE_OPTIONAL, 'Tenant code to check')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Tenant Domain Status Check');

        $readiness = $this->readinessChecker->check();
        
        if ($readiness['ready']) {
            $io->success('Tenant Domain Orchestrator is correctly wired with all adapters.');
        } else {
            $io->error('Tenant Domain Orchestrator has wiring issues:');
            $io->listing($readiness['issues']);
        }

        $code = $input->getOption('code');
        if ($code) {
            $io->section(sprintf('Status for Tenant: %s', $code));
            // In our canary, we'd need to find by code, but getContext takes ID.
            // For now, let's just log that we are checking.
            $io->note('Individual tenant status check is limited in canary (requires ID-to-Code mapping).');
        }

        return $readiness['ready'] ? Command::SUCCESS : Command::FAILURE;
    }
}
