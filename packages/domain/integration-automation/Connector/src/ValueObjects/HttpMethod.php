<?php

declare(strict_types=1);

namespace Nexus\Connector\ValueObjects;

/**
 * HTTP methods for API requests.
 */
enum HttpMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
}
