<?php

declare(strict_types=1);

namespace Nexus\Document\ValueObjects;

enum Visibility: string
{
    case Private = 'private';
    case Public = 'public';
}
