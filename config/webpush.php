<?php

return [
    /*
    |--------------------------------------------------------------------------
    | VAPID Keys for Web Push Notifications
    |--------------------------------------------------------------------------
    |
    | VAPID (Voluntary Application Server Identification) keys are used to
    | identify your application server to the browser push service.
    |
    */
    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:admin@dev.local'),
        'public_key' => env('VAPID_PUBLIC_KEY', 'BKdki4m_9EqurDdP5MfvxWZlNemb5cRI9OziB4rf8ZcXS86GTGMaoh0-71UEEjtCLpWqskjsYpe1r5idA-aAAcY'),
        'private_key' => env('VAPID_PRIVATE_KEY', 'Dvj9ylg3kQSNGCv2UXvf7CpeQKn76vX3lJiqCsduoy4'),
        'pem_file' => env('VAPID_PEM_FILE', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Push Notification Options
    |--------------------------------------------------------------------------
    */
    'client_options' => [
        'timeout' => 15,
        'verify' => false, // Set to false for local dev environments with self-signed SSL
    ],

    'default_options' => [
        'TTL' => 86400, // 1 day
        'urgency' => 'high',
    ],
];
