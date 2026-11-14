<!doctype html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#f6f0e6">
        <link rel="icon" type="image/png" sizes="192x192" href="/images/brand/menuju-favicon-v2.png">
        <link rel="shortcut icon" type="image/png" href="/images/brand/menuju-favicon-v2.png">
        <link rel="apple-touch-icon" href="/images/brand/menuju-favicon-v2.png">
        <title>{{ config('app.name', 'MENUJU') }} — Rencanakan hari spesial dengan lebih tenang</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div id="app"></div>
    </body>
</html>
