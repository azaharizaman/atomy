<?php

declare(strict_types=1);

return [
    'queue' => env('NOTIFIER_QUEUE', 'default'),
    'from_email' => env('NOTIFIER_FROM_EMAIL', env('MAIL_FROM_ADDRESS', 'no-reply@example.com')),
    'from_name' => env('NOTIFIER_FROM_NAME', env('MAIL_FROM_NAME', 'Atomy')),
];

