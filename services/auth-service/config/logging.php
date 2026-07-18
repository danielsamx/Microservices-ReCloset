<?php
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
return [
    'default' => env('LOG_CHANNEL', 'stderr'),
    'channels' => [
        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'info'),
            'handler' => StreamHandler::class,
            'formatter' => JsonFormatter::class,
            'with' => ['stream' => 'php://stderr'],
            'processors' => [App\Support\LogContext::class],
        ],
        'stack' => [
            'driver' => 'stack',
            'channels' => ['stderr'],
        ],
    ],
];
