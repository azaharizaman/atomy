<?php

declare(strict_types=1);

use Nexus\Laravel\ApprovalOperations\Providers\ApprovalOperationsAdapterServiceProvider;
use Nexus\Laravel\Identity\Providers\IdentityAdapterServiceProvider;
use App\Providers\AppServiceProvider;

return [
    ApprovalOperationsAdapterServiceProvider::class,
    IdentityAdapterServiceProvider::class,
    AppServiceProvider::class,
];
