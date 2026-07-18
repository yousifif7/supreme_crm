<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ brand_name() }} — {{ brand_tagline() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>{{ brand_title('Login') }}</title>

    <link rel="shortcut icon" type="image/x-icon" href="{{ brand_favicon_url() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/fieldline-theme.css') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="fl-guest antialiased">
    <div class="fl-guest-shell">
        <div class="fl-guest-card">
            {{ $slot }}
        </div>
    </div>
</body>

</html>
