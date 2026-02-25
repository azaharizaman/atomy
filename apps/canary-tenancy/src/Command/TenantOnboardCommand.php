<?php

declare(strict_types=1);

namespace App\Command;

use Nexus\TenantOperations\Contracts\TenantOnboardingCoordinatorInterface;
use Nexus\TenantOperations\DTOs\TenantOnboardingRequest;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'tenant:onboard',
    description: 'Onboard a new tenant in the canary app',
)]
final class TenantOnboardCommand extends Command
{
    public function __construct(
        private readonly TenantOnboardingCoordinatorInterface $onboardingCoordinator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('code', null, InputOption::VALUE_REQUIRED, 'Tenant unique code')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Tenant display name')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Admin email')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Admin password')
            ->addOption('domain', null, InputOption::VALUE_REQUIRED, 'Tenant domain')
            ->addOption('plan', null, InputOption::VALUE_REQUIRED, 'Subscription plan (starter, professional, enterprise)', 'starter')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $request = new TenantOnboardingRequest(
            tenantCode: $input->getOption('code'),
            tenantName: $input->getOption('name'),
            domain: $input->getOption('domain'),
            adminEmail: $input->getOption('email'),
            adminPassword: $input->getOption('password'),
            plan: $input->getOption('plan'),
        );

        $io->title('Onboarding Tenant: ' . $request->tenantName);

        $result = $this->onboardingCoordinator->onboard($request);

        if ($result->isSuccess()) {
            $io->success(sprintf('Tenant successfully onboarded! ID: %s', $result->tenantId));
            $io->table(
                ['Resource', 'ID'],
                [
                    ['Tenant', $result->tenantId],
                    ['Admin User', $result->adminUserId],
                    ['Company', $result->companyId],
                ]
            );

            return Command::SUCCESS;
        }

        $io->error($result->message ?? 'Onboarding failed');

        if (!empty($result->issues)) {
            $io->section('Issues Encountered:');
            foreach ($result->issues as $issue) {
                $io->text(sprintf('- [%s] %s', $issue['rule'], $issue['message']));
            }
        }

        return Command::FAILURE;
    }
}
