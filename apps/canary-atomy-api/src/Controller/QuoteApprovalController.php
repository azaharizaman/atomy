<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\QuoteApprovalRequestDto;
use App\Entity\User;
use App\Exception\ComparisonRunNotFoundException;
use App\Exception\ComparisonRunNotPendingApprovalException;
use App\Repository\TenantRepository;
use App\Service\QuoteApprovalApplicationService;
use Psr\Log\LoggerInterface;
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
        private readonly QuoteApprovalApplicationService $approvalService,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('/api/{tenantId}/quotes/{runId}/approval', name: 'quote_approval', methods: ['POST'])]
    public function approve(string $tenantId, string $runId, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Authentication required'], 401);
        }

        // Verify user belongs to the tenant
        if ((string)$user->getTenantId() !== $tenantId) {
            return $this->json(['error' => 'Forbidden: Cross-tenant access is not allowed'], 403);
        }

        // Verify tenant exists
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
        } catch (ComparisonRunNotFoundException $e) {
            return $this->json(['error' => 'Quote comparison run not found'], 404);
        } catch (ComparisonRunNotPendingApprovalException $e) {
            return $this->json(['error' => 'Quote comparison run is not pending approval'], 409);
        } catch (\RuntimeException $e) {
            if ($e->getCode() === 409) {
                return $this->json(['error' => 'Approval conflict: this run was already decided'], 409);
            }
            $this->logger->error('Quote approval failed: ' . $e->getMessage(), ['exception' => $e, 'runId' => $runId]);
            return $this->json(['error' => 'Approval failed'], 500);
        } catch (\Throwable $e) {
            $this->logger->error('Unexpected error during quote approval: ' . $e->getMessage(), ['exception' => $e, 'runId' => $runId]);
            return $this->json(['error' => 'Approval failed'], 500);
        }

        return $this->json($result, 200);
    }
}
