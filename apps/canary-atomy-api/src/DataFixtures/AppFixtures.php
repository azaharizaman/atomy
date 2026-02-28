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
use Nexus\Identity\ValueObjects\RoleEnum;
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
        }

        // 5. Create Tenant-Specific Flags (Overrides)
        foreach ($tenants as $tenant) {
            $flag = new FeatureFlag('auth.mfa_enabled');
            $flag->setTenantId($tenant->getId());
            $flag->setEnabled($tenant->getCode() === 'STARK'); // Only Stark Industries has MFA by default
            $flag->setStrategy(FlagStrategy::TENANT_LIST);
            $manager->persist($flag);

            $betaFlag = new FeatureFlag('api.v2_beta');
            $betaFlag->setTenantId($tenant->getId());
            $betaFlag->setEnabled($tenant->getCode() !== 'INITECH');
            $betaFlag->setStrategy(FlagStrategy::SYSTEM_WIDE);
            $betaFlag->setOverride($tenant->getCode() === 'STARK' ? FlagOverride::FORCE_ON : null);
            $manager->persist($betaFlag);
        }

        // 6. Create Diverse Users
        $usersData = [
            ['email' => 'system@nexus.example.com', 'name' => 'System Admin', 'roles' => ['ROLE_SUPER_ADMIN'], 'tenant' => null, 'status' => UserStatus::ACTIVE],
            ['email' => 'tony@stark.example.com', 'name' => 'Tony Stark', 'roles' => [RoleEnum::TENANT_ADMIN->value], 'tenant' => 'STARK', 'status' => UserStatus::ACTIVE],
            ['email' => 'pepper@stark.example.com', 'name' => 'Pepper Potts', 'roles' => [RoleEnum::TENANT_ADMIN->value], 'tenant' => 'STARK', 'status' => UserStatus::ACTIVE],
            ['email' => 'happy@stark.example.com', 'name' => 'Happy Hogan', 'roles' => [RoleEnum::USER->value], 'tenant' => 'STARK', 'status' => UserStatus::ACTIVE],
            ['email' => 'bruce@wayne.example.com', 'name' => 'Bruce Wayne', 'roles' => [RoleEnum::TENANT_ADMIN->value], 'tenant' => 'WAYNE', 'status' => UserStatus::ACTIVE],
            ['email' => 'alfred@wayne.example.com', 'name' => 'Alfred Pennyworth', 'roles' => [RoleEnum::USER->value], 'tenant' => 'WAYNE', 'status' => UserStatus::ACTIVE],
            ['email' => 'clark@globex.example.com', 'name' => 'Clark Kent', 'roles' => [RoleEnum::USER->value], 'tenant' => 'GLOBEX', 'status' => UserStatus::PENDING_ACTIVATION],
            ['email' => 'lex@oscorp.example.com', 'name' => 'Lex Luthor', 'roles' => [RoleEnum::USER->value], 'tenant' => 'OSCORP', 'status' => UserStatus::SUSPENDED],
        ];

        foreach ($usersData as $data) {
            $user = new User($data['email']);
            $user->setName($data['name']);
            $user->setRoles($data['roles']);
            $user->setStatus($data['status']);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
            
            if ($data['tenant'] !== null) {
                $tenant = array_filter($tenants, fn($t) => $t->getCode() === $data['tenant']);
                $tenant = reset($tenant);
                if ($tenant) {
                    $user->setTenantId($tenant->getId());
                }
            }
            
            $manager->persist($user);
        }

        $manager->flush();
    }
}
