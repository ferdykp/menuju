<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('menuju:about', function (): void {
    $this->line('MENUJU new product foundation. See docs/DEVELOPMENT_PROGRESS.md for implementation status.');
})->purpose('Show the MENUJU foundation status');
