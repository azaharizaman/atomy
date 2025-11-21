<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use Illuminate\Support\Facades\Event;
use Nexus\Inventory\Contracts\EventPublisherInterface;

final class LaravelEventPublisher implements EventPublisherInterface
{
    public function publish(object $event): void
    {
        Event::dispatch($event);
    }
}
