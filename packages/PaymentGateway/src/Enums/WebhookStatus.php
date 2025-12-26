<?php

declare(strict_types=1);

namespace Nexus\PaymentGateway\Enums;

enum WebhookStatus: string
{
    case RECEIVED = 'received';
    case VERIFIED = 'verified';
    case PROCESSING = 'processing';
    case DEDUPED = 'deduped';
    case PROCESSED = 'processed';
    case IGNORED = 'ignored';
    case FAILED = 'failed';
}
