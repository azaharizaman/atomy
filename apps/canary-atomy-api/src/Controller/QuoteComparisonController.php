<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\QuoteComparisonRequestDto;
use App\Entity\User;
use App\Repository\TenantRepository;
use App\Service\QuoteComparisonApplicationService;
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
        private readonly QuoteComparisonApplicationService $comparisonService
    ) {
    }

    #[Route('/api/{tenantId}/quotes/compare', name: 'quote_compare', methods: ['POST'])]
    public function compare(string $tenantId, Request $request): JsonResponse
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
            $requestDto = QuoteComparisonRequestDto::fromPayload(
                $payload,
                $request->headers->get('Idempotency-Key')
            );
            $result = $this->comparisonService->compare($tenantId, $requestDto);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Comparison failed', 'details' => $e->getMessage()], 500);
        }

        $status = ($result['idempotent_replay'] ?? false) ? 200 : 201;

        return $this->json($result, $status);
    }
}
