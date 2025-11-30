<?php

declare(strict_types=1);

namespace Nexus\Connector\ValueObjects;

/**
 * Authentication methods supported by connectors.
 */
enum AuthMethod: string
{
    case API_KEY = 'api_key';
    case BEARER_TOKEN = 'bearer_token';
    case OAUTH2 = 'oauth2';
    case BASIC_AUTH = 'basic_auth';
    case HMAC = 'hmac';
}
