<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'name' => config('app.name', 'MENUJU'),
    'status' => 'ok',
    'version' => 'foundation',
]));
