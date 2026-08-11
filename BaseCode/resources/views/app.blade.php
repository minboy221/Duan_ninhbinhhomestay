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

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,600;1,700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;family=Plus+Jakarta+Sans:ital,wght@0,500;0,700;0,800;1,500&amp;display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet" />
</head>

<body class="bg-[#f5f7f9] text-[#2c2f31] antialiased">
<style>
    body,
    * {
        font-family: 'Poppins', sans-serif;
    }

    .title span,
    .title1 span,
    .item_thongso h2,
    .pt-header h2 span {
        font-family: 'Cormorant Garamond', serif !important;
    }

    .navbar,
    .btn,
    .btn_xem,
    .dropdown,
    .title1 h2 {
        font-family: 'Inter', sans-serif !important;
    }

    .banner-text {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
    }

    .infor_dangky h2 {
        font-family: 'Roboto', sans-serif !important;
    }

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