<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FeatureFlag;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/settings/feature-flags')]
final class FeatureFlagController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'feature_flags_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $flags = $this->entityManager->getRepository(FeatureFlag::class)->findAll();
        
        $data = array_map(fn(FeatureFlag $flag) => [
            'id' => $flag->getId(),
            'name' => $flag->getName(),
            'description' => $flag->getDescription(),
            'enabled' => $flag->isEnabled(),
            'rolloutPercentage' => $flag->getRolloutPercentage(),
            'metadata' => $flag->getMetadata(),
            'createdAt' => $flag->getCreatedAt()?->format('c'),
            'updatedAt' => $flag->getUpdatedAt()?->format('c'),
        ], $flags);
        
        return $this->json(['data' => $data]);
    }

    #[Route('/{id}', name: 'feature_flags_get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        $flag = $this->entityManager->getRepository(FeatureFlag::class)->find($id);
        
        if (!$flag) {
            return $this->json(['error' => 'Feature flag not found'], Response::HTTP_NOT_FOUND);
        }
        
        return $this->json([
            'data' => [
                'id' => $flag->getId(),
                'name' => $flag->getName(),
                'description' => $flag->getDescription(),
                'enabled' => $flag->isEnabled(),
                'rolloutPercentage' => $flag->getRolloutPercentage(),
                'metadata' => $flag->getMetadata(),
                'createdAt' => $flag->getCreatedAt()?->format('c'),
                'updatedAt' => $flag->getUpdatedAt()?->format('c'),
            ]
        ]);
    }

    #[Route('', name: 'feature_flags_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (empty($data['name'])) {
            return $this->json(['error' => 'Name is required'], Response::HTTP_BAD_REQUEST);
        }
        
        $flag = new FeatureFlag();
        $flag->setName($data['name']);
        $flag->setDescription($data['description'] ?? null);
        $flag->setEnabled($data['enabled'] ?? false);
        $flag->setRolloutPercentage($data['rolloutPercentage'] ?? 100);
        $flag->setMetadata($data['metadata'] ?? []);
        
        $this->entityManager->persist($flag);
        $this->entityManager->flush();
        
        return $this->json([
            'data' => [
                'id' => $flag->getId(),
                'name' => $flag->getName(),
                'description' => $flag->getDescription(),
                'enabled' => $flag->isEnabled(),
                'rolloutPercentage' => $flag->getRolloutPercentage(),
                'metadata' => $flag->getMetadata(),
                'createdAt' => $flag->getCreatedAt()?->format('c'),
                'updatedAt' => $flag->getUpdatedAt()?->format('c'),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'feature_flags_update', methods: ['PUT', 'PATCH'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $flag = $this->entityManager->getRepository(FeatureFlag::class)->find($id);
        
        if (!$flag) {
            return $this->json(['error' => 'Feature flag not found'], Response::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['name'])) {
            $flag->setName($data['name']);
        }
        if (array_key_exists('description', $data)) {
            $flag->setDescription($data['description']);
        }
        if (isset($data['enabled'])) {
            $flag->setEnabled($data['enabled']);
        }
        if (isset($data['rolloutPercentage'])) {
            $flag->setRolloutPercentage($data['rolloutPercentage']);
        }
        if (isset($data['metadata'])) {
            $flag->setMetadata($data['metadata']);
        }
        
        $this->entityManager->flush();
        
        return $this->json([
            'data' => [
                'id' => $flag->getId(),
                'name' => $flag->getName(),
                'description' => $flag->getDescription(),
                'enabled' => $flag->isEnabled(),
                'rolloutPercentage' => $flag->getRolloutPercentage(),
                'metadata' => $flag->getMetadata(),
                'createdAt' => $flag->getCreatedAt()?->format('c'),
                'updatedAt' => $flag->getUpdatedAt()?->format('c'),
            ]
        ]);
    }

    #[Route('/{id}', name: 'feature_flags_delete', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $flag = $this->entityManager->getRepository(FeatureFlag::class)->find($id);
        
        if (!$flag) {
            return $this->json(['error' => 'Feature flag not found'], Response::HTTP_NOT_FOUND);
        }
        
        $this->entityManager->remove($flag);
        $this->entityManager->flush();
        
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/toggle', name: 'feature_flags_toggle', methods: ['POST'])]
    public function toggle(string $id): JsonResponse
    {
        $flag = $this->entityManager->getRepository(FeatureFlag::class)->find($id);
        
        if (!$flag) {
            return $this->json(['error' => 'Feature flag not found'], Response::HTTP_NOT_FOUND);
        }
        
        $flag->setEnabled(!$flag->isEnabled());
        $this->entityManager->flush();
        
        return $this->json([
            'data' => [
                'id' => $flag->getId(),
                'name' => $flag->getName(),
                'enabled' => $flag->isEnabled(),
            ]
        ]);
    }
}
