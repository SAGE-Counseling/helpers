<?php

return [
    'admin' => [
        'email' => env('SAGE_ADMIN_EMAIL'),
        'name' => env('SAGE_ADMIN_NAME'),
    ],
    'teams' => [
        'webhook_url' => env('SAGE_TEAMS_WEBHOOK_URL'),
    ],
];
