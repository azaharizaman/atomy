<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use App\Entity\FeatureFlag;
use App\Entity\Setting;
use App\Entity\User;
use App\Service\TenantContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Tenant Subscriber.
 * 
 * Automatically sets the tenant context on entities during API operations.
 */
final readonly class TenantSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TenantContext $tenantContext
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['setTenantContext', EventPriorities::PRE_WRITE],
        ];
    }

    public function setTenantContext(ViewEvent $event): void
    {
        $entity = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (!$entity instanceof FeatureFlag && !$entity instanceof Setting && !$entity instanceof User) {
            return;
        }

        if ($method !== 'POST') {
            return;
        }

        $tenantId = $this->tenantContext->getCurrentTenantId();
        
        if ($tenantId !== null && method_exists($entity, 'setTenantId')) {
            $entity->setTenantId($tenantId);
        }
    }
}
