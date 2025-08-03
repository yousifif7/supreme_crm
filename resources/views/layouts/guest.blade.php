<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>SR-CRM</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .video-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }

        .content-wrapper {
            position: relative;
            z-index: 1;
        }
    </style>
</head>

<body class="font-sans text-gray-900 antialiased">
    <!-- Background Video -->
    <video autoplay muted loop class="video-background">
        <source src="https://supremeprotection.co.uk/wp-content/uploads/2024/06/Untitled-design.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>

    <!-- Foreground Content -->
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 content-wrapper">
        <div class="w-full sm:max-w-md mt-6 px-6 py-4  shadow-md overflow-hidden sm:rounded-lg" style="background:#0e1b23ab">
            {{ $slot }}
        </div>
    </div>
</body>

</html>
