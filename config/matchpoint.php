<?php

return [
    'admin' => [
        'name' => env('MATCHPOINT_ADMIN_NAME', 'Administrador MatchPoint'),
        'email' => env('MATCHPOINT_ADMIN_EMAIL', 'admin@example.com'),
        'password' => env('MATCHPOINT_ADMIN_PASSWORD'),
    ],
    'audit' => [
        'retention_days' => (int) env('MATCHPOINT_AUDIT_RETENTION_DAYS', 365),
    ],
    'registrations' => [
        'queue_threshold_bytes' => (int) env('MATCHPOINT_IMPORT_QUEUE_THRESHOLD_BYTES', 524288),
    ],
];
