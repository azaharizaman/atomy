<?php

declare(strict_types=1);

namespace Nexus\SSO\ValueObjects;

/**
 * SSO protocol enum
 * 
 * Defines supported SSO protocols
 */
enum SsoProtocol: string
{
    case SAML2 = 'saml2';
    case OAuth2 = 'oauth2';
    case OIDC = 'oidc';
}
