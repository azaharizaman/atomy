<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\QuoteApprovalRequestDto;
use App\Entity\User;
use App\Repository\TenantRepository;
use App\Service\QuoteApprovalApplicationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class QuoteApprovalController extends AbstractController
{
    public function __construct(
        private readonly TenantRepository $tenantRepository,
        private readonly QuoteApprovalApplicationService $approvalService
    ) {
    }

    #[Route('/api/{tenantId}/quotes/{runId}/approval', name: 'quote_approval', methods: ['POST'])]
    public function approve(string $tenantId, string $runId, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Authentication required'], 401);
        }

        if ($this->tenantRepository->findById($tenantId) === null) {
            return $this->json(['error' => 'Tenant not found'], 404);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'Invalid JSON payload'], 400);
        }

        try {
            $requestDto = QuoteApprovalRequestDto::fromPayload($payload);
            $result = $this->approvalService->decide(
                $tenantId,
                $runId,
                $requestDto->decision,
                $requestDto->reason,
                $user->getUserIdentifier()
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\RuntimeException $e) {
            if (str_contains(strtolower($e->getMessage()), 'not found')) {
                return $this->json(['error' => $e->getMessage()], 404);
            }

            return $this->json(['error' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Approval failed', 'details' => $e->getMessage()], 500);
        }

        return $this->json($result, 200);
    }
}
