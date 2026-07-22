<?php

return [

    // Mailer por defecto. En desarrollo sin cuenta Resend usamos 'log'
    // (los correos se escriben en el log). Al tener Resend: MAIL_MAILER=resend.
    'default' => env('MAIL_MAILER', 'log'),

    'mailers' => [

        'resend' => [
            'transport' => 'resend',
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL', 'mail'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => ['resend', 'log'],
        ],
    ],

    // Remitente por defecto de todos los correos.
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'no-reply@recloset.local'),
        'name' => env('MAIL_FROM_NAME', 'ReCloset'),
    ],
];
