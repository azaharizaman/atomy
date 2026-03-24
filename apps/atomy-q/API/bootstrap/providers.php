<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Nexus\Laravel\ApprovalOperations\Providers\ApprovalOperationsAdapterServiceProvider;

return [
    ApprovalOperationsAdapterServiceProvider::class,
    AppServiceProvider::class,
];
