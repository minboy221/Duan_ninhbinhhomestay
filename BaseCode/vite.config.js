import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';
import { VitePWA } from 'vite-plugin-pwa';
import theme from 'tailwindcss/defaultTheme';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        VitePWA({
            outDir: 'public',
            buildBase: '/build/',
            registerType: 'autoUpdate',
            injectRegister: 'false',
            manifest: {
                name: 'Ninh Bình Home Stay',
                short_name: 'NB Homesta',
                description: 'Ứng dụng đặt phòng trọ và quản lý trực tuyến tại Ninh Bình',
                theme_color: '#4f46e5',
                background_color: '#ffffff',
                display: 'standalone',
                start_url: '/',
                scope: '/',
                icons: [
                    {
                        src: '/anh/logoPWA192x192.png',
                        sizes: '192x192',
                        type: 'image/png',
                        purpose: 'any'
                    },
                    {
                        src: '/anh/logoPWA512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any'
                    },
                    {
                        src: '/anh/logoPWA512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'maskable'
                    }
                ]
            },
            workbox: {
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff,woff2}'],
                globIgnores: ['**/storage/**'],
                maximumFileSizeToCacheInBytes: 10 * 1024 * 1024,
            }
        })
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
});