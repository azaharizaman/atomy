<?php

declare(strict_types=1);

use Nexus\Laravel\ApprovalOperations\Providers\ApprovalOperationsAdapterServiceProvider;
use App\Providers\AppServiceProvider;

return [
    ApprovalOperationsAdapterServiceProvider::class,
    AppServiceProvider::class,
];
