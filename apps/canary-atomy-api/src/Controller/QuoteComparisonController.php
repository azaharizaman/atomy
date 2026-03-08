<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\QuoteComparisonRequestDto;
use App\Entity\User;
use App\Repository\TenantRepository;
use App\Service\QuoteComparisonApplicationService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class QuoteComparisonController extends AbstractController
{
    public function __construct(
        private readonly TenantRepository $tenantRepository,
        private readonly QuoteComparisonApplicationService $comparisonService,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('/api/{tenantId}/quotes/compare', name: 'quote_compare', methods: ['POST'])]
    public function compare(string $tenantId, Request $request): JsonResponse
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

        // Validate Idempotency-Key length
        $idempotencyKey = $request->headers->get('Idempotency-Key');
        if ($idempotencyKey !== null && strlen($idempotencyKey) > 128) {
            return $this->json(['error' => 'Idempotency-Key header too long (max 128 chars)'], 400);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'Invalid JSON payload'], 400);
        }

        try {
            $requestDto = QuoteComparisonRequestDto::fromPayload(
                $payload,
                $idempotencyKey
            );
            $result = $this->comparisonService->compare(
                $tenantId,
                $requestDto,
                $requestDto->isPreview,
                (string)$user->getId()
            );
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            if ($e->getCode() === 409) {
                return $this->json(['error' => 'Conflict creating comparison run: duplicate idempotency key'], 409);
            }
            $this->logger->error('Quote comparison failed: ' . $e->getMessage(), ['exception' => $e, 'rfq_id' => $payload['rfq_id'] ?? 'unknown']);
            return $this->json(['error' => 'Comparison failed'], 500);
        }

        $status = ($result['idempotent_replay'] ?? false) ? 200 : 201;

        return $this->json($result, $status);
    }
}
