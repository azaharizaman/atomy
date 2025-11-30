<?php

declare(strict_types=1);

namespace App\Command;

use Nexus\Domain\Identity\Contracts\PasswordHasherInterface;
use Nexus\Domain\Identity\Contracts\UserRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Create a backend admin user for managing tenants.
 *
 * This command expects an application-level implementation of
 * Nexus\Domain\Identity\Contracts\UserRepositoryInterface to be registered.
 */
#[AsCommand(name: 'app:create-admin', description: 'Create a backend admin user')]
final class CreateAdminUserCommand extends Command
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordHasherInterface $hasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Admin email')
            ->addArgument('name', InputArgument::OPTIONAL, 'Admin name')
            ->addOption('tenant-id', null, InputOption::VALUE_OPTIONAL, 'Tenant ULID to scope admin, optional')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getHelper('question');

        $email = (string) $input->getArgument('email');
        $name = (string) $input->getArgument('name');
        $tenantId = $input->getOption('tenant-id');

        if ($email === '') {
            $q = new Question('Email: ');
            $email = (string) $io->ask($input, $output, $q);
        }

        if ($name === '') {
            $q = new Question('Name: ');
            $name = (string) $io->ask($input, $output, $q);
        }

        $passwordQuestion = new Question('Password (will be hidden): ');
        $passwordQuestion->setHidden(true);
        $passwordQuestion->setHiddenFallback(false);

        $password = (string) $io->ask($input, $output, $passwordQuestion);

        if ($password === '') {
            $output->writeln('<error>Password cannot be empty</error>');
            return self::FAILURE;
        }

        // Hash password
        $hash = $this->hasher->hash($password);

        // Create user via repo — exact method names depend on implementation; we assume create(array) for now
        if (method_exists($this->userRepository, 'create')) {
            $user = $this->userRepository->create([
                'email' => $email,
                'name' => $name,
                'password_hash' => $hash,
                'roles' => ['ROLE_ADMIN'],
                'tenant_id' => $tenantId,
            ]);

            $output->writeln(sprintf('<info>Created admin user %s (id: %s)</info>', $email, is_object($user) && method_exists($user, 'getId') ? $user->getId() : 'unknown'));
            return self::SUCCESS;
        }

        $output->writeln('<error>UserRepository implementation does not support create(array) - implement creation in your app repository</error>');
        return self::FAILURE;
    }
}
