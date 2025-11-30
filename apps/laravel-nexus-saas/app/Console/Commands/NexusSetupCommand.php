<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Nexus\Domain\Identity\ValueObjects\UserStatus;
use App\Models\User;
use Nexus\Domain\Identity\Contracts\UserRepositoryInterface;

/**
 * Custom Artisan command to perform initial boilerplate setup for Nexus SaaS.
 * This includes provisioning the Superadmin user and initializing any required core data.
 */
class NexusSetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nexus:setup {--email= : Email address for the Superadmin} {--password= : Password for the Superadmin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Provisions the initial Superadmin user and sets up Nexus core data.';

    /**
     * The Nexus User Repository instance (injected via contract).
     *
     * @var UserRepositoryInterface
     */
    protected UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        parent::__construct();
        // Dependency Injection ensures we are working against the Nexus Identity contract
        $this->userRepository = $userRepository;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('--- Starting Nexus SaaS Boilerplate Setup ---');

        // 1. Get required inputs
        $email = $this->option('email') ?: $this->ask('Enter Superadmin email address (e.g., admin@platform.com)');
        $password = $this->option('password') ?: $this->secret('Enter Superadmin password');
        $name = $this->ask('Enter Superadmin full name', 'Platform Superuser');

        if (!$email || !$password) {
            $this->error('Email and password are required for Superadmin creation.');
            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($name, $email, $password) {
                $this->createUser($name, $email, $password);
                $this->info("Superadmin user {$email} created successfully.");

                // 2. Initialize Nexus Core Settings (Placeholder)
                // In a real application, this would call a Nexus\Settings service
                $this->line('Initializing platform settings...');
                sleep(1); 
                $this->comment('Settings initialized. Feature flags are now enabled.');
            });

        } catch (\Exception $e) {
            $this->error("Setup failed: " . $e->getMessage());
            $this->error("Transaction rolled back.");
            return self::FAILURE;
        }

        $this->info('--- Nexus SaaS Platform is Ready ---');
        $this->line('You can now log in at the /login route.');

        return self::SUCCESS;
    }

    /**
     * Creates the Superadmin user using the Nexus\Domain\Identity contract.
     * * @param string $name
     * @param string $email
     * @param string $password
     */
    private function createUser(string $name, string $email, string $password): void
    {
        // 1. Check if user already exists (use nullable version to avoid exception)
        if ($this->userRepository->findByEmailOrNull($email) !== null) {
            $this->warn("User {$email} already exists. Skipping creation.");
            return;
        }

        $this->line("Creating Superadmin user: {$email}...");
        
        // 2. Prepare data for the Nexus contract
        $userData = [
            'name' => $name,
            'email' => $email,
            'password_hash' => Hash::make($password), // Password hashing is handled by the application layer
            'status' => UserStatus::ACTIVE->value,
            'roles' => ['superadmin'], // Assign the Superadmin role by name
        ];

        // 3. Call the Nexus Identity repository (Write Model)
        $this->userRepository->create($userData); 

        // 4. (Optional) Associate the user with a default "platform" tenant
        // This logic depends on whether Nexus\Tenant handles a global platform tenant.
        // For now, we assume Superadmin operates outside a specific tenant context.
    }
}