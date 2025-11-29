<?php

declare(strict_types=1);

namespace App\Security\Listener;

use App\Security\Attribute\RequiresPermission;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Listens for controller methods with RequiresPermission attribute and enforces authorization.
 */
#[AsEventListener(event: KernelEvents::CONTROLLER_ARGUMENTS, priority: 10)]
final readonly class RequiresPermissionListener
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function __invoke(ControllerArgumentsEvent $event): void
    {
        $attributes = $event->getAttributes();
        
        // Get RequiresPermission attributes from the controller method
        $permissions = $attributes[RequiresPermission::class] ?? [];
        
        foreach ($permissions as $permission) {
            if (!$permission instanceof RequiresPermission) {
                continue;
            }
            
            if (!$this->authorizationChecker->isGranted($permission->attribute)) {
                throw new AccessDeniedException($permission->message);
            }
        }
    }
}
