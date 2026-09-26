<?php

return [
    'admin' => [
        'email' => env('SAGE_ADMIN_EMAIL'),
        'name' => env('SAGE_ADMIN_NAME'),
    ],
    'teams' => [
        'webhook_url' => env('SAGE_TEAMS_WEBHOOK_URL'),
        // Shown in the Teams card heading, e.g. "URGENT — RPS (Testing Environment)".
        'app_label' => env('SAGE_TEAMS_APP_LABEL', env('APP_NAME')),
    ],
];
