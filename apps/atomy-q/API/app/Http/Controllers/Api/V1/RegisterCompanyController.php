<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\JwtServiceInterface;
use App\Exceptions\CompanyOnboardingFailedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterCompanyRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Nexus\TenantOperations\Contracts\TenantCompanyOnboardingCoordinatorInterface;
use Nexus\TenantOperations\DTOs\TenantCompanyOnboardingRequest;
use Nexus\TenantOperations\DTOs\TenantCompanyOnboardingResult;

/**
 * Public company registration endpoint for Atomy-Q alpha onboarding.
 */
final class RegisterCompanyController extends Controller
{
    public function __construct(
        private JwtServiceInterface $jwt,
        private TenantCompanyOnboardingCoordinatorInterface $onboardingCoordinator,
    ) {
    }

    public function store(RegisterCompanyRequest $request): JsonResponse
    {
        try {
            $result = DB::transaction(function () use ($request): TenantCompanyOnboardingResult {
                $onboardingResult = $this->onboardingCoordinator->onboard(
                    new TenantCompanyOnboardingRequest(
                        tenantCode: (string) $request->validated('tenant_code'),
                        companyName: (string) $request->validated('company_name'),
                        ownerName: (string) $request->validated('owner_name'),
                        ownerEmail: (string) $request->validated('owner_email'),
                        ownerPassword: (string) $request->validated('owner_password'),
                        timezone: $request->validated('timezone'),
                        locale: $request->validated('locale'),
                        currency: $request->validated('currency'),
                        metadata: [
                            'source' => 'register-company',
                        ],
                    ),
                );

                if (! $onboardingResult->isSuccess()) {
                    throw CompanyOnboardingFailedException::fromIssues(
                        $onboardingResult->getIssues(),
                        $onboardingResult->getMessage(),
                    );
                }

                return $onboardingResult;
            });
        } catch (CompanyOnboardingFailedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $this->formatIssues($e->getIssues()),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Company onboarding failed',
            ], 500);
        }

        $tenantId = (string) $result->tenantId;
        $ownerUserId = (string) $result->ownerUserId;
        $tenantEmail = (string) $request->validated('owner_email');
        $ownerName = (string) $request->validated('owner_name');

        $accessToken = $this->jwt->issueAccessToken($ownerUserId, $tenantId);
        $refreshToken = $this->jwt->issueRefreshToken($ownerUserId, $tenantId);

        return response()->json([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->jwt->getTtlMinutes() * 60,
            'user' => [
                'id' => $ownerUserId,
                'email' => $tenantEmail,
                'name' => $ownerName,
                'role' => 'admin',
                'tenantId' => $tenantId,
            ],
            'bootstrap' => $result->getData()['bootstrap'] ?? [],
        ]);
    }

    /**
     * @param array<int, array{rule: string, message: string}> $issues
     * @return array<string, array<int, string>>
     */
    private function formatIssues(array $issues): array
    {
        $errors = [];

        foreach ($issues as $issue) {
            $rule = (string) ($issue['rule'] ?? 'onboarding');
            $message = (string) ($issue['message'] ?? 'Company onboarding failed');
            $errors[$rule] ??= [];
            $errors[$rule][] = $message;
        }

        return $errors;
    }
}
