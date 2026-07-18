<?php
namespace App\Support;
use Monolog\LogRecord;
class LogContext
{
    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(extra: array_merge($record->extra, [
            'service' => env('APP_NAME', 'recloset'),
            'env' => env('APP_ENV', 'production'),
        ]));
    }
}
