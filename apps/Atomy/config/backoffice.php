<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Backoffice Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for organizational structure management.
    |
    */

    'hierarchy' => [
        // Maximum department hierarchy depth
        'max_department_depth' => 8,

        // Maximum supervisor chain depth
        'max_supervisor_chain' => 15,

        // Maximum company hierarchy depth
        'max_company_depth' => 5,
    ],

    'transfer' => [
        // Maximum retroactive days for transfer effective date
        'max_retroactive_days' => 30,

        // Auto-complete transfers on effective date
        'auto_complete' => true,

        // Require approval for all transfers
        'require_approval' => true,
    ],

    'validation' => [
        // Prevent circular references
        'prevent_circular_references' => true,

        // Validate supervisor in same or parent unit
        'validate_supervisor_hierarchy' => true,

        // Allow multiple primary assignments
        'allow_multiple_primary_assignments' => false,
    ],

    'office' => [
        // Only one head office per company
        'enforce_single_head_office' => true,

        // Require physical address for non-virtual offices
        'require_physical_address' => true,
    ],

    'audit' => [
        // Enable audit logging for organizational changes
        'enabled' => true,

        // Audit events to log
        'events' => [
            'company_created',
            'company_updated',
            'company_deleted',
            'office_created',
            'office_updated',
            'office_deleted',
            'department_created',
            'department_updated',
            'department_deleted',
            'staff_created',
            'staff_updated',
            'staff_deleted',
            'staff_transferred',
            'unit_created',
            'unit_updated',
            'unit_deleted',
        ],
    ],
];
