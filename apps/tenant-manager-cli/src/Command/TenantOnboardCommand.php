<?php

declare(strict_types=1);

namespace App\Command;

use Nexus\TenantOperations\Coordinators\TenantOnboardingCoordinator;
use Nexus\TenantOperations\DTOs\TenantOnboardingRequest;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'tenant:onboard',
    description: 'Onboard a new tenant into the system.',
)]
class TenantOnboardCommand extends Command
{
    public function __construct(
        private TenantOnboardingCoordinator $onboardingCoordinator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('code', InputArgument::REQUIRED, 'Unique tenant code (e.g. acme)')
            ->addArgument('name', InputArgument::REQUIRED, 'Display name (e.g. Acme Corp)')
            ->addArgument('domain', InputArgument::REQUIRED, 'Primary domain (e.g. acme.com)')
            ->addArgument('admin-email', InputArgument::REQUIRED, 'Admin email address')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Tenant Onboarding');

        $request = new TenantOnboardingRequest(
            tenantCode: $input->getArgument('code'),
            tenantName: $input->getArgument('name'),
            domain: $input->getArgument('domain'),
            adminEmail: $input->getArgument('admin-email'),
            adminPassword: bin2hex(random_bytes(8)), // Auto-generated for canary
            plan: 'professional',
            currency: 'USD',
            timezone: 'UTC',
            language: 'en',
        );

        $io->info("Starting onboarding for '{$request->tenantName}'...");

        $result = $this->onboardingCoordinator->onboard($request);

        if ($result->isSuccess()) {
            $io->success("Tenant onboarded successfully!");
            $io->table(
                ['Field', 'Value'],
                [
                    ['Tenant ID', $result->tenantId],
                    ['Admin User ID', $result->adminUserId],
                    ['Company ID', $result->companyId],
                ]
            );
            return Command::SUCCESS;
        }

        $io->error($result->getMessage());
        foreach ($result->getIssues() as $issue) {
            $io->writeln("- [{$issue['rule']}] {$issue['message']}");
        }

        return Command::FAILURE;
    }
}
