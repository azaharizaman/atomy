<?php

declare(strict_types=1);

namespace Nexus\Vendor\Enums;

enum VendorStatus: string
{
    case Draft = 'draft';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Restricted = 'restricted';
    case Suspended = 'suspended';
    case Archived = 'archived';
}
