<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('menuju:about', function (): void {
    $this->line('MENUJU new product foundation. See docs/DEVELOPMENT_PROGRESS.md for implementation status.');
})->purpose('Show the MENUJU foundation status');

Artisan::command('menuju:production-check', function (): int {
    $appUrl = (string) config('app.url');
    $appHost = parse_url($appUrl, PHP_URL_HOST);
    $localHost = ! is_string($appHost)
        || in_array(strtolower($appHost), ['localhost', 'example.com'], true)
        || preg_match('/\.(local|localhost|test|example|invalid)$/i', $appHost) === 1
        || filter_var($appHost, FILTER_VALIDATE_IP) !== false;

    $checks = [
        'APP_ENV is production' => app()->environment('production'),
        'APP_DEBUG is disabled' => config('app.debug') === false,
        'APP_KEY is configured' => filled(config('app.key')),
        'APP_URL uses a public HTTPS domain' => parse_url($appUrl, PHP_URL_SCHEME) === 'https' && ! $localHost,
        'Database uses MySQL with a non-root account' => config('database.default') === 'mysql'
            && filled(config('database.connections.mysql.username'))
            && config('database.connections.mysql.username') !== 'root'
            && filled(config('database.connections.mysql.password')),
        'Sessions use encrypted database storage and secure cookies' => config('session.driver') === 'database'
            && config('session.encrypt') === true
            && config('session.secure') === true,
        'SMTP sender is configured' => config('mail.default') === 'smtp'
            && filled(config('mail.mailers.smtp.host'))
            && filled(config('mail.from.address'))
            && filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL) !== false,
        'Google OAuth credentials and production callback are configured' => filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && config('services.google.redirect_uri') === rtrim((string) config('app.url'), '/').'/auth/google/callback',
    ];

    foreach ($checks as $label => $passed) {
        $this->line(($passed ? '<info>PASS</info> ' : '<error>FAIL</error> ').$label);
    }

    $failed = count(array_filter($checks, fn (bool $passed): bool => ! $passed));
    $this->newLine();
    $this->line($failed === 0
        ? 'Configuration checks passed. This does not send a test email or verify Google/provider-side settings.'
        : $failed.' production configuration check(s) need attention. No secret values were printed.');

    return $failed === 0 ? self::SUCCESS : self::FAILURE;
})->purpose('Check required MENUJU production environment settings without exposing secrets');
