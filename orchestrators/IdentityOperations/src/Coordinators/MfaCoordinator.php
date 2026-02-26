<?php

declare(strict_types=1);

namespace Nexus\IdentityOperations\Coordinators;

use Nexus\IdentityOperations\Contracts\MfaCoordinatorInterface;
use Nexus\IdentityOperations\Contracts\MfaServiceInterface;
use Nexus\IdentityOperations\Contracts\UserContextProviderInterface;
use Nexus\IdentityOperations\DTOs\MfaEnableRequest;
use Nexus\IdentityOperations\DTOs\MfaEnableResult;
use Nexus\IdentityOperations\DTOs\MfaVerifyRequest;
use Nexus\IdentityOperations\DTOs\MfaVerifyResult;
use Nexus\IdentityOperations\DTOs\MfaDisableRequest;
use Nexus\IdentityOperations\DTOs\MfaDisableResult;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use Nexus\IdentityOperations\DTOs\MfaStatusResult;

/**
 * Coordinator for MFA management.
 */
final readonly class MfaCoordinator implements MfaCoordinatorInterface
{
    public function __construct(
        private MfaServiceInterface $mfaService,
        private UserContextProviderInterface $contextDataProvider,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function getName(): string
    {
        return 'MfaCoordinator';
    }

    public function hasRequiredData(string $userId): bool
    {
        return $this->contextDataProvider->userExists($userId);
    }

    public function enable(MfaEnableRequest $request): MfaEnableResult
    {
        $this->logger->info('Processing MFA enable', [
            'user_id' => $request->userId,
            'method' => $request->method->value,
        ]);

        return $this->mfaService->enable($request);
    }

    public function verify(MfaVerifyRequest $request): MfaVerifyResult
    {
        $this->logger->info('Processing MFA verification', [
            'user_id' => $request->userId,
            'method' => $request->method->value,
        ]);

        return $this->mfaService->verify($request);
    }

    public function disable(MfaDisableRequest $request): MfaDisableResult
    {
        $this->logger->info('Processing MFA disable', [
            'user_id' => $request->userId,
        ]);

        return $this->mfaService->disable($request);
    }

    public function getStatus(string $userId, string $tenantId): MfaStatusResult
    {
        return $this->mfaService->getStatus($userId, $tenantId);
    }

    public function generateBackupCodes(string $userId): array
    {
        $this->logger->info('Generating backup codes', [
            'user_id' => $userId,
        ]);

        return $this->mfaService->generateBackupCodes($userId);
    }

    public function validateBackupCode(string $userId, string $code): bool
    {
        return $this->mfaService->validateBackupCode($userId, $code);
    }
}
