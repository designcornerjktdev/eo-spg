import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import process from 'node:process';
import { defineConfig, loadEnv } from 'vite';

export default defineConfig(({ command, mode }) => {
    // Subfolder deploys (VITE_APP_BASE=/eo-spg) need lazy chunks served from /eo-spg/build/.
    // Read from .env too: Vite does not load .env into process.env for the config file.
    const appBase = (process.env.VITE_APP_BASE ?? loadEnv(mode, process.cwd(), 'VITE_').VITE_APP_BASE ?? '').replace(/\/+$/, '');

    return {
        base: command === 'build' && appBase ? `${appBase}/build/` : undefined,
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.tsx'],
                ssr: 'resources/js/ssr.jsx',
                refresh: true,
            }),
            react(),
            tailwindcss(),
        ],
        esbuild: {
            jsx: 'automatic',
        },
    };
});
