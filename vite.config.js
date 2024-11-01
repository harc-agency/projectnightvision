import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
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
    ],
    server: {
        host: '0.0.0.0', // Exposes Vite to all network interfaces
        port: 5173,      // Ensures the port is 5173 as configured in Docker
        hmr: {
            host: 'localhost', // Use 'localhost' for Hot Module Replacement
        },
    },
});
