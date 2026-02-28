<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\FeatureFlag;
use App\Entity\Setting;
use App\Entity\Tenant;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Nexus\FeatureFlags\Enums\FlagOverride;
use Nexus\FeatureFlags\Enums\FlagStrategy;
use Nexus\Identity\ValueObjects\UserStatus;
use Nexus\Tenant\Enums\TenantStatus;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // 1. Create Global Feature Flags
        $globalFlags = [
            ['name' => 'system.maintenance', 'enabled' => false, 'strategy' => FlagStrategy::SYSTEM_WIDE],
            ['name' => 'auth.mfa_enabled', 'enabled' => true, 'strategy' => FlagStrategy::SYSTEM_WIDE],
            ['name' => 'api.v2_beta', 'enabled' => true, 'strategy' => FlagStrategy::PERCENTAGE_ROLLOUT, 'value' => 20],
        ];

        foreach ($globalFlags as $data) {
            $flag = new FeatureFlag($data['name']);
            $flag->setEnabled($data['enabled']);
            $flag->setStrategy($data['strategy']);
            if (isset($data['value'])) $flag->setValue($data['value']);
            $manager->persist($flag);
        }

        // 2. Create Global Settings
        $globalSettings = [
            ['key' => 'app.name', 'value' => 'Nexus ERP'],
            ['key' => 'app.timezone', 'value' => 'UTC'],
            ['key' => 'auth.password_min_length', 'value' => 12],
        ];

        foreach ($globalSettings as $data) {
            $setting = new Setting($data['key'], $data['value']);
            $setting->setScope('application');
            $manager->persist($setting);
        }

        // 3. Create Tenants
        $tenantsData = [
            ['code' => 'ACME', 'name' => 'Acme Corporation', 'email' => 'admin@acme.example.com'],
            ['code' => 'GLOBEX', 'name' => 'Globex Corporation', 'email' => 'admin@globex.example.com'],
            ['code' => 'INITECH', 'name' => 'Initech Industries', 'email' => 'admin@initech.example.com'],
            ['code' => 'STARK', 'name' => 'Stark Industries', 'email' => 'admin@stark.example.com'],
            ['code' => 'WAYNE', 'name' => 'Wayne Enterprises', 'email' => 'admin@wayne.example.com'],
            ['code' => 'UMBRELLA', 'name' => 'Umbrella Corporation', 'email' => 'admin@umbrella.example.com'],
            ['code' => 'OSCORP', 'name' => 'Oscorp Technologies', 'email' => 'admin@oscorp.example.com'],
            ['code' => 'BUYNLARGE', 'name' => 'Buy n Large', 'email' => 'admin@bnl.example.com'],
            ['code' => 'VERIDIAN', 'name' => 'Veridian Dynamics', 'email' => 'admin@veridian.example.com'],
            ['code' => 'MASSIVE', 'name' => 'Massive Dynamic', 'email' => 'admin@massive.example.com'],
        ];

        $tenants = [];
        foreach ($tenantsData as $data) {
            $tenant = new Tenant($data['code'], $data['name'], $data['email']);
            $tenant->setStatus($data['code'] === 'OSCORP' ? TenantStatus::Suspended : TenantStatus::Active);
            $tenant->setDomain(strtolower($data['code']) . '.example.com');
            $manager->persist($tenant);
            $tenants[] = $tenant;

            // 4. Create Tenant-Specific Settings
            $currency = new Setting('app.currency', in_array($data['code'], ['ACME', 'STARK', 'WAYNE']) ? 'USD' : 'EUR');
            $currency->setTenantId($tenant->getId());
            $manager->persist($currency);

            $timezone = new Setting('app.timezone', $data['code'] === 'STARK' ? 'America/New_York' : 'UTC');
            $timezone->setTenantId($tenant->getId());
            $manager->persist($timezone);

            // 5. Create Tenant-Specific Flags (Overrides)
            $flag = new FeatureFlag('api.v2_beta');
            $flag->setEnabled($data['code'] !== 'INITECH');
            $flag->setStrategy(FlagStrategy::SYSTEM_WIDE);
            $flag->setOverride($data['code'] === 'STARK' ? FlagOverride::FORCE_ON : null);
            $flag->setTenantId($tenant->getId());
            $manager->persist($flag);

            // 6. Create Users for each Tenant
            $adminUser = new User($data['email']);
            $adminUser->setName($data['name'] . ' Administrator');
            $adminUser->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
            $adminUser->setStatus(UserStatus::ACTIVE);
            $adminUser->setTenantId($tenant->getId());
            $adminUser->setPassword($this->passwordHasher->hashPassword($adminUser, 'password123'));
            $manager->persist($adminUser);

            // Add some regular users (5-10 per tenant)
            $userCount = rand(5, 10);
            for ($i = 1; $i <= $userCount; $i++) {
                $u = new User(strtolower($data['code']) . ".user$i@example.com");
                $u->setName($data['code'] . " User $i");
                $u->setRoles(['ROLE_USER']);
                $u->setStatus(rand(0, 10) > 8 ? UserStatus::SUSPENDED : UserStatus::ACTIVE);
                $u->setTenantId($tenant->getId());
                $u->setPassword($this->passwordHasher->hashPassword($u, 'password123'));
                $manager->persist($u);
            }
        }

        // 7. Create Platform Super Admin
        $admin = new User('admin@nexus.platform');
        $admin->setName('Platform Admin');
        $admin->setRoles(['ROLE_SUPER_ADMIN']);
        $admin->setStatus(UserStatus::ACTIVE);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'nexus-admin-secret'));
        $manager->persist($admin);

        $manager->flush();
    }
}
