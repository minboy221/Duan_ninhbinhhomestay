import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            colors: {
                "surface-container-lowest": "#ffffff",
                "on-background": "#2c2f31",
                "inverse-on-surface": "#9a9d9f",
                "error-dim": "#9f0519",
                "tertiary-fixed-dim": "#3cd2eb",
                "secondary": "#2d6179",
                "on-primary-container": "#00344d",
                "on-primary": "#e9f4ff",
                "primary": "#00628c",
                "outline-variant": "#abadaf",
                "error-container": "#fb5151",
                "surface-container-highest": "#d9dde0",
                "tertiary-fixed": "#50e1f9",
                "secondary-fixed": "#abdefa",
                "inverse-primary": "#57baf6",
                "secondary-dim": "#1e556c",
                "primary-fixed-dim": "#46ace7",
                "surface-tint": "#00628c",
                "on-primary-fixed-variant": "#003d5a",
                "tertiary-dim": "#005864",
                "surface-bright": "#f5f7f9",
                "on-surface": "#2c2f31",
                "on-tertiary-container": "#004d58",
                "on-surface-variant": "#595c5e",
                "primary-container": "#57baf6",
                "secondary-container": "#abdefa",
                "inverse-surface": "#0b0f10",
                "primary-dim": "#00557a",
                "outline": "#747779",
                "on-secondary-container": "#185167",
                "on-error": "#ffefee",
                "primary-fixed": "#57baf6",
                "on-secondary-fixed": "#003d51",
                "surface": "#f5f7f9",
                "on-secondary": "#e5f5ff",
                "surface-dim": "#d0d5d8",
                "background": "#f5f7f9",
                "on-primary-fixed": "#001a29",
                "on-tertiary-fixed-variant": "#005763",
                "error": "#b31b25",
                "surface-variant": "#d9dde0",
                "secondary-fixed-dim": "#9dd0eb",
                "tertiary-container": "#50e1f9",
                "on-tertiary-fixed": "#003840",
                "surface-container-low": "#eef1f3",
                "tertiary": "#006573",
                "surface-container-high": "#dfe3e6",
                "on-tertiary": "#daf8ff",
                "surface-container": "#e5e9eb",
                "on-error-container": "#570008",
                "on-secondary-fixed-variant": "#245a71"
            },
            borderRadius: {
                "DEFAULT": "6px",
                "md": "8px",
                "lg": "10px",
                "xl": "14px",
                "full": "9999px"
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                headline: ["Plus Jakarta Sans"],
                display: ["Plus Jakarta Sans"],
                body: ["Inter"],
                label: ["Inter"]
            },
        },
    },

    plugins: [forms],
};

