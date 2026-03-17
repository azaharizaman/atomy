<?php

declare(strict_types=1);

return [
    'projects' => (bool) env('FEATURE_PROJECTS_ENABLED', false),
    'tasks' => (bool) env('FEATURE_TASKS_ENABLED', false),
];

