<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Workflow Configuration
    |--------------------------------------------------------------------------
    |
    | Configure workflow engine behavior.
    |
    */

    /**
     * Maximum delegation chain depth.
     */
    'max_delegation_depth' => 3,

    /**
     * Self-approval prevention.
     * 
     * If true, users cannot approve their own submissions.
     */
    'prevent_self_approval' => true,

    /**
     * Default task priority.
     */
    'default_task_priority' => 'medium',

    /**
     * Default task due date offset (in days).
     * 
     * If no due date is specified, tasks will be due this many days after creation.
     */
    'default_task_due_days' => 3,

    /**
     * SLA business hours configuration.
     */
    'business_hours' => [
        'enabled' => true,
        'start' => '09:00',
        'end' => '17:00',
        'timezone' => 'Asia/Kuala_Lumpur',
        'working_days' => [1, 2, 3, 4, 5], // Monday to Friday
    ],

    /**
     * Escalation check frequency (in minutes).
     * 
     * How often the escalation job should run.
     */
    'escalation_check_frequency' => 15,

    /**
     * Timer processing batch size.
     * 
     * How many timers to process in a single batch.
     */
    'timer_batch_size' => 100,

    /**
     * Enable workflow audit logging.
     */
    'audit_enabled' => true,

    /**
     * Activity plugins directory.
     * 
     * Custom activity plugins will be auto-discovered from this directory.
     */
    'plugins_path' => app_path('Workflow/Plugins'),

    /**
     * Registered approval strategies.
     */
    'approval_strategies' => [
        'unison' => \Nexus\Workflow\Core\ApprovalStrategies\UnisonStrategy::class,
        'majority' => \Nexus\Workflow\Core\ApprovalStrategies\MajorityStrategy::class,
        'quorum' => \Nexus\Workflow\Core\ApprovalStrategies\QuorumStrategy::class,
        'weighted' => \Nexus\Workflow\Core\ApprovalStrategies\WeightedStrategy::class,
        'first' => \Nexus\Workflow\Core\ApprovalStrategies\FirstStrategy::class,
    ],
];
