<?php

$mailScheme = env('MAIL_SCHEME');
$mailScheme = in_array($mailScheme, ['smtp', 'smtps'], true)
    ? $mailScheme
    : ((int) env('MAIL_PORT', 2525) === 465 ? 'smtps' : 'smtp');

return [
    'default' => env('MAIL_MAILER', 'log'),
    'mailers' => [
        'log' => ['transport' => 'log', 'channel' => env('MAIL_LOG_CHANNEL')],
        'array' => ['transport' => 'array'],
        'smtp' => ['transport' => 'smtp', 'scheme' => $mailScheme, 'url' => env('MAIL_URL'), 'host' => env('MAIL_HOST', '127.0.0.1'), 'port' => env('MAIL_PORT', 2525), 'username' => env('MAIL_USERNAME'), 'password' => env('MAIL_PASSWORD'), 'timeout' => null, 'local_domain' => env('MAIL_EHLO_DOMAIN')],
    ],
    'from' => ['address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'), 'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'MENUJU'))],
];
