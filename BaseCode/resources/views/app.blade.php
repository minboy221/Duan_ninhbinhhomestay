<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

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
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
</head>

<script id="tailwind-config">
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                "colors": {
                    "surface-bright": "#f5f7f9",
                    "on-tertiary-container": "#004d58",
                    "secondary-fixed": "#abdefa",
                    "on-tertiary": "#daf8ff",
                    "surface-tint": "#00628c",
                    "on-secondary": "#e5f5ff",
                    "on-primary-fixed": "#001a29",
                    "error": "#b31b25",
                    "inverse-on-surface": "#9a9d9f",
                    "on-secondary-fixed": "#003d51",
                    "on-surface": "#2c2f31",
                    "primary-dim": "#00557a",
                    "on-secondary-container": "#185167",
                    "on-secondary-fixed-variant": "#245a71",
                    "surface-container-lowest": "#ffffff",
                    "tertiary-dim": "#005864",
                    "outline": "#747779",
                    "secondary-dim": "#1e556c",
                    "tertiary-fixed": "#50e1f9",
                    "error-container": "#fb5151",
                    "tertiary-container": "#50e1f9",
                    "on-error-container": "#570008",
                    "surface-container-highest": "#d9dde0",
                    "inverse-primary": "#57baf6",
                    "tertiary": "#006573",
                    "secondary": "#2d6179",
                    "secondary-fixed-dim": "#9dd0eb",
                    "on-primary-container": "#00344d",
                    "on-error": "#ffefee",
                    "inverse-surface": "#0b0f10",
                    "surface-variant": "#d9dde0",
                    "primary": "#00628c",
                    "primary-container": "#57baf6",
                    "surface": "#f5f7f9",
                    "on-background": "#2c2f31",
                    "on-tertiary-fixed-variant": "#005763",
                    "on-primary": "#e9f4ff",
                    "surface-container": "#e5e9eb",
                    "primary-fixed": "#57baf6",
                    "primary-fixed-dim": "#46ace7",
                    "background": "#f5f7f9",
                    "surface-container-low": "#eef1f3",
                    "surface-dim": "#d0d5d8",
                    "tertiary-fixed-dim": "#3cd2eb",
                    "error-dim": "#9f0519",
                    "secondary-container": "#abdefa",
                    "outline-variant": "#abadaf",
                    "on-primary-fixed-variant": "#003d5a",
                    "on-surface-variant": "#595c5e",
                    "on-tertiary-fixed": "#003840",
                    "surface-container-high": "#dfe3e6"
                },
                "borderRadius": {
                    "DEFAULT": "6px",
                    "md": "8px",
                    "lg": "10px",
                    "xl": "14px",
                    "full": "9999px"
                },
                "fontFamily": {
                    "headline": ["Plus Jakarta Sans"],
                    "body": ["Inter"],
                    "label": ["Inter"]
                }
            },
        },
    }
</script>
<style>
    body {
        font-family: 'Inter', sans-serif;
    }

    h1,
    h2,
    h3,
    .font-headline {
        font-family: 'Plus Jakarta Sans', sans-serif;
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