<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4f46e5">
    <link rel="apple-touch-icon" href="/anh/logoPWA192x192.png">
    <link rel="manifest" href="/manifest.webmanifest">
    <title inertia>{{ config('app.name', 'Ninh Bình StayWork') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('anh/logo_icon.png') }}">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    @routes
    @php
        try {
            if (!file_exists(public_path('build/manifest.json'))) {
                if (file_exists(base_path('../public_html/build/manifest.json'))) {
                    app()->usePublicPath(base_path('../public_html'));
                } elseif (file_exists(base_path('public/build/manifest.json'))) {
                    app()->usePublicPath(base_path('public'));
                }
            }
            echo app(\Illuminate\Foundation\Vite::class)(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])->toHtml();
        } catch (\Throwable $e) {
            // Chống crash 500 khi Vite manifest chưa được tìm thấy
        }
    @endphp
    @inertiaHead
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet" />
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "fontFamily": {
                        "sans": ["Arial", "Helvetica", "sans-serif"],
                        "serif": ["Arial", "Helvetica", "sans-serif"],
                        "headline": ["Arial", "Helvetica", "sans-serif"],
                        "body": ["Arial", "Helvetica", "sans-serif"],
                        "label": ["Arial", "Helvetica", "sans-serif"]
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-[#f5f7f9] text-[#2c2f31] antialiased">
<style>
    *,
    *::before,
    *::after,
    html,
    body,
    div,
    span,
    p,
    a,
    h1, h2, h3, h4, h5, h6,
    input,
    select,
    textarea,
    button,
    label,
    table, th, td,
    ul, ol, li,
    header, nav, section, article, aside, footer {
        font-family: Arial, Helvetica, sans-serif !important;
    .glass-card {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(24px);
        border: 1.5px solid rgba(255, 255, 255, 0.4);
    }

    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }

    .ghost-border {
        border: 1px solid rgba(171, 173, 175, 0.15);
    }
</style>
@inertia
</body>

</html>