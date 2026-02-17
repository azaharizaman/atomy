<?php

declare(strict_types=1);

namespace Nexus\PaymentBank\Enums;

enum ProviderType: string
{
    case PLAID = 'plaid';
    case YODLEE = 'yodlee';
    case TRUELAYER = 'truelayer';
    case NORDIGEN = 'nordigen';
    case GOCARDLESS = 'gocardless';
}
